<?php

# LinkorCMS
# © 2006-2010 Галицкий Александр Николаевич (linkorcms@yandex.ru)
# Файл: user.class.php
# Назначение: Пользовательский класс для аутентификации пользователей

if(!defined("USER")){
	define("USER", true);
	define("EXTRA_ADMIN_COOKIE", '3794y7v387o3');
}else{
	return;
}


class User{

	public $Auth = false;
	public $Started = false;
	public $session = array();
	public $errors = array();
	public $online = null;
	public $online_process = false;
	public $SuperUser = false;
	public $host;

	public function Started( $func ){
		if($this->Started == false){
			echo $errors[] = "<b>Внимание!</b> : User->$func(): Сессия не создана.<br />";
			return false;
		}else{
			return true;
		}
	}

	public function Def( $name, $value ){
		if($this->Started('Def')){
			$_SESSION[$name] = $value;
			$this->session[$name] = $value;
		}
	}

	public function UnDef( $name ){
		if($this->Started('UnDef')){
			unset($_SESSION[$name]);
			unset($this->session[$name]);
		}
	}

	public function isDef( $name ){
		return isset($this->session[$name]) || isset($_SESSION[$name]);
	}

	public function Get( $name ){
		if($this->Started('Get')){
			if(isset($this->session[$name])){
				return $this->session[$name];
			}elseif(isset($_SESSION[$name])){
				return $_SESSION[$name];
			}else{
				return false;
			}
		}
	}

	public function SetCookie( $Name, $Value, $Expiry = null){
		setcookie($Name, $Value, $Expiry);
	}

	public function UnsetCookie( $Name ){
		setcookie($Name, '', time() - 3600);
	}

	public function isAdmin(){
		if($this->Get('u_level') == '1'){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * Возвращает тип пользователя
	 */
	public function AccessLevel(){
		if($this->Get('u_level') === false){
			return 4;
		}else{
			return $this->Get('u_level');
		}
		//1 Администратор
		//2 Пользователь
		//3 Гость
		//4 Гость
	}

	/**
	 * Проверяет может ли пользователь с таким доступом
	 * видеть объект с таким уровнем видимости
	 * @param Integer $viewlevel // Уровень видимости объекта
	 * @param Integer $accesslevel // Уровень доступа пользователя
	 * @return Boolean
	 */
	public function AccessIsResolved( $viewlevel, $user_access = null ){
		if($user_access == null){
			global $user;
			$user_access = $user->AccessLevel();
		}
		if($user_access == 1){
			return true;
		}elseif($user_access == 2 && ($viewlevel == 4 || $viewlevel == 2)){
			return true;
		}elseif($user_access == 3 && ($viewlevel == 4 || $viewlevel == 3)){
			return true;
		}elseif($user_access == $viewlevel){
			return true;
		}
	}
	public $access2 = array();
	public $SuperAccess2 = false;

	// Выдает перечень модулей и их функций в
	// которые имеет доступ данная админская должность
	// $result[group] = array(name,name,name,...);
	public function AccessInit( $UserAccessGroup ){
		global $config, $db;
		$this->access2 = array();
		$this->SuperUser = false;
		$this->SuperAccess2 = false;
		if($UserAccessGroup == -1 || $UserAccessGroup == 0){
			return;
		}
		$access = $db->Select('usertypes', "`id`='".$UserAccessGroup."'");
		$ac2 = array();
		if($db->NumRows() > 0){
			if(trim($access[0]['access']) != 'ALL'){
				$ac2 = unserialize($access[0]['access']);
			}else{
				$this->SuperAccess2 = true;
			}
			if($access[0]['system'] == '1'){
				$this->SuperUser = true;
				$this->SuperAccess2 = true;
			}else{
				$this->SuperUser = false;
			}
		}
		$this->access2 = $ac2;
	}

	// Проверяет имеет ли админ доступ
	public function CheckAccess2( $AccessGroup, $AccessKey ){
		//На сервере где не работают сессии эта функция всегда возвращает false если у пользователя не полный доступ
		//return true;
		//
		if($this->SuperAccess2 || (isset($this->access2[$AccessGroup]) && in_array($AccessKey, $this->access2[$AccessGroup]))){
			return true;
		}else{
			return false;
		}
	}

	// Системный ли администратор (супердоступ)
	public function isSuperUser(){
		return $this->SuperUser;
	}

	// Возвращает id группы текущего пользователя
	public function AccessGroup(){
		//-1 Гость
		//0 Пользователь
		//Далее по базе данных
		if(!$this->Auth){
			return -1;
		}
		$acc = $this->Get('u_access');
		if($acc < 0){
			return 0;
		}else{
			return $acc;
		}
	}

	// Возвращает имя пользователя
	public function Name(){
		return $this->Get('u_name');
	}

	// Обновляет таблицу пользователей онлайн
	public function OnlineProcess( $page ){
		global $config, $db;
		//Удаляем старые сессии
		$time = time();
		$time2 = $time - 600;
		$db->Delete('online', "`time` < $time2");
		if($this->online_process){
			return;
		}else{
			$this->online_process = true;
		}
		$ip = getip();
		if($this->Auth === true){
			$id = $this->Get('u_id');
		}else{
			$id = -1;
		}
		$name = $this->Get('u_name');
		if(!$name){
			$name = '';
		}
		$level = $this->Get('u_level');
		$where = "`u_ip`='$ip'";
		$db->Select('online', $where);
		$uri = SafeEnv($_SERVER['REQUEST_URI'], 255, str);
		if(strpos($uri, $config['admin_file']) !== false){
			$uri = '';
			$page = 'Админ-панель';
		}
		if($db->NumRows() > 0){
			$db->Update('online', "'$time','$id','$name','$level','$uri','$page','$ip'", $where, true);
		}else{
			$db->Insert('online', "'$time','$id','$name','$level','$uri','$page','$ip'");
		}
	}

	// Возвращает информацию о пользователях онлайн
	public function Online(){
		global $config, $db;
		if($this->online == null){
			$info = array('admins'=>array(), 'members'=>array(), 'guests'=>array());
			$db->Select('online', '');
			while($memb = $db->FetchRow()){
				if($memb['u_level'] == 1){
					$info['admins'][] = $memb;
					$info[$memb['u_id']] = $memb;
				}elseif($memb['u_level'] == 2){
					$info['members'][] = $memb;
					$info[$memb['u_id']] = $memb;
				}else{
					$info['guests'][] = $memb;
				}
			}
			$this->online = $info;
			return $info;
		}else{
			return $this->online;
		}
	}

	// Конструктор
	public function User(){
		if($this->Started == false){
			if(!session_start()){
				echo $this->errors[] = '<b>Внимание!</b>: User->User(): Ошибка при запуске сессии.<br />';
			}else{
				$this->Started = true;
			}
		}

		// Пишем свой http_referer. Брать реферер из $_SERVER['HTTP_REFERER'].
		if($this->isDef('REFERER')){
			$_SERVER['HTTP_REFERER'] = $this->Get('REFERER'); // Пишем свой HTTP_REFERER
			// Модуль History
			if($this->isDef('HISTORY')){
				$history = $this->Get('HISTORY');
				$history[] = $_SERVER['HTTP_REFERER'];
				if(count($history) > 10){ // Максимальное число шагов которое сохраняется в сессии
					array_shift($history);
				}
				$this->Def('HISTORY', $history);
			}else{
				$this->Def('HISTORY', array($_SERVER['HTTP_REFERER']));
			}
			//
		}else{
			if(isset($_SERVER['HTTP_REFERER']) && trim($_SERVER['HTTP_REFERER']) != ''){
				$this->Def('FIRST_REFERER', SafeEnv(trim($_SERVER['HTTP_REFERER']), 255, str));
			}
		}
		$this->Def('REFERER', GetSiteHost().$_SERVER['REQUEST_URI']);

		if(isset($_SESSION['u_auth']) && $_SESSION['u_ip'] == getip()){ // сессия привязывается к ip адресу
			$this->session = $_SESSION;
			$this->Auth = $this->Get('u_auth');
		}else{
			$_SESSION = array();
		}
	}

	// Добавляет или отнимает пункты у пользователя.
	public function ChargePoints( $num, $user_id = null ){
		if($num == 0 || (!$this->Auth && $user_id == null)){
			return false;
		}
		global $config, $db;
		if($user_id != null){
			$id = $user_id;
		}elseif($this->Auth){
			$id = $this->Get('u_id');
		}
		$db->Select('users', "`id`='$id'");
		if($db->NumRows() == 0){
			return false;
		}
		$usr = $db->FetchRow();
		$points = SafeDB($usr['points'], 11, int);
		$points = $points + $num;
		$db->Update('users', "points='$points'", "`id`='$id'");
		return true;
	}

	// Устанавливает cookie авторизации пользователя.
	public function SetUserCookie( $login, $password, $remember = false, $expiry = 2592000 ){
		global $config;
		$auth = base64_encode($login.':'.md5(md5($password).$config['salt']));
		if($remember){
			$expiry = time() + $expiry;
		}else{
			$expiry = null;
		}
		$this->SetCookie('auth', $auth, $expiry);
	}

	public function SetAdminCookie( $login, $password ){
		global $config;
		$auth = base64_encode($login.':'.md5(md5($password).$config['salt'].EXTRA_ADMIN_COOKIE));
		$this->SetCookie('admin', $auth);
	}

	// Выполняет аутентификацию пользователя по логину и паролю
	public function Login( $login, $pass, $remember = false, $Second = false ){
		global $config, $db;
		sleep($config['security']['login_sleep']);
		$login = SafeEnv($login, 32, str);
		$md5_pass = md5(SafeEnv($pass, 32, str));
		$db->Select('users', "`login`='".$login."' and `pass`='".$md5_pass."'");
		$u = $db->FetchRow();
		if($db->NumRows() > 0 && $u['active'] == '1'){
			if($Second){
				$this->SecondLoginAdmin = $u['type'] == '1';
			}
			if(!$this->Auth){
				$this->Auth = true;
				$this->SetUserCookie($login, $pass, $remember);
				$this->RegisterData($u);
				$visits = $u['visits'] + 1;
				$db->Update('users', "lastvisit='".time()."',lastip='".getip()."',visits='".$visits."'", "`id`='".$u['id']."'");
			}
			return true;
		}else{
			if($Second){
				$this->SecondLoginAdmin = false;
			}else{
				if($u['active'] == '0'){
					return '<center><br />Ваш аккаунт не активирован. На указанный Вами E-Mail было отправлено письмо с ссылкой активации. Перейдите по данной ссылке и активируйте Ваш аккаунт!<br /></center>';
				}else{
					return '<center>Неверное имя пользователя или пароль!</center>';
				}
			}
		}
	}

	public function CheckCookies(){
		if(!$this->AllowCookie('auth')){
			if(isset($_COOKIE['auth'])){
				$this->UnsetCookie('auth');
			}
			$this->RegisterGuestData();
		}
	}

	// проверяет данные авторизации пришедшие в cookie
	// от пользователя и выполняет его вход если они верны.
	public function AllowCookie( $CookieName, $AdminCookie = false ){
		global $config, $db;

		if(!isset($_COOKIE[$CookieName])) return false;
		if(defined('SETUP_SCRIPT')) return false;

		$auth = base64_decode($_COOKIE[$CookieName]);
		$auth = explode(":", $auth);
		$login = SafeEnv($auth[0], 255, str);
		$cookie_md5 = $auth[1];

		$db->Select('users', "`login`='$login'");
		if($db->NumRows() > 0){
			$u = $db->FetchRow();
			$password = $u['pass'];

			if($AdminCookie){
				$scode = md5($password.$config['salt'].EXTRA_ADMIN_COOKIE);
			}else{
				$scode = md5($password.$config['salt']);
			}

			if($cookie_md5 == $scode){
				$this->RegisterData($u);
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	// Обновляет данные сессии в случае
	// если данные пользователя в бд были изменены.
	public function UpdateMemberSession(){
		global $config, $db;
		if($this->Auth){
			$user_id = $this->Get('u_id');
			$db->Select('users', "`id`='$user_id'");
			if($db->NumRows() > 0){
				$this->RegisterData($db->FetchRow());
			}
		}
	}

	// Регистрирует данные пользователя в сессии
	public function RegisterData( $user ){
		global $config;
		$this->Auth = true;
		$user_id = SafeDB($user['id'], 11, int);
		$this->Def('u_auth', true); //Авторизирован ли пользователь
		$this->Def('u_level', SafeDB($user['type'], 11, int)); //Это администратор
		$this->Def('u_access', SafeDB($user['access'], 11, int)); //Группа авторов если пользователь администратор
		$this->Def('u_id', $user_id); //Уникальный номер пользователя

		$this->Def('u_login', SafeDB($user['login'], 30, str)); // Логин
		$this->Def('u_md5', SafeDB($user['pass'], 32, str)); // Пароль пользователя в md5

		$this->Def('u_name', SafeDB($user['name'], 50, str)); //Ник пользователя
		$this->Def('u_truename', SafeDB($user['truename'], 250, str)); //Настоящее имя
		$this->Def('u_age', SafeDB($user['age'], 3, int)); //Возраст пользователя
		$this->Def('u_email', SafeDB($user['email'], 50, str)); //Емейл пользователя
		$this->Def('u_hideemail', SafeDB($user['hideemail'], 1, int)); //Скрывать ли емейл пользователя
		$this->Def('u_city', SafeDB($user['city'], 100, str)); //Город
		$this->Def('u_icq', SafeDB($user['icq'], 15, str)); //Номер ICQ пользователя
		$this->Def('u_homepage', SafeDB($user['url'], 250, str)); //Домашняя страничка пользователя
		$this->Def('u_timezone', SafeDB($user['timezone'], 255, str)); //Временная зона пользователя

		// Аватары
		$avatar = GetUserAvatar($user_id);
		$this->Def('u_avatar', $avatar);
		$this->Def('u_avatar_small', GetSmallUserAvatar($user_id, $avatar));
		$this->Def('u_avatar_smallest', GetSmallestUserAvatar($user_id, $avatar));
		//
		$this->Def('u_regdate', SafeDB($user['regdate'], 11, int)); //Дата регистрации
		$this->Def('u_points', SafeDB($user['points'], 11, int)); //Набранных пользователем пунктов
		$this->Def('u_ip', getip()); //Привязка сессии к ip адресу
	}

	// Регистрирует данные неавторизованного пользователя.
	public function RegisterGuestData(){
		global $config;
		$this->Auth = false;
		$this->Def('u_auth', false);
		$this->Def('u_level', '3');
		$this->Def('u_id', '0');
		$this->Def('u_access', '-1');
		$this->Def('u_ip', getip());
		$this->Def('u_avatar', GetGalleryAvatar('guest.gif'));
	}

	// Выполняет выход из системы и удаляет все данные сессии.
	public function UnLogin( $destroy = true ){
		global $db;
		$this->Auth = false;
		if($destroy){
			$_SESSION = array();
			$this->session = array();
			session_destroy();
		}
		$this->UnsetCookie('auth');
		$this->UnsetCookie('admin');
		$this->RegisterGuestData();
		$ip = getip();
		$where = "`u_ip`='$ip'";
		$db->Delete('online', $where);
	}
}

?>