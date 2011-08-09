<?php

/**
 * Генерирует массив опций для HTML::Select функции сорержащий все часовые пояса
 * @param $val
 * @return array
 */
function GetGmtData($val){
	global $site;
	$tlist = timezone_identifiers_list();
	$gmt = array();
	foreach($tlist as $timezone){
		$site->DataAdd($gmt, $timezone, $timezone, $val == $timezone);
	}
	return $gmt;
}

/**
 * Возвращает цифровой уровень из строки HTML форм
 * @param  $level
 * @return string
 */
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

/**
 * Переводит строку значения переключателя в число
 * @param  $onoff
 * @return int
 */
function EnToInt($onoff){
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

/**
 * Генерирует данные "Да", "Нет" для HTML::Select
 * @param bool $selected
 * @param string $on
 * @param string $off
 * @return array
 */
function GetEnData($selected = true, $on = 'Да', $off = 'Нет'){
	global $site;
	$data = array();
	$site->DataAdd($data, 'on', $on, $selected);
	$site->DataAdd($data, 'off', $off, !$selected);
	return $data;
}

/**
 * Возвращает данные формы(select) с выделенными значениями соответсвенно timestamp
 * @param  $daydata
 * @param  $mondata
 * @param  $yeardata
 * @param  $hourdata
 * @param  $mindata
 * @param  $timestamp
 * @param bool $last_years
 * @return void
 */
function GetDateFormData(&$daydata, &$mondata, &$yeardata, &$hourdata, &$mindata, $timestamp, $last_years = true){
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
	} else{
		$min = date('Y');
	}
	$max = date('Y')+40;
	for($i = $min; $i <= $max; $i++){
		$site->DataAdd($yeardata, $i, $i, ($data['year'] == $i));
	}
	for($i = 0; $i <= 23; $i++){
		if($i < 10){
			$cap = '0'.$i;
		} else{
			$cap = $i;
		}
		$site->DataAdd($hourdata, $i, $cap, ($data['hours'] == $i));
	}
	$site->DataAdd($mindata, '0', '00', ($data['minutes'] == 0));
	$site->DataAdd($mindata, '5', '05', ($data['minutes'] == 0));
	for($i = 10; $i <= 55; $i = $i+5){
		$site->DataAdd($mindata, $i, $i, ($data['minutes'] == $i));
	}
}

function LoadImage($PostName, $Dir, $ThumbsDir, $MaxWidth, $MaxHeight, $Default, &$Error, $CreateThumbs = true, $OriginalOptimization = false, $OriginalMaxWidth = 800, $OriginalMaxHeight = 600){
	$Error = false;
	if($Default == 'no_image/no_image.png') {
		$Default = '';
	}

	$alloy_mime = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png');
	$alloy_exts = array('.gif', '.jpg', '.jpeg', '.png');
	if(isset($_FILES[$PostName]) && file_exists($_FILES[$PostName]['tmp_name'])){
		if(in_array($_FILES[$PostName]['type'], $alloy_mime) && in_array(strtolower(GetFileExt($_FILES[$PostName]['name'])), $alloy_exts)) {
			$file_name = Translit4Url($_FILES[$PostName]['name']);
			if(!is_dir($Dir)) {
				mkdir($Dir, 0777);
			}
			$ext = GetFileExt($file_name);
			$name = GetFileName($file_name);
			$i = 1;
			while(is_file($Dir.$file_name)){
				$i++;
				$file_name = $name.'_'.$i.$ext;
			}
			$FileName = $Dir.$file_name;
			$ThumbFileName = $ThumbsDir.$file_name;
			if(!$OriginalOptimization){
				copy($_FILES[$PostName]['tmp_name'], $FileName);
			}else{
				CreateThumb($_FILES[$PostName]['tmp_name'], $FileName, $OriginalMaxWidth, $OriginalMaxHeight);
			}
			if($CreateThumbs){
				if(!is_dir($ThumbsDir)){
					mkdir($ThumbsDir, 0777);
				}
				CreateThumb($FileName, $ThumbFileName, $MaxWidth, $MaxHeight);
			}
			$result = $file_name;
		}else{
			$Error = true;
			return RealPath2(SafeEnv($Default, 255, str));
		}
	}else{
		$result = RealPath2(SafeEnv($Default, 255, str));
	}
	return $result;
}
