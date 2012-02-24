<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$table = SafeEnv($_GET['name'], 255, str);
$index = SafeEnv($_GET['index'], 255, int);

$rows = $db->Select($table);
$row = $rows[$index];

$columns = $db->GetTableColumns($table);
$names = array();
foreach($columns as $col){
	$names[$col['name']] = $row[$col['name']];
}
$sql = '';
foreach($row as $key=>$value){
	if(isset($names[$key]))
		$sql.= "`".$key."`='".$db->EscapeString($value)."' and ";
}
$sql = substr($sql, 0, strlen($sql) - 4);
$db->Delete($table, $sql);

if(isset($_REQUEST['back'])){
	GoRefererUrl($_REQUEST['back']);
}else{
	GO(ADMIN_FILE.'?exe=fdbadmin&a=review&name='.SafeDB($_GET['name'], 255, str));
}
