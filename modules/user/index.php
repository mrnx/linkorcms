<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

IncludeFunction('user');
$site->SetTitle('Пользователи');

if(isset($_GET['op'])){
	$op = $_GET['op'];
}else{
	$op = 'main';
}

switch($op){
	case 'registration':
		if(isset($_POST['condition']) || $config['user']['view_conditions'] == 'off'){
			IndexUserRegistration();
		}elseif(isset($_POST['usersave'])){
			IndexUserRegistrationOk();
		}else{
			IndexUserConditions();
		}
	break;
	case 'editprofile':
		IndexUserRegistration(false,true);
	break;
	case 'userinfo':
		IndexUserInfo();
	break;
	case 'userslist':
		IndexUserlist();
	break;
	case 'forgotpass':
		IndexUserForgotPassword();
	break;
	case 'sendpassword':
		IndexUserSendPassword();
	break;
	default:
		HackOff();
}

function AcceptPost(&$login,&$email,&$hideemail,&$nikname,&$realname,&$age,&$homepage,&$icq,&$city,&$avatar,&$apersonal,&$gmt,&$about,&$snews)
{
	global $config;
	if(isset($_POST['login'])){
		$login = substr($_POST['login'],0,30);
	}else{
		$login = '';
	}
	if(isset($_POST['email'])){
		$email = substr($_POST['email'],0,50);
	}else{
		$email = '';
	}
	if(isset($_POST['hideemail'])){
		$hideemail = true;
	}else{
		$hideemail = false;
	}
	if(isset($_POST['nikname'])){
		$nikname = substr($_POST['nikname'],0,50);
	}else{
		$nikname = '';
	}
	if(isset($_POST['realname'])){
		$realname = substr($_POST['realname'],0,250);
	}else{
		$realname = '';
	}
	if(isset($_POST['age'])){
		$age = substr($_POST['age'],0,3);
	}else{
		$age = '';
	}
	if(isset($_POST['homepage'])){
		$homepage = substr(Url($_POST['homepage']),0,250);
	}else{
		$homepage = '';
	}
	if(isset($_POST['icq'])){
		$icq = substr($_POST['icq'],0,15);
	}else{
		$icq = '';
	}
	if(isset($_POST['city'])){
		$city = substr($_POST['city'],0,100);
	}else{
		$city = '';
	}
	if(isset($_POST['avatar'])){
		$avatar = substr($_POST['avatar'],0,250);
	}else{
		$avatar = '';
	}
	if(isset($_POST['gmt'])){
		$gmt = SafeEnv($_POST['gmt'], 255, str);
	}else{
		$gmt = 'Europe/Moscow';
	}
	if(isset($_POST['about'])){
		$about = substr($_POST['about'],0,$config['user']['about_max_length']);
	}else{
		$about = '';
	}
	if(isset($_POST['snews'])){
		$snews = true;
	}else{
		$snews = false;
	}
}

#Загрузка данных пользователя из базы данных для редактирования
function GetEditUserData( &$login, &$email, &$hideemail, &$nikname, &$realname, &$age, &$homepage, &$icq, &$city, &$avatar, &$apersonal, &$gmt, &$about, &$snews)
{
	global $config, $db, $user;
	$db->Select('users', "`id`='".$user->Get('u_id')."'");
	$u = $db->FetchRow();
	$login = SafeDB($u['login'], 30, str);
	$email = SafeDB($u['email'], 50, str);
	$hideemail = SafeDB($u['hideemail'], 1, int);
	$nikname = SafeDB($u['name'], 50, str);
	$realname = SafeDB($u['truename'], 250, str);
	$age = SafeDB($u['age'], 11, str);
	$homepage = SafeDB($u['url'], 250, str);
	$icq = SafeDB($u['icq'], 15, str);
	$city = SafeDB($u['city'], 100, str);
	$avatar = SafeDB($u['avatar'], 250, str);
	$apersonal = SafeDB($u['a_personal'], 1, int);
	$gmt = SafeDB($u['timezone'], 255, str);
	$about = SafeDB($u['about'], 0, str);
	$snews = SafeDB($u['servernews'], 1, int);
}

function IndexUserConditions()
{
	global $config, $site, $site;
	if($config['user']['registration']=='off'){
		$site->AddTextBox('','<center>Извините, регистрация приостановлена.</center>');
		return;
	}
	$site->SetTitle('Условия регистрации');
	$vars['taname'] = 'conditions';
	$vars['conditions'] = $config['user']['reg_condition'];
	$vars['reg_url'] = Ufu('index.php?name=user&op=registration', 'user/{op}/');
	$vars['lsubmit'] = 'Согласен';

	$site->AddTemplatedBox('Условия регистрации','module/user_conditions.html');
	$site->AddBlock('user_mod',true,false,'user');
	$site->Blocks['user_mod']['vars'] = $vars;
}

// Форма регистрации / редактирования пользователя
function IndexUserRegistration($acceptPost=false, $edit=false)
{
	global $config, $site, $site, $user;
	if(!$edit){
		$user->UnLogin(false);
	}else{
		if(!$user->Auth){
			$site->Login();
			return;
		}
	}
	$site->AddJS('
	function checkData(f){
		if(f.login.value = \'\'){
			alert("Логин должен быть не менее '.$config['user']['login_min_length'].' и не более 15 символов.");
			f.login.focus();
			return false;
		}
	}
	');

	if(!$edit && $config['user']['registration']=='off'){
		$site->AddTextBox('Ошибка','<center>Извините, регистрация приостановлена.</center>');
		return;
	}
	if($acceptPost){
		AcceptPost($login,$email,$hideemail,$nikname,$realname,$age,$homepage,$icq,$city,$avatar,$apersonal,$gmt,$about,$snews);
	}elseif($edit){
		GetEditUserData($login,$email,$hideemail,$nikname,$realname,$age,$homepage,$icq,$city,$avatar,$apersonal,$gmt,$about,$snews);
	}else{
		$login = '';
		$email = '';
		$hideemail = false;
		$nikname = '';
		$realname = '';
		$age = '';
		$homepage = '';
		$icq = '';
		$city = '';
		$avatar = 'noavatar.gif';
		$apersonal = '0';
		$gmt = '0';
		$about = '';
		$snews = false;
	}

	//Генерируем текст формы
	$site->AddBlock('user_form',true,false,'form');
	$vars = array();
	if($edit){
		$vars['action'] = 'update';
		$vars['laction'] = 'Сохранить';
		$topcaption = 'Данные пользователя';
	}else{
		$vars['action'] = 'create';
		$vars['laction'] = 'Зарегистрироваться';
		$topcaption = 'Регистрация';
	}
	$fields = explode(',', $config['user']['register_call_data']);//email,snews,realname,age,city,icq,gmt,about
	$activate = $config['user']['activate_type'];//auto, mail, manual

	$vars['form_name'] = 'userform';
	$vars['url'] = Ufu('index.php?name=user&op=registration', 'user/{op}/');
	$vars['enctype'] = 'multipart/form-data';

	$vars['llogin'] = 'Логин <font color="#FF0000">*</font>';
	$vars['login'] = $login;

	$vars['lpass'] = 'Пароль <font color="#FF0000">*</font>';
	$vars['pass'] = '';

	$vars['lrpass'] = 'Повторите пароль <font color="#FF0000">*</font>';
	$vars['rpass'] = '';

	$vars['lnikname'] = 'Ваше имя на сайте <font color="#FF0000">*</font>';
	$vars['nikname'] = $nikname;

	$vars['lrealname'] = 'Настоящее имя(Ф.И.О.)';
	$vars['realname'] = $realname;
	$vars['use_realname'] = (in_array('realname',$fields) || $edit);

	$vars['lemail'] = 'E-mail <font color="#FF0000">*</font>';
	$vars['email'] = $email;
	$vars['lhideemail'] = 'Скрыть e-mail';
	$vars['hideemail'] = ($hideemail?' checked="checked"':'');
	$vars['use_email'] = (in_array('email',$fields) || $activate=='mail' || $edit);

	$vars['lage'] = 'Возраст';
	$vars['age'] = $age;
	$vars['use_age'] = (in_array('age',$fields) || $edit);

	$vars['lhomepage'] = 'Сайт';
	$vars['homepage'] = $homepage;
	$vars['use_homepage'] = (in_array('homepage',$fields) || $edit);

	$vars['licq'] = 'Номер ICQ';
	$vars['icq'] = $icq;
	$vars['use_icq'] = (in_array('icq',$fields) || $edit);

	$vars['lcity'] = 'Город';
	$vars['city'] = $city;
	$vars['use_city'] = (in_array('city',$fields) || $edit);

	$vars['lavatar'] = 'Аватар';
	$vars['lload_avatar'] = 'Загрузить аватар<br>(Размеры картинки могут быть автоматически уменьшены. Допустимые форматы: gif, jpeg, png.)';
	$vars['avatar_onchange_func'] = 'ShowAvatar()';
	$vars['avatar_filename'] = $avatar;
	$vars['use_avatar'] = (in_array('avatar', $fields) || $edit);

	if($vars['use_avatar']){
		$site->AddBlock('avatars', true, true, 'avatar');
		$avatars = GetFiles($config['general']['avatars_dir'], false, true, '.gif.jpg.jpeg.png');
		if($apersonal=='1'){
			$selected = GetPersonalAvatar($user->Get('u_id'));
		}elseif($edit){
			$selected = GetGalleryAvatar($avatar);
		}else{
			$selected = GetGalleryAvatar($avatars[1]);
		}

		$selindex = 0;
		$avd = array();
		if($apersonal=='1'){
			$site->AddSubBlock('avatars', true, array('name'=>'','caption'=>'Персональный','selected'=>true));
		}


		for($i=0, $c=count($avatars); $i<$c; $i++){
			$avars = array();
			$sel = ($avatar==$avatars[$i]);
			$avars['name'] = $avatars[$i];
			$avars['selected'] = $sel;
			$avars['caption'] = $avatars[$i];
			$site->AddSubBlock('avatars', true, $avars);
		}

		$vars['av_selected'] = $selected;

		$site->AddJS('
			function ShowAvatar(){
				if(document.userform.avatar.value==\'\'){
					document.userform.avatarview.src = \''.($config['user']['secure_avatar_upload']=='1'?'index.php?name=plugins&p=avatars_render&user='.$user->Get('u_id'):$config['general']['personal_avatars_dir'].$avatar).'\';
				}else{
					document.userform.avatarview.src = \''.($config['user']['secure_avatar_upload']=='1'?'index.php?name=plugins&p=avatars_render&aname=':$config['general']['avatars_dir']).'\'+document.userform.avatar.value;
				}
			}'
		);
	}

	$vars['lgmt'] = 'Часовой пояс';
	$vars['use_gmt'] = (in_array('gmt',$fields) || $edit);
	if($vars['use_gmt']){
		$gmtd = GetGmtArray();
		$site->AddBlock('gmt_data', true, true, 'gmt');
		for($i=0,$c=count($gmtd);$i<$c;$i++){
			$gvars['name'] = $gmtd[$i][1];
			$gvars['caption'] = $gmtd[$i][0];
			$gvars['selected'] = ($gmt==$gmtd[$i][1]);
			$site->AddSubBlock('gmt_data', true, $gvars);
		}
	}
	$vars['labout'] = 'Немного о себе';
	$vars['about'] = $about;
	$vars['use_about'] = (in_array('about', $fields) || $edit);

	$vars['kaptcha_url'] = 'index.php?name=plugins&p=antibot';
	$vars['kaptcha_width'] = '120';
	$vars['kaptcha_height'] = '40';

	$vars['lsnews'] = 'Разрешить администраторам сайта присылать Вам уведомления по электронной почте';
	$vars['snews'] = ($snews?' checked="checked"':'');
	$vars['use_snews'] = (in_array('snews', $fields) || $edit);

	$site->AddTemplatedBox($topcaption,'module/user_form.html');
	$site->SetTitle($topcaption);
	$site->Blocks['user_form']['vars'] = $vars;
}

function IndexUserRegistrationOk()
{
	global $db, $config, $site, $user;
	$site->SetTitle('Регистрация на сайте');

	if(isset($_POST['usersave']) && $_POST['usersave'] == 'update'){
		$edit = true;
		$user_id = $user->Get('u_id');
		$db->Select('users',"`id`='".$user_id."'");
		$usr = $db->FetchRow();
	}else{
		$edit = false;
	}
	if(!$edit){
		$user->UnLogin(false);
	}else{
		if(!$user->Auth){
			GO(Ufu('index.php'));
		}
	}
	if($config['user']['registration']=='off' && !$edit){
		$site->AddTextBox('Ошибка','<center>Извините, регистрация временно приостановлена.</center>');
		return;
	}
	$errors = array();

	//Обрабатываем некоторые приходящие данные
	# Логин
	if(isset($_POST['login']) && CheckLogin(SafeEnv($_POST['login'],15,str),$errors,true,($edit?$user_id:0))){
		$login = SafeEnv($_POST['login'],15,str);
	}else{
		$login = '';
	}
	# Пароль
	$pass = '';
	if(!$user->isAdmin() && $_POST['pass']<>''){
		$passmsg = '';
		if(isset($_POST['pass']) && CheckPass(SafeEnv($_POST['pass'],255,str),$errors)){
			$pass = SafeEnv($_POST['pass'],255,str);
			if(!isset($_POST['rpass']) || SafeEnv($_POST['rpass'],255,str)<>$pass){
				$errors[] = 'Пароли не совпадают.';
			}
		}else{
			$pass = '';
		}
		if(isset($_POST['pass']) && $_POST['pass']==''){
			srand(time());
			$pass = GenBPass(rand($config['user']['pass_min_length'],15));
			$passmsg = '<br>Так как Вы не указали пароль, он был сгенерирован автоматически и выслан Вам на E-mail.';
		}
		$pass2 = md5($pass);
	}
	# E-mail
	if(!$user->isAdmin() && isset($_POST['email']) && CheckUserEmail(SafeEnv($_POST['email'],50,str,true), $errors, true, ($edit?$user_id:0)) ){
		$email = SafeEnv($_POST['email'],50,str,true);
	}else{
		$email = '';
	}
	# Скрыть E-mail
	if(isset($_POST['hideemail'])){
		$hideemail = '1';
	}else{
		$hideemail = '0';
	}
	# Никнейм
	if(isset($_POST['nikname']) && CheckNikname(SafeEnv($_POST['nikname'],50,str,true),$errors,true,($edit?$user_id:0))){
		$nikname = SafeEnv($_POST['nikname'],50,str,true);
	}else{
		$nikname = '';
	}
	# Настоящее имя
	if(isset($_POST['realname'])){
		$realname = SafeEnv($_POST['realname'],250,str,true);
	}else{
		$realname = '';
	}
	# Возраст лет
	if(isset($_POST['age'])){
		if($_POST['age']=='' || is_numeric($_POST['age'])){
			$age = SafeEnv($_POST['age'],3,int);
		}else{
			$errors[] = 'Ваш возраст должен быть числом!';
		}
	}else{
		$age = '';
	}
	# Адреc домашней страницы
	if(isset($_POST['homepage'])){
		$homepage = SafeEnv(Url($_POST['homepage']),250,str,true);
	}else{
		$homepage = '';
	}
	# Номер ICQ
	if(isset($_POST['icq'])){
		if($_POST['icq']=='' || is_numeric($_POST['icq'])){
			$icq = SafeEnv($_POST['icq'],15,str,true);
		}else{
			$errors[] = 'Номер ICQ должен содержать только числа!';
		}
	}else{
		$icq = '';
	}
	# Город
	if(isset($_POST['city'])){
		$city = SafeEnv($_POST['city'],100,str,true);
	}else{
		$city = '';
	}
	# Часовой пояс
	if(isset($_POST['gmt'])){
		$gmt = SafeEnv($_POST['gmt'], 255, str);
	}else{
		$gmt = 'Europe/Moscow';
	}
	# О себе
	if(isset($_POST['about'])){
		$about = SafeEnv($_POST['about'],$config['user']['about_max_length'],str,true);
	}else{
		$about = '';
	}
	# Подписка на рассылку
	if(isset($_POST['snews'])){
		$snews='1';
	}else{
		$snews='0';
	}

	if(!$edit && (!$user->Auth && !$user->isDef('captcha_keystring') || $user->Get('captcha_keystring') != $_POST['keystr'])){
		$errors[] = 'Вы ошиблись при вводе кода с картинки.';
	}

	//Аватар
	$updateAvatar = true;
	if(isset($_POST['avatar'])){
		if($config['user']['avatar_transfer']=='1' && isset($_FILES['upavatar']) && file_exists($_FILES['upavatar']['tmp_name'])){
			UserLoadAvatar($errors, $avatar, $a_personal, $usr['avatar'], $usr['a_personal']=='1', $edit);
		}elseif($_POST['avatar']==''){
			$updateAvatar = false;
		}elseif(file_exists(RealPath2($config['general']['avatars_dir'].$_POST['avatar']))){
			if($edit){
				if($usr['a_personal']=='1'){
					UnlinkUserAvatarFiles($usr['avatar']);
				}
			}
			$a_personal = '0';
			$avatar = $_POST['avatar'];
		}else{
			$avatar = 'noavatar.gif';
			$a_personal = '0';
		}
	}else{
		$avatar = 'noavatar.gif';
		$a_personal = '0';
	}

	# Активация аккаунта
	if($edit){
		$active = '1';
		$code = '';
		$SendActivation = false;
	}else{
		$activate = $config['user']['activate_type'];
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
				$code = GenRandomString(32);
				$SendActivation = true;
			break;
		}
	}
	$status = 2;
	$access = -1;

	$regdate = time();
	$lastvisit = time();
	$ip = getip();
	$points = 0;
	$visits = 0;

	// Сохранение
	if(count($errors)==0){
		if($SendActivation){
			UserSendActivationMail($nikname, $email, $login, $pass, $code, $regdate);
			$endmsg = '<br>На указанный Вами E-Mail отправлено письмо,
				содержащее ссылку для подтверждения регистрации.
				Для активации Вашего аккаунта перейдите по данной ссылке
				и подтвердите регистрацию!';
		}elseif(!$edit){
			UserSendEndRegMail($email, $nikname, $login, $pass, $regdate);
			$endmsg = '<br>На ваш E-mail отправлено письмо с данными о
				регистрации.';
		}
		if(!$edit){ // Добавление нового пользователя
			$vals = Values('',$login, $pass2, $nikname, $realname,
				$age, $email ,$hideemail, $city, $icq,
				$homepage, $gmt, $avatar, $about, $snews,
				$regdate, $lastvisit, $ip, $points, $visits,
				$active, $code, $status, $access, $a_personal);

			$db->Insert('users',$vals);
			$site->AddTextBox('Регистрация'
			,'<center>Поздравляем! Вы успешно зарегистрированы на сайте.'.$passmsg.$endmsg
			.'<br>С уважением, администрация сайта <b>'.$config['general']['site_name'].'.</b></center>');

			// Очищаем кэш пользователей
			$cache = LmFileCache::Instance();
			$cache->Delete(system_cache, 'users');

		}else{ // Сохранение изменений
			$set = "login='$login',hideemail='$hideemail',name='$nikname',"
			."truename='$realname',age='$age',url='$homepage',icq='$icq',city='$city',timezone='$gmt'"
			.($updateAvatar==true?",avatar='$avatar',a_personal='$a_personal'":'').",about='$about',"
			."servernews='$snews'".($pass<>''?",pass='$pass2'":'').($email<>''?",email='$email'":'');

			$db->Update('users', $set,"`id`='".$user->Get('u_id')."'");
			$user->UpdateMemberSession();
			UpdateUserComments($user->Get('u_id'), $user->Get('u_id'), $nikname, $email, $hideemail, $homepage, getip());

			// Очищаем кэш пользователей
			$cache = LmFileCache::Instance();
			$cache->Delete(system_cache, 'users');
			GO(GetSiteUrl().Ufu('index.php?name=user&op=userinfo', 'user/{op}/'));
		}
	}else{ // Ошибка
		$text = 'Ваш аккаунт не '.($edit?'сохранен':'добавлен').', произошли следующие ошибки:<br><ul>';
		foreach($errors as $error){
			$text .= '<li>'.$error;
		}
		$text .= '</ul>';
		// Удаляем аватар
		if($a_personal == '1' && !$edit){
			unlink($config['general']['personal_avatars_dir'].$avatar);
		}
		$site->AddTextBox('Ошибка', $text);
		IndexUserRegistration(true, $edit);
	}
}

function IndexUserInfo()
{
	global $config, $db, $user, $site;

	if(isset($_GET['user'])){
		$user_id = SafeEnv($_GET['user'],11,int);
	}elseif($user->Auth){
		$user_id = $user->Get('u_id');
	}else{
		$site->Login();
		return;
	}

	$usr = GetUserInfo($user_id);

	if($usr !== false){

		$site->SetTitle('Информация о пользователе '.SafeDB($usr['name'], 50, str));
		$site->AddTemplatedBox('','module/user_info.html');
		$site->AddBlock('userinfo',true,false,'user');

		$vars['user_id'] = SafeDB($usr['id'], 11, int);
		$vars['name'] = SafeDB($usr['name'], 50, str);
		$vars['true_name'] = SafeDB($usr['truename'], 250, str);
		$vars['avatar'] = RealPath2(SafeDB($usr['avatar_file'], 255, str));
		$vars['rankimage'] = RealPath2(SafeDB($usr['rank_image'], 255, str));
		$vars['rank'] = SafeDB($usr['rank_name'], 255, str);
		$vars['age'] = SafeDB($usr['age'], 11, str);
		$vars['city'] = SafeDB($usr['city'], 100, str);
		if($usr['hideemail']=='1'){
			$vars['email'] = 'Скрывается';
		}else{
			$vars['email'] = SafeDB($usr['email'], 50, str);
		}
		$vars['icq'] = SafeDB($usr['icq'], 15, str);
		$vars['site'] = Url(SafeDB($usr['url'], 250, str));
		$vars['site_url'] = UrlRender(SafeDB($usr['url'], 250, str));
		$vars['about'] = SafeDB($usr['about'], 0, str);
		$vars['regdate'] = TimeRender($usr['regdate'], false);
		$vars['lastdate'] = TimeRender($usr['lastvisit']);
		$vars['counter'] = SafeDB($usr['visits'], 11, int);
		if($usr['online']){
			$vars['online'] = 'Сейчас на сайте.';
		}else{
			$vars['online'] = '';
		}

		$site->Blocks['userinfo']['vars'] = $vars;
	}else{
		$site->AddTextBox('Ошибка','<center>Пользователь не найден.<center>');
	}
}

function IndexUserlist()
{
	global $config, $db, $site;
	$site->SetTitle('Список пользователей');

	$page = 0;
	if(isset($_GET['page'])){
		$page=SafeEnv($_GET['page'],11,int);
	}else{
		$page=1;
	}
	$users = $db->Select('users',"`active`='1'");
	SortArray($users, 'points', true); // regdate
	SortArray($users, 'type', false); // type
	$num = $config['user']['users_on_page'];

	$navigation = new Navigation($page);
	$navigation->FrendlyUrl = $config['general']['ufu'];
	$navigation->GenNavigationMenu($users, $num, Ufu('index.php?name=user&op=userslist', 'user/users/page{page}/', true));

	$site->AddTemplatedBox('Список пользователей', 'module/user_list.html');
	$site->AddBlock('userlist_th',true,false,'title');
	$site->Blocks['userlist_th']['vars'] = array('name'=>'Имя', 'email'=>'E-mail', 'date'=>'Дата регистрации', 'last'=>'Посл. посещение', 'rank'=>'Ранг/статус');
	$site->AddBlock('userlist',true,true,'user');
	foreach($users as $usr){
		$vars = array();
		$rank_stat = GetUserRank($usr['points'], $usr['type'], $usr['access']);
		$rank_stat = $rank_stat[0];
		$vars['avatar'] = GetUserAvatar($usr['id']);
		$vars['avatar_small'] = GetSmallUserAvatar($usr['id'], $vars['avatar']);
		$vars['avatar_smallest'] = GetSmallestUserAvatar($usr['id'], $vars['avatar']);
		$vars['user_id'] =  SafeDB($usr['id'], 11, int);
		$vars['url'] = Ufu('index.php?name=user&op=userinfo&user='.SafeDB($usr['id'], 11, int), 'user/{user}/info/');
		$vars['name'] = SafeDB($usr['name'], 50, str);
		if($usr['hideemail']=='1'){
			$vars['email'] = 'Скрывается';
		}else{
			$vars['email'] = SafeDB($usr['email'], 50, str);
		}
		$vars['date'] = TimeRender($usr['regdate'], true);
		$vars['lastdate'] = TimeRender($usr['lastvisit'],true);
		$vars['rank'] = $rank_stat;
		$site->AddSubBlock('userlist', true, $vars);
	}
}

function IndexUserForgotPassword()
{
	global $site;
	$site->SetTitle('Восстановление пароля/логина');
	$site->AddTemplatedBox('Восстановление пароля/логина','module/user_forgotpassword.html');
	$site->AddBlock('forgot_form',true,false,'form');
	$vars['form_name'] = 'forgotpassword';
	$vars['url'] = Ufu('index.php?name=user&op=sendpassword', 'user/{op}/');
	$vars['lemail'] = 'E-mail';
	$vars['llogin'] = 'Логин';
	$vars['lsubmit'] = 'Отправить';
	$site->Blocks['forgot_form']['vars'] = $vars;
}

function IndexUserSendPassword()
{
	global $site, $db, $config;
	$title = 'Восстановление пароля/логина';
	$site->SetTitle($title);
	if(isset($_POST['email']) || isset($_POST['login'])){
		$login = SafeEnv($_POST['login'], 15, str);
		$email = SafeEnv($_POST['email'], 50, str,true);
		$db->Select('users',"`login`='$login' or `email`='$email'");
		if($db->NumRows() == 0){
			$endmsg = '<br>Пользователь с таким логином или E-mail адресом не найден.';
		}else{
			$user = $db->FetchRow();
			srand(time());
			$pass = GenBPass(rand($config['user']['pass_min_length'],15));
			$pass2 = md5($pass);
			$db->Update('users', "pass='$pass2'", "`id`='".SafeEnv(SafeDB($user['id'],11,int),11,int)."'");

			UserSendForgotPassword(SafeDB($user['email'], 255, str),SafeDB($user['name'], 255, str),SafeDB($user['login'], 255, str),$pass);
			$endmsg = '<br>Ваш логин и новый сгенерированный пароль высланы вам на e-mail.';
		}
		$site->AddTextBox($title, $endmsg);
	}else{
		$site->AddTextBox($title, "Ошибка, данные не инициализированы.");
	}
}

?>