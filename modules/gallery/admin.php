<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(!$user->CheckAccess2('gallery', 'gallery')){
	AddTextBox('Ошибка', $config['general']['admin_accd']);
	return;
}

TAddSubTitle('Фотогалерея');

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

TAddToolLink('Изображения', 'main', 'gallery');
if($editimages){
	TAddToolLink('Добавить изображения', 'editor', 'gallery&a=editor');
	TAddToolLink('Мультизагрузка', 'upload', 'gallery&a=upload');
}
TAddToolBox($action);

if($editcats){
	TAddToolLink('Категории', 'cats', 'gallery&a=cats');
	TAddToolLink('Добавить категорию', 'cateditor', 'gallery&a=cateditor');
}
TAddToolBox($action);

if($editconf){
	TAddToolLink('Настройки', 'config', 'gallery&a=config');
}
TAddToolBox($action);

switch($action){
	case 'main':
		AdminGalleryMainFunc();
		break;
	case 'editor':
		AdminGalleryEditor();
		break;
	case 'upload':
		AdminGalleryUpload(); // Загрузка фотографий и прием формы
		break;
	case 'deleteuploaded':
		AdminGalleryDeleteUploaded();
		break;
	case 'saveuploaded':
		AdminGallerySaveUploaded();
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
	////////////////// Категории
	case 'cats':
		if(!$editcats){
			AddTextBox('Ошибка', $config['general']['admin_accd']);
		}
		global $tree;
		$result = $tree->ShowCats();
		if($result == false){
			$result = 'Нет категорий для отображения.';
		}
		AddTextBox('Категории', $result);
		break;
	case 'cateditor':
		if(!$editcats){
			AddTextBox('Ошибка', $config['general']['admin_accd']);
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
			AddTextBox('Ошибка', $config['general']['admin_accd']);
		}
		global $tree, $config;
		$tree->EditorSave((isset($_GET['id']) ? SafeEnv($_GET['id'], 11, int) : null));
		GO($config['admin_file'].'?exe=gallery&a=cats');
		break;
	case 'delcat':
		if(!$editcats){
			AddTextBox('Ошибка', $config['general']['admin_accd']);
		}
		global $tree, $config;
		if($tree->DeleteCat(SafeEnv($_GET['id'], 11, int))){
			GO($config['admin_file'].'?exe=gallery&a=cats');
		}
		break;
	////////////////// Настройки
	case 'config':
		if(!$editconf){
			AddTextBox('Ошибка', $config['general']['admin_accd']);
		}
		AdminConfigurationEdit('gallery', 'gallery', false, false, 'Конфигурация модуля "Галерея"');
		break;
	case 'configsave':
		if(!$editconf){
			AddTextBox('Ошибка', $config['general']['admin_accd']);
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
	$site->DataAdd($data, -1, 'Все изображения', $cat == -1);
	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 11, int);
	}else{
		$page = 1;
	}
	AddCenterBox('Фото');
	$text = '';
	$text = '<form name="categories" method="get">'.'<table cellspacing="0" cellpadding="0" border="0" width="100%" align="center"><tr><td align="center" class="contenttd">'.'Выберите категорию: '.$site->Hidden('exe', 'gallery').$site->Select('cat', $data).$site->Submit('Показать').'</td></tr></table></form><br />';
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
	$text .= '<tr><th>Изображение</th><th>Просмотров</th><th>Комментарии</th>'.($vrating ? '<th>Оценка</th>' : '').'<th>Просматривают</th><th>Статус</th><th>Функции</th></tr>';
	foreach($r as $img){
		$id = SafeDB($img['id'], 11, int);
		switch($img['show']){
			case '1':
				$st = '<font color="#008000">Вкл.</font></a>';
				break;
			case '0':
				$st = '<font color="#FF0000">Выкл.</font>';
				break;
		}
		if($editimages){
			$st = '<a href="'.$config['admin_file'].'?exe=gallery&a=changestatus&id='.SafeDB($img['id'], 11, int).'">'.$st.'</a>';
		}
		if($editimages){
			$func = '';
			$func .= SpeedButton('Обновить эскиз', $config['admin_file'].'?exe=gallery&a=refreshthumb&id='.$id, 'images/admin/refresh.png');
			$func .= SpeedButton('Редактировать', $config['admin_file'].'?exe=gallery&a=editor&id='.$id, 'images/admin/edit.png');
			$func .= SpeedButton('Удалить', $config['admin_file'].'?exe=gallery&a=delete&id='.$id.'&ok=0', 'images/admin/delete.png');
		}else{
			$func = '-';
		}
		$filename = SafeDB($img['file'], 255, str);
		$size = FormatFileSize(filesize($GalleryDir.$filename));
		$asize = getimagesize($GalleryDir.$filename);
		$asize = $asize[0].'x'.$asize[1];
		$vi = ViewLevelToStr(SafeDB($img['view'], 1, int));
			//$rating = '<img src="'.GetRatingImage($img[14],$img[15]).'" border="0" />/ (всего '.$img[14].')'.($editimages?' / <a href="'.$config['admin_file'].'?exe=gallery&a=resetrating&id='.$img[0].'" title="Обнулить счётчик оценок">Сброс</a>':'');
		$text .= '<tr>
		<td><a href="'.$GalleryDir.SafeDB($img['file'], 255, str).'" target="_blank">'.($config['gallery']['show_thumbs'] == 0 ? '<b>'.SafeDB($img['title'], 255, str).'</b>' : '<img title="'.SafeDB($img['title'], 255, str).'" src="'.$ThumbsDir.$filename.'" />
			<br /><b>'.SafeDB($img['title'], 255, str).'</b>')." ($asize, $size)".'</a>
		</td>
		<td>'.SafeDB($img['hits'], 11, int).($editimages ? ' / <a href="'.$config['admin_file'].'?exe=gallery&a=resethits&id='.$id.'" title="Сбросить счётчик">Сброс</a>' : '').'</td>
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
		AddTextBox('Ошибка', $config['general']['admin_accd']);
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
		$top = 'Добавление изображения';
		$cap = 'Добавить';
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
		$top = 'Редактирование изображения';
		$cap = 'Сохранить';
		unset($par);
	}
	$visdata = GetUserTypesFormData($view);
	$cats_data = array();
	$cats_data = $tree->GetCatsData($cat_id);
	if(count($cats_data) == 0){
		AddTextBox($top, 'Нет категорий для добавления! Создайте категорию.');
		return;
	}
	FormRow('В категорию', $site->Select('category', $cats_data));
	FormRow('Заголовок', $site->Edit('title', $title, false, 'maxlength="250" style="width:400px;"'));
	FormRow('Изображение', $site->Edit('image', $file, false, 'style="width:400px;" maxlength="250"').'<br />'.
		$site->FFile('up_image').'<br /><small>Формат изображения только *.jpg, *.jpeg, *.gif, *.png</small><br /><small>Максимальный размер файла: '.ini_get('upload_max_filesize').'</small>');
	FormTextRow('Описание', $site->HtmlEditor('description', $description, 600, 200));
	FormRow('Автор', $site->Edit('author', $author, false, 'style="width:400px;" maxlength="50"'));
	FormRow('E-mail автора', $site->Edit('email', $email, false, 'style="width:400px;" maxlength="50"'));
	FormRow('Сайт автора', $site->Edit('www', $www, false, 'style="width:400px;" maxlength="250"'));
	$enData = GetEnData($allow_comments, 'Разрешить', 'Запретить');
	FormRow('Комментарии', $site->Select('allow_comments', $enData));
	$enData = GetEnData($allow_votes, 'Разрешить', 'Запретить');
	FormRow('Оценки', $site->Select('allow_votes', $enData));
	FormRow('Кто видит', $site->Select('view', $visdata));
	$enData = GetEnData($show, 'Да', 'Нет');
	FormRow('Показать', $site->Select('show', $enData));
	AddCenterBox($top);
	AddForm('<form action="'.ADMIN_FILE.'?exe=gallery&a='.$action.'" method="post" enctype="multipart/form-data">', $site->Button('Отмена', 'onclick="history.go(-1)"').$site->Submit($cap));
}

function AdminGalleryUploadForm(){
	global $tree, $site, $config, $db, $user, $editimages;
	if(!$editimages){
		AddTextBox('Ошибка', $config['general']['admin_accd']);
		return;
	}
	UseScript('swfupload');
	$formid = uniqid(); // Уникальный ID формы
	$_SESSION['uploadforms'][$formid] = array(
		'photos'=>array(),
		'category' => '0',
		'allow_comments' => '1',
		'allow_votes' => '1',
		'view' => '4',
		'show' => '1'
	);

	System::admin()->AddOnLoadJS('
	window.photo_id = 1;
	window.allUploadComplete = false;
	window.photosCountFiles = 0;
	window.photosUploaded = 0;

	window.SubmitFormGuard = function(){
		if(window.photosCountFiles == 0){
			alert("Выберите фотографии для загрузки");
			return false;
		}
		if(!window.allUploadComplete){
			window.swfu.startUpload();
			Admin.ShowSplashScreen("Загрузка фотографий на хостинг");
			return false;
		}
		return true;
	}

	// SWFUpload
	window.swfu = new SWFUpload({
		flash_url: "scripts/swfupload/swfupload.swf",
		upload_url: "'.ADMIN_FILE.'?exe=gallery&a=upload&formid='.$formid.'",
		file_post_name : "up_image",
		post_params: {
			"action": "upload"
		},
		file_size_limit: "100 MB",
		file_types: "*.jpg; *.png; *.jpeg; *.gif",
		file_types_description: "Все файлы",
		file_upload_limit: 0,
		file_queue_limit: 0,
		debug: false,

		button_placeholder_id: "uploadbutton",
		button_width: "54",
		button_height: "18",
		button_window_mode: SWFUpload.WINDOW_MODE.TRANSPARENT,
		button_text: "<span class=\"btnCap\">Обзор<span>",
		button_text_style: ".btnCap{ align: center; color: #4F4F4F; font-family: Verdana, Tahoma, sans-serif; font-weight: bold; }",
		button_text_left_padding: 4,
		button_text_top_padding: 1,

		file_dialog_complete_handler: function(numFilesSelected, numFilesQueued, total){
			$("#uploadFilesCount").html("Выбрано " + total + " файл(ов)");
			window.photosCountFiles = total;
		},
		upload_progress_handler: function(file, bytesLoaded, bytesTotal){
			var current = window.photosUploaded + 1;
			Admin.SetSplashScreenMessage("Загрузка фотографий на хостинг: " + current + "/" + window.photosCountFiles + " (" + Math.round(bytesLoaded/bytesTotal*100) + "%)");
		},
		upload_complete_handler: function(file){
			window.photosUploaded++;
			if(window.photosUploaded == window.photosCountFiles){
				window.allUploadComplete = true;
				$("#galleryForm").submit();
			}
		},
		minimum_flash_version: "9.0.28"
	});');

	if(!$editimages){
		AddTextBox('Ошибка', $config['general']['admin_accd']);
		return;
	}
	$visdata = GetUserTypesFormData(4);
	$cats_data = array();
	$cats_data = $tree->GetCatsData(0);
	if(count($cats_data) == 0){
		AddTextBox($top, 'Нет категорий для добавления! Создайте категорию.');
		return;
	}
	FormRow('В категорию', $site->Select('category', $cats_data));

	FormRow('Выберите файлы', '<div style="float: left;" id="uploadFilesCount">Выбрано 0 файл(ов)</div>&nbsp;&nbsp;&nbsp;&nbsp;<div class="button" style="float: right; border: 1px #ccc solid;"><span  id="uploadbutton"></span></div>');

	$enData = GetEnData(true, 'Разрешить', 'Запретить');
	FormRow('Комментарии', $site->Select('allow_comments', $enData));
	$enData = GetEnData(true, 'Разрешить', 'Запретить');
	FormRow('Оценки', $site->Select('allow_votes', $enData));
	FormRow('Кто видит', $site->Select('view', $visdata));
	$enData = GetEnData(true, 'Да', 'Нет');
	FormRow('Показать', $site->Select('show', $enData));
	AddCenterBox("Мультизагрузка");
	AddForm('<form action="'.ADMIN_FILE.'?exe=gallery&a=upload&formid='.$formid.'" method="post" onsubmit="return SubmitFormGuard();" id="galleryForm">',
		System::admin()->Hidden('action', 'preview').System::admin()->Submit('Загрузить'));
}

function AdminGalleryUpload(){
	if(!isset($_POST['action']) || !isset($_GET['formid']) || !isset($_SESSION['uploadforms'][$_GET['formid']])){
		AdminGalleryUploadForm();
		return;
	}
	global $GalleryDir, $ThumbsDir;
	$formid = $_GET['formid'];

	// Загрузка фотографий
	if($_POST['action'] == 'upload'){
		$Error = false;
		$_SESSION['uploadforms'][$formid]['photos'][] = LoadImage(
			'up_image',
			$GalleryDir,
			$ThumbsDir,
			System::config('gallery/thumb_max_width'),
			System::config('gallery/thumb_max_height'),
			'',
			$Error
		);
		if($Error){
			exit('ERROR 2');
		}
		exit('OK');
	}

	// Предпросмотр добавляемых фотографий
	$_SESSION['uploadforms'][$formid]['category'] = $_POST['category'];
	$_SESSION['uploadforms'][$formid]['allow_comments'] = EnToInt($_POST['allow_comments']);
	$_SESSION['uploadforms'][$formid]['allow_votes'] = EnToInt($_POST['allow_votes']);
	$_SESSION['uploadforms'][$formid]['show'] = EnToInt($_POST['show']);
	$_SESSION['uploadforms'][$formid]['view'] = ViewLevelToInt($_POST['view']);

	AddCenterBox('Мультизагрузка - предпросмотр');
	$count_photos = count($_SESSION['uploadforms'][$formid]['photos']);
	$text = '<form action="'.ADMIN_FILE.'?exe=gallery&a=saveuploaded&formid='.$formid.'" method="post">';
	$submits = System::admin()->Submit('Отмена', 'name="submit_cancel" value="cancel"').System::admin()->Submit('Сохранить', 'name="submit_save" value="save"');
	$text .= '<div class="cfgboxsubmit"><div style="float: left;">Загружено '.$count_photos.' изображений.</div>'.$submits.'</div>';
	foreach($_SESSION['uploadforms'][$formid]['photos'] as $id=>$photo){
		$func = System::admin()->SpeedAjax('Удалить', ADMIN_FILE.'?exe=gallery&a=deleteuploaded&id='.$id.'&formid='.$formid, 'images/admin/delete.png', '', '', "jQuery('#photo_box_$id').fadeOut();");
		$text .= '<div class="cfgbox" id="photo_box_'.$id.'">';
		$text .= '<table cellspacing="0" cellpadding="0" border="0" style="width: 100%;">';
		$text .= '<tr><td style="vertical-align: top; width: 160px;"><a href="'.$GalleryDir.$photo.'" target="_blank"><img src="'.$ThumbsDir.$photo.'" /></a></td>';
		$text .= '<td style="vertical-align: top;">';
		$text .= '<table cellspacing="2" cellpadding="4" style="width: 100%;" class="cfgtable">
		<tr><td>Заголовок</td><td colspan="3" style="text-align: left;">'.System::admin()->Edit('title_'.$id, '', false, 'maxlength="250" style="width:400px;"').'</td></tr>
		<tr><td>Описание (HTML)</td>
		<td colspan="3" style="text-align: left;">'.System::admin()->TextArea('description_'.$id, '', 'style="width: 400px; height: 120px;"').'</td></tr>
		<tr><td>Автор</td><td style="text-align: left;">'.System::admin()->Edit('author_'.$id, '', false, 'maxlength="250" style="width:200px;"').'</td>
			<td>Email автора</td><td style="text-align: left;">'.System::admin()->Edit('email_'.$id, '', false, 'maxlength="250" style="width:200px;"').'</td></tr>
		<tr><td>Сайт автора</td><td colspan="3" style="text-align: left;">'.System::admin()->Edit('www_'.$id, '', false, 'maxlength="250" style="width:200px;"').'</td></tr>
		</table>';
		$text .= '</td>';
		$text .= '<td style="vertical-align: top; text-align: right; width: 50px;">'.$func.'</td></tr>';
		$text .= '</table>';
		$text .= '</div>';
	}
	$text .= '<div class="cfgboxsubmit">'.$submits.'</div>';
	$text .= '</form>';
	AddText($text);
}

function AdminGalleryDeleteUploaded(){
	global $GalleryDir, $ThumbsDir;
	if(!isset($_GET['id']) || !isset($_GET['formid']) || !isset($_SESSION['uploadforms'][$_GET['formid']]) || !isset($_SESSION['uploadforms'][$_GET['formid']]['photos'][$_GET['id']])){
		exit("ERROR");
	}
	$photo = $_SESSION['uploadforms'][$_GET['formid']]['photos'][$_GET['id']];
	unlink($GalleryDir.$photo);
	unlink($ThumbsDir.$photo);
	unset($_SESSION['uploadforms'][$_GET['formid']]['photos'][$_GET['id']]);
	exit("OK");
}

function AdminGallerySaveUploaded(){
	global $GalleryDir, $ThumbsDir, $tree;
	if(!isset($_GET['formid']) || !isset($_SESSION['uploadforms'][$_GET['formid']])){
		System::admin()->HighlightError('Ошибка');
		return;
	}
	$formid = $_GET['formid'];
	$form = $_SESSION['uploadforms'][$formid];
	$count_photo = count($form['photos']);

	$cat_id = SafeEnv($form['category'], 11, int);
	$allow_comments = $form['allow_comments'];
	$allow_votes = $form['allow_votes'];
	$view = $form['view'];
	$show = $form['show'];

	if(isset($_POST['submit_cancel'])){ // Отмена удаляем форму и все фотографии
		foreach($form['photos'] as $id=>$photo){
			unlink($GalleryDir.$photo);
			unlink($ThumbsDir.$photo);
		}
		unset($_SESSION['uploadforms'][$formid]);
		GO(ADMIN_FILE.'?exe=gallery&a=upload');
	}else{ // Сохраняем фотографии в базе данных
		foreach($form['photos'] as $id=>$photo){
			$photo = SafeEnv($photo, 255, str);
			$title = SafeEnv($_POST['title_'.$id], 255, str);
			$desc = SafeEnv($_POST['description_'.$id], 0, str);
			$author = SafeEnv($_POST['author_'.$id], 50, str);
			$email = SafeEnv($_POST['email_'.$id], 50, str);
			$site = SafeEnv(url($_POST['www_'.$id]), 250, str);
			System::database()->Insert('gallery', "'','$cat_id','".time()."','$title','$desc','$photo','0','$author','$email','$site','$allow_comments','0','$allow_votes','0','0','$view','$show'");
		}
		if($show){
			$tree->CalcFileCounter($cat_id, $count_photo);
		}
		unset($_SESSION['uploadforms'][$formid]);
		GO(ADMIN_FILE.'?exe=gallery');
	}
}

function AdminGallerySaveImage(){
	global $db, $config, $tree, $GalleryDir, $ThumbsDir;
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
	// Изображение
	// Загружаем изображение
	$Error = false;
	$file = LoadImage('up_image', $GalleryDir, $ThumbsDir, $config['gallery']['thumb_max_width'], $config['gallery']['thumb_max_height'], $_POST['image'], $Error);

	if($Error){
		AddTextBox('Ошибка', '<center>Неправильный формат файла. Можно загружать только изображения формата GIF, JPEG или PNG.</center>');
		return;
	}
	if(!isset($_GET['id'])){
		$db->Insert('gallery', "'','$cat_id','".time()."','$title','$desc','$file','0','$author','$email','$site','$allow_comments','0','$allow_votes','0','0','$view','$show'");
		if($show){
			$tree->CalcFileCounter($cat_id, true);
		}
	}else{
		$set = "`cat_id`='$cat_id',`title`='$title',`description`='$desc',`file`='$file',`author`='$author',`email`='$email',`site`='',`allow_comments`='$allow_comments',`allow_votes`='$allow_votes',`view`='$view',`show`='$show'";
		$id = SafeEnv($_GET['id'], 11, int);
		$r = $db->Select('gallery', "`id`='$id'");
		if($r[0]['cat_id'] != $cat_id && $r[0]['show'] == '1'){ //Если переместили в другой раздел
			$tree->CalcFileCounter(SafeDB($r[0]['cat_id'], 11, int), false);
			$tree->CalcFileCounter($cat_id, true);
		}
		if($r[0]['show'] != $show){ // Выключили / Включили
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
		AddTextBox('Ошибка', $config['general']['admin_accd']);
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
			$text = '<table cellspacing="0" cellpadding="5" border="0" align="center"><tr><td align="center">'.($config['gallery']['show_thumbs'] == 1 ? '<img width="400" src="'.$filename.'" border="0" /></tr></td><tr><td align="center">' : '').'Удалить изображение "'.SafeDB($img['title'], 255, str).'" из галереи?<br />'.'<a href="'.$config['admin_file'].'?exe=gallery&a=delete&id='.$id.'&ok=1">Да</a> &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">Нет</a><br /><br />'.'</td></tr></table>';
		}else{
			$text = '<center>Изображение, которое Вы пытаетесь удалить, не найдено в галерее.<br /><a href="javascript:history.go(-1)">Назад в галерею</a></center>';
		}
		AddTextBox('Внимание!', $text);
	}
}

function AdminGalleryChangeStatus(){
	global $config, $db, $tree, $user, $editimages;
	if(!$editimages){
		AddTextBox('Ошибка', $config['general']['admin_accd']);
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
		AddTextBox('Ошибка', $config['general']['admin_accd']);
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
