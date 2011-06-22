<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

function write_http_referer( $referer ){
	global $db, $user;
	if(trim($referer) != '' && !IsMainHost($referer)){
		$referer = SafeEnv(Url($referer), 255, str);
		$count = 1;
		$db->Select('referers', "`referer`='$referer'");
		if($db->NumRows() > 0){
			$ref = $db->FetchRow();
			$count = SafeDB($ref['count'], 11, int);
			$count++;
			$db->Update('referers', "count='$count'", "`referer`='$referer'");
		}else{
			$values = Values('', $referer, $count);
			$db->Insert('referers', $values);
		}
	}
}

if(isset($_SERVER['HTTP_REFERER'])){
	write_http_referer($_SERVER['HTTP_REFERER']);
}

?>