<?php

$system_users_cache = null;
$system_userranks_cache = null;
$system_usertypes_cache = null;

/**
 * ���������� ������ ������ � ������������� � ������� �� id.
 * @return array
 */
function GetUsers(){
	global $system_users_cache;
	if($system_users_cache == null){
		$cache = LmFileCache::Instance();
		if($cache->HasCache(system_cache, 'users')){
			$system_users_cache = $cache->Get(system_cache, 'users');
		}else{
			$db = System::database();
			$db->Select('users', '');
			$system_users_cache = array();
			foreach($db->QueryResult as $usr){
				$system_users_cache[$usr['id']] = $usr;
			}
			// �� ������ ������ ��� ����������� ���� ��� � �����
			$cache->Write(system_cache, 'users', $system_users_cache, Day2Sec);
		}
	}
	return $system_users_cache;
}

/**
 * ���������� ����� �������������
 * @return array|null|string
 */
function GetUserRanks(){
	global $system_userranks_cache;
	if($system_userranks_cache == null){
		$cache = LmFileCache::Instance();
		if($cache->HasCache(system_cache, 'userranks')){
			$system_userranks_cache = $cache->Get(system_cache, 'userranks');
		}else{
			$system_users_cache = array();
			$system_userranks_cache = System::database()->Select('userranks', '');
			SortArray($system_userranks_cache, 'min');
			$cache->Write(system_cache, 'userranks', $system_userranks_cache);
		}
	}
	return $system_userranks_cache;
}

/**
 * ���������� ���� �������������
 * @return array
 */
function GetUserTypes(){
	global $system_usertypes_cache;
	if($system_usertypes_cache == null){
		$cache = LmFileCache::Instance();
		if($cache->HasCache(system_cache, 'usertypes')){
			$system_usertypes_cache = $cache->Get(system_cache, 'usertypes');
		}else{
			$types = System::database()->Select('usertypes', '');
			$system_usertypes_cache = array();
			foreach($types as $type){
				$system_usertypes_cache[$type['id']] = $type;
			}
			$cache->Write(system_cache, 'usertypes', $system_usertypes_cache);
		}
	}
	return $system_usertypes_cache;
}

/**
 * ������� E-mail ������������ ������������ ��� �����������
 * @param $Email
 * @param $error_out
 * @param bool $CheckExist
 * @param int $xor_id
 * @return bool
 */
function CheckUserEmail( $Email, &$error_out, $CheckExist=false, $xor_id=0 ){
	global $db, $config;
	if($Email == ''){
		$error_out[] = '�� �� ����� ��� E-mail �����.';
		return false;
	}
	if(!CheckEmail($Email)){
		$error_out[] = '�� ���������� ������ E-mail. �� ������ ���� ����: <b>domain@host.ru</b> .';
		return false;
	}
	if($CheckExist){
		$db->Select('users', "`email`='$Email'".($xor_id<>0?' and `id`<>'.$xor_id:''));
		if($db->NumRows() > 0){
			$error_out[] = '������������ � ����� E-mail ��� ��������������� !';
			$result = false;
		}
	}
	return true;
}

/**
 * ��������� ����� �� ������������
 * @param String $login �����
 * @param $error_out ���������� � ������� ���������� ����� ������
 * @param bool $CheckExist ���������� �������� �� ��������� ������
 * @param int $xor_id
 * @return Boolean ������ ���� ����� ������
 */
function CheckLogin( $login, &$error_out, $CheckExist=false, $xor_id=0 ){
	global $db, $config;
	$result = true;
	if(isset($config['user']['login_min_length'])){
		$minlength = $config['user']['login_min_length'];
	}else{
		$minlength = 4;
	}
	if(strlen($login) < $minlength || strlen($login)>15){
		$error_out[] = '����� ������ ���� �� ����� '.$minlength.' � �� ����� 15 ��������.';
		$result = false;
	}
	if(preg_match('/[^a-zA-Z�-��-�0-9_]/', $login)){
		$error_out[] = '��� ����� ������ �������� ������ �� ������� ��� ��������� ����, ���� � �������� �������������.';
		$result = false;
	}
	if($CheckExist){
		$db->Select('users',"`login`='$login'".($xor_id<>0?' and `id`<>'.$xor_id:''));
		if($db->NumRows()>0){
			$error_out[] = '������������ � ����� ������� ��� ��������������� !';
			$result = false;
		}
	}
	return $result;
}

/**
 * ��������� ������� �� ������������
 * @param String $nikname �������
 * @param $error_out ���������� � ������� ���������� ����� ������
 * @param bool $CheckExist ���������� �������� �� ��������� ������
 * @param int $xor_id
 * @return Boolean ������ ���� ������ ������
 */
function CheckNikname( $nikname, &$error_out, $CheckExist=false, $xor_id=0 ){
	global $db, $config;
	$result = true;
	if($nikname == ''){
		$error_out[] = '�� �� ����� ���!';
		$result = false;
	}
	if(preg_match("/[^a-zA-Z�-��-�0-9_ ]/",$nikname)){
		$error_out[] = '���� ��� ������ �������� ������ �� ������� ��� ��������� ���� � ����, �������� ������������� � �������.';
		$result = false;
	}
	if($CheckExist){
		$db->Select('users',"`name`='$nikname'".($xor_id<>0?' and `id`<>'.$xor_id:''));
		if($db->NumRows()>0){
			$error_out[] = '������������ � ����� ������ ��� ��������������� !';
			$result = false;
		}
	}
	return $result;
}

/**
 * ��������� ������ �� ������������
 * @param String $pass ������
 * @param $error_out ���������� � ������� ���������� ����� ������ (������)
 * @return Boolean ������ ���� ������ ������
 */
function CheckPass($pass,&$error_out){
	global $config;
	$result = true;
	if(isset($config['user']['pass_min_length'])){
		$minlength = $config['user']['pass_min_length'];
	}else{
		$minlength = 4;
	}
	if($pass<>'' && (strlen($pass) < $minlength || strlen($pass)>255)){
		$error_out[] = '������ ������ ���� �� ������ '.$minlength.' ��������.';
		$result = false;
	}
	return $result;
}


/**
 * ���������� ������ ���������� � ������������ ������� ����, �������� �����, ������ ������, ��� ����� ������� ��� ������. ��� ���������� ����������.
 * @param $user_id
 * @return array|bool
 */
function GetUserInfo($user_id){
	$system_users_cache = GetUsers();
	if(isset($system_users_cache[$user_id])){
		$usr = $system_users_cache[$user_id];
		// ������
		$usr['avatar_file'] = GetUserAvatar($user_id);
		$usr['avatar_file_small'] = GetSmallUserAvatar($user_id, $usr['avatar_file']);
		$usr['avatar_file_smallest'] = GetSmallestUserAvatar($user_id,  $usr['avatar_file']);
		// ����
		$rank = GetUserRank($usr['points'],$usr['type'],$usr['access']);
		$usr['rank_name'] = $rank[0];
		$usr['rank_image'] = $rank[1];
		// ������ ������
		$online = System::user()->Online();
		$usr['online'] = isset($online[$user_id]);
		return $usr;
	}else{
		return false;
	}
}

/**
 * ���������� ��� ����� ������� ������������. ����� � GetPersonalAvatar.
 * @param $user_id
 * @return string
 */
function GetUserAvatar( $user_id ){
	return GetPersonalAvatar($user_id);
}

/**
 * ���������� ��� ����� ����������� ����� ������� ������������ � 64px
 * @param $user_id
 * @param string $avatar
 * @return string
 */
function GetSmallUserAvatar( $user_id, $avatar = '' ){
	if($avatar == ''){
		$avatar = GetPersonalAvatar($user_id);
	}
	if(System::config('user/secure_avatar_upload') && GDVersion() <> 0){
		return $avatar.'&size=small';
	}else{
		$_name = GetFileName($avatar);
		$_ext = GetFileExt($avatar);
		$filename = System::config('user/personal_avatars_dir').$_name.'_64x64'.$_ext;
		if(is_file($filename)){
			return $filename;
		}else{
			return 'index.php?name=plugins&p=avatars_render&user='.$user_id.'&size=small';
		}
	}
}

/**
 * ���������� ��� ����� ������ ����������� ����� ������� ������������ � 24px
 * @param $user_id
 * @param string $avatar
 * @return string
 */
function GetSmallestUserAvatar( $user_id, $avatar = '' ){
	global $config;
	if($avatar == ''){
		$avatar = GetPersonalAvatar($user_id);
	}
	if($config['user']['secure_avatar_upload'] == '1' && GDVersion() <> 0){
		return $avatar.'&size=smallest';
	}else{
		$_name = GetFileName($avatar);
		$_ext = GetFileExt($avatar);
		$filename = $config['general']['personal_avatars_dir'].$_name.'_24x24'.$_ext;
		if(is_file($filename)){
			return $filename;
		}else{
			return 'index.php?name=plugins&p=avatars_render&user='.$user_id.'&size=smallest';
		}
	}
}

/**
 * ���������� ��� ����� ������� ������������
 * @param $user_id
 * @return string
 */
function GetPersonalAvatar($user_id){
	global $db, $config;
	if($user_id == 0){
		return GetGalleryAvatar('guest.gif');
	}
	if($config['user']['secure_avatar_upload']=='1' && GDVersion()<>0){
		if($user_id==0){
			return GetGalleryAvatar('guest.gif');
		}else{
			return 'index.php?name=plugins&p=avatars_render&user='.$user_id;
		}
	}else{
		$system_users_cache = GetUsers();
		if(!isset($system_users_cache[$user_id])){
			return GetGalleryAvatar('guest.gif');
		}
		$usePersonal = $system_users_cache[$user_id]['a_personal'];
		$filename = $system_users_cache[$user_id]['avatar'];
		if($usePersonal=='1'){
			$afn = $config['general']['personal_avatars_dir'].$filename;
		}else{
			$afn = $config['general']['avatars_dir'].$filename;
		}
		if(file_exists($afn)){
			return $afn;
		}else{
			return GetGalleryAvatar('noavatar.gif');
		}
	}
}

/**
 * ���������� ����� ������� �� ������� �� ����� �����
 * @param $filename
 * @return string
 */
function GetGalleryAvatar($filename){
	global $config;
	if(!defined('SETUP_SCRIPT')){
		if(trim($filename)==''){
			$filename = 'noavatar.gif';
		}
		if($config['user']['secure_avatar_upload']=='1' && GDVersion()!==false){
			return 'index.php?name=plugins&p=avatars_render&aname='.$filename;
		}else{
			return $config['general']['avatars_dir'].$filename;
		}
	}else{
		return $filename;
	}
}


/**
 * ���������� ��������, �������� � ������������� ����� ������������
 * @param $points
 * @param $type
 * @param $access
 * @return array
 */
function GetUserRank($points, $type, $access){
	global $config, $db;
	static $admintypes = null;
	if($type == '2'){ // ������������
		$ranks = GetUserRanks();
		$last = $ranks[0];
		foreach($ranks as $rank){
			if($rank['min'] > $points){
				return array(
				    SafeDB($last['title'], 250, str),
				    RealPath2($config['general']['ranks_dir'].SafeDB($last['image'], 250, str)),
				    SafeDB($last['id'], 11, int));
			}else{
				$last = $rank;
			}
		}
		return array(
		    SafeDB($last['title'], 250, str),
		    RealPath2($config['general']['ranks_dir'].SafeDB($last['image'], 250, str)),
		    SafeDB($last['id'], 11, int));
	}else{ // �������������
		$admintypes = GetUserTypes();
		if(isset($admintypes[$access])){
			return array(
				'<font color="'.SafeDB($admintypes[$access]['color'], 9, str).'">'.SafeDB($admintypes[$access]['name'], 255, str).'</font>',
				RealPath2($config['general']['ranks_dir'].SafeDB($admintypes[$access]['image'], 250, str)),
				SafeDB($admintypes[$access]['id'], 11, int));
		}
	}
}

/**
 * �������� ������ ��� ��������� �� E-mail
 * @param $username
 * @param $user_mail
 * @param $login
 * @param $pass
 * @param $code
 * @param $regtime
 * @return void
 */
function UserSendActivationMail($username, $user_mail, $login, $pass, $code, $regtime){
	global $config;
	$time = $regtime+604800;
	$time = date("d.m.Y", $time);

	$text = $config['user']['mail_template'];

	$sr = array(
		'{sitename}', '{siteurl}', '{username}', '{date}', '{login}', '{pass}', '{link}'
	);
	$rp = array(
		$config['general']['site_name'], $config['general']['site_url'], $username, $time, $login, $pass, $config['general']['site_url'].'index.php?name=plugins&p=activate&code='.$code
	);

	$text = str_replace($sr, $rp, $text);

	SendMail($username, $user_mail, '����������� �� '.$config['general']['site_name'], $text);
}

/**
 * �������� ������ �� ���������� �����������
 * @param $user_mail
 * @param $name
 * @param $login
 * @param $pass
 * @param $regtime
 * @return void
 */
function UserSendEndRegMail($user_mail, $name, $login, $pass, $regtime){
	global $config;
	$text = '������������, ['.$name.']!

�� ���� ������� ���������������� �� �����
'.$config['general']['site_url'].'

���� �����������: '.date("d.m.Y", $regtime).'
���: '.$name.'

��� ����� �� ���� �����������:
�����: '.$login.'
������: '.$pass.'

��������, ��� ���� ����� ��� �������.
� ���������, ������������� ����� '.$config['general']['site_url'].'.';
	SendMail($name, $user_mail, '['.$config['general']['site_url'].'] �����������', $text);
}

/**
 * �������� ������ � ����� �������
 * @param $user_mail
 * @param $name
 * @param $login
 * @param $pass
 * @return void
 */
function UserSendForgotPassword($user_mail, $name, $login, $pass){
	global $config;
	$ip = getip();
	$text = '������������, ['.$name.']!

�� ����� '.$config['general']['site_url'].'
���� ��������� ����������� ������.

���: '.$name.'

��� ����� � ����� ������:
�����: '.$login.'
������: '.$pass.'

�������� ������ �������� �� ������ �� ������:
'.GetSiteUrl().Ufu('index.php?name=user&op=editprofile', 'user/{op}/').'

IP-�����, � �������� ��� �������� ������: '.$ip.'

� ���������, ������������� ����� '.$config['general']['site_url'].'.';
	SendMail($name, $user_mail, '['.$config['general']['site_url'].'] ����������� ������', $text);
}

/**
 * ��������� ����� ������ � ������� � ���������� ������ ��� HTML::Select
 * @param $avatar
 * @param $personal
 * @return array
 */
function GetGalleryAvatarsData($avatar, $personal){
	global $config, $site;
	$avatars = GetFiles($config['general']['avatars_dir'], false, true, '.gif.jpg.jpeg.png');
	$selindex = 0;
	$avd = array(
	);
	if($personal == '1'){
		$site->DataAdd($avd, '', '������������', true);
	}
	for($i = 0, $c = count($avatars); $i < $c; $i++){
		if($avatar == $avatars[$i]){
			$sel = true;
			$selindex = $i;
		} else{
			$sel = false;
		}
		$site->DataAdd($avd, $avatars[$i], $avatars[$i], $sel);
	}
	return array(
		$avd, $avatars[$selindex]
	);
}

/**
 * ������� ��������� ��������� ������ ($_FILES['upavatar'])
 * @param $errors
 * @param $avatar
 * @param $a_personal
 * @param $oldAvatarName
 * @param $oldAvatarPersonal
 * @param $editmode
 */
function UserLoadAvatar(&$errors, &$avatar, &$a_personal, $oldAvatarName, $oldAvatarPersonal, $editmode){
	global $config;

	$alloy_mime = array(
		'image/gif' => '.gif', 'image/jpeg' => '.jpg', 'image/pjpeg' => '.jpg', 'image/png' => '.png', 'image/x-png' => '.png'
	);
	include_once($config['inc_dir'].'picture.class.php');

	$asize = getimagesize($_FILES['upavatar']['tmp_name']);

	//�������� ������� �����
	$alloy_mime = array(
		'image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png'
	);
	$alloy_exts = array(
		'.gif', '.jpg', '.jpeg', '.png'
	);
	if(in_array($_FILES['upavatar']['type'], $alloy_mime) && in_array(strtolower(GetFileExt($_FILES['upavatar']['name'])), $alloy_exts)){
		// ������� ������ ������
		if($editmode && $oldAvatarPersonal == '1'){
			UnlinkUserAvatarFiles($oldAvatarName);
		}

		//��������� ������, ���� �����, � ��������� ������ � ����� ������������ ������
		$NewName = GenRandomString(8, 'qwertyuiopasdfghjklzxcvbnm');
		$ext = strtolower(GetFileExt($_FILES['upavatar']['name']));

		if($asize[0] > $config['user']['max_avatar_width'] || $asize[1] > $config['user']['max_avatar_height']){
			$thumb = new TPicture($_FILES['upavatar']['tmp_name']);
			$thumb->SetImageSize($config['user']['max_avatar_width'], $config['user']['max_avatar_height']);
			$thumb->SaveToFile($config['general']['personal_avatars_dir'].$NewName.$ext);
		} else{
			copy($_FILES['upavatar']['tmp_name'], $config['general']['personal_avatars_dir'].$NewName.$ext);
		}

		// ������� ����������� ����������� ����� 24�24 � 64�64
		$thumb = new TPicture($_FILES['upavatar']['tmp_name']);
		$thumb->SetImageSize(64, 64);
		$thumb->SaveToFile($config['general']['personal_avatars_dir'].$NewName.'_64x64'.$ext);
		$thumb = new TPicture($_FILES['upavatar']['tmp_name']);
		$thumb->SetImageSize(24, 24);
		$thumb->SaveToFile($config['general']['personal_avatars_dir'].$NewName.'_24x24'.$ext);

		$avatar = $NewName.$ext;
		$a_personal = '1';
	} else{
		$errors[] = '������������ ������ �������. ��� ������ ������ ���� ������� GIF, JPEG ��� PNG.';
		$a_personal = '0';
	}
}

/**
 * ������� ��� ������� ������� �� ��� �����
 * @param $AvatarFileName
 * @return void
 */
function UnlinkUserAvatarFiles($AvatarFileName){
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

/**
 * ���������� ������ ������ ������� ��� Html::Select
 * @param array | int $view ������ �� ��������� ��� ������� ������ ��� ����� ����������� ������
 * @return array | int
 */
function GetUserTypesFormData( $view ){
	$visdata = array();
	if(!is_array($view)){
		$_view = $view;
		$view = array('1'=>false, '2'=>false, '3'=>false, '4'=>false);
		$view[$_view] = true;
	}
	System::admin()->DataAdd($visdata, 'all', '���', $view['4']);
	System::admin()->DataAdd($visdata, 'members', '������ ������������', $view['2']);
	System::admin()->DataAdd($visdata, 'guests', '������ �����', $view['3']);
	System::admin()->DataAdd($visdata, 'admins', '������ ��������������', $view['1']);
	return $visdata;
}

/**
 * ������������ ���������� ������� ���������������
 * @return int
 */
function GetSystemAdminsCount(){
	global $db;
	$atypes = $db->Select('usertypes', '');
	foreach($atypes as $type){
		$types[$type['id']] = $type['system'];
	}
	unset($atypes);
	$admins = $db->Select('users', "`type`='1'");
	//������������ ���������� ������� ���������������
	$system = 0;
	for($i = 0, $c = count($admins); $i < $c; $i++){
		if($types[$admins[$i]['access']] == '1'){
			$system++;
		}
	}
	return $system;
}

/**
 * ��������� ��������� �� ������ �� id ������
 * @param  $access
 * @return bool
 */
function groupIsSystem($access){
	global $db;
	if($access == -1){
		return false;
	}
	$db->Select('usertypes', "`id`='$access'");
	if($db->NumRows() > 0){
		$access = $db->FetchRow();
		return $access['system'] == '1';
	} else{
		return false;
	}
}

/**
 * ���������� ����� �������������� ������������ � �����-������
 * @param  $save_link
 * @param string $a
 * @param int $id
 * @param bool $isadmin
 * @return
 */
function AdminUserEditor($save_link, $a = 'adduser', $id = 0, $isadmin = false){
	global $config, $db, $site, $user;
	$active = array(
		false, false, false
	);
	$db->Select('usertypes', '');
	if($user->isSuperUser()){
		$types = array(
			array(
				'member', '������������', false
			)
		);
		while($type = $db->FetchRow()){
			$types[$type['id']] = array(
				$type['id'], $type['name'], false
			);
		}
	}
	if($a == 'edit'){
		$db->Select('users', "`id`='$id'".($isadmin ? " and `type`='1'" : " and `type`='2'"));
		if($db->NumRows() == 0){
			AddTextBox('������', '<p><center>������������ �� ������, ���� � ��� �� ���������� ���� ��� �������������� ���������������.</center></p>');
			return;
		}
		$usr = $db->FetchRow();
		$SystemUser = false;
		$editStatus = false;

		if($isadmin){
			$SystemUser = groupIsSystem(SafeEnv($usr['access'], 11, int));
			//������������ - ��������� ��������� �������������
			if($SystemUser && GetSystemAdminsCount() <= 1){
				$editStatus = false;
			} else{ //���� �� �� ��������� ��� ��������� ������ 1
				$editStatus = true;
			}
		} else{ // ���� ������������, ��, ���� � ��� ���� ����� ��������� �������
			$editStatus = true;
		}

		$login = SafeDB($usr['login'], 30, str);
		$mail = SafeDB($usr['email'], 50, str);
		$hideemail = ($usr['hideemail'] == 1 ? true : false);
		$snews = ($usr['servernews'] == 1 ? true : false);
		$name = SafeDB($usr['name'], 50, str);
		$tname = SafeDB($usr['truename'], 250, str);
		$age = SafeDB($usr['age'], 11, str);
		$city = SafeDB($usr['city'], 100, str);
		$url = SafeDB($usr['url'], 250, str);
		$icq = SafeDB($usr['icq'], 15, str);
		$gmt = SafeDB($usr['timezone'], 255, str);
		$about = SafeDB($usr['about'], 0, str);
		$avatar = SafeDB($usr['avatar'], 250, str);
		$apersonal = SafeDB($usr['a_personal'], 1, int);

		if($usr['type'] == '1'){
			$types[$usr['access']][2] = true;
		} else{
			$types[0][2] = true; //������������
		}

		if($usr['active'] == '1'){
			$active[0] = true;
		} elseif($usr['active'] == '0' && $usr['activate'] == ''){
			$active[1] = true;
		} elseif($usr['active'] == '0' && $usr['activate'] != ''){
			$active[2] = true;
		}

		$caption = '���������';
		$title = '�������������� ������������';
	} else{
		$login = '';
		$mail = '';
		$snews = false;
		$hideemail = false;
		$name = '';
		$tname = '';
		$age = '';
		$city = '';
		$url = '';
		$icq = '';
		$gmt = '';
		$about = '';
		$avatar = '';
		$apersonal = '0';
		$active[0] = true;
		$types[0][2] = true;
		$caption = '��������';
		$title = '�������� ������������';
		$editStatus = true;
	}
	FormRow('�����', $site->Edit('login', $login, false, 'style="width:400px;"'));
	FormRow('������', $site->Edit('pass', '', true, 'style="width:400px;"'));
	FormRow('��������� ������<br /><small>(��� ��������)</small>', $site->Edit('rpass', '', true, 'style="width:400px;"'));
	FormRow('E-mail', $site->Edit('email', $mail, false, 'style="width:300px;"').' <label for="hideemail">������</label>&nbsp;'.$site->Check('hideemail', '1', $hideemail, 'id="hideemail"'));
	FormRow('<label for="snews">��������</label>', $site->Check('snews', '1', $snews, 'id="snews"'));
	FormRow('���', $site->Edit('nikname', $name, false, 'style="width:400px;"'));
	FormRow('��������� ���', $site->Edit('realname', $tname, false, 'style="width:400px;"'));
	FormRow('�������', $site->Edit('age', $age, false, 'style="width:400px;"'));
	FormRow('�����', $site->Edit('city', $city, false, 'style="width:400px;"'));
	FormRow('����', $site->Edit('homepage', $url, false, 'style="width:400px;"'));
	FormRow('ICQ', $site->Edit('icq', $icq, false, 'style="width:400px;"'));
	$gmt = GetGmtData($gmt);
	FormRow('������� ����', $site->Select('gmt', $gmt, false, 'style="width:400px;"'));
	FormRow('� ����', $site->TextArea('about', $about, 'style="width:400px; height:200px;"'));
	$avatars = GetGalleryAvatarsData($avatar, $apersonal);
	if($apersonal == '1'){
		$selected = GetPersonalAvatar($id);
	} else{
		$selected = GetGalleryAvatar($avatars[1]);
	}
	$site->AddJS('
	function ShowAvatar(){
		if(document.userform.avatar.value==\'\'){
			document.userform.avatarview.src = \''.(
	$config['user']['secure_avatar_upload'] == '1' ? 'index.php?name=plugins&p=avatars_render&user='.$id : $config['general']['personal_avatars_dir'].$avatar).'\';
		}else{
			document.userform.avatarview.src = \''.($config['user']['secure_avatar_upload'] == '1' ? 'index.php?name=plugins&p=avatars_render&aname=' : $config['general']['avatars_dir']).'\'+document.userform.avatar.value;
		}
	}');
	FormRow('������', '<center>'.$site->Select('avatar', $avatars[0], false, 'onchange="ShowAvatar();"').'</center>');
	FormRow('', '<center><img id="avatarview" src="'.$selected.'" border="0" width="64" /></center>');
	FormRow('��������� ������', $site->FFile('upavatar'));
	if($editStatus){
		FormRow('���������', $site->Radio('activate', 'auto', $active[0]).'������������'.$site->Radio('activate', 'manual', $active[1]).'�� ������������'.(!$isadmin ? $site->Radio('activate', 'mail',
			$active[2]).'�� E-mail' : ''));
	}
	if($user->SuperUser && $editStatus){
		$usertypes = array(
		);
		foreach($types as $type){
			$site->DataAdd($usertypes, $type[0], $type[1], $type[2]);
		}
		FormRow('������', $site->Select('status', $usertypes));
	}
	TAddSubTitle($title);
	AddCenterBox($title);
	AddForm('<form name="userform" action="'.
	        $config['admin_file'].'?exe='.$save_link.'&id='.$id.'" method="post"  enctype="multipart/form-data">', $site->Button('������', 'onclick="history.go(-1);"').$site->Submit($caption));
}

/**
 * ��������� ������ ����� ��������������� ������� AdminUserEditor
 * @param  $back_link
 * @param string $a
 * @param int $id
 * @param bool $isadmin
 * @return void
 */
function AdminUserEditSave($back_link, $a = 'insert', $id = 0, $isadmin = false){
	global $db, $config, $site, $user;

	if($a == 'update'){
		$edit = true;
		$db->Select('users', "`id`='".$id."'");
		$usr = $db->FetchRow();
		if($isadmin){
			$SystemUser = groupIsSystem($usr['access']);
			//������������ - ��������� ��������� �������������
			if($SystemUser && GetSystemAdminsCount() <= 1){
				$editStatus = false;
			} else{ //���� �� �� ��������� ��� ��������� ������ 1
				$editStatus = true;
			}
		} else{ // ���� ������������, ��, ���� � ��� ���� ����� ��������� �������
			$editStatus = true;
		}
	} else{
		$edit = false;
		$editStatus = true;
	}

	$errors = array(
	);

	//������������ ��������� ���������� ������
	// �����
	if(isset($_POST['login']) && CheckLogin($_POST['login'], $errors, !$edit)){
		$login = SafeEnv($_POST['login'], 15, str);
	} else{
		$login = '';
	}
	// ������
	$pass = '';
	if(!$edit || $_POST['pass'] != ''){
		$passmsg = '';
		if(isset($_POST['pass']) && CheckPass($_POST['pass'], $errors)){
			$pass = SafeEnv($_POST['pass'], 30, str);
			if(!isset($_POST['rpass']) || SafeEnv($_POST['rpass'], 30, str) != $pass){
				$errors[] = '������ �� ���������.';
			}
		} else{
			$pass = '';
		}
		if(isset($_POST['pass']) && $_POST['pass'] == ''){
			srand(time());
			$pass = GenBPass(rand($config['user']['pass_min_length'], 15));
			$passmsg = '<br />��� ��� �� �� ������� ������, �� ��� ������������ ������������� � ������ ��� �� E-mail.';
		}
		$pass2 = md5($pass);
	}
	// e-mail
	if(isset($_POST['email']) && $_POST['email'] != ''){
		if(!CheckEmail($_POST['email'])){
			$errors[] = '�� ���������� ������ E-mail. �� ������ ���� ����: <b>domain@host.ru</b> .';
		}
		$email = SafeEnv($_POST['email'], 50, str, true);
	} else{
		$email = '';
		$errors[] = '�� �� ����� E-mail.';
	}
	// ������ e-mail
	if(isset($_POST['hideemail'])){
		$hideemail = '1';
	} else{
		$hideemail = '0';
	}
	// �������
	if(isset($_POST['nikname']) && CheckNikname($_POST['nikname'], $errors, !$edit)){
		$nikname = SafeEnv($_POST['nikname'], 50, str, true);
	} else{
		$nikname = '';
	}
	// ������ ���
	if(isset($_POST['realname'])){
		$realname = SafeEnv($_POST['realname'], 250, str, true);
	} else{
		$realname = '';
	}
	// ������� (� �����)
	if(isset($_POST['age'])){
		if($_POST['age'] == '' || is_numeric($_POST['age'])){
			$age = SafeEnv($_POST['age'], 3, int);
		} else{
			$errors[] = '��� ������� ������ ���� ������!';
		}
	} else{
		$age = '';
	}
	// �������� ��������
	if(isset($_POST['homepage'])){
		if($_POST['homepage'] != '' && substr($_POST['homepage'], 0, 7) == 'http://'){
			$_POST['homepage'] = substr($_POST['homepage'], 7);
		}
		$homepage = SafeEnv($_POST['homepage'], 250, str, true);
	} else{
		$homepage = '';
	}
	// ����� ICQ
	if(isset($_POST['icq'])){
		if($_POST['icq'] == '' || is_numeric($_POST['icq'])){
			$icq = SafeEnv($_POST['icq'], 15, str, true);
		} else{
			$errors[] = '����� ICQ ������ ��������� ������ �����!';
		}
	} else{
		$icq = '';
	}
	// �����
	if(isset($_POST['city'])){
		$city = SafeEnv($_POST['city'], 100, str, true);
	} else{
		$city = '';
	}
	// ������� ����
	if(isset($_POST['gmt'])){
		$gmt = SafeEnv($_POST['gmt'], 255, str);
	} else{
		$gmt = 'Europe/Moscow';
	}
	// � ����
	if(isset($_POST['about'])){
		$about = SafeEnv($_POST['about'], $config['user']['about_max_length'], str, true);
	} else{
		$about = '';
	}
	// �������� �� �������
	if(isset($_POST['snews'])){
		$snews = '1';
	} else{
		$snews = '0';
	}
	//������������ ������
	$alloy_mime = array(
		'image/gif' => '.gif', 'image/jpeg' => '.jpg', 'image/pjpeg' => '.jpg', 'image/png' => '.png', 'image/x-png' => '.png'
	);
	$updateAvatar = true;
	if(isset($_POST['avatar'])){
		if($config['user']['avatar_transfer'] == '1' && isset($_FILES['upavatar']) && file_exists($_FILES['upavatar']['tmp_name'])){
			UserLoadAvatar($errors, $avatar, $a_personal, $usr['avatar'], $usr['a_personal'] == '1', $edit);
		} elseif($_POST['avatar'] == ''){
			$updateAvatar = false;
		} elseif(file_exists(RealPath2($config['general']['avatars_dir'].$_POST['avatar']))){
			if($edit){
				if($usr['a_personal'] == '1'){
					UnlinkUserAvatarFiles($usr['avatar']);
				}
			}
			$a_personal = '0';
			$avatar = $_POST['avatar'];
		} else{
			$avatar = '';
			$a_personal = '0';
		}
	} else{
		$avatar = '';
		$a_personal = '0';
	}

	if($editStatus){
		$activate = $_POST['activate'];
		switch($activate){
			case 'manual':
				$active = '0';
				$code = '';
				$SendActivation = false;
				break;
			case 'auto':
				$active = '1';
				$code = '';
				$SendActivation = false;
				break;
			case 'mail':
				$active = '0';
				$code = GenRandomString(8, 'qwertyuiopasdfghjklzxcvbnm');
				$SendActivation = true;
				break;
		}
	} else{
		$active = '1';
		$code = '';
		$SendActivation = false;
	}

	if($edit && !$editStatus){
		$status = $usr['type'];
		$access = $usr['access'];
	} elseif($_POST['status'] == 'member' || !$user->SuperUser){
		$status = 2;
		$access = -1;
	} else{
		$status = 1;
		$access = SafeEnv($_POST['status'], 11, int);
	}

	$regdate = time();
	$lastvisit = time();
	$ip = getip();
	$points = 0;
	$visits = 0;
	if($SendActivation){
		UserSendActivationMail($nikname, $email, $login, $pass, $code, $regdate);
	} elseif(!$edit){
		UserSendEndRegMail($email, $nikname, $login, $pass, $regdate);
	}

	if(!$edit){
		$vals = Values('', $login, $pass2, $nikname, $realname, $age, $email, $hideemail, $city, $icq, $homepage, $gmt, $avatar, $about, $snews, $regdate, $lastvisit, $ip, $points, $visits, $active, $code, $status, $access, $a_personal);
		$db->Insert('users', $vals);
	} else{
		$set = "login='$login',email='$email',hideemail='$hideemail',name='$nikname',truename='$realname',age='$age',url='$homepage',icq='$icq',city='$city',timezone='$gmt'".($updateAvatar == true ? ",avatar='$avatar',a_personal='$a_personal'" : '').",about='$about',servernews='$snews'".($pass != '' ? ",pass='$pass2'" : '').",type='$status',access='$access',active='$active'";
		$db->Update('users', $set, "`id`='".$id."'");
		$user->UpdateMemberSession();
		UpdateUserComments($id, $id, $nikname, $email, $hideemail, $homepage);
	}

	if(count($errors) > 0){
		$text = '������� ��������, �� ����� ����� ��������� ������:<br /><ul>';
		foreach($errors as $error){
			$text .= '<li>'.$error;
		}
		$text .= '</ul>';
		AddTextBox('��������', $text);
	} else{
		// ������� ��� �������������
		$cache = LmFileCache::Instance();
		$cache->Delete(system_cache, 'users');
		GO($config['admin_file'].'?exe='.$back_link);
	}
}

/**
 * ���������� IP ����� ������������
 * @return array
 */
function getip(){
	global $_SERVER, $config;
	if(!isset($config['info']['ip'])){
		if(isset($_SERVER['REMOTE_ADDR'])){
			$ip = $_SERVER['REMOTE_ADDR'];
		}elseif(isset($HTTP_SERVER_VARS['REMOTE_ADDR'])){
			$ip = $HTTP_SERVER_VARS['REMOTE_ADDR'];
		}elseif(getenv('REMOTE_ADDR')){
			$ip = getenv('REMOTE_ADDR');
		}
		if($ip!=""){
			if(preg_match("/^([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/",$ip,$ipm)){
				$private = array("/^0\./","/^127\.0\.0\.1/","/^192\.168\..*/","/^172\.16\..*/"
				,"/^10..*/","/^224..*/","/^240..*/");
				$ip = preg_replace($private,$ip,$ipm[1]);
			}
		}
		if (strlen($ip)>16) $ip = substr($ip, 0, 16);
		return $config['info']['ip'] = $ip;
	}else{
		return $config['info']['ip'];
	}
}
