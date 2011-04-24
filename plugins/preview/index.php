<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$type = SafeDB($_GET['mod'], 40, str);
$type = RealPath2($type.'.php');
$type = PLUG_DIR.'plugins/'.$type;
if(!is_file($type) || !file_exists($type)){
	echo 'Тип не зарегистрирован';
}

global $config;

//Подключаем плагин предпросмотра
include ($type);

?>