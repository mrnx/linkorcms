<?php

function getconf_MainModules( $name )
//� $name ��� �������� ��������� ��������� ������� ��� ������ ����������
{
	global $config, $db;
	$mods = $db->Select('modules', '`system`=\'0\'');
	$r = array();
	for($i = 0, $cnt = count($mods); $i < $cnt; $i++){
		//1 �������� ��������,
		//2 �������� ������� ������� ����� ������ ������������
		$r[] = array($mods[$i]['folder'], $mods[$i]['name']);
	}
	return $r;
}

?>