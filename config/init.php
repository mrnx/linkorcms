<?php

// LinkorCMS
// © 2006-2011 Александр Галицкий (linkorcms@yandex.ru)
// Файл: init.php
// Назначение: Файл инициализации

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if($_SERVER['REQUEST_METHOD'] == "HEAD"){ // Отсеиваем HEAD запросы
	header("X-Request: HEAD");
	exit();
}

define('INIT_CORE_START', microtime(true));

@ini_set('error_reporting', E_ALL | E_STRICT); // Всегда максимальный уровень
@error_reporting(E_ALL | E_STRICT);

@ini_set('html_errors', true);
@ini_set('display_errors', true); // Включаем вывод ошибок

@ini_set('log_errors', false); // Лог ошибок пока отключаем
@ini_set('ignore_repeated_errors', true);

umask(0); // По умолчанию файлы будут создаваться с правами 0666, папки с правами 0777

// Засекаем время начала выполнения скрипта
define('SCRIPT_START_TIME', microtime(true));

// Низкоуровневая конфигурация (конфигурационные константы)
require 'config/config.php';
setlocale(LC_ALL, LOCALE);

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

// Эмуляция register_globals = off ////////////////////////////////////////////////////////////////
if(ini_get('register_globals') == 1){
	foreach($GLOBALS as $key=>$value){
		if($key != 'GLOBALS'
		   and $key != 'key'
		   and $key != '_REQUEST'
		   and $key != '_GET'
		   and $key != '_POST'
		   and $key != '_COOKIE'
		   and $key != '_SESSION'
		   and $key != '_FILES'
		   and $key != '_ENV'
		   and $key != '_SERVER')
		{
			unset($GLOBALS[$key]);
		}
	}
	unset($key);
}

// Эмуляция magic_quotes_gpc = off
if(get_magic_quotes_gpc()){
	function hstripslashes( $var ){
		return (is_array($var) ? array_map('hstripslashes', $var) : stripslashes($var));
	}
	$_POST = array_map('hstripslashes', $_POST);
	$_GET = array_map('hstripslashes', $_GET);
	$_REQUEST = array_map('hstripslashes', $_REQUEST);
}

// Буферизация вывода /////////////////////////////////////////////////////////////////////////////
ob_start();

// Глобальные переменные
$db = null;
$user = null;
$site = null;
$config = array();
$plug_config = array();
$system = array('no_templates'=>false, 'no_messages'=>false, 'no_echo'=>false);
$SiteLog = null;
$ErrorsLog = null;
$SITE_ERRORS = true;
$userAuth = false;
$userAccess = 4;
$system_autoload = array();
$system_modules = array();
define('system_cache', 'system'); // Имя группы системного кэша

require 'config/name_config.php'; // Конфигурация расположений
require 'config/autoload.php'; // Классы для автозагрузки

// Подключение ядра
if(LOAD_SYSTEM_APART){ // Подключать каждый файл по отдельности
	$system_dir = $GLOBALS['config']['inc_dir'].'system/';
	foreach($system_modules as $system_file){
		require $system_dir.$system_file;
	}
}else{ // Сборка ядра
	if(!is_file('config/system_build.php') || FORCE_BUILD_SYSTEM){
		$inc_dir = $GLOBALS['config']['inc_dir'];
		$core_dir = $inc_dir.'system/';
		$core_build = '';
		foreach($system_modules as $core_file){
			$core_php = trim(file_get_contents($core_dir.$core_file));
			if(substr($core_php, 0, 5) == '<'.'?php') $core_php = substr($core_php, 5);
			if(substr($core_php, -2) == '?'.'>') $core_php = substr($core_php, 0, -2);
			$core_build .= $core_php;
		}
		if(BUILD_SYSTEM_WITH_CLASSES){
			foreach($GLOBALS['system_autoload'] as $class_file){
				$class_php = trim(file_get_contents($class_file));
				if(substr($class_php, 0, 5) == '<'.'?php') $class_php = substr($class_php, 5);
				if(substr($class_php, -2) == '?'.'>') $class_php = substr($class_php, 0, -2);
				$core_build .= $class_php;
			}
		}
		file_put_contents('config/system_build.php', '<'.'?php'.$core_build);
	}
	require 'config/system_build.php';
}

// Отпечатки пальцев LinkorCMS
if(isset($_GET['checklcsite'])){
	exit(CMS_VERSION_STR);
}

// Подключение системных плагинов
$plugins = SystemPluginsIncludeGroup('system', '', true);
foreach($plugins as $plugin){
	include($plugin.'index.php');
}

// Обработка ошибок
@ini_set('display_errors', System::config('debug/php_errors')); // Не влияет на error_handler, выводит фатальные ошибки, если включено
@ini_set('error_log', dirname(__FILE__).'/'.$config['log_dir'].'errors.log'); // Записывает все ошибки включая фатальные
@ini_set('log_errors', System::config('debug/log_errors'));
set_error_handler('ErrorHandler'); // Обработчик ошибок, все ошибки кроме фатальных

// Логи
$SiteLog = new Logi($config['log_dir'].'site.log'); // Лог для отладочных сообщений
$ErrorsLog = new Logi($config['log_dir'].'errors.log'); // Лог для вывода ошибок

// Сессии
$user = new User();

if(is_file('config/db_config.php')){ // Система установлена

	// Загружаем конфигурацию
	require 'config/db_config.php';
	require 'config/salt.php';

	// Блокируем инсталлятор, если он не заблокирован
	if(!is_file('config/setup_lock.php') && !defined('SETUP_SCRIPT')){
		file_put_contents('config/setup_lock.php', "\n");
	}

	// Проверяем версию базы данных
	if(substr($config['db_version'], 0, 3) != substr(CMS_VERSION, 0, 3) && !defined('SETUP_SCRIPT')){
		exit('<html><head><title>Ошибка</title></head><body><center><h2>Требуется обновление базы данных.</h2></center></body></html>');
	}

	// Подключение к базе данных
	define("DATABASE", true);
	SystemPluginsIncludeGroup('database'); // Подключение драйвера базы данных
	if(method_exists($db, 'Connect')){
		$db->ErrorReporting = $config['db_errors'];
		$db->Prefix = $config['db_pref'];
		$db->Connect($config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name']);
		if(!$db->Connected){
			exit('<html><head><title>Ошибка</title></head><body><center>Проблемы с базой данных, проверьте настройки базы данных.</center></body></html>');
		}
	}else{
		exit('<html><head><title>Ошибка</title></head><body><center>Проблема с подключением драйвера базы данных.</center></body></html>');
	}
	if($db->DbSelected){
		// Загрузка конфигурации сайта
		LoadSiteConfig($config);
		LoadSiteConfig($plug_config, 'plugins_config', 'plugins_config_groups');

		// Автообновление
		include('config/autoupdate.php');
		if($updated){ // Очищаем весь кэш
			$cache = LmFileCache::Instance();
			$groups = $cache->GetGroups();
			foreach($groups as $g){
				$cache->Clear($g);
			}
		}

		// Устанавливаем временную зону сайта по умолчанию
		$user->CheckCookies();
		$user->AccessInit($user->AccessGroup());
		if($user->Auth && $user->Get('u_timezone')){
			@date_default_timezone_set($user->Get('u_timezone'));
		}else{
			@date_default_timezone_set(System::config('general/default_timezone'));
		}
		$userAuth = IntVal($user->Get('u_auth'));
		$userAccess = IntVal($user->Get('u_level'));

		// Подключаем плагины(PLUG_AUTORUN, PLUG_ADMIN_AUTORUN, PLUG_MAIN_AUTORUN)
		if(defined('MAIN_SCRIPT') || defined('ADMIN_SCRIPT')){
			$pcache = LmFileCache::Instance();
			if(defined('MAIN_SCRIPT')){
				$pcache_name = 'plugins_auto_main';
			}elseif(defined('ADMIN_SCRIPT')){
				$pcache_name = 'plugins_auto_admin';
			}
			if($pcache->HasCache('system', $pcache_name)){
				$plugins = $pcache->Get('system', $pcache_name);
			}else{
				if(defined('MAIN_SCRIPT')){
					$q = "(`type`='1' or `type`='3') and `enabled`='1'";
				}elseif(defined('ADMIN_SCRIPT')){
					$q = "(`type`='1' or `type`='2') and `enabled`='1'";
				}
				$plugins = $db->Select('plugins', $q);
				$pcache->Write('system', $pcache_name, $plugins);
			}
			foreach($plugins as $plugin){
				$PluginName = RealPath2($config['plug_dir'].$plugin['name'].'/index.php');
				if(is_file($PluginName)){
					include $PluginName;
				}else{
					UninstallPlugin($plugin['name']);
				}
			}
		}
	}
}elseif(!defined('SETUP_SCRIPT')){ // Система не установлена
	Header("Location: setup.php");
	exit();
}

define('INIT_CORE_END', microtime(true));
