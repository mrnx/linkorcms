<?php

// Вставка / Редактирование записи в таблице

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$table = SafeEnv($_GET['name'], 255, str);
$columns = $db->GetTableColumns($table);

$back = '';
if(isset($_REQUEST['back'])){
	$back = '&back='.SafeDB($_REQUEST['back'], 255, str);
}

$edit = false;
if($action == 'editfield'){
	$edit = true;
	$index = SafeEnv($_GET['index'], 255, int);
	$rows = $db->Select($table);
	$row = $rows[$index];
}

$i = 0;
foreach($columns as $col){
	if($edit){
		$val = htmlspecialchars($row[$columns[$i]['name']]);
		$cap = 'Сохранить';
		$title = 'Редактирование записи';
	}else{
		$val = '';
		$cap = 'Добавить';
		$title = 'Добавление записи';
	}
	if(strtolower($col['type']) != 'text'){
		FormRow('<font color="#0000FF">'.((isset($col['auto_increment']) && ($col['auto_increment'] == true)) ? '<u>'.$col['name'].'</u>' : $col['name']).'</font>'.'<br /><font color="#666666">'.$col['type'].(isset($col['length']) ? '('.$col['length'].')</font>' : '</font>'), $site->Edit($col['name'], $val, false, 'style="width: 400px;"'));
	}else{
		FormRow('<font color="#0000FF">'.$col['name'].'</font>', $site->TextArea($col['name'], $val, 'style="width: 400px; height: 200px;"'));
	}
	$i++;
}

AddCenterBox($title);
AddForm('<form action="'.ADMIN_FILE.'?exe=fdbadmin&a='.($edit ? 'editsave' : 'insertsave').'&name='.$table.($edit ? '&index='.$index : '').$back.'" method="post">', $site->Submit($cap));
AdminFdbAdminGenTableMenu($table);
