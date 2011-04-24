<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$mod_text = '';
if($a == 'edit'){
	$mod_text = $block_config;
}

$title = 'Блок Опросы';

?>