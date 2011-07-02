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
 * @param  $value
 * @return string
 * @since 1.3.5
 */
function JsonEncode( $value ){
	return json_encode(ObjectCp1251ToUtf8($value));
}

/**
 * ���������� ������ �� ������ � ������� JSON
 * @param  $json
 * @return mixed
 * @since 1.3.5
 */
function JsonDecode( $json ){
	return json_decode($json);
}
