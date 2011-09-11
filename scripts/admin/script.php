<?php

	if(!defined('VALID_RUN')){
		header("HTTP/1.1 404 Not Found");
		exit;
	}

	System::site()->AddJSFile('scripts/admin/admin.js', true);

?>
