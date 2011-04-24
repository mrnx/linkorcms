<?php

# LinkorCMS
# © 2006-2008 Галицкий Александр Николаевич (linkorcms@yandex.ru)
# LinkorCMS Development Group
# www.linkorcms.ru
# Лицензия LinkorCMS 1.2.
# Дополненая версия - Муратов Вячеслав (smilesoft@yandex.ru)

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

include_once('forum_init.php');
include_once($forum_lib_dir.'forum_init_main.php');

if(isset($_GET['op'])) {
	$op = $_GET['op'];
}else {
	$op = 'main';
}

switch($op) {
	case 'login':
		ForumLoadFunction('login');
		IndexForumLogin();
		break;
	case 'main':
		ForumLoadFunction('main');
		IndexForumMain();
		break;
	case 'showforum':
		ForumLoadFunction('showforum');
		IndexForumShowForum();
		break;
	case 'showtopic':
		ForumLoadFunction('showtopic');
		IndexForumShowTopic();
		break;

	case 'addtopic':
		ForumLoadFunction('addtopic');
		IndexForumAddTopic();
		break;
	case 'addpost':
		ForumLoadFunction('addpost');
		IndexForumAddPost();
		break;

	case 'markread':
		ForumLoadFunction('markread');
		IndexForumMarkRead();
		break;
	case 'deletetopic':
		if($user->isAdmin()){
			ForumLoadFunction('deletetopic');
			IndexForumDeleteTopic();
		}else {
			HackOff();
		}
		break;
	case 'closetopic':
		if($user->isAdmin()){
			ForumLoadFunction('closetopic');
			IndexForumCloseTopic();
		}else {
			HackOff();
		}
		break;
	case 'begintopic':
		if($user->isAdmin()){
			ForumLoadFunction('begintopic');
			IndexFroumBeginTopic();
		}else {
			HackOff();
		}
		break;

	// Сообщения
	case 'deletepost':
		if($user->isAdmin()){
			ForumLoadFunction('deletepost');
			IndexForumDeletePost();
		}else {
			HackOff();
		}
		break;
	case 'editpost':
		ForumLoadFunction('editpost');
		IndexForumEditPost();
		break;
	case 'savepost':
		ForumLoadFunction('savepost');
		IndexForumSavePost();
		break;
	//

	case 'edittopic':
		ForumLoadFunction('edittopic');
		IndexForumEditTopic();
		break;
	case 'edit_topics':
		ForumLoadFunction('edit_topics');
		IndexForumEditTopics();
		break;
	case 'edit_posts':
		ForumLoadFunction('edit_posts');
		IndexForumEditPosts();
		break;

	case 'marker':
		if(isset($_GET['marker'])){
//			ForumLoadFunction('marker');
//			IndexForumMarker(SafeEnv($_GET['marker'], 11, int));
		}
		exit;
		break;
	case 'markerhits':
		if(isset($_GET['topic_id'])){
//			ForumLoadFunction('markerhits');
//			IndexForumMarkerHits(SafeEnv($_GET['topic_id'], 11, int));
		}
		exit;
		break;

	case 'subscription':
		ForumLoadFunction('subscription');
		IndexForumSubscription();
		break;

	case 'restore_basket':
		if($user->isAdmin()){
			ForumLoadFunction('restore_basket');
			if(isset($_GET['forum_basket_post'])){
				IndexForumRestoreBasketPost(SafeEnv($_GET['forum_basket_post'],11,int));
			}elseif(isset($_GET['forum_basket_topics'])){
				IndexForumRestoreBasketTopic(SafeEnv($_GET['forum_basket_topics'],11,int));
			}
		}
		break;
	case 'usertopics':
		ForumLoadFunction('usertopics');
		IndexForumUserTopics();
		break;
	case 'viewnoread':
		ForumLoadFunction('viewnoread');
		IndexForumViewNoRead();
		break;
	case 'post':
		ForumLoadFunction('showtopic');
		IndexForumShowTopic(SafeEnv($_GET['post'], 11, int));
		break;
	case 'lasttopics':
		ForumLoadFunction('lasttopics');
		IndexForumLastTopics();
		break;
	default:
		HackOff();
}

function IndexForumDataFilter( &$forum, $root= true , $get_online = true) {
	global  $lang, $UFU, $config;
	$forum2 = array();
	$forum2['id'] = SafeDB($forum['id'], 11, int);
	$forum2['parent_id'] = SafeDB($forum['parent_id'], 11, int);
	$forum2['title'] = SafeDB($forum['title'], 255, str);
	$forum2['description'] = SafeDB($forum['description'], 0, str, false, false);
	$forum2['topics'] = SafeDB($forum['topics'], 11, int);

	$forum2['posts'] = SafeDB($forum['posts'], 11, int);
	$forum2['last_post_date'] = SafeDB($forum['last_post'], 11, int);
	$forum2['last_post'] = TimeRender(SafeDB($forum['last_post'], 11, int), true, true);
	if($forum2['last_post_date']> (time()-86400)){
		$forum2['last_post'] = '<FONT COLOR="#FF0000">'.$forum2['last_post'].'</FONT>';
	}
	$forum2['last_poster_id'] = SafeDB($forum['last_poster_id'], 11, int);
	$forum2['last_poster_url'] = (!$UFU?'index.php?name=user&op=userinfo&user='.$forum2['last_poster_id']:
			'user/'.$forum2['last_poster_id']);

	$forum2['last_poster_name'] = SafeDB($forum['last_poster_name'], 255, str);
	$forum2['last_title'] = 	DivideWord(SafeDB($forum['last_title'], 255, str));
	$forum2['last_id'] = SafeDB($forum['last_id'], 11, int);
	$forum2['order'] = SafeDB($forum['order'], 11, int);
	$forum2['status'] = SafeDB($forum['status'], 1, int);
	$forum2['view'] = SafeDB($forum['view'], 1, int);
	if($get_online) {
		$c = Online_GetCountUser($forum2['id'] ,$root);
		$forum2['count_read']  = $c['count'];
		$forum2['users'] = $c['users'];
	}else{
		$forum2['count_read']  = ' ';
		$forum2['users'] = ' ';
	}
	$forum2['admin_theme_add'] = SafeDB($forum['admin_theme_add'], 1, int);
	$forum2['new_message_email'] = SafeDB($forum['new_message_email'], 1, int);
	$forum2['no_link_guest'] = SafeDB($forum['no_link_guest'], 1, int);
	$forum2['rang_access'] = SafeDB($forum['rang_access'], 11, int);
	$forum2['rang_message'] = SafeDB($forum['rang_message'], 11, int);
	$forum2['rang_add_theme'] = SafeDB($forum['rang_add_theme'], 11, int);
	$forum2['close_topic'] = SafeDB($forum['close_topic'], 1, int);
	$forum2['pages'] = false;

	if($forum2['topics'] > $config['forum']['topics_on_page']){
		$forum2['pages'] = true;
		$forum2['pages'] =$lang['pages'];
		$forum_nav_url = (!$UFU ? 'index.php?name=forum&amp;op=showforum&amp;forum=' : 'forum/');
		$page = ceil($forum2['topics']/ $config['forum']['topics_on_page']);
		$str ='';
		for ($i = 0, $page ;$i< $page	 ; $i++) {
			$str .= '<a href="'.$forum_nav_url.$forum2['id'].(!$UFU?'&page=':'-').($i+1).'">'.($i+1).' </a>';
			if($i>5 and $page>14) {
				$str .= '<a href="'.$forum_nav_url.$forum2['id'].(!$UFU?'&page=':'-').($page-1).'">'.($page-1).' </a>';
				$str .= '......<a href="'.$forum_nav_url.$forum2['id'].(!$UFU?'&page=':'-').$page.'">'.$page.' </a>';
				break;
			}
		}
		$forum2['pages'] .= $str;
	}

	if($forum2['close_topic']==1) {
		$forum2['description'].=''.$lang['close_for_discussion'];
	}

	$forum2['url'] = (!$UFU?'index.php?name=forum&op=showforum&forum='.$forum2['id']:'forum/'.$forum2['id']);
	$forum2['last_url_topic'] = (!$UFU?'index.php?name=forum&amp;op=showtopic&amp;topic='.$forum2['last_id'].'&amp;view=lastpost':'forum/topic'.$forum2['last_id'].'-new.html');

	return $forum2;
}

function IndexForumCatOpen( &$category ) {
	global $site, $lang;
	$category['is_cat_open'] = true;
	$category['is_cat'] = false;
	$category['is_forum'] = false;
	$category['is_cat_close'] = false;
	if($category['close_topic']==1) {
		$category['title'].='&nbsp;'.$lang['close_for_discussion'];
	}
	$site->AddSubBlock('forums', true, $category);
}

function IndexForumCatClose( &$category ) {
	global $site, $lang;
	$category['is_cat_close'] = true;
	$category['is_cat'] = false;
	$category['is_forum'] = false;
	$category['is_cat_open'] = false;
	$category['close'] = $category['close_topic']==0;
	$category['begin'] = !$category['close'];
	$category['status'] = (!$category['close']?$lang['category_locked']:'');
	if(!isset(	 $category['count_read']))
		$category['count_read']  = ' ';
	if(!isset(	 $category['users']))
		$category['users'] = ' ';
	$site->AddSubBlock('forums', true, $category);
}

function IndexForumRender( &$forum, $read = false, $pod_forums=array() ){
	global $site, $lang;
	if($forum['parent_id'] == '0') {
		$forum['is_cat'] = true;
		$forum['is_forum'] = false;
	}else {
		$forum['is_cat'] = false;
		$forum['is_forum'] = true;
	}
	$forum['is_cat_open'] = false;
	$forum['is_cat_close'] = false;
	$forum['on'] = !$read;
	$forum['off'] = $read;
	$forum['close'] = $forum['close_topic'] == 0;
	$forum['begin'] = !$forum['close'];
	$forum['status'] = (!$forum['close'] ? $lang['category_locked'] : '');
	if(!isset($forum['count_read'])){
		$forum['count_read']  = ' ';
	}
	if(!isset($forum['users'])){
		$forum['users'] = ' ';
	}

    $forum['subforums'] = false;
	if(count($pod_forums)>0){
    $forum['subforums'] = true;
		  $forum['subforums'] = '<table border="0" cellpadding="0" cellspacing="0" width="100%" align="center" valign="top"><tr valign="top">'; 
		  $i = 0;
		foreach($pod_forums as $pod_forum){		
			$i++;
		  $img = (!$pod_forum['read']?'subforum.gif':'subforum_old.gif');
		  $alt = (!$pod_forum['read']?'Есть новые сообщения':'Все прочитаны.');
		  $forum['subforums'] .= '<td style="text-transform:none;font-size:11px;  padding-left:10px;" ><img src="images/'.$img.'" title="'.$alt.'" alt="'.$alt.'" border="0" />&nbsp;<a href="forum/'.$pod_forum['id'].'" title="'.substr(strip_tags($pod_forum['description']),0,250).'... тем  '.$pod_forum['topics'].'">'.$pod_forum['title'].' ('.$pod_forum['topics'].')</a> &nbsp;&nbsp;</td>	'.($i==3?'</tr><tr valign="top">':'') ;
		  if($i==3) $i = 0;
		}
		 $forum['subforums'] .=($i<2?'<td></td>':'').'</tr></table><BR>';
	}
	$site->AddSubBlock('forums', true, $forum);
}

?>