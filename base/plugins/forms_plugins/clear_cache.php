<?php

function saveconf_Clear_Cache( $Value )
{
	$cache = LmFileCache::Instance();
	$groups = $cache->GetGroups();
	foreach($groups as $g){
		$cache->Clear($g);
	}
	return $Value;
}

?>