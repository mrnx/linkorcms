<?php

/*
 * LinkorCMS 1.4
 * © 2011 LinkorCMS Development Group
 */

define('MAIN_SCRIPT', true);
define('VALID_RUN', true);

require 'config/init.php'; // Конфигурация и инициализация

// ЧПУ
if($config['general']['ufu'] && isset($_GET['ufu'])){
	$_GET = UfuRewrite($_GET['ufu']);
}

// Закрыть сайт для пользователей
if($config['general']['private_site'] && $user->AccessLevel() != 1){
	include_once($config['inc_dir'].'template.login.php');
	AdminShowLogin('Сайт закрыт для пользователей');
}

// Получаем имя модуля
$ModuleName = '';
if(!isset($_GET['name'])){
	define('INDEX_PHP', true); // модуль на главной странице
	$ModuleName = SafeEnv($config['general']['site_module'], 255, str, false, false);
}else{
	define('INDEX_PHP', false);
	$ModuleName = SafeEnv($_GET['name'], 255, str);
}
System::database()->Select('modules', "`enabled`='1' and `folder`='$ModuleName'"); // Проверяем доступен ли данный модуль

// Установлен такой модуль?
if(System::database()->NumRows() == 0){
	System::site()->InitPage();
	System::site()->AddTextBox('Ошибка', '<center>Данная страница ('.SafeDB($ModuleName, 255, str).') не существует или не доступна в данный момент.</center>');
	System::site()->TEcho();
	exit;
}

// Проверка на доступ
$mod = System::database()->FetchRow();
if(!System::user()->AccessIsResolved($mod['view'], $userAccess)){
	System::site()->InitPage();
	System::site()->AddTextBox('Ошибка', '<center>Доступ запрещен.</center>');
	System::site()->TEcho();
	exit;
}

// Вспомогательные константы
define('MOD_DIR', $config['mod_dir'].$ModuleName.'/');
define('MOD_FILE', MOD_DIR.'index.php');
define('MOD_INIT', MOD_DIR.'init.php');
define('MOD_THEME', RealPath2(SafeDB($mod['theme'], 255, str)));

// Инициализация модуля
$valid_init = file_exists(MOD_INIT);
if($valid_init){
	include MOD_INIT;
	if(function_exists('mod_initialization')){
		mod_initialization();
	}
}

// Шаблонизатор
if(!$system['no_templates']){
	System::site()->InitPage();
	$initfile = System::site()->Root.'init.php';
	if(file_exists($initfile)){
		include $initfile;
	}
}

// Сообщения
if(!$system['no_messages']){
	include_once(System::config('inc_dir').'messages.inc.php');
}

// Подключаем модуль
require MOD_FILE;

// Сообщения внизу
if(!$system['no_messages']){
	BottomMessages();
}

// Вывод данных пользователю
if(!$system['no_echo']){
	System::site()->TEcho();
}

// Финализация модуля
if($valid_init){
	if(function_exists('mod_finalization')){
		mod_finalization();
	}
}
