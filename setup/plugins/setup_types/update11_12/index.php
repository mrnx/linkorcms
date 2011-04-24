<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

global $setup_types, $site, $user;

if(!$user->isDef('setup_type')){
	$selected = false;
}else{
	$selected = ($user->Get('setup_type') == 'update11_12');
}
$site->DataAdd($setup_types, 'update11_12', 'Обновление 1.1->1.2', $selected);
?>