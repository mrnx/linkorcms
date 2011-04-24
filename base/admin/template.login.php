<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

include_once($config['inc_dir'].'page_template.class.php');

function AdminShowLogin( $AuthMessage = '', $AuthTitle = 'Авторизация администратора' ){
	global $config, $site;
	$site = new PageTemplate();
	$site->InitPageTemplate();
	$site->Title = $AuthTitle;
	$site->Doctype = '<!doctype html>';
	$root = $config['apanel_dir'].'template/';
	$site->SetRoot($root);
	$site->SetTempVar('head', 'body', 'login.html');
	$site->AddBlock('template', true, false, 'login');

	$back = SaveRefererUrl($_SERVER['REQUEST_URI']);
	$site->Blocks['template']['vars'] = array(
		'action'=>'admin.php?_back='.$back,
		'dir'=>$root,
		'auth_message'=>$AuthMessage,
		'auth_title'=>$AuthTitle
	);
	$site->AddCSSFile('login.css', false, true);
	$site->EchoAll();
	exit();
}



?>