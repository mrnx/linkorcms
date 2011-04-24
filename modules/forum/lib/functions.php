<?php

// LinkorCMS
// LinkorCMS Development Group
// www.linkorcms.ru
// Лицензия LinkorCMS 1.3.
# Дополненая версия - Муратов Вячеслав (smilesoft@yandex.ru)

/* * 05.02.2010
  функции TopicSetLastPostInfoDelete и ForumSetLastPostInfoDelete
  переименованы в TopicSetLastPostInfo и ForumSetLastPostInfo
 */

function ForumLoadFunction( $function ){
	global $forum_functions_dir;
	include_once $forum_functions_dir.$function.'.php';
}

function EchoForum( $var ){
	return true;
	echo $var.'<BR>';
}

function IndexForumPrintErrors( $errors ){
	global $lang;
	$text = $lang['error_comment_add'].'.<br /><ul>';
	foreach($errors as $error){
		$text .= '<li>'.$error;
	}
	$text .= '</ul><center><a href="javascript:history.back()">'.$lang['back'].'</a></center>';
	return $text;
}

function ForumAdminDeletePost( $post_id ){
	global $db;
	$db->Delete('forum_posts', "`id`='$post_id'");
}

function ForumAdminDeleteTopic( $topic_id, $delete = true ){
	global $db;
	ForumAdminDeletePosts($topic_id);
	$db->Delete('forum_topics_read', "`tid`='$topic_id'");
	Forum_Subscription_Delete($topic_id);
	if($delete){
		$db->Delete('forum_topics', "`id`='$topic_id'");
	}
}

function ForumAdminDeletePosts( $topic_id ){
	global $db;
	$db->Delete('forum_posts', "`object`='$topic_id'");
}

function ForumAdminDeleteTopics( $forum_id ){
	global $db;
	$topics = $db->Select('forum_topics', "`forum_id`='$forum_id'");
	if($db->NumRows() > 0){
		foreach($topics as $topic){
			$topic_id = SafeDB($topic['id'], 11, int);
			ForumAdminDeleteTopic($topic_id, false);
		}
	}
	$db->Delete('forum_topics', "`forum_id`='$forum_id'");
}

function ForumAdminDeleteForum( $forum_id, $delete = true ){
	global $db;
	$db->Select('forums', "`id`='$forum_id'");
	if($db->NumRows() > 0){
		$forum = $db->FetchRow();
		if($forum['parent_id'] == '0'){ // Это категория?
			$sub_forums = $db->Select('forums', "`parent_id`='$forum_id'");
			if($db->NumRows() > 0){
				foreach($sub_forums as $forum2){
					ForumAdminDeleteForum(SafeDB($forum2['id'], 11, int), false);
				}
			}
			$db->Delete('forums', "`id`='$forum_id' or `parent_id`='$forum_id'");
		}else{
			ForumAdminDeleteTopics($forum_id);
			if($delete){
				$db->Delete('forums', "`id`='$forum_id'");
				$db->Update('forums', "`parent_id`='0'", "`parent_id`='".$forum_id."'");
			}
		}
	}
}

function No_link_guest( &$text ){
	global $lang;
	$replace = '<p class="notice">'.$lang['for_auth_user'].'</p>';
	$text = preg_replace("!<a[^>]*(http|www)(.*)</a>!siU", $replace, $text);
	$text = eregi_replace('(http|www)([[:alnum:]/\n+-=%&:_.~?]+[#[:alnum:]+]*)', $replace, $text);
	return $text;
}

function IndexForumSetLastPostInfo( $forum, $topic ){
	global $user, $db;
	$forum_id = SafeDB($forum['id'], 11, int);
	$topic_id = SafeDB($topic['id'], 11, int);
	$posts = SafeDB($forum['posts'], 11, int);
	$topics = SafeDB($forum['topics'], 11, int);
	$last_post = time();
	$last_poster_id = $user->Get('u_id');
	$last_poster_name = $user->Get('u_name');
	$last_topic_title = SafeDB($topic['title'], 255, str);
	$last_topic_id = SafeDB($topic['id'], 11, int);
	$db->Update('forums', "`posts`='$posts',`last_post`='$last_post',`last_poster_id`='$last_poster_id',`last_poster_name`='$last_poster_name',`last_title`='$last_topic_title',`last_id`='$last_topic_id',`topics`='$topics'"
		, "`id`='$forum_id'");
	$topic_posts = SafeDB($topic['posts'], 11, int);
	$db->Update('forum_topics'
		, "`posts`='$topic_posts',`last_post`='$last_post',`last_poster_id`='$last_poster_id',`last_poster_name`='$last_poster_name'"
		, "`id`='$topic_id'");
}

function IgnoreInArray( $array_ignor, $mdbp ){
	$mdb = array();
	if(count($array_ignor) > 0){
		foreach($mdbp as $myarr){
			if(!in_array($myarr[0], $array_ignor))
				$mdb[] = $myarr;
		}
	}
	else{
		return $mdbp;
	}
	return $mdb;
}

function TopicSetLastPostInfo( $topic_id, $post_array_ignore= array() ){
	global $db;
	$mdb = $db->Select('forum_posts', "`object`='$topic_id' and `delete`='0'");
	if(count($mdb) > 0){
		$mdb = IgnoreInArray($post_array_ignore, $mdb);
		if(count($mdb) > 0){
			$id = count($mdb) - 1;
			if($id < 0)
				$id = 0;
			if(count($mdb) > 0){
				$a_post = $mdb[$id];
			}else{
				$a_post = $mdb;
			}
			$last_post = Safedb($a_post['public'], 11, int);
			$last_poster_id = Safedb($a_post['user_id'], 11, int);
			$last_poster_name = Safedb($a_post['name'], 50, str);
			$db->Update('forum_topics', "`last_post`='$last_post', `last_poster_id`='$last_poster_id', `last_poster_name`='$last_poster_name'", "`id`='$topic_id'");
		}else{
			$db->Update('forum_topics', "`last_post`='',`last_poster_id`='',`last_poster_name`=''", "`id`='$topic_id'");
		}
	}
}

function ForumSetLastPostInfo( $forum_id, $topic_where_ignor='', $post_where_ignor='', $topic_array_ignore = array(), $post_array_ignor= array() ){
	global $db;
	$mdb = $db->Select('forum_topics', "`forum_id`='$forum_id' and `delete`='0' ".$topic_where_ignor);
	if(count($mdb) > 0){
		$mdb = IgnoreInArray($topic_array_ignore, $mdb);
		$where = '';
		foreach($mdb as $topic){
			$where .= "`object`='".$topic['id']."' or";
		}
		$where = substr($where, 0, strlen($where) - 3);
		$mdbp = array();
		$mdbp = $db->Select('forum_posts', $where.$post_where_ignor);
		$mdb = array();
		$mdb = IgnoreInArray($post_array_ignor, $mdbp);

		if(count($mdb) > 0){
			$a_post = $mdb[count($mdb) - 1];
			$mdbp = array();
			$mdbp = $db->Select('forum_topics', "`id`='".$a_post['object']."'");
			$mdb = IgnoreInArray($topic_array_ignore, $mdbp);

			if(count($mdb) > 0){
				$a_topic = $mdb[count($mdb) - 1];
				$last_post = SafeDB($a_topic['last_post'], 11, int);
				$last_poster_id = SafeDB($a_topic['last_poster_id'], 11, int);
				$last_poster_name = SafeDB($a_topic['last_poster_name'], 255, str);
				$last_topic_title = SafeDB($a_topic['title'], 255, str);
				$last_topic_id = SafeDB($a_topic['id'], 11, int);
				$db->Update('forums',
					"`last_post`= '$last_post', `last_poster_id`= '$last_poster_id', `last_poster_name`= '$last_poster_name',`last_title`= '$last_topic_title',`last_id`= '$last_topic_id'",
					"`id`= '$forum_id'");
				return true;
			}
		}
	}

	if($forum_id > 0)
		$db->Update('forums', "`last_post`= '', `last_poster_id`= '', `last_poster_name`= '',`last_title`= '',`last_id`= ''", "`id`= '$forum_id'");
	return false;
}

function ForumSmile(){
	global $db, $site, $config;
	$site->AddBlock('smilies', true, true, 'smile');
	$cache = LmFileCache::Instance();
	if($cache->HasCache('forum', 'ForumSmilies')){
		$smilies = $cache->Get('forum', 'ForumSmilies');
	}else{
		$smilies = $db->Select('smilies', "`enabled`='1'");
		$cache->Write('forum', 'ForumSmilies', $smilies, Day2Sec);
	}
	foreach($smilies as $smile){
		$smile['file'] = $config['general']['smilies_dir'].$smile['file'];
		$smile['code'] = SafeDB($smile['code'], 255, str);
		$sub_codes = explode(',', $smile['code']);
		$smile['code'] = $sub_codes[0];
		$site->AddSubBlock('smilies', true, $smile);
	}
}

function ForumGetViewAccessTable( &$table = array() ){
	global $user;
	$result = array();
	$a = $user->AccessLevel();
	if($a == 1){
		$result = $table;
	}else{
		foreach($table as $mtable){
			if($mtable['view'] == 4 or $mtable['view'] == $a)
				$result[] = $mtable;
		}
	}
	return $result;
}

?>