<?php

function IndexForumMarkerHits($topic_id) {
	global $db, $user;
	if( $topic_id<>''){
		$where = "`id`='$topic_id'";
		$topic = $db->Select('forum_topics',$where);
		if(count($topic)>0) {
			$hits = $topic[0]['hits'] + 1;
			$db->Update('forum_topics', "hits='".$hits."'", "`id`='".$topic_id."'");
		}
	}
	// Восстанавливаем Referer
	//$user->Def('REFERER',$_SERVER['HTTP_REFERER']);
}

?>