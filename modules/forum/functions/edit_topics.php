<?php

function IndexForumEditTopics(){
	global $db, $site, $user, $config,$lang;

	if(!$user->Auth){
		$site->AddTextBox($lang['forum'], '<center>'.$lang['error_auth'].'</center>');
		return;
	}
	if(!$user->isAdmin()){
		$site->AddTextBox($lang['forum'], '<center>'.$lang['error_no_right_edit'].'.</center>');
		return;
	}
	if(!isset($_POST['topics'])){
		$backurl = $_SERVER['HTTP_REFERER'];
		$site->AddTextBox($lang['forum'], '<center>'.$lang['error_no_topics'].'</center><BR><a href="javascript:history.go(-1)">'.$site->Button('Назад').'</a>');
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
		$site->AddTextBox('<br />'.$lang['executed'].' :&nbsp'.Moderation_GetDo(SafeEnv($_GET['edit'],255,str), true), '<span allign="left">'.$text.'</span><br /><br /><a href="'.$backurl.'"><b>'.$lang['return_read'].'</b></a>');
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
			$text .= $topic['title'].'<br />';
		}
		$backurl = $_SERVER['HTTP_REFERER'];
		$text2 = $site->FormOpen('index.php?name=forum&op=edit_topics&edit='.SafeEnv($_POST['do'],0,str).'&ok=1','post').$site->Hidden('topics',$hide).$site->Hidden('backurl',$backurl);
		$site->AddTextBox($text2.Moderation_GetDo($_POST['do']).' :&nbsp;'.$lang['confirm'], '<span allign="left">'.$text.'</span><BR><BR>'.$site->Submit($lang['execute']).'<a href="javascript:history.go(-1)">'.$site->Button($lang['back']).'</a>');
	}
}

?>