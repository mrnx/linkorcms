<?php

function IndexForumLastTopics(){
	global $db, $config, $site, $user, $lang, $UFU, $forum_lib_dir;

	include_once($forum_lib_dir.'forum_render_topics.php');
	include_once($forum_lib_dir.'forum_last_topics.php');
	$gforum = -1;

	$user_id = $user->Get('u_id');

	if(isset($_GET['page'])) {
		$page = SafeEnv($_GET['page'], 11, int);
	}else{
		$page = 1;
	}
	if(!$UFU){
		$forum_nav_url = 'index.php?name=forum&op=lasttopics';
	}else{
		$forum_nav_url = 'forum/lasttopics';
	}
	$topics_on_page = $config['forum']['topics_on_page'];

	if(isset($_GET['forum'])){
		$gforum = SafeEnv($_GET['forum'], 11, int);
		if($gforum > 0){
			if(!$UFU){
				$forum_nav_url .= '&forum='.$gforum;
			}else{
				$forum_nav_url .= '/'.$gforum. '/';
			}
		}
	}
	$str_time = $lang['za'].' '.$lang['day'][1];
	$day = 1;
	if(isset($_GET['day'])){
		$wtime = Forum_Last_Inttotime(SafeEnv($_GET['day'], 3, int));
		$str_time =  SafeEnv($_GET['day'], 3, str);
		if(!$UFU){
			$forum_nav_url .= '&day='.$str_time;
		}else{
			$forum_nav_url .= SafeEnv($_GET['day'], 3, int);
		}
		if(strlen($str_time) > 1){
			$str_time = $lang['za'].$str_time.' '.$lang['day'][$str_time[strlen($str_time)-1]];
		}else{
			if($str_time == ''){
				$str_time = 1;
			}
			$str_time = $lang['za'].$str_time.' '.$lang['day'][$str_time];
		}
	}else{
		$forum_nav_url .=  1;
		$wtime = Forum_Last_Inttotime();
	}

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
			// Выводим доступные темы
			Forum_Render_FilterTopics($forum, $alltopics, $statistics, $topics );
		}

		if(is_array($topics)){
			$count_topics = count($topics);
			if($UFU){
				Navigation_AppLink($lang['forum'], 'forum');
				if($mtitle0['id']<>'')
					Navigation_AppLink($mtitle0['title'], 'forum/'.$mtitle0['id']);
				if($mtitle['id']<>'')
					Navigation_AppLink($mtitle['title'], 'forum/'.$mtitle['id']);
				Navigation_AppLink($lang['lasttopicstitle'].$str_time . '&nbsp;['.$count_topics.']',  $forum_nav_url );
			}else{
				Navigation_AppLink($lang['forum'], 'index.php?name=forum');
				Navigation_AppLink($lang['lasttopicstitle']. '&nbsp;['.$count_topics.']' ,  $forum_nav_url );
			}
			Navigation_ShowNavMenu();
		}

		if(count($topics) > $topics_on_page){
			if($UFU){
				$forum_nav_url .= '-{page}';
			}
			$navigation = new Navigation($page);
			$navigation->FrendlyUrl = $UFU;
			$navigation->GenNavigationMenu($topics, $topics_on_page, $forum_nav_url);
			$mtopics['navigation'] = $site->Blocks['navigation'];
		}else{
			$site->AddBlock('navigation',false, false);
		}
		Forum_Render_Topics($forum, $topics, $read_data, false, $page );
		$site->AddBlock('topic_form', false, false, 'form');
		$site->AddBlock('topic_right', false, false, 'topic');
	}else{
		$site->AddTextBox($lang['error'], $lang['error_no_forum']);
	}
	$cat = 0;

	$site->AddTextBox('', Forum_Last_Combo($gforum).'<span style="float:right;">'.$lang['quick_transition'].':&nbsp;'. Navigation_GetForumCategoryComboBox($cat).'</span>');
	$statistics->Render('forum_topics_statistics');
	$c_u = Online_GetCountUser(-1);
	$online_user = $c_u['users'];
	Forum_Online_Render_Online($online_user, $lang['all_online'], 'forum_topics_online');
	$site->AddBlock('old', false, false, 'mark');
}

?>