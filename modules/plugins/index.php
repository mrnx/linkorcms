<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$system['stop_hit'] = true;
if(!isset($_GET['p'])){
	HackOff(true, false);
	echo "<b>Ошибка</b>: Функция отключена или не поддерживается.";
	exit();
}else{
	$PluginName = SafeEnv($_GET['p'], 40, str);
}

//Проверяем доступен ли данный плагин
System::database()->Select('plugins', "`name`='$PluginName' and `type`='4");
if(System::database()->NumRows() > 0){
	$p = System::database()->FetchRow();
	$Name = $p['name'];
	$valid_p = true;
}

define('PLUG_DIR', $config['plug_dir'].$Name.'/');
define('PLUG_FILE', PLUG_DIR.'index.php');
if($valid_p && file_exists(PLUG_FILE)){
	include_once(PLUG_DIR.'info.php');
	include_once(PLUG_FILE);
}else{
	HackOff(true, false);
	echo "<b>Ошибка</b>: Функция отключена или не поддерживается.";
}

?>