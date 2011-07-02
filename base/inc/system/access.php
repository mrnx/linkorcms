<?php

/**
 * Уровни доступа
 */

/**
 * Вызывается при запросе несуществующей
 * страницы или ошибки и использования спецсимволов в параметрах
 * @param bool $LowProtect
 * @param bool $redirect
 * @return void
 */
function HackOff( $LowProtect=false, $redirect=true ){
	global $user, $config;
	if($user->isAdmin() || $LowProtect){
		if(defined('MAIN_SCRIPT') || defined('PLUG_SCRIPT') || !defined('ADMIN_SCRIPT')){
			if($redirect){
				GO(Ufu('index.php'));
			}
		}elseif(defined('ADMIN_SCRIPT')){
			GO($config['admin_file']);
		}
	}else{
		if($config['security']['hack_event'] == 'alert'){
			die($config['security']['hack_alert']);
		}elseif($config['security']['hack_event'] == 'ban'){
			die('Вам был запрещен доступ к сайту, возможно система обнаружила подозрительные
			действия с Вашей стороны. Если Вы считаете, что это произошло по ошибке, - обратитесь
			в службу поддержки по e-mail '.$config['general']['site_email'].'.');
		}else{
			if($redirect){
				GO(Ufu('index.php'));
			}
		}
	}
}

/**
 * Переводит уровень доступа в строку
 * @param $level
 * @param string $s_admins
 * @param string $s_members
 * @param string $s_guests
 * @param string $s_all
 * @return string
 */
function ViewLevelToStr($level,$s_admins='',$s_members='',$s_guests='',$s_all=''){
	switch($level){
		case 1:	$s_admins=='' ? $vi='<font color="#FF0000">Админы</font>' : $vi=$s_admins;
		break;
		case 2:	$s_members=='' ? $vi='<font color="#0080FF">Пользователи</font>' : $vi=$s_members;
		break;
		case 3: $s_guests=='' ? $vi='<font color="#A0A000">Гости</font>' : $vi=$s_guests;
		break;
		case 4:	$s_all=='' ? $vi='<font color="#008000">Все</font>' : $vi=$s_all;
		break;
		default: $s_all=='' ? $vi='<font color="#008000">Все</font>' : $vi=$s_all;
	}
	return $vi;
}

/**
 * Создаст запрос базы данных чтобы получить только те объекты (данные),
 * которые пользователь с данным доступом может видеть
 * @param $param_name
 * @param null $user_access
 * @return string
 */
function GetWhereByAccess($param_name, $user_access=null){
	if($user_access == null){
		global $user;
		$user_access = $user->AccessLevel();
	}
	$where = "`$param_name`='4'";
	if($user_access == '1'){//Администратор
		$where = '';
	}elseif($user_access == '2'){//Пользователь
		$where .= " or `$param_name`='2'";
	}else{//Гость
		$where .= " or `$param_name`='3'";
	}
	return $where;
}