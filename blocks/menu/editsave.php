<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$bcache = LmFileCache::Instance();
$bcache->Delete('block', 'menu1');
$bcache->Delete('block', 'menu2');
$bcache->Delete('block', 'menu3');
$bcache->Delete('block', 'menu4');

?>