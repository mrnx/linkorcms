<?php

function IndexForumSubscription(){
	global $user, $site, $lang, $UFU, $config;

	$topic = 0;
	if(isset($_GET['topic'])){
		$topic = SafeEnv($_GET['topic'], 11, int);
	}

	if($user->Auth){
		$result = Forum_Subscription($topic);
		if($UFU){
			GO($config['general']['site_url'].'forum/topic'.$topic.'-new.html');
		}else{
			GO('index.php?name=forum&op=showtopic&topic='.$topic.'&view=lastpost');
		}
	}else{
		$site->AddTextBox($lang['subscription'], $lang['error_auth']);
	}
}

?>