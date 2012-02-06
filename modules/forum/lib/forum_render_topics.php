<?php
# LinkorCMS
# LinkorCMS Development Group
# www.linkorcms.ru
# Лицензия LinkorCMS 1.2.
# темы 

/**
 *
 * @param <type> $forum Все форумы
 * @param <type> $alltopics Все темы
 * @param ForumStatistics $statistics Вывод статистики по темам и форумам
 * @param <type> $out_topics
 */
function Forum_Render_FilterTopics( &$forum, &$alltopics, &$statistics, &$out_topics ){
	global $db, $config, $site, $user, $lang;
	$ig = true;
	$topics_stick = array();
	$out_topics2 = array();
	foreach($alltopics as $topic){
		if(isset($forum[$topic['forum_id']]) && (
				$forum[$topic['forum_id']]['status'] == 1
				&& $user->AccessIsResolved($forum[$topic['forum_id']]['view'])
			)
		){ // Форум существует, включен и есть доступ
			if(isset($forum[$forum[$topic['forum_id']]['parent_id']]) && (
					$forum[$forum[$topic['forum_id']]['parent_id']]['status'] == 1
					&& $user->AccessIsResolved($forum[$forum[$topic['forum_id']]['parent_id']]['view'])
				)
			){
				// Доступ по рангу
				$rang = Rang_UserStatus($forum[$topic['forum_id']]);
				if($rang['rang_access']){
					$right = $rang['right'];
					if($topic['stick'] == 0) {
						$out_topics2[] = $topic;
					}else{
						# Прикреплённые темы
						$topics_stick[] = $topic;
					}
					$statistics->topics_count++;
					$statistics->reply_count += $topic['posts'];
					$statistics->hits += $topic['hits'];
					$statistics->AddTopicAuthor($topic['starter_id'], $topic['starter_name']);
				}
			}
		}
	}
	if(is_array($out_topics2)){
		SortArray($out_topics2, 'last_post', true);
		SortArray($topics_stick, 'last_post', true);
		$out_topics = array_merge($topics_stick, $out_topics2);
	}elseif(is_array($topics_stick)){
		SortArray($topics_stick, 'last_post', true);
		$out_topics = $topics_stick;
	}
}


function Forum_Render_Topics(&$forum, &$topics, &$read_data,
		$is_forum_member = true, $page = 0, $starter_name='', $c_u = '',
		$rang='' , &$online_user=null)
{

	global $db, $config, $site, $user, $lang, $UFU;
	$basket = Forum_Basket_RenderBasket($topics, 'forum_basket_topics');

	$site->AddBlock('no_topics', count($topics) == 0);
	$site->AddTemplatedBox('', 'module/forum_topics.html');
	if(!isset($site->Blocks['is_forum_member'])){
		$site->AddBlock('is_forum_member', $is_forum_member, false, 'topic');
	}

	$site->AddBlock('statistik', true, true, 'stat');
	if(!isset($c_u['count'])){
		$stat['count_read'] = '';
	}else{
		$stat['count_read'] =  $c_u['count'];
	}
	$site->AddSubBlock('statistik', true, $stat);

	$site->AddBlock('topics', true, true, 'topic');

	if(is_array($topics) && count($topics) > 0){
		
		foreach($topics as $topic){
			$topic = Forum_Topic_DataFilter($topic, false);

			if(isset($online_user) && isset($topic['users']['reg']) && isset($online_user['reg'])){
				foreach($topic['users']['reg'] as $muser){
					$online_user['reg'][] = $muser;
				}
			}

			if($topic['delete'] > 0 && $config['forum']['basket'] == true){
				if(isset($basket[$topic['id']])){
					$topic['title'] =  $topic['title']
					.'<br />'
					.Forum_Basket_RenderBasketComAdmin($topic['id'], $topic['title'], $basket, false);
				}
			}

			$topic['page'] = $page;
			if(isset($read_data[$topic['id']]) && $read_data[$topic['id']]['date'] >= $topic['last_post_date']){
				$topic['on'] = false;
				$topic['off'] = true;
			}else {
				$topic['on'] = true;
				$topic['off'] = false;
			}
			if(!is_array($rang)){
				if(!isset($rang['one_category'])){
					$rang = Rang_UserStatus($forum[$topic['forum_id']]);
				}
			}
			if($rang['close_topic'] == 1){
				$topic['close'] = $rang['close_topic'] == 0;
				$topic['begin'] = $rang['close_topic'] == 1;
				$topic['status'] = (!$topic['close'] ? $lang['topic_close'] : '');
			}
			if(!isset($topic['read'])){
				$topic['read']  = ' ';
			}

			//	if($starter_name<>'')
			//	$topic['starter_name'] = $starter_name;
			$site->AddSubBlock('topics', true, $topic);
		}
	}
}
