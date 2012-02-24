<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

System::admin()->AddSubTitle('Архив файлов');

if(!System::user()->CheckAccess2('downloads', 'downloads')) System::admin()->AccessDenied();

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

if(isset($_GET['a'])){
	$action = SafeEnv($_GET['a'], 255, str);
}else{
	$action = 'main';
}

TAddToolLink('Файлы', 'main', 'downloads');
if(System::user()->CheckAccess2('downloads', 'edit_files'))
	TAddToolLink('Добавить файл', 'editor', 'downloads&a=editor');
if(System::user()->CheckAccess2('downloads', 'edit_cats')){
	TAddToolLink('Категории', 'cats', 'downloads&a=cats');
	TAddToolLink('Добавить категорию', 'cateditor', 'downloads&a=cateditor');
}
if(System::user()->CheckAccess2('downloads', 'config')){
	TAddToolLink('Настройки модуля', 'config', 'downloads&a=config');
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
		if(!System::user()->CheckAccess2('downloads', 'edit_cats')) System::admin()->AccessDenied();
		global $tree;
		$result = $tree->ShowCats();
		if($result == false){
			$result = 'Нет категорий для отображения.';
		}
		AddTextBox('Категории', $result);
		break;
	case 'cateditor':
		if(!System::user()->CheckAccess2('downloads', 'edit_cats')) System::admin()->AccessDenied();
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
		if(!System::user()->CheckAccess2('downloads', 'edit_cats')) System::admin()->AccessDenied();
		global $tree;
		$tree->EditorSave((isset($_GET['id']) ? SafeEnv($_GET['id'], 11, int) : null));
		GO(ADMIN_FILE.'?exe=downloads&a=cats');
		break;
	case 'delcat':
		if(!System::user()->CheckAccess2('downloads', 'edit_cats')) System::admin()->AccessDenied();
		global $tree;
		if($tree->DeleteCat(SafeEnv($_GET['id'], 11, int))){
			GO(ADMIN_FILE.'?exe=downloads&a=cats');
		}
		break;
	case 'changestatus':
		AdminDownloadsChangeStatus();
		break;

	case 'config':
		if(!System::user()->CheckAccess2('downloads', 'config')) System::admin()->AccessDenied();
		System::admin()->AddCenterBox('Конфигурация модуля "Архив файлов"');
		if(CheckGet('saveok')){
			System::admin()->Highlight('Настройки сохранены.');
		}
		System::admin()->ConfigGroups('downloads');
		System::admin()->AddConfigsForm(ADMIN_FILE.'?exe=downloads&a=configsave');
		break;
	case 'configsave':
		if(!System::user()->CheckAccess2('downloads', 'config')) System::admin()->AccessDenied();
		System::admin()->SaveConfigs('downloads');
		GO(ADMIN_FILE.'?exe=downloads&a=config&saveok');
		break;

	case 'resetrating':
		AdminDownloadsResetRating();
		break;
	case 'resetcounter':
		AdminDownloadsResetCounter();
		break;
}

function AdminDownloadsMain(){
	global $config, $tree;
	$editfiles = System::user()->CheckAccess2('downloads', 'edit_files');
	$back = SaveRefererUrl();
	if(isset($_GET['cat']) && $_GET['cat'] > -1){
		$cat = SafeEnv($_GET['cat'], 11, int);
		$where = "`category`='$cat'";
	}else{
		$cat = -1;
		$where = "";
	}
	$data = array();
	$data = $tree->GetCatsData($cat, true);
	System::site()->DataAdd($data, -1, 'Все файлы', $cat == -1);
	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 10, int);
	}else{
		$page = 1;
	}
	AddCenterBox('Файлы');

	System::admin()->AddJS('
	DownloadsSelectCat = function(){
		Admin.LoadPage("'.ADMIN_FILE.'?exe=downloads&cat="+$("#download-cat").val());
	}
	');
	$text = '<div style="text-align: center; margin-bottom: 10px;">Категория: '.System::site()->Select('cat', $data, false, 'id="download-cat" onchange="DownloadsSelectCat();"').'</div>';
	AddText($text);

	System::database()->Select('downloads', $where);
	SortArray(System::database()->QueryResult, 'public', true);

	if(count(System::database()->QueryResult) > $config['downloads']['filesonpage']){
		$navigator = new Navigation($page);
		$navigator->GenNavigationMenu(System::database()->QueryResult, $config['downloads']['filesonpage'], ADMIN_FILE.'?exe=downloads'.($cat > 0 ? '&cat='.$cat : ''));
		$nav = true;
	}else{
		$nav = false;
	}

	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>Заголовок</th><th>Скачиваний</th><th>Оценки</th><th>Видят</th><th>Статус</th><th>Функции</th></tr>';
	while($row = System::database()->FetchRow()){
		$id = SafeDB($row['id'], 11, int);
		$title = SafeDB($row['title'], 255, str);
		$hits = SafeDB($row['hits'], 11, int);
		$rating = '<img src="'.GetRatingImage(SafeDB($row['votes_amount'], 11, int), SafeDB($row['votes'], 11, int)).'" border="0" />';
		$st = ($row['active'] == '1' ? 'Вкл.' : 'Выкл.');
		$func = '-';
		if($editfiles){
			$title = '<b>'.System::admin()->Link($title, ADMIN_FILE.'?exe=downloads&a=editor&id='.$id.'&back='.$back).'</b>';
			$hits .= '&nbsp;'.System::admin()->SpeedConfirm('Обнулить счётчик скачиваний', ADMIN_FILE.'?exe=downloads&a=resetcounter&id='.$id.'&back='.$back, 'images/admin/arrow_in.png', 'Сбросить счётчик скачиваний?');
			$rating .= '&nbsp;'.System::admin()->SpeedConfirm('Обнулить счётчик оценок ('.SafeDB($row['votes_amount'], 11, int).' голосов)', ADMIN_FILE.'?exe=downloads&a=resetrating&id='.$id.'&back='.$back, 'images/admin/arrow_in.png', 'Сбросить оценки?');
			$st = System::admin()->SpeedStatus('Вкл.', 'Выкл.', ADMIN_FILE.'?exe=downloads&a=changestatus&id='.$id, $row['active'] == '1');
			$func = System::admin()->SpeedButton('Редактировать', ADMIN_FILE.'?exe=downloads&a=editor&id='.$id.'&back='.$back, 'images/admin/edit.png');
			$func .= System::admin()->SpeedConfirm('Удалить', ADMIN_FILE.'?exe=downloads&a=deletefile&id='.$id.'&ok=0&back='.$back, 'images/admin/delete.png', 'Удалить файл?');
		}
		$text .= '<tr><td>'.$title.'</td>
		<td>'.$hits.'</td>
		<td>'.($row['allow_votes'] == '1' ? $rating : 'Запрещены').'</td>
		<td>'.ViewLevelToStr(SafeDB($row['view'], 1, int)).'</td>
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

function AdminDownloadsFileEditor( $action ){
	global $config, $site, $tree;
	if(!System::user()->CheckAccess2('downloads', 'edit_files')) System::admin()->AccessDenied();
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
	$allow_comments = true;
	$allow_votes = true;
	$view = array(1=>false, 2=>false, 3=>false, 4=>false);
	$active = array(false, false);
	$active = true;
	if(!isset($_GET['id'])){
		$view[4] = true;
		$action = 'addfilesave';
		$top = 'Добавить файл';
		$cap = 'Добавить';
	}else{
		$id = SafeEnv($_GET['id'], 11, int);
		System::database()->Select('downloads', "`id`='$id'");
		$file = System::database()->FetchRow();
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
		$allow_comments = SafeDB($file['allow_comments'], 1, int);
		$allow_votes = SafeDB($file['allow_votes'], 1, int);
		$view[SafeDB($file['view'], 1, int)] = true;
		$active = SafeDB($file['active'], 1, int);
		$action = 'editfilesave&id='.$id;
		$top = 'Редактирование файла';
		$cap = 'Сохранить изменения';
	}
	unset($file);
	$visdata = GetUserTypesFormData($view);
	$cats_data = array();
	$cats_data = $tree->GetCatsData($category);
	if(count($cats_data) == 0){
		AddTextBox($top, 'Нет категорий для добавления! Создайте категорию.');
		return;
	}

	$filesize_data = array();
	$site->DataAdd($filesize_data, 'b', 'Байт', $size_type == 'b');
	$site->DataAdd($filesize_data, 'k', 'Килобайт', $size_type == 'k');
	$site->DataAdd($filesize_data, 'm', 'Мегабайт', $size_type == 'm');
	$site->DataAdd($filesize_data, 'g', 'Гигабайт', $size_type == 'g');

	FormRow('В категорию', $site->Select('category', $cats_data));
	FormRow('Название', $site->Edit('title', $title, false, 'style="width:400px;"'));

	FormRow('Путь к файлу', $site->Edit('url', $url, false, 'style="width:400px;"'));
	//FormRow('Путь к файлу', $site->FileManager( 'url', $url, 400));

	$max_file_size = ini_get('upload_max_filesize');
	FormRow('Загрузить файл<br />(<small>Максимальный размер файла: '.$max_file_size.'</small>)',
		$site->FFile('upload_file').'<br /><div style="width: 400px; word-wrap:break-word;">Разрешенные форматы:<br />'.$config['downloads']['file_exts'].'</div>');
	FormRow('Размер файла', $site->Edit('size', $file_size, false, 'style="width:200px;"').' '.$site->Select('filesize_type', $filesize_data));
	AdminImageControl('Изображение', 'Загрузить изображение', $image, $config['downloads']['images_dir']);
	FormTextRow('Краткое описание', $site->HtmlEditor('shortdesc', $shortdesc, 600, 200));
	FormTextRow('Полное описание', $site->HtmlEditor('description', $description, 600, 400));
	FormRow('Версия файла', $site->Edit('version', $file_ver, false, 'style="width:400px;"'));
	FormRow('Автор', $site->Edit('author', $author, false, 'style="width:400px;"'));
	FormRow('E-mail автора', $site->Edit('author_email', $author_email, false, 'style="width:400px;"'));
	FormRow('Сайт автора', $site->Edit('author_site', $author_site, false, 'style="width:400px;"'));
	FormRow('Комментарии', $site->Select('allow_comments', GetEnData($allow_comments, 'Разрешить', 'Запретить')));
	FormRow('Оценки', $site->Select('allow_votes', GetEnData($allow_votes, 'Разрешить', 'Запретить')));
	FormRow('Кто видит', $site->Select('view', $visdata));
	FormRow('Активен', $site->Select('active', GetEnData($active, 'Да', 'Нет')));

	AddCenterBox($top);
	if(!isset($_REQUEST['back'])){
		$_REQUEST['back'] = SaveRefererUrl(ADMIN_FILE.'?exe=downloads');
	}
	AddForm('<form action="'.ADMIN_FILE.'?exe=downloads&a='.$action.'&back='.SafeDB($_REQUEST['back'], 255, str).'" method="post" enctype="multipart/form-data" name="edit_form">', $site->Button('Отмена', 'onclick="history.go(-1)"').$site->Submit($cap));
}

function AdminDownloadsSaveFile( $action ){
	global $config, $tree;

	if($_POST == array()){
		AddTextBox('Ошибка', '<b>Внимание! Превышен максимальный размер POST данных. Изменения не сохранены.</b>');
		return;
	}
	$Error = '';

	if(!System::user()->CheckAccess2('downloads', 'edit_files')) System::admin()->AccessDenied();
	$category = SafeEnv($_POST['category'], 11, int);
	if(in_array($category, $tree->GetAllChildId(0)) === false || $category == 0){
		GO(ADMIN_FILE.'?exe=downloads');
	}
	$title = SafeEnv($_POST['title'], 250, str);

	// Обрабатываем upload_file если загрузился файл
	$exts = explode(',', $config['downloads']['file_exts']);
	$exts2 = array();
	foreach($exts as $ext){
		$exts2[trim($ext)] = true;
	}
	$UploadErrors = array(
		0 => '',
		1 => 'Размер файла превышен', //'Загруженный файл превышает разрешённый размер (upload_max_filesize) в php.ini ('.ini_get('upload_max_filesize').')',
		2 => 'Размер файла превышен', //'Загруженный файл превышает директиву MAX_FILE_SIZE, которая была определена в форме HTML',
		3 => 'Файл загружен только частично',
		4 => 'Файл не был загружен.',
		6 => 'Не найдена папка для временных файлов на сервере',
		7 => 'Ошибка во время записи на диск',
		8 => 'Загрузка файла была прервана расширением PHP',
		9 => 'Ошибка во время записи на диск'
	);
	if($_FILES['upload_file']['error'] == UPLOAD_ERR_OK){
		if(isset($exts2[strtolower(GetFileExt($_FILES['upload_file']['name']))])){
			// Загружаем файл
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
		$file_size = SafeEnv($_POST['size'], 11, real); // Дробное число
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
	// Загружаем изображение
	$ImageUploadError = false;
	$image = LoadImage('up_image', $config['downloads']['images_dir'], $config['downloads']['images_dir'].'thumbs/', $config['downloads']['thumb_max_width'], $config['downloads']['thumb_max_height'], $_POST['image'], $ImageUploadError);
	$author = SafeEnv($_POST['author'], 50, str);
	$author_site = SafeEnv(Url($_POST['author_site']), 250, str);
	$author_email = SafeEnv($_POST['author_email'], 50, str);
	$file_ver = SafeEnv($_POST['version'], 250, str);
	$allow_comments = EnToInt($_POST['allow_comments']);
	$allow_votes = EnToInt($_POST['allow_votes']);
	$view = ViewLevelToInt($_POST['view']);
	$active = EnToInt($_POST['active']);

	if('editfilesave' == $action){
		//Здесь генерируем Set запрос
		$set = "title='$title',category='$category',size='$file_size',size_type='$size_type',url='$url',shortdesc='$shortdesc',description='$description',image='$image',author='$author',author_site='$author_site',author_email='$author_email',file_version='$file_ver',allow_comments='$allow_comments',allow_votes='$allow_votes',view='$view',active='$active'";
		$id = SafeEnv($_GET['id'], 11, int);
		$r = System::database()->Select('downloads', "`id`='$id'");
		if($r[0]['category'] != $category && $r[0]['active'] == '1'){
			$tree->CalcFileCounter($r[0]['category'], false);
			$tree->CalcFileCounter($category, true);
		}
		if($r[0]['active'] != $active){ // Выключили / Включили
			if($active == 0){
				$tree->CalcFileCounter($category, false);
			}else{
				$tree->CalcFileCounter($category, true);
			}
		}
		System::database()->Update('downloads', $set, "`id`='$id'");
	}elseif('addfilesave' == $action){
		$values = Values('', $category, time(), $file_size, $size_type, $title, $url, $shortdesc, $description, $image, $author, $author_site, $author_email, $file_ver, $allow_comments, 0, $allow_votes, 0, 0, 0, $view, $active);
		System::database()->Insert('downloads', $values);
		if($active){
			$tree->CalcFileCounter($category, true);
		}
	}
	if($ImageUploadError){
		AddTextBox('Ошибка', '<center>Неправильный формат файла. Можно загружать только изображения формата GIF, JPEG или PNG. Остальные изменения сохранены.</center><br /><a href="'.GetRefererUrl($_REQUEST['back']).'" class="button">Далее</a>');
		return;
	}
	if($Error != ''){
		AddTextBox('Ошибка', '<center>Не удалось загрузить файл, изменения сохранены. Ошибка: '.$Error.'.</center><br /><a href="'.GetRefererUrl($_REQUEST['back']).'" class="button">Далее</a>');
		return;
	}
	GoRefererUrl($_REQUEST['back']);
}

function AdminDownloadsDeleteFile(){
	global $tree;
	if(!System::user()->CheckAccess2('downloads', 'edit_files')) System::admin()->AccessDenied();
	if(IsAjax() || (isset($_GET['ok']) && SafeEnv($_GET['ok'], 1, int) == '1')){
		$id = SafeEnv($_GET['id'], 11, int);
		$r = System::database()->Select('downloads', "`id`='$id'");
		$tree->CalcFileCounter(SafeDB($r[0]['category'], 11, int), false);
		if(is_file(RealPath2($r[0]['url']))){
			unlink(RealPath2($r[0]['url']));
		}
		System::database()->Delete('downloads', "`id`='$id'");
		System::database()->Delete('downloads_comments', "`object_id`='$id'");
		GoRefererUrl($_REQUEST['back']);
	}else{
		System::admin()->AddCenterBox('Удаление файла');
		System::database()->Select('downloads', "`id`='".SafeEnv($_REQUEST['id'], 11, int)."'");
		$file = System::database()->FetchRow();
		$id = SafeDB($_REQUEST['id'], 11, int);
		$back = SafeDB($_REQUEST['back'], 255, str);
		System::admin()->HighlightConfirmNoAjax('Удалить файл "'.SafeDB($file['title'], 255, str).'"?', ADMIN_FILE.'?exe=downloads&a=deletefile&id='.$id.'&ok=1&back='.$back);
	}
}

function AdminDownloadsChangeStatus(){
	global $tree;
	if(!System::user()->CheckAccess2('downloads', 'edit_files')){
		exit("ACCESS DENIED");
	}
	if(!isset($_GET['id'])){
		exit("ERROR");
	}
	System::database()->Select('downloads', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	if(System::database()->NumRows() > 0){
		$r = System::database()->FetchRow();
		if($r['active'] == 1){
			$en = '0';
			$tree->CalcFileCounter(SafeDB($r['category'], 11, int), false);
		}else{
			$en = '1';
			$tree->CalcFileCounter(SafeDB($r['category'], 11, int), true);
		}
		System::database()->Update('downloads', "`active`='$en'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	}
	exit("OK");
}

function AdminDownloadsResetRating(){
	if(!System::user()->CheckAccess2('downloads', 'edit_files')) System::admin()->AccessDenied();
	System::database()->Update('downloads', "votes_amount='0',votes='0'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	GoRefererUrl($_REQUEST['back']);
}

function AdminDownloadsResetCounter(){
	if(!System::user()->CheckAccess2('downloads', 'edit_files')) System::admin()->AccessDenied();
	System::database()->Update('downloads', "hits='0'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	GoRefererUrl($_REQUEST['back']);
}
