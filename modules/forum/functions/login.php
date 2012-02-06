<?php

function IndexForumLogin(){
	global $user, $site;
	if(!$user->Auth){
		$site->Login('');
		$user->Def('forum_referrer', $_SERVER['HTTP_REFERER']);
	}else{
		GO($user->Get('forum_referrer'), 202);
	}
}
