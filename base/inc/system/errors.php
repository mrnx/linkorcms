<?php

// Ошибки
define('ERROR_HANDLER', true);
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

/**
 * Включить вывод ошибок
 * @return void
 */
function ErrorsOn(){
	global $SITE_ERRORS;
	$SITE_ERRORS = true;
}

/**
 * Временно отключить вывод ошибок
 * @return void
 */
function ErrorsOff(){
	global $SITE_ERRORS;
	$SITE_ERRORS = false;
}

/**
 * Обработчик ошибок
 * @param  $No
 * @param  $Error
 * @param  $File
 * @param  $Line
 * @return void
 */
function ErrorHandler( $No, $Error, $File, $Line = -1 ){
	global $SITE_ERRORS;
	$errortype = array(
		1 => 'Ошибка', 2 => 'Предупреждение!', 4 => 'Ошибка разборщика', 8 => 'Замечание', 16 => 'Ошибка ядра', 32 => 'Предупреждение ядра!', 64 => 'Ошибка компиляции',
		128 => 'Предупреждение компиляции!', 256 => 'Пользовательская Ошибка', 512 => 'Пользовательскаое Предупреждение!', 1024 => 'Пользовательскаое Замечание', 2048 => 'Небольшое замечание',
		4096=> 'Улавливаемая ошибка', 8192 => 'Устаревший код', 16384 => 'Устаревший код (пользовательская)'
	);
	$ErrorHtml = '<b>'.$errortype[$No].'</b>: '.$Error.' в <b>'.$File.($Line > -1 ? '</b> на линии <b>'.$Line.'</b>' : '').'.';
	if($SITE_ERRORS){
		System::$Errors[] = $ErrorHtml;
	}
}
