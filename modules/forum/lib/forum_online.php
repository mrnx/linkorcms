<?php

# LinkorCMS
# LinkorCMS Development Group
# www.linkorcms.ru
# Лицензия LinkorCMS 1.2.
# кто на форуме - статистика
# точность -1 для гостя который открывает страницу первый раз
# из за того что данные берутся из системной таблицы в которую запись идёт в самом конце работы системы
# Муратов Вячеслав (smilesoft@yandex.ru)


function Online_Forum(&$u) {
	global $UFU;
	if($UFU) {
		$pos = strpos($u['p_url'] , 'forum');
		if($pos<>false) {
			$pos = strpos($u['p_url'] , '/topic');
			if($pos<>false) {
				$t = substr($u['p_url'], $pos+6);
				$pos = strpos($t , '-new');
				if($pos<>false)
					$t = substr($t, 0, $pos);
				$pos = strpos($t , '.html');
				if($pos<>false)
					$t = substr($t, 0, $pos);
				$u['p_url'] = ' &topic='.$t;
				return true;
			}
			$a = array();
			$a = explode('/',$u['p_url']);
			if(count($a)>3) {
				$u['p_url'] = ' &cat='.$a[count($a)-1];
				return true;
			}else {
				$u['p_url'] = ' &forum='.$a[count($a)-1];
				return true;
			}
		}
	}

	if(strpos($u['p_url'],'name=forum') == true) {
		return true;
	}
	else {
		return false;
	}
}


# Сколько в разделах пользователей  - обработка
function Online_GetOnlineUser(&$u, &$online) {

	$add = false;
	$pos = strpos($u['p_url'] , '&forum=');
	if($pos<>false) {
		$f = substr($u['p_url'], $pos+7);
		$pos = strpos($f , '&');
		if($pos<>false)
			$f = substr($f, 0, $pos);
		$online['forum'][trim($f)][] = $u;
		return;
	}

	$pos = strpos($u['p_url'] , '&cat=');
	if($pos<>false) {
		$t = substr($u['p_url'], $pos+5);
		$pos = strpos($t , '&');
		if($pos<>false)
			$t = substr($t, 0, $pos);
		$online['forum'][trim($t)][] = $u;
		return;
	}
	$pos = strpos($u['p_url'] , '&topic=');
	if($pos<>false) {
		$t = substr($u['p_url'], $pos+7);
		$pos = strpos($t , '&');
		if($pos<>false)
			$t = substr($t, 0, $pos);
		$online['topic'][trim($t)][] = $u;
		return;
	}
	$online['forum'][0][] = $u;
}


# Сколько пользователей на форуме
# id - раздел , форум, топик
# root - корень форума, раздела	?
# only_topic - только те кто в теме ?
# only_int_all - только число тех кто на всём форуме 
function Online_GetCountUser($id, $root = true, $only_topic = false, $only_int_all = false) {
	global $user, $db, $lang;
	static $online = array();
	static $topics = array();
	static $all = 0;
	static $forums = array();
	static $yes = false;
	static $all_forum = array();

	if($only_int_all ) {
		$mall = $all;
		if($mall == 0) $mall = 1;
		$results = array();
		$results['users'] = '';
		$results['users']['count'] =  $mall;
		return 	$results;
	}

	$u_id = $user->Get('u_id');
	$u = array();
	if(!$yes) {
		$ip = getip()  ;
		$auth = $user->Auth;
		$info = $user->Online();
		$online = array();
		$online['forum'] = array();
		$online['topic']= array();
		$add = false;
		foreach($info['admins'] as $u) {
			if(Online_Forum($u)) {
				if($u['u_id'] == $u_id) continue;
				Online_GetOnlineUser($u, $online);
				$all++;
				$all_forum[]=$u;
			}
		}
		foreach($info['members'] as $u)
			if(Online_Forum($u)) {
				if($u['u_id'] == $u_id) continue;
				Online_GetOnlineUser($u, $online);
				$all++;
				$all_forum[]=$u['u_id'];
			}
		foreach($info['guests'] as $u) {
			if(Online_Forum($u)) {
				if($u['u_ip'] <> $ip)
					Online_GetOnlineUser($u, $online);
				$all++;
				$all_forum[]=$u;
			}
		}
	  $current_user = array('u_id'=>$user->Get('u_id'), 'u_name'=>$user->Get('u_name'));

		# коректировка для авторизованного пользователя
		if( $user->Auth)
			$all_forum[] = $current_user;

		if(count( $all_forum) > $all)
			$all = count($all_forum);

		$add = false;

		# коректировка для авторизованного пользователя
		if($user->Auth){
			if(isset($_GET['topic'])){
				if(!isset($online['topic'][$_GET['topic']])){
					$online['topic'][$_GET['topic']][] = $current_user ;
				}else{
					$online['topic'][$_GET['topic']][] = $current_user ;
				}

			}elseif(isset($_GET['cat'])){
				if(isset( $online['forum'][$_GET['cat']])){
					$online['forum'][$_GET['cat']][] = $current_user	 ;
				}else{
					$online['forum'][$_GET['cat']][] = $current_user;
				}
			}elseif(isset($_GET['forum'])){
				if(isset( $online['forum'][$_GET['forum']])){
					$online['forum'][$_GET['forum']][] = $current_user;
				}else{
					$online['forum'][$_GET['forum']][] = $current_user	;
				}
			}
		}
		if(isset($_GET['topic'])){
			if(!isset( $online['topic'][$_GET['topic']])){
				$online['topic'][$_GET['topic']][] = $current_user;
			}

		}elseif(isset($_GET['forum']) ) {
			if(!isset( $online['forum'][$_GET['forum']])){
				$online['forum'][$_GET['forum']][] = $current_user;
			}
		}elseif(isset($_GET['cat']) ) {
			if(!isset( $online['forum'][$_GET['cat']])){
				$online['forum'][$_GET['cat']][] = $current_user;
			}
		}

		/*только те кто в теме*/
		if($only_topic){
			$results = array();
			$results['users'] = '';
			$count = 0;
			if(isset($online['topic'][$id])){
				$results['users']['reg'] = Forum_Online_Get_User($online['topic'][$id]);
				$count = count($online['topic'][$id]);
			}
			if($count>0 and $id>-1){
				$results['users']['count'] = $count;
				$results['count'] ='<FONT SIZE="1"> ('.$lang['online'].': '.$count.')</FONT>';
				return  $results;
			}

		}
		$result = array();
		$result = Forum_Cache_AllDataTableForumTopics();
		$topics = array();
		$topics_id = array();
		$where_f = '';
		/*сколько в общем в каждом топике*/
		foreach($result as $topic){
			if(isset($online['topic'][$topic['id']])){
				foreach($online['topic'][$topic['id']] as $user_id){
					$topics[$topic['forum_id']][$topic['id']][] =$user_id;
					$topics_id[$topic['forum_id']][] = $user_id;
				}
			}
		}

		$result = Forum_Cache_AllDataTableForum();
		$forums = array();
		/*сколько в общем в каждой категории*/
		foreach($result as $mforum){
			if(isset($topics_id[$mforum['id']])){
				if(isset($forums[$mforum['parent_id']])){
					$forums[$mforum['parent_id']] = $forums[$mforum['parent_id']]+count($topics_id[$mforum['id']]);
				}else{
					$forums[$mforum['parent_id']] = count($topics_id[$mforum['id']]);
				}
			}
			if(isset($online['forum']) and isset($online['forum'][$mforum['id']])){
				if(isset($forums[$mforum['parent_id']])){
					$forums[$mforum['parent_id']] = $forums[$mforum['parent_id']]+count($online['forum'][$mforum['id']]);
				}else{
					$forums[$mforum['parent_id']] = count($online['forum'][$mforum['id']]);
				}
			}
		}
		$yes = true;
	}



	$results = array();
	$results['users'] = '';
	$count = 0;
	if($root){
		if(isset($forums) and isset($forums[$id])){
			$count = $forums[$id];
		}
		if(isset($online['forum']) and isset($online['forum'][$id])){
			$results['users']['reg'] = Forum_Online_Get_User($online['forum'][$id]);
			$count = $count + count($online['forum'][$id]);
		}
		if(isset($topics[$id])) {
			$count = $count+count($topics[$id]);
			if( isset($online['topic'][$id]))
				$results['users']['reg'] = Forum_Online_Get_User($online['topic'][$id]);
		}
	}else{
		if(isset($online['topic'][$id])) {
			$results['users']['reg'] = Forum_Online_Get_User($online['topic'][$id]);
			$count = $count+count($online['topic'][$id]);
		}
	}

	if($count > 0 and $id > -1){
		$results['users']['count'] = $count;
		$results['count'] ='<FONT SIZE="1"> ('.$lang['online'].': '.$count.')</FONT>';
		return  $results;
	}else{
		if($id == -1){
			if($all == 0) $all = 1;
			$results['users']['reg'] = Forum_Online_Get_User($all_forum);
			$results['users']['count'] = $all;
			$results['count'] = '<FONT SIZE="1"> ('.$lang['online'].': '.$all.')</FONT>';
			return  $results;
		}
	}
}


/*
 * Врзвращает имя пользователя по его id(кешируется)
 */
function Forum_Online_Get_User_Info( $user_id ) {
	global $db,  $lang;
	static $users = null;
	if($users == null){
		$cache = LmFileCache::Instance();
		if($cache->HasCache('forum', 'users_name')){
			$users = $cache->Get('forum', 'users_name');
		}else{
			$db->Select('users','');
			$users = array();
			foreach($db->QueryResult as $usr) {
				$users[$usr['id']] = $usr['name'];
			}
			$cache->Write('forum', 'users_name', $users, 600);
		}
	}
	if(isset($users[$user_id])){
		return $users[$user_id];
	}else{
		return $lang['guest'];
	}
}


function Forum_Online_Get_User(&$users , $full = true) {
	global $db, $use, $users_reg2, $UFU;

	$sus_user_info = false;
	if(isset($_GET['op']) and $_GET['op'] == 'showtopic')
		$sus_user_info = true;
	$users_str = '';
	$users_reg = array();
	$u_id=-1;
	if($users > 0){
		foreach($users as $m_user){
			$usr =	$m_user;
			if( $usr>0 ){
				if($full){
					if($sus_user_info){
						$usr = $m_user['u_name'];	

					}else{
						$usr =  $m_user['u_name'];
					}
					$user_online = (!$UFU?'<a href="index.php?name=user&amp;op=userinfo&amp;user=' .$m_user. '">' . $usr . '</a>': '<a href="user/'.$m_user. '">'.$usr.'</a>');

					$users_reg[] = $user_online.'&nbsp;';
				}
				else {
					$users_reg[] = $m_user;
				}
			}
		}
	}
	return $users_reg ;
}

function  Forum_Online_Render_Online($users = array(), $title='', $block='forum_online') {
	global $site, $lang;
	if(count($users)>0){
		$site->AddBlock($block, true, false, $block, 'module/forum_online.html');
		$site->AddBlock('gen_online', true, false, 'users');
		$vars_online= array();
		$vars_online['count'] = $users['count'];
		$vars_online['online_img'] = true;
		$vars_online['title'] = $title;
		$vars_online['reg'] = count($users['reg']);
		$vars_online['guest'] = ($users['count'] - count($users['reg'])>0?$users['count'] - count($users['reg']):0);
		$site->Blocks['gen_online']['vars'] = $vars_online;
		$site->AddBlock('onlines', true,true, 'user');
		$i = 0;
		$vars_online= array();
		foreach($users['reg'] as $reg_user) {
			$i++;
			$vars_online['url'] = $reg_user;
			$site->AddSubBlock('onlines',true,$vars_online);
			if($i>250) break;
		}
		if($title<>$lang['all_online']) {
			$c_u = Online_GetCountUser(-1,true,false, true);
			$vars_online['url'] = '<BR>всего на форуме: '.$c_u['users']['count'] ;
			$site->AddSubBlock('onlines',true,$vars_online);

		}
	}
	else {
		$site->AddBlock('forum_online',false,false);
	}

}
?>