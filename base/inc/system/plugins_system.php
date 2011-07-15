<?php

/**
 * ������� ��� ������ � ���������� ���������.
 */

/**
 * ���������� ������ ��������� ��������.
 * @param  $group ������
 * @param string $function ���������(���� ����)
 * @param bool $return ���������� ����� ������ �������� ������ �� ��������������� �����������
 * @param bool $return_full ���������� ������ ���� ������ ������ � ������ ����������� �� ��������
 * @return array
 */
function SystemPluginsIncludeGroup($group, $function = '', $return = false, $return_full = false){
	global $config;
	$result = array();

	// ����� ��������
	$group_dir = $config['sys_plug_dir'].$group;
	if(!is_dir($group_dir)){
		return array();
	}
	$plugins = GetFolders($group_dir.'/');

	// �������������� ���������
	foreach($plugins as $plugin){
		if($function != ''){
			$plugin_name = RealPath2($group_dir.'/'.$plugin.'/'.$function).'/';
		}else{
			$plugin_name = RealPath2($group_dir.'/'.$plugin).'/';
		}
		global $include_plugin_path; // ��� ���������� ����� �������� �� �������
		$include_plugin_path = $plugin_name;
		if($return){
			$result[] = $include_plugin_path;
		}else{
			include $include_plugin_path.'index.php';
		}
	}
	return $result;
}
