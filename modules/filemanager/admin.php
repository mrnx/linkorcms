<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('Ôאיכמגûי לוםוהזונ');

if(isset($_GET['a'])){
	$action = $_GET['a'];
}else{
	$action = 'main';
}

switch($action){
	case 'main':
		AdminFileManager();
		break;
}

function AdminFileManager(){
	UseScript('elfinder');

//	$().ready(function() {
//		var elf = $('#elfinder').elfinder({
//			// lang: 'ru',             // language (OPTIONAL)
//			url : 'php/connector.php'  // connector URL (REQUIRED)
//		}).elfinder('instance');
//	});

	$options = array(
		'url' => '/index.php?name=plugins&p=connectors&mod=elfinder',
		'lang' => 'ru',
		'docked' => true,
		'height' => 490
	);

	System::admin()->AddOnLoadJS('var elfinder = $("#finder").elfinder('.JsonEncode($options).')');
	System::admin()->AddTextBox('Ôאיכמגûי לוםוהזונ', '<div id="finder">finder</div>');
}

