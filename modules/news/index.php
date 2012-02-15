<?php

// Модуль новости

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$site->SetTitle('Новости');
$site->RssTitle = 'Новости RSS';
$site->RssLink = $config['general']['site_url'].'rss.php';

if(isset($_GET['op'])){
	$op = $_GET['op'];
}else{
	$op = 'main';
}

switch($op){
	case 'main': IndexNewsMain();
		break;
	case 'readfull': IndexNewsReadFull();
		break;
	case 'topics': IndexNewsTopics();
		break;
	case 'addpost': IndexNewsAddPost();
		break;
	case 'editpost': IndexNewsEditPost();
		break;
	case 'savepost': IndexNewsEditPostSave();
		break;
	case 'deletepost': IndexNewsDeletePost();
		break;
	default: IndexNewsMain();
}

function IndexNewsFunc( $id ){
	global $config;
	return '&nbsp'
	.'<a href="'.ADMIN_FILE.'?exe=news&a=edit&id='.$id.'&back='.SaveRefererUrl().'" class="admin_edit_link"><img src="images/admin/edit.png" title="Редактировать"></a>'
	.'<a href="'.ADMIN_FILE.'?exe=news&a=delete&id='.$id.'&back='.SaveRefererUrl().'&ok=0" class="admin_edit_link"><img src="images/admin/delete.png" title="Удалить"></a>';
}

function IndexNewsAdd( &$news, $topic, $readfull=false ){
	global $newsTemp, $site, $op, $config, $user;

	$func = IndexNewsFunc(SafeDB($news['id'], 11, int));
	$img_view = SafeDB($news['img_view'],1,int);
	$link = Ufu('index.php?name=news&op=readfull&news='.SafeDB($news['id'], 11, int).'&topic='.SafeDB($news['topic_id'], 11, int), 'news/{topic}/{news}/');
	$topic_link = Ufu('index.php?name=news&topic='.SafeDB($news['topic_id'], 11, int), 'news/{topic}/');

	$vars['topic'] = $topic;
	$vars['id'] = SafeDB($news['id'],11,int);
	$vars['title'] = SafeDB($news['title'],255,str).($user->isAdmin() ? $func : '');
	$vars['author'] = SafeDB($news['author'],255,str);

	$date = SafeDB($news['date'], 11, int);
	$vars['date'] = TimeRender($date);
	$vars['time'] = date('H:i', $date);
	$vars['day'] = date('d', $date);
	$vars['month'] = date('m', $date);
	$vars['year'] = date('y', $date);
	$vars['year_full'] = date('Y', $date);

	$vars['link'] = $link;
	$vars['cat_link'] = $topic_link;
	$vars['com_count'] = SafeDB($news['comments_counter'],11,int);
	$vars['admin'] = $user->isAdmin();

	if(isset($_GET['topic'])){
		$link .= '&topic='.SafeEnv($_GET['topic'],11,int);
	}

	if(strlen(strip_tags($news['end_text']))>0){ // Есть ли продолжение новости.
		$vars['full'] = '<a href="'.$link.'">Читать далее…</a>';
		$read_more = true;
	}else{
		$vars['full'] = '';
		$read_more = false;
	}

	$image = SafeDB(RealPath2($news['icon']),255,str);
	$icons_dir = $config['news']['icons_dirs'];
	if(!is_file($icons_dir.$image)){
		$vars['image'] = '';
		$vars['image_url'] = false;
	}elseif($img_view == 1){ // Исходная картинка
		$vars['image'] = $icons_dir.$image;
		$vars['image_url'] = false;
	}elseif($img_view == 2){ // Эскиз
		$vars['image'] = $icons_dir.'thumbs/'.$image;
		$vars['image_url'] = $icons_dir.$image;
	}elseif($img_view == 0){ // Авто
		$size = ImageSize($icons_dir.$image);
		if($size['width'] > $config['news']['thumb_max_width']){
			$vars['image'] = $icons_dir.'thumbs/'.$image;
			$vars['image_url'] = $icons_dir.$image;
		}else{
			$vars['image'] = $icons_dir.$image;
			$vars['image_url'] = false;
		}
	}

	if(!$readfull){
	// Короткая новость
		if($news['allow_comments']=='1'){
			$vars['com'] = '<a href="'.$link.'#comments">Комментировать('.SafeDB($news['comments_counter'],11,int).')</a>';
		}else{
			$vars['com'] = '';
		}

		if($news['auto_br']=='1'){
			$news['start_text'] = SafeDB(nl2br($news['start_text']), 0, str, false, false);
		}else{
			$news['start_text'] = SafeDB($news['start_text'], 0, str, false, false);
		}
		$vars['text'] = $news['start_text'];

	}else{
	// Полная новость
		if(SafeDB($news['comments_counter'],11,int) > 0){
			$vars['com_status'] = 'Комментарии';
		}else{
			$vars['com_status'] = 'Комментариев пока нет';
		}
		if($news['auto_br']=='1' && $read_more){
			$news['end_text'] = SafeDB(nl2br($news['end_text']), 0, str, false, false);
		}elseif($news['auto_br']=='0' && $read_more){
			$news['end_text'] = SafeDB($news['end_text'], 0, str, false, false);
		}elseif($news['auto_br']=='1' && !$read_more){
			$news['end_text'] = SafeDB(nl2br($news['start_text']), 0, str, false, false);
		}else{
			$news['end_text'] = SafeDB($news['start_text'], 0, str, false, false);
		}
		$vars['text'] = $news['end_text'];
	}

	$site->AddSubBlock('news',true,$vars);
}


function IndexNewsMain(){
	global $config, $site;

	$site->AddBlock('news',true,true);

	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'],10,int);
	}else{
		$page = 1;
	}

	$topics = GetTopics();
	if(isset($_GET['topic'])){
		$topic = SafeEnv($_GET['topic'], 11, int);
		$site->SetTitle('Новости в категории '.SafeDB($topics[$topic]['title'], 255, str));
	}else{
		$topic = false;
	}

	$news = System::database()->Select('news', GetWhereByAccess('view', "`enabled`='1'".($topic !== false ? " and `topic_id`='$topic'" : "")));
	SortArray($news, 'date', true);

	// Постраничная навигация
	$num = SafeDB($config['news']['newsonpage'], 11, int); //Количество новостей на страницу
	$navigation = new Navigation($page);
	$nav_link = Ufu('index.php?name=news'.($topic != 0 ? '&topic='.$topic : ''), 'news/'.($topic != 0 ? '{topic}/' : '').'page-{page}/', true);
	$navigation->FrendlyUrl = $config['general']['ufu'];
	$navigation->GenNavigationMenu($news, $num, $nav_link);

	if(count($news)>0){
		$site->AddTemplatedBox('','module/news.html');
		foreach($news as $s){
			IndexNewsAdd($s, SafeDB($topics[$s['topic_id']]['title'], 255, str), false);
		}
	}else{
		if($topic===false){
			$NewsContent = '<center>Новостей пока нет</center>';
		}else{
			$NewsContent = '<center>В этой категории пока нет новостей</center>';
		}
		$site->AddTextBox('', $NewsContent);
	}
}


function GetTopics(){
	System::database()->Select('news_topics','');
	$rs = array();
	while($topic = System::database()->FetchRow()){
		$rs[SafeDB($topic['id'], 11, int)] = $topic;
	}
	return $rs;
}

function IndexNewsTopics(){
	global $site, $config;
	$site->SetTitle('Разделы новостей');
	$topics = GetTopics();
	if(count($topics) == 0){
		$site->AddTextBox('Разделы новостей', '<center>Разделов новостей пока нет.</center>');
	}else{
		$site->AddTemplatedBox('Разделы новостей', 'module/news_topics.html');
		$site->AddBlock('news_topics', true, true, 'newstopic');
		foreach($topics as $topic){
			$vars = array();
			$image = SafeDB(RealPath2($topic['image']), 255, str);
			$icons_dir = $config['news']['icons_dirs'];
			if(!is_file($icons_dir.$image)){
				$vars['image'] = '';
				$vars['image_url'] = false;
			}else{
				$size = ImageSize($icons_dir.$image);
				if($size['width'] > $config['news']['thumb_max_width']){
					$vars['image'] = $icons_dir.'thumbs/'.$image;
					$vars['image_url'] = $icons_dir.$image;
				}else{
					$vars['image'] = $icons_dir.$image;
					$vars['image_url'] = false;
				}
			}
			$vars['url'] = Ufu('index.php?name=news&topic='.SafeDB($topic['id'], 11, int), 'news/{topic}/');
			$vars['desc'] = SafeDB($topic['description'],255,str);
			$vars['title'] = SafeDB($topic['title'],255,str);
			$vars['num_news'] = SafeDB($topic['counter'],11,int);
			$site->AddSubBlock('news_topics',true,$vars);
		}
	}
}

function IndexNewsReadFull(){
	global $db, $config, $site;
	$site->AddTemplatedBox('', 'module/news_full.html');
	$site->AddBlock('news', true, true);
	if(isset($_GET['news'])){
		$topics = GetTopics();
		$news_id = SafeEnv($_GET['news'],11,int);
		$db->Select('news', GetWhereByAccess('view', "`id`='$news_id'"));
		if($db->NumRows() > 0){
			$news = $db->FetchRow();
		}
		if($db->NumRows() > 0 && $news['enabled']=='1'){ // Новость включена
			$site->SetTitle(SafeDB($news['title'],255,str));
			//Модуль SEO
			$site->SeoTitle = SafeDB($news['seo_title'],255,str);
			$site->SeoKeyWords = SafeDB($news['seo_keywords'],255,str);
			$site->SeoDescription = SafeDB($news['seo_description'],255,str);
			//
			$topic_id = SafeDB($news['topic_id'], 11, int);
			IndexNewsAdd($news, SafeDB($topics[$topic_id]['title'], 255, str), true);
			$db->Update('news', "hit_counter='".(SafeDB($news['hit_counter'],11,int)+1)."'","`id`='".$news_id."'");

			// Выводим комментарии
			if(isset($_GET['page'])){
				$page = SafeEnv($_GET['page'], 11, int);
			}else{
				$page = 0;
			}
			include_once($config['inc_dir'].'posts.class.php');
			$posts = new Posts('news_comments', $news['allow_comments'] == '1');
			$posts->EditPageUrl = "index.php?name=news&op=editpost&news=$news_id";
			$posts->DeletePageUrl = "index.php?name=news&op=deletepost&news=$news_id";
			$posts->PostFormAction = "index.php?name=news&op=addpost&news=$news_id&topic=$topic_id&page=$page";

			$posts->NavigationUrl = Ufu("index.php?name=news&op=readfull&news=$news_id&topic=$topic_id", 'news/{topic}/{news}/page{page}/', true);
			$posts->RenderPosts($news_id, 'news_comments', 'comments_navigation', false, $page);
			$posts->RenderForm(false, 'news_comments_form');
		}else{
			$site->AddTextBox('Ошибка','<center>Эта новость не доступна в данный момент!<br><input type="button" value="Назад" onclick="history.back();"></center>');
		}
	}else{
		$site->AddTextBox('Ошибка','<center><input type="button" value="Назад" onclick="history.back();"></center>');
	}
}

function IndexNewsAddPost(){
	global $db, $config, $site;
	$get_id        = 'news';             // Имя параметра в get для получения id объекта
	$table         = 'news_comments';    // Таблица комментариев
	$object_table  = 'news';             // Таблица объектов
	$counter_field = 'comments_counter'; // Поле счетчик комментариев в таблице объекта
	$alloy_field   = 'allow_comments';   // Поле разрешить комментирии для этого объекта

	$id = SafeEnv($_GET[$get_id],11,int);
	$db->Select($object_table, "`id`='$id'");
	$obj = $db->FetchRow();
	$alloy_comments = $obj[$alloy_field] == '1';
	// Добавляем комментарий
	include_once($config['inc_dir'].'posts.class.php');
	$posts = new Posts($table, $alloy_comments);
	if($posts->SavePost($id, false)){
		$counter = $obj[$counter_field] + 1;
		$db->Update($object_table, "`$counter_field`='$counter'", "`id`='$id'");
		// Генерируем обратную ссылку
		$parent = explode('_', $_POST['parent_id'], 2);
		$parent_id = SafeEnv($parent[1], 11, int);
		$page = ($parent_id != 0 && $_GET['page'] != 0 ? "&page={$_GET['page']}" : '');
		$parent = ($parent_id != 0 ? "#post_$parent_id" : '#post_'.$db->GetLastId());
		$topic = SafeDB($_GET['topic'], 11, int);
		GO(GetSiteUrl().Ufu("index.php?name=news&op=readfull&news=$id$page&topic=$topic$parent", 'news/{topic}/{news}/'.($page != '' ? 'page{page}/' : '')));
		// --------------------------
	}else{
		$site->AddTextBox('Ошибка', $posts->PrintErrors());
	}
}

function IndexNewsEditPost( $back_id = null ){
	global $site, $config;
	$get_id = 'news';             // Имя параметра в get для получения id объекта
	$table = 'news_comments'; // Таблица комментариев
	if($back_id == null){
		$back_id = SaveRefererUrl();
	}
	$action_url = 'index.php?name=news&op=savepost&news='.SafeEnv($_GET[$get_id],11,int)."&back=$back_id";

	$site->AddTemplatedBox('','edit_comment.html');
	include_once($config['inc_dir'].'posts.class.php');
	$posts = new Posts($table);
	$posts->PostFormAction = $action_url;
	$posts->RenderForm(true, 'post_form');
}

function IndexNewsEditPostSave(){
	global $config;
	$get_id = 'news';          // Имя параметра в get для получения id объекта
	$table  = 'news_comments'; // Таблица комментариев

	include_once($config['inc_dir'].'posts.class.php');
	$posts = new Posts($table);
	if($posts->SavePost(SafeEnv($_GET[$get_id], 11, int), true)){
		$post_id = SafeDB($_GET['post_id'], 11, int);
		GoRefererUrl($_GET['back']);
	}else{
		$site->AddTextBox('Ошибка', $posts->PrintErrors());
		IndexNewsEditPost($_GET['back']);
	}
}

function IndexNewsDeletePost(){
	global $config, $db;
	$get_id = 'news'; // Имя параметра в get для получения id объекта
	$table = 'news_comments'; // Таблица комментариев
	$object_table = 'news'; // Таблица объектов
	$counter_field = 'comments_counter'; // Поле счетчик комментариев в таблице объекта

	if(!isset($_GET['back'])){
		$back_id = SaveRefererUrl();
	}else{
		$back_id = $_GET['back'];
	}
	$id = SafeEnv($_GET[$get_id], 11, int);
	$delete_url = "index.php?name=news&op=deletepost&news=$id&back=$back_id";

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
