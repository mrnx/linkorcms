<?php

/*
 * LinkorCMS 1.4
 * � 2011 LinkorCMS Development Group
 */

define('MAIN_SCRIPT', true);
define('VALID_RUN', true);

require 'config/init.php'; // ������������ � �������������

// ���
if($config['general']['ufu'] && isset($_GET['ufu'])){
	$_GET = UfuRewrite($_GET['ufu']);
}

// ������� ���� ��� �������������
if($config['general']['private_site'] && $user->AccessLevel() != 1){
	include_once($config['inc_dir'].'template.login.php');
	AdminShowLogin('���� ������ ��� �������������');
}

// �������� ��� ������
$ModuleName = '';
if(!isset($_GET['name'])){
	define('INDEX_PHP', true); // ������ �� ������� ��������
	$ModuleName = SafeEnv($config['general']['site_module'], 255, str, false, false);
}else{
	define('INDEX_PHP', false);
	$ModuleName = SafeEnv($_GET['name'], 255, str);
}
System::database()->Select('modules', "`enabled`='1' and `folder`='$ModuleName'"); // ��������� �������� �� ������ ������

// ���������� ����� ������?
if(System::database()->NumRows() == 0){
	System::site()->InitPage();
	System::site()->AddTextBox('������', '<center>������ �������� ('.SafeDB($ModuleName, 255, str).') �� ���������� ��� �� �������� � ������ ������.</center>');
	System::site()->TEcho();
	exit;
}

// �������� �� ������
$mod = System::database()->FetchRow();
if(!System::user()->AccessIsResolved($mod['view'], $userAccess)){
	System::site()->InitPage();
	System::site()->AddTextBox('������', '<center>������ ��������.</center>');
	System::site()->TEcho();
	exit;
}

// ��������������� ���������
define('MOD_DIR', $config['mod_dir'].$ModuleName.'/');
define('MOD_FILE', MOD_DIR.'index.php');
define('MOD_INIT', MOD_DIR.'init.php');
define('MOD_THEME', RealPath2(SafeDB($mod['theme'], 255, str)));

// ������������� ������
$valid_init = file_exists(MOD_INIT);
if($valid_init){
	include MOD_INIT;
	if(function_exists('mod_initialization')){
		mod_initialization();
	}
}

// ������������
if(!$system['no_templates']){
	System::site()->InitPage();
	$initfile = System::site()->Root.'init.php';
	if(file_exists($initfile)){
		include $initfile;
	}
}

// ���������
if(!$system['no_messages']){
	include_once(System::config('inc_dir').'messages.inc.php');
}

// ���������� ������
require MOD_FILE;

// ��������� �����
if(!$system['no_messages']){
	BottomMessages();
}

// ����� ������ ������������
if(!$system['no_echo']){
	System::site()->TEcho();
}

// ����������� ������
if($valid_init){
	if(function_exists('mod_finalization')){
		mod_finalization();
	}
}
