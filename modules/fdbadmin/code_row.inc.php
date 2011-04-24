<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$table = SafeEnv($_GET['name'], 255, str);
$index = SafeEnv($_GET['index'], 11, int);

$rows = $db->Select($table);
$row = $rows[$index];

$row2 = array();
$columns = $db->GetTableColumns($table);
foreach($columns as $col){
	if(isset($col['auto_increment'])){
		$row2[] = '';
	}else{
		$row2[] = $row[$col['name']];
	}
}
$row2 = SafeEnv($row2, 0, str);
$code_vals = Values($row2);
$install_code = '$db->Insert("'.$table.'","'.$code_vals.'");';

AddCenterBox('PHP код вставки');
FormRow('Установка', $site->TextArea('code', $install_code, 'style="width: 400px; height: 200px;"'));

AddForm('', $site->Button('Назад', 'onclick="history.go(-1);"'));

?>