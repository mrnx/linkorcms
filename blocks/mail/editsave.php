<?php

//Здесь нужно получить данные формы и сохранить их в переменную $block_config

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(isset($_POST['topic'])){
	$block_config = SafeEnv($_POST['topic'], 11, int);
}else{
	$block_config = 0;
}

$bcache = LmFileCache::Instance();
$bcache->Delete('block', 'mail');

?>