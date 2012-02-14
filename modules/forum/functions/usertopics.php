<?php

function IndexForumUserTopics(){
	global $db, $config, $site, $user, $forum_lang, $forum_lib_dir;
	if(isset($_GET['user'])){
		include_once($forum_lib_dir.'forum_render_topics.php');
		$user_id = SafeEnv($_GET['user'], 11, int);

		// Навигация
		if(isset($_GET['page'])) {
			$page = SafeEnv($_GET['page'], 11, int);
		}else {
			$page = 1;
		}
		$topics_on_page = $config['forum']['topics_on_page'];
		$forum_nav_url = Ufu('index.php?name=forum&op=usertopics&user='.$user_id, 'forum/usertopics/{user}-{page}/', true);
		$navigation = new Navigation($page);
		$navigation->FrendlyUrl = $config['general']['ufu'];
		//

		if($config['forum']['cache']){
			$cache = LmFileCache::Instance();
			$cache_group = 'forum';
			$cache_key = 'IndexForumShowTopicUser_acc'.$user->AccessLevel().'_page'.$page.'_user'.$user_id;
			if(!$user->Auth && $cache->HasCache($cache_group, $cache_key)){
				$site = $cache->Get($cache_group, $cache_key);
				return true;
			}
			$cache_topics_key = 'UserTopics_user'.$user_id;
		}

		$forum = array();
		$forum_parent = array();
		$current_forum = false;
		$current_forum_id = '';

		$mforums = Forum_Cache_AllDataTableForum();
		foreach($mforums as $mforum){
			$forum[$mforum['id']] = $mforum;
		}

		// Если указать форум то мы увидим созданные темы в конкретном форуме
		if(isset($_GET['forum']) && $_GET['forum'] <> ''){
			$current_forum_id = SafeEnv($_GET['forum'], 11, int);
			$your_where = " and `forum_id`='".$current_forum_id."'";
			$current_forum = true;
		}else{
			$your_where = '';
		}

		if(count($forum) > 0){
			// Выводим темы
			$topics = array();
			$topics_stick = array();

			if($config['forum']['cache'] && $cache->HasCache('forum', $cache_topics_key)){
				$topics = $cache->Get('forum', $cache_topics_key);
			}else{
				$topics = $db->Select('forum_topics', "`starter_id`='$user_id'".$your_where);
				if($config['forum']['cache']){
					$cache->Write('forum', $cache_topics_key, $topics, $config['forum']['maxi_cache_duration']);
				}
			}

			$statistics = ForumStatistics::Instance();
			$statistics->Initialize($forum_lang['statistics_cat']);

			$count_topics = count($topics);
			if(count($topics) > 0){
				Forum_Render_FilterTopics($forum, $topics, $statistics, $topics );
			}

			$starter_name = Forum_Online_Get_User_Info($user_id);
			if($starter_name == $forum_lang['guest']){
				if(isset($topics[0])){
					$starter_name = $topics[0]['starter_name'];
				}
			}

			Navigation_AppLink($forum_lang['forum'], Ufu('index.php?name=forum', 'forum/'));
			Navigation_AppLink($forum_lang['usertopics']. $starter_name. '&nbsp;['.$count_topics.']', Ufu('index.php?name=forum&op=usertopics&user='.$user_id, 'forum/usertopics/{user}/'));
			Navigation_ShowNavMenu();

			$navigation->GenNavigationMenu($topics, $topics_on_page, $forum_nav_url);

			$read_data = Forum_Marker_GetReadData();
			Forum_Render_Topics($forum, $topics, $read_data, false, $page);
			$site->AddBlock('topic_form', false, false, 'form');
			$site->AddBlock('topic_right', false, true, 'topic');
			Navigation_ShowNavMenu();
		}else{
			$site->AddTextBox($forum_lang['error'], $forum_lang['error_no_forum']);
		}

		$cat = 0;
		$statistics->Render();
		$c_u = Online_GetCountUser(-1);
		$online_user = $c_u['users'];
		Forum_Online_Render_Online($online_user, $forum_lang['all_online']) ;

		$site->AddTextBox('', '<span style="float:right;">'.$forum_lang['quick_transition'].':&nbsp;'. Navigation_GetForumCategoryComboBox($cat).'</span>');
		$site->AddBlock('old', false, false, 'mark');

		if($config['forum']['cache']){
			if(!$user->Auth){
				$cache->Write('forum', $cache_key, $site);
			}
		}
	}else{
		$site->AddTextBox($forum_lang['error'], $forum_lang['error_no_forum']);
	}
}

