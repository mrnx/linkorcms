<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('Главная');

$text = '';
IncludePluginsGroup('adminpanel');
AddTextBox('Админ-панель', $text);
