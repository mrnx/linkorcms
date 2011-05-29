<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(!$user->CheckAccess2('news', 'news_edit')){
	AddTextBox('Ошибка', 'Доступ запрещён!');
	return;
}

$author = $user->Get('u_name');

#Получаем основные параметры
$topic_id = SafeEnv($_POST['topic_id'], 11, int);
$title = SafeEnv($_POST['title'], 255, str);

//Модуль SEO
$seo_title = SafeEnv($_POST['seo_title'], 255, str);
$seo_keywords = SafeEnv($_POST['seo_keywords'], 255, str);
$seo_description = SafeEnv($_POST['seo_description'], 255, str);
//

$allow_comments = EnToInt($_POST['acomments']);

$NewsImagesDir = RealPath2($config['news']['icons_dirs']);
$ThumbsDir = $NewsImagesDir.'thumbs/';
$error = false;
	$icon = LoadImage(
		'up_photo',
		$NewsImagesDir,
		$ThumbsDir,
		$config['news']['thumb_max_width'],
		$config['news']['thumb_max_height'],
		$_POST['icon'],
		$error
	);
if($error){
	AddTextBox('Ошибка', '<center>Неправильный формат файла. Можно загружать только изображения формата GIF, JPEG или PNG.<br /><a href="javascript:history.go(-1)">Назад</a></center>');
	return;
}

	$start_text = SafeEnv($_POST['shorttext'],0,str, false);
	$end_text = SafeEnv($_POST['continuation'],0,str, false);
	$auto_br = EnToInt($_POST['auto_br']);
	$view = ViewLevelToInt(SafeEnv($_POST['view'],15,str));
	$enabled = EnToInt($_POST['enabled']);

	$img_view = SafeEnv($_POST['img_view'],1,int);

	$comments_counter = 0;
	$hit_counter = 0;

	if($method=='edit'){
		$db->Select('news',"`id`='".SafeEnv($_GET['id'],11,int)."'");
		$news = $db->FetchRow();
		$author = SafeDB($news['author'],255,str);
		$comments_counter = SafeDB($news['comments_counter'],11,int);
		$hit_counter = SafeDB($news['hit_counter'],11,int);
		$date = SafeDB($news['date'],11,int);
		if($topic_id != $news['topic_id'] && $news['enabled'] == 1){
			CalcNewsCounter($news['topic_id'], false);
			CalcNewsCounter($topic_id, true);
		}

		if($enabled == 0 &&  $news['enabled'] == 1){
			CalcNewsCounter($topic_id, false);
		}elseif($enabled == 1 &&  $news['enabled'] == 0){
			CalcNewsCounter($topic_id, true);
		}
	}else{
		$date = time();
	}

	$vals = Values('',$title,$date,$author,$topic_id,
	$allow_comments,$icon,$start_text,$end_text,$auto_br,
	$comments_counter,$hit_counter,$view,$enabled, $img_view,
	$seo_title, $seo_keywords, $seo_description);

	if($method=='add'){
		$db->Insert('news',$vals);
		CalcNewsCounter($topic_id, true);
	}else{
		$db->Update('news', $vals, "`id`='".SafeEnv($_GET['id'],11,int)."'",true);
	}

$bcache = LmFileCache::Instance();
$bcache->Delete('block', 'news1');
$bcache->Delete('block', 'news2');
$bcache->Delete('block', 'news3');
$bcache->Delete('block', 'news4');

GoRefererUrl($_GET['back']);
AddTextBox('Сообщение', 'Изменения сохранены.');

?>