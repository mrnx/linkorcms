<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

define('STATISTIC', true);
$statistika = array('hit'=>0, 'host'=>0, 'visitor'=>0);

function VisitorStatisticProcess()
{
	global $statistika, $user;
	if(!$user->Get('u_visitor')){
		$statistika['visitor'] = 1;
		$user->Def('u_visitor', true);
	}
}

function HitStatisticProcess()
{
	global $user, $statistika;
	$hits = 0;
	if($user->isDef('u_hits')){
		$hits = $user->Get('u_hits');
	}
	$user->Def('u_hits', $hits + 1);
	if($hits > 3){
		VisitorStatisticProcess();
	}
	$statistika['hit'] = 1;
}

function StatisticProcess()
{
	global $statistika, $db, $stats_alloy, $user;
	if(!$stats_alloy){
		return false;
	}
	if($user->host){
		$statistika['host'] = 1;
	}
	$time = time();
	$year = date('Y', $time);
	$month = date('m', $time);
	$day = date('d', $time);
	$where = "`year`='$year' and `month`='$month' and `day`='$day'";
	$db->Select('stats', $where);
	if($db->NumRows() > 0){
		$stat = $db->FetchRow();
		$hits = $stat['hits'] + $statistika['hit'];
		$hosts = $stat['hosts'] + $statistika['host'];
		$visitors = $stat['visitors'] + $statistika['visitor'];
		$db->Update('stats', "hits='$hits',hosts='$hosts',visitors='$visitors'", $where);
	}else{
		$hits = $statistika['hit'];
		$hosts = $statistika['host'];
		$visitors = $statistika['visitor'];
		$db->Insert('stats', "'$year','$month','$day','$hits','$hosts','$visitors'");
	}
}
?>