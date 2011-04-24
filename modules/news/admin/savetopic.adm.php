<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(!$user->CheckAccess2('news', 'edit_topics')){
	AddTextBox('Ошибка', 'Доступ запрещён!');
	return;
}

$NewsImagesDir = $config['news']['icons_dirs'];
$ThumbsDir = $NewsImagesDir.'thumbs/';
$error = false;
$file = LoadImage('up_photo', $NewsImagesDir, $ThumbsDir, $config['news']['thumb_max_width'], $config['news']['thumb_max_height'], SafeEnv(RealPath2($_POST['topic_image']), 255, str, true), $error);
if($error){
	AddTextBox('Ошибка', '<center>Неправильный формат файла. Можно загружать только изображения формата GIF, JPEG или PNG.<br /><a href="javascript:history.go(-1)">Назад</a></center>');
	return;
}

if($action == 'addtopic'){
	$values = Values('', SafeEnv($_POST['topic_name'], 255, str), SafeEnv($_POST['topic_description'], 255, str), $file, '0');
	$db->Insert('news_topics', $values);
}elseif($action == 'savetopic'){
	$id = SafeEnv($_GET['id'], 11, int);
	$db->Select('news_topics', "`id`='".$id."'");
	$topic = $db->FetchRow();
	$values = Values('', SafeEnv($_POST['topic_name'], 255, str), SafeEnv($_POST['topic_description'], 255, str), $file, SafeEnv($topic['counter'], 11, int));
	$db->Update('news_topics', $values, "`id`='".$id."'", true);
}

GO($config['admin_file'].'?exe=news&a=topics');

?>