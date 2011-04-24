<?php

// Добавление сообщения
function IndexForumAddPost(){
	global $db, $config, $user, $site, $lang, $UFU;

	$topic_id = SafeEnv($_GET['topic'], 11, int);
	$forum_id = SafeEnv($_GET['forum'], 11, int);

	$db->Select('forums', "`id`='$forum_id'");
	$forum = $db->FetchRow();
	if($user->AccessIsResolved($forum['view'])
			&& $user->AccessIsResolved(2)
			&& $forum['parent_id'] != '0'
	){
		# Доступ по рангу
		$rang = Rang_UserStatus($forum);
		if($rang['rang_access']){
			$result = Forum_Add_AddPost2();
			if(is_array($result)){
				$site->AddTextBox($lang['forum'], IndexForumPrintErrors($result));
			}else{
				$user->ChargePoints($config['points']['forum_post']);
				$db->Select('forum_topics', "`id`='$topic_id'");
				if($db->NumRows() > 0){
					$topic = $db->FetchRow();

					$u_id = $user->Get('u_id');
					$db->Delete('forum_topics_read',"`tid`='$topic_id' and `mid`='$u_id'");
					$time = time();
					$vals = "'$u_id','$topic_id','$time'";
					$db->Insert('forum_topics_read',$vals);

					$forum['posts'] = (int)$forum['posts'] + 1;
					$topic['posts'] = (int)$topic['posts'] + 1;
					IndexForumSetLastPostInfo($forum, $topic);

					Forum_Subscription_Send($topic_id);

					if($config['forum']['update_cache_in_add']){
						Forum_Cache_ClearAllCacheForum();
					}
				}
				if(!$UFU){
					GO('index.php?name=forum&op=showtopic&topic='.$topic_id.'&view=lastpost#last');
				}else{
					GO($config['general']['site_url'].'forum/topic'.$topic_id.'-new.html#last');
				}
			}
		}else{
			$site->AddTextBox($lang['error'], $lang['error_access_category']);
		}
	}else{
		$site->AddTextBox($lang['error'], $lang['error_access_category']);
	}
}

?>