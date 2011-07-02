<?php

/**
 * Автозагрузка классов
 */

function __autoload( $ClassName ){
	require_once $GLOBALS['system_autoload'][$ClassName];
}

/**
 * Регистрация класса для автозагрузки
 * @param Строка $ClassName Название класса
 * @param Строка $FileName Имя файла
 */
function RegisterClass( $ClassName, $FileName ){
	$GLOBALS['system_autoload'][$ClassName] = $FileName;
}

/**
 * Регистрация массива классов для автозагрузки
 * @param Массив $ClassesArray Ассоциативный массив имен классов и файлов
 * @param Строка $Path Путь к файлам
 */
function RegisterClassesArray( $ClassesArray, $Path = '' ){
	foreach($ClassesArray as $class=>$file){
		$GLOBALS['system_autoload'][$class] = $Path.$file;
	}
}

/**
 * Удаляет класс или массив классов из автозагрузки
 * @param string|array $ClassName
 */
function UnregisterClass( $ClassName ){
	if(is_array($ClassName)){
		foreach($ClassName as $class){
			unset($GLOBALS['system_autoload'][$class]);
		}
	}else{
		unset($GLOBALS['system_autoload'][$ClassName]);
	}
}

