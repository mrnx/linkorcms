<?php

/**
 * Функции для работы с системными плагинами.
 */

/**
 * Подключает группу системных плагинов.
 * @param  $group группа
 * @param string $function подгруппа(если есть)
 * @param bool $return возвратить имена файлов плагинов вместо их автоматического подключения
 * @param bool $return_full возвращать вместо имен файлов массив с полной информацией по плагинам
 * @return array
 */
function SystemPluginsIncludeGroup($group, $function = '', $return = false, $return_full = false){
	global $config;
	$result = array();

	// Поиск плагинов
	$group_dir = $config['sys_plug_dir'].$group;
	if(!is_dir($group_dir)){
		return array();
	}
	$plugins = GetFolders($group_dir.'/');

	// Подготавливаем результат
	foreach($plugins as $plugin){
		if($function != ''){
			$plugin_name = RealPath2($group_dir.'/'.$plugin.'/'.$function).'/';
		}else{
			$plugin_name = RealPath2($group_dir.'/'.$plugin).'/';
		}
		global $include_plugin_path; // эта переменная будет доступна из плагина
		$include_plugin_path = $plugin_name;
		if($return){
			$result[] = $include_plugin_path;
		}else{
			include $include_plugin_path.'index.php';
		}
	}
	return $result;
}
