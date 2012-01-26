<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

UseScript('jquery_ui');

System::site()->JQueryPlugin('scripts/jquery_popup/jquery.popup.js', true);
