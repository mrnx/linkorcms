<?php

function getconf_MainModules( $name )
//в $name имя элемента настройки вызвавшей функцию для своего заполнения
{
	global $config, $db;
	$mods = $db->Select('modules', '`system`=\'0\'');
	$r = array();
	for($i = 0, $cnt = count($mods); $i < $cnt; $i++){
		//1 значение,
		//2 надпись которую будет видеть пользователь
		$r[] = array($mods[$i]['folder'], $mods[$i]['name']);
	}
	return $r;
}

