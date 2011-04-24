<?php

// Обработчик ошибок
// Запись ошибок в лог файл

define('ERROR_HANDLER', true);
error_reporting(E_ALL);

define('ERROR', 1);
define('WARNING', 2);
define('PARSE', 4);
define('NOTICE', 8);
define('CORE_ERROR', 16);
define('CORE_WARNING', 32);
define('COMPILE_ERROR', 64);
define('COMPILE_WARNING', 128);
define('USER_ERROR', 256);
define('USER_WARNING', 512);
define('USER_NOTICE', 1024);

$SITE_ERRORS = true;
function ErrorsOn(){
	global $SITE_ERRORS;
	$SITE_ERRORS = true;
}

function ErrorsOff(){
	global $SITE_ERRORS;
	$SITE_ERRORS = false;
}

function error_handler( $No, $Error, $File, $Line = -1 ){
	global $ErrorsLog, $SITE_ERRORS;
	$errortype = array(
		1=>'Ошибка',
		2=>'Предупреждение!',
		4=>'Ошибка разборщика',
		8=>'Замечание',
		16=>'Ошибка ядра',
		32=>'Предупреждение ядра!',
		64=>'Ошибка компиляции',
		128=>'Предупреждение компиляции!',
		256=>'Пользовательская Ошибка',
		512=>'Пользовательскаое Предупреждение!',
		1024=>'Пользовательскаое Замечание',
		2048=>'Небольшое замечание',
		8192=>'Устаревший код');
	$Error = '<br /><b>'.$errortype[$No].'</b>: '.$Error.' в <b>'.$File.($Line > -1 ? '</b> на линии <b>'.$Line.'</b>' : '').'.<br />';
	if(!defined('SETUP_SCRIPT') && System::$config['debug']['log_errors'] == '1'){
		$ErrorsLog->Write($Error);
	}
	if($SITE_ERRORS && isset(System::$config['debug']['php_errors']) && System::$config['debug']['php_errors'] == '1'){
		System::$Errors[] = $Error."\n";
	}
}

set_error_handler('error_handler');

?>