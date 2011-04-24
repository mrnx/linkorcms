<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(!$user->CheckAccess2('news', 'news_edit')){
	AddTextBox('Ошибка', 'Доступ запрещён!');
	return;
}

if(isset($_GET['ok']) && SafeEnv($_GET['ok'], 1, int) == '1'){
	$id = 0;
	$id = SafeEnv($_GET['id'], 11, int);
	$r = $db->Select('news', "`id`='$id'");
	$db->Delete('news', "`id`='$id'");
	$db->Delete('news_comments', "`object_id`='$id'");
	if($r[0]['enabled'] == '1'){
		CalcNewsCounter(SafeDB($r[0]['topic_id'], 11, int), false);
	}

	$bcache = LmFileCache::Instance();
	$bcache->Delete('block', 'news1');
	$bcache->Delete('block', 'news2');
	$bcache->Delete('block', 'news3');
	$bcache->Delete('block', 'news4');

	//GO($config['admin_file'].'?exe=news');
	GoRefererUrl($_GET['back']);
	AddTextBox('Сообщение', 'Новость удалена.');
}else{
	$r = $db->Select('news', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	$text = 'Вы действительно хотите удалить новость "'.SafeDB($r[0]['title'], 255, str).'"<br />'
		.'<a href="'.$config['admin_file'].'?exe=news&a=delnews&id='.SafeEnv($_GET['id'], 11, int).'&back='.SaveRefererUrl().'&ok=1">Да</a>'
		.' &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">Нет</a>';
	AddTextBox("Предупреждение", $text);
}

?>