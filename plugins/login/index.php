<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

//Плагин для аутентификации на сайте

if(!isset($_GET['a'])){
	GO(Ufu('index.php'));
}

if($_GET['a'] == 'login'){
	if(!isset($_POST['login_form']) || !isset($_POST['login']) || !isset($_POST['pass'])){
		GO(Ufu('index.php'));
	}
	$r = $user->Login(SafeEnv($_POST['login'], 30, str), SafeEnv($_POST['pass'], 32, str), isset($_POST['remember']));
	if($r === true){
		if(strpos($_SERVER['HTTP_REFERER'], 'index.php?name=user&op=registration') === false &&
			strpos($_SERVER['HTTP_REFERER'], 'user/registration/') === false){
			GoBack();
		}else{ // Логин сразу после регистрации
			GO(Ufu('index.php'));
		}
	}else{
		$SiteLog->Write('Plugin:login  Неверный вход. Логин: '.SafeEnv($_POST['login'], 30, str).', Пароль: '.SafeEnv($_POST['pass'], 32, str));
		include_once($config['inc_dir'].'index_template.class.php');
		$site->Login($r);
		$site->TEcho();
	}
}elseif($_GET['a'] == 'exit' && $user->Auth == '1'){
	$user->UnLogin(false);
	GoBack();
}
