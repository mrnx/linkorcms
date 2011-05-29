<?php

	if(!defined('VALID_RUN')){
		header("HTTP/1.1 404 Not Found");
		exit;
	}

	UseScript('jquery');

  System::site()->AddJSFile('scripts/jquery_popup/jquery.popup.js', true);

?>