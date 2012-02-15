<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(!$user->CheckAccess2('articles', 'articles')){
	System::admin()->AccessDenied();
}

TAddSubTitle('����� ������');

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

if(isset($_GET['a'])){
	$action = $_GET['a'];
}else{
	$action = 'main';
}

TAddToolLink('������', 'main', 'articles');
if($editarticles) TAddToolLink('�������� ������', 'editor', 'articles&a=editor');
if($editcats) TAddToolLink('���������', 'cats', 'articles&a=cats');
if($editcats) TAddToolLink('�������� ���������', 'cateditor', 'articles&a=cateditor');
if($editconf) TAddToolLink('��������� ������', 'config', 'articles&a=config');
TAddToolBox($action);

switch($action){
	case 'main':
		AdminArticlesMain();
		break;
	case 'editor':
		AdminArticlesEditor();
		break;
	case 'add':
	case 'save':
		AdminArticlesSaveArticle($action);
		break;
	case 'changestatus':
		AdminArticlesChangeStatus();
		break;
	case 'delete':
		AdminArticlesDelete();
		break;
	case 'resethits':
		AdminArticlesResetHits();
		break;
	case 'resetrating':
		AdminArticlesResetRating();
		break;
	// ���������
	case 'cats':
		if(!$editcats){
			AddTextBox('������', $config['general']['admin_accd']);
		}else{
			global $tree;
			$result = $tree->ShowCats();
			if($result == false){
				$result = '��� ��������� ��� �����������.';
			}
			AddTextBox('���������', $result);
		}
		break;
	case 'cateditor':
		if(!$editcats){
			AddTextBox('������', $config['general']['admin_accd']);
		}else{
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
		}
		break;
	case 'catsave':
		if(!$editcats){
			AddTextBox('������', $config['general']['admin_accd']);
		}else{
			global $tree, $config;
			$tree->EditorSave((isset($_GET['id']) ? SafeEnv($_GET['id'], 11, int) : null));
			GO(ADMIN_FILE.'?exe=articles&a=cats');
		}
		break;
	case 'delcat':
		if(!$editcats){
			AddTextBox('������', $config['general']['admin_accd']);
		}else{
			global $tree, $config;
			if($tree->DeleteCat(SafeEnv($_GET['id'], 11, int))){
				GO(ADMIN_FILE.'?exe=articles&a=cats');
			}
		}
		break;
	// ���������
	case 'config':
		if(!$editconf){
			System::admin()->AccessDenied();
		}
		System::admin()->AddCenterBox('������������ ������ "����� ������"');
		if(CheckGet('saveok')){
			System::admin()->Highlight('��������� ���������.');
		}
		System::admin()->ConfigGroups('articles');
		System::admin()->AddConfigsForm(ADMIN_FILE.'?exe=articles&a=configsave');
		break;
	case 'configsave':
		if(!$editconf){
			System::admin()->AccessDenied();
		}
		System::admin()->SaveConfigs('articles');
		GO(ADMIN_FILE.'?exe=articles&a=config&saveok');
		break;
}

// ������� - ������ ������
function AdminArticlesMain(){
	global $config, $tree, $site, $editarticles;

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
	System::admin()->AddJS('
	ArticlesSelectCat = function(){
		Admin.LoadPage("'.ADMIN_FILE.'?exe=articles&cat="+$("#article-cat").val());
	}
	');
	$text = '<div style="text-align: center; margin-bottom: 10px;">���������: '.$site->Select('cat', $data, false, 'id="article-cat" onchange="ArticlesSelectCat();"').'</div>';
	AddText($text);

	// ����� ������ �� �� � �������� ������������ ��������� ���� �����.
	$r = System::database()->Select('articles', $where);
	SortArray($r, 'public', true); // ��������� �� ���� ����������
	if(count($r) > $config['articles']['articles_on_page']){
		$navigator = new Navigation($page);
		$navigator->GenNavigationMenu($r, $config['articles']['articles_on_page'], ADMIN_FILE.'?exe=articles'.($cat > 0 ? '&cat='.$cat : ''));
		//AddNavigation();
		$nav = true;
	}else{
		$nav = false;
	}
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>��������</th><th>���������</th><th>������</th><th>�������������</th><th>������</th><th>�������</th></tr>';

	$back = SaveRefererUrl();
	foreach($r as $art){
		$id = SafeDB($art['id'], 11, int);
		$st = System::admin()->SpeedStatus('��������', '���������', ADMIN_FILE.'?exe=articles&a=changestatus&id='.$id, $art['active'] == '1');
		if($editarticles){
			$func = '';
			$func .= SpeedButton('�������������', ADMIN_FILE.'?exe=articles&a=editor&id='.$id.'&back='.$back, 'images/admin/edit.png');
			$func .= System::admin()->SpeedConfirm('�������', ADMIN_FILE.'?exe=articles&a=delete&id='.$id.'&ok=1&back='.$back, 'images/admin/delete.png', '������� ������?');
		}else{
			$func = '-';
		}
		$vi = ViewLevelToStr(SafeDB($art['view'], 1, int));

		$hits = SafeDB($art['hits'], 11, int);
		if($editarticles){
			$hits .= '&nbsp;'.System::admin()->SpeedConfirm('�������� ������� ����������', ADMIN_FILE.'?exe=articles&a=resethits&id='.SafeDB($art['id'], 11, int), 'images/admin/arrow_in.png', '�������� ������� ����������?');
		}

		$rating = '<img src="'.GetRatingImage(SafeDB($art['num_votes'], 11, int), SafeDB($art['all_votes'], 11, int)).'" border="0" />';
		if($editarticles){
			$rating .= '&nbsp;'.System::admin()->SpeedConfirm('�������� ������� ������ ('.SafeDB($art['num_votes'], 11, int).' �������)', ADMIN_FILE.'?exe=articles&a=resetrating&id='.SafeDB($art['id'], 11, int), 'images/admin/arrow_in.png', '�������� ������?');
		}

		$text .= '<tr>
		<td><b>'.System::admin()->Link(SafeDB($art['title'], 255, str), ADMIN_FILE.'?exe=articles&a=editor&id='.$id).'</b></td>
		<td>'.$hits.'</td>
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
function AdminArticlesEditor(){
	global $tree, $site, $editarticles;
	if(!$editarticles){
		System::admin()->AccessDenied();
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
		System::database()->Select('articles', "`id`='$id'");
		$par = System::database()->FetchRow();
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
	AdminImageControl('�����������', '��������� �����������', $image, System::config('articles/images_dir'));

	FormTextRow('�������� ������ (HTML)', $site->HtmlEditor('description', $description, 600, 200));
	FormRow('', '������������� ����� � HTML: '.$site->Select('auto_br_desc', GetEnData($auto_br_desc, '��', '���')));

	FormTextRow('������ ������ (HTML)', $site->HtmlEditor('article', $article, 600, 400));
	FormRow('', '������������� ����� � HTML: '.$site->Select('auto_br_article', GetEnData($auto_br_article, '��', '���')));

	FormRow('�����', $site->Edit('author', $author, false, 'style="width:400px;" maxlength="50"'));
	FormRow('E-mail ������', $site->Edit('email', $email, false, 'style="width:400px;" maxlength="50"'));
	FormRow('���� ������', $site->Edit('www', $www, false, 'style="width:400px;" maxlength="250"'));
	FormRow('�����������', $site->Select('allow_comments', GetEnData($allow_comments, '���������', '���������')));
	FormRow('������', $site->Select('allow_votes', GetEnData($allow_votes, '���������', '���������')));
	FormRow('��� �����', $site->Select('view', GetUserTypesFormData($view)));
	FormRow('�������', $site->Select('active', GetEnData($active, '��', '���')));
	AddCenterBox($top);
	$back = '';
	if(isset($_REQUEST['back'])){
		$back = '&back='.SafeDB($_REQUEST['back'], 255, str);
	}
	AddForm('<form name="edit_form" action="'.ADMIN_FILE.'?exe=articles&a='.$action.$back.'" method="post" enctype="multipart/form-data">',
		$site->Button('������', 'onclick="history.go(-1)"').$site->Button('������������', 'onclick="PreviewOpen();"').$site->Submit($cap));
}

// ���������� ������ ��� ���������
function AdminArticlesSaveArticle( $action ){
	global $config, $tree, $editarticles;
	if(!$editarticles){
		AddTextBox('������', $config['general']['admin_accd']);
		return;
	}
	$cat_id = SafeEnv($_POST['category'], 11, int);
	if(in_array($cat_id, $tree->GetAllChildId(0)) === false || $cat_id == 0){
		GO(ADMIN_FILE.'?exe=articles');
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
		System::database()->Insert('articles', $values);
		if($active){
			$tree->CalcFileCounter($cat_id, true);
		}
	}elseif('save' == $action){
		$set = "cat_id='$cat_id',author='$author',email='$email',www='$www',title='$title',description='$description',article='$article',image='$image',allow_comments='$allow_comments',allow_votes='$allow_votes',view='$view',active='$active',auto_br_desc='$auto_br_desc',auto_br_article='$auto_br_article',seo_title='$seo_title',seo_keywords='$seo_keywords',seo_description='$seo_description'";
		$id = SafeEnv($_GET['id'], 11, int);
		$r = System::database()->Select('articles', "`id`='$id'");
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
		System::database()->Update('articles', $set, "`id`='$id'");
	}
	if(isset($_REQUEST['back'])){
		GoRefererUrl($_REQUEST['back']);
	}else{
		GO(ADMIN_FILE.'?exe=articles');
	}
}

// ����� ������� ������
function AdminArticlesChangeStatus(){
	global $tree, $editarticles;
	if(!$editarticles){
		if(IsAjax()){
			exit("ERROR");
		}
		System::admin()->AccessDenied();
	}
	if(!isset($_GET['id'])){
		if(IsAjax()){
			exit("ERROR");
		}
		GO(ADMIN_FILE.'?exe=articles');
	}
	System::database()->Select('articles', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	if(System::database()->NumRows() > 0){
		$r = System::database()->FetchRow();
		if($r['active'] == 1){
			$en = '0';
			$tree->CalcFileCounter($r['cat_id'], false);
		}else{
			$en = '1';
			$tree->CalcFileCounter($r['cat_id'], true);
		}
		System::database()->Update('articles', "active='$en'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	}
	if(IsAjax()){
		exit("OK");
	}
	GO(ADMIN_FILE.'?exe=articles');
}

// �������� ������
function AdminArticlesDelete(){
	global $tree, $editarticles;
	if(!$editarticles){
		System::admin()->AccessDenied();
	}
	if(!isset($_GET['id'])){
		GO(ADMIN_FILE.'?exe=articles');
	}
	if(IsAjax() || isset($_GET['ok']) && $_GET['ok'] == '1'){
		$id = SafeEnv($_GET['id'], 11, int);
		$r = System::database()->Select('articles', "`id`='".$id."'");
		$tree->CalcFileCounter($r[0]['cat_id'], false);
		System::database()->Delete('articles', "`id`='$id'");
		System::database()->Delete('articles_comments', "`object_id`='$id'");
		if(isset($_REQUEST['back'])){
			GoRefererUrl($_REQUEST['back']);
		}else{
			GO(ADMIN_FILE.'?exe=articles');
		}
	}else{
		System::admin()->AddCenterBox('�������� ������');
		System::database()->Select('articles', "`id`='".SafeEnv($_REQUEST['id'], 11, int)."'");
		$article = System::database()->FetchRow();
		$id = SafeDB($_REQUEST['id'], 11, int);
		$back = SafeDB($_REQUEST['back'], 255, str);
		System::admin()->HighlightConfirmNoAjax('������� ������ "'.SafeDB($article['title'], 255, str).'"?', ADMIN_FILE.'?exe=articles&a=delete&id='.$id.'&ok=1&back='.$back);
	}
}

// ����� �������� ���������� ������
function AdminArticlesResetHits(){
	global $editarticles;
	if(!$editarticles){
		System::admin()->AccessDenied();
	}
	if(isset($_GET['id'])){
		System::database()->Update('articles', "hits='0'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	}
	GO(ADMIN_FILE.'?exe=articles');
}

// ����� ������ ������
function AdminArticlesResetRating(){
	global $editarticles;
	if(!$editarticles){
		System::admin()->AccessDenied();
	}
	System::database()->Update('articles', "num_votes='0',all_votes='0'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	GO(ADMIN_FILE.'?exe=articles');
}

