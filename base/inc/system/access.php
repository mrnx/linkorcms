<?php

/**
 * Уровни доступа
 */

define('ACCESS_ADMIN', 1);
define('ACCESS_MEMBER', 2);
define('ACCESS_GUEST', 3);
define('ACCESS_ALL', 4);

/**
 * Вызывается при запросе несуществующей
 * страницы или ошибки и использования спецсимволов в параметрах
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
			die('Вам был запрещен доступ к сайту, возможно система обнаружила подозрительные
			действия с Вашей стороны. Если Вы считаете, что это произошло по ошибке, - обратитесь
			в службу поддержки по e-mail '.$config['general']['site_email'].'.');
		}else{
			if($Redirect){
				GO(Ufu('index.php'));
			}
		}
	}
}

/**
 * Переводит уровень доступа в строку
 * @param $Level
 * @param string $Admins
 * @param string $Members
 * @param string $Guests
 * @param string $All
 * @return string
 */
function ViewLevelToStr( $Level, $Admins='', $Members='', $Guests='', $All='' ){
	switch($Level){
		case 1:	$Admins == '' ? $view = '<span style="color: #FF0000;">Администраторы</span>' : $view = $Admins;
		break;
		case 2:	$Members == '' ? $view = '<span style="color: #0080FF;">Пользователи</span>' : $view = $Members;
		break;
		case 3: $Guests == '' ? $view = '<span style="color: #A0A000;">Гости</span>' : $view = $Guests;
		break;
		case 4:
		default: $All == '' ? $view = '<span style="color: #008000;">Все</span>' : $view = $All;
	}
	return $view;
}

/**
 * Создаст запрос базы данных чтобы получить только те объекты (данные),
 * которые пользователь с данным доступом может видеть
 * @param $ParamName Имя параметра с уровнем доступа объекта в базе данных
 * @param null $UserAccess Уровень доступа пользлвателя
 * @return string
 */
function GetWhereByAccess( $ParamName, $UserAccess=null ){
	if($UserAccess == null){
		global $user;
		$UserAccess = $user->AccessLevel();
	}
	$where = "`$ParamName`='4'";
	if($UserAccess == ACCESS_ADMIN){ // Администратор
		$where = '';
	}elseif($UserAccess == ACCESS_MEMBER){ // Пользователь
		$where .= " or `$ParamName`='2'";
	}else{ // Гость
		$where .= " or `$ParamName`='3'";
	}
	return $where;
}
