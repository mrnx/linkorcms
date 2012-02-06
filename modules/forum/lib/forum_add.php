<?php
# LinkorCMS
# LinkorCMS Development Group
# www.linkorcms.ru
# Лицензия LinkorCMS 1.2.
# Запись сообщений и тем

// Обрабатывает и добавляет сообщение.
function Forum_Add_AddPost2( $topic = null ) {
	global $db, $config, $user, $lang;
	$er = array();
	$text = '';
	if($topic == null) {
		if(isset($_GET['topic'])) {
			$object = SafeEnv($_GET['topic'], 11, int);
		}else {
			$er[] = $lang['error'].': !isset($_GET[\'topic\']).';
		}
	}else {
		$object = $topic;
	}
	if((!isset($_POST['name'])
					|| !isset($_POST['email'])
					|| !isset($_POST['site'])
					|| !isset($_POST['icq'])
					|| !isset($_POST['text']))
			&& !$user->Auth) {
		$er[] = $lang['no_data'];
	}else {
		if($user->Auth) {
			$name = $user->Get('u_name');
			$email = $user->Get('u_email');
			$hideemail = $user->Get('u_hideemail');
			$site = $user->Get('u_homepage');
			$icq = $user->Get('u_icq');
			$uid = $user->Get('u_id');
		}else {
			$er[] =$lang['no_reg_add'];
		}
		$text = SafeEnv($_POST['text'], 0, str);
		if(strlen($text) == 0) {
			$er[] =$lang['error_no_message'];
		}
	}
	if(!$user->Get('u_add_forum')) {
		$er[] =$lang['blocking'];
	}
	if(count($er) == 0) {
		$mdb = $db->Select('forum_topics', "`id`='$object'");
		if( count($mdb)>0 and $mdb[0]['delete'] == 0 or  $config['forum']['basket'] == false) {
			if($mdb[0]['close_topics'] == 1) {
				$er[] = $lang['topic_close_for_discussion'].'.'.$lang['no_create_new_message_current_topic_add'];
				return $er;
			}
			$m_time	= time();
			$vals = Values('', $object, $uid, $m_time, $name, $site, $email, $hideemail, $icq, $text, getip(), 0);
			$db->Insert('forum_posts', $vals);
		}
		else {
			$er[] = $lang['topic_basket'].'.'.$lang['no_topic_basket_edit'];
			return $er;
		}
	}
}
