<?php

# LinkorCMS
# © 2006-2010 Галицкий Александр Николаевич (linkorcms@yandex.ru)
# Файл: plugins.php
# Назначение: Поддержка плагинов

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

//Подключаем AUTORUN плагины
if(defined('MAIN_SCRIPT') || defined('ADMIN_SCRIPT')){
	$pcache = LmFileCache::Instance();
	if(defined('MAIN_SCRIPT')){
		$pcache_name = 'plugins_auto_main';
	}elseif(defined('ADMIN_SCRIPT')){
		$pcache_name = 'plugins_auto_admin';
	}

	if($pcache->HasCache('system', $pcache_name)){
		$plugins = $pcache->Get('system', $pcache_name);
	}else{
		if(defined('MAIN_SCRIPT')){
			$q = "`type`= 1 or `type`= 3";
		}elseif(defined('ADMIN_SCRIPT')){
			$q = "`type`= 1 or `type`= 2";
		}
		$plugins = $db->Select('plugins', $q);
		$pcache->Write('system', $pcache_name, $plugins);
	}


	foreach($plugins as $plugin){
		$PluginName = $config['plug_dir'].SafeDB(RealPath2($plugin['name']), 255, str);
		if(file_exists($PluginName.'/index.php') && is_dir($PluginName)){
			include_once ($PluginName.'/index.php');
		}else{
			UninstallPlugin($plugin['name']);
		}		
	}
	unset($PluginName, $plugins, $plugin, $q, $pcache, $pcache_name);
}

// Группы настроек плагинов
function PluginsConfigsGroups()
{
	global $db;
	$result = array();
	$db->Select('plugins_config_groups', '');
	while($group = $db->FetchRow()){
		$result[$group['name']] = $group;
	}
	return $result;
}

// Возвращает все плагины
function GetPlugins( $ClearCache = false )
{
	global $config, $db;
	static $resultcache = null;

	if($ClearCache){
		$resultcache = null;
		PluginsClearCache();
	}

	if($resultcache != null) return $resultcache;
	$cache = LmFileCache::Instance();
	if($cache->HasCache('system', 'plugins')){
		$resultcache = $cache->Get('system', 'plugins');
		return $resultcache;
	}

	$install_plugins = array(); // Установленные плагины
	$install_groups = array(); // Установленные группы

	$plugins = $db->Select('plugins', '');
	foreach($plugins as $temp){
		if($temp['type'] == PLUG_MANUAL || $temp['type'] == PLUG_MANUAL_ONE){
			$install_groups[$temp['group']][$temp['name']] = true;
		}else{
			$install_plugins[$temp['name']] = true;
		}
	}

	$result = LoadPlugins($ClearCache);
	$groups = &$result['groups'];
	$plugins = &$result['plugins'];
	foreach($plugins as $name=>$plugin){
		if(isset($plugins['type']) && $plugins['type'] == PLUG_SYSTEM){
			unset($plugins[$name]);
		}else{
			$plugins[$name]['installed'] = isset($install_plugins[$name]);
		}
	}
	foreach($groups as $name=>$group){
		if(isset($groups[$name]['type']) && $groups[$name]['type'] == PLUG_SYSTEM){
			unset($groups[$name]);
		}else{
			foreach($group['plugins'] as $pname=>$plugin){
				$groups[$name]['plugins'][$pname]['installed'] = isset($install_groups[$name][$pname]);
			}
		}
	}
	$resultcache = &$result;
	$cache->Write('system', 'plugins', $result);
	return $result;
}

// Удаляет плагин
function UninstallPlugin( $plugin_name, $group = '' )
{
	global $config, $db;
	$name = $plugin_name;
	$plugins = GetPlugins();
	if($group != ''){
		if(isset($plugins['groups'][$group]['plugins'][$name]) && $plugins['groups'][$group]['plugins'][$name]['installed'] == true){
			$p = &$plugins['groups'][$group]['plugins'][$name];
			$uninstall_file = RealPath2($config['plug_dir'].$group.'/'.$name.'/'.'uninstall.php');
			if(file_exists($uninstall_file)){
				include_once ($uninstall_file);
			}
			$db->Delete('plugins', "`name`='$name' and `group`='$group'");
			PluginsClearCache();
		}
	}else{
		if(isset($plugins['plugins'][$name]) && $plugins['plugins'][$name]['installed'] == true){
			$p = &$plugins['plugins'][$name];
			$uninstall_file = RealPath2($config['plug_dir'].$name.'/'.'uninstall.php');
			if(file_exists($uninstall_file)){
				include_once ($uninstall_file);
			}
			$db->Delete('plugins', "`name`='".$name."'");
			PluginsClearCache();
		}
	}
}

// Удаляет группу плагинов
function UninstallGroup( $group )
{
	global $db;
	$db->Delete('plugins', "`group`='$group'");
	PluginsClearCache();
}

// Установка плагина
function InstallPlugin( $plugin_name, $group = '' )
{
	global $config, $db;
	$name = $plugin_name;
	$plugins = GetPlugins();
	if($group != ''){
		if(isset($plugins['groups'][$group]['plugins'][$name]) && $plugins['groups'][$group]['plugins'][$name]['installed'] == false){
			$p = &$plugins['groups'][$group]['plugins'][$name];
			if(!isset($p['config'])){
				$p['config'] = '';
			}
			if(($plugins['groups'][$group]['type'] == PLUG_MANUAL_ONE || $p['type'] == PLUG_MANUAL_ONE)){
				UninstallGroup($group);
			}
			$install_file = RealPath2($config['plug_dir'].$group.'/'.$name.'/'.'install.php');
			if(file_exists($install_file)){
				include_once ($install_file);
			}
			$vals = Values('', $name, SafeEnv($p['config'], 0, str), SafeEnv($p['type'], 1, int), $group);
			$db->Insert('plugins', $vals);
			PluginsClearCache();
		}
	}else{
		if(isset($plugins['plugins'][$name]) && $plugins['plugins'][$name]['installed'] == false){
			$p = &$plugins['plugins'][$name];
			if(!isset($p['config'])){
				$p['config'] = '';
			}
			$install_file = RealPath2($config['plug_dir'].$name.'/'.'install.php');
			if(file_exists($install_file)){
				include_once ($install_file);
			}
			$vals = Values('', SafeEnv($name, 250, str), SafeEnv($p['config'], 0, str), SafeEnv($p['type'], 1, int), SafeEnv($group, 250, str));
			$db->Insert('plugins', $vals);
			PluginsClearCache();
		}
	}
}

// Подключает группу плагинов
# $function - это просто метка для подгруппы плагинов
function IncludePluginsGroup( $group, $function = '', $return = false )
{
	global $config, $db;
	$plugins = GetPlugins();
	$result = array();
	if(isset($plugins['groups'][$group])){
		$plugins = $plugins['groups'][$group]['plugins'];
		foreach($plugins as $plugin){
			if(($plugin['installed'] && $function == '') || ($plugin['installed'] && isset($plugin['function']) && $function == $plugin['function'])){
				global $include_plugin_path; // эта переменная будет доступна из плагина
				$include_plugin_path = RealPath2($config['plug_dir'].$group.'/'.$plugin['name'].'/');
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
?>