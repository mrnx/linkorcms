<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(!$user->CheckAccess2('gallery', 'gallery')){
	AddTextBox('������', $config['general']['admin_accd']);
	return;
}

TAddSubTitle('�����������');

include_once ($config['inc_dir'].'tree_a.class.php');
$tree = new AdminTree('gallery_cats');
$tree->module = 'gallery';
$tree->obj_table = 'gallery';
$tree->obj_cat_coll = 'cat_id';
$tree->showcats_met = 'cats';
$tree->edit_met = 'cateditor';
$tree->save_met = 'catsave';
$tree->del_met = 'delcat';
$tree->action_par_name = 'a';
$tree->id_par_name = 'id';
$editimages = $user->CheckAccess2('gallery', 'edit_images');
$editcats = $user->CheckAccess2('gallery', 'edit_cats');
$editconf = $user->CheckAccess2('gallery', 'config');
$GalleryDir = $config['gallery']['gallery_dir'];
$ThumbsDir = $config['gallery']['thumbs_dir'];

include_once ($config['inc_dir'].'configuration/functions.php');

if(isset($_GET['a'])){
	$action = $_GET['a'];
}else{
	$action = 'main';
}

TAddToolLink('�����������', 'main', 'gallery');
if($editimages){
	TAddToolLink('�������� �����������', 'editor', 'gallery&a=editor');
}
TAddToolBox($action);

if($editcats){
	TAddToolLink('���������', 'cats', 'gallery&a=cats');
	TAddToolLink('�������� ���������', 'cateditor', 'gallery&a=cateditor');
}
TAddToolBox($action);

if($editconf){
	TAddToolLink('���������', 'config', 'gallery&a=config');
}
TAddToolBox($action);

switch($action){
	case 'main':
		AdminGalleryMainFunc();
		break;
	case 'editor':
		AdminGalleryEditor();
		break;
	case 'add':
	case 'save':
		AdminGallerySaveImage($action);
		break;
	case 'changestatus':
		AdminGalleryChangeStatus();
		break;
	case 'delete':
		AdminGalleryDeleteImage();
		break;
	case 'resethits':
		AdminGalleryResetHits();
		break;
	case 'resetrating':
		AdminArticlesResetRating();
		break;
	////////////////// ���������
	case 'cats':
		if(!$editcats){
			AddTextBox('������', $config['general']['admin_accd']);
		}
		global $tree;
		$result = $tree->ShowCats();
		if($result == false){
			$result = '��� ��������� ��� �����������.';
		}
		AddTextBox('���������', $result);
		break;
	case 'cateditor':
		if(!$editcats){
			AddTextBox('������', $config['general']['admin_accd']);
		}
		global $tree;
		if(isset($_GET['id'])){
			$id = SafeEnv($_GET['id'], 11, str);
		}else{
			$id = null;
		}
		if(isset($_GET['to'])){
			$to = SafeEnv($_GET['to'], 11, str);
		}else{
			$to = null;
		}
		$text = $tree->CatEditor($id, $to);
		break;
	case 'catsave':
		if(!$editcats){
			AddTextBox('������', $config['general']['admin_accd']);
		}
		global $tree, $config;
		$tree->EditorSave((isset($_GET['id']) ? SafeEnv($_GET['id'], 11, int) : null));
		GO($config['admin_file'].'?exe=gallery&a=cats');
		break;
	case 'delcat':
		if(!$editcats){
			AddTextBox('������', $config['general']['admin_accd']);
		}
		global $tree, $config;
		if($tree->DeleteCat(SafeEnv($_GET['id'], 11, int))){
			GO($config['admin_file'].'?exe=gallery&a=cats');
		}
		break;
	////////////////// ���������
	case 'config':
		if(!$editconf){
			AddTextBox('������', $config['general']['admin_accd']);
		}
		AdminConfigurationEdit('gallery', 'gallery', false, false, '������������ ������ "�������"');
		break;
	case 'configsave':
		if(!$editconf){
			AddTextBox('������', $config['general']['admin_accd']);
		}
		AdminConfigurationSave('gallery&a=config', 'gallery', false);
		break;
	////////
	case 'refreshthumb':
		AdminGalleryThumbRefresh();
		break;
}

function AdminGalleryMainFunc(){
	global $config, $db, $tree, $site, $user, $editimages, $GalleryDir, $ThumbsDir;
	$vrating = false;
	if(isset($_GET['cat']) && $_GET['cat'] > -1){
		$cat = SafeEnv($_GET['cat'], 11, int);
		$where = "`cat_id`='$cat'";
	}else{
		$cat = -1;
		$where = "";
	}
	$data = array();
	$data = $tree->GetCatsData($cat, true);
	$site->DataAdd($data, -1, '��� �����������', $cat == -1);
	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 11, int);
	}else{
		$page = 1;
	}
	AddCenterBox('����');
	$text = '';
	$text = '<form name="categories" method="get">'.'<table cellspacing="0" cellpadding="0" border="0" width="100%" align="center"><tr><td align="center" class="contenttd">'.'�������� ���������: '.$site->Hidden('exe', 'gallery').$site->Select('cat', $data).$site->Submit('��������').'</td></tr></table></form><br />';
	AddText($text);
	$r = $db->Select('gallery', $where);

	if(count($r) > $config['gallery']['images_on_page']){
		$navigator = new Navigation($page);
		$navigator->GenNavigationMenu($r, $config['gallery']['images_on_page'], $config['admin_file'].'?exe=gallery'.($cat > 0 ? '&cat='.$cat : ''));
		AddNavigation();
		$nav = true;
	}else{
		$nav = false;
		AddText('<br />');
	}
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>�����������</th><th>����������</th><th>�����������</th>'.($vrating ? '<th>������</th>' : '').'<th>�������������</th><th>������</th><th>�������</th></tr>';
	foreach($r as $img){
		$id = SafeDB($img['id'], 11, int);
		switch($img['show']){
			case '1':
				$st = '<font color="#008000">���.</font></a>';
				break;
			case '0':
				$st = '<font color="#FF0000">����.</font>';
				break;
		}
		if($editimages){
			$st = '<a href="'.$config['admin_file'].'?exe=gallery&a=changestatus&id='.SafeDB($img['id'], 11, int).'">'.$st.'</a>';
		}
		if($editimages){
			$func = '';
			$func .= SpeedButton('�������� �����', $config['admin_file'].'?exe=gallery&a=refreshthumb&id='.$id, 'images/admin/refresh.png');
			$func .= SpeedButton('�������������', $config['admin_file'].'?exe=gallery&a=editor&id='.$id, 'images/admin/edit.png');
			$func .= SpeedButton('�������', $config['admin_file'].'?exe=gallery&a=delete&id='.$id.'&ok=0', 'images/admin/delete.png');
		}else{
			$func = '-';
		}
		$filename = SafeDB($img['file'], 255, str);
		$size = FormatFileSize(filesize($GalleryDir.$filename));
		$asize = getimagesize($GalleryDir.$filename);
		$asize = $asize[0].'x'.$asize[1];
		$vi = ViewLevelToStr(SafeDB($img['view'], 1, int));
			//$rating = '<img src="'.GetRatingImage($img[14],$img[15]).'" border="0" />/ (����� '.$img[14].')'.($editimages?' / <a href="'.$config['admin_file'].'?exe=gallery&a=resetrating&id='.$img[0].'" title="�������� ������� ������">�����</a>':'');
		$text .= '<tr>
		<td><a href="'.$GalleryDir.SafeDB($img['file'], 255, str).'" target="_blank">'.($config['gallery']['show_thumbs'] == 0 ? '<b>'.SafeDB($img['title'], 255, str).'</b>' : '<img title="'.SafeDB($img['title'], 255, str).'" src="'.$ThumbsDir.$filename.'" />
			<br /><b>'.SafeDB($img['title'], 255, str).'</b>')." ($asize, $size)".'</a>
		</td>
		<td>'.SafeDB($img['hits'], 11, int).($editimages ? ' / <a href="'.$config['admin_file'].'?exe=gallery&a=resethits&id='.$id.'" title="�������� �������">�����</a>' : '').'</td>
		<td>'.SafeDB($img['com_counter'], 11, int).'</td>
		<td>'.$vi.'</td>
		<td>'.$st.'</td>
		<td>'.$func.'</td>
		</tr>';
	}
	$text .= '</table>';
	AddText($text);
	if($nav){
		AddNavigation();
	}
}

function AdminGalleryEditor(){
	global $tree, $site, $config, $db, $user, $editimages;
	if(!$editimages){
		AddTextBox('������', $config['general']['admin_accd']);
		return;
	}
	$cat_id = 0;
	$author = '';
	$email = '';
	$www = '';
	$title = '';
	$description = '';
	$file = '';
	$allow_comments = true;
	$allow_votes = true;
	$view = array(1=>false, 2=>false, 3=>false, 4=>false);
	$show = true;
	if(!isset($_GET['id'])){
		$view[4] = true;
		$action = 'add';
		$top = '���������� �����������';
		$cap = '��������';
	}else{
		$id = SafeEnv($_GET['id'], 11, int);
		$db->Select('gallery', "`id`='$id'");
		$par = $db->FetchRow();
		$cat_id = SafeDB($par['cat_id'], 11, int);
		$author = SafeDB($par['author'], 50, str);
		$email = SafeDB($par['email'], 50, str);
		$www = SafeDB($par['site'], 250, str);
		$title = SafeDB($par['title'], 255, str);
		$description = SafeDB($par['description'], 0, str, false);
		$file = SafeDB($par['file'], 255, str);
		$allow_comments = SafeDB($par['allow_comments'], 1, bool);
		$allow_votes = SafeDB($par['allow_votes'], 1, bool);
		$show = SafeDB($par['show'], 1, bool);
		$view[SafeDB($par['view'], 1, int)] = true;
		$action = 'save&id='.$id;
		$top = '�������������� �����������';
		$cap = '���������';
		unset($par);
	}
	$visdata = GetUserTypesFormData($view);
	$cats_data = array();
	$cats_data = $tree->GetCatsData($cat_id);
	if(count($cats_data) == 0){
		AddTextBox($top, '��� ��������� ��� ����������! �������� ���������.');
		return;
	}
	FormRow('� ���������', $site->Select('category', $cats_data));
	FormRow('���������', $site->Edit('title', $title, false, 'maxlength="250" style="width:400px;"'));
	FormRow('�����������', $site->Edit('image', $file, false, 'style="width:400px;" maxlength="250"').'<br />'.
		$site->FFile('up_image').'<br /><small>������ ����������� ������ *.jpg, *.jpeg, *.gif, *.png</small><br /><small>������������ ������ �����: '.ini_get('upload_max_filesize').'</small>');
	FormTextRow('��������', $site->HtmlEditor('description', $description, 600, 200));
	FormRow('�����', $site->Edit('author', $author, false, 'style="width:400px;" maxlength="50"'));
	FormRow('E-mail ������', $site->Edit('email', $email, false, 'style="width:400px;" maxlength="50"'));
	FormRow('���� ������', $site->Edit('www', $www, false, 'style="width:400px;" maxlength="250"'));
	$enData = GetEnData($allow_comments, '���������', '���������');
	FormRow('�����������', $site->Select('allow_comments', $enData));
	$enData = GetEnData($allow_votes, '���������', '���������');
	FormRow('������', $site->Select('allow_votes', $enData));
	FormRow('��� �����', $site->Select('view', $visdata));
	$enData = GetEnData($show, '��', '���');
	FormRow('��������', $site->Select('show', $enData));
	AddCenterBox($top);
	AddForm('<form action="'.$config['admin_file'].'?exe=gallery&a='.$action.'" method="post" enctype="multipart/form-data">', $site->Button('������', 'onclick="history.go(-1)"').$site->Submit($cap));
}

function AdminGallerySaveImage(){
	global $db, $config, $tree, $GalleryDir, $ThumbsDir;
	$alloy_mime = array('image/gif'=>'.gif', 'image/jpeg'=>'.jpg', 'image/pjpeg'=>'.jpg', 'image/png'=>'.png', 'image/x-png'=>'.png');
	$ThumbsDir = $config['gallery']['thumbs_dir'];
	$cat_id = SafeEnv($_POST['category'], 11, int);
	$title = SafeEnv($_POST['title'], 255, str);
	$file = SafeEnv($_POST['image'], 255, str);
	$desc = SafeEnv($_POST['description'], 0, str);
	$author = SafeEnv($_POST['author'], 50, str);
	$email = SafeEnv($_POST['email'], 50, str);
	$site = SafeEnv(url($_POST['www']), 250, str);
	$allow_comments = EnToInt($_POST['allow_comments']);
	$allow_votes = EnToInt($_POST['allow_votes']);
	$view = ViewLevelToInt($_POST['view']);
	$show = EnToInt($_POST['show']);
	// �����������
	// ��������� �����������
	$Error = false;
	$file = LoadImage('up_image', $GalleryDir, $ThumbsDir, $config['gallery']['thumb_max_width'], $config['gallery']['thumb_max_height'], $_POST['image'], $Error);

	if($Error){
		AddTextBox('������', '<center>������������ ������ �����. ����� ��������� ������ ����������� ������� GIF, JPEG ��� PNG.</center>');
		return;
	}
	if(!isset($_GET['id'])){
		$db->Insert('gallery', "'','$cat_id','".time()."','$title','$desc','$file','0','$author','$email','$site','$allow_comments','0','$allow_votes','0','0','$view','$show'");
		if($show){
			$tree->CalcFileCounter($cat_id, true);
		}
	}else{
		// TODO: ��������� ������, �� MySQL �� �������� ���������� ���������. �������� �������� �������� ����������� ���. �������, ��������� ��� ������� �� �������� ��.
		$set = "cat_id='$cat_id',title='$title',description='$desc',file='$file',author='$author',email='$email',site='',allow_comments='$allow_comments',allow_votes='$allow_votes',view='$view',show='$show'";
		$id = SafeEnv($_GET['id'], 11, int);
		$r = $db->Select('gallery', "`id`='$id'");
		if($r[0]['cat_id'] != $cat_id && $r[0]['show'] == '1'){ //���� ����������� � ������ ������
			$tree->CalcFileCounter(SafeDB($r[0]['cat_id'], 11, int), false);
			$tree->CalcFileCounter($cat_id, true);
		}
		if($r[0]['show'] != $show){ // ��������� / ��������
			if($show == 0){
				$tree->CalcFileCounter($cat_id, false);
			}else{
				$tree->CalcFileCounter($cat_id, true);
			}
		}
		if($r[0]['file'] != $file){
			if(is_file($GalleryDir.$r[0]['file'])){
				unlink($GalleryDir.$r[0]['file']);
			}
			if(is_file($ThumbsDir.$r[0]['file'])){
				unlink($ThumbsDir.$r[0]['file']);
			}
		}
		$db->Update('gallery', $set, "`id`='$id'");
	}
	GO($config['admin_file'].'?exe=gallery');
}

function AdminGalleryDeleteImage(){
	global $config, $db, $tree, $user, $editimages, $GalleryDir, $ThumbsDir;
	if(!$editimages){
		AddTextBox('������', $config['general']['admin_accd']);
		return;
	}
	if(!isset($_GET['id'])){
		GO($config['admin_file'].'?exe=gallery');
	}
	if(isset($_GET['ok']) && SafeEnv($_GET['ok'], 1, int) == '1'){
		$id = SafeEnv($_GET['id'], 11, int);
		$r = $db->Select('gallery', "`id`='".$id."'");
		if($db->NumRows() > 0){
			$img = $db->FetchRow();
			$filename = $GalleryDir.SafeDB($img['file'], 255, str);
			if(file_exists($filename) && is_file($filename)){
				unlink($filename);
				unlink($ThumbsDir.SafeDB($img['file'], 255, str));
			}
			$tree->CalcFileCounter(SafeDB($img['cat_id'], 11, int), false);
		}
		$db->Delete('gallery', "`id`='$id'");
		$db->Delete('gallery_comments', "`object_id`='$id'");
		GO($config['admin_file'].'?exe=gallery');
	}else{
		$id = SafeEnv($_GET['id'], 11, int);
		$db->Select('gallery', "`id`='$id'");
		if($db->NumRows() > 0){
			$img = $db->FetchRow();
			$filename = $GalleryDir.SafeDB($img['file'], 255, str);
			$text = '<table cellspacing="0" cellpadding="5" border="0" align="center"><tr><td align="center">'.($config['gallery']['show_thumbs'] == 1 ? '<img width="400" src="'.$filename.'" border="0" /></tr></td><tr><td align="center">' : '').'������� ����������� "'.SafeDB($img['title'], 255, str).'" �� �������?<br />'.'<a href="'.$config['admin_file'].'?exe=gallery&a=delete&id='.$id.'&ok=1">��</a> &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">���</a><br /><br />'.'</td></tr></table>';
		}else{
			$text = '<center>�����������, ������� �� ��������� �������, �� ������� � �������.<br /><a href="javascript:history.go(-1)">����� � �������</a></center>';
		}
		AddTextBox('��������!', $text);
	}
}

function AdminGalleryChangeStatus(){
	global $config, $db, $tree, $user, $editimages;
	if(!$editimages){
		AddTextBox('������', $config['general']['admin_accd']);
		return;
	}
	if(!isset($_GET['id'])){
		GO($config['admin_file'].'?exe=gallery');
	}
	$db->Select('gallery', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	if($db->NumRows() > 0){
		$r = $db->FetchRow();
		if($r['show'] == 1){
			$en = '0';
			$tree->CalcFileCounter(SafeDB($r['cat_id'], 11, int), false);
		}else{
			$en = '1';
			$tree->CalcFileCounter(SafeDB($r['cat_id'], 11, int), true);
		}
		$db->Update('gallery', "show='$en'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	}
	GO($config['admin_file'].'?exe=gallery');
}

function AdminGalleryResetHits(){
	global $config, $db, $user, $editimages;
	if(!$editimages){
		AddTextBox('������', $config['general']['admin_accd']);
		return;
	}
	if(isset($_GET['id'])){
		$db->Update('gallery', "hits='0'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	}
	GO($config['admin_file'].'?exe=gallery');
}

function AdminGalleryThumbRefresh(){
	global $config, $db, $GalleryDir, $ThumbsDir;
	if(!isset($_GET['id'])){
		GoBack();
	}
	$db->Select('gallery', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	if($db->NumRows() > 0){
		$r = $db->FetchRow();
		$file_name = $r['file'];
		if(is_file($ThumbsDir.$file_name)){
			unlink($ThumbsDir.$file_name);
		}
		CreateThumb($GalleryDir.$file_name, $ThumbsDir.$file_name, $config['gallery']['thumb_max_width'], $config['gallery']['thumb_max_height']);
	}
	GoBack();
}
