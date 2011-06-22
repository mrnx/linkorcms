<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('Новости');

if(!System::user()->CheckAccess2('news', 'news')){
	AddTextBox('Ошибка', 'Доступ запрещён');
	return;
}
$news_access_editnews = System::user()->CheckAccess2('news', 'news_edit');
$news_access_edittopics = System::user()->CheckAccess2('news', 'edit_topics');
$news_access_editconfig = System::user()->CheckAccess2('news', 'news_conf');

include_once System::config('inc_dir').'configuration/functions.php';

$action = 'main';
if(isset($_GET['a'])) $action = $_GET['a'];

TAddToolLink('Главная', 'main', 'news');
if($news_access_editnews) TAddToolLink('Добавить новость', 'add', 'news&a=add');
if($news_access_edittopics) TAddToolLink('Управление разделами', 'topics', 'news&a=topics');
if($news_access_editconfig) TAddToolLink('Конфигурация', 'config', 'news&a=config');
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
	case 'delete':
		AdminNewsDelete();
		break;
	case 'changestatus':
		AdminNewsChangeStatus();
		break;
	case 'topics':
		AdminNewsTopics();
		break;
	case 'savetopic':
	case 'addtopic':
		AdminNewsTopicSave();
		break;
	case 'deltopic':
		AdminNewsTopicsDelete();
		break;
	case 'edittopic':
		AdminNewsEditTopic();
		break;
	case 'config':
		if(!$news_access_editconfig){
			AddTextBox('Ошибка', 'Доступ запрещён!');
			return;
		}
		AdminConfigurationEdit('news', 'news', false, false, 'Конфигурация модуля "Новости"');
		break;
	case 'configsave':
		if(!$news_access_editconfig){
			AddTextBox('Ошибка', 'Доступ запрещён!');
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
 * Главная страница, список новостей
 * @return void
 */
function AdminNewsMain(){
	System::admin()->AddSubTitle('Главная');

	// Количество новостей на странице
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

	// Выводим новости
	UseScript('jquery_ui_table');
	$table = new jQueryUiTable();
	$table->listing = ADMIN_FILE.'?exe=news&ajax';
	$table->del = ADMIN_FILE.'?exe=news&a=delete';
	$table->total = count($newsdb);
	$table->onpage = $num;
	$table->page = $page;
	$table->sortby = $sortbyid;
	$table->sortdesc = $desc;

	$table->AddColumn('Заголовок');
	$table->AddColumn('Дата', 'left', true, true);
	$table->AddColumn('Просмотров', 'right');
	$table->AddColumn('Комментарий', 'right');
	$table->AddColumn('Кто видит', 'center');
	$table->AddColumn('Статус', 'center');
	$table->AddColumn('Функции', 'center', false, true);

	$newsdb = ArrayPage($newsdb, $num, $page); // Берем только новости с текущей страницы
	foreach($newsdb as $news){
		$id = SafeDB($news['id'], 11, int);
		$aed = System::user()->CheckAccess2('news', 'news_edit');

		$status = System::admin()->SpeedStatus(
			'Выключить', 'Включить',
			ADMIN_FILE.'?exe=news&a=changestatus&id='.$id, $news['enabled'],
			'images/bullet_green.png', 'images/bullet_red.png'
		);
		$view = ViewLevelToStr(SafeDB($news['view'], 1, int));

		$allowComments = SafeDB($news['allow_comments'], 1, bool);
		$comments = SafeDB($news['comments_counter'], 11, int); // Количество комментарий

		$func = '';
		$func .= System::admin()->SpeedButton('Редактировать', ADMIN_FILE.'?exe=news&a=edit&id='.$id, 'images/admin/edit.png');
		$func .= System::admin()->SpeedConfirmJs(
			'Удалить',
			'$(\'#news_table\').table(\'deleteRow\', '.$id.');',
			'images/admin/delete.png',
			'Уверены, что хотите удалить эту новость?'
		);

		$table->AddRow(
			$id,
			'<b><a href="'.ADMIN_FILE.'?exe=news&a=edit&id='.$id.'">'.SafeDB($news['title'], 255, str).'</a></b>',
			TimeRender(SafeDB($news['date'], 11, int)),
			SafeDB($news['hit_counter'], 11, int),
			($allowComments ? $comments : 'Обсуждение закрыто'),
			$view,
			$status,
			$func
		);
	}
	if(isset($_GET['ajax'])){
		echo $table->GetOptions();
		exit;
	}else{
		System::admin()->AddTextBox('Новости', $table->GetHtml());
	}
}

/**
 * Редактор новостей (редактирование / добавление)
 * @return void
 */
function AdminNewsEditor(){
	global $news_access_editnews;

	if(!$news_access_editnews){
		AddTextBox('Ошибка', 'Доступ запрещён');
		return;
	}

	UseScript('jquery_ui');
	System::admin()->AddJS("
	function NewsPreviewOpen(){
		window.open('index.php?name=plugins&p=preview&mod=news','Preview','resizable=yes,scrollbars=yes,menubar=no,status=no,location=no,width=640,height=480');
	}");
	System::admin()->AddOnLoadJS('
	$( "#datepicker" ).datepicker({
			dateFormat: "dd.mm.yy",
			changeMonth: true,
			changeYear: true
	});');

	$topic_id = 0; // Номер темы
	$newstitle = ''; // Заголовок новости
	$icon = ''; // Иконки
	$stext = ''; // Короткая новость
	$ctext = ''; // Полная новость
	$view = 4; // Кто видит
	$allow_comments = true; // Разрешить комментарии
	$auto_br = false; // Авто добавление тега <br />
	$enabled = true; // Включить да/нет
	$alname = 'Разместить'; // Надпись на отправляющей кнопке
	$img_view = 0;
	//Модуль SEO
	$seo_title = '';
	$seo_keywords = '';
	$seo_description = '';

	$public_date = date("d.m.Y", time());
	$public_time = date("G:i", time());

	if(!isset($_GET['id'])){ // Добавление новости
		$auto_br = false;
		$title = 'Добавление новости';
		$caption = 'Добавить';
		TAddSubTitle($title);
		$met = '';
	}else{ // Редактирование новости
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

		//Модуль SEO
		$seo_title = SafeDB($news['seo_title'], 255, str);
		$seo_keywords = SafeDB($news['seo_keywords'], 255, str);
		$seo_description = SafeDB($news['seo_description'], 255, str);

		$public_date = date("d.m.Y", $news['date']);
		$public_time = date("G:i", $news['date']);

		$title = 'Редактирование новости';
		$caption = 'Сохранить';
		TAddSubTitle($title);
		$met = '&id='.SafeEnv($_GET['id'], 11, int);
	}

	System::database()->Select('news_topics', '');
	$topicdata = array();
	while($topic = System::database()->FetchRow()){
		System::admin()->DataAdd($topicdata, $topic['id'], $topic['title'], ($topic['id'] == $topic_id));
	}
	if(count($topicdata) == 0){
		AddTextBox($title, 'Нет раздела для добавления. Создайте хотя бы один раздел.');
		return;
	}

	$img_view_data = array();
	System::admin()->DataAdd($img_view_data, '0', 'Авто', $img_view == 0);
	System::admin()->DataAdd($img_view_data, '1', 'Исходная картинка', $img_view == 1);
	System::admin()->DataAdd($img_view_data, '2', 'Эскиз', $img_view == 2);

	$acts = array();
	System::admin()->DataAdd($acts, 'save', $alname);
	System::admin()->DataAdd($acts, 'preview', 'Предпросмотр');

	FormRow('Раздел', System::admin()->Select('topic_id', $topicdata));
	FormRow('Заголовок новости', System::admin()->Edit('title', $newstitle, false, 'style="width:400px;"'));

	// Модуль SEO
	FormRow('[seo] Заголовок страницы', System::admin()->Edit('seo_title', $seo_title, false, 'style="width:400px;"'));
	FormRow('[seo] Ключевые слова', System::admin()->Edit('seo_keywords', $seo_keywords, false, 'style="width:400px;"'));
	FormRow('[seo] Описание', System::admin()->Edit('seo_description', $seo_description, false, 'style="width:400px;"'));

	AdminImageControl('Изображение', 'Загрузить изображение', $icon, System::config('news/icons_dirs'), 'icon', 'up_photo', 'news_editor');
	FormRow('Отображение рисунка', System::admin()->Select('img_view', $img_view_data));
	FormTextRow('Короткая новость (HTML)', System::admin()->HtmlEditor('shorttext', $stext, 600, 200));
	FormTextRow('Полная новость (HTML)', System::admin()->HtmlEditor('continuation', $ctext, 600, 400));

	FormRow('Преобразовать текст в HTML', System::admin()->Select('auto_br', GetEnData($auto_br, 'Да', 'Нет')));

	FormRow('Дата и время публикации',
	        System::admin()->Edit('public_date', $public_date, false, 'id="datepicker" style="width:120px;"')
	        .System::admin()->Edit('public_time', $public_time, false, 'style="width:60px;"'));

	FormRow('Комментарии', System::admin()->Select('acomments', GetEnData($allow_comments, 'Разрешить', 'Запретить')));
	FormRow('Кто видит', System::admin()->Select('view', GetUserTypesFormData($view)));
	FormRow('Включить', System::admin()->Select('enabled', GetEnData($enabled, 'Да', 'Нет')));

	AddCenterBox($title);
	AddForm(
		'<form name="news_editor" action="'.ADMIN_FILE.'?exe=news&a=save'.$met.'&back='.SaveRefererUrl().'" method="post" enctype="multipart/form-data">',
	  System::admin()->Button('Отмена', 'onclick="history.go(-1)"')
	  .System::admin()->Button('Предпросмотр', 'onclick="NewsPreviewOpen();"')
	  .System::admin()->Submit($caption)
	);
}

function AdminNewsSave(){
	global $news_access_editnews;

	if(!$news_access_editnews){
		AddTextBox('Ошибка', 'Доступ запрещён');
		return;
	}

	$author = SafeEnv(System::user()->Get('u_name'), 255, str);

	// Получаем параметры
	$topic_id = SafeEnv($_POST['topic_id'], 11, int);
	$title = SafeEnv($_POST['title'], 255, str);
	// Модуль SEO
	$seo_title = SafeEnv($_POST['seo_title'], 255, str);
	$seo_keywords = SafeEnv($_POST['seo_keywords'], 255, str);
	$seo_description = SafeEnv($_POST['seo_description'], 255, str);
	//
	$allow_comments = EnToInt($_POST['acomments']);

	$NewsImagesDir = RealPath2(System::config('news/icons_dirs'));
	$ThumbsDir = $NewsImagesDir.'/thumbs/';
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
		AddTextBox('Ошибка', '<center>Неправильный формат файла. Можно загружать только изображения формата GIF, JPEG или PNG.<br /><a href="javascript:history.go(-1)">Назад</a></center>');
		return;
	}

	$start_text = SafeEnv($_POST['shorttext'], 0, str, false);
	$end_text = SafeEnv($_POST['continuation'], 0, str, false);
	$auto_br = EnToInt($_POST['auto_br']);
	$view = ViewLevelToInt(SafeEnv($_POST['view'],15,str));
	$enabled = EnToInt($_POST['enabled']);
	$img_view = SafeEnv($_POST['img_view'],1,int);

	$public_date = $_POST['public_date'];
	$public_time = $_POST['public_time'];
	$public = strtotime(str_replace('.', '-', $public_date).' '.$public_time);

	$comments_counter = 0;
	$hit_counter = 0;

	if(isset($_GET['id'])){
		$id = SafeEnv($_GET['id'],11,int);
		System::database()->Select('news', "`id`='$id'");
		$news = System::database()->FetchRow();
		$author = SafeEnv($news['author'], 255, str);
		$comments_counter = SafeEnv($news['comments_counter'], 11, int);
		$hit_counter = SafeEnv($news['hit_counter'], 11, int);
		if($topic_id != $news['topic_id'] && $news['enabled'] == 1){
			CalcNewsCounter($news['topic_id'], false);
			CalcNewsCounter($topic_id, true);
		}
		if($enabled != $news['enabled']){
			CalcNewsCounter($topic_id, $enabled);
		}
	}

	$vals = Values('', $title, $public, $author, $topic_id,
	$allow_comments, $icon, $start_text, $end_text, $auto_br,
	$comments_counter, $hit_counter, $view, $enabled, $img_view,
	$seo_title, $seo_keywords, $seo_description);

	if(isset($id)){
		System::database()->Update('news', $vals, "`id`='$id'", true);
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
	AddTextBox('Сообщение', 'Изменения сохранены.');
}

function AdminNewsDelete(){
	global $news_access_editnews;

	if(!isset($_POST['id']) || !$news_access_editnews){
		exit('ERROR');
	}

	$id = SafeEnv($_POST['id'], 11, int);
	System::database()->Select('news', "`id`='$id'");
	$news = System::database()->FetchRow();

	System::database()->Delete('news', "`id`='$id'");
	System::database()->Delete('news_comments', "`object_id`='$id'");
	if($news['enabled']){
		CalcNewsCounter(SafeDB($news['topic_id'], 11, int), false);
	}

	$bcache = LmFileCache::Instance();
	$bcache->Delete('block', 'news1');
	$bcache->Delete('block', 'news2');
	$bcache->Delete('block', 'news3');
	$bcache->Delete('block', 'news4');

	exit('OK');
}

function AdminNewsChangeStatus(){
	global $news_access_editnews;

	if(!isset($_POST['id']) || !$news_access_editnews){
		exit('ERROR');
	}

	$id = SafeEnv($_GET['id'], 11, int);
	System::database()->Select('news', "`id`='$id'");
	$news = System::database()->FetchRow();
	$enabled = ($news['enabled'] ? '0' : '1');
	CalcNewsCounter(SafeDB($news['topic_id'], 11, int), $enabled);
	System::database()->Update('news', "enabled='$enabled'", "`id`='$id'");

	$bcache = LmFileCache::Instance();
	$bcache->Delete('block', 'news1');
	$bcache->Delete('block', 'news2');
	$bcache->Delete('block', 'news3');
	$bcache->Delete('block', 'news4');

	exit('OK');
}

function AdminNewsTopics(){
	global $news_access_edittopics;

	if(!$news_access_edittopics){
		AddTextBox('Ошибка', 'Доступ запрещён');
		return;
	}

	AddCenterBox('Текущие новостные разделы');
	$topics = System::database()->Select('news_topics');
	$icons_dir = System::config('news/icons_dirs');

	$cntr = 0;
	$text = '<table style="width: 100%; border: 1px #ABC5D8 solid; background-color: #fff; border-collapse: inherit; padding: 10px;">';
	foreach($topics as $i=>$topic){
		$topic_id = SafeDB($topic['id'], 11, int);
		$edit_url = ADMIN_FILE.'?exe=news&a=edittopic&id='.$topic_id;
		$edit = System::admin()->SpeedButton('Редактировать', $edit_url, 'images/admin/edit.png');
		$del = System::admin()->SpeedAjax(
			'Удалить',
			ADMIN_FILE.'?exe=news&a=deltopic&id='.$topic_id,
			'images/admin/delete.png',
			'Удалить раздел? Все новости в этом разделе так-же будут удалены.',
			'',
			"$('#topic_$topic_id').children('div').fadeOut('slow');"
		);
		if($cntr % 4 == 0) $text .= '<tr>';
		$text .= '<td id="topic_'.$topic_id.'" valign="top" align="center" style="padding: 10px;"><div>';
		$text .= '<b><a href="'.$edit_url.'">'.SafeDB($topic['title'], 255, str).'</a></b> ('. SafeDB($topic['counter'], 11, int).')';
		if(is_file($icons_dir.SafeDB($topic['image'], 255, str))){
			$text .= '<br /><a href="'.$edit_url.'"><img src="'.$icons_dir.SafeDB($topic['image'], 255, str).'" height="80" title="'.SafeDB($topic['description'], 255, str).'" /></a>';
		}
		$text .= '<br />'.$edit.' '.$del.'';
		$text .= '</div></td>';
		$cntr++;
		if($cntr % 4 == 0) $text .= '</tr>';
	}
	if($cntr % 4 != 0) $text .= '</tr>';

	$text .= '</table>';
	$text .= '<br />.:Создать новый раздел:.<br />';
	AddText($text);

	FormRow('Название раздела', System::admin()->Edit('topic_name', '', false, 'maxlength="255" style="width:400px;"'));
	FormTextRow('Описание (HTML)', System::admin()->HtmlEditor('topic_description', '', 600, 200));
	AdminImageControl('Изображение', 'Загрузить изображение', '', $icons_dir, 'topic_image', 'up_photo', 'topicsform');
	AddForm('<form name="topicsform" action="'.ADMIN_FILE.'?exe=news&a=addtopic" method="post" enctype="multipart/form-data">', System::admin()->Submit('Создать'));
}

function AdminNewsTopicSave(){
	global $news_access_edittopics, $action;

	if(!$news_access_edittopics){
		AddTextBox('Ошибка', 'Доступ запрещён');
		return;
	}

	$NewsImagesDir = System::config('news/icons_dirs');
	$ThumbsDir = $NewsImagesDir.'thumbs/';
	$error = false;
	$file = LoadImage('up_photo', $NewsImagesDir, $ThumbsDir, System::config('news/thumb_max_width'), System::config('news/thumb_max_height'), SafeEnv(RealPath2($_POST['topic_image']), 255, str, true), $error);
	if($error){
		AddTextBox('Ошибка', '<center>Неправильный формат файла. Можно загружать только изображения формата GIF, JPEG или PNG.<br /><a href="javascript:history.go(-1)">Назад</a></center>');
		return;
	}

	if($action == 'addtopic'){
		$values = Values('', SafeEnv($_POST['topic_name'], 255, str), SafeEnv($_POST['topic_description'], 255, str), $file, '0');
		System::database()->Insert('news_topics', $values);
	}elseif($action == 'savetopic'){
		$id = SafeEnv($_GET['id'], 11, int);
		System::database()->Select('news_topics', "`id`='".$id."'");
		$topic = System::database()->FetchRow();
		$values = Values('', SafeEnv($_POST['topic_name'], 255, str), SafeEnv($_POST['topic_description'], 255, str), $file, SafeEnv($topic['counter'], 11, int));
		System::database()->Update('news_topics', $values, "`id`='$id'", true);
	}

	GO(ADMIN_FILE.'?exe=news&a=topics');
}

function AdminNewsTopicsDelete(){
	global $news_access_edittopics;

	if(!isset($_GET['id']) || !$news_access_edittopics){
		exit('ERROR');
	}

	$id = SafeEnv($_GET['id'], 11, int);
	System::database()->Delete('news', "`topic_id`='$id'");
	System::database()->Delete('news_coments', "`object`='$id'");
	System::database()->Delete('news_topics', "`id`='$id'");

	exit('OK');
}

function AdminNewsEditTopic(){
	global $news_access_edittopics;

	if(!isset($_GET['id']) || !$news_access_edittopics){
		AddTextBox('Ошибка', 'Доступ запрещён');
		return;
	}

	AddCenterBox('Редактирование раздела');

	$id = SafeEnv($_GET['id'], 11, int);
	System::database()->Select('news_topics', "`id`='".$id."'");
	$topic = System::database()->FetchRow();

	FormRow('Название раздела', System::admin()->Edit('topic_name', SafeDB($topic['title'], 255, str), false, 'maxlength="255" style="width:400px;"'));
	FormTextRow('Описание (HTML)', System::admin()->HtmlEditor('topic_description', SafeDB($topic['description'], 255, str), 600, 200));
	AdminImageControl('Изображение', 'Загрузить изображение', RealPath2(SafeDB($topic['image'], 255, str)), RealPath2(System::config('news/icons_dirs')), 'topic_image', 'up_photo', 'topicsform');
	AddForm(
		'<form name="topicsform" action="'.ADMIN_FILE.'?exe=news&a=savetopic&id='.$id.'" method="post" enctype="multipart/form-data">',
		System::admin()->Button('Отмена', 'onclick="history.go(-1);"').System::admin()->Submit('Сохранить')
	);
}

?>