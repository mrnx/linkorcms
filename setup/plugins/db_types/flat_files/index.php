<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

global $db_types, $site, $user;

if(!$user->isDef('db_type')){
	$selected = false;
}else{
	$selected = ($user->Get('setup_type') == 'flatfilesdb_setup');
}

$site->DataAdd($db_types, 'flatfilesdb_setup', 'FlatFiles (текстовые файлы)', $selected);

?>