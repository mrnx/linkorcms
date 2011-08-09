<?php

// Время в секундах
define('Min2Sec', 60);
define('Hour2Sec', 3600);
define('Day2Sec', 86400);

function GetGmtArray(){
	$tlist = timezone_identifiers_list();
	$gmt = array();
	foreach($tlist as $timezone){
		$gmt[] = array($timezone, $timezone);
	}
	return $gmt;
}

function GetMicroTime(){
	return microtime(true);
}

/**
 * Выводит дату в строковом формате
 * @param $Time
 * @param bool $Full
 * @param bool $Logic
 * @return string
 */
function TimeRender( $Time, $Full=true, $Logic=true){
	global $config;
	if($Time==false || !is_numeric($Time)){
		return 'Нет данных';
	}
	$format = '';
	$now = time();
	$ld = round(($now / 86400) - ($Time / 86400));
	if($ld>1 || $now<$Time || !$Logic){
		$fdate = 'd.m.Y';
	}elseif($ld==0){
		$fdate = 'Сегодня';
	}elseif($ld==1){
		$fdate = 'Вчера';
	}else{
		return 'Нет данных';
	}
	if($Full){
		$date = date($fdate.' '.$config['general']['datetime_delemiter'].' H:i', $Time);
	}else{
		$date = date($fdate,$Time);
	}
	return $date;
}

/**
 * Определяет промежуток времени между двумя датами и выводит
 * результат в виде массива, где есть количество часов и дней
 * прошедших между датами и их строковые обозначения.
 *
 * @param Time $RunTime Время старта в секундах
 * @param Time $EndTime Время остановки в секундах
 * @return array('days'=>Количество дней,'hours'=>Количество часов,'sdays'=>Обозначение дней,'shours'=>Обозначение часов)
 */
function TotalTime( $RunTime, $EndTime ){
	$right = $EndTime - $RunTime;
	if($right<0){return false;}

	$str = '';
	$days = floor($right / Day2Sec);

	$str2 = '';
	$hours = round(($right - $days * Day2Sec) / Hour2Sec);
	if($hours==24){
		$hours=0;
		$days++;
	}

	//Определяем количество дней
	$days2 = $days;
	if($days>19){$days = $days % 10;}
	if($days == 1){$str .= 'день';}
	elseif($days > 1 && $days <= 4){$str .= 'дня';}
	elseif(($days > 4 && $days <= 19) || $days == 0){$str .= 'дней';}

	//Определяем количество часов
	$hours2 = $hours;
	if($hours>19){$hours = $hours % 10;}
	if($hours == 1){$str2 = 'час';}
	elseif($hours > 1 && $hours <= 4){$str2 = 'часа';}
	elseif(($hours > 4 && $hours <= 19) || $hours == 0){$str2 = 'часов';}

	$str = $days2.' '.$str;
	$str2 = $hours2.' '.$str2;
	return array('days'=>$days2,'hours'=>$hours2,'sdays'=>$str,'shours'=>$str2);
}
