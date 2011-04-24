<?php

# LinkorCMS
# LinkorCMS Development Group
# www.linkorcms.ru
# Лицензия LinkorCMS 1.2.
# работа с рангами пользвателей

# Статусное сообщение по правам в разделе
# и права и доступ по рангу
# return array

function Rang_UserStatus( $forum = null ) {
	global $user, $lang;

	// Доступ по рангу
	$rang = array();
	$rang['rang_access'] = Rang_GetUsersRang($forum['rang_access']);
	
	// Создание сообщений по рангу
	$rang['rang_message'] = Rang_GetUsersRang($forum['rang_message']);
	
	// Создание тем по рангу
	$rang['rang_add_theme'] = Rang_GetUsersRang($forum['rang_add_theme']);
	$rang['no_link_guest'] = ($forum['no_link_guest']==1 && !$user->Auth);
	$rang['right'] = '';
	$rang['is_theme_add'] = (!$forum['admin_theme_add'] && $rang['rang_add_theme'] && $user->Get('u_add_forum') ? true : false) || $user->IsAdmin();

	$remark = '';
	$rang['close_topic'] = $forum['close_topic'];
	if($forum['close_topic'] == 1){
		$rang['is_theme_add'] = false;
		$rang['rang_add_theme'] = false;
		$rang['rang_message'] = false;
		$remark = $lang['topic_close_for_discussion'];
	}else{
		if(!$user->Get('u_add_forum')){
			$remark = $lang['error_blocking'];
		}
	}

	if($forum['admin_theme_add'] == 1 && $forum['close_topic'] == 0){
		$remark .= $lang['create_new_topics_admin'];
	}


	if($rang['is_theme_add'] && $user->Auth && $user->Get('u_add_forum')){
		$rang['right'] .= $lang['create_new_topics'].'<br />';
	}else{
		$rang['right'] .= $lang['no_create_new_topics'].'<br />';
	}
	if($user->Auth and $rang['rang_message'] && $user->Auth && $user->Get('u_add_forum')){
		$rang['right'] .= $lang['create_new_message_in_topics'].'<br />'.$remark;
	}else{
		$rang['right'] .= $lang['no_create_new_message_in_topics'].'<br />'.$remark;
	}

	return $rang;
}

# Статус
function Rang_RangUserTopic($rang, $topic) {
	global $user, $lang;
	$rang2=array();

	$rang2['is_theme_add'] = $topic['close_topics']==1;
	$remark='';
	if($rang2['is_theme_add']){
		$rang2['is_theme_add'] = false;
		$rang2['rang_add_theme'] = false;
		$rang2['rang_message'] = false;
		$remark = '&nbsp;'.$lang['topic_close_for_discussion'];
		$rang2['right'] = '';
		if($topic['close_topics'] == 0){
			$rang2['right'] .= (
				($rang2['is_theme_add'] && $user->Auth && $user->Get('u_add_forum'))
				? $lang['create_new_message_in_topics']
					: $lang['no_create_new_message_in_topics'])
			.'<BR>';
		}
		$rang2['right'].=(($user->Auth and $rang2['rang_message'])?$lang['create_new_message_in_topics']:$lang['no_create_new_message_current_topic']).'<BR>'.$remark;
		return $rang2;
	}
	else {
		return  $rang;
	}
}


# Комбобокс  "ранг пользователей"
function ForumAdminGetUsersTypesComboBox($group='',$rank=0) {
    global $config, $db, $site, $lang;
	$mdb = $db->Select('userranks');
	SortArray($mdb, 'min', false);
	$types = array(array('id' => '0', 'title' => $lang['all_rang'], 'select' => false));
	foreach ($mdb as $type) {
		if ($type['id'] > 0)
			$types[$type['id']] = array('id' => $type['id'], 'title' => $type['title'], 'select' => ($rank == $type['id'] ? true : false));
	}
	$usertypes = array();
	foreach ($types as $type) {
		$site->DataAdd($usertypes, $type['id'], $type['title'], $type['select']);
	}
	return $site->Select($group, $usertypes);
}

# Доступ по "рангу пользователей"
# return boolean
function Rang_GetUsersRang( $rang_access = 0 ) {
	global $db, $user;
	$result = true;
	if($rang_access > 0 and !$user->IsAdmin()) {
		if($user->Auth){
			static $curentuserranks = null;
			static $userranks = null;
			$user_id = $user->Get('u_id');
			$u_points = $user->Get('u_points');
			if($userranks == null){
				$cache = LmFileCache::Instance();
				if($cache->HasCache('forum', 'Rang_GetUsersRang')){
					$userranks = $cache->Get('forum', 'Rang_GetUsersRang');
				}else{
					$ranks = $db->Select('userranks');
					foreach($ranks as $userr){
						$userranks[$userr['id']] = $userr['min'];
					}
					$cache->Write('forum', 'Rang_GetUsersRang', $userranks, Day2Sec);
				}
			}
			if($userranks[$rang_access] > $u_points){
				$result = false;
			}
		}else{
			$result = false;
		}
	}
	return $result;
}

?>