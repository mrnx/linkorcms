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

$db = 0;
IncludeSystemPluginsGroup('database', 'layer');
if(!method_exists($db, 'Connect')){ // Боремся с кешированием раньше времени
	$pcache = LmFileCache::Instance();
	$pcache->Clear('system');
	IncludeSystemPluginsGroup('database', 'layer');
	unset($pcache);
}

if(method_exists($db, 'Connect')){
	$db->ErrorReporting = $config["db_errors"];
	$db->Prefix = $config['db_pref'];
	$db->Connect($config["db_host"], $config["db_user"], $config["db_pass"], $config["db_name"]);
	if(!$db->Connected){
		exit('<html>'."\n"
		.'<head>'."\n"
		.'	<title>'.CMS_NAME.' - !!!Ошибка!!!</title>'."\n"
		.'</head>'."\n"
		.'<body>'."\n"
		.'	<center>Проблемы с базой данных, проверьте настройки базы данных.</center>'."\n"
		.'</body>'."\n"
		.'</html>');
	}
}else{
	exit("<center><h2><span style=\"color: #FF0000;\">Ошибка! Данный тип базы данных не поддерживается!</span></h2></center>");
}

?>