<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

define('CONF_GET_PREFIX', 'getconf_');
define('CONF_SAVE_PREFIX', 'saveconf_');

$forms_plugins_dir = $config['plug_dir'].'forms_plugins/';
$user_funcs = array();
$user_funcs2 = array();
$plugins = array();

//Подключаем плагины
$user_funcs = get_defined_functions();
$user_funcs = $user_funcs['user'];

// Подключаем плагины
SystemPluginsIncludeGroup('forms');

$user_funcs2 = get_defined_functions();
$user_funcs2 = $user_funcs2['user'];
$a_plugins = array();
$a_plugins = array_diff($user_funcs2, $user_funcs);

$cl_plugins = array();
$cs_plugins = array();
$ll = strlen(CONF_GET_PREFIX);
$sl = strlen(CONF_SAVE_PREFIX);
foreach($a_plugins as $pl){
	if(substr($pl, 0, $ll) == CONF_GET_PREFIX){
		$cl_plugins[] = array(substr($pl, $ll), $pl);
	}elseif(substr($pl, 0, $sl) == CONF_SAVE_PREFIX){
		$cs_plugins[] = array(substr($pl, $sl), $pl);
	}
}
///////////////

// Приводим переменную к нужному типу
function FormsCheckType( $var, $typearr )
{
	if($typearr[1] == 'file'){
		return $var;
	}
	if($typearr[2] == 'false'){
		$strip_tags = false;
	}else{
		$strip_tags = true;
	}
	$r = SafeEnv($var, (integer)$typearr[0], (string)$typearr[1], $strip_tags);
	if($r === false){
		$r = '0';
	}elseif($r === true){
		$r = '1';
	}
	return $r;
}

// Проверяем, является ли значение настройки именем функции плагина
function FormsConfigCheck2Func( $lname, $fname, $method = 'load' )
{
	global $a_plugins;
	if($method == 'load'){
		$p = CONF_GET_PREFIX;
	}else{
		$p = CONF_SAVE_PREFIX;
	}
	if(trim(strtolower($lname)) == 'function' && function_exists(trim($p.$fname)) && in_array(strtolower($p.$fname), $a_plugins)){
		return true;
	}else{
		return false;
	}
}

// Разбираем параметры элемента формы в массив
function FormsParseParams( $kind )
{
	$result = array('cols'=>1, 'style'=>'', 'control'=>$kind[0], 'width'=>'', 'height'=>'');
	if(count($kind) > 1){
		$style = 'style="';
		$St = '';
		$control = $kind[0];
		$cols = 1;
		$width = '';
		$height = '';
		for($i = 1; $i < count($kind); $i++){
			switch($kind[$i][0]){
				case 'w':
					$width = substr($kind[$i], 1);
					$St .= ' width:'.$width.';';
					break;
				case 'h':
					$height = substr($kind[$i], 1);
					$St .= ' height:'.$height.';';
					break;
				case 'c':
					$cols = (integer)substr($kind[$i], 1);
					break;
			}
		}
		if($St == ''){
			$style = '';
		}else{
			$style = $style.$St.'"';
		}
		if($cols == '' || $cols < 0){
			$cols = 1;
		}
		$result = array(
			'cols'=>$cols,
			'style'=>$style,
			'control'=>$control,
			'width'=>$width,
			'height'=>$height
		);
	}
	return $result;
}

// Генериуем html-представление элемента формы
function FormsGetControl( $name, $value, $kind, $type, $values )
	//values = array(val1,val2,val3,...)
	//kind = (Edit,Password,Text,Check,Radio,List,Combo)
{
	global $site;
	$kind = explode(':', $kind);
	$control = '';
	$kind[0] = trim(strtolower($kind[0]));
	if($type != ''){
		$type = explode(',', $type);
		settype($type[0], int); //maxlength
		settype($type[1], str); //type
		settype($type[2], bool); //strip tags/special chars
	}else{
		$type = array(255, str, false);
	}
	$params = FormsParseParams($kind);
	switch($kind[0]){
		case 'edit':
			$control = $site->Edit($name, htmlspecialchars($value), false, ($type[0] != 0 ? 'maxlength="'.$type[0].'" ' : '').$params['style']);
			break;
		case 'password':
			$control = $site->Edit($name, htmlspecialchars($value), true, ($type[0] != 0 ? 'maxlength="'.$type[0].'" ' : '').$params['style']);
			break;
		case 'text':
			$control = $site->TextArea($name, $value, $params['style']);
			break;
		case 'check':
			$vals = explode(':', $values);
			if(count($vals) == 2 && FormsConfigCheck2Func($vals[0], $vals[1])){
				$func = CONF_GET_PREFIX.trim($vals[1]);
				$vals = $func($name);
				$usefunc = true;
			}else{
				$vals = explode(',', $values);
				$usefunc = false;
			}
			$value = explode(',', $value);
			$control = '<table cellspacing="0" cellpadding="0" align="center">';
			$col = 0;
			$cols = $params['cols'];
			for($i = 0; $i < count($vals); $i++){
				if(!$usefunc){
					$s = explode(':', $vals[$i]);
				}else{
					$s = $vals[$i];
				}
				if($col == 0){
					$control .= '<tr>';
				}
				$col++;
				$control .= '<td nowrap class="rightc">'.$site->Check($name.'[]', $s[0], in_array($s[0], $value)).$s[1].'</td>';
				if($col == $cols){
					$control .= '</tr>';
					$col = 0;
				}
			}
			if($col < $cols){
				$control .= '</tr>';
			}
			$control .= '</table>';
			break;
		case 'radio':
			$vals = explode(':', $values);
			if(count($vals) == 2 && FormsConfigCheck2Func($vals[0], $vals[1])){
				$func = CONF_GET_PREFIX.trim($vals[1]);
				$vals = $func($name);
				$usefunc = true;
			}else{
				$vals = explode(',', $values);
				$usefunc = false;
			}
			$control = '<table cellspacing="0" cellpadding="0" align="center">';
			$col = 0;
			$cols = $params['cols'];
			for($i = 0; $i < count($vals); $i++){
				if(!$usefunc){
					$s = explode(':', $vals[$i]);
				}else{
					$s = $vals[$i];
				}
				if($col == 0){
					$control .= '<tr>';
				}
				$col++;
				$control .= '<td nowrap class="rightc">'.$site->Radio($name, $s[0], ($value == $s[0])).$s[1].'</td>';
				if($col == $cols){
					$control .= '</tr>';
					$col = 0;
				}
			}
			if($col < $cols){
				$control .= '</tr>';
			}
			$control .= '</table>';
			break;
		case 'list':
		case 'combo':
			$vals = explode(':', $values);
			if(count($vals) == 2 && FormsConfigCheck2Func($vals[0], $vals[1])){
				$func = CONF_GET_PREFIX.trim($vals[1]);
				$vals = $func($name);
				$usefunc = true;
			}else{
				$vals = explode(',', $values);
				$usefunc = false;
			}
			$cdata = array();
			for($i = 0; $i < count($vals); $i++){
				if(!$usefunc){
					$s = explode(':', $vals[$i]);
				}else{
					$s = $vals[$i];
				}
				if(count($s) == 2){
					$site->DataAdd($cdata, $s[0], $s[1], ($value == $s[0]));
				}
			}
			$control = $site->Select($name.($kind[0] == 'list' ? '[]' : ''), $cdata, ($kind[0] == 'list'), $params['style']);
			break;
		case 'file':
			//////////////////////////////////////////////////////////////////
			$control = $site->FFile($name);
			break;
		default:
			$control = $value;
	}
	return $control;
}
