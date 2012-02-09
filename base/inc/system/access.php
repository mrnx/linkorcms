<?php

/**
 * ������ �������
 */

define('ACCESS_ADMIN', 1);
define('ACCESS_MEMBER', 2);
define('ACCESS_GUEST', 3);
define('ACCESS_ALL', 4);

/**
 * ���������� ��� ������� ��������������
 * �������� ��� ������ � ������������� ������������ � ����������
 * @param bool $LowProtect
 * @param bool $Redirect
 * @return void
 */
function HackOff( $LowProtect=false, $Redirect=true ){
	global $user, $config;
	if($user->isAdmin() || $LowProtect){
		if(defined('MAIN_SCRIPT') || defined('PLUG_SCRIPT') || !defined('ADMIN_SCRIPT')){
			if($Redirect){
				GO(Ufu('index.php'));
			}
		}elseif(defined('ADMIN_SCRIPT')){
			GO(ADMIN_FILE);
		}
	}else{
		if($config['security']['hack_event'] == 'alert'){
			die($config['security']['hack_alert']);
		}elseif($config['security']['hack_event'] == 'ban'){
			die('��� ��� �������� ������ � �����, �������� ������� ���������� ��������������
			�������� � ����� �������. ���� �� ��������, ��� ��� ��������� �� ������, - ����������
			� ������ ��������� �� e-mail '.$config['general']['site_email'].'.');
		}else{
			if($Redirect){
				GO(Ufu('index.php'));
			}
		}
	}
}

/**
 * ��������� ������� ������� � ������
 * @param $Level
 * @param string $Admins
 * @param string $Members
 * @param string $Guests
 * @param string $All
 * @return string
 */
function ViewLevelToStr( $Level, $Admins='', $Members='', $Guests='', $All='' ){
	switch($Level){
		case 1:	$Admins == '' ? $view = '<span style="color: #FF0000;">��������������</span>' : $view = $Admins;
		break;
		case 2:	$Members == '' ? $view = '<span style="color: #0080FF;">������������</span>' : $view = $Members;
		break;
		case 3: $Guests == '' ? $view = '<span style="color: #A0A000;">�����</span>' : $view = $Guests;
		break;
		case 4:
		default: $All == '' ? $view = '<span style="color: #008000;">���</span>' : $view = $All;
	}
	return $view;
}

/**
 * ������� ������ ���� ������ ����� �������� ������ �� ������� (������),
 * ������� ������������ � ������ �������� ����� ������
 * @param $ParamName ��� ��������� � ������� ������� ������� � ���� ������
 * @param null $UserAccess ������� ������� ������������
 * @return string
 */
function GetWhereByAccess( $ParamName, $UserAccess=null ){
	if($UserAccess == null){
		global $user;
		$UserAccess = $user->AccessLevel();
	}
	$where = "`$ParamName`='4'";
	if($UserAccess == ACCESS_ADMIN){ // �������������
		$where = '';
	}elseif($UserAccess == ACCESS_MEMBER){ // ������������
		$where .= " or `$ParamName`='2'";
	}else{ // �����
		$where .= " or `$ParamName`='3'";
	}
	return $where;
}
