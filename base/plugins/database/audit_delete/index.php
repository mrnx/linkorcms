<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(defined('ADMIN_SCRIPT')){
	$values2 = $data[$i];
	foreach($info["cols"] as $cid=>$col){
		$values2[$col["name"]] = &$values2[$cid];
	}
	audit_action($name, 'delete', $values2);
}

?>