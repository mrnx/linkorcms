<?php

# LinkorCMS
# � 2006-2009 �������� ��������� ���������� (linkorcms@yandex.ru)
# ����: admin.php
# ����������: ������� �������� �����-������

define('ADMIN_SCRIPT', true);
define('VALID_RUN', true);

include_once('config/init.php'); // ������������ � �������������
define('ADMIN_FILE', System::$config['admin_file']); // ������ �� �����-������

// �������� ������������
if(!($userAuth === 1 && $userAccess === 1 && isset($_COOKIE['admin']) && $user->AllowCookie('admin', true))){
	if(isset($_POST['admin_login'])){ // �������� ������-������
		$admin_name = SafeEnv($_POST['admin_name'], 255, str);
		$admin_password = SafeEnv($_POST['admin_password'], 255, str);
		$a = $user->Login($admin_name, $admin_password, false, true);
		if($a === true && $user->SecondLoginAdmin){
			$user->SetAdminCookie($admin_name, $admin_password);
		}else{
			$user->UnsetCookie('admin');
			include_once $config['inc_dir'].'template.login.php';
			AdminShowLogin('�������� ����� ��� ������'); // exit
		}
	}else{ // ����� �����������
		include_once $config['inc_dir'].'template.login.php';
		AdminShowLogin(); // exit
	}
}

// �������� ��� ������
$ModuleName = '';
if(!isset($_GET['exe'])){
	define('INDEX_PHP', true); // ������ �� ������� ��������
	$ModuleName = 'adminpanel';
}else{
	define('INDEX_PHP', false);
	$ModuleName = SafeEnv($_GET['exe'], 255, str);
	if($ModuleName == 'exit'){ // �����
		$user->UnsetCookie('admin');
		GO(Ufu('index.php'));
	}
}
$db->Select('modules', "`enabled`='1' and `folder`='$ModuleName'");

// ���������� ����� ������?
if($db->NumRows() == 0){
	System::admin()->AddAdminMenu();
	System::admin()->AddTextBox('����� ������ - ������ �� ������', '<div style="text-align: center;">������ "'.$exe.'" �� ������!</div>');
	System::admin()->TEcho();
	exit;
}

// �������� �� ������
if($user->CheckAccess2($ModuleName, $ModuleName)){
	AddTextBox('������', $config['general']['admin_accd']);
	return;
}

// ��������������� ���������
define('MOD_DIR', $config['mod_dir'].$ModuleName.'/');
define('MOD_FILE', MOD_DIR.'admin.php');

// ������������
include_once $config['inc_dir'].'admin_template.class.php';

// ���������� ������
if(is_file(MOD_FILE)){
	System::admin()->AddAdminMenu();
	require MOD_FILE;
}

// ����� ������
System::admin()->TEcho();

?>