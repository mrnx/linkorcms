<?php

// Проверяет является ли ссылка внутренней.
function IsMainHost( $url ){
	$host = $_SERVER['HTTP_HOST'];
	if(stristr(Url($url), Url($host))) {
		return true;
	}else{
		return false;
	}
}

/**
 * Посылает команду браузеру перейти по указанному адресу.
 * Рекомендуется использовать вместо Header('Location: ...');
 * @param String $address // Адрес перехода.
 * @param bool $exit
 * @param int $response_code
 * @return
 *
 */
function GO( $address, $exit = true, $response_code = 303 ){
	if($address == '') return;
	if(!defined('ERROR_HANDLER') || count(System::$Errors) == 0){ // todo Учитывать значение настройки вывода ошибок в браузер
		if($response_code == 302){
			Header('Location: '.$address);
		}else{
			Header('Location: '.$address, true, $response_code);
		}
		if($exit){
			exit;
		}
	}
}

function GoBack( $exit = true, $response_code = 303 ){
	if(isset($_SERVER['HTTP_REFERER'])){
		GO($_SERVER['HTTP_REFERER'], $exit, $response_code);
	}else{
		GO(Ufu('index.php'), $exit, $response_code);
	}
}

// Перенаправляет пользователя на страницу на которой он был заданное число переходов ранее
// Если в качестве значения параметра $BackSteps передать единицу, то работа функции будет аналогична функции GoBack()
// Максимальное значение $BackSteps равно девяти.
function HistoryGoBack( $BackSteps, $exit = true, $response_code = 303 ){
	global $user;
	$history = $user->Get('HISTORY');
	if(isset($history[10-$BackSteps])){
		GO($history[10-$BackSteps], $exit, $response_code);
	}
}

function HistoryGetUrl( $BackSteps ){
	global $user;
	$history = $user->Get('HISTORY');
	if(isset($history[10-$BackSteps])){
		return $history[10-$BackSteps];
	}else{
		return '';
	}
}

/**
 * Сохраняет адрес в сессии и возвращает идентификатор
 * @param string $Url
 *
 * @internal param $ <type> $Url
 * @return \String <type>
 */
function SaveRefererUrl( $Url = '' ){
	if($Url == ''){
		$Url = HistoryGetUrl(1);
	}
	$id = GenRandomString(10);
	$_SESSION['saved_urls'][$id] = $Url;
	return $id;
}

/**
 * Выполняет перенаправление по сохраненному в сессии адресу
 * @param $id
 */
function GoRefererUrl( $id ){
	if(isset($_SESSION['saved_urls'][$id])){
		$url = $_SESSION['saved_urls'][$id];
		unset($_SESSION['saved_urls'][$id]);
		GO($url);
	}else{
		GO(HistoryGetUrl(2));
	}
}

function GetRefererUrl( $id ){
	if(isset($_SESSION['saved_urls'][$id])){
		$url = $_SESSION['saved_urls'][$id];
		unset($_SESSION['saved_urls'][$id]);
		return $url;
	}else{
		return HistoryGetUrl(2);
	}
}

/**
 * Возвращает относительную директорию, в которую установлен сайт.
 * @return String
 * @since 1.3.3
 */
function GetSiteDir( $EndSlash = true ){
	$dir = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
	if(substr($dir, -1) != '/' && $EndSlash){
		$dir .= '/';
	}elseif(substr($dir, -1) == '/' && !$EndSlash){
		$dir = substr($dir, 0, -1);
	}
	return $dir;
}

function GetSiteRoot( $EndSlash = true ){
	$doc = $_SERVER['DOCUMENT_ROOT'];
	$dir = GetSiteDir($EndSlash);
	if(substr($doc, -1) != '/' && substr($dir, 0, 1) != '/') $doc .= '/';
	$root = $doc.$dir;
	return $root;
}

/**
 * Возвращает домен сайта
 * @return string
 */
function GetSiteDomain(){
	return getenv("HTTP_HOST");
}

/**
 * Возвращает адрес сайта
 * @return void
 */
function GetSiteHost(){
	$host = 'http://'.GetSiteDomain();
	if(substr($host, -1) == '/'){
		$host = substr($host, 0, -1);
	}
	return $host;
}

/**
 * Возвращает URL сайта с относительной директорией в которую установлен сайт.
 * @return String
 * @since 1.3.3
 */
function GetSiteUrl( $EndSlash = true ){
	return GetSiteHost().GetSiteDir($EndSlash);
}
