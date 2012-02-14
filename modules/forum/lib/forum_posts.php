<?php
# LinkorCMS
# LinkorCMS Development Group
# www.linkorcms.ru
# Лицензия LinkorCMS 1.2.
# сообщения

function word_wrapped_string($text, $limit = 75, $wraptext = '<br />'){
	$limit = intval($limit);
	if ($limit > 0 AND !empty($text)) {
		return preg_replace('	#((?>[^\s&/<>"\\-\[\]]|&[\#a-z0-9]{1,7};){'.$limit.'})(?=[^\s&/<>"\\-\[\]]|&[\#a-z0-9]{1,7};)#i',
			'$0'.$wraptext,
			$text
		);
	}else{
		return $text;
	}
}


function Posts_RenderPosts( $topic_id, $blockname, $lastpost = false,
		$page = 0, $EnNav = false, $com_on_page = 10, $nav_url = '',
		$no_link_guest = false, $your_where = '', $parent_forum_id = '',
		$parent_parent_forum_id = '' , $topic_close = false , $one_post=''){
	global $site, $db, $config, $user, $forum_lang;

	$cache_name = 'IndexForumRenderPosts_page'.$page.'_';
	$site->AddBlock($blockname, true, true, 'post');
	if($user->isAdmin() || $config['forum']['basket'] == false) {
		$your_where = '';
		$cache_name .= 'object'.$topic_id;
	}else{
		$your_where = 'and `delete`=\'0\'';
		$cache_name .= 'object'.$topic_id.'_u';
	}
	if($user->isAdmin()){
		$cache_name .='a_page'.$page;
	}
	$where = "`object`='".$topic_id."'".$your_where;
	if($lastpost){
		$cache_name .= 'lastpost';
	}
	$cache = LmFileCache::Instance();
	if(!$cache->HasCache('forum', $cache_name)){
		$coms = $db->Select('forum_posts', $where.(($where <> '' && $one_post <> '')?' and ':'').$one_post);
		SortArray($coms, 'public', false); //Сортируем по дате
		$navigation = new Navigation($page);
		if(count($coms) > $com_on_page && $EnNav){
			if($lastpost){
				$page = ceil(count($coms) / $com_on_page);
			}
			$navigation->FrendlyUrl = $config['general']['ufu'];
			$post_nav_url = Ufu($nav_url, 'forum/topic'.$topic_id.'-{page}.html', true);
			$navigation->GenNavigationMenu($coms, $com_on_page, $post_nav_url);
		}else{
			$navigation->DisableNavigation();
		}

		$basket = Forum_Basket_RenderBasket($coms);
		$i = 1;
		$i2 = 1;
		$num = 0;
		if($page > 1){
			$num = ($page * $com_on_page) - $com_on_page;
		}

		foreach($coms as $comment){
			$num++;
			$uid = $comment['user_id'];
			$vars = array();
			if($comment['delete'] == 0 || $config['forum']['basket'] == false){
				$vars['text'] = htmlspecialchars($comment['message']);
				if($no_link_guest){
					No_link_guest($vars['text']);
				}
				$vars['mpage'] = ($lastpost ? $page-1 : $page);
				SmiliesReplace($vars['text']);
				$vars['text'] = nl2br($vars['text']);
				$vars['text'] = BbCodePrepare($vars['text']);
				if(isset($config['forum']['max_word_length']) && $config['forum']['max_word_length'] > 0) {
					$vars['text'] = word_wrapped_string($vars['text'], $config['forum']['max_word_length']);
				}else{
					// $vars['text'] = $text;
				}
			}else{
				$vars['text'] = Forum_Basket_RenderBasketComAdmin($comment['id'], $comment['message'], $basket ) ;
			}
			if($uid != 0){
				$userinfo = GetUserInfo($uid);
				$vars['usertopics'] = '<a href="'.Ufu('index.php?name=forum&op=usertopics&user='.$uid, 'forum/usertopics/{user}/').'">'.$forum_lang['allusertopics'].'</a>' ;
				$vars['public'] = $comment['public'];
				if($userinfo['rank_name']<>'') {
					$vars['author_name'] = $userinfo['name'];
					$vars['author'] = '<a href="'.Ufu('index.php?name=user&op=userinfo&user='.$uid, 'user/{user}/info/').'">'.$userinfo['name'].'</a>';
				}else{
					$vars['author'] = $comment['name'];
					$vars['author_name'] = $comment['name'];
				}
				if($userinfo['hideemail'] == '0') {
					$vars['email'] = AntispamEmail($userinfo['email']);
				}else {
					$vars['email'] = '&nbsp;';
				}
				$vars['homepage'] = $userinfo['url'];
				$vars['icq'] = $userinfo['icq'];
				if($userinfo['online']) {
					$vars['status'] = $forum_lang['user_online'];
				}else {
					$vars['status'] = '';
				}
				$vars['rank_image'] = ($userinfo['rank_image']<>''?$userinfo['rank_image']:'');
				$vars['rank_name'] = ($userinfo['rank_name']<>''?$userinfo['rank_name']:'');

				$vars['avatar'] = ($userinfo['avatar_file']<>''?$userinfo['avatar_file']: GetPersonalAvatar(0));
				$vars['regdate'] = TimeRender($userinfo['regdate'], false, false);
				$ruser = true;
			}else {
				$vars['author'] = strip_tags($comment['name']);
				$vars['usertopics'] ='';

				$vars['public'] = $comment['public'];
				if($comment['email'] != '' && $comment['hide_email'] != 0) {
					$vars['email'] = AntispamEmail(strip_tags($comment['email']));
				}else {
					$vars['email'] = '&nbsp;';
				}
				if($comment['homepage'] != '') {
					$vars['homepage'] = strip_tags($comment['homepage']);
				}else {
					$vars['homepage'] = '';
				}
				if($comment['icq'] != '') {
					$vars['icq'] = strip_tags($comment['icq']);
				}else {
					$vars['icq'] = '&nbsp;';
				}
				$vars['rank_image'] = '';
				$vars['rank_name'] = '';
				$vars['avatar'] = GetPersonalAvatar(0);
				$vars['regdate'] = '';
				$ruser = false;
			}
			if($vars['homepage'] != ''){
				$vars['homepage'] = '<a href="http://'.$vars['homepage'].'" target="_blank">'.$vars['homepage'].'</a>';
			}else {
				$vars['homepage'] = '&nbsp;';
			}
			$vars['public'] = $forum_lang['added'].TimeRender($vars['public']);
			$vars['ip'] = SafeDB($comment['user_ip'], 19, str);
			$vars['topic_id'] = $topic_id;
			$vars['id'] = SafeDB($comment['id'], 11, int);
			$vars['nodelete'] = (SafeDB($comment['delete'], 1, int) ? false : true);
			$vars['page'] = $page;
			$vars['last'] = false;
			$vars['start'] = false;
			if($i2 == 1) {
				$vars['start'] = true;
			}

			if($i2 == count($coms)) {
				$vars['last'] = true;
			}
			$i2++;
			if($comment['delete'] == 0 ) {
				$vars['is_current_user'] = ($user->Get('u_id') == $comment['user_id'] || $user->isAdmin());
			}else{
				$vars['is_current_user'] = false;
			}
			if(!$user->isAdmin() and $topic_close){
				$vars['is_current_user'] = false;
			}
			if($one_post ==''){
				$vars['num'] = $num;
				$vars['url'] = "javascript:link_post('".GetSiteUrl().Ufu("index.php?name=forum&op=post&topic=".$topic_id."&post=".$comment['id'], 'forum/t{topic}/post{post}.html')."')";
			}else{
				$vars['url'] = 'javascript:history.go(-1)';
				$vars['num'] = '';
			}
			$site->AddSubBlock($blockname, true, $vars, array(), 'module/forum_post.html');
		}
		if($config['forum']['cache']){
			$mcache = array();
			if(isset($site->Blocks['navigation'])){
				$mcache['navigation'] = $site->Blocks['navigation'];
			}
			$mcache['blocks'] = $site->Blocks[$blockname];
			$cache->Write('forum', $cache_name, $mcache, $config['forum']['maxi_cache_duration']);
		}
	}else{
		$coms = $cache->Get('forum', $cache_name);
		if(isset($coms['navigation'])){
			$site->Blocks['navigation'] = $coms['navigation'];
		}
		$site->Blocks[$blockname] = $coms['blocks'];
	}
}

function Posts_RenderPostForm($edit = false, $forum = 0, $topic = 0, $id = 0,
		$value = '', $text_title='', $loadform = true, $close = false)
{
	global $site, $db, $config, $user, $forum_lang;
	if($edit){
		$site->AddTemplatedBox('', 'module/forum_edit_post.html');
	}else{
		$site->AddBlock('post_form', true, false, 'post_form', 'module/forum_edit_post.html');
	}
	if($user->Get('u_add_forum')){
		$site->AddBlock('forum_editpost_form', $loadform, false, 'form');
		$vars = array();
		$page = 1;
		if(isset($_GET['page'])){
			$page = SafeEnv($_GET['page'], 11, int);
		}

		if($edit){
			$vars['pag'] = $page;
			$vars['post'] = $id;
			$vars['text_value'] = $value;
			$vars['text_title'] = $text_title;
			$vars['edit_title'] = $text_title<>'';
			$vars['topic'] = $topic;
			$vars['title'] = $forum_lang['edit_post'];
			$vars['url'] = Ufu("index.php?name=forum&op=savepost&topic=$topic&post=$id&page=$page", 'forum/savepost/topic{topic}/post{post}-{page}/');
			$vars['edit'] = true;
			$vars['lsubmit'] = $forum_lang['save'];
			$vars['lsubmit_title'] = $forum_lang['save_edit'];
			$vars['visibility'] = 'visible';
		}else{
			$vars['post'] = 0;
			$vars['topic'] = $topic;
			$vars['forum'] = $forum;
			$vars['edit_title'] = $text_title<>'';
			$vars['text_value'] = '';
			$vars['title'] = $forum_lang['add_post'];
			$vars['url'] = Ufu("index.php?name=forum&op=addpost&topic=$topic&forum=$forum", 'forum/addpost/{forum}/topic{topic}/');
			$vars['edit'] = false;
			$vars['lsubmit'] = $forum_lang['add'];
			$vars['lsubmit_title'] = $forum_lang['add_post'];
			$vars['visibility'] = 'hidden';
		}
		$vars['add'] = !$vars['edit'];
		$site->Blocks['forum_editpost_form']['vars'] = $vars;
		ForumSmile();
		$site->AddBlock('files', false ,true, 'u_files');
	}
}
