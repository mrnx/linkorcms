<?php

/**
 * Конвертирует строку в кодировку UTF-8
 * @param $String
 * @return string
 */
function Cp1251ToUtf8( $String ){
	return iconv("windows-1251", "utf-8//IGNORE//TRANSLIT", $String);
}

/**
 * Конвертирует строку из UTF-8 кодировки в Cp1251
 * @param $Unicode
 * @return string
 */
function Utf8ToCp1251( $Unicode ){
	return iconv("utf-8", "windows-1251", $Unicode);
}

/**
 * Преобразует строки объекта или массива в кодировку UTF-8
 * @param  $var
 * @return array|string
 * @since 1.3.5
 */
function ObjectCp1251ToUtf8( &$var ){
	if(is_array($var)){
		foreach($var as &$v){
			$v = ObjectCp1251ToUtf8($v);
		}
	}elseif(is_object($var)){
		$vars = get_object_vars($var);
		foreach($vars as $f=>&$v) {
			$var->$f = ObjectCp1251ToUtf8($v);
		}
	}elseif(is_string($var)){
		$var = Cp1251ToUtf8($var);
	}
	return $var;
}

/**
 * Преобразует строки объекта или массива из UTF-8 кодировки в CP1251
 * @param  $var
 * @return array|string
 * @since 1.3.5
 */
function ObjectUtf8ToCp1251( &$var ){
	if(is_array($var)){
		foreach($var as &$v){
			$v = ObjectUtf8ToCp1251($v);
		}
	}elseif(is_object($var)){
		$vars = get_object_vars($var);
		foreach($vars as $f=>&$v) {
			$var->$f = ObjectUtf8ToCp1251($v);
		}
	}elseif(is_string($var)){
		$var = Utf8ToCp1251($var);
	}
	return $var;
}
