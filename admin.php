<?php

# LinkorCMS
# � 2006-2009 �������� ��������� ���������� (linkorcms@yandex.ru)
# ����: admin.php
# ����������: ������� �������� �����-������

define('ADMIN_SCRIPT', true);
define('VALID_RUN', true);

include_once('config/init.php'); // ������������ � �������������
include_once($config['inc_dir'].'system_plugins.inc.php'); // ��������� �������
include_once($config['inc_dir'].'system.php'); // �������
include_once($config['inc_dir'].'database.php'); // ����������� � ���� ������

// �������� ������������ �����
LoadSiteConfig($config);
LoadSiteConfig($plug_config, 'plugins_config', 'plugins_config_groups');

// ��������������
include('config/autoupdate.php');

// ������������� ��������� ���� �� ���������
SetDefaultTimezone();

// ������
include_once($config['inc_dir'].'user.class.php');

// �������
include_once($config['inc_dir'].'plugins.inc.php');

// �������� ������������
if($userAuth === 1 && $userAccess === 1 && isset($_COOKIE['admin']) && $user->AllowCookie('admin', true)){ // ������������ ����������� � �����-������
	if(isset($_GET['exe'])){
		$exe = SafeEnv($_GET['exe'], 255, str);
	}else{
		$exe = 'adminpanel';
	}

	if($exe == 'exit'){ // �����
		$user->UnsetCookie('admin');
		GO(Ufu('index.php'));
	}

	// ��������������� ���������
	define('MOD_DIR', $config['mod_dir'].$exe.'/');
	define('MOD_FILE', MOD_DIR.'admin.php');
	define('ADMIN_FILE', System::$config['admin_file']); // ������ �� �����-������
	define('ADMIN_AJAX', IsAjax()); // ������� �������, ��� ������ ��������� c ������� ajax
	define('ADMIN_AJAX_LINKS', System::$config['admin_panel']['enable_ajax'] ? 'true' : 'false'); // ������� ������� ��� �����-������ �������� � ������ AJAX

	// ������������ � �������
	include_once $config['apanel_dir'].'template.php';
	include_once $config['apanel_dir'].'functions.php';

	$db->Select('modules', "`enabled`='1' and `folder`='$exe'");
	if($db->NumRows() > 0){
		// ���������� ������
		if(is_file(MOD_FILE)){
			System::admin()->AddAdminMenu();
			include MOD_FILE;
		}
		// ����� ������
		System::admin()->TEcho();
	}else{
		System::admin()->AddAdminMenu();
		AddTextBox('����� ������ - ������', '<div style="text-align: center;">������ "'.$exe.'" �� ������!</div>');
		System::admin()->TEcho();
	}
}else{
	if(isset($_POST['admin_login'])){ // �������� ������-������
		$admin_name = SafeEnv($_POST['admin_name'], 255, str);
		$admin_password = SafeEnv($_POST['admin_password'], 255, str);
		$a = $user->Login($admin_name, $admin_password, false, true);
		if($a === true && $user->SecondLoginAdmin){
			$user->SetAdminCookie($admin_name, $admin_password);
			GoRefererUrl($_GET['_back']);
		}else{
			$user->UnsetCookie('admin');
			include_once $config['apanel_dir'].'template.login.php';
			AdminShowLogin('�������� ����� ��� ������');
		}
	}else{ // ����� �����������
		include_once $config['apanel_dir'].'template.login.php';
		AdminShowLogin();
	}
}

?>