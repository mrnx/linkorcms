<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(!$user->CheckAccess2('news', 'news_edit')){
	AddTextBox('Ошибка', 'Доступ запрещён!');
	return;
}

$status = 0;
$topic_id = -1;
$auth_id = -1;
$menuurl = GenMenuUrl($status, $topic_id, $auth_id);
$db->Select('news', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
$news = $db->FetchRow();
if(SafeDB($news['enabled'], 1, int) == 1){
	$en = '0';
	CalcNewsCounter(SafeDB($news['topic_id'], 11, int), false);
}else{
	$en = '1';
	CalcNewsCounter(SafeDB($news['topic_id'], 11, int), true);
}
$db->Update('news', "enabled='$en'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
$par = SafeEnv($_GET['pv'], 10, str);
switch($par){
	case 'main':
		if(isset($_GET['page'])){
			$link = '&page='.SafeEnv($_GET['page'], 11, int);
		}else{
			$link = '';
		}
		break;
	case 'readfull':
		$link = '&a=readfull&id='.SafeEnv($_GET['id'], 11, int);
		break;
	default:
		$link = '';
}

$bcache = LmFileCache::Instance();
$bcache->Delete('block', 'news1');
$bcache->Delete('block', 'news2');
$bcache->Delete('block', 'news3');
$bcache->Delete('block', 'news4');

GO($config['admin_file'].'?exe=news'.$link.$menuurl);

?>