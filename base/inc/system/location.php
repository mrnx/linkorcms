<?php

// ��������� �������� �� ������ ����������.
function IsMainHost( $url ){
	$host = $_SERVER['HTTP_HOST'];
	if(stristr(Url($url), Url($host))) {
		return true;
	}else{
		return false;
	}
}

/**
 * �������� ������� �������� ������� �� ���������� ������.
 * ������������� ������������ ������ Header('Location: ...');
 * @param String $address // ����� ��������.
 * @param bool $exit
 * @param int $response_code
 * @return
 *
 */
function GO( $address, $exit = true, $response_code = 303 ){
	if($address == '') return;
	if(!defined('ERROR_HANDLER') || count(System::$Errors) == 0){ // todo ��������� �������� ��������� ������ ������ � �������
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

// �������������� ������������ �� �������� �� ������� �� ��� �������� ����� ��������� �����
// ���� � �������� �������� ��������� $BackSteps �������� �������, �� ������ ������� ����� ���������� ������� GoBack()
// ������������ �������� $BackSteps ����� ������.
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
 * ��������� ����� � ������ � ���������� �������������
 * @param string $Url ���� URL
 * @return String <type>
 */
function SaveRefererUrl( $Url = '' ){
	static $Cache;
	if(isset($Cache[$Url])){
		return $Cache[$Url];
	}
	if($Url == ''){ // ��������� ������� �����
		$Url = GetSiteUrl().GetPageUri();
	}
	if(isset($_SESSION['saved_urls']) && in_array($Url, $_SESSION['saved_urls'])){
		$key = array_keys($_SESSION['saved_urls'], $Url);
		$Cache[$Url] = $key[0];
		return $key[0];
	}
	$id = GenRandomString(10);
	$_SESSION['saved_urls'][$id] = $Url;
	return $id;
}

/**
 * ��������� ��������������� �� ������������ � ������ ������
 * @param $id ������������� ������
 * @param $anchor ���������� ����� � ������. ������: #post244
 */
function GoRefererUrl( $id, $anchor = '' ){
	if(isset($_SESSION['saved_urls'][$id])){
		$url = $_SESSION['saved_urls'][$id];
		unset($_SESSION['saved_urls'][$id]);
		GO($url.$anchor);
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
 * ���������� ������������� ����������, � ������� ���������� ����.
 * @param bool $EndSlash �������� ��������� ����
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

/**
 * ���������� ���������� ���� �� ����� �����.
 * @param bool $EndSlash �������� ��������� ����
 * @return string
 */
function GetSiteRoot( $EndSlash = true ){
	$dir = $_SERVER['DOCUMENT_ROOT'].GetSiteDir(true);
	if(substr($dir, -1) != '/' && $EndSlash){
		$dir .= '/';
	}elseif(substr($dir, -1) == '/' && !$EndSlash){
		$dir = substr($dir, 0, -1);
	}
	return $dir;
}

/**
 * ���������� ����� �����
 * @return string
 */
function GetSiteDomain(){
	return getenv("HTTP_HOST");
}

/**
 * ���������� ����� �����
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
 * ���������� URL ����� � ������������� ����������� � ������� ���������� ����.
 * @return String
 * @since 1.3.3
 */
function GetSiteUrl( $EndSlash = true ){
	return GetSiteHost().GetSiteDir($EndSlash);
}

function GetPageUri( $FirstSlash = false ){
	$uri = $_SERVER['REQUEST_URI'];
	if(substr($uri, 0, 1) != '/' && $FirstSlash){
		$uri = '/'.$uri;
	}elseif(substr($uri, 0, 1) == '/' && !$FirstSlash){
		$uri = substr($uri, 1);
	}
	return $uri;
}
