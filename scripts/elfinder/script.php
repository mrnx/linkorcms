<?php

	if(!defined('VALID_RUN')){
		header("HTTP/1.1 404 Not Found");
		exit;
	}

	UseScript('jquery_ui');

	System::site()->JQueryPlugin('scripts/elfinder/js/elfinder.min.js', true);
	System::site()->AddJSFile('scripts/elfinder/js/i18n/elfinder.ru.js', true);
	System::site()->AddCSSFile('scripts/elfinder/css/elfinder.css', true);

?>