<?php

function IndexForumLastTopics(){
	global $db, $config, $site, $user, $lang, $forum_lib_dir;

	include_once($forum_lib_dir.'forum_render_topics.php');
	include_once($forum_lib_dir.'forum_last_topics.php');
	$gforum = -1;

	$user_id = $user->Get('u_id');

	if(isset($_GET['page'])) {
		$page = SafeEnv($_GET['page'], 11, int);
	}else{
		$page = 1;
	}
	$topics_on_page = $config['forum']['topics_on_page'];

	if(isset($_GET['forum'])){
		$gforum = SafeEnv($_GET['forum'], 11, int);
	}

	$str_time = $lang['za'].' '.$lang['day'][1];
	$day = 1;
	if(isset($_GET['day'])){
		$wtime = Forum_Last_Inttotime(SafeEnv($_GET['day'], 3, int));
		$str_time = SafeEnv($_GET['day'], 3, str);
		$url_time = $str_time;
		if(strlen($str_time) > 1){
			$str_time = $lang['za'].$str_time.' '.$lang['day'][$str_time[strlen($str_time)-1]];
		}else{
			if($str_time == ''){
				$str_time = 1;
			}
			$str_time = $lang['za'].$str_time.' '.$lang['day'][$str_time];
		}
	}else{
		$wtime = Forum_Last_Inttotime();
	}
	$forum_url = Ufu(
		'index.php?name=forum&op=lasttopics'.($gforum > 0 ? '&forum='.$gforum : '').(isset($_GET['day']) ? '&day='.$url_time: ''),
		'forum/lasttopics/'.($gforum > 0 ? '{forum}/' : '').(isset($_GET['day']) ? 'day{day}/': '')
	);
	$forum_nav_url = Ufu(
		'index.php?name=forum&op=lasttopics'.($gforum > 0 ? '&forum='.$gforum : '').(isset($_GET['day']) ? '&day='.$url_time: '').'&page='.$page,
		'forum/lasttopics/'.($gforum > 0 ? '{forum}/' : '').(isset($_GET['day']) ? 'day{day}/': '').'page{page}',
		true
	);

	$statistics = ForumStatistics::Instance();
	$statistics->Initialize($lang['statistics_cat']);
	
	$your_where = "`delete`='0'";
	if($user->isAdmin() || $config['forum']['basket'] == false){
		$your_where = '';
	}

	$forum = array();
	$forum_parent = array();
	$mforums = Forum_Cache_AllDataTableForum();
	$mtitle0 = array();
	$mtitle0['title'] ='';
	$mtitle0['id'] = '';
	$mtitle = array();
	$mtitle['title'] ='';
	$mtitle['id'] = '';

	if(count($mforums) > 0){
		foreach($mforums as $mforum){
			if($gforum < 0 or $gforum == $mforum['id']){
				$forum[$mforum['id']] = $mforum;
				$forums[$mforum['id']] = $mforum;
			}else{
				$forums[$mforum['id']] = $mforum;
			}
			if($gforum>0 and $gforum == $mforum['id']){
				$mtitle['title'] = $mforum['title'];
				$mtitle['id'] = $mforum['id'];
			}
		}
	}

	if(count($forum) > 0 ){
		if($gforum > 0 and $mtitle['id']<>''){
			if($forum[$mtitle['id']]['parent_id'] > 0){
				$mtitle0['id'] =  $forum[$mtitle['id']]['parent_id'];
				$mtitle0['title'] = $forums[$mtitle0['id']]['title'];
			}
		}
		if($gforum > -1){
			if(count($mforums) > 0){
				foreach($mforums as $mforum){
					if($mforum['id'] == $gforum || $mforum['parent_id'] == $gforum){
						$forum[$mforum['id']] = $mforum;
						if($mforum['parent_id'] > 0){
							$forum[$mforum['parent_id']] = $forums[$mforum['parent_id']];
						}
					}
				}
			}
		}

		$site->Title .= $lang['site_slas'].$lang['lasttopicstitle'].$str_time;
		$topics = array();
		$topics_stick = array();
		$allnoreadtopics = array();

		$alltopics = $db->Select('forum_topics', $wtime);
		if(count($alltopics)>0){
			$read_data = Forum_Marker_GetReadData();
			Forum_Render_FilterTopics($forum, $alltopics, $statistics, $topics );// Выводим доступные темы
		}

		// Хлебные крошки
		Navigation_AppLink($lang['forum'], Ufu('index.php?name=forum', 'forum/'));
		Navigation_AppLink($lang['lasttopicstitle'].$str_time.'&nbsp;['.count($topics).']' ,  $forum_url);
		Navigation_ShowNavMenu();

		$navigation = new Navigation($page);
		$navigation->FrendlyUrl = $config['general']['ufu'];
		$navigation->GenNavigationMenu($topics, $topics_on_page, $forum_nav_url);

		Forum_Render_Topics($forum, $topics, $read_data, false, $page );
		$site->AddBlock('topic_form', false, false, 'form');
		$site->AddBlock('topic_right', false, false, 'topic');
	}else{
		$site->AddTextBox($lang['error'], $lang['error_no_forum']);
	}
	$site->AddTextBox('', Forum_Last_Combo($gforum).'<span style="float:right;">'.$lang['quick_transition'].':&nbsp;'. Navigation_GetForumCategoryComboBox(0).'</span>');
	$statistics->Render('forum_topics_statistics');
	$c_u = Online_GetCountUser(-1);
	$online_user = $c_u['users'];
	Forum_Online_Render_Online($online_user, $lang['all_online'], 'forum_topics_online');
	$site->AddBlock('old', false, false, 'mark');
}
