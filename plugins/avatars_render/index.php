<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

// ������ ��� ����� �������
$avatar = '';
if(isset($_GET['user'])){
	$uid = SafeEnv($_GET['user'], 11, int);
	$db->Select('users', "`id`='$uid'");
	if($db->NumRows() == 0){
		$SiteLog->Write('Plugin::render_avatar ������������ �� ������.');
	}else{
		$u = $db->FetchRow();
		if($u['a_personal'] == '1'){
			$avatar = $config['general']['personal_avatars_dir'].$u['avatar'];
		}else{
			$avatar = $config['general']['avatars_dir'].$u['avatar'];
		}
	}
}elseif(isset($_GET['aname'])){
	$avatar = RealPath2($config['general']['avatars_dir'].SafeEnv($_GET['aname'], 250, str));
}else{
	$SiteLog->Write('Plugin::render_avatar ������ ������ ��� ����������.');
}
if(!file_exists($avatar) || is_dir($avatar)){
	$avatar = $config['general']['avatars_dir'].'noavatar.gif';
}

function _SendAvatar( $avatar, $saveTo = '', $width = 0, $height = 0 ){
	global $config;
	$avatar_image = new TPicture($avatar);
	if(!is_dir($config['general']['personal_avatars_dir'])){
		mkdir($config['general']['personal_avatars_dir']);
	}
	if($saveTo != ''){
		$avatar_image->SetImageSize($width, $height);
		$avatar_image->SaveToFile($saveTo);
		$avatar_image->SendToHTTPClient();
	}else{
		$avatar_image->SendToHTTPClient();
	}
}

include_once($config['inc_dir'].'picture.class.php');
if(isset($_GET['size'])){
	$_name = GetFileName($avatar);
	$_ext = GetFileExt($avatar);
	$_avatar24 = $config['general']['personal_avatars_dir'].$_name.'_24x24'.$_ext;
	$_avatar64 = $config['general']['personal_avatars_dir'].$_name.'_64x64'.$_ext;
	switch ($_GET['size']){
		case 'small':
			if(is_file($_avatar64)){
				_SendAvatar($_avatar64);
			}else{
				_SendAvatar($avatar, $_avatar64, 64, 64);
			}
			break;
		case 'smallest':
			if(is_file($_avatar24)){
				_SendAvatar($_avatar24);
			}else{
				_SendAvatar($avatar, $_avatar24, 24, 24);
			}
			break;
		default:
			$size = 0;
	}
}else{
	_SendAvatar($avatar);
}

// ��������������� Referer
$user->Def('REFERER', $_SERVER['HTTP_REFERER']);
exit();
