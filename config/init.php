<?php

# LinkorCMS
# � 2006-2010 �������� ��������� ���������� (linkorcms@yandex.ru)
# ����: init.php
# ����������: ���� �������������

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

@error_reporting(E_ALL);
@ini_set('display_errors', true);
@ini_set('html_errors', false);
@ini_set('error_reporting', E_ALL);

umask(0); // �� ��������� ����� ����� ����������� � ������� 0666, ����� � ������� 0777

// �������� ����� ������ ���������� �������
define('SCRIPT_START_TIME', microtime(true));

// �������������� ������������ (���������������� ���������)
require 'config/config.php';

// ��������� ������� LinkorCMS
require 'config/version.php';
if(isset($_GET['checklcsite'])){
	exit(CMS_VERSION_STR);
}

// �������� ������ ��������������
if(version_compare(phpversion(), '5.0.0', '<')){
	exit('<html>
		<head>
			<title>'.CMS_NAME.' - ������!</title>
		</head>
		<body>
			<center><h2>'.CMS_NAME.': ��������� ������ PHP >= 5.0.0.</h2>
				�� ����������� PHP '.phpversion().'.</center>
		</body>
		</html>');
}

// �������� register_globals = off ////////////////////////////////////////////////////////////////
if(ini_get('register_globals') == 1){
	foreach($GLOBALS as $key=>$value){
		if($key != 'GLOBALS'
		   and $key != 'key'
		   and $key != '_REQUEST'
		   and $key != '_GET'
		   and $key != '_POST'
		   and $key != '_COOKIE'
		   and $key != '_SESSION'
		   and $key != '_FILES'
		   and $key != '_ENV'
		   and $key != '_SERVER')
		{
			unset($GLOBALS[$key]);
		}
	}
	unset($key);
}

// �������� magic_quotes_gpc = off
if(get_magic_quotes_gpc()){
	function hstripslashes( $var ){
		return (is_array($var) ? array_map('hstripslashes', $var) : stripslashes($var));
	}
	$_POST = array_map('hstripslashes', $_POST);
	$_GET = array_map('hstripslashes', $_GET);
}

// ����������� ������ /////////////////////////////////////////////////////////////////////////////
ob_start();

// ���������� ����������
$db = null;
$user = null;
$site = null;
$config = array();
$plug_config = array();
$system = array('no_templates'=>false, 'no_messages'=>false, 'no_echo'=>false, 'stop_hit'=>false);
$SiteLog = null;
$ErrorsLog = null;
$SITE_ERRORS = true;
$userAuth = false;
$userAccess = 4;

require 'config/name_config.php'; // ������������ ������������
require $config['inc_dir'].'system.php'; // �������

// ��������� ������
set_error_handler('ErrorHandler');

// ����
$SiteLog = new Logi($config['log_dir'].'site.log.php');
$ErrorsLog = new Logi($config['log_dir'].'errors.log.php');

if(is_file('config/db_config.php')){ // ������� �����������

	// ��������� ������������
	require 'config/db_config.php';
	require 'config/salt.php';

	// ��������� ������ ���� ������
	if(!defined('SETUP_SCRIPT') && substr($config['db_version'], 0, 3) != substr(CMS_VERSION, 0, 3)){
		exit('<html><head><title>������</title></head><body><center><h2>��������� ���������� ���� ������.</h2></center></body></html>');
	}

	// ����������� � ���� ������
	define("DATABASE", true);
	IncludeSystemPluginsGroup('database', 'layer'); // ����������� �������� ���� ������
	if(method_exists($db, 'Connect')){
		$db->ErrorReporting = $config["db_errors"];
		$db->Prefix = $config['db_pref'];
		$db->Connect($config["db_host"], $config["db_user"], $config["db_pass"], $config["db_name"]);
		if(!$db->Connected){
			exit('<html><head><title>������</title></head><body><center>�������� � ����� ������, ��������� ��������� ���� ������.</center></body></html>');
		}
	} else{
		exit('<html><head><title>������</title></head><body><center>�������� � ������������ �������� ���� ������.</center></body></html>');
	}

	// �������� ������������ �����
	LoadSiteConfig($config);
	LoadSiteConfig($plug_config, 'plugins_config', 'plugins_config_groups');

	// ��������������
	include('config/autoupdate.php');

	// ������������� ��������� ���� ����� �� ���������
	SetDefaultTimezone();

	// ������ � ������������
	$user = new User();
	$userAuth = IntVal($user->Get('u_auth'));
	$userAccess = IntVal($user->Get('u_level'));

	// ���������� �������(� �����������)
	if(defined('MAIN_SCRIPT') || defined('ADMIN_SCRIPT')){
		$pcache = LmFileCache::Instance();
		if(defined('MAIN_SCRIPT')){
			$pcache_name = 'plugins_auto_main';
		}elseif(defined('ADMIN_SCRIPT')){
			$pcache_name = 'plugins_auto_admin';
		}
		if($pcache->HasCache('system', $pcache_name)){
			$plugins = $pcache->Get('system', $pcache_name);
		}else{
			if(defined('MAIN_SCRIPT')){
				$q = "`type`= 1 or `type`= 3";
			} elseif(defined('ADMIN_SCRIPT')){
				$q = "`type`= 1 or `type`= 2";
			}
			$plugins = $db->Select('plugins', $q);
			$pcache->Write('system', $pcache_name, $plugins);
		}
		foreach($plugins as $plugin){
			$PluginName = $config['plug_dir'].SafeDB(RealPath2($plugin['name']), 255, str);
			if(file_exists($PluginName.'/index.php') && is_dir($PluginName)){
				include $PluginName.'/index.php';
			} else{
				UninstallPlugin($plugin['name']);
			}
		}
	}
}elseif(!defined('SETUP_SCRIPT')){ // ������� �� �����������
	Header("Location: setup.php");
	exit();
}else{ // ��� ���������
	$user = new User();
}

?>