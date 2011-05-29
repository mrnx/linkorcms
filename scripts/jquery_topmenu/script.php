<?php

	if(!defined('VALID_RUN')){
		header("HTTP/1.1 404 Not Found");
		exit;
	}

	UseScript('jquery', 'jquery_ui');

	System::site()->JQueryPlugin('scripts/jquery_topmenu/jquery.topmenu.js', true);
	System::site()->AddCSSFile('scripts/jquery_topmenu/theme/jquery.topmenu.css', true);

?>