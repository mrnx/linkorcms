<?php

// Удаление темы
function IndexForumDeleteTopic(){
	global $db, $site,  $user, $config, $lang;
	if(isset($_GET['ok'])){
		$topic_id = SafeEnv($_GET['topic'], 11, int);
		$db->Select('forum_topics',"`id`='$topic_id'");
		$topic = $db->FetchRow();
		$forum_id = SafeDB($topic['forum_id'],11,int);
		if($topic['delete'] == 0){
			$db->Select('forums', "`id`='$forum_id'");
			$forum = $db->FetchRow();
			$ftopics = (int)$forum['topics'] - 1;
			if($ftopics < 0){
				$ftopics = 0;
			}
			$fposts = (int)$forum['posts'] - (int)$topic['posts'];
			if($fposts < 0){
				$fposts = 0;
			}
			$fset = "`topics`='$ftopics', `posts`='$fposts'";
			if((int)$topic['id'] == (int)$forum['last_id']) {
				if($fposts == 0) {
					$fset .= ",`last_post`='0',`last_poster_id`='0',`last_poster_name`='',`last_title`='',`last_id`='0'";
				}
				else {
					ForumSetLastPostInfo($forum_id, " and (`id`<'$topic_id' or `id`>'$topic_id')");
				}
			}

			$db->Update('forums',$fset,"`id`='$forum_id'");
		}
		if($config['forum']['basket'] == false){
			// Удаляем тему
			ForumAdminDeleteTopic($topic_id);
		}else{
			if($topic['delete'] == 0) {
				$text_value = '';
				if(isset($_POST['text'])){
					$text_value = SafeEnv($_POST['text'],255,str);
				}
				Moderation_Do_Basket_Topic($topic_id, $text_value);
			}
		}
		Forum_Cache_ClearAllCacheForum();
		GO('index.php?name=forum&op=showforum&forum='.$forum_id);
	}else{
		$db->Select('forum_topics', "`id`='".SafeEnv($_GET['topic'], 11, int)."'");
		$topic = $db->FetchRow();
		$text = 'Удалить тему "'.SafeDB($topic['title'], 255, str).'"?';
		$site->AddTextBox($lang['forum'], '<center>'.$text.'</center>');
		$site->AddTemplatedBox('', 'module/forum_delete_post.html');
		$site->AddBlock('delete_form', true, false, 'form');
		$vars = array();
		$vars['basket'] = $config['forum']['basket'] == true;
		$vars['url'] = 'index.php?name=forum&amp;op=deletetopic&amp;topic=' . SafeEnv($_GET['topic'], 11, int) . '&amp;ok=1';
		$site->Blocks['delete_form']['vars'] = $vars;
	}
}

?>