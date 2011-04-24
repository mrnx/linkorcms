<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

function UserSendActivationMail( $username, $user_mail, $login, $pass, $code, $regtime )
{
	global $config;
	$time = $regtime + 604800;
	$time = date("d.m.Y", $time);

	$text = $config['user']['mail_template'];

	$sr = array(
		'{sitename}',
		'{siteurl}',
		'{username}',
		'{date}',
		'{login}',
		'{pass}',
		'{link}'
	);
	$rp = array(
		$config['general']['site_name'],
		$config['general']['site_url'],
		$username,
		$time,
		$login,
		$pass,
		$config['general']['site_url'].'index.php?name=plugins&p=activate&code='.$code
	);

	$text = str_replace($sr, $rp, $text);

	SendMail(
		$username,
		$user_mail,
		'Регистрация на '.$config['general']['site_name'],
		$text
	);
}

function UserSendEndRegMail( $user_mail, $name, $login, $pass, $regtime )
{
	global $config;
	$text = 'Здравствуйте, ['.$name.']!

Вы были успешно зарегистрированы на сайте
'.$config['general']['site_url'].'

Дата регистрации: '.date("d.m.Y", $regtime).'
Имя: '.$name.'

Для входа на сайт используйте:
логин: '.$login.'
пароль: '.$pass.'

Надеемся, наш сайт будет Вам полезен.
С уважением, администрация сайта '.$config['general']['site_url'].'.';
	SendMail($name, $user_mail, '['.$config['general']['site_url'].'] Регистрация', $text);
}

function UserSendForgotPassword( $user_mail, $name, $login, $pass )
{
	global $config;
	$ip = getip();
	$text = 'Здравствуйте, ['.$name.']!

На сайте '.$config['general']['site_url'].'
было запрошено напоминание пароля.

Имя: '.$name.'

Ваш логин и новый пароль:
логин: '.$login.'
пароль: '.$pass.'

Изменить данные аккаунта можете по адресу:
'.GetSiteUrl().Ufu('index.php?name=user&op=editprofile', 'user/{op}/').'

IP-адрес, с которого был запрошен пароль: '.$ip.'

С уважением, администрация сайта '.$config['general']['site_url'].'.';
	SendMail($name, $user_mail, '['.$config['general']['site_url'].'] Напоминание пароля', $text);
}

function GetGmtArray()
{
	$tlist = timezone_identifiers_list();
	$gmt = array();
	foreach($tlist as $timezone){
		$gmt[] = array($timezone, $timezone);
	}
	return $gmt;
}

function GetGmtData( $val )
{
	global $site;
	$tlist = timezone_identifiers_list();
	$gmt = array();
	foreach($tlist as $timezone){
		$site->DataAdd($gmt, $timezone, $timezone, $val == $timezone);
	}
	return $gmt;
}

function GetGalleryAvatarsData( $avatar, $personal )
{
	global $config, $site;
	$avatars = GetFiles($config['general']['avatars_dir'], false, true, '.gif.jpg.jpeg.png');
	$selindex = 0;
	$avd = array();
	if($personal == '1'){
		$site->DataAdd($avd, '', 'Персональный', true);
	}
	for($i = 0, $c = count($avatars); $i < $c; $i++){
		if($avatar == $avatars[$i]){
			$sel = true;
			$selindex = $i;
		}else{
			$sel = false;
		}
		$site->DataAdd($avd, $avatars[$i], $avatars[$i], $sel);
	}
	return array($avd, $avatars[$selindex]);
}

function GetGalleryAvatars( $avatar, $personal )
{
	global $config, $site;
	$avatars = GetFiles($config['general']['avatars_dir'], false, true, '.gif.jpg.jpeg.png');
	$selindex = 0;
	$avd = array();
	if($personal == '1'){
		$site->DataAdd($avd, '', 'Персональный', true);
	}
	for($i = 0, $c = count($avatars); $i < $c; $i++){
		if($avatar == $avatars[$i]){
			$sel = true;
			$selindex = $i;
		}else{
			$sel = false;
		}
		$vars['name'] = $avatars[$i];
		$vars['selected'] = $sel;
		$vars['caption'] = $avatars[$i];
	}
	return $vars;
}

/**
 * Функция управляет загрузкой аватар ($_FILES['upavatar])
 */
function UserLoadAvatar( &$errors, &$avatar, &$a_personal, $oldAvatarName, $oldAvatarPersonal, $editmode )
{
	global $config;

	$alloy_mime = array('image/gif'=>'.gif', 'image/jpeg'=>'.jpg', 'image/pjpeg'=>'.jpg', 'image/png'=>'.png', 'image/x-png'=>'.png');
	include_once($config['inc_dir'].'picture.class.php');

	$asize = getimagesize($_FILES['upavatar']['tmp_name']);

	//Проверка формата файла
	$alloy_mime = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png');
	$alloy_exts = array('.gif', '.jpg', '.jpeg', '.png');
	if(in_array($_FILES['upavatar']['type'], $alloy_mime) && in_array(strtolower(GetFileExt($_FILES['upavatar']['name'])), $alloy_exts)) {
		// Удаляем старый аватар
		if($editmode && $oldAvatarPersonal == '1'){
			UnlinkUserAvatarFiles($oldAvatarName);
		}

		//Выполняем ресайз, если нужно, и сохраняем аватар в папку персональных аватар
		$NewName = GenRandomString(8,'qwertyuiopasdfghjklzxcvbnm');
		$ext = strtolower(GetFileExt($_FILES['upavatar']['name']));

		if($asize[0] > $config['user']['max_avatar_width'] || $asize[1] > $config['user']['max_avatar_height']){
			$thumb = new TPicture($_FILES['upavatar']['tmp_name']);
			$thumb->SetImageSize($config['user']['max_avatar_width'],$config['user']['max_avatar_height']);
			$thumb->SaveToFile($config['general']['personal_avatars_dir'].$NewName.$ext);
		}else{
			copy($_FILES['upavatar']['tmp_name'], $config['general']['personal_avatars_dir'].$NewName.$ext);
		}

		// Создаем стандартные уменьшенные копии 24х24 и 64х64
		$thumb = new TPicture($_FILES['upavatar']['tmp_name']);
		$thumb->SetImageSize(64, 64);
		$thumb->SaveToFile($config['general']['personal_avatars_dir'].$NewName.'_64x64'.$ext);
		$thumb = new TPicture($_FILES['upavatar']['tmp_name']);
		$thumb->SetImageSize(24, 24);
		$thumb->SaveToFile($config['general']['personal_avatars_dir'].$NewName.'_24x24'.$ext);

		$avatar = $NewName.$ext;
		$a_personal = '1';
	}else{
		$errors[] = 'Неправильный формат аватара. Ваш аватар должен быть формата GIF, JPEG или PNG.';
		$a_personal = '0';
	}
}

function UnlinkUserAvatarFiles( $AvatarFileName )
{
	global $config;
	$AvatarFileName = RealPath2($config['general']['personal_avatars_dir'].$AvatarFileName);
	if(is_file($AvatarFileName)){
		unlink($AvatarFileName);
		$_name = GetFileName($AvatarFileName);
		$_ext = GetFileExt($AvatarFileName);
		if(is_file($config['general']['personal_avatars_dir'].$_name.'_24x24'.$_ext)){
			unlink($config['general']['personal_avatars_dir'].$_name.'_24x24'.$_ext);
		}
		if(is_file($config['general']['personal_avatars_dir'].$_name.'_64x64'.$_ext)){
			unlink($config['general']['personal_avatars_dir'].$_name.'_64x64'.$_ext);
		}
	}
}

?>