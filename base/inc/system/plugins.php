<?php

define('PLUGINS', true);

// ���� ��������
define('PLUG_AUTORUN', 1); // ���������� (����� ����� � config/init.php)
define('PLUG_ADMIN_AUTORUN', 2); // ���������� ������ � �������
define('PLUG_MAIN_AUTORUN', 3); // ���������� ������ �� �����

define('PLUG_CALLEE', 4); // ���������� �������� ����� index.php&name=plugins&p=plugin_name (����� ����� � modules/plugins/index.php)

define('PLUG_MANUAL', 5); // ����� ��� ������ ������������ ������ � ������������ �������. ���������� ������.
define('PLUG_MANUAL_ONE', 7); // ������������ ���� �����-�� ������ �� ������. ���������� ������.

/**
 * ������� ��� ��������
 * @return void
 */
function PluginsClearCache(){
	$cache = LmFileCache::Instance();
	$cache->Delete('system', 'plugins_auto_main');
	$cache->Delete('system', 'plugins_auto_admin');
}

/**
 * ���������� ������ ��������
 * @param $group ������
 * @param string $function ���������
 * @param bool $return ���������� ����� ������ �������� ������ ��������������� ����������
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
				global $include_plugin_path; // ��� ���������� ����� �������� �� �������
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
