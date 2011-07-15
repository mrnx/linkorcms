<?php

function getconf_TemplatesList( $name )
{
	global $config;
	$r = array();
	$dir = opendir($config['tpl_dir']);
	while($template = readdir($dir)){
		if(($template != ".") && ($template != "..")){
			if(is_dir($config['tpl_dir'].$template)){
				$r[] = array($template, $template);
			}
		}
	}
	closedir($dir);
	return $r;
}

?>