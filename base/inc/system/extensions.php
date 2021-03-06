<?php

/**
 * ������� ��� ������ � ������������.
 */

// ��������� ��� ����� ����������
define('EXT_MODULE', '1');
define('EXT_PLUGIN', '2');
define('EXT_BLOCK', '3');
define('EXT_TEMPLATE', '4');

/**
 * ��������� ���������� � ���������� � ���������� ������
 * @param $ExtPath ���� � ����� � �������������� ������
 * @return array
 */
function ExtLoadInfo( $ExtPath ){
	$result = false;
	$infoFile = RealPath2($ExtPath.'/info.php');
	if(is_file($infoFile)){ // ��������� ���� �� PHP �����
		$result = include $infoFile;
		if(!is_array($result)){
			if(isset($module)){ // ������ ������ ������
				foreach($module as $module){}
				return array(
					'name' => $module['name'],
					'description' => $module['comment'],
					'author' => $module['copyright'],
					'site' => '',
					'version' => '',
					'icon' => '',
					'1.3' => true
				);
			}elseif(isset($plugins)){ // ������ ������ �������
				foreach($plugins as $plugins){}
				return array(
					'name' => $plugins['name-ru'],
					'description' => $plugins['description-ru'],
					'author' => $plugins['author'],
					'site' => $plugins['site'],
					'version' => $plugins['version'],
					'icon' => '',
					'1.3' => true
				);
			}elseif(isset($groups)){ // ������ 1.3
				foreach($groups as $groups){}
				return array(
					'name' => $groups['name-ru'],
					'description' => $groups['description-ru'],
					'author' => '',
					'site' => '',
					'version' => '',
					'icon' => '',
					'1.3' => true,
					'1.3_old_plugins_group' => true,
					'1.3_old_plugins_group_type' => $groups['type']
				);
			}
		}
		return $result;
	}
	$infoXML = RealPath2($ExtPath.'/info.xml');
	if(is_file($infoXML)){ // ��������� ���� �� XML �����(��� ��� ����������)
		$info = simplexml_load_file($infoXML);
		$result = get_object_vars($info);
		foreach($result as $f=>&$v) {
			$result[$f] = Utf8ToCp1251($v);
		}
		return $result;
	}else{
		return false;
	}
}

/**
 * ����������� ������ � ��.
 * ������������� ������������ ��� ������� ������ ������� �������� ��������� � ���� ������.
 * @param string $Name ��� ������
 * @param string $Folder ��� ����� ������ � ���������� �������
 * @param string $IsIndex ������ �������� �� index.php (1|0)
 * @param string $View ������� ��������� (1|2|3|4)
 * @param string $Enabled ������� (1|0)
 * @return void
 */
function ExtInstallModule( $Name, $Folder, $IsIndex, $View, $Enabled = '1' ){
	$Name = SafeEnv($Name, 255, str);
	$Folder = SafeEnv($Folder, 255, str);
	$IsIndex = SafeEnv($IsIndex, 1, int);
	$View = SafeEnv($View, 1, int);
	$Enabled = SafeEnv($Enabled, 1, int);
	System::database()->Insert('modules',"'','$Name','$Folder','0','$IsIndex','','','$View','$Enabled','0','1',''");
}

/**
 * ������� ����������� ������ �� ���� ������.
 * @param string $Folder ��� ����� ������ � ���������� �������
 * @return void
 */
function ExtDeleteModule( $Folder ){
	$Folder = SafeEnv($Folder, 255, str);
	System::database()->Delete('modules', "`folder`='$Folder'");
}

/**
 * ����������� ������� � ��.
 * ������������� ������������ ��� ������� ������ ������� �������� ��������� � ���� ������.
 * @param string $Group ��� ������, ����� ���� ������ ���� ������ �� ������ � ������
 * @param string $Name ��� ����� ������� � ���������� �������� ��� ���������� ������ ��������
 * @param string $Function ������� �������. ������� � �������� ����� ���� ������� �� ��������
 * @param string $Type ��� �������
 * @param string $Enabled �������
 * @return void
 */
function ExtInstallPlugin( $Group, $Name, $Function, $Type, $Enabled = '1' ){
	$Group = SafeEnv($Group, 250, str);
	$Name = SafeEnv($Name, 255, str);
	$Function = SafeEnv($Function, 255, str);
	$Type = SafeEnv($Type, 1, int);
	$Enabled = SafeEnv($Enabled, 1, int);
	if($Type == PLUG_MANUAL_ONE && $Enabled == '1'){ // ��������� ��� ����� � �����-�� �������
		System::database()->Update('plugins', "`enabled`='0'", "`group`='$Group'");
	}
	System::database()->Insert('plugins',"'','$Name','$Function','','$Type','$Group','0','$Enabled'");
	PluginsClearCache();
}

/**
 * ������� ����������� ������� �� ���� ������.
 * @param string $Group ��� ������ ������� (���� ����)
 * @param string $Name ��� �������
 * @return void
 */
function ExtDeletePlugin( $Group, $Name ){
	$Group = SafeEnv($Group, 250, str);
	$Name = SafeEnv($Name, 255, str);
	System::database()->Delete('plugins', "`name`='$Name' and `group`='$Group'");
	PluginsClearCache();
}

/**
 * ����������� ����� � ��.
 * ������������� ������������ ��� ������� ������ ������� �������� ��������� � ���� ������.
 * @param string $Name ��� ���� �����
 * @param string $Folder ��� ����� ����� � ���������� ������
 * @return void
 */
function ExtInstallBlock( $Name, $Folder ){
	$Name = SafeEnv($Name, 255, str);
	$Folder = SafeEnv($Folder, 255, str);
	System::database()->Insert('block_types',"'','$Name','','$Folder'");
}

/**
 * ������� ����������� ����� �� ���� ������.
 * @param string $Folder ��� ����� ����� � ���������� ������
 * @return void
 */
function ExtDeleteBlock( $Folder ){
	$Folder = SafeEnv($Folder, 255, str);
	System::database()->Delete('block_types', "`folder`='$Folder'");
}

/**
 * ����������� ������� � ��.
 * ������������� ������������ ��� ������� ������ ������� �������� ��������� � ���� ������.
 * @param string $Folder ��� ����� ������� � ���������� ��������
 * @param string $Admin ������ ��� �����-������
 * @return void
 */
function ExtInstallTemplate( $Name, $Folder, $Admin = '0' ){
	$Name = SafeEnv($Name, 255, str);
	$Folder = SafeEnv($Folder, 255, str);
	$Admin = SafeEnv($Admin, 1, int);
	System::database()->Insert('templates',"'','$Name','$Folder','$Admin','0'");
}

/**
 * ������� ����������� ������� �� ���� ������.
 * @param string $Folder ��� ����� ������� � ���������� ��������
 * @param bool $DelFiles ������� ��� ����� �������
 * @return void
 */
function ExtDeleteTemplate( $Folder, $DelFiles = false ){
	$FolderEnv = SafeEnv($Folder, 255, str);
	System::database()->Delete('templates', "`folder`='$FolderEnv'");
	if($DelFiles){ // ������� ����� �������
		RmDirRecursive(RealPath2(System::config('tpl_dir').$Folder));
	}
}
