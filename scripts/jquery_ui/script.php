<?php

	if(!defined('VALID_RUN')){
		header("HTTP/1.1 404 Not Found");
		exit;
	}

	UseScript('jquery');

	System::site()->JQueryPlugin('scripts/jquery_ui/jquery-ui.min.js');
	System::site()->JQueryPlugin('scripts/jquery_ui/jquery.ui.datepicker-ru.js');
	System::site()->AddCSSFile('scripts/jquery_ui/themes/aristo/jquery-ui-1.8.7.custom.css', true);

?>