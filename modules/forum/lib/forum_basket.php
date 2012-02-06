<?php
# LinkorCMS
# LinkorCMS Development Group
# www.linkorcms.ru
# Лицензия LinkorCMS 1.2.
# работа с удаляемыми через "корзину"	 объектами

global  $config;
// Автоочистка корзины
if(isset($config['forum']['basket'])){
	global $db, $user;
	if($config['forum']['basket']){
		if($config['forum']['clear_basket_day'] < 1){
			$config['forum']['clear_basket_day'] = 1;
		}
		if($config['forum']['del_auto_time'] < time()){
			$clear_cache = false;
			$db->Select('config_groups', "`name`='forum'");
			$group = $db->FetchRow();
			$m_time = time() + Day2Sec;
			$m_group = $group['id'];
			$delete_date = time() - (Day2Sec * $config['forum']['clear_basket_day']);
			$db->Update('config', "`value`='$m_time'","`name`='del_auto_time' and `group_id`='$m_group'");

			$mdb = array();
			$mdb = $db->Select('forum_basket_topics', "`date`<'$delete_date'");
			if(count($mdb)>0){
				$clear_cache = true;
				foreach($mdb as $d_topic){
					ForumAdminDeleteTopic(SafeDb($d_topic['obj_id'], 11, int));
				}
			}
			$db->Delete('forum_basket_topics', "`date`<'$delete_date'");

			$mdb = array();
			$mdb = $db->Select('forum_basket_post', "`date`<'$delete_date'");
			if(count($mdb)>0){
				$clear_cache = true;
				foreach($mdb as $d_post){
					ForumAdminDeletePost(SafeDb($d_post['obj_id'], 11, int));
				}
			}
			$db->Delete('forum_basket_post', "`date`<'$delete_date'");

			if($clear_cache){
				Forum_Cache_ClearAllCacheForum();
			}
		}
	}
}

/*
* Восстановить помеченое и вернуть статистику  для всего
*/
function Forum_Basket_RestoreBasketAll() {
	global $db, $config;
	if(isset($config['forum']['basket'])){
		$db->Select('config_groups', "`name`='forum'");
		$group = $db->FetchRow();
		$m_group = $group[0];
		$db->Update('config', "`value`='0'", "`name`='del_auto_time' and `group_id`='$m_group'");
		$db->Delete('forum_basket_topics');
		$db->Delete('forum_basket_post');

		$mdb = $db->Select('forum_topics', "`delete`='1'");
		if(count($mdb) > 0){
			$m_topics = array();
			foreach($mdb as $topic){
				$forum_id = SafeDB($topic['forum_id'],11,int);
				$db->Select('forums',"`id`='$forum_id'");
				$forum = $db->FetchRow();
				$ftopics = (int)$forum['topics'] + 1;
				if($ftopics < 0){
					$ftopics = 0;
				}
				$fposts = (int)$forum['posts'] + (int)$topic['posts'];
				if($fposts < 0){
					$fposts = 0;
				}
				$fset = "`topics`='$ftopics',`posts`='$fposts'";
				$db->Update('forums', $fset, "`id`='$forum_id'");
			}
		}

		$mdb = array();
		$mdb = $db->Select('forum_posts',"`delete`='1'");
		if(count($mdb)>0){
			$m_post = array();
			foreach($mdb as $post){
				$post_id = $post['id'];
				$topic_id = $post['object'];
				$db->Select('forum_topics',"`id`='$topic_id'");
				$topic = $db->FetchRow();
				$tposts = (int)$topic['posts'] + 1;
				if($tposts < 0){
					$tposts = 0;
				}
				$db->Update('forum_topics',"`posts`='$tposts'","`id`='$topic_id'");
				$forum_id = SafeDB($topic['forum_id'],11,int);
				$db->Select('forums',"`id`='$forum_id'");
				$forum = $db->FetchRow();
				$fposts = (int)$forum['posts'] + 1;
				if($fposts < 0){
					$fposts = 0;
				}
				$db->Update('forums',"`posts`='$fposts'","`id`='$forum_id'");
			}
		}

		$db->Update('forum_topics',"`delete`='0'","`id`>'0'");
		$db->Update('forum_posts',"`delete`='0'","`id`>'0'");

		Forum_Cache_ClearAllCacheForum();
	}
}


function Forum_Basket_RenderBasket($coms = array(),$table= 'forum_basket_post'){
	global $user, $db, $config;

	$basket = array();
	if($config['forum']['basket'] == true){
		$basket['table'] = $table;
		if($user->isAdmin()){
			$mwhere = '';
			foreach($coms as $mpost){
				$mwhere .= "`obj_id`='".$mpost['id']."' or ";
			}
			if($mwhere <> ''){
				$mwhere = substr($mwhere, 0, -3);
				$forum_basket = $db->Select($table, $mwhere);
				if(count($forum_basket) > 0){
					foreach($forum_basket as $mbasket){
						$basket[$mbasket['obj_id']] = $mbasket;
					}
				}
			}
		}
	}

	return  $basket;
}


function Forum_Basket_RenderBasketComAdmin($id = 0,$text= '', $basket = array() , $full = true, $next = true) {
	global $user, $config, $lang;
	static $num_del = 0;
	$out_text =$text;
	if($config['forum']['basket'] == true) {
		$deltext = '';
		$deltext = htmlspecialchars($text);
		SmiliesReplace($deltext);
		$deltext = nl2br($deltext);
		$deltext = BbCodePrepare($deltext);
		$del_admin = '';
		$com_admin = '';
		$del_time = '';
		if(isset($basket[$id])) {
			$ainfo = GetUserInfo($basket[$id]['user']);
			$del_admin = $lang['deleted'].':<A HREF="index.php?name=user&op=userinfo&user='.$basket[$id]['user'].'"> '.$ainfo['name'].'</A>';
			$del_time =$lang['basket_delete_forever'].' '.TimeRender($basket[$id]['date']+(86400*$config['forum']['clear_basket_day']), false, false);
			if(trim($basket[$id]['reason'])<>'') {
				$com_admin = $basket[$id]['reason'];
				$com_admin = '<FONT  COLOR="#FF0000">'.$lang['reason'].'</FONT>:<BR>'.BbCodePrepare($com_admin);
			}
			$num_del++;
			$out_text=($full?$lang['basket_removed_in_basket_message']:$lang['basket_removed_in_basket_message_smile']).
			($full?'<BR>'.$del_admin.'<BR>'.$del_time.'.<BR>'.$com_admin.'<HR>'.
			($next?'<a href="#" onclick="ShowHide(\'delete_com'.$num_del.'\'); return false;">'.
			$lang['basket_see'] .'</a>&nbsp;|&nbsp;<a href="index.php?name=forum&op=restore_basket&'.
			$basket['table'].'='.$id.'">'.$lang['restore'].'</a><div  align="left" id="delete_com'.
			$num_del.'" style="visibility: hidden; display: none; "><BR>'.$deltext .'</div>':''):'');
		}
	}
	return $out_text;
}
