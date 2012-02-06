<?php
# LinkorCMS
# LinkorCMS Development Group
# www.linkorcms.ru
# Лицензия LinkorCMS 1.2.
# функции администрирования

function Moderation_Do_Basket_Topic($topic_id = -1, $text_value=''){
	global $db, $user;
	if($topic_id > -1){
		$db->Update('forum_topics',"`delete`='1'","`id`='$topic_id'");
		$text_value = SafeEnv($text_value,255,str);
		$vals = Values('', time(), $user->Get('u_id'), $text_value, $topic_id);
		$db->Insert('forum_basket_topics',$vals);
	}
	Forum_Cache_ClearAllCacheForum();
}

function Moderation_Do_Basket_Post($post_id = -1, $text_value=''){
	global $db, $user;
	if($post_id > -1){
		$db->Update('forum_posts',"`delete`='1'","`id`='$post_id'");
		$vals = Values('', time(), $user->Get('u_id'), $text_value, $post_id);
		$db->Insert('forum_basket_post',$vals);
	}
}

function Moderation_Do_Delete_Topic2($topic_id){
	global $db, $config;
	if(isset($_GET['ok']) && SafeEnv($_GET['ok'],1,int)=='1'){
		$db->Select('forum_topics',"`id`='$topic_id'");
		$topic = $db->FetchRow();
		if($topic['delete'] == 0){
			$forum_id = SafeDB($topic['forum_id'],11,int);
			$db->Select('forums',"`id`='$forum_id'");
			$forum = $db->FetchRow();
			$ftopics = (int)$forum['topics'] - 1;
			if($ftopics<0){
				$ftopics=0;
			}
			$fposts = (int)$forum['posts'] - (int)$topic['posts'];
			if($fposts<0){
				$fposts=0;
			}
			$fset = "`topics`='$ftopics',`posts`='$fposts'";
			if((int)$topic['id'] == (int)$forum['last_id']){
				if($fposts == 0){
					$fset .= ",`last_post`='0',`last_poster_id`='0',`last_poster_name`='',`last_title`='',`last_id`='0'";
				}else{
					ForumSetLastPostInfo($forum_id, " and (`id`<'$topic_id' or `id`>'$topic_id')");
				}
			}
			$db->Update('forums',$fset,"`id`='$forum_id'");
		}
		if($config['forum']['basket'] == false){
			ForumAdminDeleteTopic($topic_id);
		}else{
			if($topic['delete'] == 0){
				$text_value = '';
				if(isset($_POST['text'])){
					$text_value = SafeEnv($_POST['text'],255,str);
				}
				Moderation_Do_Basket_Topic($topic_id, $text_value);
			}
		}
	}

}

function Moderation_Do_Delete_Topic(){
	global $db;
	if(isset($_GET['ok']) && SafeEnv($_GET['ok'],1,int)=='1'){
		$topics = explode(',',$_POST['topics']);
		foreach( $topics as $topic) {
			Moderation_Do_Delete_Topic2( $topic);
		}

		Forum_Cache_ClearAllCacheForum();
	}
}

function Moderation_Do_Open(){
	global $db;
	if(isset($_GET['ok']) && SafeEnv($_GET['ok'],1,int)=='1'){
		$topics = explode(',',$_POST['topics']);
		$ed_top = implode("' or`id`='",$topics);
		$ed_top	= "`id`='".$ed_top."'";
		$db->Update('forum_topics',"`close_topics`='0'",$ed_top);
		Forum_Cache_ClearAllCacheForum();
	}
}

function Moderation_Do_Close(){
	global $db;
	if(isset($_GET['ok']) && SafeEnv($_GET['ok'],1,int)=='1'){
		$topics = explode(',',$_POST['topics']);
		$ed_top = implode("' or`id`='",$topics);
		$ed_top	= "`id`='".$ed_top."'";
		$db->Update('forum_topics',"`close_topics`='1'",$ed_top);
		Forum_Cache_ClearAllCacheForum();
	}
}

function Moderation_Do_Stick() {
	global $db;
	if(isset($_GET['ok']) && SafeEnv($_GET['ok'],1,int)=='1'){
		$topics = explode(',',$_POST['topics']);
		$ed_top = implode("' or`id`='",$topics);
		$ed_top	= "`id`='".$ed_top."'";
		$db->Update('forum_topics',"`stick`='1'",$ed_top);
		Forum_Cache_ClearAllCacheForum();
	}
}

function Moderation_Do_UnStick() {
	global $db;
	if(isset($_GET['ok']) && SafeEnv($_GET['ok'],1,int)=='1'){
		$topics = explode(',',$_POST['topics']);
		$ed_top = implode("' or`id`='",$topics);
		$ed_top	= "`id`='".$ed_top."'";
		$db->Update('forum_topics',"`stick`='0'",$ed_top);
		Forum_Cache_ClearAllCacheForum();
	}
}

function Moderation_Do_MoveTopic(){
	global $site, $db, $lang;
	if(isset($_GET['ok']) && SafeEnv($_GET['ok'],1,int)=='1'){
		$forum = $_POST['forum'];
		$mdb = $db->Select('forums', "`id`='$forum'");
		if(count($mdb) > 0 && $mdb[0]['parent_id'] > 0){
			$topics = explode(',',$_POST['topics']);
			$ed_top = implode("' or`id`='",$topics);
			$ed_top	= "`id`='".$ed_top."'";
			$e_t = $db->Select('forum_topics', $ed_top);
			$ar_forums = array();
			$ar_forums[$forum] = $forum;
			foreach( $e_t as $topic){
				$forum_id	 = $topic['forum_id'];
				$ar_forums[$forum_id] = $forum_id;
				$id	 = $topic['id'];
				$count_post = $topic['posts'];
				CalcCounter('forums', "`id`='$forum_id'", 'topics', -1);
				CalcCounter('forums', "`id`='$forum_id'", 'posts', -$count_post);
				CalcCounter('forums', "`id`='$forum'", 'topics', 1);
				CalcCounter('forums', "`id`='$forum'", 'posts', $count_post);
				$db->Update('forum_topics',"`forum_id`='$forum'","`id`='$id'");
			}
			foreach($ar_forums	as $forum_id){
				ForumSetLastPostInfo($forum_id);
			}
			Forum_Cache_ClearAllCacheForum();
		}else{
			return $site->AddTextBox($lang['error'],$lang['error_no_forum'].'<BR><a href="javascript:history.go(-1)">'.$site->Button('Назад').'</a>');
		}
	}else{
		return $lang['select_category'].'<BR><center>'.Navigation_GetForumCategoryComboBox(0, false, true, false).'</center>';

	}
}

function Moderation_Do_MergeTopic(){
	global $site, $db, $lang;
	if(isset($_GET['ok']) && SafeEnv($_GET['ok'],1,int)=='1') {
		$dest_topic =  $_POST['dest_topic'];
		$all_topics = explode(',',$_POST['topics']);
		foreach($all_topics as $top)
			if($top<>$dest_topic) $topics[] = $top;
		$ed_top = implode("' or`id`='",$topics);
		$ed_top	= "`id`='".$ed_top."'";
		$e_t = $db->Select('forum_topics', $ed_top);
		foreach( $e_t as $topic) {
			$forum_id	= $topic['forum_id'];
			$id	= $topic['id'];
			CalcCounter('forums', "`id`='$forum_id'", 'topics', -1);
			$db->Update('forum_posts',"`object`='$dest_topic'","`object`='$id'");
			$db->Delete('forum_topics',"`id`='$id'");
		}
		Forum_Cache_ClearAllCacheForum();
	}
	else {
		$topics = array();
		foreach (array_keys($_POST['topics']) AS $topic) {
			$topic = intval($topic);
			$topics["$topic"] = $topic;
		}
		unset($topic);
		$ed_top = implode("' or`id`='",$topics);
		$ed_top	= "`id`='".$ed_top."'";

		$e_t = $db->Select('forum_topics', $ed_top);
		$data = array();
		foreach( $e_t as $topic) {
			$site->DataAdd($data, $topic['id'], $topic['title'], false);
		}
		return $lang['merge_dest_topic'].'<BR>'.$site->Select('dest_topic', $data).'<BR><BR>';
	}
}


function Moderation_GetDo($do, $begin = false) {
	global $lang;
	global $config, $site;
	switch($do) {
		case 'deletetopic':
			if($begin) {
				Moderation_Do_Delete_Topic();
			}
			else {
				if( $config['forum']['basket'] == true ) {
					$site->AddTemplatedBox('', 'module/forum_delete.html');
					$site->AddBlock('delete_form', true, false, 'form');
					$vars = array();
					$vars['basket'] = $config['forum']['basket'] == true ;
					$site->Blocks['delete_form']['vars'] = $vars;
				}
			}
			return $lang['delete_topics'];
		case 'open':  if($begin) Moderation_Do_Open();
			return $lang['open_topics'];
		case 'close':  if($begin) Moderation_Do_Close();
			return $lang['close_topics'];
		case 'stick':  if($begin) Moderation_Do_Stick();
			return $lang['important_topics'];
		case 'unstick':  if($begin) Moderation_Do_UnStick();
			return $lang['remove_important_topics'];
		case 'movetopic':    return   Moderation_Do_MoveTopic().$lang['move_topics'];
		case 'mergetopic':  return Moderation_Do_MergeTopic().$lang['merge_topics'];
	}
}


function Moderation_Do_DeletePosts(){
	global $db, $config;

	$posts = explode(',', $_POST['posts']);

	// Обновляем данные темы
	$id = SafeEnv($posts[0], 11, int);
	$db->Select('forum_posts', "`id`='$id'");
	$mdb = $db->FetchRow();
	if($mdb['delete'] == 0){
		$topic_id = SafeDB($mdb['object'], 11, int);
		$db->Select('forum_topics', "`id`='$topic_id'");
		$topic = $db->FetchRow();

		if($topic['delete'] == 0){
			$tposts = (int)$topic['posts'] - count($posts);
			if($tposts < 0){
				$tposts = 0;
			}
			$db->Update('forum_topics',"`posts`='$tposts'","`id`='$topic_id'");

			// Обновляем данные форума
			$forum_id = SafeDB($topic['forum_id'],11,int);
			$last_post_topic =  SafeDB($topic['last_post'],11,int);
			$db->Select('forums',"`id`='$forum_id'");
			$forum = $db->FetchRow();
			$fposts = (int)$forum['posts'] - count($posts);
			if($fposts < 0){
				$fposts = 0;
			}
			$fset = "`posts`='$fposts'";
			if($fposts == 0){
				$fset .= ",`last_post`='0',`last_poster_id`='0',`last_poster_name`='',`last_title`='',`last_id`='0'";
				$db->Update('forums', $fset, "`id`='$forum_id'");
			}else{
				$p = array();
				$db->Update('forums', $fset, "`id`='$forum_id'");
				TopicSetLastPostInfo($topic_id,$posts );
				if($forum['last_post'] == $last_post_topic){
					ForumSetLastPostInfo($forum_id,'', '',$p , $posts);
				}
			}
		}
	}

	$text_value = '';
	if(isset($_POST['text'])){
		$text_value = SafeEnv($_POST['text'],255,str);
	}
	foreach($posts as $post){
		if($config['forum']['basket'] == false){
			ForumAdminDeletePost($post);
		}else{
			$mdb = $db->Select('forum_posts',"`id`='$post'");
			if(count($mdb) >0){
				$mdb = $db->FetchRow();
				if($mdb['delete'] == 0){
					Moderation_Do_Basket_Post($post, $text_value);
				}
			}
		}
	}
	Forum_Cache_ClearAllCacheForum();
}

function Moderation_Do_MergePosts() {
	global $db;
	if(isset($_GET['ok']) && SafeEnv($_GET['ok'],1,int)=='1') {
		$posts =$_POST['posts'];
		$posts = explode(',',$posts);
		if(count($posts)>1) {
			$ed_posts = implode("' or`id`='",$posts);
			$ed_posts = "`id`='".$ed_posts."'";
			$id = $posts[0];
			$db->Select('forum_posts',"`id`='$id'");
			$mdb = $db->FetchRow();
			$topic_id =	 $mdb['object'];
			$db->Select('forum_topics',"`id`='$topic_id'");
			$topic = $db->FetchRow();
			$tposts = (int)$topic['posts'] - (count($posts)-1);
			$db->Update('forum_topics',"`posts`='$tposts'","`id`='$topic_id'");
			$forum_id = SafeDB($topic['forum_id'],11,int);
			$db->Select('forums', "`id`='$forum_id'");
			$forum = $db->FetchRow();
			$fposts = (int)$forum['posts'] - (count($posts)-1);
			$db->Update('forums',"`posts`='$fposts'","`id`='$forum_id'");
			$posts = $db->Select('forum_posts', $ed_posts);
			SortArray($posts, 'public', false); //Сортируем по дате
			$text = '';
			$where = '';
			foreach($posts as $post){
				if($text <> '') $where.= "`id`= '".SafeEnv($post['id'], 11, int)."' or ";
				$text .= $post['message']."\r\n"."\r\n";
			}
			$text = SafeEnv($text, 0, str);
			$db->Update('forum_posts',"`message`='$text'","`id`='".SafeEnv($posts[0]['id'], 11, int)."'");
			$where = substr($where,0,strlen($where)-3);
			if($where <>'')
				$db->Delete('forum_posts',$where);
			Forum_Cache_ClearAllCacheForum();
		}
	}
}


function Moderation_GetPostsDo( $do ) {
	global $site, $config, $lang;
	switch($do){
		case 'deleteposts': Moderation_Do_DeletePosts();
			break;
		case 'mergeposts': Moderation_Do_MergePosts();
			break;
	}
}
