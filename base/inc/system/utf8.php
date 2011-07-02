<?php

function Cp1251ToUtf8( $String ){
	return iconv("windows-1251", "utf-8//IGNORE//TRANSLIT", $String);
}

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
 * Преобразует строки объекта или массива в кодировку CP1251 из UTF8
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
