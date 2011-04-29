<?php

# LinkorCMS
# © 2006-2009 Галицкий Александр Николаевич (linkorcms@yandex.ru)
# Файл: admin.php
# Назначение: Главная страница АДМИН-панели

define('ADMIN_SCRIPT', true);
define('VALID_RUN', true);

include_once('config/init.php'); // Конфигурация и инициализация
define('ADMIN_FILE', System::$config['admin_file']); // Ссылка на админ-панель

// Проверка пользователя
if(!($userAuth === 1 && $userAccess === 1 && isset($_COOKIE['admin']) && $user->AllowCookie('admin', true))){
	if(isset($_POST['admin_login'])){ // Проверка логина-пароля
		$admin_name = SafeEnv($_POST['admin_name'], 255, str);
		$admin_password = SafeEnv($_POST['admin_password'], 255, str);
		$a = $user->Login($admin_name, $admin_password, false, true);
		if($a === true && $user->SecondLoginAdmin){
			$user->SetAdminCookie($admin_name, $admin_password);
		}else{
			$user->UnsetCookie('admin');
			include_once $config['inc_dir'].'template.login.php';
			AdminShowLogin('Неверные логин или пароль'); // exit
		}
	}else{ // Форма авторизации
		include_once $config['inc_dir'].'template.login.php';
		AdminShowLogin(); // exit
	}
}

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
		GO(Ufu('index.php'));
	}
}
$db->Select('modules', "`enabled`='1' and `folder`='$ModuleName'");

// Установлен такой модуль?
if($db->NumRows() == 0){
	System::admin()->AddAdminMenu();
	System::admin()->AddTextBox('Админ панель - модуль не найден', '<div style="text-align: center;">Модуль "'.$exe.'" не найден!</div>');
	System::admin()->TEcho();
	exit;
}

// Проверка на доступ
if($user->CheckAccess2($ModuleName, $ModuleName)){
	AddTextBox('Ошибка', $config['general']['admin_accd']);
	return;
}

// Вспомогательные константы
define('MOD_DIR', $config['mod_dir'].$ModuleName.'/');
define('MOD_FILE', MOD_DIR.'admin.php');

// Шаблонизатор
include_once $config['inc_dir'].'admin_template.class.php';

// Подключаем модуль
if(is_file(MOD_FILE)){
	System::admin()->AddAdminMenu();
	require MOD_FILE;
}

// Вывод данных
System::admin()->TEcho();

?>