<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

global $db;

if($config['db_type'] == 'MySQL'){
	include_once ($include_plugin_path.'mysql.layer.php');
	$db = new LcDatabaseMySQL();
}

?>