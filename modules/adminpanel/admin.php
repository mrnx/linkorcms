<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('�������');

$text = '';
IncludePluginsGroup('adminpanel');
AddTextBox('�����-������', $text);
