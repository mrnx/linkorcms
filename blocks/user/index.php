<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

// Гость
if(!$user->Auth){
	$tempvars['content'] = 'block/content/user_form.html';
	$vars['title'] = 'Вход';
	$vars['form_action'] = 'index.php?name=plugins&p=login&a=login&back=main';
	$vars['registration'] = $config['user']['registration'] == 'on';
	$vars['registration_url'] = Ufu('index.php?name=user&op=registration', 'user/{op}/');
	$vars['llogin'] = 'Логин';
	$vars['lpass'] = 'Пароль';
	$vars['lregistration'] = 'Регистрация';
	$vars['lremember'] = 'Запомнить меня';
	$vars['lsubmit'] = 'Вход';
	$vars['title'] = 'Вход';
}elseif($user->Auth){// Пользователь
	$tempvars['content'] = 'block/content/user.html';
	$vars['lhello'] = 'Здравствуйте';
	$vars['user_name'] = $user->Get('u_name');
	$vars['user_avatar_url'] = $user->Get('u_avatar');
	$vars['user_info_url'] = Ufu('index.php?name=user&op=userinfo', 'user/{op}/');
	$vars['luser_info'] = 'Личные данные';
	$vars['edit_user_data_url'] = Ufu('index.php?name=user&op=editprofile', 'user/{op}/');
	$vars['ledit_user_data'] = 'Редактировать данные';
	$vars['exit_url'] = 'index.php?name=plugins&p=login&a=exit';
	$vars['lexit'] = 'Выход';
	$vars['isadmin'] = $user->isAdmin();
	$vars['adminfile_url'] = $config['admin_file'];
	$vars['ladminpanel'] = 'Админ-панель';
	$vars['title'] = 'Блок пользователя';
}

?>