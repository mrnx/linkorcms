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

$db = 0;
include ($config['config_dir'].'db_config.php');
IncludeSystemPluginsGroup('database', 'layer');

if(method_exists($db, 'Connect')){
	$db->ErrorReporting = $config["db_errors"];
	$db->Prefix = $config['db_pref'];
	$db->Connect($config["db_host"], $config["db_user"], $config["db_pass"], $config["db_name"]);
	if(!$db->Connected){
		exit("<html>\n<head>\n\t<title>!!!Ошибка!!!</title>\n</head>\n<body>\n<center>Проблемы с базой данных, проверьте настройки базы данных.</center>\n</body>\n</html>");
	}
}else{
	exit("<center><h2><font color=\"#FF0000\">Ошибка! Данный тип базы данных не поддерживается!</font></h2></center>");
}

?>