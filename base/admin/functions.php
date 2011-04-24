<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

#Возвращает имена шаблонов блоков, которые имеет текущий шаблон сайта
function GetBlockTemplates(){
	global $config, $db;
	$TemplateDir = $config['tpl_dir'].$config['general']['site_template'].'/block/';
	return GetFiles($TemplateDir, false, true, '.html.htm.tpl', true);
}

#Возвращает данные формы "кто видит"
function GetUserTypesFormData( $view ){
	global $site;
	$visdata = array();
	$site->DataAdd($visdata, 'all', 'Все', $view['4']);
	$site->DataAdd($visdata, 'members', 'Только пользователи', $view['2']);
	$site->DataAdd($visdata, 'guests', 'Только гости', $view['3']);
	$site->DataAdd($visdata, 'admins', 'Только администраторы', $view['1']);
	return $visdata;
}

#Возвращает цифровой уровень из строки HTML форм
function ViewLevelToInt( $level ){
	switch($level){
		case 'admins':
			$vi = '1';
			break;
		case 'members':
			$vi = '2';
			break;
		case 'guests':
			$vi = '3';
			break;
		case 'all':
			$vi = '4';
			break;
		default:
			$vi = '4';
	}
	return $vi;
}

function GetViewData( $view ){
	global $site;
	$viewdata = array();
	$site->DataAdd($viewdata, 'all', 'Все', $view['4']);
	$site->DataAdd($viewdata, 'members', 'Только пользователи', $view['2']);
	$site->DataAdd($viewdata, 'guests', 'Только гости', $view['3']);
	$site->DataAdd($viewdata, 'admins', 'Только администраторы', $view['1']);
	return $viewdata;
}

# Переводит строку значения переключателя в число
function EnToInt( $onoff ){
	switch($onoff){
		case 'on':
			$r = 1;
			break;
		case 'off':
			$r = 0;
			break;
		default:
			$r = 1;
	}
	return $r;
}

function GetEnData( $selected = true, $on = 'Да', $off = 'Нет' ){
	global $site;
	$data = array();
	$site->DataAdd($data, 'on', $on, $selected);
	$site->DataAdd($data, 'off', $off, !$selected);
	return $data;
}

# Подсчитывает количество главных администраторов
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

# Проверяет системная ли группа по id группы
function groupIsSystem( $access ){
	global $db;
	if($access == -1){
		return false;
	}
	$db->Select('usertypes', "`id`='$access'");
	if($db->NumRows() > 0){
		$access = $db->FetchRow();
		return $access['system'] == '1';
	}else{
		return false;
	}
}

//Возвращает данные формы(select) с выделенными значениями соответсвенно timestamp
function GetDateFormData( &$daydata, &$mondata, &$yeardata, &$hourdata, &$mindata, $timestamp, $last_years = true ){
	global $site;
	$data = getdate($timestamp);
	for($i = 1; $i <= 31; $i++){
		$site->DataAdd($daydata, $i, $i, ($data['mday'] == $i));
	}
	for($i = 1; $i <= 12; $i++){
		$site->DataAdd($mondata, $i, $i, ($data['mon'] == $i));
	}
	if($last_years){
		$min = 1970;
	}else{
		$min = date('Y');
	}
	$max = date('Y') + 40;
	for($i = $min; $i <= $max; $i++){
		$site->DataAdd($yeardata, $i, $i, ($data['year'] == $i));
	}
	for($i = 0; $i <= 23; $i++){
		if($i < 10){
			$cap = '0'.$i;
		}else{
			$cap = $i;
		}
		$site->DataAdd($hourdata, $i, $cap, ($data['hours'] == $i));
	}
	$site->DataAdd($mindata, '0', '00', ($data['minutes'] == 0));
	$site->DataAdd($mindata, '5', '05', ($data['minutes'] == 0));
	for($i = 10; $i <= 55; $i = $i + 5){
		$site->DataAdd($mindata, $i, $i, ($data['minutes'] == $i));
	}
}

function PrintEmail( $email, $nik = '' ){
	global $config, $site;
	$email = SafeDB($email, 50, str);
	$nik = SafeDB($nik, 50, str);
	static $incjs = false;
	if($email == ''){
		return '&nbsp;';
	}else{
		if(!$incjs){
			$site->AddJS("
			function MailTo(email,nik){
				window.open('index.php?name=plugins&p=mail&email='+email+'&toname='+nik,'MaiL','resizable=yes,scrollbars=no,menubar=no,status=no,location=no,width=500,height=420,screenX=300,screenY=200');
			}");
			$incjs = true;
		}
		return "<a onclick=\"MailTo('$email','$nik');\">$email</a>";
	}
}
?>