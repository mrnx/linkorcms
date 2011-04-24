<?php

// Блок пользователя

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

global $site;

$info = $user->Online();
$vars['title'] = $title;
$tempvars['content'] = 'block/content/online.html';
$site->AddBlock('block_online', true, false, 'online');
$block_vars['num_admins'] = count($info['admins']);
$block_vars['num_members'] = count($info['members']);
$block_vars['num_guests'] = count($info['guests']);
$block_vars['num_online'] = count($info['admins']) + count($info['members']) + count($info['guests']);
$site->Blocks['block_online']['vars'] = $block_vars;

?>