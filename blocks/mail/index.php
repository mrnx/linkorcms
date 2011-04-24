<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$bcache = LmFileCache::Instance();
if($bcache->HasCache('block', 'mail')){
	$tempvars['content'] = 'block/content/mail.html';
	$vars = $bcache->Get('block', 'mail');
}else{
	$topic_id = SafeDB($block_config, 11, int);
	$db->Select('mail_topics', "`id`='$topic_id'");
	if($db->NumRows() > 0){
		$topic = $db->FetchRow();
		$tempvars['content'] = 'block/content/mail.html';
		$vars['title'] = SafeDB($title, 255, str);
		$vars['form_action'] = Ufu('index.php?name=mail&op=subscribe', 'mail/{op}/');
		$vars['topic_id'] = $topic['id'];
		$vars['lmail_title'] = '����������� �� �������� ';
		$vars['mail_title'] = $topic['title'];
		$vars['lemail'] = '��� e-mail';
		$vars['lsubmit'] = '�����������';
		$vars['lother'] = '������ ��������';
		$vars['lformat'] = '������';
		$vars['lhtml'] = 'HTML';
		$vars['ltext'] = '�����';
		$bcache->Write('block', 'mail', $vars);
	}elseif($user->Auth && $user->isAdmin()){
		$vars['title'] = SafeDB($title, 255, str);
		$vars['content'] = '<center><font color="#FF0000">���� �������� ��� ����� �� �������. ���������� �������� ����� ���� ��� �����.</font></center>';
	}else{
		$enabled = false;
	}
}

?>