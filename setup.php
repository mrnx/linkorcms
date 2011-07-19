<?php

/*
 * LinkorCMS 1.4
 * � 2011 LinkorCMS Development Group
 */

define("SETUP_SCRIPT", true);
define("VALID_RUN", true);

@set_time_limit(600);

// ���������� ������������
if(is_file('config/setup_lock.php') && !is_file('dev.php')){
	exit('<html><head><title>������!</title></head><body><center><h2>������� ��� �����������.</h2><br />
		����������� ������������.<br />
		��� ������������� ������� ������� ����� <strong>config/db_config.php</strong> � <strong>config/setup_lock.php</strong>.</center>
	</body></html>');
}

require 'config/init.php';

$default_prefix = 'table';
$bases_path = 'setup/bases/';
$info_ext = '.MYD';
$data_ext = '.FRM';

$config['s_dir'] = 'setup/';
$config['s_plug_dir'] = 'setup/plugins/';
$config['s_inc_dir'] = 'setup/inc/';
$config['s_lng_dir'] = 'setup/lng/';
$config['s_mod_dir'] = 'setup/mods/';
$config['s_tpl_dir'] = 'setup/template/';

include_once($config['s_inc_dir'].'functions.php');
include_once($config['s_inc_dir'].'template.php');// ������
$site->AddJSFile($config['s_inc_dir'].'functions.js', true, true);
include_once($config['s_inc_dir'].'setup.class.php'); // ����� ���������� �������������
include_once($config['s_inc_dir'].'plugins.php'); // ��������� ��������
include_once($config['s_lng_dir'].'lang-russian.php'); // ���������������

if(isset($_GET['mod'])){
	$mod = SafeEnv($_GET['mod'], 255, str);
}else{
	$mod = '';
}
$setup->Page($mod);
