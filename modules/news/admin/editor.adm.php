<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(!$user->CheckAccess2('news', 'news_edit')){
	AddTextBox('������', $config['general']['admin_accd']);
	return;
}

$site->AddJSFile('news.js');
$status = 0;
$topic_id = -1;
$auth_id = -1;
$menuurl = GenMenuUrl($status, $topic_id, $auth_id);
$topic_id = 0; #����� ����
$newstitle = ''; # ��������� �������
$icon = ''; # ������
$stext = ''; # �������� �������
$ctext = ''; # ������ �������
$view = array('1'=>false, '2'=>false, '3'=>false, '4'=>false); # ��� �����
$allow_comments = array(false, false); # ��������� �����������
$auto_br = array(false, false); # ���� ���������� ���� <br />
$enabled = array(false, false); # �������� ��/���
$alname = '����������'; # ������� �� ������������ ������
$img_view = 0;

//������ SEO
$seo_title = '';
$seo_keywords = '';
$seo_description = '';
//


function AcceptPOST()
{
	global $config, $topic_id, $newstitle,
		$icon, $stext, $ctext,
		$view, $allow_comments, $auto_br,
		$enabled, $img_view, $seo_title,
		$seo_keywords, $seo_description;

	$topic_id = $_POST['topic_id'];
	$newstitle = htmlspecialchars($_POST['title']);
	$NewsImagesDir = $config['news']['icons_dirs'];
	$ThumbsDir = $NewsImagesDir.'thumbs/';
	$error = false;
	$icon = LoadImage('up_photo', $NewsImagesDir, $ThumbsDir, $config['news']['thumb_max_width'], $config['news']['thumb_max_height'], $_POST['icon'], $error);
	if($error){
		AddTextBox('������', '<center>������������ ������ �����. ����� ��������� ������ ����������� ������� GIF, JPEG ��� PNG.</center>');
	}
	$stext = htmlspecialchars($_POST['shorttext']);
	$ctext = htmlspecialchars($_POST['continuation']);
	$view = array('1'=>false, '2'=>false, '3'=>false, '4'=>false);
	$view[ViewLevelToInt($_POST['view'])] = true;
	$allow_comments = array(false, false);
	$allow_comments[EnToInt($_POST['acomments'])] = true;
	$auto_br = array(false, false);
	$auto_br[EnToInt($_POST['auto_br'])] = true;
	$enabled = array(false, false);
	$enabled[EnToInt($_POST['enabled'])] = true;
	$img_view = $_POST['img_view'];
	//������ SEO
	$seo_title = htmlspecialchars($_POST['seo_title']);
	$seo_keywords = htmlspecialchars($_POST['seo_keywords']);
	$seo_description = htmlspecialchars($_POST['seo_description']);
	//
}
if($action == 'add'){ // ���������� �������
	TAddSubTitle('���������� �������');
	$view[4] = true;
	$show_on_home[1] = true;
	$allow_comments[1] = true;
	$auto_br[0] = true;
	$enabled[1] = true;
	$title = '���������� �������';
	$a = 'save';
	$met = 'add';
}elseif($action == 'addpreview'){
	TAddSubTitle('���������� �������');
	TAddSubTitle('������������');
	$site->AddCSSFile('news.css');
	AcceptPOST();
	$title = '���������� �������';
	AddTextBox('������������', AdminRenderPreviewNews($newstitle, $stext, $ctext, ($auto_br[1] == true ? true : false)));
	$stext = htmlspecialchars($stext);
	$ctext = htmlspecialchars($ctext);
	$met = 'add';
}elseif($action == 'edit'){ // �������������� �������
	TAddSubTitle('�������������� �������');
	$db->Select('news', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	$news = $db->FetchRow();
	$topic_id = SafeDB($news['topic_id'], 11, int);
	$newstitle = SafeDB($news['title'], 255, str);
	$icon = RealPath2(SafeDB($news['icon'], 255, str));
	$stext = SafeDB($news['start_text'], 0, str, false);
	$ctext = SafeDB($news['end_text'], 0, str, false);
	$view[SafeDB($news['view'], 1, int)] = true;
	$allow_comments[SafeDB($news['allow_comments'], 1, int)] = true;
	$auto_br[SafeDB($news['auto_br'], 1, int)] = true;
	$enabled[SafeDB($news['enabled'], 1, int)] = true;
	$img_view = SafeDB($news['img_view'], 1, int);
	//������ SEO
	$seo_title = SafeDB($news['seo_title'], 255, str);
	$seo_keywords = SafeDB($news['seo_keywords'], 255, str);
	$seo_description = SafeDB($news['seo_description'], 255, str);
	//
	$alname = '���������';
	$title = '�������������� �������';
	$met = 'edit&id='.SafeEnv($_GET['id'], 11, int);
}elseif($action == 'editpreview'){
	TAddSubTitle('�������������� �������');
	TAddSubTitle('������������');
	$site->AddCSSFile('news.css');
	AcceptPOST();
	$title = '�������������� �������';
	$alname = '���������';
	AddTextBox('������������', AdminRenderPreviewNews($newstitle, $stext, $ctext, ($auto_br[1] == true ? true : false)));
	$stext = htmlspecialchars($stext);
	$ctext = htmlspecialchars($ctext);
	$met = 'edit&id='.SafeEnv($_GET['id'], 11, int);
}
unset($news);
//������� ������ ����
//�������
$db->Select('news_topics', '');
$topicdata = array();
while($topic = $db->FetchRow()){
	$site->DataAdd($topicdata, $topic['id'], $topic['title'], ($topic['id'] == $topic_id));
}
if(count($topicdata) == 0){
	AddTextBox($title, '��� ������� ��� ����������. �������� ���� �� ���� ������.');
	return;
}
$visdata = array();
//��� �����
$site->DataAdd($visdata, 'all', '���', $view['4']);
$site->DataAdd($visdata, 'members', '������ ������������', $view['2']);
$site->DataAdd($visdata, 'guests', '������ �����', $view['3']);
$site->DataAdd($visdata, 'admins', '������ ��������������', $view['1']);
$img_view_data = array();
$site->DataAdd($img_view_data, '0', '����', $img_view == 0);
$site->DataAdd($img_view_data, '1', '�������� ��������', $img_view == 1);
$site->DataAdd($img_view_data, '2', '�����', $img_view == 2);
$acts = array();
//��������: ������������/����������/���������
$site->DataAdd($acts, 'save', $alname);
$site->DataAdd($acts, 'preview', '������������');
FormRow('������', $site->Select('topic_id', $topicdata));
FormRow('��������� �������', $site->Edit('title', $newstitle, false, 'style="width:400px;"'));
// ������ SEO
FormRow('[seo] ��������� ��������', $site->Edit('seo_title', $seo_title, false, 'style="width:400px;"'));
FormRow('[seo] �������� �����', $site->Edit('seo_keywords', $seo_keywords, false, 'style="width:400px;"'));
FormRow('[seo] ��������', $site->Edit('seo_description', $seo_description, false, 'style="width:400px;"'));
//
AdminImageControl('�����������', '��������� �����������', $icon, $config['news']['icons_dirs'], 'icon', 'up_photo', 'news_editor');
FormRow('����������� �������', $site->Select('img_view', $img_view_data));
FormTextRow('�������� ������� (HTML)', $site->HtmlEditor('shorttext', $stext, 600, 200));
FormTextRow('������ ������� (HTML)', $site->HtmlEditor('continuation', $ctext, 600, 400));
FormRow('������������� ����� � HTML', $site->Radio('auto_br', 'on', $auto_br[1]).'��&nbsp;'.$site->Radio('auto_br', 'off', $auto_br[0]).'���');
FormRow('�����������', '<nobr>'.$site->Radio('acomments', 'on', $allow_comments[1]).'���������&nbsp;'.$site->Radio('acomments', 'off', $allow_comments[0]).'���������</nobr>');
FormRow('��� �����', $site->Select('view', $visdata));
FormRow('��������', $site->Radio('enabled', 'on', $enabled[1]).'��&nbsp;'.$site->Radio('enabled', 'off', $enabled[0]).'���');

AddCenterBox($title);
AddForm('<form name="news_editor" action="'.$config['admin_file'].'?exe=news&a=save&m='.$met.$menuurl.'&back='.SaveRefererUrl().'" method="post" enctype="multipart/form-data">', $site->Button('������', 'onclick="history.go(-1)"').$site->Select('action', $acts).$site->Submit('���������'));

?>