<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

IncludeFunction('user');

function AdminUserEditor( $save_link, $a = 'adduser', $id = 0, $isadmin = false )
{
	global $config, $db, $site, $user;
	$active = array(false, false, false);
	$db->Select('usertypes', '');
	if($user->isSuperUser()){
		$types = array(array('member', '������������', false));
		while($type = $db->FetchRow()){
			$types[$type['id']] = array($type['id'], $type['name'], false);
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
			}else{ //���� �� �� ��������� ��� ��������� ������ 1
				$editStatus = true;
			}
		}else{ // ���� ������������, ��, ���� � ��� ���� ����� ��������� �������
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
		}else{
			$types[0][2] = true; //������������
		}

		if($usr['active'] == '1'){
			$active[0] = true;
		}elseif($usr['active'] == '0' && $usr['activate'] == ''){
			$active[1] = true;
		}elseif($usr['active'] == '0' && $usr['activate'] != ''){
			$active[2] = true;
		}

		$caption = '���������';
		$title = '�������������� ������������';
	}else{
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
	FormRow(
		'E-mail', $site->Edit('email', $mail, false, 'style="width:300px;"')
		.' <label for="hideemail">������</label>&nbsp;'
		.$site->Check('hideemail', '1', $hideemail, 'id="hideemail"')
	);
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
	}else{
		$selected = GetGalleryAvatar($avatars[1]);
	}
	$site->AddJS('
	function ShowAvatar(){
		if(document.userform.avatar.value==\'\'){
			document.userform.avatarview.src = \''.($config['user']['secure_avatar_upload'] == '1' ? 'index.php?name=plugins&p=avatars_render&user='.$id : $config['general']['personal_avatars_dir'].$avatar).'\';
		}else{
			document.userform.avatarview.src = \''.($config['user']['secure_avatar_upload'] == '1' ? 'index.php?name=plugins&p=avatars_render&aname=' : $config['general']['avatars_dir']).'\'+document.userform.avatar.value;
		}
	}');
	FormRow('������', '<center>'.$site->Select('avatar', $avatars[0], false, 'onchange="ShowAvatar();"').'</center>');
	FormRow('', '<center><img id="avatarview" src="'.$selected.'" border="0" width="64" /></center>');
	FormRow('��������� ������', $site->FFile('upavatar'));
	if($editStatus){
		FormRow('���������', $site->Radio('activate', 'auto', $active[0]).'������������'.$site->Radio('activate', 'manual', $active[1]).'�� ������������'.(!$isadmin ? $site->Radio('activate', 'mail', $active[2]).'�� E-mail' : ''));
	}
	if($user->SuperUser && $editStatus){
		$usertypes = array();
		foreach($types as $type){
			$site->DataAdd($usertypes, $type[0], $type[1], $type[2]);
		}
		FormRow('������', $site->Select('status', $usertypes));
	}
	TAddSubTitle($title);
	AddCenterBox($title);
	AddForm('<form name="userform" action="'.$config['admin_file'].'?exe='.$save_link.'&id='.$id.'" method="post"  enctype="multipart/form-data">', $site->Button('������', 'onclick="history.go(-1);"').$site->Submit($caption));
}

function AdminUserEditSave( $back_link, $a = 'insert', $id = 0, $isadmin = false )
{
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
			}else{ //���� �� �� ��������� ��� ��������� ������ 1
				$editStatus = true;
			}
		}else{ // ���� ������������, ��, ���� � ��� ���� ����� ��������� �������
			$editStatus = true;
		}
	}else{
		$edit = false;
		$editStatus = true;
	}

	$errors = array();

	//������������ ��������� ���������� ������
	// �����
	if(isset($_POST['login']) && CheckLogin($_POST['login'], $errors, !$edit)){
		$login = SafeEnv($_POST['login'], 15, str);
	}else{
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
		}else{
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
	}else{
		$email = '';
		$errors[] = '�� �� ����� E-mail.';
	}
	// ������ e-mail
	if(isset($_POST['hideemail'])){
		$hideemail = '1';
	}else{
		$hideemail = '0';
	}
	// �������
	if(isset($_POST['nikname']) && CheckNikname($_POST['nikname'], $errors, !$edit)){
		$nikname = SafeEnv($_POST['nikname'], 50, str, true);
	}else{
		$nikname = '';
	}
	// ������ ���
	if(isset($_POST['realname'])){
		$realname = SafeEnv($_POST['realname'], 250, str, true);
	}else{
		$realname = '';
	}
	// ������� (� �����)
	if(isset($_POST['age'])){
		if($_POST['age'] == '' || is_numeric($_POST['age'])){
			$age = SafeEnv($_POST['age'], 3, int);
		}else{
			$errors[] = '��� ������� ������ ���� ������!';
		}
	}else{
		$age = '';
	}
	// �������� ��������
	if(isset($_POST['homepage'])){
		if($_POST['homepage'] != '' && substr($_POST['homepage'], 0, 7) == 'http://'){
			$_POST['homepage'] = substr($_POST['homepage'], 7);
		}
		$homepage = SafeEnv($_POST['homepage'], 250, str, true);
	}else{
		$homepage = '';
	}
	// ����� ICQ
	if(isset($_POST['icq'])){
		if($_POST['icq'] == '' || is_numeric($_POST['icq'])){
			$icq = SafeEnv($_POST['icq'], 15, str, true);
		}else{
			$errors[] = '����� ICQ ������ ��������� ������ �����!';
		}
	}else{
		$icq = '';
	}
	// �����
	if(isset($_POST['city'])){
		$city = SafeEnv($_POST['city'], 100, str, true);
	}else{
		$city = '';
	}
	// ������� ����
	if(isset($_POST['gmt'])){
		$gmt = SafeEnv($_POST['gmt'], 255, str);
	}else{
		$gmt = 'Europe/Moscow';
	}
	// � ����
	if(isset($_POST['about'])){
		$about = SafeEnv($_POST['about'], $config['user']['about_max_length'], str, true);
	}else{
		$about = '';
	}
	// �������� �� �������
	if(isset($_POST['snews'])){
		$snews = '1';
	}else{
		$snews = '0';
	}
	//������������ ������
	$alloy_mime = array('image/gif'=>'.gif', 'image/jpeg'=>'.jpg', 'image/pjpeg'=>'.jpg', 'image/png'=>'.png', 'image/x-png'=>'.png');
	$updateAvatar = true;
	if(isset($_POST['avatar'])){
		if($config['user']['avatar_transfer'] == '1' && isset($_FILES['upavatar']) && file_exists($_FILES['upavatar']['tmp_name'])){
			UserLoadAvatar($errors, $avatar, $a_personal, $usr['avatar'], $usr['a_personal']=='1', $edit);
		}elseif($_POST['avatar'] == ''){
			$updateAvatar = false;
		}elseif(file_exists(RealPath2($config['general']['avatars_dir'].$_POST['avatar']))){
			if($edit){
				if($usr['a_personal'] == '1'){
					UnlinkUserAvatarFiles($usr['avatar']);
				}
			}
			$a_personal = '0';
			$avatar = $_POST['avatar'];
		}else{
			$avatar = '';
			$a_personal = '0';
		}
	}else{
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
	}else{
		$active = '1';
		$code = '';
		$SendActivation = false;
	}

	if($edit && !$editStatus){
		$status = $usr['type'];
		$access = $usr['access'];
	}elseif($_POST['status'] == 'member' || !$user->SuperUser){
		$status = 2;
		$access = -1;
	}else{
		$status = 1;
		$access = SafeEnv($_POST['status'], 11, int);
	}

	$regdate = time();
	$lastvisit = time();
	$ip = getip();
	$points = 0;
	$visits = 0;
	if($SendActivation){
		UserSendActivationMail($nikname , $email, $login, $pass, $code, $regdate);
	}elseif(!$edit){
		UserSendEndRegMail($email, $nikname, $login, $pass, $regdate);
	}

	if(!$edit){
		$vals = Values('', $login, $pass2, $nikname, $realname, $age, $email, $hideemail, $city, $icq, $homepage, $gmt, $avatar, $about, $snews, $regdate, $lastvisit, $ip, $points, $visits, $active, $code, $status, $access, $a_personal);
		$db->Insert('users', $vals);
	}else{
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
	}else{
		// ������� ��� �������������
		$cache = LmFileCache::Instance();
		$cache->Delete(system_cache, 'users');
		GO($config['admin_file'].'?exe='.$back_link);
	}
}

?>