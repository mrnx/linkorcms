<?php

/*
 * LinkorCMS 1.4
 * � 2011 LinkorCMS Development Group
 */

define('ADMIN_SCRIPT', true);
define('VALID_RUN', true);

require 'config/init.php'; // ������������ � �������������
define('ADMIN_FILE', System::config('admin_file')); // ������ �� �����-������

// �������� ������������
if(!($userAuth === 1 && $userAccess === 1 && System::user()->AllowCookie(System::user()->AdminCookieName, true))){
	if(isset($_POST['admin_login'])){ // �������� ������-������
		$admin_name = $_POST['admin_name'];
		$admin_password = $_POST['admin_password'];
		$a = System::user()->Login($admin_name, $admin_password, false, true);
		if($a === true && System::user()->SecondLoginAdmin){
			System::user()->SetAdminCookie($admin_name, $admin_password);
		}else{
			System::user()->UnsetCookie(System::user()->AdminCookieName);
			System::admin()->Login('�������� ����� ��� ������.'); // exit
		}
	}else{ // ����� �����������
		System::admin()->Login(); // exit
	}
}

// ��������, ������������ �� setup.php �� �������
if(is_file('setup.php') && !is_file('dev.php')){
	exit('<html>'."\n".'<head>'."\n".'	<title>'.CMS_NAME.' - !!!������!!!</title>'."\n".'</head>'."\n".'<body>'."\n".'	<center><h2>������� setup.php � �������.</h2>
		<br />
		�����-������ �������������.
		<br />
		����������� <b>setup.php</b> �� ������� ������ ����<br />
		��������, �������, ����� ��� ��� ������ ������,<br />
		������������� ��� <strong>�������</strong>.</center>'."\n".'</body>'."\n".'</html>');
}

System::admin()->InitPage();
define('INDEX_PHP', false);

// �������� ��� ������
$ModuleName = '';
if(!isset($_GET['exe'])){
	$ModuleName = 'adminpanel';
}else{
	$ModuleName = SafeEnv($_GET['exe'], 255, str);
	if($ModuleName == 'exit'){ // �����
		System::user()->UnsetCookie(System::user()->AdminCookieName);
		GO(Ufu('index.php')); // exit
	}
}
System::database()->Select('modules', "`enabled`='1' and `folder`='$ModuleName'");

// ���������� ����� ������?
if(System::database()->NumRows() == 0){
	System::admin()->AddAdminMenu();
	System::admin()->AddTextBox('�����-������ - ������ �� ������', '<div style="text-align: center;">������ "'.$ModuleName.'" �� ������!</div>');
	System::admin()->TEcho();
	exit;
}else{
	System::admin()->Mod = SafeDB(System::database()->FetchRow(), 255, str);
}

// �������� �� ������
if(!System::user()->CheckAccess2($ModuleName, $ModuleName)){
	System::admin()->AddTextBox('������', $ModuleName.' ������ ������!');
	System::admin()->TEcho();
	exit;
}

// ��������������� ���������
define('MOD_DIR', System::config('mod_dir').$ModuleName.'/');
define('MOD_FILE', MOD_DIR.'admin.php');

// ���������� ������
if(is_file(MOD_FILE)){
	System::admin()->AddAdminMenu();
	require MOD_FILE;
}

// ����� ������
System::admin()->TEcho();
