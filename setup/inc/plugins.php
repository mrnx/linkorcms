<?php

# LinkorCMS
# © 2006-2008 Галицкий Александр Николаевич (linkorcms@yandex.ru)
# Файл: plugins.php
# Назначение: Ищет и подключает плагины.

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

define('SETUP_PLUGINS_SUPPORT', true);

function GetPlugins()
{
	global $config;
	static $Cache = false;
	static $resultcache = array();
	if($Cache){
		return $resultcache;
	}
	$folder = $config['s_plug_dir'];
	$plugin_groups = array();
	$plugins = array();
	// Ищем все группы
	$dir = @opendir($folder);
	while($file = @readdir($dir)){
		if($file != '.' && $file != '..' && is_dir($folder.$file)){
			$plugin_groups[$file] = array();
			$folder2 = $config['s_plug_dir'].$file.'/';
			$dir2 = opendir($folder2); // Папка группы
			while($file2 = readdir($dir2)){
				if($file2 != '.' && $file2 != '..' && is_dir($folder2.$file2)){
					$plugin_groups[$file][] = $file2;
				}
			}
		}
	}
	$Cache = true;
	$resultcache = $plugin_groups;
	return $plugin_groups;
}

function Plugins( $group )
{
	global $config;
	$plugins = GetPlugins();
	if(isset($plugins[$group])){
		$plugins = $plugins[$group];
		foreach($plugins as $plugin){
			global $include_plugin_path;
			$include_plugin_path = $config['s_plug_dir'].$group.'/'.$plugin;
			if(is_file($include_plugin_path.'/index.php')){
				include ($include_plugin_path.'/index.php');
			}
		}
	}
}

?>