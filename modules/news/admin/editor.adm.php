<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(!$user->CheckAccess2('news', 'news_edit')){
	AddTextBox('Ошибка', $config['general']['admin_accd']);
	return;
}

$site->AddJSFile('news.js');
$status = 0;
$topic_id = -1;
$auth_id = -1;
$menuurl = GenMenuUrl($status, $topic_id, $auth_id);
$topic_id = 0; #Номер темы
$newstitle = ''; # Заголовок новости
$icon = ''; # Иконки
$stext = ''; # Короткая новость
$ctext = ''; # Полная новость
$view = array('1'=>false, '2'=>false, '3'=>false, '4'=>false); # Кто видит
$allow_comments = array(false, false); # Разрешить комментарии
$auto_br = array(false, false); # Авто добавление тега <br />
$enabled = array(false, false); # Включить да/нет
$alname = 'Разместить'; # Надпись на отправляющей кнопке
$img_view = 0;

//Модуль SEO
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
		AddTextBox('Ошибка', '<center>Неправильный формат файла. Можно загружать только изображения формата GIF, JPEG или PNG.</center>');
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
	//Модуль SEO
	$seo_title = htmlspecialchars($_POST['seo_title']);
	$seo_keywords = htmlspecialchars($_POST['seo_keywords']);
	$seo_description = htmlspecialchars($_POST['seo_description']);
	//
}
if($action == 'add'){ // Добавление новости
	TAddSubTitle('Добавление новости');
	$view[4] = true;
	$show_on_home[1] = true;
	$allow_comments[1] = true;
	$auto_br[0] = true;
	$enabled[1] = true;
	$title = 'Добавление новости';
	$a = 'save';
	$met = 'add';
}elseif($action == 'addpreview'){
	TAddSubTitle('Добавление новости');
	TAddSubTitle('Предпросмотр');
	$site->AddCSSFile('news.css');
	AcceptPOST();
	$title = 'Добавление новости';
	AddTextBox('Предпросмотр', AdminRenderPreviewNews($newstitle, $stext, $ctext, ($auto_br[1] == true ? true : false)));
	$stext = htmlspecialchars($stext);
	$ctext = htmlspecialchars($ctext);
	$met = 'add';
}elseif($action == 'edit'){ // Редактирование новости
	TAddSubTitle('Редактирование новости');
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
	//Модуль SEO
	$seo_title = SafeDB($news['seo_title'], 255, str);
	$seo_keywords = SafeDB($news['seo_keywords'], 255, str);
	$seo_description = SafeDB($news['seo_description'], 255, str);
	//
	$alname = 'Сохранить';
	$title = 'Редактирование новости';
	$met = 'edit&id='.SafeEnv($_GET['id'], 11, int);
}elseif($action == 'editpreview'){
	TAddSubTitle('Редактирование новости');
	TAddSubTitle('Предпросмотр');
	$site->AddCSSFile('news.css');
	AcceptPOST();
	$title = 'Редактирование новости';
	$alname = 'Сохранить';
	AddTextBox('Предпросмотр', AdminRenderPreviewNews($newstitle, $stext, $ctext, ($auto_br[1] == true ? true : false)));
	$stext = htmlspecialchars($stext);
	$ctext = htmlspecialchars($ctext);
	$met = 'edit&id='.SafeEnv($_GET['id'], 11, int);
}
unset($news);
//Создаем данные форм
//Разделы
$db->Select('news_topics', '');
$topicdata = array();
while($topic = $db->FetchRow()){
	$site->DataAdd($topicdata, $topic['id'], $topic['title'], ($topic['id'] == $topic_id));
}
if(count($topicdata) == 0){
	AddTextBox($title, 'Нет раздела для добавления. Создайте хотя бы один раздел.');
	return;
}
$visdata = array();
//Кто видет
$site->DataAdd($visdata, 'all', 'Все', $view['4']);
$site->DataAdd($visdata, 'members', 'Только пользователи', $view['2']);
$site->DataAdd($visdata, 'guests', 'Только гости', $view['3']);
$site->DataAdd($visdata, 'admins', 'Только администраторы', $view['1']);
$img_view_data = array();
$site->DataAdd($img_view_data, '0', 'Авто', $img_view == 0);
$site->DataAdd($img_view_data, '1', 'Исходная картинка', $img_view == 1);
$site->DataAdd($img_view_data, '2', 'Эскиз', $img_view == 2);
$acts = array();
//Действие: предпросмотр/разместить/сохранить
$site->DataAdd($acts, 'save', $alname);
$site->DataAdd($acts, 'preview', 'Предпросмотр');
FormRow('Раздел', $site->Select('topic_id', $topicdata));
FormRow('Заголовок новости', $site->Edit('title', $newstitle, false, 'style="width:400px;"'));
// Модуль SEO
FormRow('[seo] Заголовок страницы', $site->Edit('seo_title', $seo_title, false, 'style="width:400px;"'));
FormRow('[seo] Ключевые слова', $site->Edit('seo_keywords', $seo_keywords, false, 'style="width:400px;"'));
FormRow('[seo] Описание', $site->Edit('seo_description', $seo_description, false, 'style="width:400px;"'));
//
AdminImageControl('Изображение', 'Загрузить изображение', $icon, $config['news']['icons_dirs'], 'icon', 'up_photo', 'news_editor');
FormRow('Отображение рисунка', $site->Select('img_view', $img_view_data));
FormTextRow('Короткая новость (HTML)', $site->HtmlEditor('shorttext', $stext, 600, 200));
FormTextRow('Полная новость (HTML)', $site->HtmlEditor('continuation', $ctext, 600, 400));
FormRow('Преобразовать текст в HTML', $site->Radio('auto_br', 'on', $auto_br[1]).'Да&nbsp;'.$site->Radio('auto_br', 'off', $auto_br[0]).'Нет');
FormRow('Комментарии', '<nobr>'.$site->Radio('acomments', 'on', $allow_comments[1]).'Разрешить&nbsp;'.$site->Radio('acomments', 'off', $allow_comments[0]).'Запретить</nobr>');
FormRow('Кто видит', $site->Select('view', $visdata));
FormRow('Включить', $site->Radio('enabled', 'on', $enabled[1]).'Да&nbsp;'.$site->Radio('enabled', 'off', $enabled[0]).'Нет');

AddCenterBox($title);
AddForm('<form name="news_editor" action="'.$config['admin_file'].'?exe=news&a=save&m='.$met.$menuurl.'&back='.SaveRefererUrl().'" method="post" enctype="multipart/form-data">', $site->Button('Отмена', 'onclick="history.go(-1)"').$site->Select('action', $acts).$site->Submit('Выполнить'));

?>