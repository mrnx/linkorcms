<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

System::site()->JQueryPlugin('scripts/jquery_treeview/jquery.treeview.js', true);
System::site()->AddCSSFile('scripts/jquery_treeview/theme/treeview.css', true);

?>