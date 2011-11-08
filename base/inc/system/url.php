<?php

/**
 * Удаляет протокол у http ссылок.
 * @param $url
 * @return mixed
 */
function Url( $url ){
	return preg_replace(array('/^https:\/\//', '/^http:\/\//', '/^www\./'), '', $url);
}

//Код проверяет ссылку - если это ссылка на страницы своего же сайта
// - то редирект не используется.
//Если это ссылка на внешние ресурсы (другие сайты) - то редирект включается
//(при включённой опции "Промежуточная страница для внешних ссылок").
function UrlRender( $url ){
	global $config;
	if($config['general']['specialoutlinks']) {
		if(!IsMainHost($url)){
			return 'index.php?name=plugins&p=out&url='.urlencode(Url($url));
		}else{
			return 'http://'.Url($url);
		}
	}else{
		return 'http://'.Url($url);
	}
}
