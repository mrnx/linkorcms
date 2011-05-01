<?php

# LinkorCMS
# � 2006-2009 �������� ��������� ���������� (linkorcms@yandex.ru)
# ����: admin.php
# ����������: ������� �������� �����-������

define('ADMIN_SCRIPT', true);
define('VALID_RUN', true);

require 'config/init.php'; // ������������ � �������������
define('ADMIN_FILE', System::$config['admin_file']); // ������ �� �����-������

// ������������
include_once $config['inc_dir'].'admin_template.class.php';
$site = new AdminPage();

// �������� ������������
if(!($userAuth === 1 && $userAccess === 1 && isset($_COOKIE['admin']) && System::user()->AllowCookie('admin', true))){
	if(isset($_POST['admin_login'])){ // �������� ������-������
		$admin_name = SafeEnv($_POST['admin_name'], 255, str);
		$admin_password = SafeEnv($_POST['admin_password'], 255, str);
		$a = System::user()->Login($admin_name, $admin_password, false, true);
		if($a === true && System::user()->SecondLoginAdmin){
			System::user()->SetAdminCookie($admin_name, $admin_password);
		}else{
			System::user()->UnsetCookie('admin');
			System::admin()->Login('�������� ����� ��� ������'); // exit
		}
	}else{ // ����� �����������
		System::admin()->Login(); // exit
	}
}

// �������� ������������ �� setup.php �� �������
if(is_file('setup.php') && !is_file('dev.php')){
	exit('<html>'."\n".'<head>'."\n".'	<title>'.CMS_NAME.' - !!!������!!!</title>'."\n".'</head>'."\n".'<body>'."\n".'	<center><h2>������� setup.php � �������.</h2>
		<br />
		����� ������ �������������.
		<br />
		����������� <b>setup.php</b> �� ������� ������ ����<br />
		��������, �������, ����� ��� ��� ������ ������,<br />
		������������� ��� <strong>�������</strong>.</center>'."\n".'</body>'."\n".'</html>');
}

System::admin()->InitPage();

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
		GO(Ufu('index.php')); // exit
	}
}
System::db()->Select('modules', "`enabled`='1' and `folder`='$ModuleName'");

// ���������� ����� ������?
if(System::db()->NumRows() == 0){
	System::admin()->AddAdminMenu();
	System::admin()->AddTextBox('����� ������ - ������ �� ������', '<div style="text-align: center;">������ "'.$ModuleName.'" �� ������!</div>');
	System::admin()->TEcho();
	exit;
}

// �������� �� ������
if(!System::user()->CheckAccess2($ModuleName, $ModuleName)){
	System::admin()->AddTextBox('������', $ModuleName.' ������ ������!');
	System::admin()->TEcho();
	exit;
}

// ��������������� ���������
define('MOD_DIR', System::$config['mod_dir'].$ModuleName.'/');
define('MOD_FILE', MOD_DIR.'admin.php');

// ���������� ������
if(is_file(MOD_FILE)){
	System::admin()->AddAdminMenu();
	require MOD_FILE;
}

// ����� ������
System::admin()->TEcho();

?>