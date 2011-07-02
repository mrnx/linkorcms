<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

function write_to_audit( $action )
{
	global $db, $user;
	$user_id = $user->Get("u_id");
	$time = time();
	$ip = getip();
	$vals = Values('', $user_id, $time, $action, $ip);
	$db->Insert('audit', $vals);
}

function audit_action( $table, $method, &$vars )
{
	global $db;
	$db->Select('audit_rules', "`table`='$table' and `method`='$method'");
	if($db->NumRows() > 0){
		$action = $db->FetchRow();
		$action = SafeDB($action['method'], 255, str);
		foreach($vars as $key=>$var){
			$action = str_replace('{'.$key.'}', $var, $action);
		}
		write_to_audit($action);
	}else{
	}
}

?>