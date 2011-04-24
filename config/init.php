<?php

# LinkorCMS
# © 2006-2010 Галицкий Александр Николаевич (linkorcms@yandex.ru)
# Файл: init.php
# Назначение: Файл инициализации

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

// Засекаем время начала выполнения скрипта
$GLOBALS['script_start_time'] = microtime(true);

// Низкоуровневая конфигурация
require 'config/config.php';

// Отпечатки пальцев LinkorCMS
include_once('config/version.php');
if(isset($_GET['checklcsite'])){
	exit(CMS_VERSION_STR);
}

// Проверка версии интерпретатора
if(version_compare(phpversion(), '5.0.0', '<')){
	exit('<html>
		<head>
			<title>'.CMS_NAME.' - Ошибка!</title>
		</head>
		<body>
			<center><h2>'.CMS_NAME.': Требуется версия PHP >= 5.0.0.</h2>
				Вы используете PHP '.phpversion().'.</center>
		</body>
		</html>');
}

// Эмуляция register_globals = off;
if(ini_get('register_globals') == 1){
	foreach($GLOBALS as $key=>$value){
		if($key != 'GLOBALS' and $key != 'key' and $key != '_REQUEST' and $key != '_GET' and $key != '_POST' and $key != '_COOKIE' and $key != '_SESSION' and $key != '_FILES' and $key != '_ENV' and $key != '_SERVER'){
			unset($GLOBALS[$key]);
		}
	}
	unset($key);
}

// Эмуляция magic_quotes_gpc = off;
if(get_magic_quotes_gpc()){
	function hstripslashes( $var )
	{
		return (is_array($var) ? array_map('hstripslashes', $var) : stripslashes($var));
	}
	$_POST = array_map('hstripslashes', $_POST);
	$_GET = array_map('hstripslashes', $_GET);
}

// Буферизация вывода
ob_start();

// Глобальные переменные
$db = null;
$user = null;
$site = null;
$config = array();
$plug_config = array();
$system = array('no_templates'=>false, 'no_messages'=>false, 'no_echo'=>false, 'stop_hit'=>false);
$SiteLog = null;
$ErrorsLog = null;

if(is_file('config/db_config.php')){

	// Загружаем конфигурацию
	require 'config/name_config.php';
	require 'config/db_config.php';
	require 'config/salt.php';

	// Проверяем версию базы данных
	if(!defined('SETUP_SCRIPT') && substr($config['db_version'], 0, 3) != substr(CMS_VERSION, 0, 3)){
		exit('<html>
			<head>
				<title>'.CMS_NAME.' - Ошибка!</title>
			</head>
			<body>
				<center><h2>'.CMS_NAME.': Требуется обновление базы данных.</h2></center>
			</body>
			</html>');
	}

	// Обработка ошибок
	include_once($config['inc_dir'].'error_handler.php');

	// Логи
	include_once ($config['inc_dir'].'logi.class.php');
	$SiteLog = new Logi($config['log_dir'].'site.log.php');
	$ErrorsLog = new Logi($config['log_dir'].'errors.log.php');

}elseif(!defined('SETUP_SCRIPT')){
	// Установка
	Header("Location: setup.php");
	exit();
}

?>