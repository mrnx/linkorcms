<?php

/*
 * LinkorCMS 1.3.5
 * © 2011 Александр Галицкий (linkorcms@yandex.ru)
 * and LinkorCMS Development Group
 *
 */

define('MAIN_SCRIPT', true);
define('VALID_RUN', true);

include_once('config/init.php'); // Конфигурация и инициализация


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
$db->Select('modules', "`enabled`='1' and `folder`='$ModuleName'"); // Проверяем доступен ли данный модуль

if($db->NumRows() > 0){
	$mod = $db->FetchRow();
	if($user->AccessIsResolved($mod['view'], $userAccess)){
		define('MOD_DIR', $config['mod_dir'].$ModuleName.'/');
		define('MOD_FILE', MOD_DIR.'index.php');
		define('MOD_INIT', MOD_DIR.'init.php');
		define('MOD_THEME', RealPath2(SafeDB($mod['theme'], 255, str)));
		$valid_init = file_exists(MOD_INIT);
		// Инициализация модуля
		if($valid_init){
			include MOD_INIT;
			if(function_exists('mod_initialization')){
				mod_initialization();
			}
		}
		// Шаблонизатор
		if(!$system['no_templates']){
			include_once($config['inc_dir'].'index_template.inc.php');
		}
		// Сообщения
		if(!$system['no_messages']){
			include_once($config['inc_dir'].'messages.inc.php');
		}
		// Модуль
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
	}else{
		include $config['inc_dir'].'index_template.inc.php';
		System::site()->AddTextBox('Ошибка', '<center>Доступ запрещен.</center>');
		System::site()->TEcho();
	}
}else{
	include $config['inc_dir'].'index_template.inc.php';
	System::site()->AddTextBox('Ошибка', '<center>Данная страница ('.SafeDB($ModuleName, 255, str).') не существует или не доступна в данный момент.</center>');
	System::site()->TEcho();
}

?>