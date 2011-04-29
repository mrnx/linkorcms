<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$msgs = $db->Select('messages', "`active`='1'");

function MessagesCheckView( &$id, &$mods, &$uris, &$view ){
	global $ModuleName, $s_m, $user;
	if(!$user->AccessIsResolved($view)){
		return false;
	}
	$r = false;
	$mods = unserialize($mods);
	$uris = unserialize($uris);
	if(in_array($ModuleName, $mods)){
		$r = true;
	}elseif($_SERVER['REQUEST_URI'] != '' && in_array($_SERVER['REQUEST_URI'], $uris)){
		$r = true;
	}elseif(INDEX_PHP == true && in_array('INDEX', $mods)){
		$r = true;
	}else{
		$r = false;
	}
	if(in_array('ALL_EXCEPT', $mods)){
		$r = !$r;
	}
	return $r;
}

$disableMsg = false;
$bottomMessages = array();

function MessagesRender( $msg ){
	global $site, $userAccess, $config;
	$disableMsg = '';
	$total = TotalTime(time(), $msg['date'] + (Day2Sec * $msg['expire']));
	if($total === false){
		if($msg['expire'] != 0){
			$disableMsg .= "and `id`='".$msg['id']."'";
			return;
		}
	}
	if(MessagesCheckView($msg['id'], $msg['showin'], $msg['showin_uri'], $msg['view'])){
		$adin = '';
		if($userAccess == '1'){
			if($msg['expire'] != '0'){
				$vt = 'Срок истекает через '.$total['sdays'].($total['hours'] != 0 ? ' и '.$total['shours'] : '');
			}else{
				$vt = 'Неограниченно';
			}
			$adin = '(Просматривают: '.ViewLevelToStr($msg['view'], 'Только администраторы', 'Только пользователи', 'Только анонимные пользователи', 'Все посетители').' - '.$vt.' - <a href="'.$config['admin_file'].'?exe=messages&a=msgeditor&id='.$msg['id'].'">Редактировать</a>)';
		}
		if($msg['view_title'] == 1){ // Показывать заголовок
			$title = $msg['title'];
		}else{
			$title = '';
		}
		$site->AddMessage($title, $msg['text'], $adin);
	}
}

foreach($msgs as $a){
	if($a['position'] == '1'){
		MessagesRender($a);
	}else{
		$bottomMessages[] = $a;
	}
}

if($disableMsg !== false){
	$disableMsg = substr($disableMsg, 4);
	$db->Update('messages', "active='0'", $disableMsg);
}

function BottomMessages()
{
	global $bottomMessages;
	foreach($bottomMessages as $a){
		MessagesRender($a);
	}
}

?>