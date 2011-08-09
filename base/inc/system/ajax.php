<?php

/**
 * ������� ��� ������ � ������� ������������� � Ajax ��������
 */

/**
 * �������� ��������� � ������� Ajax (XMLHttpRequest) ?
 * @return bool
 */
function IsAjax(){
	return isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' : false;
}

/**
 * �������� ������ � ������ JSON
 * @param  $Value
 * @return string
 * @since 1.3.5
 */
function JsonEncode( $Value ){
	return json_encode(ObjectCp1251ToUtf8($Value));
}

/**
 * ���������� ������ �� ������ � ������� JSON
 * @param  $Json
 * @return mixed
 * @since 1.3.5
 */
function JsonDecode( $Json ){
	return json_decode($Json);
}
