<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$site->SetTitle('Фотогалерея');

//категории
include_once ($config['inc_dir'].'tree_b.class.php');
$tree = new IndexTree('gallery_cats');
$tree->moduleName = 'gallery';
$tree->id_par_name = 'cat';
$tree->NumItemsCaption = '<center>Всего изображений в галерее: ';
$tree->TopCatName = 'Галерея';
$GalleryDir = $config['gallery']['gallery_dir'];
$ThumbsDir = $config['gallery']['thumbs_dir'];

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
		$tree->Catalog($cat, 'IndexGalleryGetNumItems');
		if($cat != 0){
			IndexGalleryShow($cat);
		}
		break;
	case 'view':
		IndexGalleryView();
		break;
	// Комментарии
	case 'addpost':
		$id = intval(SafeEnv($_GET['img'], 11, int));
		$cat = SafeDB($_GET['cat'], 11, int);
		CommentsAddPost($id, 'gallery_comments', 'gallery', 'com_counter', 'allow_comments', "index.php?name=gallery&op=view&img=$id&cat=$cat", 'gallery/{cat}/{img}/');
		break;
	case 'savepost':
		if(CommentsEditPostSave(SafeEnv($_GET['img'], 11, int), 'gallery_comments')){
			break;
		}
	case 'editpost':
		CommentsEditPost('gallery_comments', "index.php?name=gallery&op=savepost&img=".SafeDB($_GET['img'], 11, int).'&back='.SafeDB($_GET['back'], 255, str));
		break;
	case 'deletepost':
		$id = SafeEnv($_GET['img'], 11, int);
		CommentsDeletePost($id, 'gallery_comments', 'gallery', 'com_counter', "index.php?name=gallery&op=deletepost&img=$id&back=".SafeDB($_GET['back'], 255, str));
		break;
	// //
	default:
		HackOff();
}

function IndexGalleryGetNumItems(){
	System::database()->Select('gallery', GetWhereByAccess('view', "`show`='1'"));
	return System::database()->NumRows().'.</center>';
}

function RenderThumb( $title, $filename, $description, $comments, $link ){
	global $site, $ThumbsDir, $GalleryDir;
	$vars = array();
	$vars['title'] = $title;
	$vars['description'] = $description;
	$thumbfile = $ThumbsDir.$filename;
	$vars['thumb_src'] = $thumbfile;
	$vars['image_view'] = $link;
	if(is_file($GalleryDir.$filename)){
		$vars['size'] = FormatFileSize(filesize($GalleryDir.$filename));
		$asize = getimagesize($GalleryDir.$filename);
		$asize = $asize[0].'x'.$asize[1];
		$vars['asize'] = $asize;
	}
	$vars['lcomments'] = 'Комментариев';
	$vars['comments'] = $comments;
	$site->AddTableCell('gallery_images', true, $vars);
}

function RenderImageView( &$img, &$db_images, &$index ){
	global $site, $GalleryDir;

	$vars = array();
	$vars['title'] = SafeDB($img['title'], 255, str);
	$vars['image_view_full'] = $GalleryDir.SafeDB($img['file'], 255, str);
	$vars['image_src'] = $GalleryDir.SafeDB($img['file'], 255, str);
	$vars['ldescription'] = 'Описание';
	$vars['description'] = SafeDB($img['description'], 0, str, false, false);

	$vars['next'] = isset($db_images[$index + 1]);
	if($vars['next']){
		$nimg = $db_images[$index + 1];
		$vars['next_url'] = Ufu('index.php?name=gallery&op=view&img='.SafeDB($nimg['id'], 11, int).'&cat='.SafeDB($nimg['cat_id'], 11, int), 'gallery/{cat}/{img}/');
		$vars['next_title'] = SafeDB($nimg['title'], 255, str);
	}
	$vars['prev'] = isset($db_images[$index - 1]);
	if($vars['prev']){
		$nimg = $db_images[$index - 1];
		$vars['prev_url'] = Ufu('index.php?name=gallery&op=view&img='.SafeDB($nimg['id'], 11, int).'&cat='.SafeDB($nimg['cat_id'], 11, int), 'gallery/{cat}/{img}/');
		$vars['prev_title'] = SafeDB($nimg['title'], 255, str);
	}

	$site->AddBlock('gallery_image', true, false, 'img');
	$site->Blocks['gallery_image']['vars'] = $vars;
}

function IndexGalleryShow( $cat ){
	global $db, $config, $site, $tree;
	if($cat != 0){
		$site->SetTitle('Изображения в категории '.SafeDB($tree->IdCats[$cat]['title'], 255, str));
	}
	$thumbs_onrow = $config['gallery']['thumbs_onrow'];

	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 11, int);
	}else{
		$page = 1;
	}
	$images = $db->Select('gallery', GetWhereByAccess('view', "`cat_id`='$cat' and `show`='1'"));
	//SortArray($images, 'public', true);

	// Постраничная навигация
	$num = $config['gallery']['images_on_page'];
	$navigation = new Navigation($page);
	$nav_link = Ufu('index.php?name=gallery'.($cat != 0 ? '&cat='.$cat : ''), 'gallery/'.($cat != 0 ? '{cat}/' : '').'page{page}/', true);
	$navigation->FrendlyUrl = $config['general']['ufu'];
	$navigation->GenNavigationMenu($images, $num, $nav_link);

	if($db->NumRows() > 0){
		$site->AddTemplatedBox('', 'module/gallery_image.html');
		$site->AddTable('gallery_images', true, 'img', $thumbs_onrow);
		foreach($images as $img){
			$view_link = Ufu('index.php?name=gallery&op=view&img='.SafeDB($img['id'], 11, int).'&cat='.SafeDB($img['cat_id'], 11, int), 'gallery/{cat}/{img}/');
			RenderThumb(
				SafeDB($img['title'], 255, str),
				SafeDB($img['file'], 255, str),
				SafeDB($img['description'], 0, str),
				SafeDB($img['com_counter'], 11, int),
				$view_link);
		}
	}elseif(!isset($tree->Cats[$cat]) && count($tree->Cats) > 0){
		$site->AddTextBox('', '<center>В этой категории пока нет изображений.</center>');
	}
}

function IndexGalleryView(){
	global $db, $config, $site, $tree, $user;
	if(isset($_GET['img'])){
		$id = SafeEnv($_GET['img'],11,int);
	}else{
		GO(GetSiteUrl().Ufu('index.php?name=gallery', '{name}/'));
	}

	$cat = SafeEnv($_GET['cat'], 11, int);

	$db_images = $db->Select('gallery', GetWhereByAccess('view', "`cat_id`='$cat' and `show`='1'"));
	if($db->NumRows() == 0){
		GO(GetSiteUrl().Ufu('index.php?name=gallery', '{name}/'));
	}
	$images = array();
	foreach($db_images as $k=>$img){
		$images[$k] = $img['id'];
	}
	$index = array_search($id, $images);
	if($index !== false){
		$img = $db_images[$index];
	}else{
		GO(GetSiteUrl().Ufu('index.php?name=gallery', '{name}/'));
	}

	$tree->ShowPath($cat, true, SafeDB($img['title'], 255, str));
	$site->SetTitle(SafeDB($img['title'], 255, str));
	$site->AddTemplatedBox('', 'module/gallery_view.html');
	$db->Update('gallery', "hits='".($img['hits'] + 1)."'", "`id`='$id'");

	RenderImageView($img, $db_images, $index);

	// Выводим комментарии
	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 11, int);
	}else{
		$page = 0;
	}
	include_once($config['inc_dir'].'posts.class.php');
	$posts = new Posts('gallery_comments', $img['allow_comments'] == '1');
	$posts->EditPageUrl = 'index.php?name=gallery&op=editpost&img='.$id;
	$posts->DeletePageUrl = 'index.php?name=gallery&op=deletepost&img='.$id;
	$posts->PostFormAction = "index.php?name=gallery&op=addpost&img=$id&cat=$cat&page=$page";

	$posts->NavigationUrl = Ufu("index.php?name=gallery&op=view&img=$id&cat=$cat", 'gallery/{cat}/{img}/page{page}/', true);
	$posts->RenderPosts($id, 'gallery_comments', 'comments_navigation', false, $page);
	$posts->RenderForm(false, 'gallery_comments_form');
}
