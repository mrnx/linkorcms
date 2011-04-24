<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$site->SetTitle('�������� �����');

if(isset($_GET['op'])){
	$op = $_GET['op'];
}else{
	$op = 'main';
}

switch($op){
	case 'main':
		IndexGBMain();
		break;
	case 'add':
		IndexNewsAddCommentSave();
		break;
	case 'save':
		IndexGBAddMsgSave();
		break;
	default:
		HackOff();
}

function GBCheckFlood()
{
	global $db, $config;
	$flood_time = $config['gb']['floodtime'];
	$db->Select('guestbook', "`user_ip`='".getip()."' and `date`>'".(time() - $flood_time)."'");
	if($db->NumRows() > 0){
		return true;
	}else{
		return false;
	}
}

//����� ���������
function IndexGBAddMessage( &$msg )
{
	global $site, $user, $config;
	// ��� � ����������� �����
	if($msg['email'] != '' && $msg['hide_email'] != '1'){
		$vars['name'] = '<a href="mailto:'.SafeDB($msg['email'], 50, str).'">'.SafeDB($msg['name'], 50, str).'</a>';
		$vars['name2'] = SafeDB($msg['name'], 50, str);
		$vars['email'] = '<a href="mailto:'.SafeDB($msg['email'], 50, str).'"><img src="images/buttons/email.gif" /></a>';
		$vars['email2'] = SafeDB($msg['email'], 50, str);
	}else{
		$vars['name'] = SafeDB($msg['name'], 50, str);
		$vars['email'] = '';
		$vars['email2'] ='';
	}

	// ����
	if($msg['url'] != ''){
		$url = UrlRender(SafeDB($msg['url'], 255, str));
		$vars['url'] = '<a href="'.$url.'" target="_blank"><img src="images/buttons/www.gif" /></a>';
		$vars['url2'] = $url;
	}else{
		$vars['url'] = '';
		$vars['url2'] ='';
	}

	// �����
	if($msg['icq'] != ''){
		$vars['icq'] = '<a href="http://web.icq.com/'.SafeDB($msg['icq'], 255, str).'" target="_blank"><img src="images/buttons/icq.gif" /></a>';
		$vars['icq2'] = SafeDB($msg['icq'], 255, str);
	}else{
		$vars['icq'] = '';
		$vars['icq2'] = '';
	}

	// ������
	if(trim($msg['answers']) == ''){
		$answers = array();
	}else{
		$answers = unserialize($msg['answers']);
	}

	// ������� ��� ��������������

	$id = SafeDB($msg['id'], 11, int);
	$vars['access_answer'] = $user->CheckAccess2('guestbook', 'answer');
	$vars['edit_answer_url'] = $config['admin_file'].'?exe=guestbook&amp;a=editanswer&amp;id='.$id;
	$vars['delete_answer_url'] = $config['admin_file'].'?exe=guestbook&amp;a=delanswer&amp;id='.$id;
	$vars['add_answer_url'] = $config['admin_file'].'?exe=guestbook&amp;a=addanswer&amp;id='.$id;
	$vars['edit_message_url'] = $config['admin_file'].'?exe=guestbook&amp;a=edit&amp;id='.$id;
	$vars['delete_message_url'] = $config['admin_file'].'?exe=guestbook&amp;a=delete&amp;id='.$id.'&amp;ok=0';
	if($user->isAdmin()){
		$func = '';
		$msg_func = '';
		if(key_exists($user->Name(), $answers)){
			if($vars['access_answer']){
				$func = '<a href="'.$vars['edit_answer_url'].'">������������� �����</a> :: '
				.'<a href="'.$vars['delete_answer_url'].'">������� �����</a>';
			}else{
				$func = '';
			}
		}elseif($vars['access_answer']){
			$func = '<a href="'.$vars['add_answer_url'].'">��������</a>';
		}
		$msg_func = ($func != '' ? ' :: ' : '').'<a href="'.$vars['edit_message_url'].'">������������� ���������</a> :: '
				.'<a href="'.$vars['delete_message_url'].'">������� ���������</a>';
		$vars['admin'] = $func.$msg_func;
	}else{
		$vars['admin'] = '';
	}

	$keys = array_keys($answers);
	$answerstext = '';
	if(count($answers) > 0){
		$answerstext = '������: <ul style="margin:3px;margin-left:16px;">'."\n";
		foreach($keys as $key){
			$answerstext .= '<li>'.$key.' - '.$answers[$key]."\n";
		}
		$answerstext .= '</ul>'."\n";
	}

	$vars['date'] = TimeRender(SafeDB($msg['date'], 11, int));
	$vars['text'] = SafeDB($msg['message'], 0, str);
	$vars['answers'] = $answerstext;
	$site->AddSubBlock('guestbook', true, $vars);
}

function IndexGBMain()
{
	global $db, $config, $site, $userAuth;
	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 10, int);
	}else{
		$page = 1;
	}
	$msgs = $db->Select('guestbook', '`premoderate`=\'1\'');
	SortArray($msgs, 'date', true);
	$num = $config['gb']['msgonpage'];
	$navigation = new Navigation($page);
	$navigation->FrendlyUrl = $config['general']['ufu'];
	$navigation->GenNavigationMenu($msgs, $num, Ufu('index.php?name=guestbook', 'guestbook/page{page}/', true));

	$site->AddBlock('guestbook', true, true, 'gb');
	if(count($msgs) > 0){
		foreach($msgs as $message){
			IndexGBAddMessage($message);
		}
	}else{
		$site->AddTextBox('', '<center>��������� ���� ���.</center>');
	}
	$site->AddTemplatedBox('', 'module/guestbook.html');
	IndexGBAddForm($config['gb']['formposition'] == 'top');
}

function IndexGBAddForm( $top = false )
{
	global $site, $user, $config, $userAuth;
	$vars['lname'] = '���� ���*: ';
	$vars['name'] = ($userAuth && $user->isDef('u_name') ? $user->Get('u_name') : '');
	$vars['lemail'] = '��� e-mail*: ';
	$vars['email'] = ($userAuth && $user->isDef('u_email') ? $user->Get('u_email') : '');
	$vars['lhideemail'] = '������ ��� e-mail �� �����������: ';
	if($userAuth){
		$vars['hideemail'] = ($user->isDef('u_hideemail') ? ' checked' : '');
	}else{
		$vars['hideemail'] = ' checked';
	}
	$vars['lsite'] = '��� ����: ';
	$vars['site'] = ($userAuth && $user->isDef('u_homepage') ? $user->Get('u_homepage') : '');
	$vars['licq'] = '��� ICQ: ';
	$vars['icq'] = ($userAuth && $user->isDef('u_icq') ? $user->Get('u_icq') : '');
	$vars['ltext'] = '���� ���������*: ';
	$vars['text'] = '';
	$vars['scaption'] = '��������';
	$vars['hcaption'] = '�������� ���������';
	$vars['title'] = '�������� ���� ���������: ';
	$vars['action'] = Ufu('index.php?name=guestbook&op=save', 'guestbook/{op}/');

	// �����
	$vars['show_kaptcha'] = !$user->Auth || ($config['gb']['show_captcha'] == '1' && !$user->isAdmin());
	$vars['kaptcha_url'] = 'index.php?name=plugins&p=antibot';
	$vars['kaptcha_width'] = '120';
	$vars['kaptcha_height'] = '40';

	if($top){
		$site->AddBlock('bottomgbform', false, false);
		$site->AddBlock('topgbform', true, false, 'form', 'module/guestbookform.html');
		$site->Blocks['topgbform']['vars'] = $vars;
	}else{
		$site->AddBlock('topgbform', false, false);
		$site->AddBlock('bottomgbform', true, false, 'form', 'module/guestbookform.html');
		$site->Blocks['bottomgbform']['vars'] = $vars;
	}
}

function IndexGBAddMsgSave()
{
	global $db, $config, $site, $user, $userAuth;
	$r = array();
	$er = array();
	if(!isset($_GET['name']) || !isset($_POST['email']) || !isset($_POST['site']) || !isset($_POST['icq']) || !isset($_POST['text'])){
		$er[] = '������ �� ����������������.';
	}
	if(GBCheckFlood()){
		$er[] = '���� ������, ��������� �������.';
	}
	if(strlen($_POST['name']) == 0){
		$er[] = '�� �� ����� ���.';
	}
	if(strlen($_POST['email']) == 0){
		$er[] = '�� �� ����� ���� e-mail.';
	}elseif(!CheckEmail($_POST['email'])){
		$er[] = '�� ��������� ������ ��� ����� e-mail.';
	}
	if(strlen($_POST['text']) == 0){
		$er[] = '�� �� ����� ����� ���������, ���� ��������� ������� ��������.';
	}
	if($_POST['icq'] != ''){
		if(!is_numeric($_POST['icq'])){
			$er[] = '��� ����� ICQ ������ �������� ������ �� �����.';
		}
	}

	// ��������� �����
	if(!$user->Auth || (!$user->isAdmin() && $config['gb']['show_captcha'])){
		if(!$user->isDef('captcha_keystring') || $user->Get('captcha_keystring') != $_POST['keystr']){
			$er[] = '�� �������� ��� ����� ���� � ��������.';
		}
	}

	if(count($er) == 0){
		if(isset($_POST['hideemail'])){
			$hideemail = '1';
		}else{
			$hideemail = '0';
		}
		if($userAuth == 1 || !$config['gb']['moderation']){
			$moderated = 1;
		}else{
			$moderated = 0;
		}
		$vals = Values('', SafeEnv($_POST['name'], 50, str, true), SafeEnv($_POST['email'], 50, str, true), $hideemail, SafeEnv(Url($_POST['site']), 250, str, true), SafeEnv($_POST['icq'], 15, str, true), SafeEnv($_POST['text'], $config['gb']['msgmaxlen'], str, true), '', time(), getip(), $moderated);
		$db->Insert('guestbook', $vals);
		$user->ChargePoints($config['points']['gb_public']);
		if($userAuth == '1' || !$config['gb']['moderation']){
			GO(GetSiteUrl().Ufu('index.php?name=guestbook', '{name}/'));
		}else{
			$text = '<center><br />�������! ���� ��������� ����� ��������� ����� ���������.<br /><br />';
			$text .= '<input type="button" value="�����" onclick="history.back();"><br /></center>';
			$site->AddTextBox('', $text);
		}
	}else{
		$text = '���� ��������� �� ��������� �� ��������� ��������:<br /><ul>';
		foreach($er as $error){
			$text .= '<li>'.$error;
		}
		$text .= '</ul><center><input type="button" value="�����" onclick="history.back();"></center>';
		$site->AddTextBox('', $text);
	}
}

?>