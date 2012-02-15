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
	//
	default:
		HackOff();
}

function IndexArticlesGetNumItems(){
	System::database()->Select('articles', GetWhereByAccess('view', "`active`='1'"));
	return System::database()->NumRows().'.</center>';
}

function IndexArticlesFunc( $id ){
	$back = SaveRefererUrl();
	return
	'&nbsp'
	.'<a href="'.ADMIN_FILE.'?exe=articles&a=editor&id='.$id.'&back='.$back.'" class="admin_edit_link"><img src="images/admin/edit.png" title="Редактировать"></a>'
	.'<a href="'.ADMIN_FILE.'?exe=articles&a=delete&id='.$id.'&ok=0&back='.$back.'" class="admin_edit_link"><img src="images/admin/delete.png" title="Удалить"></a>';
}

function RenderReadArticle( &$art ){
	global $tree;
	$vars = array();
	$art_id = SafeDB($art['id'], 11, int);
	$cat_id = SafeDB($art['cat_id'], 11, int);
	$vars['title'] = SafeDB($art['title'], 250, str).(System::user()->isAdmin() ? IndexArticlesFunc($art_id) : '');
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

	System::site()->DataAdd($vdata, '0', 'Ваша оценка');
	System::site()->DataAdd($vdata, '1', 'Очень плохо');
	System::site()->DataAdd($vdata, '2', 'Плохо');
	System::site()->DataAdd($vdata, '3', 'Средне');
	System::site()->DataAdd($vdata, '4', 'Хорошо');
	System::site()->DataAdd($vdata, '5', 'Отлично');
	$vars['votes'] = System::site()->Select('vote', $vdata);
	$vars['lvote'] = 'Оценить эту статью';
	$vars['addvotesubm'] = System::site()->Submit('Оценить статью');

	//Выводим rating
	$rating = GetRatingImage(SafeDB($art['num_votes'], 11, int), SafeDB($art['all_votes'], 11, int));
	$vars['rating_image'] = $rating;
	$vars['alloy_rating'] = SafeDB($art['allow_votes'], 1, bool);
	$vars['disable_rating'] = !$vars['alloy_rating'];
	$vars['lrating'] = 'Оценка';
	$vars['rating_num_votes'] = SafeDB($art['num_votes'], 11, int);
	//

	System::site()->AddBlock('article', true, false, 'art');
	System::site()->Blocks['article']['vars'] = $vars;
}

function RenderArticle( &$art ){
	global $tree;
	$vars = array();
	$art_id = SafeDB($art['id'], 11, int);
	$cat_id = SafeDB($art['cat_id'], 11, int);
	$func = IndexArticlesFunc($art_id);
	$vars['title'] = SafeDB($art['title'], 250, str).(System::user()->isAdmin() ? $func : '');
	$vars['cat'] = $tree->IdCats[$cat_id]['title'];
	$vars['catlink'] = Ufu("index.php?name=articles&cat=$cat_id", 'articles/{cat}/');
	if($art['image'] != ''){
		$vars['image'] = RealPath2(System::config('articles/images_dir').SafeDB($art['image'], 255, str));
		$vars['thumb_image'] = RealPath2(System::config('articles/images_dir').'thumbs/'.SafeDB($art['image'], 255, str));
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

	System::site()->AddSubBlock('articles', true, $vars);
}

function IndexArticlesShow( $cat ){
	global $tree;
	if($cat != 0){
		System::site()->SetTitle('Статьи в категории '.SafeDB($tree->IdCats[$cat]['title'], 255, str));
	}
	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 11, int);
	}else{
		$page = 1;
	}
	$arts = System::database()->Select('articles', GetWhereByAccess('view', ($cat != 0 ? "`cat_id`='$cat' and " : '')."`active`='1'"));
	SortArray($arts, 'public', true);

	// Постраничная навигация
	$num = System::config('articles/articles_on_page');
	$navigation = new Navigation($page);
	$nav_link = Ufu('index.php?name=articles'.($cat != 0 ? '&cat='.$cat : ''), 'articles/'.($cat != 0 ? '{cat}/' : '').'page{page}/', true);
	$navigation->FrendlyUrl = System::config('general/ufu');
	$navigation->GenNavigationMenu($arts, $num, $nav_link);

	if(System::database()->NumRows() > 0){
		System::site()->AddTemplatedBox('', 'module/article.html');
		System::site()->AddBlock('articles', true, true, 'art');
		foreach($arts as $art){
			RenderArticle($art);
		}
	}elseif(!isset($tree->Cats[$cat]) && count($tree->Cats) > 0){
		System::site()->AddTextBox('', '<center>В этой категории статей пока нет.</center>');
	}
}

function IndexArticlesRead(){
	global $tree;
	if(isset($_GET['art'])){
		$id = SafeEnv($_GET['art'], 11, int);
	}else{
		GO(GetSiteUrl().Ufu('index.php?name=articles', '{name}/'));
	}
	$where = "`id`='$id' and `active`='1'";
	System::database()->Select('articles', GetWhereByAccess('view', $where));
	if(System::database()->NumRows() == 0){
		GO(GetSiteUrl().Ufu('index.php?name=articles', '{name}/'));
	}
	$art = System::database()->FetchRow();
	System::database()->Update('articles', "hits='".(SafeEnv($art['hits'], 11, int) + 1)."'", $where);
	$cat = SafeDB($art['cat_id'], 11, int);

	// Показываем путь
	if(System::config('articles/show_catnav') == '1'){
		$tree->ShowPath($art['cat_id'], true, SafeDB($art['title'], 255, str));
	}
	System::site()->AddTemplatedBox('', 'module/article_read.html');
	System::site()->SetTitle(SafeDB($art['title'], 255, str));
	System::site()->SeoTitle = SafeDB($art['seo_title'], 255, str);
	System::site()->SeoKeyWords = SafeDB($art['seo_keywords'], 255, str);
	System::site()->SeoDescription = SafeDB($art['seo_description'], 255, str);
	RenderReadArticle($art);

	// Выводим комментарии
	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 11, int);
	}else{
		$page = 0;
	}
	$posts = new Posts('articles_comments', $art['allow_comments'] == '1');
	$posts->EditPageUrl = "index.php?name=articles&op=editpost&art=$id"; // Форма редактирования поста
	$posts->DeletePageUrl = "index.php?name=articles&op=deletepost&art=$id"; // Удаление поста
	$posts->PostFormAction = "index.php?name=articles&op=addpost&art=$id&cat=$cat"; // Добавление поста (сохранение)

	$posts->NavigationUrl = Ufu("index.php?name=articles&op=read&art=$id&cat=$cat", 'articles/{cat}/{art}/page{page}/', true);
	$posts->NavigationAnchor = '#comments';
	$posts->RenderPosts($id, 'article_comments', 'comments_navigation', false, $page);
	$posts->RenderForm(false, 'article_comments_form');
}

function IndexArticlesAddPost(){
	$get_id        = 'art'; // Имя параметра в get для получения id объекта
	$table         = 'articles_comments'; // Таблица комментариев
	$object_table  = 'articles'; // Таблица объектов
	$counter_field = 'comments_counter'; // Поле счетчик комментариев в таблице объекта
	$alloy_field   = 'allow_comments';     // Поле разрешить комментирии для этого объекта
	$id = SafeEnv($_GET[$get_id], 11, int);
	$cat = SafeDB($_GET['cat'], 11, int);
	$back_url = GetSiteUrl().Ufu("index.php?name=articles&op=read&art=$id&cat=$cat", 'articles/{cat}/{art}/');
	// -----------------------------------------------------
	System::database()->Select($object_table, "`id`='$id'");
	$obj = System::database()->FetchRow();
	$alloy_comments = $obj[$alloy_field] == '1';
	$posts = new Posts($table, $alloy_comments);
	if($posts->SavePost(SafeEnv($_GET[$get_id], 11, int), false)){
		$post_id = System::database()->GetLastId();
		// Увеличиваем счетчик комментариев объекта
		$counter = $obj[$counter_field] + 1;
		System::database()->Update($object_table, "`$counter_field`='$counter'", "`id`='$id'");
		// Генерируем обратную ссылку
		$parent_id = explode('_', $_POST['parent_id'], 2);
		$parent_id = SafeDB($parent_id[1], 11, int);
		$post_anchor = ($parent_id != 0 ? "#post_$parent_id" : '#post_'.$post_id);
		GO($back_url.$post_anchor);
	}else{
		System::site()->AddTextBox('Ошибка', $posts->PrintErrors());
	}
}

function IndexArticlesEditPost(){
	$table = 'articles_comments'; // Таблица комментариев
	$id = '&art='.SafeDB($_GET['art'], 11, int);
	$back = '&back='.SafeDB($_GET['back'], 255, str);
	$action = "index.php?name=articles&op=savepost$id$back";
	// -----------------------------------------------------
	System::site()->AddTemplatedBox('','edit_comment.html');
	$posts = new Posts($table);
	$posts->PostFormAction = $action;
	$posts->RenderForm(true, 'post_form');
}

function IndexArticlesEditPostSave(){
	$get_id = 'art';             // Имя параметра в get для получения id объекта
	$table = 'articles_comments'; // Таблица комментариев
	// -----------------------------------------------------
	$posts = new Posts($table);
	if($posts->SavePost(SafeEnv($_GET[$get_id], 11, int), true)){
		$post_anchor = "#post_".SafeDB($_GET['post_id'], 11, int);
		GoRefererUrl($_REQUEST['back'], $post_anchor);
	}else{
		$site->AddTextBox('Ошибка', $posts->PrintErrors());
		IndexArticlesEditPost();
	}
}

function IndexArticlesDeletePost(){
	$get_id = 'art'; // Имя параметра в get для получения id объекта
	$table = 'articles_comments'; // Таблица комментариев
	$object_table = 'articles'; // Таблица объектов
	$counter_field = 'comments_counter'; // Поле счетчик комментариев в таблице объекта
	$id = SafeDB($_GET[$get_id], 11, int);
	$back = '&back='.SafeDB($_GET['back'], 255, str);
	$delete_url = "index.php?name=articles&op=deletepost&art=$id$back"; // Ссылка страницы удаления
	$anchor = "#comments"; // Дополнительный анкор при возврате
	// -----------------------------------------------------
	$posts = new Posts($table);
	$posts->DeletePageUrl = $delete_url;
	$deleted_posts_count = $posts->DeletePost();
	if($deleted_posts_count > 0){
		$id = SafeEnv($_GET[$get_id], 11, int);
		System::database()->Select($object_table, "`id`='$id'");
		$obj = System::database()->FetchRow();
		$counter = $obj[$counter_field] - $deleted_posts_count;
		System::database()->Update($object_table, "`$counter_field`='$counter'", "`id`='$id'");
		GoRefererUrl($_REQUEST['back'], $anchor);
	}
}

function IndexArticlesAddVote(){
	$ip = getip();
	$time = time() - 86400; //1 день
	$article = SafeEnv($_GET['article'], 11, int);
	$vote = SafeEnv($_POST['vote'], 1, int);
	System::database()->Delete('articles_rating', "`time`<'$time'");
	System::site()->OtherMeta .= '<meta http-equiv="REFRESH" content="3; URL='.HistoryGetUrl(1).'">';
	System::database()->Select('articles', GetWhereByAccess('view', "`id`='$article' and `active`='1'"));
	if(System::database()->NumRows() > 0){
		$dfile = System::database()->FetchRow();
		if($dfile['allow_votes']=='1'){ // оценки разрешены
			System::database()->Select('articles_rating',"`ip`='$ip' and `downid`='$article'");
			if(System::database()->NumRows() > 0){
				System::site()->AddTextBox('','<center>Вы уже голосовали за эту статью.<br /><br /><a href="javascript:history.go(-1)">Назад</a></center>');
			}else{
				if($vote==0){
					System::site()->AddTextBox('','<center>Вы не выбрали оценку.<br /><br /><a href="javascript:history.go(-1)">Назад</a></center>');
				}else{
					System::user()->ChargePoints(System::config('points/article_rating'));
					$time = time();
					System::database()->Insert('articles_rating',"'','$article','$ip','$time'");
					$numvotes = SafeDB($dfile['num_votes'],11,int) + 1;
					$vote = SafeDB($dfile['all_votes'],11,int) + $vote;
					System::database()->Update('articles', "num_votes='$numvotes',all_votes='$vote'", "`id`='$article'");
					System::site()->AddTextBox('','<center>Спасибо за вашу оценку.<br><br><a href="javascript:history.go(-1)">Назад</a></center>');
				}
			}
		}else{
			System::site()->AddTextBox('','<center>Извините, оценка этой статьи запрещена.<br><br><a href="javascript:history.go(-1)">Назад</a></center>');
		}
	}else{
		System::site()->AddTextBox('','<center>Произошла ошибка. Статья не найдена.<br><br><a href="javascript:history.go(-1)">Назад</a></center>');
	}
}
