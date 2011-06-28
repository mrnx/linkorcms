<?php

	if(!defined('VALID_RUN')){
		header("HTTP/1.1 404 Not Found");
		exit;
	}

	UseScript('jquery_ui');

	if(is_file('scripts/elfinder/js/elfinder.min.js')){
		System::site()->JQueryPlugin('scripts/elfinder/js/elfinder.min.js', true);
	}else{
		System::site()->JQueryPlugin('scripts/elfinder/js/elFinder.js', true);
		System::site()->JQueryPlugin('scripts/elfinder/js/elFinder.ui.js', true);
		System::site()->JQueryPlugin('scripts/elfinder/js/elFinder.view.js', true);
		System::site()->JQueryPlugin('scripts/elfinder/js/elFinder.quickLook.js', true);
		System::site()->JQueryPlugin('scripts/elfinder/js/elFinder.eventsManager.js', true);
	}
	System::site()->AddJSFile('scripts/elfinder/js/i18n/elfinder.ru.js', true);
	System::site()->AddCSSFile('scripts/elfinder/css/elfinder.css', true);

?>