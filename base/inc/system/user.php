<?php

$system_users_cache = null;
$system_userranks_cache = null;
$system_usertypes_cache = null;

/**
 * Возвращает массив данных о пользователях с ключами по id.
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
			// На всякий случай кеш обновляется один раз в сутки
			$cache->Write(system_cache, 'users', $system_users_cache, Day2Sec);
		}
	}
	return $system_users_cache;
}

/**
 * Возвращает ранги пользователей
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
 * Возвращает типы пользователей
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
 * Поверка E-mail пользователя используемая при регистрации
 * @param $Email
 * @param $error_out
 * @param bool $CheckExist
 * @param int $xor_id
 * @return bool
 */
function CheckUserEmail( $Email, &$error_out, $CheckExist=false, $xor_id=0 ){
	global $db, $config;
	if($Email == ''){
		$error_out[] = 'Вы не ввели ваш E-mail адрес.';
		return false;
	}
	if(!CheckEmail($Email)){
		$error_out[] = 'Не правильный формат E-mail. Он должен быть вида: <b>domain@host.ru</b> .';
		return false;
	}
	if($CheckExist){
		$db->Select('users', "`email`='$Email'".($xor_id<>0?' and `id`<>'.$xor_id:''));
		if($db->NumRows() > 0){
			$error_out[] = 'Пользователь с таким E-mail уже зарегистрирован !';
			$result = false;
		}
	}
	return true;
}

/**
 * Проверяет логин на корректность
 * @param String $login Логин
 * @param $error_out Переменная в которую произвести вывод ошибок
 * @param bool $CheckExist Произвести проверку на занятость логина
 * @param int $xor_id
 * @return Boolean Истина если логин верный
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
		$error_out[] = 'Логин должен быть не менее '.$minlength.' и не более 15 символов.';
		$result = false;
	}
	if(preg_match('/[^a-zA-Zа-яА-Я0-9_]/', $login)){
		$error_out[] = 'Ваш логин должен состоять только из русских или латинских букв, цифр и символов подчеркивания.';
		$result = false;
	}
	if($CheckExist){
		$db->Select('users',"`login`='$login'".($xor_id<>0?' and `id`<>'.$xor_id:''));
		if($db->NumRows()>0){
			$error_out[] = 'Пользователь с таким логином уже зарегистрирован !';
			$result = false;
		}
	}
	return $result;
}

/**
 * Проверяет никнейм на корректность
 * @param String $nikname Никнейм
 * @param $error_out Переменная в которую произвести вывод ошибок
 * @param bool $CheckExist Произвести проверку на занятость логина
 * @param int $xor_id
 * @return Boolean Истина если пароль верный
 */
function CheckNikname( $nikname, &$error_out, $CheckExist=false, $xor_id=0 ){
	global $db, $config;
	$result = true;
	if($nikname == ''){
		$error_out[] = 'Вы не ввели Имя!';
		$result = false;
	}
	if(preg_match("/[^a-zA-Zа-яА-Я0-9_ ]/",$nikname)){
		$error_out[] = 'Ваше имя должно состоять только из русских или латинских букв и цифр, символов подчеркивания и пробела.';
		$result = false;
	}
	if($CheckExist){
		$db->Select('users',"`name`='$nikname'".($xor_id<>0?' and `id`<>'.$xor_id:''));
		if($db->NumRows()>0){
			$error_out[] = 'Пользователь с таким именем уже зарегистрирован !';
			$result = false;
		}
	}
	return $result;
}

/**
 * Проверяет пароль на корректность
 * @param String $pass Пароль
 * @param $error_out Переменная в которую произвести вывод ошибок (массив)
 * @return Boolean Истина если пароль верный
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
		$error_out[] = 'Пароль должен быть не короче '.$minlength.' символов.';
		$result = false;
	}
	return $result;
}


/**
 * Возвращает полную информацию о пользователе включая ранг, картинку ранга, статус онлайн, имя файла аватара для вывода. Вся информация кэшируется.
 * @param $user_id
 * @return array|bool
 */
function GetUserInfo($user_id){
	$system_users_cache = GetUsers();
	if(isset($system_users_cache[$user_id])){
		$usr = $system_users_cache[$user_id];
		// Аватар
		$usr['avatar_file'] = GetUserAvatar($user_id);
		$usr['avatar_file_small'] = GetSmallUserAvatar($user_id, $usr['avatar_file']);
		$usr['avatar_file_smallest'] = GetSmallestUserAvatar($user_id,  $usr['avatar_file']);
		// Ранг
		$rank = GetUserRank($usr['points'],$usr['type'],$usr['access']);
		$usr['rank_name'] = $rank[0];
		$usr['rank_image'] = $rank[1];
		// Статус онлайн
		$online = System::user()->Online();
		$usr['online'] = isset($online[$user_id]);
		return $usr;
	}else{
		return false;
	}
}

/**
 * Возвращает имя файла аватара пользователя. Алиас к GetPersonalAvatar.
 * @param $user_id
 * @return string
 */
function GetUserAvatar( $user_id ){
	return GetPersonalAvatar($user_id);
}

/**
 * Возвращает имя файла уменьшенной копии аватара пользователя в 64px
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
 * Возвращает имя файла сильно уменьшенной копии аватара пользователя в 24px
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
 * Возвращает имя файла аватара пользователя
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
 * Возвращает адрес аватара из галереи по имени файла
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
 * Возвращает название, картинку и идентификатор ранга пользователя
 * @param $points
 * @param $type
 * @param $access
 * @return array
 */
function GetUserRank($points, $type, $access){
	global $config, $db;
	static $admintypes = null;
	if($type == '2'){ // Пользователь
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
	}else{ // Администратор
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
 * Отправка письма для активации по E-mail
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

	SendMail($username, $user_mail, 'Регистрация на '.$config['general']['site_name'], $text);
}

/**
 * Отправка письма по завершении регистрации
 * @param $user_mail
 * @param $name
 * @param $login
 * @param $pass
 * @param $regtime
 * @return void
 */
function UserSendEndRegMail($user_mail, $name, $login, $pass, $regtime){
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

/**
 * Отправка письма с новым паролем
 * @param $user_mail
 * @param $name
 * @param $login
 * @param $pass
 * @return void
 */
function UserSendForgotPassword($user_mail, $name, $login, $pass){
	global $config;
	$ip = getip();
	$text = 'Здравствуйте, ['.$name.']!

На сайте '.$config['general']['site_url'].'
было запрошено напоминание пароля.

Имя: '.$name.'

Ваш логин и новый пароль:
логин: '.$login.'
пароль: '.$pass.'

Изменить данные аккаунта вы можете по адресу:
'.GetSiteUrl().Ufu('index.php?name=user&op=editprofile', 'user/{op}/').'

IP-адрес, с которого был запрошен пароль: '.$ip.'

С уважением, администрация сайта '.$config['general']['site_url'].'.';
	SendMail($name, $user_mail, '['.$config['general']['site_url'].'] Напоминание пароля', $text);
}

/**
 * Выполняет поиск аватар в галерее и генерирует данные для HTML::Select
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
		$site->DataAdd($avd, '', 'Персональный', true);
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
 * Функция управляет загрузкой аватар ($_FILES['upavatar'])
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

	//Проверка формата файла
	$alloy_mime = array(
		'image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png'
	);
	$alloy_exts = array(
		'.gif', '.jpg', '.jpeg', '.png'
	);
	if(in_array($_FILES['upavatar']['type'], $alloy_mime) && in_array(strtolower(GetFileExt($_FILES['upavatar']['name'])), $alloy_exts)){
		// Удаляем старый аватар
		if($editmode && $oldAvatarPersonal == '1'){
			UnlinkUserAvatarFiles($oldAvatarName);
		}

		//Выполняем ресайз, если нужно, и сохраняем аватар в папку персональных аватар
		$NewName = GenRandomString(8, 'qwertyuiopasdfghjklzxcvbnm');
		$ext = strtolower(GetFileExt($_FILES['upavatar']['name']));

		if($asize[0] > $config['user']['max_avatar_width'] || $asize[1] > $config['user']['max_avatar_height']){
			$thumb = new TPicture($_FILES['upavatar']['tmp_name']);
			$thumb->SetImageSize($config['user']['max_avatar_width'], $config['user']['max_avatar_height']);
			$thumb->SaveToFile($config['general']['personal_avatars_dir'].$NewName.$ext);
		} else{
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
	} else{
		$errors[] = 'Неправильный формат аватара. Ваш аватар должен быть формата GIF, JPEG или PNG.';
		$a_personal = '0';
	}
}

/**
 * Удаляет все размеры аватара по его имени
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
 * Генерирует данные уровня доступа для Html::Select
 * @param array | int $view Массиы со значением для каждого уровня или номер выделенного уровня
 * @return array | int
 */
function GetUserTypesFormData( $view ){
	$visdata = array();
	if(!is_array($view)){
		$_view = $view;
		$view = array('1'=>false, '2'=>false, '3'=>false, '4'=>false);
		$view[$_view] = true;
	}
	System::admin()->DataAdd($visdata, 'all', 'Все', $view['4']);
	System::admin()->DataAdd($visdata, 'members', 'Только пользователи', $view['2']);
	System::admin()->DataAdd($visdata, 'guests', 'Только гости', $view['3']);
	System::admin()->DataAdd($visdata, 'admins', 'Только администраторы', $view['1']);
	return $visdata;
}

/**
 * Подсчитывает количество главных администраторов
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
	//Подсчитываем количество главных администраторов
	$system = 0;
	for($i = 0, $c = count($admins); $i < $c; $i++){
		if($types[$admins[$i]['access']] == '1'){
			$system++;
		}
	}
	return $system;
}

/**
 * Проверяет системная ли группа по id группы
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
 * Генерирует форму редактирования пользователя в админ-панели
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
				'member', 'Пользователь', false
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
			AddTextBox('Ошибка', '<p><center>Пользователь не найден, либо у вас не достаточно прав для редактирования администраторов.</center></p>');
			return;
		}
		$usr = $db->FetchRow();
		$SystemUser = false;
		$editStatus = false;

		if($isadmin){
			$SystemUser = groupIsSystem(SafeEnv($usr['access'], 11, int));
			//пользователь - последний системный администратор
			if($SystemUser && GetSystemAdminsCount() <= 1){
				$editStatus = false;
			} else{ //Если он не системный или системных больше 1
				$editStatus = true;
			}
		} else{ // Если пользователь, то, если у нас есть права создавать админов
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
			$types[0][2] = true; //пользователь
		}

		if($usr['active'] == '1'){
			$active[0] = true;
		} elseif($usr['active'] == '0' && $usr['activate'] == ''){
			$active[1] = true;
		} elseif($usr['active'] == '0' && $usr['activate'] != ''){
			$active[2] = true;
		}

		$caption = 'Сохранить';
		$title = 'Редактирование пользователя';
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
		$caption = 'Добавить';
		$title = 'Добавить пользователя';
		$editStatus = true;
	}
	FormRow('Логин', $site->Edit('login', $login, false, 'style="width:400px;"'));
	FormRow('Пароль', $site->Edit('pass', '', true, 'style="width:400px;"'));
	FormRow('Повторите пароль<br /><small>(для проверки)</small>', $site->Edit('rpass', '', true, 'style="width:400px;"'));
	FormRow('E-mail', $site->Edit('email', $mail, false, 'style="width:300px;"').' <label for="hideemail">Скрыть</label>&nbsp;'.$site->Check('hideemail', '1', $hideemail, 'id="hideemail"'));
	FormRow('<label for="snews">Рассылка</label>', $site->Check('snews', '1', $snews, 'id="snews"'));
	FormRow('Ник', $site->Edit('nikname', $name, false, 'style="width:400px;"'));
	FormRow('Настоящее имя', $site->Edit('realname', $tname, false, 'style="width:400px;"'));
	FormRow('Возраст', $site->Edit('age', $age, false, 'style="width:400px;"'));
	FormRow('Город', $site->Edit('city', $city, false, 'style="width:400px;"'));
	FormRow('Сайт', $site->Edit('homepage', $url, false, 'style="width:400px;"'));
	FormRow('ICQ', $site->Edit('icq', $icq, false, 'style="width:400px;"'));
	$gmt = GetGmtData($gmt);
	FormRow('Часовой пояс', $site->Select('gmt', $gmt, false, 'style="width:400px;"'));
	FormRow('О себе', $site->TextArea('about', $about, 'style="width:400px; height:200px;"'));
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
	FormRow('Аватар', '<center>'.$site->Select('avatar', $avatars[0], false, 'onchange="ShowAvatar();"').'</center>');
	FormRow('', '<center><img id="avatarview" src="'.$selected.'" border="0" width="64" /></center>');
	FormRow('Загрузить аватар', $site->FFile('upavatar'));
	if($editStatus){
		FormRow('Активация', $site->Radio('activate', 'auto', $active[0]).'Активировать'.$site->Radio('activate', 'manual', $active[1]).'Не активировать'.(!$isadmin ? $site->Radio('activate', 'mail',
			$active[2]).'По E-mail' : ''));
	}
	if($user->SuperUser && $editStatus){
		$usertypes = array(
		);
		foreach($types as $type){
			$site->DataAdd($usertypes, $type[0], $type[1], $type[2]);
		}
		FormRow('Статус', $site->Select('status', $usertypes));
	}
	TAddSubTitle($title);
	AddCenterBox($title);
	AddForm('<form name="userform" action="'.
	        $config['admin_file'].'?exe='.$save_link.'&id='.$id.'" method="post"  enctype="multipart/form-data">', $site->Button('Отмена', 'onclick="history.go(-1);"').$site->Submit($caption));
}

/**
 * Сохраняет данные формы сгенерированной фукцией AdminUserEditor
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
			//пользователь - последний системный администратор
			if($SystemUser && GetSystemAdminsCount() <= 1){
				$editStatus = false;
			} else{ //Если он не системный или системных больше 1
				$editStatus = true;
			}
		} else{ // Если пользователь, то, если у нас есть права создавать админов
			$editStatus = true;
		}
	} else{
		$edit = false;
		$editStatus = true;
	}

	$errors = array(
	);

	//Обрабатываем некоторые приходящие данные
	// Логин
	if(isset($_POST['login']) && CheckLogin($_POST['login'], $errors, !$edit)){
		$login = SafeEnv($_POST['login'], 15, str);
	} else{
		$login = '';
	}
	// Пароль
	$pass = '';
	if(!$edit || $_POST['pass'] != ''){
		$passmsg = '';
		if(isset($_POST['pass']) && CheckPass($_POST['pass'], $errors)){
			$pass = SafeEnv($_POST['pass'], 30, str);
			if(!isset($_POST['rpass']) || SafeEnv($_POST['rpass'], 30, str) != $pass){
				$errors[] = 'Пароли не совпадают.';
			}
		} else{
			$pass = '';
		}
		if(isset($_POST['pass']) && $_POST['pass'] == ''){
			srand(time());
			$pass = GenBPass(rand($config['user']['pass_min_length'], 15));
			$passmsg = '<br />Так как вы не указали пароль, он был сгенерирован автоматически и выслан Вам на E-mail.';
		}
		$pass2 = md5($pass);
	}
	// e-mail
	if(isset($_POST['email']) && $_POST['email'] != ''){
		if(!CheckEmail($_POST['email'])){
			$errors[] = 'Не правильный формат E-mail. Он должен быть вида: <b>domain@host.ru</b> .';
		}
		$email = SafeEnv($_POST['email'], 50, str, true);
	} else{
		$email = '';
		$errors[] = 'Вы не ввели E-mail.';
	}
	// Скрыть e-mail
	if(isset($_POST['hideemail'])){
		$hideemail = '1';
	} else{
		$hideemail = '0';
	}
	// Никнейм
	if(isset($_POST['nikname']) && CheckNikname($_POST['nikname'], $errors, !$edit)){
		$nikname = SafeEnv($_POST['nikname'], 50, str, true);
	} else{
		$nikname = '';
	}
	// Полное имя
	if(isset($_POST['realname'])){
		$realname = SafeEnv($_POST['realname'], 250, str, true);
	} else{
		$realname = '';
	}
	// Возраст (в годах)
	if(isset($_POST['age'])){
		if($_POST['age'] == '' || is_numeric($_POST['age'])){
			$age = SafeEnv($_POST['age'], 3, int);
		} else{
			$errors[] = 'Ваш возраст должен быть числом!';
		}
	} else{
		$age = '';
	}
	// Домашняя страница
	if(isset($_POST['homepage'])){
		if($_POST['homepage'] != '' && substr($_POST['homepage'], 0, 7) == 'http://'){
			$_POST['homepage'] = substr($_POST['homepage'], 7);
		}
		$homepage = SafeEnv($_POST['homepage'], 250, str, true);
	} else{
		$homepage = '';
	}
	// Номер ICQ
	if(isset($_POST['icq'])){
		if($_POST['icq'] == '' || is_numeric($_POST['icq'])){
			$icq = SafeEnv($_POST['icq'], 15, str, true);
		} else{
			$errors[] = 'Номер ICQ должен содержать только числа!';
		}
	} else{
		$icq = '';
	}
	// Город
	if(isset($_POST['city'])){
		$city = SafeEnv($_POST['city'], 100, str, true);
	} else{
		$city = '';
	}
	// Часовой пояс
	if(isset($_POST['gmt'])){
		$gmt = SafeEnv($_POST['gmt'], 255, str);
	} else{
		$gmt = 'Europe/Moscow';
	}
	// О себе
	if(isset($_POST['about'])){
		$about = SafeEnv($_POST['about'], $config['user']['about_max_length'], str, true);
	} else{
		$about = '';
	}
	// Подписка на новости
	if(isset($_POST['snews'])){
		$snews = '1';
	} else{
		$snews = '0';
	}
	//Обрабатываем аватар
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
		$text = 'Аккаунт сохранен, но имели место следующие ошибки:<br /><ul>';
		foreach($errors as $error){
			$text .= '<li>'.$error;
		}
		$text .= '</ul>';
		AddTextBox('Внимание', $text);
	} else{
		// Очищаем кэш пользователей
		$cache = LmFileCache::Instance();
		$cache->Delete(system_cache, 'users');
		GO($config['admin_file'].'?exe='.$back_link);
	}
}

/**
 * Возвращает IP адрес пользователя
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
