<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$system['stop_hit'] = true;
if(!isset($_GET['p'])){
	$PluginName = '';
}else{
	$PluginName = SafeEnv($_GET['p'], 40, str);
}

//Проверяем доступен ли данный плагин
$db->Select('plugins', '`type`=\''.PLUG_CALLEE.'\'');
$valid_p = false;
while($mod = $db->FetchRow()){
	if($PluginName == $mod['name']){
		$valid_p = true;
		break;
	}
}

define('PLUG_DIR', $config['plug_dir'].$PluginName.'/');
define('PLUG_FILE', PLUG_DIR.'index.php');

if($valid_p && file_exists(PLUG_FILE)){
	include_once(PLUG_DIR.'info.php');
	include_once(PLUG_FILE);
}else{
	HackOff(true, false);
	echo "<b>Ошибка</b>: Функция отключена или не поддерживается.";
}

?>