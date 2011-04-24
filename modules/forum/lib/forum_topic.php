<?php
# LinkorCMS
# LinkorCMS Development Group
# www.linkorcms.ru
# Лицензия LinkorCMS 1.2.
# Темы форума

function Forum_Topic_DataFilter( &$topic, $root = true ){
	global  $lang, $UFU, $config;

	$topic2 = array();
	$topic2['id'] = SafeDB($topic['id'], 11, int);
	$topic2['forum_id'] = SafeDB($topic['forum_id'], 11, int);
	$topic2['title'] = SafeDB($topic['title'], 255, str);
	$topic2['state'] = SafeDB($topic['state'], 1, int);
	$topic2['posts'] = SafeDB($topic['posts'], 11, int)<0?0:SafeDB($topic['posts'], 11, int);
	$topic2['hits'] = SafeDB($topic['hits'], 11, int);
	$topic2['start_date'] = TimeRender(SafeDB($topic['start_date'], 11, int), true, true);
	$topic2['starter_id'] = SafeDB($topic['starter_id'], 11, int);
	$topic2['starter_name'] = SafeDB($topic['starter_name'], 255, str);
	$topic2['starter_url'] = (!$UFU?'index.php?name=user&op=userinfo&user='.$topic2['starter_id']:
	'user/'.$topic2['starter_id']);

	$topic2['last_post_date'] = SafeDB($topic['last_post'], 11, int);
	$topic2['last_post'] = TimeRender(SafeDB($topic['last_post'], 11, int), true, true);
	if($topic2['last_post_date'] > (time()-86400)){
		$topic2['last_post'] = '<font color="#FF0000">'.$topic2['last_post'].'</font>';
	}

	$topic2['last_poster_id'] = SafeDB($topic['last_poster_id'], 11, int);
	$topic2['last_poster_name'] = SafeDB($topic['last_poster_name'], 255, str);
	$topic2['last_poster_url'] = (!$UFU ? 'index.php?name=user&op=userinfo&user='.$topic2['last_poster_id']:
	'user/'.$topic2['last_poster_id']);

	$c = Online_GetCountUser($topic2['id'], $root, true);
	$topic2['read'] = $c['count'];
	$topic2['users'] = $c['users'];
	$topic2['close'] = SafeDB($topic['close_topics'], 1, int)==0;
	$topic2['begin'] = !$topic2['close'];
	$topic2['status'] = (!$topic2['close']?$lang['topic_close']:'');
	$topic2['stick'] = ($topic['stick'] == 1?$lang['it_is_ important']:'');
	$topic2['delete'] = SafeDB($topic['delete'], 1, int);
	$topic2['nodelete'] = (SafeDB($topic['delete'], 1, int)?false:true);
	$topic2['category'] = $topic['forum_id'];

	if($UFU){
		$topic2['url'] = 'forum/topic'.$topic2['id'].'.html';
		$topic2['last_url'] = 'forum/topic'.$topic2['id'].'-new.html';
	}else{
		$topic2['url'] = 'index.php?name=forum&amp;op=showtopic&amp;topic='.$topic2['id'];
		$topic2['last_url'] = 'index.php?name=forum&amp;op=showtopic&amp;topic='.$topic2['id'].'&amp;view=lastpost';
	}

	$topic2['pages'] = '';
	if($topic2['posts'] + 1 > $config['forum']['posts_on_page']){
		$forum_nav_url ='index.php?name=forum&amp;op=showtopic&amp;topic=';
		if($UFU){
			$forum_nav_url ='forum/'.$topic2['category'].'/'.$topic2['forum_id'].'/topic';
		}
		$page = ceil(($topic2['posts']+1)/$config['forum']['posts_on_page']);

		$str = $lang['pages'];
		for($i=0, $page; $i<$page; $i++){
			$str .= '<a href="'.$forum_nav_url.$topic2['id'].($UFU?'-'.($i+1).'.html':'&page='.($i+1)).'"><FONT SIZE="1">'.($i+1).' </FONT></a>';
			if($i > 5 && $page > 10) {
				$str .= '....<a href="'.$forum_nav_url.$topic2['id'].($UFU?'-'.($page-1).'.html':'&page='.($page-1)).'"><FONT SIZE="1">'.($page-1).' </FONT></a>';
				$str .= '<a href="'.$forum_nav_url.$topic2['id'].($UFU?'-'.($page).'.html':'&page='.$page).'"><FONT SIZE="1">'.$page.' </FONT></a>';
				break;
			}
		}
		$topic2['pages'] .= $str.'<br />';
	}
	return $topic2;
}

function Forum_Topic_SetHitsData($topic_id) {
	global $db;
	if( $topic_id<>'') {
		$where = "`id`='$topic_id'";
		$topic = $db->Select('forum_topics',$where);
		if(count($topic) > 0) {
			$hits = $topic[0]['hits'] + 1;
			$db->Update('forum_topics', "hits='".$hits."'", "`id`='" . $topic_id . "'");
		}
	}

}

?>