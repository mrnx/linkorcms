<?php

function IndexForumMarker($topic_id = '') {
	global  $user, $db;
	if($user->Auth and $topic_id <> '') {
		$u_id = $user->Get('u_id');
		$db->Delete('forum_topics_read', "`tid`='$topic_id' and `mid`='$u_id'");
		$time = time();
		$vals = "'$u_id','$topic_id','$time'";
		$db->Insert('forum_topics_read',$vals);
	}
	// Восстанавливаем Referer
	//$user->Def('REFERER',$_SERVER['HTTP_REFERER']);
}
