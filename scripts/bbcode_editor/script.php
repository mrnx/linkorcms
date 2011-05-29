<?php

	# LinkorCMS
	# © 2006-2010 Александр Галицкий (linkorcms@yandex.ru)
	# LinkorCMS Development Group
	# www.linkorcms.ru
	# Лицензия LinkorCMS 1.3

	if(!defined('VALID_RUN')){
		header("HTTP/1.1 404 Not Found");
		exit;
	}

	$bb_cssfile = System::site()->Root.'/style/bbcode.css';
	if(file_exists($bb_cssfile)){
		System::site()->AddCSSFile('bbcode.css');
	}else{
		System::site()->AddCSSFile('scripts/bbcode_editor/style.css', true);
	}
	System::site()->AddJSFile('scripts/bbcode_editor/bbcode_editor.js', true);

?>