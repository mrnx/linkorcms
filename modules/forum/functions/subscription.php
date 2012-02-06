<?php

function IndexForumSubscription(){
	global $user, $site, $lang, $config;
	$topic = 0;
	if(isset($_GET['topic'])){
		$topic = SafeEnv($_GET['topic'], 11, int);
	}
	if($user->Auth){
		$result = Forum_Subscription($topic);
		GO(Ufu('index.php?name=forum&op=showtopic&topic='.$topic.'&view=lastpost', 'forum/topic{topic}-new.html'));
	}else{
		$site->AddTextBox($lang['subscription'], $lang['error_auth']);
	}
}
