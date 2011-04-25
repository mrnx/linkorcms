<?php

# LinkorCMS
# © 2006 Галицкий Александр Николаевич (linkorcms@yandex.ru)
# Файл: database.php
# Назначение: Подключает и настраивает классы для управления базами данных

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

global $db;

if(!defined("DATABASE")){
	define("DATABASE", true);
}

$db = null;
IncludeSystemPluginsGroup('database', 'layer');
if(method_exists($db, 'Connect')){
	$db->ErrorReporting = $config["db_errors"];
	$db->Prefix = $config['db_pref'];
	$db->Connect($config["db_host"], $config["db_user"], $config["db_pass"], $config["db_name"]);
	if(!$db->Connected){
		exit("<html><head><title>Ошибка</title></head><body><center>Проблемы с базой данных, проверьте настройки базы данных.</center></body></html>");
	}
}else{
	exit("<html><head><title>Ошибка</title></head><body><center>Проблема с подключением драйвера базы данных.</center></body></html>");
}

?>