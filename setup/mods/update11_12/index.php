<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

global $user;

if(!$user->isDef('setup_type')){
	$user->Def('setup_type', 'update11_12');
}

if(isset($_GET['p'])){
	$p = SafeEnv($_GET['p'], 1, int);
}else{
	$p = 1;
}

switch($p){
	// Производим обновление:::
	case 1:
		global $db, $config;
		include_once ($config['s_inc_dir'].'database.php');
		$this->SetTitle('Обновление 1.1->1.2');
		if(!isset($config['db_version'])){
			$config['db_version'] = '1.1';
		}
		if($config['db_version'] == CMS_VERSION){
			$this->SetContent('Обновление НЕ требуется. Версия БД и версия системы совпадают.');
			$this->AddButton('На сайт', 'finish&p=2');
		}elseif($config['db_version'] != '1.1' || CMS_VERSION != '1.2'){
			$this->SetContent('Версия БД должна быть 1.1, а версия системы 1.2.');
		}elseif(!is_writable($config['config_dir']."db_config.php")){
			$this->SetContent('Файл конфигурации "'.$config['config_dir']."db_config.php".'" не доступен для записи, обновление не было произведено. Выставите права 666 для этого файла и повторите снова.');
			$this->AddButton('Повторить', 'update11_12');
		}else{
			// Обновление
			include_once ($config['s_mod_dir'].'update11_12/update.php');
			$this->SetContent('Обновление базы данных прошло успешно!');
			// Обновление файла конфига
			$filename = $config['config_dir']."db_config.php";
			copy($filename, $config['config_dir']."db_config-backup.php");
			WriteConfigFile($filename, $config['db_type'], $config['db_host'], $config['db_user'], $config['db_pass'], $config['db_name'], $config['db_pref'], CMS_VERSION);
			$this->AddButton('На сайт', 'finish&p=2');
		}
		break;
}

?>