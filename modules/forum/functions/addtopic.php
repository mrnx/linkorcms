<?php

// Добавление новой темы пользователем
function IndexForumAddTopic(){
	global $user, $db, $site, $config, $lang, $UFU;

	if(isset($_GET['forum']) && isset($_POST['topic_title']) && isset($_POST['text'])) {

		$page = 1;
		if(isset($_GET['page'])){
			$page = SafeEnv($_GET['page'], 11, int);
		}

		$forum_id = SafeEnv($_GET['forum'], 11, int);
		$db->Select('forums', "`id`='$forum_id'");
		$forum = $db->FetchRow();

		if($user->AccessIsResolved($forum['view'])
				&& $user->AccessIsResolved(2)
				&& $forum['parent_id'] != '0'
				&& $user->Get('u_add_forum'))
		{ # Доступ по рангу

			$rang = Rang_UserStatus($forum);
			if($rang['rang_access']){
				$uniq_code = '';//GenRandomString(12, '1234567890');
				
				$topic_title = SafeEnv($_POST['topic_title'], 255, str);
				$time = time();
				if(strlen($topic_title) == 0) {
					$topic_error = array();
					$topic_error[] = $lang['no_title_topic'];
					$site->AddTextBox($lang['error'], IndexForumPrintErrors($topic_error));
					return;
				}
				$topic_values = Values('', $forum_id, $topic_title, '1', '0', '0', $time, $user->Get('u_id'), $user->Get('u_name'), $time, '0', '', $uniq_code, 0, 0, 0);
				$db->Insert('forum_topics', $topic_values);
				$topic_id = $db->GetLastId();
				$topic = $db->Select('forum_topics', "`id`='$topic_id'");
				$topic = $topic[0];

				$result = Forum_Add_AddPost2($topic_id);
				if(is_array($result)){
					$site->AddTextBox($lang['error'], IndexForumPrintErrors($result));
					ForumAdminDeleteTopic($topic_id);
				}else{
					$user->ChargePoints($config['points']['forum_post']);
					$forum['topics'] = SafeDB($forum['topics'],11,int) + 1;
					IndexForumSetLastPostInfo($forum, $topic);
					$u_id = $user->Get('u_id');
					$time = time();
					$vals = "'$u_id','$topic_id','$time'";
					$db->Insert('forum_topics_read',$vals);
					if($config['forum']['update_cache_in_add']){
						Forum_Cache_ClearAllCacheForum();
					}
					if(!$UFU){
						GO('index.php?name=forum&op=showforum&forum='.$forum_id);
					}else{
						GO($config['general']['site_url'].'forum/'.$forum_id);
					}
				}
			}else{
				$site->AddTextBox($lang['error'], $lang['error_access_category']);
			}
		}else{
			$site->AddTextBox($lang['error'], $lang['error_access_category']);
		}
	}else{
		$site->AddTextBox($lang['error'], $lang['error_data']);
	}
}

?>