<?php

function IndexForumEditTopic() {
	global $db, $site, $user, $config, $lang;
	if(!$user->Auth) {
		$site->AddTextBox($lang['forum'], '<center>'.$lang['error_auth'].'</center>');
		return;
	}
	$topic = SafeEnv($_GET['topic'],11,int);
	$post = $db->Select('forum_posts', "`object`='$topic' and `delete`='0'");
	SortArray($post, 'id', false);

	$db->Select('forum_topics', "`id`='$topic'");

	$topic = $db->FetchRow();
	if( $topic['delete'] == 0 or $config['forum']['basket'] == false ) {
		$title='';
		if(SafeDB($topic['starter_id'],11,str)==$user->Get('u_id') or  $user->IsAdmin())
			$title=SafeDB($topic['title'], 0, str);

		if($user->Get('u_id') == $post[0]['user_id'] || $user->isAdmin()) {
			Posts_RenderPostForm(true, 0, SafeEnv($_GET['topic'],11,int), SafeDB($post[0]['id'],11,int), SafeDB($post[0]['message'],0,str,false,true),$title);
		}else {
			$site->AddTextBox($lang['forum'], '<center>'.$lang['no_right_comment_edit'].'</center>');
			return;
		}
	}
	else {
		$site->AddTextBox($lang['topic_basket_current_post'], '<center>'.$lang['topic_basket_post'].'</BR><input type="button" value="'.$lang['back'].'"onclick="history.back();"></center>');
	}
}

?>