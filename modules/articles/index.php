<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$site->SetTitle('Архив статей');

include_once ($config['inc_dir'].'tree_b.class.php');
$tree = new IndexTree('articles_cats');
$tree->moduleName = 'articles';
$tree->id_par_name = 'cat';
$tree->NumItemsCaption = '<center>Всего статей в нашем архиве: ';
$tree->TopCatName = 'Статьи';

if(isset($_GET['op'])){
	$op = $_GET['op'];
}else{
	$op = 'main';
}

switch($op){
	case 'main':
		if(isset($_GET['cat'])){
			$cat = SafeEnv($_GET['cat'], 11, int);
		}else{
			$cat = 0;
		}
		if($config['articles']['show_catnav'] == '1'){
			$tree->Catalog($cat, 'IndexArticlesGetNumItems');
		}
		if($cat != 0 || $config['articles']['show_last'] == '1'){
			IndexArticlesShow($cat);
		}
		break;
	case 'read':
		IndexArticlesRead();
		break;
	case 'addvote':
		IndexArticlesAddVote();
		break;
	// Комментарии
	case 'addpost': IndexArticlesAddPost();
		break;
	case 'editpost': IndexArticlesEditPost();
		break;
	case 'savepost': IndexArticlesEditPostSave();
		break;
	case 'deletepost': IndexArticlesDeletePost();
		break;
	// //
	default:
		HackOff();
}

function IndexArticlesGetNumItems()
{
	global $db;
	$ex_where = GetWhereByAccess('view');
	$db->Select('articles', '`active`=\'1\''.($ex_where != '' ? ' and '.$ex_where : ''));
	return $db->NumRows().'.</center>';
}

function IndexArticlesFunc( $id )
{
	global $config;
	return
	'&nbsp'
	."<a href=\"{$config['admin_file']}?exe=articles&a=editor&id=$id\" class=\"admin_edit_link\"><img src=\"images/admin/edit.png\" title=\"Редактировать\"></a>"
	."<a href=\"{$config['admin_file']}?exe=articles&a=delete&id=$id&ok=0\" class=\"admin_edit_link\"><img src=\"images/admin/delete.png\" title=\"Удалить\"></a>";
}

function RenderReadArticle( &$art )
{
	global $site, $config, $user, $tree;
	$vars = array();
	$art_id = SafeDB($art['id'], 11, int);
	$cat_id = SafeDB($art['cat_id'], 11, int);

	$func = IndexArticlesFunc($art_id);
	$vars['title'] = SafeDB($art['title'], 250, str).($user->isAdmin() ? $func : '');

	$vars['cat'] = $tree->IdCats[$cat_id]['title'];
	$vars['catlink'] = Ufu("index.php?name=articles&cat=$cat_id", 'articles/{cat}/');

	if($art['auto_br_article'] == '1'){
		$vars['article'] = nl2br(SafeDB($art['article'], 0, str, false, false, false));
	}else{
		$vars['article'] = SafeDB($art['article'], 0, str, false, false, false);
	}
	$vars['lauthor'] = 'Автор';
	$vars['author'] = SafeDB($art['author'], 200, str);
	$vars['lemail'] = 'E-mail';
	$vars['email'] = SafeDB($art['email'], 50, str);
	$vars['lurl'] = 'Источник';
	$vars['site'] = SafeDB($art['www'], 250, str);
	$vars['site_url'] = UrlRender(SafeDB($art['www'], 250, str));
	$vars['lpublic'] = 'Опубликована';
	$vars['public'] = TimeRender(SafeDB($art['public'], 11, int), false);
	$vars['lhits'] = 'Просмотров';
	$vars['hits'] = SafeDB($art['hits'], 11, int);
	$vars['lcomments'] = 'Комментарий';
	$vars['comments'] = SafeDB($art['comments_counter'], 11, int);
	$vars['allow_votes'] = $art['allow_votes'] == '1';

	$vars['addvote_url'] = "index.php?name=articles&op=addvote&article=$art_id&cat=$cat_id";

	$site->DataAdd($vdata, '0', 'Ваша оценка');
	$site->DataAdd($vdata, '1', 'Очень плохо');
	$site->DataAdd($vdata, '2', 'Плохо');
	$site->DataAdd($vdata, '3', 'Средне');
	$site->DataAdd($vdata, '4', 'Хорошо');
	$site->DataAdd($vdata, '5', 'Отлично');
	$vars['votes'] = $site->Select('vote', $vdata);
	$vars['lvote'] = 'Оценить эту статью';
	$vars['addvotesubm'] = $site->Submit('Оценить статью');

	//Выводим rating
	$rating = GetRatingImage(SafeDB($art['num_votes'], 11, int), SafeDB($art['all_votes'], 11, int));
	$vars['rating_image'] = $rating;
	$vars['alloy_rating'] = SafeDB($art['allow_votes'], 1, bool);
	$vars['disable_rating'] = !$vars['alloy_rating'];
	$vars['lrating'] = 'Оценка';
	$vars['rating_num_votes'] = SafeDB($art['num_votes'], 11, int);
	//

	$site->AddBlock('article', true, false, 'art');
	$site->Blocks['article']['vars'] = $vars;
}

function RenderArticle( &$art )
{
	global $site, $config, $user, $tree;
	$vars = array();
	$art_id = SafeDB($art['id'], 11, int);
	$cat_id = SafeDB($art['cat_id'], 11, int);
	$func = IndexArticlesFunc($art_id);
	$vars['title'] = SafeDB($art['title'], 250, str).($user->isAdmin() ? $func : '');
	$vars['cat'] = $tree->IdCats[$cat_id]['title'];

	$vars['catlink'] = Ufu("index.php?name=articles&cat=$cat_id", 'articles/{cat}/');

	if($art['image'] != ''){
		$vars['image'] = RealPath2($config['articles']['images_dir'].SafeDB($art['image'], 255, str));
		$vars['thumb_image'] = RealPath2($config['articles']['images_dir'].'thumbs/'.SafeDB($art['image'], 255, str));
	}else{
		$vars['image'] = false;
	}
	if($art['auto_br_desc'] == '1'){
		$vars['description'] = nl2br(SafeDB($art['description'], 0, str, false, false, false));
	}else{
		$vars['description'] = SafeDB($art['description'], 0, str, false, false, false);
	}
	$vars['lauthor'] = 'Автор';
	$vars['author'] = SafeDB($art['author'], 200, str);
	$vars['lemail'] = 'E-mail';
	$vars['email'] = SafeDB($art['email'], 50, str);
	$vars['lurl'] = 'Источник';
	$vars['site'] = SafeDB($art['www'], 250, str);
	$vars['site_url'] = UrlRender(SafeDB($art['www'], 250, str));

	$vars['lpublic'] = 'Опубликована';
	$vars['public'] = TimeRender(SafeDB($art['public'], 11, int), false);

	$vars['link2'] = Ufu("index.php?name=articles&op=read&art=$art_id&cat=$cat_id", 'articles/{cat}/{art}/');

	$vars['link'] = '<a href="'.$vars['link2'].'">Читать...</a>';
	$vars['lhits'] = 'Просмотров';
	$vars['hits'] = SafeDB($art['hits'], 11, int);
	$vars['lcomments'] = 'Комментарий';
	$vars['comments'] = SafeDB($art['comments_counter'], 11, int);

	//Выводим rating
	$rating = GetRatingImage(SafeDB($art['num_votes'], 11, int), SafeDB($art['all_votes'], 11, int));
	$vars['rating_image'] = $rating;
	$vars['alloy_rating'] = SafeDB($art['allow_votes'], 1, bool);
	$vars['disable_rating'] = !$vars['alloy_rating'];
	$vars['lrating'] = 'Оценка';
	$vars['rating_num_votes'] = SafeDB($art['num_votes'], 11, int);
	//

	$site->AddSubBlock('articles', true, $vars);
}

function IndexArticlesShow( $cat )
{
	global $db, $config, $site, $tree;

	if($cat != 0){
		$site->SetTitle('Статьи в категории '.SafeDB($tree->IdCats[$cat]['title'], 255, str));
	}

	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 11, int);
	}else{
		$page = 1;
	}

	$where = ($cat != 0 ? "`cat_id`='$cat' and " : '')."`active`='1'";
	$ex_where = GetWhereByAccess('view');
	if($ex_where != ''){
		$where .= ' and ('.$ex_where.')';
	}
	$arts = $db->Select('articles', $where);
	SortArray($arts, 'public', true);


	// Постраничная навигация
	$num = $config['articles']['articles_on_page'];
	$navigation = new Navigation($page);
	$nav_link = Ufu('index.php?name=articles'.($cat != 0 ? '&cat='.$cat : ''), 'articles/'.($cat != 0 ? '{cat}/' : '').'page{page}/', true);
	$navigation->FrendlyUrl = $config['general']['ufu'];
	$navigation->GenNavigationMenu($arts, $num, $nav_link);

	if($db->NumRows() > 0){
		$site->AddTemplatedBox('', 'module/article.html');
		$site->AddBlock('articles', true, true, 'art');
		foreach($arts as $art){
			RenderArticle($art);
		}
	}elseif(!isset($tree->Cats[$cat]) && count($tree->Cats) > 0){
		$site->AddTextBox('', '<center>В этой категории статей пока нет.</center>');
	}
}

function IndexArticlesRead()
{
	global $db, $config, $site, $tree, $user;
	if(isset($_GET['art'])){
		$id = SafeEnv($_GET['art'], 11, int);
	}else{
		GO(GetSiteUrl().Ufu('index.php?name=articles', '{name}/'));
	}
	$where = "`id`='$id' and `active`='1'";
	$ex_where = GetWhereByAccess('view');
	if($ex_where != ''){
		$where .= ' and ('.$ex_where.')';
	}

	$db->Select('articles', $where);
	if($db->NumRows() == 0){
		GO(GetSiteUrl().Ufu('index.php?name=articles', '{name}/'));
	}
	$art = $db->FetchRow();
	$db->Update('articles', "hits='".(SafeEnv($art['hits'], 11, int) + 1)."'", $where);
	$cat = SafeDB($art['cat_id'], 11, int);

	// Показываем путь
	if($config['articles']['show_catnav'] == '1'){
		$tree->ShowPath($art['cat_id'], true, SafeDB($art['title'], 255, str));
	}

	$site->AddTemplatedBox('', 'module/article_read.html');
	$site->SetTitle(SafeDB($art['title'], 255, str));
	$site->SeoTitle = SafeDB($art['seo_title'], 255, str);
	$site->SeoKeyWords = SafeDB($art['seo_keywords'], 255, str);
	$site->SeoDescription = SafeDB($art['seo_description'], 255, str);
	RenderReadArticle($art);

	// Выводим комментарии
	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 11, int);
	}else{
		$page = 0;
	}
	include_once($config['inc_dir'].'posts.class.php');
	$posts = new Posts('articles_comments', $art['allow_comments'] == '1');
	$posts->EditPageUrl = "index.php?name=articles&op=editpost&art=$id";
	$posts->DeletePageUrl = "index.php?name=articles&op=deletepost&art=$id";
	$posts->PostFormAction = "index.php?name=articles&op=addpost&art=$id&cat=$cat&page=$page";

	$posts->NavigationUrl = Ufu("index.php?name=articles&op=read&art=$id&cat=$cat", 'articles/{cat}/{art}/page{page}/', true);
	$posts->RenderPosts($id, 'article_comments', 'comments_navigation', false, $page);
	$posts->RenderForm(false, 'article_comments_form');
}

function IndexArticlesAddVote()
{
	global $db, $config, $site, $user;
	$ip = getip();
	$time = time() - 86400; //1 день
	$article = SafeEnv($_GET['article'], 11, int);

	$vote = SafeEnv($_POST['vote'], 1, int);
	$db->Delete('articles_rating', "`time`<'$time'");

	$site->OtherMeta .= '<meta http-equiv="REFRESH" content="3; URL='.HistoryGetUrl(1).'">';

	$where = "`id`='$article' and `active`='1'";
	$ex_where = GetWhereByAccess('view');
	if($ex_where != ''){
		$where .= ' and ('.$ex_where.')';
	}
	$db->Select('articles', $where);

	if($db->NumRows() > 0){
		$dfile = $db->FetchRow();
		if($dfile['allow_votes']=='1'){ // оценки разрешены
			$db->Select('articles_rating',"`ip`='$ip' and `downid`='$article'");
			if($db->NumRows() > 0){
				$site->AddTextBox('','<center>Вы уже голосовали за эту статью.<br /><br /><a href="javascript:history.go(-1)">Назад</a></center>');
			}else{
				if($vote==0){
					$site->AddTextBox('','<center>Вы не выбрали оценку.<br /><br /><a href="javascript:history.go(-1)">Назад</a></center>');
				}else{
					$user->ChargePoints($config['points']['article_rating']);
					$time = time();
					$db->Insert('articles_rating',"'','$article','$ip','$time'");
					$numvotes = SafeDB($dfile['num_votes'],11,int) + 1;
					$vote = SafeDB($dfile['all_votes'],11,int) + $vote;
					$db->Update('articles', "num_votes='$numvotes',all_votes='$vote'", "`id`='$article'");
					$site->AddTextBox('','<center>Спасибо за вашу оценку.<br><br><a href="javascript:history.go(-1)">Назад</a></center>');
				}
			}
		}else{
			$site->AddTextBox('','<center>Извините, оценка этой статьи запрещена.<br><br><a href="javascript:history.go(-1)">Назад</a></center>');
		}
	}else{
		$site->AddTextBox('','<center>Произошла ошибка. Статья не найдена.<br><br><a href="javascript:history.go(-1)">Назад</a></center>');
	}
}

function IndexArticlesAddPost()
{
	global $db, $config, $site;
	$get_id        = 'art'; // Имя параметра в get для получения id объекта
	$table         = 'articles_comments'; // Таблица комментариев
	$object_table  = 'articles'; // Таблица объектов
	$counter_field = 'comments_counter'; // Поле счетчик комментариев в таблице объекта
	$alloy_field   = 'allow_comments';     // Поле разрешить комментирии для этого объекта

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
		GO(GetSiteUrl().Ufu("index.php?name=articles&op=read&art=$id$page&cat=$cat$parent", 'articles/{cat}/{art}/'.($page != '' ? 'page{page}/' : '')));
		// --------------------------
	}else{
		$site->AddTextBox('Ошибка', $posts->PrintErrors());
	}
}

function IndexArticlesEditPost( $back_id = null )
{
	global $site, $config;
	$get_id = 'art';              // Имя параметра в get для получения id объекта
	$table = 'articles_comments'; // Таблица комментариев
	if($back_id == null){
		$back_id = SaveRefererUrl();
	}
	$action_url = 'index.php?name=articles&op=savepost&art='.SafeEnv($_GET[$get_id],11,int)."&back=$back_id";
	$site->AddTemplatedBox('','edit_comment.html');
	include_once($config['inc_dir'].'posts.class.php');
	$posts = new Posts($table);
	$posts->PostFormAction = $action_url;
	$posts->RenderForm(true, 'post_form');
}

function IndexArticlesEditPostSave()
{
	global $config;
	$get_id = 'art';             // Имя параметра в get для получения id объекта
	$table = 'articles_comments'; // Таблица комментариев

	include_once($config['inc_dir'].'posts.class.php');
	$posts = new Posts($table);
	if($posts->SavePost(SafeEnv($_GET[$get_id], 11, int), true)){
		GoRefererUrl($_GET['back']);
	}else{
		$site->AddTextBox('Ошибка', $posts->PrintErrors());
		IndexArticlesEditPost($_GET['back']);
	}
}

function IndexArticlesDeletePost()
{
	global $config, $db;
	$get_id = 'art'; // Имя параметра в get для получения id объекта
	$table = 'articles_comments'; // Таблица комментариев
	$object_table = 'articles'; // Таблица объектов
	$counter_field = 'comments_counter'; // Поле счетчик комментариев в таблице объекта

	if(!isset($_GET['back'])){
		$back_id = SaveRefererUrl();
	}else{
		$back_id = $_GET['back'];
	}
	$id = SafeEnv($_GET[$get_id], 11, int);
	$delete_url = "index.php?name=articles&op=deletepost&art=$id&back=$back_id";

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