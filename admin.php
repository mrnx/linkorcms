<?php

# LinkorCMS
# © 2006-2009 Галицкий Александр Николаевич (linkorcms@yandex.ru)
# Файл: admin.php
# Назначение: Главная страница АДМИН-панели

define('ADMIN_SCRIPT', true);
define('VALID_RUN', true);

include_once('config/init.php'); // Конфигурация и инициализация

// Проверка пользователя
if($userAuth === 1 && $userAccess === 1 && isset($_COOKIE['admin']) && $user->AllowCookie('admin', true)){ // Пользователь авторизован в админ-панели
	if(isset($_GET['exe'])){
		$exe = SafeEnv($_GET['exe'], 255, str);
	}else{
		$exe = 'adminpanel';
	}

	if($exe == 'exit'){ // Выход
		$user->UnsetCookie('admin');
		GO(Ufu('index.php'));
	}

	// Вспомогательные константы
	define('MOD_DIR', $config['mod_dir'].$exe.'/');
	define('MOD_FILE', MOD_DIR.'admin.php');
	define('ADMIN_FILE', System::$config['admin_file']); // Ссылка на админ-панель
	define('ADMIN_AJAX', IsAjax()); // Говорит скрипту, что данные запрошены c помощью ajax
	define('ADMIN_AJAX_LINKS', System::$config['admin_panel']['enable_ajax'] ? 'true' : 'false'); // Говорит скрипту что админ-панель работает в режиме AJAX

	include_once $config['inc_dir'].'admin_template.class.php';

	$db->Select('modules', "`enabled`='1' and `folder`='$exe'");
	if($db->NumRows() > 0){
		// Подключаем модуль
		if(is_file(MOD_FILE)){
			System::admin()->AddAdminMenu();
			include MOD_FILE;
		}
		// Вывод данных
		System::admin()->TEcho();
	}else{
		System::admin()->AddAdminMenu();
		AddTextBox('Админ панель - модуль не найден', '<div style="text-align: center;">Модуль "'.$exe.'" не найден!</div>');
		System::admin()->TEcho();
	}
}else{
	if(isset($_POST['admin_login'])){ // Проверка логина-пароля
		$admin_name = SafeEnv($_POST['admin_name'], 255, str);
		$admin_password = SafeEnv($_POST['admin_password'], 255, str);
		$a = $user->Login($admin_name, $admin_password, false, true);
		if($a === true && $user->SecondLoginAdmin){
			$user->SetAdminCookie($admin_name, $admin_password);
			GoRefererUrl($_GET['_back']);
		}else{
			$user->UnsetCookie('admin');
			include_once $config['inc_dir'].'template.login.php';
			AdminShowLogin('Неверные логин или пароль');
		}
	}else{ // Форма авторизации
		include_once $config['inc_dir'].'template.login.php';
		AdminShowLogin();
	}
}

?>