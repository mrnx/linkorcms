<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$name = SafeEnv($_GET['name'], 255, str);
$id = SafeEnv($_GET['collid'], 11, int);
$db->DeleteColl($name, $id);

//GO($config['admin_file'].'?exe=fdbadmin&a=structure&name='.$name);
GoBack();

?>