<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$bconf['topic'] = SafeEnv($_POST['topic'], 11, int);
$bconf['count'] = SafeEnv($_POST['count'], 11, int);
$block_config = serialize($bconf);

$bcache = LmFileCache::Instance();
$bcache->Delete('block', 'news1');
$bcache->Delete('block', 'news2');
$bcache->Delete('block', 'news3');
$bcache->Delete('block', 'news4');

?>