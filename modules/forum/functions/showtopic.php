<?php

/*
*  Просмотр темы форума или отдельного поста
*/
function IndexForumShowTopic( $one_post = false ){
	global $db, $config, $site, $user, $lang, $topic_show_title;

	$topic_id = SafeEnv($_GET['topic'], 11, int);

	if(isset($_GET['page'])) {
		$page = SafeEnv($_GET['page'], 11, int);
	}else {
		$page = 1;
	}
	if(isset($_GET['view']) && $_GET['view'] == 'lastpost') {
		$lastpost = true;
	}else {
		$lastpost = false;
	}
	$posts_on_page = $config['forum']['posts_on_page'];

	if($user->Auth){ // Прочитанная тема
		$u_id = $user->Get('u_id');
		$db->Delete('forum_topics_read', "`tid`='$topic_id' and `mid`='$u_id'");
		$time = time();
		$vals = "'$u_id','$topic_id','$time'";
		$db->Insert('forum_topics_read',$vals);
	}

	// Кэширование
	$cache_name = 'Forum_Topic_ShowTopic_AccessLevel'.$user->AccessLevel();
	$cache_name .= '_page'.$page;
	if($lastpost){
		$cache_name .= '_lastpost';
	}
	$cache_name .='_id'.$topic_id;
	$cache = LmFileCache::Instance();
	if($config['forum']['cache']){
		if(!$user->Auth){
			// Кэшировние страницы
			if($cache->HasCache('forum', $cache_name.'_quest')){
				Forum_Topic_SetHitsData($topic_id);
				$site = $cache->Get('forum', $cache_name.'_quest');
				$site->TEcho();
				exit;
			}
		}
		// Кэширование сообщений
		if($cache->HasCache('forum', $cache_name)){
			$topics = $cache->Get('forum', $cache_name);
		}else{
			$topics = $db->Select('forum_topics', "`id`='$topic_id'");
			$cache->Write('forum', $cache_name, $topics, $config['forum']['maxi_cache_duration']);
		}
	}else{
		$topics = $db->Select('forum_topics', "`id`='$topic_id'");
		if($config['forum']['cache']){
			$cache->Write('forum', $cache_name, $topics, $config['forum']['maxi_cache_duration']);
		}
	}


	if(count($topics) > 0){
		$topic = $topics[0];
		$topic_show_title = $topic['title'] ;
		$delete = ($config['forum']['basket'] ? SafeDb($topic['delete'], 1,  int) : false);
		if($delete == 0 || $user->isAdmin()){ // Тема не удалена
			if($delete == 1 && $user->isAdmin()){
				if($config['forum']['basket']){
					$basket = Forum_Basket_RenderBasket($topics, 'forum_basket_topics');
					if(isset($basket[$topic['id']])){
						$text = Forum_Basket_RenderBasketComAdmin($topic['id'], $topic['title'], $basket, true, true);
						$site->AddTextBox($lang['topic_basket_red'], $text);
					}
				}
			}

			$c = Online_GetCountUser($topic['id'], false, true);
			$topic2['read'] = $c['count'];
			$topic2['users'] = $c['users'];

			$forum_id = SafeDB($topic['forum_id'], 11, int);
			$mforums  =  Forum_Cache_AllDataTableForum();
			$forums2 = array();
			if(count($mforums) > 0){
				foreach($mforums as $mforum){
					if($mforum['id'] == $forum_id)
					$forum = $mforum;
				}
				foreach($mforums as $mforum){
					if($mforum['id'] == $forum['parent_id'])
					$parent = $mforum;
					$forums2[$mforum['id']] =  $mforum;
				}
			}

			if($parent['close_topic'] == 1){
				$forum['close_topic'] = 1;
			}

			// Доступ по уровню видимости
			if(
			($user->AccessIsResolved($forum['view']) && $forum['parent_id'] != '0')
			&& ($user->AccessIsResolved($parent['view']))
			){
				// Доступ по рангу
				$rang = Rang_UserStatus($forum);
				if($rang['rang_access']){
					$right = $rang['right'];
					$is_message_add	= $rang['rang_message'];
					Navigation_Patch($forum['id'], true);
					$site->Title .= $lang['site_slas'].$parent['title'].$lang['site_slas']. $forum['title'] .$lang['site_slas'].$topic['title'];

					// Бокс с названием темы и количеством просматривающих пользователей
					$site->AddTextBox('','<b>'.SafeDB($topic['title'], 255, str).'</b>'.$topic2['read']);

					// Увеличиваем счетчик просмотров
					$db->Update('forum_topics', "hits='".(SafeDB($topic['hits'], 11, int) + 1)."'", "`id`='".$topic_id."'");

					//Вывод сообщений
					$site->AddTemplatedBox('', 'module/forum_showtopic.html');
					if($one_post){
						$one_post = "`id`='$one_post'";
					}else{
						$one_post = '';
					}
					Posts_RenderPosts(
					$topic_id,
					'forum_posts',
					$lastpost,
					$page,
					true,
					$posts_on_page,
					'index.php?name=forum&amp;op=showtopic&amp;topic='.$topic_id,$rang['no_link_guest'],
					'', // your_where
					$forum['id'],
					$parent['id'],
					($topic['close_topics']==1?true:false),
					$one_post
					);

					// Форма добавления комментарий
					$is_forum_member = $user->AccessIsResolved(2);
					$site->AddBlock('post_form', $is_forum_member, false);

					if($is_message_add){
						$rang= Rang_RangUserTopic($rang,$topic);
						$is_message_add = $rang['rang_message'];
						Posts_RenderPostForm(
						false,
						$forum['id'],
						$topic['id'],
						0,
						'',
						'',
						$is_forum_member
						);
					}else{
						$is_forum_member = false;
					}

					// Подписка на тему
					$is_subscription = $forum['new_message_email'] == 1;
					$site->AddBlock('subscription', $is_subscription, false, 'subs');
					$vars_subs = array();
					$vars_subs['topic'] = $topic_id;
					if(Forum_Subscription_Status($topic_id)){
						$vars_subs['status'] = 'Отписаться от этой темы';
					}else{
						$vars_subs['status'] = 'Подписаться на эту тему';
					}
					$site->Blocks['subscription']['vars'] = $vars_subs;
					//

					$site->AddBlock('is_forum_member', $is_forum_member, false, 'marker');
					$vars_marker = array();
					$vars_marker['id'] = $topic_id;
					$site->Blocks['is_forum_member']['vars'] = $vars_marker;

					$site->AddBlock('is_marker', true, false, 'marker');
					$vars_marker['id'] = $topic_id;
					$site->Blocks['is_marker']['vars'] = $vars_marker;

					if(isset($topic2['users'])){
						Forum_Online_Render_Online($topic2['users'], $lang['current_online']);
					}
				}else {
					$site->AddTextBox($lang['error'], $lang['error_access_category']);
				}
			}else {
				$site->AddTextBox($lang['error'], $lang['error_access_category']);
			}
		}else {
			$site->AddTextBox($lang['topic_basket'], $lang['topic_basket_current'].'</BR><input type="button" value="'.$lang['back'].'"onclick="history.back();"></center>');
		}
	}else{
		$site->AddTextBox($lang['error'], '<center><input type="button" value="'.$lang['back'].'"onclick="history.back();"></center>');
	}

	// Права на форуме
	if(isset($right)){
		$site->AddBlock('topic_right', true, true, 'topic', 'module/forum_right.html');
		$vars = array();
		$vars['right'] = $right;
		$site->AddSubBlock('topic_right', true, $vars);
	}

	if(!isset($forum_id)) $forum_id = 0;
	// Быстрый переход по форумам
	$site->AddTextBox('', '<span style="float:right;">'.$lang['quick_transition'].':&nbsp;'. Navigation_GetForumCategoryComboBox($forum_id).'</span>');

	// Кэшируем страницу
	if($config['forum']['cache']) {
		if(!$user->Auth){
			$cache->Write('forum', $cache_name.'_quest_', $site, 0);
		}
	}

}

?>