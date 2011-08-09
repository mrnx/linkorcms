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
 * @param  $Value
 * @return string
 * @since 1.3.5
 */
function JsonEncode( $Value ){
	return json_encode(ObjectCp1251ToUtf8($Value));
}

/**
 * Декодирует объект из строки в формате JSON
 * @param  $Json
 * @return mixed
 * @since 1.3.5
 */
function JsonDecode( $Json ){
	return json_decode($Json);
}
