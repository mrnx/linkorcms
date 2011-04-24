<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

global $setup_types, $site, $user;

if(!$user->isDef('setup_type')){
	$selected = false;
}else{
	$selected = ($user->Get('setup_type') == 'update12_13');
}
$site->DataAdd($setup_types, 'update12_13', 'Обновление 1.2->1.3', $selected);

?>