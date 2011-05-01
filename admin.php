<?php

# LinkorCMS
# © 2006-2009 Галицкий Александр Николаевич (linkorcms@yandex.ru)
# Файл: admin.php
# Назначение: Главная страница АДМИН-панели

define('ADMIN_SCRIPT', true);
define('VALID_RUN', true);

require 'config/init.php'; // Конфигурация и инициализация
define('ADMIN_FILE', System::$config['admin_file']); // Ссылка на админ-панель

// Шаблонизатор
include_once $config['inc_dir'].'admin_template.class.php';
$site = new AdminPage();

// Проверка пользователя
if(!($userAuth === 1 && $userAccess === 1 && isset($_COOKIE['admin']) && System::user()->AllowCookie('admin', true))){
	if(isset($_POST['admin_login'])){ // Проверка логина-пароля
		$admin_name = SafeEnv($_POST['admin_name'], 255, str);
		$admin_password = SafeEnv($_POST['admin_password'], 255, str);
		$a = System::user()->Login($admin_name, $admin_password, false, true);
		if($a === true && System::user()->SecondLoginAdmin){
			System::user()->SetAdminCookie($admin_name, $admin_password);
		}else{
			System::user()->UnsetCookie('admin');
			System::admin()->Login('Неверные логин или пароль'); // exit
		}
	}else{ // Форма авторизации
		System::admin()->Login(); // exit
	}
}

// Проверка присутствует ли setup.php на сервере
if(is_file('setup.php') && !is_file('dev.php')){
	exit('<html>'."\n".'<head>'."\n".'	<title>'.CMS_NAME.' - !!!Ошибка!!!</title>'."\n".'</head>'."\n".'<body>'."\n".'	<center><h2>Удалите setup.php с сервера.</h2>
		<br />
		Админ панель заблокирована.
		<br />
		Присутствие <b>setup.php</b> на сервере делает сайт<br />
		уязвимым, поэтому, перед тем как начать работу,<br />
		рекомендуется его <strong>удалить</strong>.</center>'."\n".'</body>'."\n".'</html>');
}

System::admin()->InitPage();

// Получаем имя модуля
$ModuleName = '';
if(!isset($_GET['exe'])){
	define('INDEX_PHP', true); // модуль на главной странице
	$ModuleName = 'adminpanel';
}else{
	define('INDEX_PHP', false);
	$ModuleName = SafeEnv($_GET['exe'], 255, str);
	if($ModuleName == 'exit'){ // Выход
		$user->UnsetCookie('admin');
		GO(Ufu('index.php')); // exit
	}
}
System::db()->Select('modules', "`enabled`='1' and `folder`='$ModuleName'");

// Установлен такой модуль?
if(System::db()->NumRows() == 0){
	System::admin()->AddAdminMenu();
	System::admin()->AddTextBox('Админ панель - модуль не найден', '<div style="text-align: center;">Модуль "'.$ModuleName.'" не найден!</div>');
	System::admin()->TEcho();
	exit;
}

// Проверка на доступ
if(!System::user()->CheckAccess2($ModuleName, $ModuleName)){
	System::admin()->AddTextBox('Ошибка', $ModuleName.' Доступ закрыт!');
	System::admin()->TEcho();
	exit;
}

// Вспомогательные константы
define('MOD_DIR', System::$config['mod_dir'].$ModuleName.'/');
define('MOD_FILE', MOD_DIR.'admin.php');

// Подключаем модуль
if(is_file(MOD_FILE)){
	System::admin()->AddAdminMenu();
	require MOD_FILE;
}

// Вывод данных
System::admin()->TEcho();

?>