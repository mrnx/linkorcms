<?php

/*
 * ¬осстановить помеченое и вернуть статистику дл€ удалЄнных постов
 */
function IndexForumRestoreBasketPost( $id = 0, $go_back = true ){
	global $db, $lang;
	$mdb = $db->Select('forum_posts', "`id`='$id' and `delete`='1'");
	if(count($mdb) > 0){
		$post = $mdb[0];
		$topic_id = SafeDB($post['object'], 11, int);
		$db->Select('forum_topics', "`id`='$topic_id'");
		$topic = $db->FetchRow();
		$tposts = SafeDB($topic['posts'], 11, int) + 1;
		if($tposts < 0){
			$tposts = 0;
		}
		$db->Update('forum_topics',"`posts`='$tposts'", "`id`='$topic_id'");
		$forum_id = SafeDB($topic['forum_id'],11,int);
		$db->Select('forums',"`id`='$forum_id'");
		$forum = $db->FetchRow();
		$fposts = SafeDB($forum['posts'], 11, int) + 1;
		if($fposts < 0){
			$fposts = 1;
		}
		$db->Update('forums',"`posts`='$fposts'", "`id`='$forum_id'");
		TopicSetLastPostInfo($topic_id);
		ForumSetLastPostInfo($forum_id);
		$db->Update('forum_posts', "`delete`='0'", "`id`='$id'");
		$db->Delete('forum_basket_post', "`obj_id`='$id'");
		Forum_Cache_ClearAllCacheForum();
	}
	if($go_back){
		GoBack();
	}
}

/*
 * ¬осстановить помеченое и вернуть статистику дл€ тем
 */
function IndexForumRestoreBasketTopic( $id = 0, $go_back = true ){
	global $db;
	$mdb = $db->Select('forum_topics', "`id`='$id' and`delete`='1'");
	if(count($mdb) > 0){
		foreach($mdb as $topic){
			$forum_id = SafeDB($topic['forum_id'],11,int);
			$db->Select('forums',"`id`='$forum_id'");
			$forum = $db->FetchRow();
			$ftopics = SafeDB($forum['topics'], 11, int) + 1;
			if($ftopics < 0){
				$ftopics = 0;
			}
			$fposts = SafeDB($forum['posts'], 11, int) + SafeDB($topic['posts'], 11, int);
			if($fposts < 0){
				$fposts = 0;
			}
			$fset = "`topics`='$ftopics',`posts`='$fposts'";
			$db->Update('forums', $fset, "`id`='$forum_id'");
		}
	}
	$db->Update('forum_topics', "`delete`='0'", "`id`='$id'");
	$db->Delete('forum_basket_topics', "`obj_id`='$id'");
	if(count($mdb) > 0){
		TopicSetLastPostInfo($id);
		ForumSetLastPostInfo($forum_id);
		Forum_Cache_ClearAllCacheForum();
	}
	if($go_back){
		GoBack();
	}
}
