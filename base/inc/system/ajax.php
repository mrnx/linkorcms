<?php

/**
 * Функции для работы с данными передаваемыми в Ajax запросах
 */

/**
 * Страница запрошена с помощью Ajax (XMLHttpRequest) ?
 * @return bool
 */
function IsAjax(){
	return isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' : false;
}

/**
 * Кодирует объект в формат JSON
 * @param  $value
 * @return string
 * @since 1.3.5
 */
function JsonEncode( $value ){
	return json_encode(ObjectCp1251ToUtf8($value));
}

/**
 * Декодирует объект из строки в формате JSON
 * @param  $json
 * @return mixed
 * @since 1.3.5
 */
function JsonDecode( $json ){
	return json_decode($json);
}
