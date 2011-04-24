<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

global $site;
$site->AddJSFile('scripts/forum_scripts/forum.js', true, false);

?>