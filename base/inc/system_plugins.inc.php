<?php

# LinkorCMS
# � 2006-2010 �������� ��������� ���������� (linkorcms@yandex.ru)
# ����: system_plugins.inc.php
# ����������: ��������� ��������

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

define('PLUGINS', true);
define('PLUG_AUTORUN', 1); //����������
define('PLUG_ADMIN_AUTORUN', 2); //���������� ������ � �������
define('PLUG_MAIN_AUTORUN', 3); //���������� ������ �� �������
define('PLUG_CALLEE', 4); //���������� �������� ����� index.php&name=plugins&p=plugin_name
define('PLUG_MANUAL', 5); //����� ��� ������ ������������ ������ � ������������ �������. ���������� ������.
define('PLUG_MANUAL_ONE', 7); //������������ ���� �����-�� ������ �� ������. ���������� ������.
define('PLUG_SYSTEM', 8); //��������� ������, �� ������� �����������, ���������� ������ �������
// � ����� �������������� ����������� �� ���� ����������� �������

$plug_config = array();

function PluginsClearCache()
{
	$cache = LmFileCache::Instance();
	$cache->Delete('system', 'plugins');
	$cache->Delete('system', 'plugins_auto_main');
	$cache->Delete('system', 'plugins_auto_admin');
	$cache->Delete('system', 'plugins_load');
}

function LoadPlugins( $ClearCache = false )
{
	global $config;
	static $resultcache = null;

	if($ClearCache){
		$resultcache = null;
		PluginsClearCache();
	}

	if($resultcache != null){
		return $resultcache;
	}

	$cache = LmFileCache::Instance();
	if($cache->HasCache('system', 'plugins_load')){
		$resultcache = $cache->Get('system', 'plugins_load');
		return $resultcache;
	}

	$plugins = array();
	$folder = $config['plug_dir'];
	$dir = @opendir($folder);
	while($file = @readdir($dir)){
		if($file != '.' && $file != '..' && is_dir($folder.$file) && is_file($folder.$file.'/info.php')){
			include(RealPath2($folder.$file.'/info.php'));
		}
	}

	// ������� (��������� ����������)
	foreach($plugins as $name=>$plugin){
		$plugins[$name]['group'] = '';
		$plugins[$name]['name'] = $name;
	}
	$result['plugins'] = $plugins;

	// ������ (����� �������� ������) $groups ����������� �� ������ info.php
	foreach($groups as $name=>$group){
		$plugins = array();
		$folder = $config['plug_dir'].$name.'/';
		$dir = opendir($folder);
		while($file = readdir($dir)){
			if($file != '.' && $file != '..' && is_dir($folder.$file) && is_file($folder.$file.'/info.php')){
				include(RealPath2($folder.$file.'/info.php'));
			}
		}
		foreach($plugins as $pname=>$plugin){
			$plugins[$pname]['group'] = $name;
			$plugins[$pname]['name'] = $pname;
		}
		$groups[$name]['plugins'] = $plugins;
	}
	$result['groups'] = $groups;

	$resultcache = &$result;
	$cache->Write('system', 'plugins_load', $result, Day2Sec);
	return $result;
}

// ���������� ������ ��������
# $function - ��� ������ ����� ��� ��������� ��������
function IncludeSystemPluginsGroup( $group, $function = '', $return = false, $return_full = false )
{
	global $config;
	$plugins = LoadPlugins();
	$result = array();
	if(isset($plugins['groups'][$group])){
		$plugins = $plugins['groups'][$group]['plugins'];
		foreach($plugins as $plugin){
			if(($function == '') || (isset($plugin['function']) && $function == $plugin['function'])){
				global $include_plugin_path; // ��� ���������� ����� �������� �� �������
				$include_plugin_path = RealPath2($config['plug_dir'].$group.'/'.$plugin['name'].'/');
				if($return){
					if($return_full){
						$plugin['path'] = $include_plugin_path;
						$result[] = $plugin;
					}else{
						$result[] = $include_plugin_path;
					}
				}else{
					include ($include_plugin_path.'index.php');
				}
			}
		}
	}
	return $result;
}
?>