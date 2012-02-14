<?php
# LinkorCMS
# LinkorCMS Development Group
# www.linkorcms.ru
# Лицензия LinkorCMS 1.2.
# Подписка на уведомления изменений в теме

/*
 * Одна функция и для подписки и для отписки
 */
function Forum_Subscription( $Topic = 0, $UserId = 0 ){
	global $user;
	if($UserId == 0){
		$UserId = $user->Get('u_id');
	}
	if(Forum_Subscription_Status($Topic, $UserId)){
		Forum_Subscription_Delete($Topic, $UserId);
	}else{
		Forum_Subscription_Add($Topic, $UserId);
	}
}

function Forum_Subscription_Add( $topic = 0, $user_id = 0 ){
	global $db, $user;
	if($user_id == 0){
		$user_id = $user->Get('u_id');
	}
	$vals = "'','$topic','$user_id'";
	$db->Insert('forum_subscription', $vals);
}

function Forum_Subscription_Delete( $topic = 0, $user_id = 0 ){
	global $db;
	$where = "`topic`='$topic'";
	if($user_id > 0){
		$where .=  " and `user`='$user_id'";
	}
	$db->Delete('forum_subscription', $where);
}

function Forum_Subscription_Status( $Topic, $UserId = 0 ){
	global $db, $user;
	if($UserId == 0){
		$UserId = $user->Get('u_id');
	}
	$db->Select('forum_subscription', "`topic`='$Topic' and `user`='$UserId'");
	return ($db->NumRows() > 0);
}

function Forum_Subscription_Get_User( $topic = 0, $full = true, $ignore_user_id = 0 ){
	global $db;
	$users = array();
	if($topic>0){
		$mdb = $db->Select('forum_subscription', "`topic`='$topic'");
		if($db->NumRows() > 0) {
			foreach($mdb as $m_user){
				$usr = SafeDb($m_user['user'], 11, int);
				if($usr <> $ignore_user_id){
					if($full){
						$usr = GetUserInfo(SafeDb($m_user['user'], 11, int));
					}
					$users[] =$usr;
				}
			}
		}
	}
	$users = array_unique($users);
	return $users;
}

function Forum_Subscription_Send_Email($users, $topic_id, $name, $title) {
	global $config, $forum_lang;
	$link = $config['general']['site_url'].'/index.php?name=forum&op=showtopic&topic='.$topic_id.'&view=lastpost';
	$link_delete = $config['general']['site_url'].'index.php?name=forum&op=subscription&a=delete&topic='.$topic_id;
	$Text = $forum_lang['hello'];
	$Text .= $name.$forum_lang['add_message'].$title.$forum_lang['last_subscription'];
	$Text .= "\r\n";
	$Text .= $forum_lang['view_message'];
	$Text .= $link."\r\n";
	$Text .= $forum_lang['delete_subscription'];
	$Text .= $link_delete."\r\n";
	$Text .= $forum_lang['auto_message'];
	$robot = $forum_lang['robot'];
	$robot_email = 'noreply@'.getenv("HTTP_HOST");
	foreach($users as $c_user){
		SendMail($c_user['name'],$c_user['email'],$forum_lang['new_message'].$title,$Text, false, $robot, $robot_email);
	}
}

function Forum_Subscription_Send($topic_id = 0){
	global $db, $user;
	if($topic_id > 0){
		$db->Select('forum_topics', "`id`='$topic_id'");
		if($db->NumRows() > 0){
			$topic = $db->FetchRow();
			$title = Safedb($topic['title'],  75, str);
			$users = Forum_Subscription_Get_User($topic_id, true, $user->Get('u_id'));
			if(count($users) > 0){
				Forum_Subscription_Send_Email($users, $topic_id, $user->Get('u_name'), $title);
			}
		}
	}
}
