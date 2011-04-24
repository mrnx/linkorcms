<?php

// LinkorCMS
// LinkorCMS Development Group
// www.linkorcms.ru
// Лицензия LinkorCMS 1.3.

global  $config, $db, $user;

// Автоочистка корзины
if(isset($config['forum']['basket'])){
	if($config['forum']['clear_basket_day'] < 1){
		$config['forum']['clear_basket_day'] = 1;
	}
	if($config['forum']['del_auto_time'] < time()){
		$clear_cache = false;

		$db->Select('config_groups', "`name`='forum'");
		$group = $db->FetchRow();
		$m_time = time() + Day2Sec;
		$m_group = SafeEnv($group['id'], 11, int);
		$delete_date = time() - (Day2Sec * $config['forum']['clear_basket_day']);
		$db->Update('config', "`value`='$m_time'","`name`='del_auto_time' and `group_id`='$m_group'");

		$mdb = array();
		$mdb = $db->Select('forum_basket_topics', "`date`<'$delete_date'");
		if(count($mdb)>0){
			$clear_cache = true;
			foreach($mdb as $d_topic){
				ForumAdminDeleteTopic(SafeDb($d_topic['obj_id'], 11, int));
			}
		}
		$db->Delete('forum_basket_topics', "`date`<'$delete_date'");

		$mdb = array();
		$mdb = $db->Select('forum_basket_post', "`date`<'$delete_date'");
		if(count($mdb)>0){
			$clear_cache = true;
			foreach($mdb as $d_post){
				ForumAdminDeletePost(SafeDb($d_post['obj_id'], 11, int));
			}
		}
		$db->Delete('forum_basket_post', "`date`<'$delete_date'");

		if($clear_cache){
			Forum_Cache_ClearAllCacheForum();
		}
	}
}

?>