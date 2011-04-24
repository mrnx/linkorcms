<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$name = SafeEnv($_GET['name'], 255, str);
$onid = SafeEnv($_POST['toindex'], 11, int);
$text = '';
AdminFdbAdminInitCollForm($text, 'addcoll&to='.$name.'&onid='.$onid);
AdminFdbAdminCollForm($text, 1);
AdminFdbAdminCloseCollForm($text, 1, 'Добавить');
AddTextBox('Форма добавления колонки в таблицу "'.$name.'"', $text);

?>