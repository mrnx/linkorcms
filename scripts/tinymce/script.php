<?php

	if(!defined('VALID_RUN')){
		header("HTTP/1.1 404 Not Found");
		exit;
	}

	UseScript('jquery');

	System::site()->JQueryPlugin('scripts/tinymce/jquery.tinymce.js', true);
	$GLOBALS['TinyMceScriptUrl'] = 'scripts/tinymce/tiny_mce.js';

?>
