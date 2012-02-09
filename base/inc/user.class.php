<?php

# LinkorCMS
# � 2006-2010 �������� ��������� ���������� (linkorcms@yandex.ru)
# ����: user.class.php
# ����������: ���������������� ����� ��� �������������� �������������

if(!defined("USER")){
	define("USER", true);
	define("EXTRA_ADMIN_COOKIE", 'Chahghiu6eiFai2kan4ei');
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

	public $access2 = array();
	public $SuperAccess2 = false;

	public $AuthCookieName = 'c_auth';
	public $AdminCookieName = 'c_admin';

	/**
	 * �����������
	 */
	public function __construct(){
		if($this->Started == false){
			if(isset($_GET['PHPSESSID'])){ // ��������� �� ������
				session_id($_GET['PHPSESSID']);
			}elseif(isset($_POST['PHPSESSID'])){
				session_id($_POST['PHPSESSID']);
			}elseif(isset($_COOKIE['PHPSESSID'])){
				session_id($_COOKIE['PHPSESSID']);
			}
			if(!session_start()){
				echo $this->errors[] = '<b>��������!</b>: User->User(): ������ ��� ������� ������.<br />';
			}else{
				$this->Started = true;
			}
		}
		// ����� ���� http_referer. ����� ������� �� $_SERVER['HTTP_REFERER'].
		if(!IsAjax()){ // �� Ajax ������
			if($this->isDef('REFERER')){
				$_SERVER['HTTP_REFERER'] = $this->Get('REFERER'); // ����� ���� HTTP_REFERER
				// ������ History
				if($this->isDef('HISTORY')){
					$history = $this->Get('HISTORY');
					$history[] = $_SERVER['HTTP_REFERER'];
					if(count($history) > 10){ // ������������ ����� ����� ������� ����������� � ������
						array_shift($history);
					}
					$this->Def('HISTORY', $history);
				}else{
					$this->Def('HISTORY', array($_SERVER['HTTP_REFERER']));
				}
			}else{
				if(isset($_SERVER['HTTP_REFERER']) && trim($_SERVER['HTTP_REFERER']) != ''){
					$this->Def('FIRST_REFERER', SafeEnv(trim($_SERVER['HTTP_REFERER']), 255, str));
				}
			}
			$this->Def('REFERER', GetSiteHost().$_SERVER['REQUEST_URI']);
		}

		if(isset($_SESSION['u_auth']) && $_SESSION['u_ip'] == getip()){ // ������ ������������� � ip ������
			$this->session = $_SESSION;
			$this->Auth = $this->Get('u_auth');
		}else{
			$_SESSION = array();
		}
	}

	protected function Started( $func ){
		if($this->Started == false){
			echo $errors[] = "<b>��������!</b> : User->$func(): ������ �� �������.<br />";
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
			}
			return false;
		}
		return false;
	}

	public function SetCookie( $Name, $Value, $Expiry = null){
		setcookie($Name, $Value, $Expiry);
	}

	public function UnsetCookie( $Name ){
		if(isset($_COOKIE[$Name])){
			setcookie($Name, '', time() - 3600);
		}
	}

	public function isAdmin(){
		if($this->Get('u_level') == ACCESS_ADMIN){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * ���������� ��� ������������
	 * @return int 1 �������������, 2 ������������, 3 �����, 4 �����
	 */
	public function AccessLevel(){
		if($this->Get('u_level') === false){
			return ACCESS_ALL;
		}else{
			return $this->Get('u_level');
		}
	}

	/**
	 * ��������� ����� �� ������������ � ����� ��������
	 * ������ ������ � ����� ������� ���������
	 * @param Integer $viewlevel ������� ��������� �������
	 * @param null $user_access ������� ������� ������������
	 * @internal param int $accesslevel ������� ������� ������������
	 * @return Boolean
	 */
	public function AccessIsResolved( $viewlevel, $user_access = null ){
		if($user_access == null){
			$user_access = $this->AccessLevel();
		}
		if($user_access == ACCESS_ADMIN){
			return true;
		}elseif($user_access == ACCESS_MEMBER && ($viewlevel == ACCESS_ALL || $viewlevel == ACCESS_MEMBER)){
			return true;
		}elseif($user_access == ACCESS_GUEST && ($viewlevel == ACCESS_ALL || $viewlevel == ACCESS_GUEST)){
			return true;
		}elseif($user_access == $viewlevel){
			return true;
		}
		return false;
	}

	/**
	 * ������ �������� ������� � �� �������
	 * � ������� ����� ������ ������ ��������� ���������
	 * $result[group] = array(name,name,name,...);
	 * @param  $UserAccessGroup
	 * @return void
	 */
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

	/**
	 * ��������� ����� �� ����� ������
	 * �� ������� ��� �� �������� ������ ��� ������� ������
	 * ���������� false ���� � ������������ �� ������ ������
	 * @param  $AccessGroup
	 * @param  $AccessKey
	 * @return bool
	 */
	public function CheckAccess2( $AccessGroup, $AccessKey ){
		if($this->SuperAccess2 || (isset($this->access2[$AccessGroup]) && in_array($AccessKey, $this->access2[$AccessGroup]))){
			return true;
		}else{
			return false;
		}
	}

	/**
	 * ��������� �� ������������� (�����������)
	 * @return bool
	 */
	public function isSuperUser(){
		return $this->SuperUser;
	}

	/**
	 * ���������� id ������ �������� ������������
	 * @return int -1 �����, 0 ������������, ����� �� ���� ������
	 */
	public function AccessGroup(){
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

	/**
	 * ���������� ��� ������������
	 * @return string
	 */
	public function Name(){
		return $this->Get('u_name');
	}

	/**
	 * ��������� ������� ������������� ������
	 * @param $page
	 * @return
	 */
	public function OnlineProcess( $page ){
		global $config, $db;
		//������� ������ ������
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
		if(strpos($uri, ADMIN_FILE) !== false){
			$uri = '';
			$page = '�����-������';
		}
		if($db->NumRows() > 0){
			$db->Update('online', "'$time','$id','$name','$level','$uri','$page','$ip'", $where, true);
		}else{
			$db->Insert('online', "'$time','$id','$name','$level','$uri','$page','$ip'");
		}
	}

	/**
	 * ���������� ���������� � ������������� ������
	 * @return array
	 */
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

	/**
	 * ��������� ��� �������� ������ � ������������
	 * @param  $num
	 * @param null $user_id
	 * @return bool
	 */
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

	/**
	 * ������������� cookie ����������� ������������
	 * @param  $login
	 * @param  $password
	 * @param bool $remember
	 * @param int $expiry
	 * @return void
	 */
	public function SetUserCookie( $login, $password, $remember = false, $expiry = 2592000 ){
		global $config;
		$auth = base64_encode($login.':'.md5(md5($password).$config['salt']));
		if($remember){
			$expiry = time() + $expiry;
		}else{
			$expiry = null;
		}
		$this->SetCookie($this->AuthCookieName, $auth, $expiry);
	}

	public function SetAdminCookie( $login, $password ){
		global $config;
		$auth = base64_encode($login.':'.md5(md5($password).$config['salt'].EXTRA_ADMIN_COOKIE));
		$this->SetCookie($this->AdminCookieName, $auth);
	}

	// ��������� �������������� ������������ �� ������ � ������
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
					return '<center><br />��� ������� �� �����������. �� ��������� ���� E-Mail ���� ���������� ������ � ������� ���������. ��������� �� ������ ������ � ����������� ��� �������!<br /></center>';
				}else{
					return '<center>�������� ��� ������������ ��� ������!</center>';
				}
			}
		}
	}

	public function CheckCookies(){
		if(!$this->AllowCookie($this->AuthCookieName)){
			if(isset($_COOKIE[$this->AuthCookieName])){
				$this->UnsetCookie($this->AuthCookieName);
			}
			$this->RegisterGuestData();
		}
	}

	// ��������� ������ ����������� ��������� � cookie
	// �� ������������ � ��������� ��� ���� ���� ��� �����.
	public function AllowCookie( $CookieName, $AdminCookie = false ){
		global $config, $db;
		if(defined('SETUP_SCRIPT')) return false;

		// ����� ���������� � POST � GET
		if(isset($_GET[$CookieName])){
			$cookie = $_GET[$CookieName];
		}elseif(isset($_POST[$CookieName])){
			$cookie = $_POST[$CookieName];
		}elseif(isset($_COOKIE[$CookieName])){
			$cookie = $_COOKIE[$CookieName];
		}else{
			return false;
		}

		$auth = base64_decode($cookie);
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

	// ��������� ������ ������ � ������
	// ���� ������ ������������ � �� ���� ��������.
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

	// ������������ ������ ������������ � ������
	public function RegisterData( $user ){
		global $config;
		$this->Auth = true;
		$user_id = SafeDB($user['id'], 11, int);
		$this->Def('u_auth', true); //������������� �� ������������
		$this->Def('u_level', SafeDB($user['type'], 11, int)); //��� �������������
		$this->Def('u_access', SafeDB($user['access'], 11, int)); //������ ������� ���� ������������ �������������
		$this->Def('u_id', $user_id); //���������� ����� ������������

		$this->Def('u_login', SafeDB($user['login'], 30, str)); // �����
		$this->Def('u_md5', SafeDB($user['pass'], 32, str)); // ������ ������������ � md5

		$this->Def('u_name', SafeDB($user['name'], 50, str)); //��� ������������
		$this->Def('u_truename', SafeDB($user['truename'], 250, str)); //��������� ���
		$this->Def('u_age', SafeDB($user['age'], 3, int)); //������� ������������
		$this->Def('u_email', SafeDB($user['email'], 50, str)); //����� ������������
		$this->Def('u_hideemail', SafeDB($user['hideemail'], 1, int)); //�������� �� ����� ������������
		$this->Def('u_city', SafeDB($user['city'], 100, str)); //�����
		$this->Def('u_icq', SafeDB($user['icq'], 15, str)); //����� ICQ ������������
		$this->Def('u_homepage', SafeDB($user['url'], 250, str)); //�������� ��������� ������������
		$this->Def('u_timezone', SafeDB($user['timezone'], 255, str)); //��������� ���� ������������

		// �������
		$avatar = GetUserAvatar($user_id);
		$this->Def('u_avatar', $avatar);
		$this->Def('u_avatar_small', GetSmallUserAvatar($user_id, $avatar));
		$this->Def('u_avatar_smallest', GetSmallestUserAvatar($user_id, $avatar));
		//
		$this->Def('u_regdate', SafeDB($user['regdate'], 11, int)); //���� �����������
		$this->Def('u_points', SafeDB($user['points'], 11, int)); //��������� ������������� �������
		$this->Def('u_ip', getip()); //�������� ������ � ip ������
	}

	// ������������ ������ ����������������� ������������.
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

	// ��������� ����� �� ������� � ������� ��� ������ ������.
	public function UnLogin( $destroy = true ){
		global $db;
		$this->Auth = false;
		if($destroy){
			$_SESSION = array();
			$this->session = array();
			session_destroy();
		}
		$this->UnsetCookie($this->AuthCookieName);
		$this->UnsetCookie($this->AdminCookieName);
		$this->RegisterGuestData();
		$ip = getip();
		$where = "`u_ip`='$ip'";
		$db->Delete('online', $where);
	}
}

