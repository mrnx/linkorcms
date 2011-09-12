<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('Настройки сайта');

if(!$user->CheckAccess2('config', 'config')){
	AddTextBox('Ошибка', 'Доступ запрещен!');
	return;
}

include_once ($config['inc_dir'].'configuration/functions.php');

if(isset($_GET['a'])){
	$action = $_GET['a'];
}else{
	$action = 'general';
}
$titles = array(
	'general'=>'Основная информация',
	'debug'=>'Отладка',
	'comments'=>'Комментарии',
	'security'=>'Безопасность',
	'meta_tags'=>'Мета теги',
	'smtp'=>'Параметры SMTP',
	'admin_panel'=>'Админ-панель',
);
TAddToolLink($titles['general'], 'general', 'config&a=general');
TAddToolLink($titles['debug'], 'debug', 'config&a=debug');
TAddToolLink($titles['comments'], 'comments', 'config&a=comments');
TAddToolLink($titles['security'], 'security', 'config&a=security');
TAddToolLink($titles['meta_tags'], 'meta_tags', 'config&a=meta_tags');
TAddToolLink($titles['smtp'], 'smtp', 'config&a=smtp');
TAddToolLink($titles['admin_panel'], 'admin_panel', 'config&a=admin_panel');
TAddToolBox($action, 'Настройки сайта');

if($action != 'save'){
	System::admin()->AddCenterBox('Настройки сайта - '.$titles[$action]);
	if(isset($_GET['saveok'])){
		System::admin()->Highlight('Настройки сохранены.');
	}
	System::admin()->ConfigGroups($action);
	System::admin()->AddConfigsForm(ADMIN_FILE.'?exe=config&a=save&group='.SafeDB($action, 255, str));
}else{
	$Groups = $_GET['group'];
	System::admin()->SaveConfigs($Groups);
	GO(ADMIN_FILE.'?exe=config&a='.$Groups.'&saveok');
}
