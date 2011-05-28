<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

System::site()->JQueryPlugin('scripts/jquery_table/jquery.table.js', true);
System::site()->AddCSSFile('scripts/jquery_table/theme/jquery.table.css', true);

