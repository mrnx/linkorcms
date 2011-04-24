<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

global $db_types, $site, $user;

if(extension_loaded('mysql')){
	if(!$user->isDef('db_type')){
		$selected = true;
	}else{
		$selected = ($user->Get('setup_type') == 'mysql_setup');
	}

	$site->DataAdd($db_types, 'mysql_setup', 'MySQL', $selected);
}

?>