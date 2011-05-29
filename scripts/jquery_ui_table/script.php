<?php

	if(!defined('VALID_RUN')){
		header("HTTP/1.1 404 Not Found");
		exit;
	}

	UseScript('jquery_ui');

	System::site()->JQueryPlugin('scripts/jquery_ui_table/jquery.ui.table.js', true);
	System::site()->AddCSSFile('scripts/jquery_ui_table/theme/jquery.ui.table.css', true);

?>