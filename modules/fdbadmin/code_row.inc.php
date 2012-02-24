<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$table = SafeEnv($_GET['name'], 255, str);
$index = SafeEnv($_GET['index'], 11, int);

$rows = System::database()->Select($table);
$row = $rows[$index];

$row2 = array();
$columns = System::database()->GetTableColumns($table);
foreach($columns as $col){
	if(isset($col['auto_increment'])){
		$row2[] = '';
	}else{
		$row2[] = $row[$col['name']];
	}
}
$row2 = SafeEnv($row2, 0, str);
$code_vals = Values($row2);
$install_code = 'System::database()->Insert("'.$table.'","'.$code_vals.'");';

System::admin()->AddCenterBox('PHP код вставки');
System::admin()->FormRow('Установка', $site->TextArea('code', $install_code, 'style="width: 800px; height: 200px;"'));

System::admin()->AddForm('', $site->Button('Назад', 'onclick="history.go(-1);"'));

AdminFdbAdminGenTableMenu(SafeDB($table, 255, str));
