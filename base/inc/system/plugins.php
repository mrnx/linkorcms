<?php

define('PLUGINS', true);

// Типы плагинов
define('PLUG_AUTORUN', 1); // Автозапуск (точка входа в config/init.php)
define('PLUG_ADMIN_AUTORUN', 2); // Автозапуск только в админке
define('PLUG_MAIN_AUTORUN', 3); // Автозапуск только на сайте

define('PLUG_CALLEE', 4); // Вызываемый отдельно через index.php&name=plugins&p=plugin_name (точка входа в modules/plugins/index.php)

define('PLUG_MANUAL', 5); // Нужен для работы определённого модуля и подключается вручную. Использует группы.
define('PLUG_MANUAL_ONE', 7); // Подключается один какой-то плагин из группы. Использует группы.

/**
 * Очищает кэш плагинов
 * @return void
 */
function PluginsClearCache(){
	$cache = LmFileCache::Instance();
	$cache->Delete('system', 'plugins_auto_main');
	$cache->Delete('system', 'plugins_auto_admin');
}

/**
 * Подключает группу плагинов
 * @param $group группа
 * @param string $function подгруппа
 * @param bool $return возвратить имена файлов плагинов вместо автоматического подкочения
 * @return array
 */
function IncludePluginsGroup($group, $function = '', $return = false){
	global $config, $db;
	$plugins = GetPlugins();
	$result = array();
	if(isset($plugins['groups'][$group])){
		$plugins = $plugins['groups'][$group]['plugins'];
		foreach($plugins as $plugin){
			if(($plugin['installed'] && $function == '') || ($plugin['installed'] && isset($plugin['function']) && $function == $plugin['function'])){
				global $include_plugin_path; // эта переменная будет доступна из плагина
				$include_plugin_path = RealPath2($config['plug_dir'].$group.'/'.$plugin['name']).'/';
				if($return){
					$result[] = $include_plugin_path;
				}else{
					include ($include_plugin_path.'index.php');
				}
			}
		}
	}
	return $result;
}
