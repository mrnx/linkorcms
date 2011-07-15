<?php

function getconf_TimeZone_Identifiers( $name )
{
	$tlist = timezone_identifiers_list();
	$result = array();
	foreach($tlist as $timezone){
		$result[] = array($timezone, $timezone);
	}

	return $result;
}

?>