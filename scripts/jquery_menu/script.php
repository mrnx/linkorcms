<?php

	if(!defined('VALID_RUN')){
		header("HTTP/1.1 404 Not Found");
		exit;
	}

	UseScript('jquery', 'jquery_ui');

	System::site()->JQueryPlugin('scripts/jquery_menu/jquery.menu.js', true);
	System::site()->AddCSSFile('scripts/jquery_menu/theme/jquery.menu-default.css', true); // Стандартная тема
