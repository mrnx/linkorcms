<?php

function IndexForumViewNoRead(){
	global $db, $config, $site, $user, $lang, $UFU, $forum_lib_dir;
if(isset($_GET['page'])) {
		$page = SafeEnv($_GET['page'], 11, int);
	}else {
		$page = 1;
	}
	if(!$UFU){
		$forum_nav_url = 'index.php?name=forum&op=viewnoread';
	}else{
		$forum_nav_url = 'forum/viewnoread-{page}';
	}

	if($user->Auth){
		$user_id = $user->Get('u_id');
		$topics_on_page = $config['forum']['topics_on_page'];
		include_once($forum_lib_dir.'forum_render_topics.php');

		$statistics = ForumStatistics::Instance();
		$statistics->Initialize($lang['statistics_cat']);

		if(isset($_GET['forum'])){
			$gforum = SafeEnv($_GET['forum'], 11, int);
			if($gforum>0)
				if(!$UFU) {
					$forum_nav_url .= '&forum='.$gforum;
				}else{
					$forum_nav_url .= '/'.$gforum;
				}
		}else{
			$gforum = -1;
		}

		$your_where = "`delete`='0'";
		if($user->isAdmin() ||  $config['forum']['basket'] == false){
			$your_where = '';
		}

		$forum = array();
		$forum_parent = array();
		// Загружаем форумы
		$mforums = Forum_Cache_AllDataTableForum();
		//-----------
		$mtitle0 = array();
		$mtitle0['title'] ='';
		$mtitle0['id'] = '';
		//-----------
		$mtitle = array();
		$mtitle['title'] ='';
		$mtitle['id'] = '';

		if(count($mforums) > 0){
			foreach($mforums as $mforum){
				if($gforum < 0 || $gforum == $mforum['id']){
					$forum[$mforum['id']] = $mforum;
				}else{
					$forums[$mforum['id']] = $mforum;
				}
				if($gforum > 0 && $gforum == $mforum['id']){
					$mtitle['title'] = $mforum['title'];
					$mtitle['id'] = $mforum['id'];
				}
			}
		}

		if(count($forum) > 0 ){
			if($gforum>0 && $mtitle['id']<>''){
				if($forum[$mtitle['id']]['parent_id'] >0){
					$mtitle0['id'] =  $forum[$mtitle['id']]['parent_id'];
					$mtitle0['title'] = $forums[$mtitle0['id']]['title'];
				}
			}
			$topics = array();
			$topics_stick = array();
			$allnoreadtopics = array();
			$alltopics = Forum_Cache_AllDataTableForumTopics();
			if(count($alltopics)>0){
				$read_data = Forum_Marker_GetReadData();
				foreach($alltopics as $alltopic ) {
					if(!isset($read_data[$alltopic['id']]) || $read_data[$alltopic['id']]['date'] < $alltopic['last_post']){
						if($user->isAdmin() || $alltopic['delete'] == 0){
							$allnoreadtopics[] = $alltopic;
						}
					}
				}
				// Выводим темы
				Forum_Render_FilterTopics($forum, $allnoreadtopics, $statistics, $topics );
			}
			if(is_array($topics)){
				$count_topics = count($topics);
				if($UFU){
					Navigation_AppLink($lang['forum'], 'forum');
					if($mtitle0['id']<>''){
						Navigation_AppLink($mtitle0['title'], 'forum/'.$mtitle0['id']);
					}
					if($mtitle['id']<>''){
						Navigation_AppLink($mtitle['title'], 'forum/'.$mtitle['id']);
					}
					Navigation_AppLink($lang['viewnoreadtitle'].'&nbsp;['.$count_topics.']', $forum_nav_url);
				}else{
					Navigation_AppLink($lang['forum'], 'index.php?name=forum');
					Navigation_AppLink($lang['viewnoreadtitle'].'&nbsp;['.$count_topics.']' , $forum_nav_url);
				}
				Navigation_ShowNavMenu();
			}

			if(count($topics) > $topics_on_page){
				$navigation = new Navigation($page);
				$navigation->FrendlyUrl = true;
				$navigation->GenNavigationMenu($topics, $topics_on_page, $forum_nav_url);
				$mtopics['navigation'] = $site->Blocks['navigation'];
			}
			else {
				$site->AddBlock('navigation',false, false);
			}

			Forum_Render_Topics($forum, $topics, $read_data, false, $page );
			$site->AddBlock('topic_form', false, false, 'form');
			$site->AddBlock('topic_right', false, true, 'topic');
			Navigation_ShowNavMenu();
		}else {
			$site->AddTextBox($lang['error'], $lang['error_no_forum'] );
		}

		$cat = 0;

		$statistics->Render('forum_topics_statistics');

		$c_u = Online_GetCountUser(-1);
		$online_user = $c_u['users'];
		Forum_Online_Render_Online($online_user, $lang['all_online'], 'forum_topics_online');

		$site->AddTextBox('', '<span style="float:right;">'.$lang['quick_transition'].':&nbsp;'. Navigation_GetForumCategoryComboBox($cat).'</span>');
		$site->AddBlock('old', false, false, 'mark');

	}else{ // Пользователь не авторизован
		$site->AddTextBox($lang['error'], $lang['error_auth']);
	}
}

?>