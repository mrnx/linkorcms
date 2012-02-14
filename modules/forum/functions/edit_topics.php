<?php

function IndexForumEditTopics(){
	global $db, $site, $user, $config, $forum_lang;
	if(!$user->Auth){
		$site->AddTextBox($forum_lang['forum'], '<center>'.$forum_lang['error_auth'].'</center>');
		return;
	}
	if(!$user->isAdmin()){
		$site->AddTextBox($forum_lang['forum'], '<center>'.$forum_lang['error_no_right_edit'].'.</center>');
		return;
	}
	if(!isset($_POST['topics'])){
		$backurl = $_SERVER['HTTP_REFERER'];
		$site->AddTextBox($forum_lang['forum'], '<center>'.$forum_lang['error_no_topics'].'</center><br><a href="javascript:history.go(-1)">'.$site->Button('Назад').'</a>');
		return;
	}
	if(isset($_GET['ok']) && SafeEnv($_GET['ok'],1,int)=='1'){
		$topics = explode(',',$_POST['topics']);
		$ed_top = implode("' or`id`='",$topics);
		$ed_top	= "`id`='".$ed_top."'";

		$e_t = $db->Select('forum_topics', $ed_top);
		$text = '';
		foreach($e_t as $topic){
			$text .= $topic['title'].'<br />';
		}
		$backurl = $_POST['backurl'];
		$site->AddTextBox('<br />'.$forum_lang['executed'].' :&nbsp'.Moderation_GetDo(SafeEnv($_GET['edit'],255,str), true), '<span allign="left">'.$text.'</span><br><br><a href="'.$backurl.'"><b>'.$forum_lang['return_read'].'</b></a>');
	}else{
		$topics = array();
		foreach(array_keys($_POST['topics']) as $topic){
			$topic = intval($topic);
			$topics["$topic"] = $topic;
		}
		unset($topic);
		$hide = implode(',',$topics);
		$ed_top = implode("' or`id`='",$topics);
		$ed_top	= "`id`='".$ed_top."'";
		$e_t = $db->Select('forum_topics', $ed_top);
		$text = '';
		foreach( $e_t as $topic){
			$text .= $topic['title'].'<br>';
		}
		$backurl = $_SERVER['HTTP_REFERER'];
		$text2 = $site->FormOpen('index.php?name=forum&op=edit_topics&edit='.SafeEnv($_POST['do'],0,str).'&ok=1','post') // Без UFU
			.$site->Hidden('topics',$hide)
			.$site->Hidden('backurl',$backurl);
		$site->AddTextBox($text2.Moderation_GetDo($_POST['do']).' :&nbsp;'.$forum_lang['confirm'], '<span allign="left">'.$text.'</span><br><br>'.$site->Submit($forum_lang['execute']).'<a href="javascript:history.go(-1)">'.$site->Button($forum_lang['back']).'</a>');
	}
}
