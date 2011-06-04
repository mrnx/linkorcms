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
		$method = $_GET['m'];
		if($_POST['action'] == 'save'){
			include (MOD_DIR.'admin/save.adm.php');
		} else{
			if($method == 'add'){
				$action = 'addpreview';
			} else{
				$action = 'editpreview';
			}
			include (MOD_DIR.'admin/editor.adm.php');
		}
		break;
	case 'addpreview':
		include (MOD_DIR.'admin/editor.adm.php');
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
			AddTextBox('Ошибка', 'Доступ запрещён!');
			return;
		}
		AdminConfigurationEdit('news', 'news', false, false, 'Конфигурация модуля "Новости"');
		break;
	case 'configsave':
		if(!$user->CheckAccess2('news', 'news_conf')){
			AddTextBox('Ошибка', 'Доступ запрещён!');
			return;
		}
		AdminConfigurationSave('news&a=config', 'news', false);
		break;
}

function AdminRenderPreviewNews($title, $stext, $ctext, $auto_br){
	if($auto_br){
		$stext = nl2br($stext);
		$ctext = nl2br($ctext);
	}
	$news = '<table cellspacing="0" cellpadding="0" class="newspreview">'.'<tr><td><b>'.$title.'</b><br /><br /></td></tr>'.'<tr><td>'.$stext.' '.$ctext.'</td></tr>'.'</table>';
	return $news;
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
	$table->total = count($newsdb);
	$table->onpage = $num;
	$table->page = $page;
	$table->sortby = $sortbyid;
	$table->sortdesc = $desc;

	$table->AddColumn('Заголовок');
	$table->AddColumn('Дата', 'left');
	$table->AddColumn('Просмотров', 'right');
	$table->AddColumn('Комментарий', 'right');
	$table->AddColumn('Кто видит', 'center');
	$table->AddColumn('Статус', 'center');
	$table->AddColumn('Функции', 'center', false);

	$newsdb = ArrayPage($newsdb, $num, $page); // Берем только новости с текущей страницы
	foreach($newsdb as $news){
		$id = SafeDB($news['id'], 11, int);
		$aed = System::user()->CheckAccess2('news', 'news_edit');

		$status = System::admin()->SpeedStatus('Выключить', 'Включить', ADMIN_FILE.'?exe=news&a=changestatus&id='.$id.'&pv=main', $news['enabled'] == '1', 'images/bullet_green.png', 'images/bullet_red.png');
		$view = ViewLevelToStr(SafeDB($news['view'], 1, int));

		$allowComments = SafeDB($news['allow_comments'], 1, bool);
		$comments = SafeDB($news['comments_counter'], 11, int); // Количество комментарий

		$func = '';
		$func .= System::admin()->SpeedButton('Редактировать', ADMIN_FILE.'?exe=news&a=edit&id='.$id, 'images/admin/edit.png');
		$func .= System::admin()->SpeedButton('Удалить', ADMIN_FILE.'?exe=news&a=delnews&id='.$id, 'images/admin/delete.png');

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
		echo $table->GetRowsJson();
		exit;
	}else{
		System::admin()->AddTextBox('Новости', $table->GetHtml());
	}
}

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

	System::admin()->AddJS("
	function NewsPreviewOpen(){
		window.open('index.php?name=plugins&p=preview&mod=news','Preview','resizable=yes,scrollbars=yes,menubar=no,status=no,location=no,width=640,height=480');
	}");

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

	if(!isset($_GET['id'])){ // Добавление новости
		$auto_br = false;
		$title = 'Добавление новости';
		$caption = 'Добавить';
		TAddSubTitle($title);
		$met = 'add';
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

		$title = 'Редактирование новости';
		$caption = 'Сохранить';
		TAddSubTitle($title);
		$met = 'edit&id='.SafeEnv($_GET['id'], 11, int);
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
	FormRow('Комментарии', System::admin()->Select('acomments', GetEnData($allow_comments, 'Разрешить', 'Запретить')));
	FormRow('Кто видит', System::admin()->Select('view', GetUserTypesFormData($view)));
	FormRow('Включить', System::admin()->Select('enabled', GetEnData($enabled, 'Да', 'Нет')));

	AddCenterBox($title);
	AddForm(
		'<form name="news_editor" action="'.ADMIN_FILE.'?exe=news&a=save&m='.$met.'&back='.SaveRefererUrl().'" method="post" enctype="multipart/form-data">',
	  System::admin()->Button('Отмена', 'onclick="history.go(-1)"')
	  .System::admin()->Button('Предпросмотр', 'onclick="NewsPreviewOpen();"')
	  .System::admin()->Submit($caption)
	);
}

?>