<?php

/**
 * Функции для работы с системными плагинами
 */

/**
 * Очищает кэш поиска системных плагинов
 * @return void
 */
function SystemPluginsClearCache(){
	LmFileCache::Instance()->Delete('system', 'system_plugins');
}

/**
 * Ищет все системные плагины и группы
 * @param bool $ClearCache
 * @return array|null|string
 */
function SystemPluginsLoad( $ClearCache = false ){
	global $config;
	static $resultcache = null;
	$plug_dir = $config['sys_plug_dir'];

	if($ClearCache){
		$resultcache = null;
		SystemPluginsClearCache();
	}

	if($resultcache != null){
		return $resultcache;
	}

	if(LmFileCache::Instance()->HasCache('system', 'system_plugins')){
		$resultcache = LmFileCache::Instance()->Get('system', 'system_plugins');
		return $resultcache;
	}

	$plugins = array();
	$plug_dirs = GetFolders($plug_dir);
	foreach($plug_dirs as $dir){
		if(is_file($plug_dir.$dir.'/info.php')){
			include $plug_dir.$dir.'/info.php'; // => $groups[name] | $plugins[name]
		}
	}

	// Плагины (добавляем информацию)
	foreach($plugins as $plugin_name => $plugin_info){
		$plugins[$plugin_name]['group'] = '';
		$plugins[$plugin_name]['name'] = $name;
	}
	$result['plugins'] = $plugins;

	// Группы (поиск плагинов группы) $groups загружается из файлов info.php
	foreach($groups as $group_name => $group_info){
		$plugins = array();
		$folder = $plug_dir.$group_name.'/';

		$plug_dirs = GetFolders($folder);
		foreach($plug_dirs as $dir){
			if(is_file($folder.$dir.'/info.php')){
				include $folder.$dir.'/info.php';
			}
		}
		foreach($plugins as $plugin_name => $plugin_info){
			$plugins[$plugin_name]['group'] = $group_name;
			$plugins[$plugin_name]['name'] = $plugin_name;
		}
		// Плагины группы доступны через группу
		$groups[$group_name]['plugins'] = $plugins;
	}
	$result['groups'] = $groups;

	$resultcache = &$result;
	LmFileCache::Instance()->Write('system', 'system_plugins', $result, Day2Sec);
	return $result;
}

/**
 * Подключает группу системных плагинов
 * @param  $group группа
 * @param string $function подгруппа(если есть)
 * @param bool $return возвратить имена файлов плагинов вместо их автоматического подключения
 * @param bool $return_full возвращать вместо имен файлов массив с полной информацией по плагинам
 * @return array
 */
function SystemPluginsIncludeGroup($group, $function = '', $return = false, $return_full = false){
	global $config;
	$plug_dir = $config['sys_plug_dir'];
	$plugins = SystemPluginsLoad();
	$result = array();
	if(isset($plugins['groups'][$group])){
		$plugins = $plugins['groups'][$group]['plugins'];
		foreach($plugins as $plugin){
			if(($function == '') || (isset($plugin['function']) && $function == $plugin['function'])){
				global $include_plugin_path; // эта переменная будет доступна из плагина
				$include_plugin_path = RealPath2($plug_dir.$group.'/'.$plugin['name']).'/';
				if($return){
					if($return_full){
						$plugin['path'] = $include_plugin_path;
						$result[] = $plugin;
					} else{
						$result[] = $include_plugin_path;
					}
				}else{
					include  $include_plugin_path.'index.php';
				}
			}
		}
	}
	return $result;
}
