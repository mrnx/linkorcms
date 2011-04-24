<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(!$user->CheckAccess2('downloads', 'downloads')){
	AddTextBox('������', $config['general']['admin_accd']);
	return;
}

TAddSubTitle('����� ������');

include_once ($config['inc_dir'].'tree_a.class.php');
$tree = new AdminTree('downloads_cats');
$tree->module = 'downloads';
$tree->obj_table = 'downloads';
$tree->obj_cat_coll = 'category';
$tree->showcats_met = 'cats';
$tree->edit_met = 'cateditor';
$tree->save_met = 'catsave';
$tree->del_met = 'delcat';
$tree->action_par_name = 'a';
$tree->id_par_name = 'id';

include_once ($config['apanel_dir'].'configuration/functions.php');

function AdminDownloadsMain()
{
	global $config, $db, $site, $tree, $user;
	if(isset($_GET['cat']) && $_GET['cat'] > -1){
		$cat = SafeEnv($_GET['cat'], 11, int);
		$where = "`category`='$cat'";
	}else{
		$cat = -1;
		$where = "";
	}
	$data = array();
	$data = $tree->GetCatsData($cat, true);
	$site->DataAdd($data, -1, '��� �����', $cat == -1);
	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 10, int);
	}else{
		$page = 1;
	}
	AddCenterBox('�����');
	$text = '';
	$text = '<form name="categories" method="get">'
	.'<table cellspacing="0" cellpadding="0" border="0" width="100%" align="center">
	<tr>
	<td align="center" class="contenttd">'.'�������� ���������: '.$site->Hidden('exe', 'downloads').$site->Select('cat', $data).$site->Submit('��������').'</td>
	</tr>
	</table>
	</form>';

	AddText($text);
	$db->Select('downloads', $where);
	SortArray($db->QueryResult, 'public', true);
	if(count($db->QueryResult) > $config['downloads']['filesonpage']){
		$navigator = new Navigation($page);
		$navigator->GenNavigationMenu($db->QueryResult, $config['downloads']['filesonpage'], $config['admin_file'].'?exe=downloads'.($cat > 0 ? '&cat='.$cat : ''));
		AddNavigation();
		$nav = true;
	}else{
		$nav = false;
		AddText('<br />');
	}
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>���������</th><th>����������</th><th>�����������</th><th>������</th><th>��� �����</th><th>������</th><th>�������</th></tr>';
	$editfiles = $user->CheckAccess2('downloads', 'edit_files');
	// ���������� �������
	while($row = $db->FetchRow()){
		$vi = ViewLevelToStr(SafeDB($row['view'], 1, int));
		switch($row['active']){
			case '1':
				$st = '<font color="#008000">���.</font></a>';
				break;
			case '0':
				$st = '<font color="#FF0000">����.</font>';
				break;
		}
		if($editfiles){
			$st = '<a href="'.$config['admin_file'].'?exe=downloads&a=changestatus&id='.SafeDB($row['id'], 11, int).'">'.$st.'</a>';
		}
		$rating = '<img src="'.GetRatingImage(SafeDB($row['votes_amount'], 11, int), SafeDB($row['votes'], 11, int)).'" border="0" />/ (����� '.SafeDB($row['votes_amount'], 11, int).')'
		.($editfiles ? ' / <a href="'.$config['admin_file'].'?exe=downloads&a=resetrating&id='.SafeDB($row['id'], 11, int).'" title="�������� ������� ������">�����</a>' : '');
		if($editfiles){
			$func = '';
			$func .= SpeedButton('�������������', $config['admin_file'].'?exe=downloads&a=editor&id='.SafeDB($row['id'], 11, int), 'images/admin/edit.png');
			$func .= SpeedButton('�������', $config['admin_file'].'?exe=downloads&a=deletefile&id='.SafeDB($row['id'], 11, int).'&ok=0', 'images/admin/delete.png');
		}else{
			$func = '-';
		}
		$text .= '<tr><td>'.($editfiles ? '<b><a href="'.$config['admin_file'].'?exe=downloads&a=editor&id='.SafeDB($row['id'], 11, int).'">' : '').SafeDB($row['title'], 255, str).($editfiles ? '</a><b>' : '').'</td>
		<td>'.SafeDB($row['hits'], 11, int).($editfiles ? ' / <a href="'.$config['admin_file'].'?exe=downloads&a=resetcounter&id='.SafeDB($row['id'], 11, str).'" title="�������� ������� ����������">�����</a>' : '').'</td>
		<td>'.SafeDB($row['comments_counter'], 11, int).'</a></td>
		<td>'.$rating.'</td>
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

function AdminDownloadsFileEditor( $action )
{
	global $config, $db, $site, $user, $tree;
	if(!$user->CheckAccess2('downloads', 'edit_files')){
		AddTextBox('������', $config['general']['admin_accd']);
		return;
	}
	$category = 0;
	$title = '';
	$url = '';
	$file_size = '0';
	$size_type = 'b';
	$shortdesc = '';
	$description = '';
	$image = '';
	$author = '';
	$author_site = '';
	$author_email = '';
	$file_ver = '';
	$allow_comments = array(false, false);
	$allow_votes = array(false, false);
	$view = array(1=>false, 2=>false, 3=>false, 4=>false);
	$active = array(false, false);
	if(!isset($_GET['id'])){
		$allow_comments[1] = true;
		$allow_votes[1] = true;
		$view[4] = true;
		$active[1] = true;
		$action = 'addfilesave';
		$top = '���������� �����';
		$cap = '��������';
	}else{
		$id = SafeEnv($_GET['id'], 11, int);
		$db->Select('downloads', "`id`='$id'");
		$file = $db->FetchRow();
		$category = SafeDB($file['category'], 11, int);
		$title = SafeDB($file['title'], 250, str);
		$url = SafeDB($file['url'], 250, str);
		$file_size = SafeDB($file['size'], 11, real);
		$size_type = SafeDB($file['size_type'], 1, str);
		$shortdesc = SafeDB($file['shortdesc'], 0, str, false);
		$description = SafeDB($file['description'], 0, str, false);
		$image = SafeDB($file['image'], 250, str);
		$author = SafeDB($file['author'], 200, str);
		$author_site = SafeDB($file['author_site'], 250, str);
		$author_email = SafeDB($file['author_email'], 50, str);
		$file_ver = SafeDB($file['file_version'], 250, str);
		$allow_comments[SafeDB($file['allow_comments'], 1, int)] = true;
		$allow_votes[SafeDB($file['allow_votes'], 1, int)] = true;
		$view[SafeDB($file['view'], 1, int)] = true;
		$active[SafeDB($file['active'], 1, int)] = true;
		$action = 'editfilesave&id='.$id;
		$top = '�������������� �����';
		$cap = '��������� ���������';
	}
	unset($file);
	$visdata = GetUserTypesFormData($view);
	$cats_data = array();
	$cats_data = $tree->GetCatsData($category);
	if(count($cats_data) == 0){
		AddTextBox($top, '��� ��������� ��� ����������! �������� ���������.');
		return;
	}

	$filesize_data = array();
	$site->DataAdd($filesize_data, 'b', '����', $size_type == 'b');
	$site->DataAdd($filesize_data, 'k', '��������', $size_type == 'k');
	$site->DataAdd($filesize_data, 'm', '��������', $size_type == 'm');
	$site->DataAdd($filesize_data, 'g', '��������', $size_type == 'g');

	FormRow('� ���������', $site->Select('category', $cats_data));
	FormRow('��������', $site->Edit('title', $title, false, 'style="width:400px;"'));
	FormRow('���� � �����', $site->Edit('url', $url, false, 'style="width:400px;"'));
	$max_file_size = ini_get('upload_max_filesize');
	FormRow(
		'��������� ����<br />(<small>������������ ������ �����: '.$max_file_size.'</small>)',
		$site->FFile('upload_file').'<br /><div style="width: 400px; word-wrap:break-word;">����������� �������:<br />'.$config['downloads']['file_exts'].'</div>');

	FormRow('������ �����', $site->Edit('size', $file_size, false, 'style="width:200px;"').' '.$site->Select('filesize_type', $filesize_data));
	AdminImageControl('�����������', '��������� �����������', $image, $config['downloads']['images_dir']);
	FormTextRow('������� ��������', $site->HtmlEditor('shortdesc', $shortdesc, 600, 200));
	FormTextRow('������ ��������', $site->HtmlEditor('description', $description, 600, 400));
	FormRow('������ �����', $site->Edit('version', $file_ver, false, 'style="width:400px;"'));
	FormRow('�����', $site->Edit('author', $author, false, 'style="width:400px;"'));
	FormRow('E-mail ������', $site->Edit('author_email', $author_email, false, 'style="width:400px;"'));
	FormRow('���� ������', $site->Edit('author_site', $author_site, false, 'style="width:400px;"'));
	FormRow('�����������', $site->Radio('allow_comments', 'on', $allow_comments[1]).' ���������<br />'.$site->Radio('allow_comments', 'off', $allow_comments[0]).' ���������');
	FormRow('������', $site->Radio('allow_votes', 'on', $allow_votes[1]).' ���������<br />'.$site->Radio('allow_votes', 'off', $allow_votes[0]).' ���������');
	FormRow('��� �����', $site->Select('view', $visdata));
	FormRow('�������', $site->Radio('active', 'on', $active[1]).' ��&nbsp;<br />'.$site->Radio('active', 'off', $active[0]).' ���');
	AddCenterBox($top);
	AddForm('<form action="'.$config['admin_file'].'?exe=downloads&a='.$action.'&back='.SaveRefererUrl().'" method="post" enctype="multipart/form-data">', $site->Button('������', 'onclick="history.go(-1)"').$site->Submit($cap));
}

function AdminDownloadsSaveFile( $action )
{
	global $config, $db, $tree, $user;

	if($_POST == array()){
		AddTextBox('������', '<b>��������! �������� ������������ ������ POST ������. ��������� �� ���������.</b>');
		return;
	}
	$Error = '';

	if(!$user->CheckAccess2('downloads', 'edit_files')){
		AddTextBox('������', $config['general']['admin_accd']);
		return;
	}
	$category = SafeEnv($_POST['category'], 11, int);
	if(in_array($category, $tree->GetAllChildId(0)) === false || $category == 0){
		GO($config['admin_file'].'?exe=downloads');
	}
	$title = SafeEnv($_POST['title'], 250, str);

	// ������������ upload_file ���� ���������� ����
	$exts = explode(',', $config['downloads']['file_exts']);
	$exts2 = array();
	foreach($exts as $ext){
		$exts2[trim($ext)] = true;
	}
	$UploadErrors = array(
		0 => '',
		1 => '������ ����� ��������', //'����������� ���� ��������� ����������� ������ (upload_max_filesize) � php.ini ('.ini_get('upload_max_filesize').')',
		2 => '������ ����� ��������', //'����������� ���� ��������� ��������� MAX_FILE_SIZE, ������� ���� ���������� � ����� HTML',
		3 => '���� �������� ������ ��������',
		4 => '���� �� ��� ��������.',
		6 => '�� ������� ����� ��� ��������� ������ �� �������',
		7 => '������ �� ����� ������ �� ����',
		8 => '�������� ����� ���� �������� ����������� PHP',
		9 => '������ �� ����� ������ �� ����'
	);
	if($_FILES['upload_file']['error'] == UPLOAD_ERR_OK){
		if(isset($exts2[strtolower(GetFileExt($_FILES['upload_file']['name']))])){
			// ��������� ����
			$Dir = $config['downloads']['files_dir'];
			$file_name = Translit($_FILES['upload_file']['name'], true);
			$ext = GetFileExt($file_name);
			$name = GetFileName($file_name);
			$i = 1;
			while(is_file($Dir.$file_name)){
				$i++;
				$file_name = $name.'_'.$i.$ext;
			}
			$FileName = $Dir.$file_name;
			copy($_FILES['upload_file']['tmp_name'], $FileName);
			$url = SafeEnv($FileName, 255, str);
		}else{
			$url = SafeEnv($_POST['url'], 255, str);
		}
	}else{
		if($_FILES['upload_file']['error'] != 4){
			$Error = $UploadErrors[$_FILES['upload_file']['error']];
		}
		$url = SafeEnv($_POST['url'], 255, str);
	}

	if($_POST['size'] > 0){
		$file_size = SafeEnv($_POST['size'], 11, real); // ������� �����
		$size_type = SafeEnv($_POST['filesize_type'], 1, str);
	}elseif(file_exists($url)){
		$file_size = filesize($url);
		$size_type = 'b';
	}elseif(file_exists($config['general']['site_url'].$url)){
		$file_size = filesize($config['general']['site_url'].$url);
		$size_type = 'b';
	}else{
		$file_size = SafeEnv($_POST['size'], 11, int);
		$size_type = 'b';
	}

	$shortdesc = SafeEnv($_POST['shortdesc'], 0, str);
	$description = SafeEnv($_POST['description'], 0, str);
	// ��������� �����������
	$ImageUploadError = false;
	$image = LoadImage('up_image', $config['downloads']['images_dir'], $config['downloads']['images_dir'].'thumbs/', $config['downloads']['thumb_max_width'], $config['downloads']['thumb_max_height'], $_POST['image'], $ImageUploadError);
	if($ImageUploadError){
		$Error = '<center>������������ ������ �����. ����� ��������� ������ ����������� ������� GIF, JPEG ��� PNG.</center>';
	}
	$author = SafeEnv($_POST['author'], 50, str);
	$author_site = SafeEnv(Url($_POST['author_site']), 250, str);
	$author_email = SafeEnv($_POST['author_email'], 50, str);
	$file_ver = SafeEnv($_POST['version'], 250, str);
	$allow_comments = EnToInt($_POST['allow_comments']);
	$allow_votes = EnToInt($_POST['allow_votes']);
	$view = ViewLevelToInt($_POST['view']);
	$active = EnToInt($_POST['active']);

	if('editfilesave' == $action){
		//����� ���������� Set ������
		$set = "title='$title',category='$category',size='$file_size',size_type='$size_type',url='$url',shortdesc='$shortdesc',description='$description',image='$image',author='$author',author_site='$author_site',author_email='$author_email',file_version='$file_ver',allow_comments='$allow_comments',allow_votes='$allow_votes',view='$view',active='$active'";
		$id = SafeEnv($_GET['id'], 11, int);
		$r = $db->Select('downloads', "`id`='$id'");
		if($r[0]['category'] != $category && $r[0]['active'] == '1'){
			$tree->CalcFileCounter($r[0]['category'], false);
			$tree->CalcFileCounter($category, true);
		}
		if($r[0]['active'] != $active){ // ��������� / ��������
			if($active == 0){
				$tree->CalcFileCounter($category, false);
			}else{
				$tree->CalcFileCounter($category, true);
			}
		}
		$db->Update('downloads', $set, "`id`='$id'");
	}elseif('addfilesave' == $action){
		$values = Values('', $category, time(), $file_size, $size_type, $title, $url, $shortdesc, $description, $image, $author, $author_site, $author_email, $file_ver, $allow_comments, 0, $allow_votes, 0, 0, 0, $view, $active);
		$db->Insert('downloads', $values);
		if($active){
			$tree->CalcFileCounter($category, true);
		}
	}
	if($Error == ''){
		//GO($config['admin_file'].'?exe=downloads');
		GoRefererUrl($_GET['back']);
		AddTextBox('���������', '��������� ������� ���������.'); // � ������, ���� �� ����� ����������� ���������������
	}else{
		AddTextBox('������', $Error.'.<br /><a href="'.GetRefererUrl($_GET['back']).'">�����</a>');
	}

}

function AdminDownloadsDeleteFile()
{
	global $config, $db, $tree, $user;
	if(!$user->CheckAccess2('downloads', 'edit_files')){
		AddTextBox('������', $config['general']['admin_accd']);
		return;
	}
	if(!isset($_GET['id'])){
		GO($config['admin_file'].'?exe=downloads');
	}
	if(isset($_GET['ok']) && SafeEnv($_GET['ok'], 1, int) == '1'){
		$id = SafeEnv($_GET['id'], 11, int);
		$r = $db->Select('downloads', "`id`='$id'");
		$tree->CalcFileCounter(SafeDB($r[0]['category'], 11, int), false);
		// ������� ����
		if(is_file(RealPath2($r[0]['url']))){
			unlink(RealPath2($r[0]['url']));
		}
		$db->Delete('downloads', "`id`='$id'");
		$db->Delete('downloads_comments', "`object_id`='$id'");
		//GO($config['admin_file'].'?exe=downloads');
		GoRefererUrl($_GET['back']);
		AddTextBox('���������', '���� ������ �������.');
	}else{
		$r = $db->Select('downloads', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
		$text = '�� ������������� ������ ������� ���� "'.SafeDB($r[0]['title'], 250, str).'"<br />'
		.'<a href="'.$config['admin_file'].'?exe=downloads&a=deletefile&id='.SafeEnv($_GET['id'], 11, int).'&back='.SaveRefererUrl().'&ok=1">��</a> &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">���</a>';
		AddTextBox('��������', $text);
	}
}

function AdminDownloadsChangeStatus()
{
	global $config, $db, $tree, $user;
	if(!$user->CheckAccess2('downloads', 'edit_files')){
		AddTextBox('������', $config['general']['admin_accd']);
		return;
	}
	if(!isset($_GET['id'])){
		GO($config['admin_file'].'?exe=downloads');
	}
	$db->Select('downloads', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	if($db->NumRows() > 0){
		$r = $db->FetchRow();
		if($r['active'] == 1){
			$en = '0';
			$tree->CalcFileCounter(SafeDB($r['category'], 11, int), false);
		}else{
			$en = '1';
			$tree->CalcFileCounter(SafeDB($r['category'], 11, int), true);
		}
		$db->Update('downloads', "active='$en'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	}
	GO($config['admin_file'].'?exe=downloads');
}

function AdminDownloadsResetRating()
{
	global $config, $db, $user;
	if(!$user->CheckAccess2('downloads', 'edit_files')){
		AddTextBox('������', $config['general']['admin_accd']);
		return;
	}
	if(isset($_GET['ok']) && $_GET['ok'] == '1'){
		$db->Update('downloads', "votes_amount='0',votes='0'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
		GO($config['admin_file'].'?exe=downloads');
	}else{
		$r = $db->Select('downloads', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
		$text = '�� ������������� ������ �������� ������ �����? "'.SafeDB($r[0]['title'], 250, str).'"<br />'
			.'<a href="'.$config['admin_file'].'?exe=downloads&a=resetrating&id='.SafeEnv($_GET['id'], 11, int).'&ok=1">��</a> &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">���</a>';
		AddTextBox("��������", $text);
	}
}

function AdminDownloadsResetCounter()
{
	global $config, $db, $user;
	if(!$user->CheckAccess2('downloads', 'edit_files')){
		AddTextBox('������', $config['general']['admin_accd']);
		return;
	}
	$db->Update('downloads', "hits='0'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	GO($config['admin_file'].'?exe=downloads');
}

function AdminDownloads( $action )
{
	global $config, $db, $user;
	TAddToolLink('�����', 'main', 'downloads');
	if($user->CheckAccess2('downloads', 'edit_cats')){
		TAddToolLink('���������', 'cats', 'downloads&a=cats');
	}
	if($user->CheckAccess2('downloads', 'config')){
		TAddToolLink('���������', 'config', 'downloads&a=config');
	}
	TAddToolBox($action);
	if($user->CheckAccess2('downloads', 'edit_files')){
		TAddToolLink('�������� ����', 'editor', 'downloads&a=editor');
	}
	if($user->CheckAccess2('downloads', 'edit_cats')){
		TAddToolLink('�������� ���������', 'cateditor', 'downloads&a=cateditor');
	}
	TAddToolBox($action);
	switch($action){
		case 'main':
			AdminDownloadsMain();
			break;
		case 'editor':
			AdminDownloadsFileEditor($action);
			break;
		case 'addfilesave':
		case 'editfilesave':
			AdminDownloadsSaveFile($action);
			break;
		case 'deletefile':
			AdminDownloadsDeleteFile();
			break;
		case 'cats':
			if(!$user->CheckAccess2('downloads', 'edit_cats')){
				AddTextBox('������', $config['general']['admin_accd']);
				return;
			}
			global $tree;
			$result = $tree->ShowCats();
			if($result == false){
				$result = '��� ��������� ��� �����������.';
			}
			AddTextBox('���������', $result);
			break;
			if(!$user->CheckAccess2('downloads', 'edit_cats')){
				AddTextBox('������', $config['general']['admin_accd']);
				return;
			}
		case 'cateditor':
			if(!$user->CheckAccess2('downloads', 'edit_cats')){
				AddTextBox('������', $config['general']['admin_accd']);
				return;
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
			if(!$user->CheckAccess2('downloads', 'edit_cats')){
				AddTextBox('������', $config['general']['admin_accd']);
				return;
			}
			global $tree, $config;
			$tree->EditorSave((isset($_GET['id']) ? SafeEnv($_GET['id'], 11, int) : null));
			GO($config['admin_file'].'?exe=downloads&a=cats');
			break;
		case 'delcat':
			if(!$user->CheckAccess2('downloads', 'edit_cats')){
				AddTextBox('������', $config['general']['admin_accd']);
				return;
			}
			global $tree, $config;
			if($tree->DeleteCat(SafeEnv($_GET['id'], 11, int))){
				GO($config['admin_file'].'?exe=downloads&a=cats');
			}
			break;
		case 'changestatus':
			AdminDownloadsChangeStatus();
			break;
		case 'config':
			if(!$user->CheckAccess2('downloads', 'config')){
				AddTextBox('������', '������ ��������!');
				return;
			}
			AdminConfigurationEdit('downloads', 'downloads', false, false, '������������ ������ "����� ������"');
			break;
		case 'configsave':
			if(!$user->CheckAccess2('downloads', 'config')){
				AddTextBox('������', '������ ��������!');
				return;
			}
			AdminConfigurationSave('downloads&a=config', 'downloads', false);
			break;
		case 'resetrating':
			AdminDownloadsResetRating();
			break;
		case 'resetcounter':
			AdminDownloadsResetCounter();
			break;
	}
}

if(isset($_GET['a'])){
	$action = SafeEnv($_GET['a'], 255, str);
	AdminDownloads($action);
}else{
	$action = 'main';
	AdminDownloads($action);
}

?>