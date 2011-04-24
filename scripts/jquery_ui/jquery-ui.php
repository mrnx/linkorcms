<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

System::site()->JQueryPlugin('scripts/jquery_ui/jquery-ui-1.8.11.custom.min.js');
System::site()->AddCSSFile('scripts/jquery_ui/themes/aristo/jquery-ui-1.8.7.custom.css', true);

?>