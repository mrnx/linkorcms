<?php

	if(!defined('VALID_RUN')){
		header("HTTP/1.1 404 Not Found");
		exit;
	}

	UseScript('jquery_ui');

	System::site()->JQueryPlugin('scripts/jquery_ui_nestedSortable/jquery.ui.nestedSortable.js');

?>