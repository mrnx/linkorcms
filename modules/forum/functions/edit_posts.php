<?php

function IndexForumEditPosts(){
	global $db, $site, $user, $config, $forum_lang;
	if(!$user->Auth){
		$site->AddTextBox($forum_lang['forum'], '<center>'.$forum_lang['error_auth'].'</center>');
		return;
	}
	if(!$user->isAdmin()){
		$site->AddTextBox($forum_lang['forum'], '<center>'.$forum_lang['error_no_right_edit'].'</center>');
		return;
	}
	if(!isset($_POST['posts'])){
		$backurl = $_SERVER['HTTP_REFERER'];
		$site->AddTextBox($forum_lang['forum'], '<center>'.$forum_lang['error_no_messages'].'</center><br /><a href="javascript:history.go(-1)">'.$site->Button($forum_lang['back']).'</a>');
		return;
	}
	if(isset($_GET['ok']) && $_GET['ok'] == '1'){
		$posts = explode(',', $_POST['posts']);
		$posts = implode("' or`id`='", $posts);
		$posts	= "`id`='".$posts."'";
		$backurl = $_POST['backurl'];

		Moderation_GetPostsDo(SafeEnv($_GET['edit'], 255, str));
		GO($backurl);
	}else{
		$posts = array();
		foreach(array_keys($_POST['posts']) as $post){
			$post = intval($post);
			$posts[$post] = $post;
		}
		unset($post);
		$do = SafeEnv($_POST['do'], 0, str);
		$hide = implode(',', $posts);
		$backurl = $_SERVER['HTTP_REFERER'];
		$vars['lang_premoderation'] = $forum_lang['moderation_messages'];
		$vars['posts_count'] = count($posts);
		$vars['form_action'] = 'index.php?name=forum&op=edit_posts&edit='.$do.'&ok=1'; // Без UFU
		$vars['form_name'] = 'forum_delete';
		$vars['posts'] = $hide;
		$vars['backurl'] = $backurl;
		$vars['reason'] = $config['forum']['basket'] && $do == 'deleteposts';
		if($do == 'deleteposts'){
			$vars['lang_do'] = $forum_lang['delete_posts'];
		}else{
			$vars['lang_do'] = $forum_lang['merge_posts'];
		}
		$site->AddTemplatedBox($vars['lang_do']." [{$vars['posts_count']}]", 'module/forum_moderation.html');
		$site->AddBlock('forum_moderation', true, false, 'mod');
		$site->Blocks['forum_moderation']['vars'] = $vars;
	}
}
