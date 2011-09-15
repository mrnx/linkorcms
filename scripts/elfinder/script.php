<?php

	if(!defined('VALID_RUN')){
		header("HTTP/1.1 404 Not Found");
		exit;
	}

	UseScript('jquery_ui');

	// elFinder CSS
	System::site()->AddCSSFile('scripts/elfinder/css/elfinder.min.css', true);
	System::site()->AddCSSFile('scripts/elfinder/css/theme.css', true);

	// elFinder JS
	System::site()->JQueryPlugin('scripts/elfinder/js/elfinder.min.js', true);

	// elFinder translation
	System::site()->JQueryPlugin('scripts/elfinder/js/i18n/elfinder.ru.js', true);
