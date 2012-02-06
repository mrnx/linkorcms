<?php
# LinkorCMS
# LinkorCMS Development Group
# www.linkorcms.ru
# Ћицензи€ LinkorCMS 1.2.
# кеширование

define("FORUM_CACHE_DIR",'cache/forum/');
define("FORUM_CACHE_ALL_DATA_FORUMS",'Forum_Cache_AllDataTableForum');
define("FORUM_CACHE_ALL_DATA_FORUM_TOPICS",'Forum_Cache_AllDataTableForumTopics');

function Forum_Cache_Topic_DataFilter( &$topic ){
	$topic2 = array();
	$topic2['id'] = $topic['id'];
	$topic2['forum_id'] = $topic['forum_id'];
	$topic2['title'] = $topic['title'];
	$topic2['state'] = $topic['state'];
	$topic2['posts'] = $topic['posts'];
	$topic2['hits'] = $topic['hits'];
	$topic2['start_date'] = $topic['start_date'];
	$topic2['starter_id'] = $topic['starter_id'];
	$topic2['starter_name'] = $topic['starter_name'];
	$topic2['last_post'] = $topic['last_post'];
	$topic2['last_poster_id'] = $topic['last_poster_id'];
	$topic2['last_poster_name'] = $topic['last_poster_name'];
	$topic2['uniq_code'] = $topic['uniq_code'];
	$topic2['close_topics'] = $topic['close_topics'];
	$topic2['stick'] = $topic['stick'] ;
	$topic2['delete'] = $topic['delete'];
	return $topic2;
}

function Forum_Cache_Forum_DataFilter( &$forum ){
	$forum2 = array();
	$forum2['id'] = $forum['id'];
	$forum2['parent_id'] = $forum['parent_id'];
	$forum2['title'] = $forum['title'];
	$forum2['description'] = $forum['description'];
	$forum2['topics'] = $forum['topics'];
	$forum2['posts'] = $forum['posts'];
	$forum2['last_post_date'] = $forum['last_post'];
	$forum2['last_post'] = $forum['last_post'];
	$forum2['last_poster_id'] = $forum['last_poster_id'];
	$forum2['last_poster_name'] = $forum['last_poster_name'];
	$forum2['last_title'] = $forum['last_title'];
	$forum2['last_id'] = $forum['last_id'];
	$forum2['order'] = $forum['order'];
	$forum2['status'] = $forum['status'];
	$forum2['view'] = $forum['view'];
	$forum2['admin_theme_add'] = $forum['admin_theme_add'];
	$forum2['new_message_email'] = $forum['new_message_email'];
	$forum2['no_link_guest'] = $forum['no_link_guest'];
	$forum2['rang_access'] = $forum['rang_access'];
	$forum2['rang_message'] = $forum['rang_message'];
	$forum2['rang_add_theme'] = $forum['rang_add_theme'];
	$forum2['close_topic'] = $forum['close_topic'];
	return $forum2;
}

/*
 * получить все записи из таблицы forum_topics и закешировать
 */
function Forum_Cache_AllDataTableForumTopics() {
	global $db, $config;
	static $forum_topics = array();
	static $qwerytopic = false;
	if(!$qwerytopic){
		if($config['forum']['cache']){
			$cache = LmFileCache::Instance();
			if($cache->HasCache('forum', FORUM_CACHE_ALL_DATA_FORUM_TOPICS)){
				$forum_topics = $cache->Get('forum', FORUM_CACHE_ALL_DATA_FORUM_TOPICS);
			}else{
				$forum_topics = $db->Select('forum_topics');
				$cache->Write('forum', FORUM_CACHE_ALL_DATA_FORUM_TOPICS, $forum_topics);
			}
		}else{
			$forum_topics = $db->Select('forum_topics');
		}
		$qwerytopic = true;
	}
	return $forum_topics;
}

/*
 * ѕолучить все записи из таблицы forums и закешировать
 */
function Forum_Cache_AllDataTableForum() {
	global $db, $config;
	static $forums  = array();
	static $qweryf = false;

	if(!$qweryf){
		$where = "`status`='1'";
		if($config['forum']['cache']){
			$cache = LmFileCache::Instance();
			if($cache->HasCache('forum', FORUM_CACHE_ALL_DATA_FORUMS)){
				$forums = $cache->Get('forum', FORUM_CACHE_ALL_DATA_FORUMS);
			}else{
				$forums = $db->Select('forums', $where);
				$cache->Write('forum', FORUM_CACHE_ALL_DATA_FORUMS, $forums);
			}
		}else{
			$forums = $db->Select('forums', $where);
		}
		$qweryf = true;
	}
	return $forums ;
}

function Forum_Cache_AllUpdateCache() {
	global $config;
	if($config['forum']['cache']){
		$cache = LmFileCache::Instance();
		$cache->Delete('forum', FORUM_CACHE_ALL_DATA_FORUM_TOPICS);
		$cache->Delete('forum', FORUM_CACHE_ALL_DATA_FORUMS);
	}
}

function Forum_Cache_ClearAllCacheForum() {
	global $config;
	static $clear = false;
	if($config['forum']['cache']){
		if(!$clear){
			$cache = LmFileCache::Instance();
			$cache->Clear('forum');
		}
	}
}
