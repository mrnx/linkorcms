<?php

function IndexForumViewNoRead(){
	global $db, $config, $site, $user, $forum_lang, $forum_lib_dir;
	if(isset($_GET['page'])) {
		$page = SafeEnv($_GET['page'], 11, int);
	}else {
		$page = 1;
	}

	if($user->Auth){
		$user_id = $user->Get('u_id');
		$topics_on_page = $config['forum']['topics_on_page'];
		include_once($forum_lib_dir.'forum_render_topics.php');

		$statistics = ForumStatistics::Instance();
		$statistics->Initialize($forum_lang['statistics_cat']);

		if(isset($_GET['forum'])){
			$gforum = SafeEnv($_GET['forum'], 11, int);
		}else{
			$gforum = -1;
		}
		$forum_nav_url = Ufu('index.php?name=forum&op=viewnoread'.($gforum>0 ? '&forum='.$gforum : ''), 'forum/viewnoread-{page}/'.($gforum>0 ? '{forum}/' : ''), true);

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
				if($forum[$mtitle['id']]['parent_id'] > 0){
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
				Forum_Render_FilterTopics($forum, $allnoreadtopics, $statistics, $topics);
			}

			// Добавляем хлебные крошки
			$mtitle['id'] = SafeDB($mtitle['id'], 11, int);
			$mtitle['title'] = SafeDB($mtitle['title'], 255, str);
			$mtitle0['id'] = SafeDB($mtitle0['id'], 11, int);
			$mtitle0['title'] = SafeDB($mtitle0['title'], 255, str);
			Navigation_AppLink($forum_lang['forum'], Ufu('index.php?name=forum', 'forum/'));
			if($mtitle0['id']>0){
				Navigation_AppLink($mtitle0['title'], Ufu('index.php?name=forum&op=showforum&forum='.$mtitle0['id'], 'forum/{forum}/'));
			}
			if($mtitle['id']>0){
				Navigation_AppLink($mtitle['title'], Ufu('index.php?name=forum&op=showforum&forum='.$mtitle['id'], 'forum/{forum}/'));
			}
			Navigation_AppLink($forum_lang['viewnoreadtitle'].'&nbsp;['.count($topics).']', Ufu('index.php?name=forum&op=viewnoread'.($mtitle['id']>0 ? '&forum='.$mtitle['id'] : ''), 'forum/viewnoread/'.($mtitle['id']>0 ? $mtitle['id'].'/' : '')));
			Navigation_ShowNavMenu();

			// Постраничная навигация
			$navigation = new Navigation($page);
			$navigation->FrendlyUrl = $config['general']['ufu'];
			$navigation->GenNavigationMenu($topics, $topics_on_page, $forum_nav_url);

			Forum_Render_Topics($forum, $topics, $read_data, false, $page );
			$site->AddBlock('topic_form', false, false, 'form');
			$site->AddBlock('topic_right', false, true, 'topic');
			Navigation_ShowNavMenu();
		}else {
			$site->AddTextBox($forum_lang['error'], $forum_lang['error_no_forum'] );
		}
		$statistics->Render('forum_topics_statistics');
		$c_u = Online_GetCountUser(-1);
		$online_user = $c_u['users'];
		Forum_Online_Render_Online($online_user, $forum_lang['all_online'], 'forum_topics_online');
		$site->AddTextBox('', '<span style="float:right;">'.$forum_lang['quick_transition'].':&nbsp;'. Navigation_GetForumCategoryComboBox(0).'</span>');
		$site->AddBlock('old', false, false, 'mark');
	}else{ // Пользователь не авторизован
		$site->AddTextBox($forum_lang['error'], $forum_lang['error_auth']);
	}
}
