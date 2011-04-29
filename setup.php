<?php

# LinkorCMS
# � 2006-2009 �������� ��������� ���������� (linkorcms@yandex.ru)
# ����: index.php
# ����������: �����������

define("SETUP_SCRIPT", true);
define("VALID_RUN", true);

@set_time_limit(600);
include_once('config/init.php');

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

?>