<?php

// Типы данных
define('int', 'integer');
define('real', 'float');
define('bool', 'boolean');
define('str', 'string');
define('mix', 'array');
define('intmix', 'int_mix');
define('realmix', 'real_mix');
define('boolmix', 'bool_mix');
define('strmix', 'str_mix');
define('obj', 'object');
define('nil', null);
define('onoff', 'onoff2int');

// Функция преобразует значение к типу boolean.
// Её отличие в приведении строк. Любая непустая строка даст true.
function GetBoolValue($var){
	if(is_string($var)){
		if(strlen($var)==0){
			$r = false;
		}else{
			$r = true;
		}
	}else{
		$r = (bool)$var;
	}
	return $r;
}

function SafeXSS( &$var ){
	$var = strtr( $var,array(
		'&#34'=>'"',
		'&#x22;'=>'"',
		'&quot;'=>'"',
		'%22'=>'"',
		'&#39'=>"'",
		'&#x27;'=>"'",
		'%27'=>"'",
		'&#96'=>'`',
		'&#x60;'=>'`',
		'%60'=>'`',
		'&#32'=>' ',
		'&#x20;'=>' ',
		'&#9'=>"\t",
		'&#x09;'=>"\t",
		'%09'=>"\t",
		'&#61'=>'=',
		'&#x3D;'=>'=',
		'%3D'=>'=',
		'&#60'=>'<',
		'&#x3C;'=>'<',
		'&lt;'=>'<',
		'%3C'=>'<',
		'&#62'=>'>',
		'&#x3E;'=>'>',
		'&gt;'=>'>',
		'%3E'=>'>',
		'&#92'=>'\\',
		'&#x5C;'=>'\\',
		'%5C'=>'\\',
		'&#37'=>'%',
		'&#x25;'=>'%',
		'%25'=>'%',
		'&#43'=>'+',
		'&#x2B;'=>'+',
		'%2B'=>'+',
		'&#173'=>'-',
		'&#xAD;'=>'-',
		'&shy;'=>'-',
		'%AD'=>'-',
		'&#38'=>'&',
		'&#x26;'=>'&',
		'&amp;'=>'&',
		'%26'=>'&'
		)
	);
}

/**
 * Добавляет экранирование в зависимости от настройки php и приводит к типу для вставки переменной в запрос к базе данных.
 * Внимание, включение параметра SafeXSS может исказить данные так, что они будут не равны при стравнении.
 *
 * @param mixed $Var Какая-то переменная
 * @param int $MaxLength Длина строкового значения переменной
 * @param const $Type Константа типа переменной. Константы описаны в system.php
 * @param bool $StripTags Вырезать тэги
 * @param bool $AddSlashes Добавить обратные слэши перед всеми спецсимволами
 * @param bool $SafeXss Заменить некоторые html эквиваленты обычными символами
 * @return mixed
 */
function SafeEnv( $Var, $MaxLength, $Type, $StripTags = false, $AddSlashes = true, $SafeXss = true ){
	$onoff = false;
	if($Type == onoff){
		$onoff = true;
		$Type = str;
		$MaxLength = 3;
		$StripTags = false;
		$SpecialChars = false;
		$SafeXss = false;
	}
	if(is_array($Var)){
		foreach($Var as &$v){
			if($MaxLength > 0){
				$v = substr($v, 0, $MaxLength);
			}
			$v = trim($v);
			if($SafeXss){
				SafeXSS($v);
			}
			if($StripTags){
				$v = strip_tags($v);
			}
			if($AddSlashes){
				if(defined("DATABASE")){
					$v = System::database()->EscapeString($v);
				}else{
					$v = addslashes($v);
				}
			}
			settype($v, $Type);
			if($onoff){
				$v = EnToInt($v);
			}
		}
	}else{
		if($MaxLength > 0){
			$Var = substr($Var, 0, $MaxLength);
		}
		$Var = trim($Var);
		if($SafeXss){
			SafeXSS($Var);
		}
		if($StripTags){
			$Var = strip_tags($Var);
		}
		if($AddSlashes){
			if(defined("DATABASE")){
				$Var = System::database()->EscapeString($Var);
			}else{
				$Var = addslashes($Var);
			}
		}
		settype($Var, $Type);
		if($onoff){
			$Var = EnToInt($Var);
		}
	}
	return $Var;
}

/**
 * Фильтрует переменную для безопасного вывода ее содержимого в браузер пользователя.
 * Функция не добавляет экранирование, переменую отфильтрованную данной функцией опасно передавать в запрос к базе данных.
 *
 * @param mixed $Var
 * @param int $MaxLength
 * @param const $Type
 * @param bool $StripTags
 * @param bool $SpecialChars
 * @param bool $SafeXss
 * @return mixed
 */
function SafeDB( $Var, $MaxLength, $Type, $StripTags = true, $SpecialChars = true, $SafeXss = true ){
	if(is_array($Var)){
		foreach($var as &$v){
			if($MaxLength > 0){
				$v = substr($v, 0, $MaxLength);
			}
			$v = trim($v);
			if($SafeXss){
				SafeXSS($v);
			}
			if($StripTags){
				$v = strip_tags($v);
			}
			if($SpecialChars){
				$v = htmlspecialchars($v);
			}
			settype($v, $Type);
		}
	}else{
		if($MaxLength > 0){
			$Var = substr($Var, 0, $MaxLength);
		}
		$Var = trim($Var);
		if($SafeXss){
			SafeXSS($Var);
		}
		if($StripTags){
			$Var = strip_tags($Var);
		}
		if($SpecialChars){
			$Var = htmlspecialchars($Var);
		}
		settype($Var, $Type);
	}
	return $Var;
}

/**
 * Добавляет экранирование в зависимости от настройки php и приводит к типу для вставки переменной в запрос к базе данных.
 * Внимание, включение параметра SafeXSS может исказить данные так, что они будут не равны при стравнении.
 *
 * @param string $Names Имя переменной в массиве $_REQUEST. Можно передать несколько
 * имен в строке через запятую, тогда результатом будет ассоциативный массив
 * @param int $MaxLength Длина строкового значения переменных
 * @param const $Type Тип переменных
 * @param bool $StripTags Вырезать Html теги
 * @param bool $AddSlashes Добавить экранирование
 * @param bool $SafeXss Заменить некоторые html эквиваленты обычными символами
 * @return array
 */
function SafeR( $Names, $MaxLength, $Type, $StripTags = false, $AddSlashes = true, $SafeXss = true ){
	$Names = explode(',', $Names);
	$Result = array();
	foreach($Names as $n){
		$n = trim($n);
		$Result[$n] = SafeEnv($_REQUEST[$n], $MaxLength, $Type, $StripTags, $AddSlashes, $SafeXss);
	}
	return $Result;
}

/**
 * Проверяет массив $_GET на наличие нужных ключей
 * @return bool
 */
function CheckGet(){
	$args = func_get_args();
	foreach($args as $name){
		if(!isset($_GET[$name])){
			return false;
		}
	}
	return true;
}

/**
 * Проверяет массив $_POST на наличие ключей
 * @return bool
 */
function CheckPost(){
	$args = func_get_args();
	foreach($args as $name){
		if(!isset($_POST[$name])){
			return false;
		}
	}
	return true;
}

function RequestMethod(){
	return $_SERVER['REQUEST_METHOD'];
}
