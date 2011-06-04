<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('�������');

if(!System::user()->CheckAccess2('news', 'news')){
	AddTextBox('������', '������ ��������');
	return;
}
$news_access_editnews = System::user()->CheckAccess2('news', 'news_edit');
$news_access_edittopics = System::user()->CheckAccess2('news', 'edit_topics');
$news_access_editconfig = System::user()->CheckAccess2('news', 'news_conf');

include_once System::config('inc_dir').'configuration/functions.php';

$action = 'main';
if(isset($_GET['a'])) $action = $_GET['a'];

TAddToolLink('�������', 'main', 'news');
if($news_access_editnews) TAddToolLink('�������� �������', 'add', 'news&a=add');
if($news_access_edittopics) TAddToolLink('���������� ���������', 'topics', 'news&a=topics');
if($news_access_editconfig) TAddToolLink('������������', 'config', 'news&a=config');
TAddToolBox($action);

switch($action){
	case 'main':
		AdminNewsMain();
		break;
	case 'add':
	case 'edit':
		AdminNewsEditor();
		break;
	case 'save':
		AdminNewsSave();
		break;
	case 'delnews':
		include (MOD_DIR.'admin/delete.adm.php');
		break;
	case 'changestatus':
		include (MOD_DIR.'admin/status.adm.php');
		break;
	case 'topics':
		include (MOD_DIR.'admin/topics.adm.php');
		break;
	case 'savetopic':
	case 'addtopic':
		include (MOD_DIR.'admin/savetopic.adm.php');
		break;
	case 'deltopic':
		include (MOD_DIR.'admin/topic_delete.php');
		break;
	case 'edittopic':
		include (MOD_DIR.'admin/topic_edit.php');
		break;
	case 'config':
		if(!$user->CheckAccess2('news', 'news_conf')){
			AddTextBox('������', '������ ��������!');
			return;
		}
		AdminConfigurationEdit('news', 'news', false, false, '������������ ������ "�������"');
		break;
	case 'configsave':
		if(!$user->CheckAccess2('news', 'news_conf')){
			AddTextBox('������', '������ ��������!');
			return;
		}
		AdminConfigurationSave('news&a=config', 'news', false);
		break;
}

function CalcNewsCounter($topic_id, $inc){
	System::database()->Select('news_topics', "`id`='".$topic_id."'");
	$topic = System::database()->FetchRow();
	if($inc == true){
		$counter_val = $topic['counter']+1;
	} else{
		$counter_val = $topic['counter']-1;
	}
	System::database()->Update('news_topics', "counter='".$counter_val."'", "`id`='".$topic_id."'");
}

/**
 * ������� ��������, ������ ��������
 * @return void
 */
function AdminNewsMain(){
	System::admin()->AddSubTitle('�������');

	// ���������� �������� �� ��������
	if(isset($_REQUEST['onpage'])){
		$num = intval($_REQUEST['onpage']);
	}else{
		$num = System::config('news/newsonpage');
	}
	if(isset($_REQUEST['page'])){
		$page = intval($_REQUEST['page']);
		if($page > 1){
			$pageparams = '&page='.$page;
		}
	}else{
		$page = 1;
		$pageparams = '';
	}

	$newsdb = System::database()->Select('news');
	$columns = array('title', 'date', 'hit_counter', 'comments_counter', 'view', 'enabled');
	$sortby = 'date';
	$sortbyid = 1;
	$desc = true;
	if(isset($_REQUEST['sortby'])){
		$sortby = $columns[$_REQUEST['sortby']];
		$sortbyid = intval($_REQUEST['sortby']);
		$desc = $_REQUEST['desc'] == '1';
	}
	SortArray($newsdb, $sortby, $desc);

	// ������� �������
	UseScript('jquery_ui_table');
	$table = new jQueryUiTable();
	$table->listing = ADMIN_FILE.'?exe=news&ajax';
	$table->total = count($newsdb);
	$table->onpage = $num;
	$table->page = $page;
	$table->sortby = $sortbyid;
	$table->sortdesc = $desc;

	$table->AddColumn('���������');
	$table->AddColumn('����', 'left');
	$table->AddColumn('����������', 'right');
	$table->AddColumn('�����������', 'right');
	$table->AddColumn('��� �����', 'center');
	$table->AddColumn('������', 'center');
	$table->AddColumn('�������', 'center', false);

	$newsdb = ArrayPage($newsdb, $num, $page); // ����� ������ ������� � ������� ��������
	foreach($newsdb as $news){
		$id = SafeDB($news['id'], 11, int);
		$aed = System::user()->CheckAccess2('news', 'news_edit');

		$status = System::admin()->SpeedStatus('���������', '��������', ADMIN_FILE.'?exe=news&a=changestatus&id='.$id.'&pv=main', $news['enabled'] == '1', 'images/bullet_green.png', 'images/bullet_red.png');
		$view = ViewLevelToStr(SafeDB($news['view'], 1, int));

		$allowComments = SafeDB($news['allow_comments'], 1, bool);
		$comments = SafeDB($news['comments_counter'], 11, int); // ���������� �����������

		$func = '';
		$func .= System::admin()->SpeedButton('�������������', ADMIN_FILE.'?exe=news&a=edit&id='.$id, 'images/admin/edit.png');
		$func .= System::admin()->SpeedButton('�������', ADMIN_FILE.'?exe=news&a=delnews&id='.$id, 'images/admin/delete.png');

		$table->AddRow(
			$id,
			'<b><a href="'.ADMIN_FILE.'?exe=news&a=edit&id='.$id.'">'.SafeDB($news['title'], 255, str).'</a></b>',
			TimeRender(SafeDB($news['date'], 11, int)),
			SafeDB($news['hit_counter'], 11, int),
			($allowComments ? $comments : '���������� �������'),
			$view,
			$status,
			$func
		);
	}

	if(isset($_GET['ajax'])){
		echo $table->GetRowsJson();
		exit;
	}else{
		System::admin()->AddTextBox('�������', $table->GetHtml());
	}
}

/**
 * �������� �������� (�������������� / ����������)
 * @return void
 */
function AdminNewsEditor(){
	global $news_access_editnews;

	if(!$news_access_editnews){
		AddTextBox('������', '������ ��������');
		return;
	}

	System::admin()->AddJS("
	function NewsPreviewOpen(){
		window.open('index.php?name=plugins&p=preview&mod=news','Preview','resizable=yes,scrollbars=yes,menubar=no,status=no,location=no,width=640,height=480');
	}");

	$topic_id = 0; // ����� ����
	$newstitle = ''; // ��������� �������
	$icon = ''; // ������
	$stext = ''; // �������� �������
	$ctext = ''; // ������ �������
	$view = 4; // ��� �����
	$allow_comments = true; // ��������� �����������
	$auto_br = false; // ���� ���������� ���� <br />
	$enabled = true; // �������� ��/���
	$alname = '����������'; // ������� �� ������������ ������
	$img_view = 0;
	//������ SEO
	$seo_title = '';
	$seo_keywords = '';
	$seo_description = '';

	if(!isset($_GET['id'])){ // ���������� �������
		$auto_br = false;
		$title = '���������� �������';
		$caption = '��������';
		TAddSubTitle($title);
		$met = '';
	}else{ // �������������� �������
		System::database()->Select('news', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
		$news = System::database()->FetchRow();
		$topic_id = SafeDB($news['topic_id'], 11, int);
		$newstitle = SafeDB($news['title'], 255, str);
		$icon = RealPath2(SafeDB($news['icon'], 255, str));
		$stext = SafeDB($news['start_text'], 0, str, false);
		$ctext = SafeDB($news['end_text'], 0, str, false);
		$allow_comments = SafeDB($news['allow_comments'], 1, bool);
		$auto_br = SafeDB($news['auto_br'], 1, bool);
		$img_view = SafeDB($news['img_view'], 1, int);

		$view = SafeDB($news['view'], 1, int);
		$enabled = SafeDB($news['enabled'], 1, bool);

		//������ SEO
		$seo_title = SafeDB($news['seo_title'], 255, str);
		$seo_keywords = SafeDB($news['seo_keywords'], 255, str);
		$seo_description = SafeDB($news['seo_description'], 255, str);

		$title = '�������������� �������';
		$caption = '���������';
		TAddSubTitle($title);
		$met = '&id='.SafeEnv($_GET['id'], 11, int);
	}

	System::database()->Select('news_topics', '');
	$topicdata = array();
	while($topic = System::database()->FetchRow()){
		System::admin()->DataAdd($topicdata, $topic['id'], $topic['title'], ($topic['id'] == $topic_id));
	}
	if(count($topicdata) == 0){
		AddTextBox($title, '��� ������� ��� ����������. �������� ���� �� ���� ������.');
		return;
	}

	$img_view_data = array();
	System::admin()->DataAdd($img_view_data, '0', '����', $img_view == 0);
	System::admin()->DataAdd($img_view_data, '1', '�������� ��������', $img_view == 1);
	System::admin()->DataAdd($img_view_data, '2', '�����', $img_view == 2);

	$acts = array();
	System::admin()->DataAdd($acts, 'save', $alname);
	System::admin()->DataAdd($acts, 'preview', '������������');

	FormRow('������', System::admin()->Select('topic_id', $topicdata));
	FormRow('��������� �������', System::admin()->Edit('title', $newstitle, false, 'style="width:400px;"'));

	// ������ SEO
	FormRow('[seo] ��������� ��������', System::admin()->Edit('seo_title', $seo_title, false, 'style="width:400px;"'));
	FormRow('[seo] �������� �����', System::admin()->Edit('seo_keywords', $seo_keywords, false, 'style="width:400px;"'));
	FormRow('[seo] ��������', System::admin()->Edit('seo_description', $seo_description, false, 'style="width:400px;"'));

	AdminImageControl('�����������', '��������� �����������', $icon, System::config('news/icons_dirs'), 'icon', 'up_photo', 'news_editor');
	FormRow('����������� �������', System::admin()->Select('img_view', $img_view_data));
	FormTextRow('�������� ������� (HTML)', System::admin()->HtmlEditor('shorttext', $stext, 600, 200));
	FormTextRow('������ ������� (HTML)', System::admin()->HtmlEditor('continuation', $ctext, 600, 400));

	FormRow('������������� ����� � HTML', System::admin()->Select('auto_br', GetEnData($auto_br, '��', '���')));
	FormRow('�����������', System::admin()->Select('acomments', GetEnData($allow_comments, '���������', '���������')));
	FormRow('��� �����', System::admin()->Select('view', GetUserTypesFormData($view)));
	FormRow('��������', System::admin()->Select('enabled', GetEnData($enabled, '��', '���')));

	AddCenterBox($title);
	AddForm(
		'<form name="news_editor" action="'.ADMIN_FILE.'?exe=news&a=save'.$met.'&back='.SaveRefererUrl().'" method="post" enctype="multipart/form-data">',
	  System::admin()->Button('������', 'onclick="history.go(-1)"')
	  .System::admin()->Button('������������', 'onclick="NewsPreviewOpen();"')
	  .System::admin()->Submit($caption)
	);
}

function AdminNewsSave(){
	global $news_access_editnews;

	if(!$news_access_editnews){
		AddTextBox('������', '������ ��������');
		return;
	}

	$author = System::user()->Get('u_name');

	// �������� ���������
	$topic_id = SafeEnv($_POST['topic_id'], 11, int);
	$title = SafeEnv($_POST['title'], 255, str);
	// ������ SEO
	$seo_title = SafeEnv($_POST['seo_title'], 255, str);
	$seo_keywords = SafeEnv($_POST['seo_keywords'], 255, str);
	$seo_description = SafeEnv($_POST['seo_description'], 255, str);
	//
	$allow_comments = EnToInt($_POST['acomments']);

	$NewsImagesDir = RealPath2(System::config('news/icons_dirs'));
	$ThumbsDir = $NewsImagesDir.'thumbs/';
	$error = false;
	$icon = LoadImage(
			'up_photo',
			$NewsImagesDir,
			$ThumbsDir,
			System::config('news/thumb_max_width'),
			System::config('news/thumb_max_height'),
			$_POST['icon'],
			$error
	);

	if($error){
		AddTextBox('������', '<center>������������ ������ �����. ����� ��������� ������ ����������� ������� GIF, JPEG ��� PNG.<br /><a href="javascript:history.go(-1)">�����</a></center>');
		return;
	}

	$start_text = SafeEnv($_POST['shorttext'],0,str, false);
	$end_text = SafeEnv($_POST['continuation'],0,str, false);
	$auto_br = EnToInt($_POST['auto_br']);
	$view = ViewLevelToInt(SafeEnv($_POST['view'],15,str));
	$enabled = EnToInt($_POST['enabled']);
	$img_view = SafeEnv($_POST['img_view'],1,int);

	$comments_counter = 0;
	$hit_counter = 0;

	if(isset($_GET['id'])){
		$id = SafeEnv($_GET['id'],11,int);
		System::database()->Select('news',"`id`='$id'");
		$news = System::database()->FetchRow();
		$author = SafeDB($news['author'], 255, str);
		$comments_counter = SafeDB($news['comments_counter'], 11, int);
		$hit_counter = SafeDB($news['hit_counter'], 11, int);
		$date = SafeDB($news['date'], 11, int);

		if($topic_id != $news['topic_id'] && $news['enabled'] == 1){
			CalcNewsCounter($news['topic_id'], false);
			CalcNewsCounter($topic_id, true);
		}

		if($enabled != $news['enabled']){
			CalcNewsCounter($topic_id, $enabled);
		}
	}else{
		$date = time();
	}

	$vals = Values('',$title,$date,$author,$topic_id,
	$allow_comments,$icon,$start_text,$end_text,$auto_br,
	$comments_counter,$hit_counter,$view,$enabled, $img_view,
	$seo_title, $seo_keywords, $seo_description);

	if(isset($id)){
		System::database()->Update('news', $vals, "`id`='".SafeEnv($_GET['id'],11,int)."'",true);
	}else{
		System::database()->Insert('news', $vals);
		CalcNewsCounter($topic_id, true);
	}

	$bcache = LmFileCache::Instance();
	$bcache->Delete('block', 'news1');
	$bcache->Delete('block', 'news2');
	$bcache->Delete('block', 'news3');
	$bcache->Delete('block', 'news4');

	GoRefererUrl($_GET['back']);
	AddTextBox('���������', '��������� ���������.');
}

?>