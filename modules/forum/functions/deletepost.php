<?php

// Удаление сообщения
function IndexForumDeletePost(){
	global $db, $site, $user, $config, $lang;
	if(isset($_GET['ok'])){
		if(isset($_GET['page'])){
			$page = SafeEnv($_GET['page'], 11, int);
		}else{
			$page = 1;
		}
		$post_id = SafeEnv($_GET['post'], 11, int);
		$db->Select('forum_posts',"`id`='$post_id'");
		$post = $db->FetchRow();
		if($post['delete'] == 0){
			$public = SafeDB($post['public'],11,int);
			$topic_id = SafeEnv($_GET['topic'],11,int);
			$set_last = true;
			$last_db = $db->Select('forum_posts', "`object`='$topic_id' and `id`>'$post_id' and `delete`='0'");
			if(count($last_db) > 0){
				$set_last = false;
			}

			$db->Select('forum_topics',"`id`='$topic_id'");
			$topic = $db->FetchRow();

			if($topic['delete'] == 0){
				$tposts = (int)$topic['posts'] - 1;
				if($tposts < 0){
					$tposts = 0;
				}
				$last_post = SafeDB($topic['last_post'],11,int);
				$last_post_topic = SafeDB($topic['last_post'],11,int);
				$post_array_ignore = array();
				$post_array_ignore[] = $post_id;
				if($set_last){
					TopicSetLastPostInfo($topic_id, $post_array_ignore);
				}
				$db->Update('forum_topics',"`posts`='$tposts'","`id`='$topic_id'");

				$forum_id = SafeDB($topic['forum_id'],11,int);
				$db->Select('forums',"`id`='$forum_id'");
				$forum = $db->FetchRow();
				$fposts = (int)$forum['posts'] - 1;
				if($fposts < 0){
					$fposts = 0;
				}
				$db->Update('forums',"`posts`='$fposts'","`id`='$forum_id'");
				if($set_last){
					ForumSetLastPostInfo($forum_id, '', '', array(), $post_array_ignore);
				}
			}
		}
		if($config['forum']['basket'] == false){
			// Удаляем сообщение
			ForumAdminDeletePost($post_id);
		}else{
			$text_value = '';
			if(isset($_POST['text'])){
				$text_value = SafeEnv($_POST['text'],255,str);
			}
			Moderation_Do_Basket_Post($post_id, $text_value);
		}

		Forum_Cache_ClearAllCacheForum();
		GO(Ufu('index.php?name=forum&op=showtopic&topic='.$topic_id.'&page='.$page, 'forum/topic{topic}-{page}.html'));
	}else {
		$text = '<br />'.$lang['delete_post'].'?<br /><br />'
				.' <a href="javascript:history.go(-1)">Нет</a>';
		$site->AddTextBox($lang['forum'], '<center>'.$text.'</center>');
		$site->AddTemplatedBox('', 'module/forum_delete_post.html');
		$site->AddBlock('delete_form', true, false, 'form');
		$vars = array();
		$vars['basket'] = $config['forum']['basket'] == true;
		$vars['url'] = 'index.php?name=forum&op=deletepost&topic='.SafeEnv($_GET['topic'], 11, int).'&post='.SafeEnv($_GET['post'], 11, int).'&ok=1'; // Без UFU
		$site->Blocks['delete_form']['vars'] = $vars;
	}
}
