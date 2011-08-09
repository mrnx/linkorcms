<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('����� ������');

if(!$user->CheckAccess2('articles', 'articles')){
	AddTextBox('������', $config['general']['admin_accd']);
	return;
}

include_once ($config['inc_dir'].'tree_a.class.php');
$tree = new AdminTree('articles_cats');
$tree->module = 'articles';
$tree->obj_table = 'articles';
$tree->obj_cat_coll = 'cat_id';
$tree->showcats_met = 'cats';
$tree->edit_met = 'cateditor';
$tree->save_met = 'catsave';
$tree->del_met = 'delcat';
$tree->action_par_name = 'a';
$tree->id_par_name = 'id';

$editarticles = $user->CheckAccess2('articles', 'edit_articles');
$editcats = $user->CheckAccess2('articles', 'edit_cats');
$editconf = $user->CheckAccess2('articles', 'config');

// ������� - ������ ������
function AdminArticlesMain()
{
	global $config, $db, $tree, $site, $user, $editarticles;
	// ������, ���� ����������� ���������� ������ ������������ ���������.
	if(isset($_GET['cat']) && $_GET['cat'] > -1){
		$cat = SafeEnv($_GET['cat'], 11, int);
		$where = "`cat_id`='$cat'";
	}else{
		$cat = -1;
		$where = "";
	}
	$data = array();
	$data = $tree->GetCatsData($cat, true);
	$site->DataAdd($data, -1, '��� ������', $cat == -1);
	// �������� ����� ��������
	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 10, int);
	}else{
		$page = 1;
	}
	AddCenterBox('������');

	// ����� ������� �� ����������
	$text = '';
	$text = '<form name="categories" method="get">'
	.'<table cellspacing="0" cellpadding="0" border="0" width="100%" align="center">'
	.'<tr><td align="center" class="contenttd">�������� ���������: '.$site->Hidden('exe', 'articles').$site->Select('cat', $data).$site->Submit('��������').'</td></tr>'
	.'</table></form>';
	AddText($text);
	// ����� ������ �� �� � �������� ������������ ��������� ���� �����.
	$r = $db->Select('articles', $where);
	SortArray($r, 'public', true); // ��������� �� ���� ����������
	if(count($r) > $config['articles']['articles_on_page']){
		$navigator = new Navigation($page);
		$navigator->GenNavigationMenu($r, $config['articles']['articles_on_page'], $config['admin_file'].'?exe=articles'.($cat > 0 ? '&cat='.$cat : ''));
		AddNavigation();
		$nav = true;
	}else{
		$nav = false;
		AddText('<br />');
	}
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>��������</th><th>���������</th><th>�����������</th><th>������</th><th>�������������</th><th>������</th><th>�������</th></tr>';
	foreach($r as $art){
		switch($art['active']){
			case '1':
				$st = '<font color="#008000">���.</font></a>';
				break;
			case '0':
				$st = '<font color="#FF0000">����.</font>';
				break;
		}
		if($editarticles){
			$st = '<a href="'.$config['admin_file'].'?exe=articles&a=changestatus&id='.SafeDB($art['id'], 11, int).'">'.$st.'</a>';
			$func = '';
			$func .= SpeedButton('�������������', $config['admin_file'].'?exe=articles&a=editor&id='.SafeDB($art['id'], 11, int), 'images/admin/edit.png');
			$func .= SpeedButton('�������', $config['admin_file'].'?exe=articles&a=delete&id='.SafeDB($art['id'], 11, int).'&ok=0', 'images/admin/delete.png');
		}else{
			$func = '-';
		}
		$vi = ViewLevelToStr(SafeDB($art['view'], 1, int));
		$rating = '<img src="'.GetRatingImage(SafeDB($art['num_votes'], 11, int), SafeDB($art['all_votes'], 11, int)).'" border="0" />/ (����� '.SafeDB($art['num_votes'], 11, int).')'.($editarticles ? ' / <a href="'.$config['admin_file'].'?exe=articles&a=resetrating&id='.SafeDB($art['id'], 11, int).'" title="�������� ������� ������">�����</a>' : '');
		$text .= '<tr>
		<td><b><a href="'.$config['admin_file'].'?exe=articles&a=editor&id='.SafeDB($art['id'], 11, int).'">'.SafeDB($art['title'], 255, str).'</a></b></td>
		<td>'.SafeDB($art['hits'], 11, int).($editarticles ? ' / <a href="'.$config['admin_file'].'?exe=articles&a=resethits&id='.SafeDB($art['id'], 11, int).'" title="�������� �������">�����</a>' : '').'</td>
		<td>'.SafeDB($art['comments_counter'], 11, int) .'</td>
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

// �������� ������ - ����������, ��������������
function AdminArticlesEditor()
{
	global $tree, $site, $config, $db, $user, $editarticles;
	if(!$editarticles){
		AddTextBox('������', $config['general']['admin_accd']);
		return;
	}
	$site->AddJS('
	function PreviewOpen(){
		window.open(\'index.php?name=plugins&p=preview&mod=article\',\'Preview\',\'resizable=yes,scrollbars=yes,menubar=no,status=no,location=no,width=640,height=480\');
	}');
	$cat_id = 0;
	$author = '';
	$email = '';
	$www = '';
	$title = '';
	$description = '';
	$article = '';
	$image = '';
	$auto_br_desc = false;
	$auto_br_article = false;
	$allow_comments = true;
	$allow_votes = true;
	$view = 4;
	$active = true;
	//������ SEO
	$seo_title = '';
	$seo_keywords = '';
	$seo_description = '';
	//
	if(!isset($_GET['id'])){
		$action = 'add';
		$top = '���������� ������';
		$cap = '��������';
	}else{
		$id = SafeEnv($_GET['id'], 11, str);
		$db->Select('articles', "`id`='$id'");
		$par = $db->FetchRow();
		$cat_id = SafeDB($par['cat_id'], 11, int);
		$author = SafeDB($par['author'], 200, str);
		$email = SafeDB($par['email'], 50, str);
		$www = SafeDB($par['www'], 250, str);
		$title = SafeDB($par['title'], 255, str);
		$description = SafeDB($par['description'], 0, str, false);
		$article = SafeDB($par['article'], 0, str, false);
		$image = SafeDB($par['image'], 250, str);

		$auto_br_article = SafeDB($par['auto_br_article'], 1, bool);
		$auto_br_desc = SafeDB($par['auto_br_desc'], 1, bool);

		$active = SafeDB($par['active'], 1, bool);

		$allow_comments = SafeDB($par['allow_comments'], 1, int);
		$allow_votes = SafeDB($par['allow_votes'], 1, int);
		$view = SafeDB($par['view'], 1, int);
		//������ SEO
		$seo_title = SafeDB($par['seo_title'], 255, str);
		$seo_keywords = SafeDB($par['seo_keywords'], 255, str);
		$seo_description = SafeDB($par['seo_description'], 255, str);
		//
		$action = 'save&id='.$id;
		$top = '�������������� ������';
		$cap = '���������';
	}
	unset($par);

	$cats_data = array();
	$cats_data = $tree->GetCatsData($cat_id);
	if(count($cats_data) == 0){
		AddTextBox($top, '��� ��������� ��� ����������! �������� ���������.');
		return;
	}

	FormRow('� ���������', $site->Select('category', $cats_data));
	FormRow('���������', $site->Edit('title', $title, false, 'maxlength="250" style="width:400px;"'));
	//������ SEO
	FormRow('[seo] ��������� ��������', $site->Edit('seo_title', $seo_title, false, 'style="width:400px;"'));
	FormRow('[seo] �������� �����', $site->Edit('seo_keywords', $seo_keywords, false, 'style="width:400px;"'));
	FormRow('[seo] ��������', $site->Edit('seo_description', $seo_description, false, 'style="width:400px;"'));
	//
	AdminImageControl('�����������', '��������� �����������', $image, $config['articles']['images_dir']);

	FormTextRow('�������� ������ (HTML)', $site->HtmlEditor('description', $description, 600, 200));
	FormRow('������������� ����� � HTML', $site->Select('auto_br_desc', GetEnData($auto_br_desc, '��', '���')));

	FormTextRow('������ ������ (HTML)', $site->HtmlEditor('article', $article, 600, 400));
	FormRow('������������� ����� � HTML', $site->Select('auto_br_article', GetEnData($auto_br_article, '��', '���')));

	FormRow('�����', $site->Edit('author', $author, false, 'style="width:400px;" maxlength="50"'));
	FormRow('E-mail ������', $site->Edit('email', $email, false, 'style="width:400px;" maxlength="50"'));
	FormRow('���� ������', $site->Edit('www', $www, false, 'style="width:400px;" maxlength="250"'));
	FormRow('�����������', $site->Select('allow_comments', GetEnData($allow_comments, '���������', '���������')));
	FormRow('������', $site->Select('allow_votes', GetEnData($allow_votes, '���������', '���������')));
	FormRow('��� �����', $site->Select('view', GetUserTypesFormData($view)));
	FormRow('�������', $site->Select('active', GetEnData($active, '��', '���')));
	AddCenterBox($top);
	AddForm('<form name="edit_form" action="'.$config['admin_file'].'?exe=articles&a='.$action.'&back='.SaveRefererUrl().'" method="post" enctype="multipart/form-data">', $site->Button('������', 'onclick="history.go(-1)"').$site->Button('������������', 'onclick="PreviewOpen();"').$site->Submit($cap));
}

// ���������� ������ ��� ���������
function AdminArticlesSaveArticle( $action )
{
	global $config, $db, $tree, $user, $editarticles;
	if(!$editarticles){
		AddTextBox('������', $config['general']['admin_accd']);
		return;
	}
	$cat_id = SafeEnv($_POST['category'], 11, int);
	if(in_array($cat_id, $tree->GetAllChildId(0)) === false || $cat_id == 0){
		GO($config['admin_file'].'?exe=articles');
	}
	$author = SafeEnv($_POST['author'], 200, str, true);
	$email = SafeEnv($_POST['email'], 50, str, true);
	$www = SafeEnv(Url($_POST['www']), 250, str, true);
	$title = SafeEnv($_POST['title'], 255, str);
	$description = SafeEnv($_POST['description'], 0, str, false, true, false);
	$article = SafeEnv($_POST['article'], 0, str, false, true, false);
	// ��������� �����������
	$Error = false;
	$image = LoadImage('up_image', $config['articles']['images_dir'], $config['articles']['images_dir'].'thumbs/', $config['articles']['thumb_max_width'], $config['articles']['thumb_max_height'], $_POST['image'], $Error);
	if($Error){
		AddTextBox('������', '<center>������������ ������ �����. ����� ��������� ������ ����������� ������� GIF, JPEG ��� PNG.</center>');
		return;
	}
	$auto_br_desc = EnToInt($_POST['auto_br_desc']);
	$auto_br_article = EnToInt($_POST['auto_br_article']);
	$allow_comments = EnToInt($_POST['allow_comments']);
	$allow_votes = EnToInt($_POST['allow_votes']);
	$view = ViewLevelToInt($_POST['view']);
	$active = EnToInt($_POST['active']);
	//������ SEO
	$seo_title = SafeEnv($_POST['seo_title'], 255, str);
	$seo_keywords = SafeEnv($_POST['seo_keywords'], 255, str);
	$seo_description = SafeEnv($_POST['seo_description'], 255, str);
	//
	if('add' == $action){
		$values = Values('', $cat_id, time(), $author, $email, $www, $title, $description, $article, $image, 0, $allow_comments, 0, $allow_votes, 0, 0, $active, $view, $auto_br_desc, $auto_br_article, $seo_title, $seo_keywords, $seo_description);
		$db->Insert('articles', $values);
		if($active){
			$tree->CalcFileCounter($cat_id, true);
		}
	}elseif('save' == $action){
		$set = "cat_id='$cat_id',author='$author',email='$email',www='$www',title='$title',description='$description',article='$article',image='$image',allow_comments='$allow_comments',allow_votes='$allow_votes',view='$view',active='$active',auto_br_desc='$auto_br_desc',auto_br_article='$auto_br_article',seo_title='$seo_title',seo_keywords='$seo_keywords',seo_description='$seo_description'";
		$id = SafeEnv($_GET['id'], 11, int);
		$r = $db->Select('articles', "`id`='$id'");
		if($r[0]['cat_id'] != $cat_id && $r[0]['active'] == '1'){ // ���� ����������� � ������ ������
			$tree->CalcFileCounter($r[0]['cat_id'], false);
			$tree->CalcFileCounter($cat_id, true);
		}
		if($r[0]['active'] != $active){ // ��������� / ��������
			if($active == 0){
				$tree->CalcFileCounter($cat_id, false);
			}else{
				$tree->CalcFileCounter($cat_id, true);
			}
		}
		$db->Update('articles', $set, "`id`='$id'");
	}
	//GO($config['admin_file'].'?exe=articles');
	GoRefererUrl($_GET['back']);
	AddTextBox('���������', '��������� ������� ���������.');
}

// ����� ������� ������
function AdminArticlesChangeStatus()
{
	global $config, $db, $tree, $user, $editarticles;
	if(!$editarticles){
		AddTextBox('������', $config['general']['admin_accd']);
		return;
	}
	if(!isset($_GET['id'])){
		GO($config['admin_file'].'?exe=articles');
	}
	$db->Select('articles', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	if($db->NumRows() > 0){
		$r = $db->FetchRow();
		if($r['active'] == 1){
			$en = '0';
			$tree->CalcFileCounter($r['cat_id'], false);
		}else{
			$en = '1';
			$tree->CalcFileCounter($r['cat_id'], true);
		}
		$db->Update('articles', "active='$en'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	}
	GO($config['admin_file'].'?exe=articles');
}

// �������� ������
function AdminArticlesDelete()
{
	global $config, $db, $tree, $user, $editarticles;
	if(!$editarticles){
		AddTextBox('������', $config['general']['admin_accd']);
		return;
	}
	if(!isset($_GET['id'])){
		GO($config['admin_file'].'?exe=articles');
	}
	if(isset($_GET['ok']) && SafeEnv($_GET['ok'], 1, int) == '1'){
		$id = SafeEnv($_GET['id'], 11, int);
		$r = $db->Select('articles', "`id`='".$id."'");
		$tree->CalcFileCounter($r[0]['cat_id'], false);
		$db->Delete('articles', "`id`='$id'");
		$db->Delete('articles_comments', "`object_id`='$id'");
		//GO($config['admin_file'].'?exe=articles');
		GoRefererUrl($_GET['back']);
		AddTextBox('���������', '������ �������.'); // � ������, ���� �� ����� ����������� ���������������
	}else{
		$r = $db->Select('articles', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
		$text = '������� ������ "'.$r[0]['title'].'"<br />'.'<a href="'.$config['admin_file'].'?exe=articles&a=delete&id='.SafeEnv($_GET['id'], 11, int).'&back='.SaveRefererUrl().'&ok=1">��</a> &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">���</a>';
		AddTextBox('��������', $text);
	}
}

// ����� �������� ���������� ������
function AdminArticlesResetHits()
{
	global $config, $db, $user, $editarticles;
	if(!$editarticles){
		AddTextBox('������', $config['general']['admin_accd']);
		return;
	}
	if(isset($_GET['id'])){
		$db->Update('articles', "hits='0'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	}
	GO($config['admin_file'].'?exe=articles');
}

// ����� ������ ������
function AdminArticlesResetRating()
{
	global $config, $db, $user, $editarticles;
	if(!$editarticles){
		AddTextBox('������', $config['general']['admin_accd']);
		return;
	}
	if(isset($_GET['ok']) && $_GET['ok'] == '1'){
		$db->Update('articles', "num_votes='0',all_votes='0'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
		GO($config['admin_file'].'?exe=articles');
	}else{
		$r = $db->Select('articles', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
		$text = '�� ������������� ������ �������� ������ ��� ������ "'.$r[0]['title'].'"<br />'.'<a href="'.$config['admin_file'].'?exe=articles&a=resetrating&id='.SafeEnv($_GET['id'], 11, int).'&ok=1">��</a> &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">���</a>';
		AddTextBox("��������", $text);
	}
}

include_once ($config['inc_dir'].'configuration/functions.php');

function AdminArticles( $action )
{
	global $user, $editarticles, $editcomments, $editcats, $editconf;
	TAddToolLink('������', 'main', 'articles');
	if($editcats){
		TAddToolLink('���������', 'cats', 'articles&a=cats');
	}
	if($editconf){
		TAddToolLink('���������', 'config', 'articles&a=config');
	}
	TAddToolBox($action);
	if($editarticles){
		TAddToolLink('�������� ������', 'editor', 'articles&a=editor');
	}
	if($editcats){
		TAddToolLink('�������� ���������', 'cateditor', 'articles&a=cateditor');
	}
	TAddToolBox($action);
	switch($action){
		case 'main':
			AdminArticlesMain();
			return true;
			break;
		case 'editor':
			AdminArticlesEditor();
			return true;
			break;
		case 'add':
		case 'save':
			AdminArticlesSaveArticle($action);
			return true;
			break;
		case 'changestatus':
			AdminArticlesChangeStatus();
			return true;
			break;
		case 'delete':
			AdminArticlesDelete();
			return true;
			break;
		case 'resethits':
			AdminArticlesResetHits();
			return true;
			break;
		case 'resetrating':
			AdminArticlesResetRating();
			return true;
			break;
		////////////////// ���������
		case 'cats':
			if(!$editcats){
				return false;
			}
			global $tree;
			$result = $tree->ShowCats();
			if($result == false){
				$result = '��� ��������� ��� �����������.';
			}
			AddTextBox('���������', $result);
			return true;
			break;
		case 'cateditor':
			if(!$editcats){
				return false;
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
			return true;
			break;
		case 'catsave':
			if(!$editcats){
				return false;
			}
			global $tree, $config;
			$tree->EditorSave((isset($_GET['id']) ? SafeEnv($_GET['id'], 11, int) : null));
			GO($config['admin_file'].'?exe=articles&a=cats');
			break;
		case 'delcat':
			if(!$editcats){
				return false;
			}
			global $tree, $config;
			if($tree->DeleteCat(SafeEnv($_GET['id'], 11, int))){
				GO($config['admin_file'].'?exe=articles&a=cats');
			}
			return true;
			break;
		////////////////// ���������
		case 'config':
			if(!$editconf){
				return false;
			}
			AdminConfigurationEdit('articles', 'articles', false, false, '������������ ������ "����� ������"');
			return true;
			break;
		case 'configsave':
			if(!$editconf){
				return false;
			}
			AdminConfigurationSave('articles&a=config', 'articles', false, false);
			return true;
			break;
	}
	return false;
}

if(isset($_GET['a'])){
	$a = $_GET['a'];
}else{
	$a = 'main';
}

if(!AdminArticles($a)){
	AddTextBox('������', $config['general']['admin_accd']);
	return;
}

?>
