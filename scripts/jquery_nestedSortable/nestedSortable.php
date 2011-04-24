<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

System::site()->JQueryPlugin('scripts/jquery_nestedSortable/jquery.ui.nestedSortable.js');

?>