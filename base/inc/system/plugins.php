<?php

define('PLUGINS', true);

// Типы плагинов
define('PLUG_AUTORUN', 1); // Автозапуск (точка входа в config/init.php)
define('PLUG_ADMIN_AUTORUN', 2); // Автозапуск только в админке
define('PLUG_MAIN_AUTORUN', 3); // Автозапуск только на сайте
// -------------
define('PLUG_CALLEE', 4); // Вызываемый отдельно через index.php&name=plugins&p=plugin_name (точка входа в modules/plugins/index.php)
// -------------
define('PLUG_MANUAL', 5); // Нужен для работы определённого модуля и подключается вручную. Использует группы.
define('PLUG_MANUAL_ONE', 7); // Подключается один какой-то плагин из группы. Использует группы.
// ----------------------------

/**
 * Очищает кэш плагинов
 * @return void
 */
function PluginsClearCache(){
	$cache = LmFileCache::Instance();
	$cache->Delete('system', 'plugins');
	$cache->Delete('system', 'plugins_auto_main');
	$cache->Delete('system', 'plugins_auto_admin');
}

/**
 * Возвращает все группы настроек плагинов
 * @return array
 */
function PluginsConfigsGroups(){
	$result = array();
	System::database()->Select('plugins_config_groups', '');
	while($group = System::database()->FetchRow()){
		$result[$group['name']] = $group;
	}
	return $result;
}

/**
 * Загружает информацию об установленных подключаемых плагинах из базы данных и возвращает результат.
 * Кэшируется.
 * @return array
 */
function PluginsGetInstalled(){
	static $plugins = null;
	if(LmFileCache::Instance()->HasCache('system', 'plugins')){
		$plugins = LmFileCache::Instance()->Get('system', 'plugins');
	}
	if(!isset($plugins)){
		$plugins = System::database()->Select('plugins', "(`type`='5' or `type`='7') and `enabled`='1'");
		LmFileCache::Instance()->Write('system', 'plugins', $plugins, Day2Sec);
	}
	return $plugins;
}

/**
 * Возвращает информацию по установленным плагинам.
 * @param string $Group
 * @return void
 */
function PluginsGetInfo( $Group = '', $function = '' ){
	global $config;
	$plugins = PluginsGetInstalled();
	$plug_dir = $config['plug_dir'];
	$result = array();
	foreach($plugins as $plugin){
		if($Group == $plugin['group'] && $function == $plugin['function']){
			$include_plugin_path = RealPath2($plug_dir.$group.'/'.$plugin['name']).'/';
			$info = ExtLoadInfo($include_plugin_path);
			$info['path'] = $include_plugin_path;
			$info['folder'] = $plugin['name'];
			$result[] = $info;
		}
	}
	return $result;
}

/**
 * Подключает группу плагинов
 * @param $group группа
 * @param string $function подгруппа
 * @param bool $return возвратить пути к папкам плагинов вместо автоматического подкочения
 * @return array
 */
function IncludePluginsGroup( $group, $function = '', $return = false ){
	global $config, $db; // В поддержку старых плагинов
	global $include_plugin_path; // эта переменная будет доступна из плагина
	$plugins = PluginsGetInstalled();
	$plug_dir = $config['plug_dir'];
	$result = array();
	foreach($plugins as $plugin){
		if($group == $plugin['group'] && $function == $plugin['function']){
			$include_plugin_path = RealPath2($plug_dir.$group.'/'.$plugin['name']).'/';
			if($return){
				$result[] = $include_plugin_path;
			}else{
				include $include_plugin_path.'index.php';
			}
		}
	}
	return $result;
}
