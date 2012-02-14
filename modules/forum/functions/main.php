<?php

global $forums2, $topics, $statistics;
$statistics = ForumStatistics::Instance();
$forums2 = array();
$topics = array();
$read_data = array();

// Статистика и пометки о прочтёном/не прочтёном для подфорумов
function IndexForumStats($id){
	global $topics, $read_data, $statistics, $forums2;
	$result = array(
		'topics'=>0,
		'posts'=>0,
		'last'=> array(
			'last_post_date'=>0,
			'last_id'=>0,
			'last_poster_id'=>0,
			'last_poster_name'=>'',
			'last_title'=>''
		)
	);
	foreach($forums2[$id] as $key=>$sub_forum){
		$result['topics'] += $sub_forum['topics'];
		$result['posts'] += $sub_forum['posts'];
		if($sub_forum['last_post_date'] > $result['last']['last_post_date']){
			$result['last']['last_post_date'] = $sub_forum['last_post_date'];
			$result['last']['last_id'] = $sub_forum['last_id'];
			$result['last']['last_poster_id'] = $sub_forum['last_poster_id'];
			$result['last']['last_poster_name'] = SafeDB($sub_forum['last_poster_name'], 250, str);
			$result['last']['last_title'] = SafeDB($sub_forum['last_title'], 250, str);
		}
		$forums2[$id][$key]['read'] = 1;
		$forums2[$id][$key]['read2'] = false;
		if(isset($topics[$sub_forum['id']]) ) {
			foreach($topics[$sub_forum['id']] as $topic2){
				if( isset($read_data[SafeDB($topic2['id'], 11, int)])) {
					if($read_data[SafeDB($topic2['id'], 11, int)]['date'] < SafeDB($topic2['last_post'], 11, int)){
						if( !$forums2[$id][$key]['read2'] )
						$forums2[$id][$key]['read'] = 0;
						$forums2[$id][$key]['read2'] = true;
					}
				}else{
					if(	!$forums2[$id][$key]['read2'] )
					$forums2[$id][$key]['read'] = 0;
					$forums2[$id][$key]['read2'] = true;
				}
				if(!$statistics->stop){
					$statistics->hits += $topic2['hits'];
					$statistics->AddTopicAuthor($topic2['starter_id'], SafeDB($topic2['starter_name'], 250, str));
				}
			}
		}
		if(isset($forums2[$sub_forum['id']])){
			$result2 = IndexForumStats($sub_forum['id']);
			$result['topics'] += $result2['topics'];
			$result['posts'] += $result2['posts'];
			if($result2['last']['last_post_date'] > $result2['last']['last_post_date']){
				$result['last']['last_post_date'] = $result2['last']['last_post_date'];
				$result['last']['last_id'] = $result2['last']['last_id'];
				$result['last']['last_poster_id'] = $result2['last']['last_poster_id'];
				$result['last']['last_poster_name'] = $result2['last']['last_poster_name'];
				$result['last']['last_title'] = $result2['last']['last_title'];
			}
		}
	}
	return $result;
}


// Главная страница форума, список форумов в категории
function IndexForumMain(){
	global $site, $user, $forum_lang, $config, $db, $forums2, $topics, $read_data, $statistics;
	$forums2 = array();
	$topics = array();
	$read_data = array();
	if(isset($_GET['cat'])){
		$cat = true;
		$_GET['cat'] = str_replace('/', '', $_GET['cat']);
		$pid = SafeEnv($_GET['cat'], 11, int);
		$e_where = " and (`id`='$pid' or `parent_id`='$pid')";
	}else{
		$cat = false;
		$e_where = '';
	}
	$s_title = (!$cat ? $forum_lang['statistics'] : $forum_lang['statistics_cat']);
	$statistics->Initialize($s_title);
	if($cat){
		$statistics->_current = $pid;
	}else {
		$statistics->stop = true;
	}
	if($config['forum']['cache'] && !$user->Auth){
		$cache_name = 'IndexForumMain_'.$user->AccessLevel()."_cat$pid";
		$cache = LmFileCache::Instance();
		if($cache->HasCache('forum', $cache_name)){
			$site = $cache->Get('forum', $cache_name);
			return true;
		}
	}
	$result =  Forum_Cache_AllDataTableForum();
	$forums = array();
	foreach($result as $f) {
		$forums[$f['parent_id']][] = IndexForumDataFilter($f);
		$statistics->reply_count +=  $f['posts'];
		if($f['parent_id']>0){
			$forums2[$f['parent_id']][] = IndexForumDataFilter($f);
		}
	}
	// $forums - форумы по родительским категориям
	$result = Forum_Cache_AllDataTableForumTopics();
	$statistics->topics_count =  count($result) ;
	foreach($result as $topic) {
		$topics[$topic['forum_id']][] = $topic;
		if(!$cat){
			$statistics->hits += $topic['hits'];
			$statistics->AddTopicAuthor($topic['starter_id'], $topic['starter_name']);
		}
	}
	// $topics - темы по родительским форумам
	if(isset($forums['0'])){ // есть категории
		$cat_id = -1;
		if(isset($_GET['cat'])){
			Navigation_Patch( $cat) ;
			$cat_id = SafeEnv($_GET['cat'], 11, int);
		}

		$read_data = Forum_Marker_GetReadData();
		$c_u = Online_GetCountUser($cat_id, true);

		$is_forum_member = $user->AccessIsResolved(2);
		$site->AddBlock('is_forum_member', $is_forum_member, false, 'mark');
		$site->AddBlock('old', true, false, 'mark');

		$vars_is_forum_member['url'] = '<a href="'.Ufu('index.php?name=forum&op=markread', 'forum/markread/').'">'.$forum_lang['mark_all_read'].'</a>';
		$vars_is_forum_member['viewnoreadurl'] = '<a href="'.Ufu('index.php?name=forum&op=viewnoread', 'forum/viewnoread/').'">'.$forum_lang['viewnoread'].'</a>';
		$vars_old['lasttopics'] = '<a href="'.Ufu('index.php?name=forum&op=lasttopics', 'forum/lasttopics/').'">'.$forum_lang['lasttopics'].'</a>';

		$site->Blocks['is_forum_member']['vars'] = $vars_is_forum_member;
		$site->Blocks['old']['vars'] = $vars_old;

		$site->AddTemplatedBox('', 'module/forums.html');
		$site->AddBlock('forums', true, true, 'forum');
		$site->AddBlock('is_no_sub_forum', true, false);


		SortArray($forums['0'], 'order');
		foreach($forums['0'] as $category){
			if(isset($forums[$category['id']])){
				if($cat and $category['id']<>$pid){
					continue;
				}
				IndexForumCatOpen($category);
				IndexForumRender($category);
				SortArray($forums[$category['id']], 'order');
				foreach($forums[$category['id']] as $forum) {
					if($category['close_topic'] == 1){
						if($forum['close_topic'] == 0){
							$forum['close_topic'] =1;
							$forum = IndexForumDataFilter($forum);
						}
					}
					if(isset($forums2[$forum['id']]))  {
						foreach($forums2[$forum['id']] as $key=>$sub_forum){
							if(isset($forums2[$sub_forum['id']])){
								$result2 = IndexForumStats($sub_forum['id']);
								if($result2['last']['last_post_date'] > $forum['last_post_date']){
									$forum['last_post'] = $result2['last']['last_post_date'];
									$forum['last_id'] = $result2['last']['last_id'];
									$forum['last_poster_id'] = $result2['last']['last_poster_id'];
									$forum['last_poster_name'] =  SafeDB($result2['last']['last_poster_name'], 250, str);
									$forum['last_title'] =  SafeDB($result2['last']['last_title'], 250, str);
									$forum = IndexForumDataFilter($forum);
								}
								$forum['topics'] =	$forum['topics'] + $result2['topics'];
								$forum['posts']  = $forum['posts'] + $result2['posts'];
							}

							if($sub_forum['last_post_date'] > $forum['last_post_date']){
								$forum['last_post'] = $sub_forum['last_post_date'];
								$forum['last_id'] = $sub_forum['last_id'];
								$forum['last_poster_id'] = $sub_forum['last_poster_id'];
								$forum['last_poster_name'] =  SafeDB($sub_forum['last_poster_name'], 250, str);
								$forum['last_title'] =  SafeDB($sub_forum['last_title'], 250, str);
								$forum = IndexForumDataFilter($forum);
							}
							$forum['topics'] =	$forum['topics'] + $sub_forum['topics'];
							if($forum['topics'] < 0 ) $forum['topics'] = 0;
							$forum['posts']  = $forum['posts'] + $sub_forum['posts'];
							if($forum['posts'] < 0 ) $forum['posts'] = 0;
							$forums2[$sub_forum['parent_id']][$key]['read'] = 1;
							$forums2[$sub_forum['parent_id']][$key]['read2'] = false;
							if(isset($topics[$sub_forum['id']])) {
								foreach($topics[$sub_forum['id']] as $topic2){
									if(isset($read_data[SafeDB($topic2['id'],11,int)])) {
										if($read_data[SafeDB($topic2['id'],11,int)]['date'] < SafeDB($topic2['last_post'],11,int)){
											if(	!$forums2[$id][$key]['read2'])
											$forums2[$sub_forum['parent_id']][$key]['read'] = 0;
											$forums2[$sub_forum['parent_id']][$key]['read2'] = true;
										}
									}else{
										if(	!$forums2[$sub_forum['parent_id']][$key]['read2'])
										$forums2[$sub_forum['parent_id']][$key]['read'] = 0;
										$forums2[$sub_forum['parent_id']][$key]['read2'] = true;
									}
									if(!$statistics->stop){
										$statistics->hits += $topic2['hits'];
										$statistics->AddTopicAuthor($topic2['starter_id'],  SafeDB($topic2['starter_name'], 250, str));
									}
								}
							}
						}
					}

					if($cat){
						if(isset($c_u['users']) and isset($forum['users']['reg'])){
							foreach($forum['users']['reg'] as $muser) {
								$c_u['users']['reg'][] = $muser;
							}
						}
					}
					$read = true;
					if(isset($topics[$forum['id']])){
						foreach($topics[$forum['id']] as $topic){
							if(isset($read_data[SafeDB($topic['id'],11,int)])){
								if($read_data[SafeDB($topic['id'],11,int)]['date'] < SafeDB($topic['last_post'],11,int)){
									$read = false;
								}
							}else{
								$read = false;
							}
						}
					}
					IndexForumRender($forum, $read, (isset($forums2[$forum['id']])?$forums2[$forum['id']]:array()));
				}
				IndexForumCatClose($category);
			}
		}
	}else{
		$site->AddTextBox($forum_lang['forum'], $forum_lang['no_category'] );
	}

	if(isset($_GET['cat'])){
		$cat = SafeEnv($_GET['cat'], 11, int);
	}else{
		$cat = 0;
	}
	if(isset($c_u['users'])){
		Forum_Online_Render_Online($c_u['users'], ($cat_id == -1?$forum_lang['all_online']:$forum_lang['current_category'])) ;
	}
	$statistics->Render();
	if(isset($_GET['cat'])){
		$quick_transition= Navigation_GetForumCategoryComboBox($cat);
		$site->AddTextBox('', '<span style="float:right;">'.$forum_lang['quick_transition'].':&nbsp;'. $quick_transition.'</span>');
	}
	if($config['forum']['cache']){
		if(!$user->Auth){
			$cache->Write('forum', $cache_name, $site);
		}
	}
}
