<?php

function IndexForumEditPost() {
	global $db, $site, $user, $config, $lang;
	if(!$user->Auth){
		$site->AddTextBox($lang['forum'], '<center>'.$lang['error_auth'].'</center>');
		return;
	}
	$post_id = SafeEnv($_GET['post'], 11, int);
	$topic = SafeEnv($_GET['topic'], 11, int);
	$db->Select('forum_topics', "`id`='$topic'");
	$topic = $db->FetchRow();
	if($topic['delete'] == 0 || $config['forum']['basket'] == false){
		$title = '';
		if(SafeDB($topic['starter_id'],11,str) == $user->Get('u_id') || $user->IsAdmin()){
			$title=SafeDB($topic['title'],0,str);
		}
		$db->Select('forum_posts', "`id`='$post_id'");
		$post = $db->FetchRow();
		if($post['delete'] == 0 or $config['forum']['basket'] == false){
			if($user->Get('u_id') == $post['user_id'] || $user->isAdmin()){
				Posts_RenderPostForm(true, 0, SafeEnv($_GET['topic'],11,int), SafeDB($post['id'],11,int), SafeDB($post['message'],0,str,false,true),$title);
			}else{
				$site->AddTextBox($lang['forum'], '<center>'.$lang['no_right_comment_edit'].'</center>');
				return;
			}
		}else{
			$site->AddTextBox($lang['post_basket'], '<center> '.$lang['post_basket_no_edit'].'<br><input type="button" value="'.$lang['back'].'" onclick="history.back();"></center>');
		}
	}else{
		$site->AddTextBox($lang['topic_basket_current_post'], '<center> '.$lang['topic_basket_post'].'<br><input type="button" value="'.$lang['back'].'" onclick="history.back();"></center>');
	}
}
