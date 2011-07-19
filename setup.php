<?php

/*
 * LinkorCMS 1.4
 * © 2011 LinkorCMS Development Group
 */

define("SETUP_SCRIPT", true);
define("VALID_RUN", true);

@set_time_limit(600);

// Блокировка инсталлятора
if(is_file('config/setup_lock.php') && !is_file('dev.php')){
	exit('<html><head><title>Ошибка!</title></head><body><center><h2>Система уже установлена.</h2><br />
		Инсталлятор заблокирован.<br />
		Для переустановки системы удалите файлы <strong>config/db_config.php</strong> и <strong>config/setup_lock.php</strong>.</center>
	</body></html>');
}

require 'config/init.php';

$default_prefix = 'table';
$bases_path = 'setup/bases/';
$info_ext = '.MYD';
$data_ext = '.FRM';

$config['s_dir'] = 'setup/';
$config['s_plug_dir'] = 'setup/plugins/';
$config['s_inc_dir'] = 'setup/inc/';
$config['s_lng_dir'] = 'setup/lng/';
$config['s_mod_dir'] = 'setup/mods/';
$config['s_tpl_dir'] = 'setup/template/';

include_once($config['s_inc_dir'].'functions.php');
include_once($config['s_inc_dir'].'template.php');// Шаблон
$site->AddJSFile($config['s_inc_dir'].'functions.js', true, true);
include_once($config['s_inc_dir'].'setup.class.php'); // Класс управления инсталлятором
include_once($config['s_inc_dir'].'plugins.php'); // Поддержка плагинов
include_once($config['s_lng_dir'].'lang-russian.php'); // Мультиязычность

if(isset($_GET['mod'])){
	$mod = SafeEnv($_GET['mod'], 255, str);
}else{
	$mod = '';
}
$setup->Page($mod);
