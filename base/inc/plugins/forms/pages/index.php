<?php

function getconf_Pages( $name )
{
	global $config, $db;
	$pages = $db->Select('pages', "`enabled`='1' and `type`='page'");
	$r = array();
	foreach($pages as $page){
		$r[] = array(SafeEnv($page['link'], 255, str), SafeEnv($page['title'], 255, str));
	}
	return $r;
}

?>