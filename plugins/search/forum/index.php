<?php

if(!defined('VALID_RUN')){
	Header("Location: http://".getenv("HTTP_HOST")."/index.php");
	exit;
}

global $search_results, $searchstr, $db;

$forums = $db->Select('forums',"`view`='4'");
foreach($forums as $forum){
	$available[$forum['id']] = true;
}

$topics = $db->Select('forum_topics',"`state`='1'");

foreach($topics as $topic){
	if(!isset($available[$topic['forum_id']])) continue;

	$result = array();
	$result['mod'] =  $plugin['mod_title'];  // Имя модуля
	$result['coincidence'] = ''; // Показывает где было совпадение
	$result['title'] = SafeDB($topic['title'],255,str);
	$result['public'] = TimeRender(SafeDB($topic['start_date'],11,int));
	$result['link'] = 'index.php?name=forum&op=showtopic&topic='.SafeDB($topic['id'],11,int);

	if(SSearch($topic['title'], $searchstr) != false){
		$result['text'] = SCoincidence($topic['title'], $searchstr);
		$result['coincidence'] = 'Название темы на форуме';
		$search_results[] = $result;
	}

	$pid = SafeDB($topic['id'],11,int);
	$posts = $db->Select('forum_posts',"`object`='$pid'");

	foreach($posts as $post){
		if(SSearch($post['message'], $searchstr) != false){
			$result['text'] = SCoincidence($post['message'], $searchstr);
			$result['coincidence'] = 'Текст сообщения в теме на форуме';
			$search_results[] = $result;
		}
	}
}

?>