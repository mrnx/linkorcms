<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$name = SafeEnv($_GET['name'], 255, str);
$id = SafeEnv($_GET['collid'], 11, int);
$coll = System::database()->GetColl($name, $id);
$install = "System::database()->InsertColl('$name', Unserialize('".Serialize($coll)."'), ".($id - 1).");";
$install2 = "System::database()->EditColl('$name', $id, Unserialize('".Serialize($coll)."'));";

AddCenterBox('Информация для установки колонки таблицы');
FormRow('Установка', $site->TextArea('code', $install, 'style="width: 400px; height: 200px;"'));
FormRow('Редактирование', $site->TextArea('code', $install2, 'style="width: 400px; height: 200px;"'));
AddForm('', $site->Button('Назад', 'onclick="history.go(-1);"'));
