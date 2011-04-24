<?php

// Закрыть тему для обсуждения
function IndexForumCloseTopic(){
	global $db, $site;
	$topic_id = SafeEnv($_GET['topic'], 11, int);
	$db->Select('forum_topics',"`id`='$topic_id'");
	$topic = $db->FetchRow();
	// Форум
	$forum_id = SafeDB($topic['forum_id'], 11, int);
	$db->Update('forum_topics', "`close_topics`='1'", "`id`='$topic_id'");
	GO('index.php?name=forum&op=showforum&forum='.$forum_id);
}

?>