<?php

// ����� � ��������
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
 * ������� ���� � ��������� �������
 * @param $Time
 * @param bool $Full
 * @param bool $Logic
 * @return string
 */
function TimeRender( $Time, $Full=true, $Logic=true){
	global $config;
	if($Time==false || !is_numeric($Time)){
		return '��� ������';
	}
	$format = '';
	$now = time();
	$ld = round(($now / 86400) - ($Time / 86400));
	if($ld>1 || $now<$Time || !$Logic){
		$fdate = 'd.m.Y';
	}elseif($ld==0){
		$fdate = '�������';
	}elseif($ld==1){
		$fdate = '�����';
	}else{
		return '��� ������';
	}
	if($Full){
		$date = date($fdate.' '.$config['general']['datetime_delemiter'].' H:i', $Time);
	}else{
		$date = date($fdate,$Time);
	}
	return $date;
}

/**
 * ���������� ���������� ������� ����� ����� ������ � �������
 * ��������� � ���� �������, ��� ���� ���������� ����� � ����
 * ��������� ����� ������ � �� ��������� �����������.
 *
 * @param Time $RunTime ����� ������ � ��������
 * @param Time $EndTime ����� ��������� � ��������
 * @return array('days'=>���������� ����,'hours'=>���������� �����,'sdays'=>����������� ����,'shours'=>����������� �����)
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

	//���������� ���������� ����
	$days2 = $days;
	if($days>19){$days = $days % 10;}
	if($days == 1){$str .= '����';}
	elseif($days > 1 && $days <= 4){$str .= '���';}
	elseif(($days > 4 && $days <= 19) || $days == 0){$str .= '����';}

	//���������� ���������� �����
	$hours2 = $hours;
	if($hours>19){$hours = $hours % 10;}
	if($hours == 1){$str2 = '���';}
	elseif($hours > 1 && $hours <= 4){$str2 = '����';}
	elseif(($hours > 4 && $hours <= 19) || $hours == 0){$str2 = '�����';}

	$str = $days2.' '.$str;
	$str2 = $hours2.' '.$str2;
	return array('days'=>$days2,'hours'=>$hours2,'sdays'=>$str,'shours'=>$str2);
}
