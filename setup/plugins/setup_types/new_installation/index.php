<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

global $setup_types, $site, $user;

if(!$user->isDef('setup_type')){
	$selected = true;
}else{
	$selected = ($user->Get('setup_type') == 'install');
}

$site->DataAdd($setup_types, 'install', 'Новая установка', $selected);

?>