<?php

	if(!defined('VALID_RUN')){
		header("HTTP/1.1 404 Not Found");
		exit;
	}

	System::site()->AddJSFile('scripts/swfupload/swfupload.js', true);
	System::site()->AddJSFile('scripts/swfupload/plugins/swfupload.swfobject.js', true);
	System::site()->AddJSFile('scripts/swfupload/plugins/swfupload.queue.js', true);
	System::site()->AddJSFile('scripts/swfupload/plugins/swfupload.cookies.js', true);
	System::site()->AddJSFile('scripts/swfupload/plugins/swfupload.speed.js', true);

