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
	$cache->Delete('system', 'plugins');
	$cache->Delete('system', 'plugins_auto_main');
	$cache->Delete('system', 'plugins_auto_admin');
}

/**
 * ���������� ������ ��������
 * @param $group ������
 * @param string $function ���������
 * @param bool $return ���������� ���� � ������ �������� ������ ��������������� ����������
 * @return array
 */
function IncludePluginsGroup($group, $function = '', $return = false){
	global $config, $db; // � ��������� ������ ��������
	global $include_plugin_path; // ��� ���������� ����� �������� �� �������
	static $plugins = null, $plug_dir;
	$plug_dir = $config['plug_dir'];
	if(LmFileCache::Instance()->HasCache('system', 'plugins')){
		$plugins = LmFileCache::Instance()->Get('system', 'plugins');
	}
	if(!isset($plugins)){
		$plugins = System::database()->Select('plugins', "(`type`='5' or `type`='7') and `enabled`='1'");
		LmFileCache::Instance()->Write('system', 'plugins', $plugins, Day2Sec);
	}
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
