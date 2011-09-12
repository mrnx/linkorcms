<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('��������� �����');

if(!$user->CheckAccess2('config', 'config')){
	AddTextBox('������', '������ ��������!');
	return;
}

include_once ($config['inc_dir'].'configuration/functions.php');

if(isset($_GET['a'])){
	$action = $_GET['a'];
}else{
	$action = 'general';
}
$titles = array(
	'general'=>'�������� ����������',
	'debug'=>'�������',
	'comments'=>'�����������',
	'security'=>'������������',
	'meta_tags'=>'���� ����',
	'smtp'=>'��������� SMTP',
	'admin_panel'=>'�����-������',
);
TAddToolLink($titles['general'], 'general', 'config&a=general');
TAddToolLink($titles['debug'], 'debug', 'config&a=debug');
TAddToolLink($titles['comments'], 'comments', 'config&a=comments');
TAddToolLink($titles['security'], 'security', 'config&a=security');
TAddToolLink($titles['meta_tags'], 'meta_tags', 'config&a=meta_tags');
TAddToolLink($titles['smtp'], 'smtp', 'config&a=smtp');
TAddToolLink($titles['admin_panel'], 'admin_panel', 'config&a=admin_panel');
TAddToolBox($action, '��������� �����');

if($action != 'save'){
	System::admin()->AddCenterBox('��������� ����� - '.$titles[$action]);
	if(isset($_GET['saveok'])){
		System::admin()->Highlight('��������� ���������.');
	}
	System::admin()->ConfigGroups($action);
	System::admin()->AddConfigsForm(ADMIN_FILE.'?exe=config&a=save&group='.SafeDB($action, 255, str));
}else{
	$Groups = $_GET['group'];
	System::admin()->SaveConfigs($Groups);
	GO(ADMIN_FILE.'?exe=config&a='.$Groups.'&saveok');
}
