<?php

function IndexForumSavePost() {
	global $db, $site, $user,$config, $lang, $UFU;
	if(!$user->Auth) {
		$site->AddTextBox($lang['forum'], '<center>'.$lang['error_auth'].'</center>');
		return;
	}
	$post_id = SafeEnv($_GET['post'],11,int);
	$db->Select('forum_posts', "`id`='$post_id'");
	$post = $db->FetchRow();
	if($post['delete'] == 0 or $config['forum']['basket'] == false){
		$db->Select('forum_topics', "`id`='".SafeDB($post['object'], 11, int)."'");
		$topic = $db->FetchRow();
		if($topic['delete'] == 0 or $config['forum']['basket'] == false){
			if($user->Get('u_id') == $post['user_id'] || $user->isAdmin()){
				$topic_id = SafeEnv($_GET['topic'],11,int);
				$text_value = SafeEnv($_POST['text'], 100000, str);
				$text_title ='';
				if(isset($_POST['title']))
					$text_title = SafeEnv($_POST['title'],255,str);
				$db->Update('forum_posts',"`message`='$text_value'","`id`='$post_id'");
				if(isset($_POST['title'])) {
					$object = SafeDB($post['object'], 11, int);
					$db->Update('forum_topics',"`title`='$text_title'", "`id`='$object'");
				}

				$page = 1 ;
				if(isset($_GET['page']))
					$page=safeenv($_GET['page'], 11 ,int);
				Forum_Cache_ClearAllCacheForum();
				if(!$UFU) {
					GO('index.php?name=forum&op=showtopic&topic='.$topic_id.'&page='.$page.'#'.$post_id);
				}else{
					GO($config['general']['site_url'].'forum/topic'.$topic_id.'-'.$page.'.html#'.$post_id);
				}
			}else{
				$site->AddTextBox($lang['forum'], '<center>'.$lang['no_right_comment_edit'].'</center>');
				return;
			}
		}else{
			$site->AddTextBox($lang['topic_basket_current_post'], '<center>'.$lang['topic_basket_post'].'.</BR><input type="button" value="'.$lang['back'].'"onclick="history.back();"></center>');
		}
	}else{
		$site->AddTextBox($lang['post_basket'], '<center>'.$lang['post_basket_no_edit'].'.</BR><input type="button" value="'.$lang['back'].'"onclick="history.back();"></center>');
	}
}

?>