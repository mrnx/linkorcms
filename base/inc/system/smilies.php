<?php

/**
 * Парсер смайликов
 * @param  $text
 * @return void
 */
function SmiliesReplace( &$text ){
	global $db, $config;
	static $codes = array();
	static $cached = false;
	if(!$cached){
		$smilies = $db->Select('smilies'); // Пусть отключенные смайлики тоже парсятся
		foreach($smilies as $smile){
			$sub_codes = explode(',', $smile['code']);
			foreach($sub_codes as $code){
				$codes[$code] = '<img src="'.$config['general']['smilies_dir'].$smile['file'].'" />';
			}
		}
		$cached = true;
	}
	$text = strtr($text, $codes);
}
