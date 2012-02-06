<?php

// Отметить все, как прочитанные
function IndexForumMarkRead(){
	global $db, $user, $config;
	if(isset($_GET['forum'])){
		$forum_id = SafeEnv($_GET['forum'],11,int);
		$where = "`forum_id`='".$forum_id."'";
	}else {
		$where = '';
	}
	$u_id = $user->Get('u_id');
	if($user->Auth){
		$read_data = Forum_Marker_GetReadData();
		if($where <> ''){
			$topics = $db->Select('forum_topics', $where);
		}else{
			$topics =  Forum_Cache_AllDataTableForumTopics();
		}
		
		$del_where = '';
		$insert_values = array();
		$time = time();
		foreach($topics as $topic){
			if(!isset($read_data[$topic['id']]) || $read_data[$topic['id']]['date'] < $topic['last_post']){
				// Не прочитана
				$tid = SafeDB($topic['id'],11,int);
				$del_where .= "(`tid`='$tid' and `mid`= '$u_id') or ";
				$insert_values[] = "'$u_id','$tid','$time'";
			}
		}
		$del_where = substr($del_where, 0, -4);
		if($del_where != ''){
			$db->Delete('forum_topics_read', $del_where);
		}
		if(count($insert_values) > 0){
			foreach($insert_values as $vals){
				$db->Insert('forum_topics_read', $vals);
			}
		}
	}
	GO(GetSiteUrl().Ufu('index.php?name=forum'.(isset($forum_id) ? '&op=showforum&forum='.$forum_id : ''), 'forum/'.(isset($forum_id) ? '{forum}/' : '')));
}
