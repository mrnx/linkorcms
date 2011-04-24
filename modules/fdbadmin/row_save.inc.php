<?php

// Сохранение новой записи
// Сохранение изменений отредактированной записи

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$table_name = SafeEnv($_GET['name'], 255, str);

$columns = $db->GetTableColumns($table_name);
$values = ''; // Новые значения
foreach($columns as $col){
	$values .= ",'".SafeEnv($_POST[$col['name']], 0, str)."'";
}
$values = substr($values, 1);


if($action == 'insertsave'){ // Добавление записи
	$db->Insert($table_name, $values);
}elseif($action == 'editsave'){ // Редактирование
	$index = SafeEnv($_GET['index'], 255, int);
	$rows = $db->Select($table_name);
	$old_values = $rows[$index]; // Старые значения
	unset($rows);

	$where = '';
	foreach($old_values as $key=>$value){
		$where .= "`".$key."`='".SafeEnv($value, 0, str, false, true, false)."' and ";
	}
	$where = substr($where, 0, -4);

	$db->Update($table_name, $values, $where, true);
}

//GO($config['admin_file'].'?exe=fdbadmin&a=review&name='.$table_name);
GoRefererUrl($_GET['back']);
AddTextBox('Сообщение', 'Изменения сохранены.');

?>