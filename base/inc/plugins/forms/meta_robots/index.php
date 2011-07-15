<?php

function getconf_Meta_Robots( $name )
{
	return array(
		array('all', 'all'),
		array('index', 'index'),
		array('follow', 'follow'),
		array('index,nofollow', 'index,nofollow'),
		array('noindex,follow', 'noindex,follow'),
		array('none', 'none')
	);
}

?>