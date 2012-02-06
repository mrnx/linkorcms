<?php

ForumLoadFunction('main');

function IndexForumShowForum(){
	global $db, $config, $site, $user, $lang, $UFU, $forum_lib_dir, $read_data, $forums2,  $topics, $statistics;
	$forums2 = array();
	$topics = array();
	$read_data = array();

	if($UFU){
		$forum_nav_url = 'forum/';
	}else{
		$forum_nav_url = 'index.php?name=forum&amp;op=showforum&amp;forum=';
	}

	$where_forum_id = '';

	if(isset($_GET['page'])) {
		$page = SafeEnv($_GET['page'], 11, int);
	}else {
		$page = 1;
	}
	$topics_on_page = $config['forum']['topics_on_page'];


	$statistics = ForumStatistics::Instance();
	$statistics->Initialize($lang['statistics_cat']);

	if($user->isAdmin() && $config['forum']['basket']){
		$your_where = '';
	}else{
		$your_where = " and`delete`='0'";
	}
	$online_user = array();

	if($config['forum']['cache']){
		$cache_name = 'IndexForumShowForum_AccessLevel'.$user->AccessLevel();
		$cache_name .= '_page'.$page;
		$cache = LmFileCache::Instance();
		if(!$user->Auth){
			if(isset($_GET['forum'])){
				$forum_id = SafeEnv($_GET['forum'], 11, int);
				$cache_name .= '_forum_id'.$forum_id.'_';
			}
			$cache_name .= '_quest_';
			if($cache->HasCache('forum', $cache_name)){
				$site = $cache->Get('forum', $cache_name);
				return true;
			}
		}
	}

	if(isset($_GET['forum'])){
		$forum_id = SafeEnv($_GET['forum'], 11, int);
		$statistics->_current = $forum_id;
		$c_u = Online_GetCountUser($forum_id);
		$online_user = $c_u['users'];

		if($_GET['forum'] > 0){
			$forum = array();
			$forum_parent = array();
			$mforums = Forum_Cache_AllDataTableForum();
			if(count($mforums) > 0){
				foreach($mforums as $mforum){
					if($mforum['id'] == $forum_id){
						$forum = $mforum;
					}
					if($mforum['parent_id'] == 0){
						$forum_parent['id'] = $mforum;
					}
					$mforums[$mforum['id']] = $mforum;

					if($mforum['parent_id'] > 0 )
					$forums2[$mforum['parent_id']][] = IndexForumDataFilter($mforum);
					if($mforum['parent_id'] == SafeEnv($_GET['forum'], 11, int))
					$where_forum_id .= "`forum_id`= '".$mforum['id']."' or";

				}

				if(count($forum) > 0){
					foreach($mforums as $mforum){
						if($mforum['id'] == $forum['parent_id']){
							$parent = $mforum;
						}
					}
				}
				if(count($forum) == 0){
					IndexForumMain();
					return;
				}
			}
		}else{
			IndexForumMain();
			return;
		}


		if(count($forum) > 0){
			include_once($forum_lib_dir.'forum_render_topics.php');
			if($forum['parent_id'] == '0'){
				if($_GET['forum'] > 0){
					$_GET['cat'] = $forum['id'];
				}
				IndexForumMain();
				return;
			}
			if($forum['parent_id'] != '0') {
				if(isset($parent)) {
					if($parent['close_topic'] == 1){
						$forum['close_topic'] = 1;
					}
					$forum = IndexForumDataFilter($forum);
					if($user->AccessIsResolved($parent['view'])){
						# Доступ по рангу
						$rang = Rang_UserStatus($forum);
						if($rang['rang_access']){
							$right = $rang['right'];
							$is_theme_add = $rang['is_theme_add'];
							$parent = IndexForumDataFilter($parent);
							Navigation_Patch($forum['id']);
							$site->Title = $site->Title.$lang['site_slas'].$parent['title'].$lang['site_slas'].$forum['title'];
							$is_forum_member = $user->AccessIsResolved(2);
							if(!$is_theme_add || !$user->Get('u_add_forum')){
								$is_forum_member = false;
							}



							if(isset($forums2[$forum['id']])) {
								$site->AddBlock('is_no_sub_forum', false, false);
							} else {
								$site->AddBlock('is_no_sub_forum', true);
							}
							$site->AddBlock('is_no_sub_forum', false, false);
							$read_data = Forum_Marker_GetReadData();
							if(isset($forums2[$forum['id']])) {
								$site->AddTemplatedBox('', 'module/forums.html');
								$site->AddBlock('forums', true, true, 'forum');
								$site->AddBlock('forum_statistics', false, false);
								$site->AddBlock('forum_online', false, false);
								$f =array();
								$f = $forum;
								$f['parent_id'] = 0;
								$f['title'] = 'Подфорумы категории - '.$f['title'];
								IndexForumCatOpen($f);
								IndexForumRender($f);

								foreach($forums2[$forum['id']] as $forum2) {
									if(isset($forums2[$forum2['id']]))
									foreach($forums2[$forum2['id']] as $forum3){
										$where_forum_id .= "`forum_id`= '".$forum3['id']."' or";
									}
								}
								SortArray($forums2[$forum['id']], 'order');
								if(isset($where_forum_id) and $where_forum_id<>''){
									$where_forum_id = "(".substr($where_forum_id, 0, strlen($where_forum_id)-3).")";
								}
								$sub_topics = array();
								if($where_forum_id <>''){
									//$sub_topics1 = $db->SelectFields('forum_topics',$where_forum_id , " id, last_post");
									$sub_topics1 = $db->Select('forum_topics',$where_forum_id );
									foreach($sub_topics1 as $sub_topics2){
										$sub_topics[$sub_topics2['forum_id']][] =  $sub_topics2;
										$statistics->hits += $sub_topics2['hits'];
										$statistics->AddTopicAuthor($sub_topics2['starter_id'], $sub_topics2['starter_name']);
										$forum['topics'] ++;

									}
									$topics = $sub_topics;
								}
								foreach($forums2[$forum['id']] as $forum2){
									if(isset($forums2[$forum2['id']])) {
										$result2 = IndexForumStats($forum2['id']);
										$forum2['topics'] =	 $forum2['topics'] +$result2['topics'];
										$forum2['posts'] =	$forum2['posts'] +$result2['posts'];
										$forum['posts'] =	$forum['posts'] +$result2['posts'];
										if($forum2['topics'] < 0 ) $forum2['topics'] = 0;
										if($forum2['posts'] < 0 ) $forum2['posts'] = 0;
										if($forum['posts'] < 0 ) $forum['posts'] = 0;

										if($result2['last']['last_post_date'] > $forum2['last_post_date']){
											$forum2['last_post'] = $result2['last']['last_post_date'];
											$forum2['last_id'] = $result2['last']['last_id'];
											$forum2['last_poster_id'] = $result2['last']['last_poster_id'];
											$forum2['last_poster_name'] = $result2['last']['last_poster_name'];
											$forum2['last_title'] = $result2['last']['last_title'];
											$forum2 = IndexForumDataFilter($forum);
										}
									}

									if($forum2['close_topic'] == 1)	 {
										if($forum2['close_topic'] == 0){
											$forum2['close_topic'] =1;
										}
									}
									if(isset($c_u['users']) and isset($forum2['users']['reg']) )
									{
										foreach($forum2['users']['reg'] as $muser)
										{
											$c_u['users']['reg'][] = $muser;
										}
									}

									$read = true;
									if(isset($sub_topics[$forum['id']])) {
										foreach($sub_topics[$forum['id']] as $topic) {
											if(isset($read_data[SafeDB($sub_topics['id'],11,int)])){
												if($read_data[SafeDB($sub_topics['id'],11,int)]['date'] < SafeDB($sub_topics['last_post'],11,int)){
													$read = false;
												}
											}else{
												$read = false;
											}
										}
									}

									IndexForumRender($forum2, $read, (isset($forums2[$forum2['id']])?$forums2[$forum2['id']]:array()));
								}
								IndexForumCatClose($f);
							}

							$statistics->reply_count += $forum['posts'];
							$statistics->topics_count +=  $forum['topics'] ;
							$site->AddBlock('is_forum_member', $is_forum_member, false, 'mark');
							$site->AddBlock('old', true, false, 'mark');
							if(!$UFU){
								$vars_is_forum_member['url'] = '<a href="index.php?name=forum&amp;op=markread&amp;forum='.$forum['id'].'">'.$lang['mark_all_read'].'</a>';
								$vars_is_forum_member['viewnoreadurl'] = '<a href="index.php?name=forum&amp;op=viewnoread&amp;forum='.$forum['id'].'">'.$lang['viewnoread'].'</a>';
								$vars_old['lasttopics'] = '<a href="index.php?name=forum&amp;op=lasttopics&amp;forum='.$forum['id'].'">'.$lang['lasttopics'].'</a>';
							}else{
								$vars_is_forum_member['url'] = '<a href="forum/markread/'.$forum['id'].'">'.$lang['mark_all_read'].'</a>';
								$vars_is_forum_member['viewnoreadurl'] = '<a href="forum/viewnoread/'.$forum['id'].'">'.$lang['viewnoread'].'</a>';
								$vars_old['lasttopics'] = '<a href="forum/lasttopics/'.$forum['id'].'">'.$lang['lasttopics'].'</a>';
							}
							$site->Blocks['is_forum_member']['vars'] = $vars_is_forum_member;
							$site->Blocks['old']['vars'] = $vars_old;
							$site->SetVar('is_forum_member', 'forum_id', $forum['id']);

							if($user->AccessIsResolved($forum['view']) && $user->AccessIsResolved($parent['view'])){
								// Выводим темы
								$topics = array();
								if($config['forum']['cache'] && $cache->HasCache('forum', $cache_name.'_forum_id'.$forum_id)){
									$topics = $cache->Get('forum', $cache_name.'_forum_id'.$forum_id);
								}else{
									$topics = $db->Select('forum_topics', "`forum_id`='$forum_id'".$your_where);
									if($config['forum']['cache']){
										$cache->Write('forum', $cache_name.'_forum_id'.$forum_id, $topics, $config['forum']['maxi_cache_duration']);
									}
								}
								if(count($topics) > 0){
									Forum_Render_FilterTopics($mforums, $topics, $statistics, $topics);
								}

								if($UFU){
									$forum_nav_url .= $forum['id'].'-{page}';
								}else{
									$forum_nav_url .= $forum_id;
								}
								$navigation = new Navigation($page);
								$navigation->FrendlyUrl = $UFU;
								$navigation->GenNavigationMenu($topics, $topics_on_page, $forum_nav_url);

								if(is_array($topics) && count($topics) > 0){
									Forum_Render_Topics($forum, $topics, $read_data, $is_forum_member, $page, '', $c_u, $rang, $online_use);
								}else{
									Forum_Render_Topics($forum, $topics, $read_data, $is_forum_member, $page, '', $c_u, $rang, $online_use);
								}
								$site->AddBlock('topic_form', $is_forum_member, false, 'form');

								if($is_forum_member){
									if(!$UFU) {
										$site->SetVar('topic_form', 'url', 'index.php?name=forum&amp;op=addtopic&amp;forum=' . $forum_id);
									}else{
										$site->SetVar('topic_form', 'url', 'forum/addtopic/'.$forum_id);
									}
									ForumSmile();
								}
							}else{
								$site->AddTextBox($lang['error'], $lang['error_access_category']);
							}
						}else{
							$site->AddTextBox($lang['error'], $lang['error_access_category']);
						}
					}else{
						$site->AddTextBox($lang['error'], $lang['error_access_category']);
					}
				}else{
					$site->AddTextBox($lang['error'], $lang['error_access_category']);
				}
			}else{
				$site->AddTextBox($lang['error'], $lang['error_no_forum'] );
			}
		}else{
			$site->AddTextBox($lang['error'], $lang['error_no_forum'] );
		}
	}else{
		$site->AddTextBox($lang['error'], $lang['error_no_forum'] );
	}

	if(isset($_GET['forum'])) {
		$cat = SafeEnv($_GET['forum'], 11, int);
	}else{
		$cat = 0;
	}

	// Пользователи онлайн
	if(isset($online_user)){
		if(isset($online_user['reg'])){		
			Forum_Online_Render_Online($online_user, $lang['current_category'], 'forum_topics_online');
		}
	}
	   
	// Права на форуме
	if(isset($right)){
		$site->AddBlock('topic_right', true, true, 'topic', 'module/forum_right.html');
		$vars = array();
		$vars['right'] = $right;
		$site->AddSubBlock('topic_right', true, $vars);
	}

	// Статистика
	$statistics->Render('forum_topics_statistics');

	// Быстрый переход по форумам
	$site->AddTextBox('', '<span style="float:right;">'.$lang['quick_transition'].':&nbsp;'. Navigation_GetForumCategoryComboBox($cat).'</span>');

	// Кэширование страницы
	if($config['forum']['cache']){
		if(!$user->Auth){
			$cache->Write('forum', $cache_name, $site);
		}
	}
}
