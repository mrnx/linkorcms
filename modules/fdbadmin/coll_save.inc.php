<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$col = array();
$name = SafeEnv($_GET['to'], 255, str);
$after = SafeEnv($_GET['onid'], 11, int);

$col['name'] = SafeEnv($_POST['name0'], 250, str);
$col['type'] = SafeEnv($_POST['type0'], 250, str);

if($_POST['length0'] != ''){
	$col['length'] = SafeEnv($_POST['length0'], 11, int);
}
if($_POST['default0'] != ''){
	$col['default'] = $_POST['default0'];
}
if($_POST['atributes0'] != 'none'){
	$col['atributes'] = $_POST['atributes0'];
}
if(!isset($_POST['notnull0'])){
	$col['notnull'] = true;
}
if(isset($_POST['auto_increment0'])){
	$col['auto_increment'] = true;
}

switch($_POST['params0']){
	case "primary":
		$col['primary'] = true;
		break;
	case "index":
		$col['index'] = true;
		break;
	case "unique":
		$col['unique'] = true;
		break;
}

if(isset($_POST['fulltext0'])){
	$col['fulltext'] = true;
}

$db->InsertColl($name, $col, $after);
GO(ADMIN_FILE.'?exe=fdbadmin&a=structure&name='.$name);

?>