<?php

	if(!defined('VALID_RUN')){
		header("HTTP/1.1 404 Not Found");
		exit;
	}

	$type = SafeDB($_GET['mod'], 255, str);
	$type = RealPath2($type);
	$type = PLUG_DIR.'plugins/'.$type.'/connector.php';
	if(!is_file($type) || !file_exists($type)){
		echo 'Тип не зарегистрирован ('.$type.')';
		return;
	}

	//Подключаем коннектор
	require $type;

?>