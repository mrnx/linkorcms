<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$site->SetTitle('Архив файлов');

include_once ($config['inc_dir'].'tree_b.class.php');
$tree = new IndexTree('downloads_cats');
$tree->moduleName = 'downloads';
$tree->id_par_name = 'cat';
$tree->NumItemsCaption = '<center>Всего файлов в нашем архиве: ';
$tree->TopCatName = 'Файлы';

if(isset($_GET['op'])){
	$op = $_GET['op'];
}else{
	$op = 'main';
}

switch($op){
	case 'main': IndexDownloadsMain();
	break;
	case 'full': IndexDownloadsFull();
	break;
	case 'download': IndexDownloadsFile();
	break;
	case 'addvote': IndexDownloadsAddVote();
	break;
	// Комментарии
	case 'addpost': IndexDownloadsAddPost();
		break;
	case 'editpost': IndexDownloadsEditPost();
		break;
	case 'savepost': IndexDownloadsEditPostSave();
		break;
	case 'deletepost': IndexDownloadsDeletePost();
		break;
	// //
	default:
		HackOff();
}

function filetypecheck( $filename )
{
	$ext = substr(GetFileExt($filename), 1);
	if($ext == 'rar'){ $dtype = 'Архив RAR'; }
	if($ext == 'zip'){ $dtype = 'Архив ZIP'; }
	if($ext == '7z') { $dtype = 'Архив 7zip'; }
	if($ext == 'bz2'){ $dtype = 'BZ2'; }
	if($ext == 'cab'){ $dtype = 'CAB'; }
	if($ext == 'ace'){ $dtype = 'WinACE'; }
	if($ext == 'arj'){ $dtype = 'ARJ'; }
	if($ext == 'jar'){ $dtype = 'JAR'; }

	if($ext == 'gzip'){ $dtype = 'GZIP'; }
	if($ext == 'tar'){ $dtype = 'TAR'; }
	if($ext == 'tgz'){ $dtype = 'TGZ'; }
	if($ext == 'gz'){ $dtype = 'GZ'; }

	if($ext == 'gif'){ $dtype = 'GIF'; }
	if(preg_match('/jpeg|jpe|jpg/i', $ext)){ $dtype = 'JPEG/JPE/JPG'; }
	if($ext == 'png'){ $dtype = 'PNG'; }
	if($ext == 'bmp'){ $dtype = 'BMP'; }

	if($ext == 'txt'){ $dtype = 'TXT'; }
	if($ext == 'sql'){ $dtype = 'SQL'; }
	if($ext == 'exe'){ $dtype = 'EXE'; }
	if($ext == 'swf'){ $dtype = 'SWF'; }
	if($ext == 'fla'){ $dtype = 'FLA'; }
	if(preg_match('/flv|f4v|f4p|f4a|f4b/i', $ext)){ $dtype = 'Flash Video (FLV)'; }

	if($ext == 'wav'){ $dtype = 'WAV'; }
	if($ext == 'mp2'){ $dtype = 'MP2'; }
	if($ext == 'mp3'){ $dtype = 'MP3'; }
	if($ext == 'mp4'){ $dtype = 'MP4'; }
	if(preg_match('/ogv|oga|ogx|ogg/i', $ext)){ $dtype = 'Ogg'; }
	if($ext == 'mid'){ $dtype = 'MID'; }
	if($ext == 'midi'){ $dtype = 'MIDI'; }
	if($ext == 'mmf'){ $dtype = 'MMF'; }

	if($ext == 'mpeg'){ $dtype = 'MPEG'; }
	if($ext == 'mpe'){ $dtype = 'MPE'; }
	if($ext == 'mpg'){ $dtype = 'MPG'; }
	if($ext == 'mpa'){ $dtype = 'MPA'; }
	if($ext == 'avi'){ $dtype = 'AVI'; }
	if($ext == 'mpga'){ $dtype = 'MPGA'; }

	if(preg_match('/pdf|pds/i', $ext)){ $dtype = 'Документ Adobe PDF'; }
	if(preg_match('/xls|xl|xla|xlb|xlc|xld|xlk|xll|xlm|xlt|xlv|xlw/i', $ext)){ $dtype = 'Документ MS-Excel'; }
	if(preg_match('/doc|dot|wiz|wzs|docx/i', $ext)){ $dtype = 'Документ MS-Word'; }
	if($ext == 'odt'){ $dtype = 'Текстовый документ OpenDocument'; }
	if($ext == 'odg'){ $dtype = 'Графический документ OpenDocument'; }
	if($ext == 'odp'){ $dtype = 'Документ презентации OpenDocument'; }
	if($ext == 'ods'){ $dtype = 'Электронная таблица OpenDocument'; }
	if($ext == 'odc'){ $dtype = 'Документ диаграммы OpenDocument'; }
	if($ext == 'odi'){ $dtype = 'Документ изображения OpenDocument'; }
	if($ext == 'odf'){ $dtype = 'Документ формулы OpenDocument'; }
	if($ext == 'odm'){ $dtype = 'Составной текстовый документ OpenDocument'; }
	if(preg_match('/pot|ppa|pps|ppt|pwz/i', $ext)){ $dtype = 'Документ MS-Powerpoint'; }
	if($ext == 'rtf'){ $dtype = 'RTF'; }
	if(empty($dtype)) $dtype = '';

	return $dtype;
}

function IndexDownloadsGetNumItems()
{
	global $db;
	$ex_where = GetWhereByAccess('view');
	$db->Select('downloads', "`active`='1'".($ex_where != '' ? ' and '.$ex_where : ''));
	return $db->NumRows().'.</center>';
}

function IndexDownloadsFunc($id)
{
	global $config;
	return
	'&nbsp'
	.'<a href="'.ADMIN_FILE.'?exe=downloads&a=editor&id='.$id.'" class="admin_edit_link"><img src="images/admin/edit.png" title="Редактировать"></a>'
	.'<a href="'.ADMIN_FILE.'?exe=downloads&a=deletefile&id='.$id.'&ok=0" class="admin_edit_link"><img src="images/admin/delete.png" title="Удалить"></a>';
}

function AddDownload(&$down)
{
	global $site, $config, $user;

	$id = SafeDB($down['id'], 11, int);
	$cat_id = SafeDB($down['category'], 11, int);
	$func = IndexDownloadsFunc($id);

	$vars = array();
	$vars['file_title'] = SafeDB($down['title'], 255, str).($user->isAdmin() ? $func : '');
	$vars['url'] = Ufu("index.php?name=downloads&op=full&file=$id&cat=$cat_id", 'downloads/{cat}/{file}/');

	if($down['image'] != ''){
		$vars['image'] = RealPath2($config['downloads']['images_dir'].SafeDB($down['image'], 255, str));
		$vars['thumb_image'] = RealPath2($config['downloads']['images_dir'].'thumbs/'.SafeDB($down['image'], 255, str));
	}else{
		$vars['image'] = false;
	}
	$vars['description'] = SafeDB($down['shortdesc'], 0, str, false, false);

	if($down['allow_comments'] == '1'){
		$vars['comments'] = SafeDB($down['comments_counter'], 11, int);
	}else{
		$vars['comments'] = ' - ';
	}
	
	$vars['author'] = SafeDB($down['author'], 200, str);
	$vars['homepage'] = SafeDB($down['author_site'], 250, str);
	$vars['homepage_url'] = UrlRender(SafeDB($down['author_site'], 250, str));
	$vars['mail'] = SafeDB($down['author_email'], 50, str);

	$vars['date'] = TimeRender($down['public']);
	$vars['hits'] = SafeDB($down['hits'], 11, int);
	$vars['version'] = SafeDB($down['file_version'], 250, str);

	$vars['size'] = FormatFileSize(SafeDB($down['size'], 11, real), SafeDB($down['size_type'], 1, str));
	$vars['filetype'] = filetypecheck(SafeDB($down['url'], 250, str));
	$vars['allow_votes'] = SafeDB($down['allow_votes'], 1, bool);
	$vars['num_votes'] = SafeDB($down['votes_amount'], 11, int);

	$vars['rating'] = GetRatingImage(SafeDB($down['votes_amount'], 11, int), SafeDB($down['votes'], 11, int));
	$site->AddSubBlock('download', true, $vars);
}

function AddDetailDownload(&$down)
{
	global $site, $config, $tree, $user;

	$id = SafeDB($down['id'], 11, int);
	$cat_id = SafeDB($down['category'], 11, int);
	$func = IndexDownloadsFunc($id);

	$vars = array();
	$vars['category_url'] = Ufu("index.php?name=downloads&cat=$cat_id", 'downloads/{cat}/');
	$vars['category_title'] = $tree->IdCats[$down['category']]['title'];
	$vars['category'] = '<a href="'.$vars['category_url'].'">'.$vars['category_title'].'</a>';

	$vars['file_link'] = "index.php?name=downloads&op=download&file=$id"; // Если сделать ЧПУ ссылку, то появляется проблема с относительным адресом файла
	if($user->AccessIsResolved($down['view'])){
		$vars['access'] = true;
		$url = '<a href="'.$vars['file_link'].'" target="_blank">Скачать файл</a>';
	}else{
		$vars['access'] = false;
		$url = 'Файл только для зарегистрированных пользователей.';
	}
	$vars['not_access'] = !$vars['access'];
	$vars['url'] = $url;

	$vars['file_title'] = SafeDB($down['title'], 255, str).($user->isAdmin() ? $func : '');
	$vars['description'] = SafeDB($down['description'], 0, str, false, false);

	$vars['author'] = SafeDB($down['author'], 200, str);
	$vars['homepage'] = SafeDB($down['author_site'], 250, str);
	$vars['homepage_url'] = UrlRender(SafeDB($down['author_site'], 250, str));
	$vars['mail'] = SafeDB($down['author_email'], 50, str);

	$vars['date'] = TimeRender($down['public']);
	$vars['hits'] = SafeDB($down['hits'], 11, int);
	$vars['version'] = SafeDB($down['file_version'], 250, str);

	$vars['size'] = FormatFileSize(SafeDB($down['size'], 11, real), SafeDB($down['size_type'], 1, str));
	$vars['filetype'] = filetypecheck(SafeDB($down['url'], 250, str));

	$vars['addvote_url'] = "index.php?name=downloads&op=addvote&file=$id";
	$site->DataAdd($vdata, '0', 'Ваша оценка');
	$site->DataAdd($vdata, '1', 'Очень плохо');
	$site->DataAdd($vdata, '2', 'Плохо');
	$site->DataAdd($vdata, '3', 'Средне');
	$site->DataAdd($vdata, '4', 'Хорошо');
	$site->DataAdd($vdata, '5', 'Отлично');

	$vars['votes'] = $site->Select('vote', $vdata);
	$vars['addvotesubm'] = $site->Submit('Оценить файл');
	$vars['allow_votes'] = SafeDB($down['allow_votes'], 1, bool);
	

	if($down['image'] != ''){
		$vars['image'] = RealPath2($config['downloads']['images_dir'].SafeDB($down['image'], 255, str));
		$vars['thumb_image'] = RealPath2($config['downloads']['images_dir'].'thumbs/'.SafeDB($down['image'], 255, str));
	}else{
		$vars['image'] = false;
	}

	//Выводим rating
	$vars['rating_num_votes'] = SafeDB($down['votes_amount'], 11, int);
	$vars['rating_image'] = GetRatingImage(SafeDB($down['votes_amount'], 11, int), SafeDB($down['votes'], 11, int));
	$rating = $vars['rating_image'];
	if($rating == '' && SafeDB($down['allow_votes'], 1, bool)){ // allow_rating
		$rating = 'Нет оценки';
	}elseif(SafeDB($down['allow_votes'], 1, bool)){
		$rating = '<img src="'.$rating.'" /> (Оценок: '.SafeDB($down['votes_amount'], 11, int).')';
	}else{
		$rating = ' - ';
	}
	$vars['rating'] = $rating;
	//

	if(!SafeDB($down['allow_comments'], 1, bool)){ // allow coments
		$vars['comments'] = ' - ';
	}else{
		$vars['comments'] = SafeDB($down['comments_counter'], 11, int);
	}

	$site->AddBlock('download',true,false,'dl');
	$site->Blocks['download']['vars'] = $vars;
}

function IndexDownloadsMain()
{
	global $tree, $site, $db, $config;

	if(isset($_GET['cat'])){
		$cat = SafeEnv($_GET['cat'],11,int);
	}else{
		$cat = 0;
	}

	if($cat != 0){
		$site->SetTitle('Файлы в категории '.SafeDB($tree->IdCats[$cat]['title'], 255, str));
	}

	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'],10,int);
	}else{
		$page = 1;
	}

	if($config['downloads']['show_catnav']=='1'){
		$tree->Catalog($cat,'IndexDownloadsGetNumItems');
	}
	if($cat != 0 || $config['downloads']['show_last'] == '1'){
		$where = ($cat != 0 ? "`category`='$cat' and " : '')."`active`='1'";
		$ex_where = GetWhereByAccess('view');
		if($ex_where != ''){
			$where .= ' and ('.$ex_where.')';
		}
		$downs = $db->Select('downloads', $where);
		SortArray($downs, 'public', true);

		// Постраничная навигация
		$num = $config['downloads']['filesonpage'];
		$navigation = new Navigation($page);
		$nav_link = Ufu('index.php?name=downloads'.($cat != 0 ? '&cat='.$cat : ''), 'downloads/'.($cat != 0 ? '{cat}/' : '').'page{page}/', true);
		$navigation->FrendlyUrl = $config['general']['ufu'];
		$navigation->GenNavigationMenu($downs, $num, $nav_link);

		if($db->NumRows() > 0){
			$site->AddTemplatedBox('','module/download.html');
			$site->AddBlock('download', true, true, 'dl');
			foreach($downs as $down){
				AddDownload($down);
			}
		}elseif(!isset($tree->Cats[$cat]) && count($tree->Cats) > 0){
			$site->AddTextBox('','<center>В этой категории пока нет файлов.</center>');
		}
	}
}

function IndexDownloadsFull()
{
	global $db, $config, $site, $tree, $user;
	if(isset($_GET['file'])){
		$id = SafeEnv($_GET['file'],11,int);
	}else{
		GO(GetSiteUrl().Ufu('index.php?name=downloads', '{name}/'));
	}
	$where = "`id`='$id' and `active`='1'";
	$ex_where = GetWhereByAccess('view');
	if($ex_where != ''){
		$where .= ' and ('.$ex_where.')';
	}
	$db->Select('downloads', $where);
	if($db->NumRows() == 0){
		GO(GetSiteUrl().Ufu('index.php?name=downloads', '{name}/'));
	}
	$file = $db->FetchRow();
	$cat = SafeDB($file['category'], 11, int);
	$tree->ShowPath($cat, true, SafeDB($file['title'],255,str));
	$site->SetTitle('Скачать '.SafeDB($file['title'], 255, str));

	$site->AddTemplatedBox('','module/download_full.html');
	AddDetailDownload($file);

	// Выводим комментарии
	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 11, int);
	}else{
		$page = 0;
	}
	include_once($config['inc_dir'].'posts.class.php');
	$posts = new Posts('downloads_comments', $file['allow_comments'] == '1');
	$posts->EditPageUrl = "index.php?name=downloads&op=editpost&file=$id";
	$posts->DeletePageUrl = "index.php?name=downloads&op=deletepost&file=$id";
	$posts->PostFormAction = "index.php?name=downloads&op=addpost&file=$id&page=$page&cat=$cat";

	$posts->NavigationUrl = Ufu("index.php?name=downloads&op=full&file=$id&cat=$cat", 'downloads/{cat}/{file}/page{page}/', true);

	$posts->RenderPosts($id, 'download_comments', 'comments_navigation', false, $page);
	$posts->RenderForm(false, 'download_comments_form');
}

function IndexDownloadsFile()
{
	global $config, $db, $site, $user;
	$file = SafeEnv($_GET['file'],11,int);
	$where = "`id`='$file' and `active`='1'";
	$ex_where = GetWhereByAccess('view');
	if($ex_where != ''){
		$where .= ' and ('.$ex_where.')';
	}
	$db->Select('downloads', $where);
	if($db->NumRows() > 0){
		$sfile = $db->FetchRow();
		$counter = SafeDB($sfile['hits'],11,int)+1;
		$db->Update('downloads',"hits='$counter'","`id`='$file'");
		$user->ChargePoints($config['points']['download_download']);
		GO(SafeDB($sfile['url'],250,str));
	}else{
		$site->AddTextBox('Ошибка','<center>Файл, который вы пытаетесь скачать, не найден, возможно, он был удален из архива.</center>');
	}
}

function IndexDownloadsAddVote()
{
	global $db, $config, $site, $user;
	$ip = getip();
	$time = time() - 86400; //1 день
	$file = SafeEnv($_GET['file'], 11, int);
	$vote = SafeEnv($_POST['vote'], 1, int);
	$db->Delete('downloads_rating', "`time`<'$time'");
	$site->OtherMeta .= '<meta http-equiv="REFRESH" content="3; URL='.HistoryGetUrl(1).'">';
	$where = "`id`='$file' and `active`='1'";
	$ex_where = GetWhereByAccess('view');
	if($ex_where != ''){
		$where .= ' and ('.$ex_where.')';
	}
	$db->Select('downloads', $where);
	if($db->NumRows() > 0){
		$dfile = $db->FetchRow();
		if($dfile['allow_votes']=='1'){ // оценки разрешены
			$db->Select('downloads_rating',"`ip`='$ip' and `downid`='$file'");
			if($db->NumRows()>0){
				$site->AddTextBox('','<center>Вы уже голосовали за этот файл.<br /><br /><a href="javascript:history.go(-1)">Назад</a></center>');
			}else{
				if($vote==0){
					$site->AddTextBox('','<center>Вы не выбрали оценку.<br /><br /><a href="javascript:history.go(-1)">Назад</a></center>');
				}else{
					$user->ChargePoints($config['points']['download_rating']);
					$time = time();
					$db->Insert('downloads_rating',"'','$file','$ip','$time'");
					$vote = SafeDB($dfile['votes'],11,int)+$vote;
					$numvotes = SafeDB($dfile['votes_amount'],11,int)+1;
					$db->Update('downloads',"votes_amount='$numvotes',votes='$vote'","`id`='$file'");
					$site->AddTextBox('','<center>Спасибо за вашу оценку.<br><br><a href="javascript:history.go(-1)">Назад</a></center>');
				}
			}
		}else{
			//Оценка запрещена
			$site->AddTextBox('','<center>Извините, оценка этого файла запрещена.<br><br><a href="javascript:history.go(-1)">Назад</a></center>');
		}
	}else{
		//Файл не существует
		$site->AddTextBox('','<center>Произошла ошибка. Файл, который вы пытаетесь оценить, не найден в нашем файловом архиве. Возможно он был удален.<br><br><a href="javascript:history.go(-1)">Назад</a></center>');
	}
}

function IndexDownloadsAddPost()
{
	global $db, $config, $site;
	$get_id        = 'file'; // Имя параметра в get для получения id объекта
	$table         = 'downloads_comments'; // Таблица комментариев
	$object_table  = 'downloads'; // Таблица объектов
	$counter_field = 'comments_counter'; // Поле счетчик комментариев в таблице объекта
	$alloy_field   = 'allow_comments' ; // Поле разрешить комментирии для этого объекта

	$id = SafeEnv($_GET[$get_id], 11, int);
	$db->Select($object_table, "`id`='$id'");
	$obj = $db->FetchRow();
	$alloy_comments = $obj[$alloy_field] == '1';
	// Добавляем комментарий
	include_once($config['inc_dir'].'posts.class.php');
	$posts = new Posts($table, $alloy_comments);
	if($posts->SavePost($id, false)){
		$db->Select($object_table, "`id`='$id'");
		$obj = $db->FetchRow();
		$counter = $obj[$counter_field] + 1;
		$db->Update($object_table, "`$counter_field`='$counter'", "`id`='$id'");
		// Генерируем обратную ссылку
		$parent = explode('_', $_POST['parent_id'], 2);
		$parent_id = SafeEnv($parent[1], 11, int);
		$page = ($parent_id != 0 && $_GET['page'] != 0 ? "&page={$_GET['page']}" : '');
		$parent = ($parent_id != 0 ? "#post_$parent_id" : '#post_'.$db->GetLastId());
		$cat = SafeDB($_GET['cat'], 11, int);
		GO(GetSiteUrl().Ufu("index.php?name=downloads&op=full&file=$id$page&cat=$cat$parent", 'downloads/{cat}/{file}/'.($page != '' ? 'page{page}/' : '')));
		// --------------------------
	}else{
		$site->AddTextBox('Ошибка', $posts->PrintErrors());
	}
}

function IndexDownloadsEditPost( $back_id = null )
{
	global $site, $config;
	$get_id = 'file';              // Имя параметра в get для получения id объекта
	$table = 'downloads_comments'; // Таблица комментариев
	if($back_id == null){
		$back_id = SaveRefererUrl();
	}
	$action_url = 'index.php?name=downloads&op=savepost&file='.SafeEnv($_GET[$get_id],11,int)."&back=$back_id";

	$site->AddTemplatedBox('','edit_comment.html');
	include_once($config['inc_dir'].'posts.class.php');
	$posts = new Posts($table);
	$posts->PostFormAction = $action_url;
	$posts->RenderForm(true, 'post_form');
}

function IndexDownloadsEditPostSave()
{
	global $config;
	$get_id = 'file';              // Имя параметра в get для получения id объекта
	$table = 'downloads_comments'; // Таблица комментариев

	include_once($config['inc_dir'].'posts.class.php');
	$posts = new Posts($table);
	if($posts->SavePost(SafeEnv($_GET[$get_id], 11, int), true)){
		GoRefererUrl($_GET['back']);
	}else{
		$site->AddTextBox('Ошибка', $posts->PrintErrors());
		IndexDownloadsEditPost($_GET['back']);
	}
}

function IndexDownloadsDeletePost()
{
	global $config, $db;
	$get_id = 'file'; // Имя параметра в get для получения id объекта
	$table = 'downloads_comments'; // Таблица комментариев
	$object_table = 'downloads'; // Таблица объектов
	$counter_field = 'comments_counter'; // Поле счетчик комментариев в таблице объекта

	if(!isset($_GET['back'])){
		$back_id = SaveRefererUrl();
	}else{
		$back_id = $_GET['back'];
	}
	$id = SafeEnv($_GET[$get_id], 11, int);
	$delete_url = "index.php?name=downloads&op=deletepost&file=$id&back=$back_id";

	include_once($config['inc_dir'].'posts.class.php');
	$posts = new Posts($table);
	$posts->DeletePageUrl = $delete_url;
	$deleted_posts_count = $posts->DeletePost();
	if($deleted_posts_count > 0){
		$db->Select($object_table, "`id`='$id'");
		$obj = $db->FetchRow();
		$counter = $obj[$counter_field] - $deleted_posts_count;
		$db->Update($object_table, "`$counter_field`='$counter'", "`id`='$id'");
		GoRefererUrl($back_id);
	}
}

?>