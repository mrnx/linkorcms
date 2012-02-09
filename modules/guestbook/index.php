<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$site->SetTitle('Гостевая книга');

if(isset($_GET['op'])){
	$op = $_GET['op'];
}else{
	$op = 'main';
}

switch($op){
	case 'main':
		IndexGBMain();
		break;
	case 'save':
		IndexGBAddMsgSave();
		break;
	default:
		HackOff();
}

function GBCheckFlood(){
	global $db, $config;
	$flood_time = $config['gb']['floodtime'];
	$db->Select('guestbook', "`user_ip`='".getip()."' and `date`>'".(time() - $flood_time)."'");
	if($db->NumRows() > 0){
		return true;
	}else{
		return false;
	}
}

function IndexGBAddMessage( &$msg ){
	global $config;
	// Имя и электронная почта
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

	// Сайт
	if($msg['url'] != ''){
		$url = UrlRender(SafeDB($msg['url'], 255, str));
		$vars['url'] = '<a href="'.$url.'" target="_blank"><img src="images/buttons/www.gif" /></a>';
		$vars['url2'] = $url;
	}else{
		$vars['url'] = '';
		$vars['url2'] ='';
	}

	// Аська
	if($msg['icq'] != ''){
		$vars['icq'] = '<a href="http://web.icq.com/'.SafeDB($msg['icq'], 255, str).'" target="_blank"><img src="images/buttons/icq.gif" /></a>';
		$vars['icq2'] = SafeDB($msg['icq'], 255, str);
	}else{
		$vars['icq'] = '';
		$vars['icq2'] = '';
	}

	// Ответы
	if(trim($msg['answers']) == ''){
		$answers = array();
	}else{
		$answers = unserialize($msg['answers']);
	}

	// Функции для администратора

	$id = SafeDB($msg['id'], 11, int);
	$vars['access_answer'] = System::user()->CheckAccess2('guestbook', 'answer');
	$vars['edit_answer_url'] = ADMIN_FILE.'?exe=guestbook&amp;a=editanswer&amp;id='.$id;
	$vars['delete_answer_url'] = ADMIN_FILE.'?exe=guestbook&amp;a=delanswer&amp;id='.$id;
	$vars['add_answer_url'] = ADMIN_FILE.'?exe=guestbook&amp;a=addanswer&amp;id='.$id;
	$vars['edit_message_url'] = ADMIN_FILE.'?exe=guestbook&amp;a=edit&amp;id='.$id;
	$vars['delete_message_url'] = ADMIN_FILE.'?exe=guestbook&amp;a=delete&amp;id='.$id.'&amp;ok=0';
	if(System::user()->isAdmin()){
		$func = '';
		$msg_func = '';
		if(key_exists(System::user()->Name(), $answers)){
			if($vars['access_answer']){
				$func = '<a href="'.$vars['edit_answer_url'].'">Редактировать ответ</a> :: '
				.'<a href="'.$vars['delete_answer_url'].'">Удалить ответ</a>';
			}else{
				$func = '';
			}
		}elseif($vars['access_answer']){
			$func = '<a href="'.$vars['add_answer_url'].'">Ответить</a>';
		}
		$msg_func = ($func != '' ? ' :: ' : '').'<a href="'.$vars['edit_message_url'].'">Редактировать сообщение</a> :: '
				.'<a href="'.$vars['delete_message_url'].'">Удалить сообщение</a>';
		$vars['admin'] = $func.$msg_func;
	}else{
		$vars['admin'] = '';
	}

	$keys = array_keys($answers);
	$answerstext = '';
	if(count($answers) > 0){
		$answerstext = 'Ответы: <ul style="margin:3px;margin-left:16px;">'."\n";
		foreach($keys as $key){
			$answerstext .= '<li>'.$key.' - '.$answers[$key]."\n";
		}
		$answerstext .= '</ul>'."\n";
	}

	$vars['date'] = TimeRender(SafeDB($msg['date'], 11, int));
	$vars['text'] = SafeDB($msg['message'], 0, str);
	$vars['answers'] = $answerstext;
	System::site()->AddSubBlock('guestbook', true, $vars);
}

function IndexGBAddForm( $top = false ){
	global $site, $user, $config, $userAuth;
	$vars['lname'] = 'Ваше имя*: ';
	$vars['name'] = ($userAuth && $user->isDef('u_name') ? $user->Get('u_name') : '');
	$vars['lemail'] = 'Ваш e-mail*: ';
	$vars['email'] = ($userAuth && $user->isDef('u_email') ? $user->Get('u_email') : '');
	$vars['lhideemail'] = 'Скрыть ваш e-mail от посетителей: ';
	if($userAuth){
		$vars['hideemail'] = ($user->isDef('u_hideemail') ? ' checked' : '');
	}else{
		$vars['hideemail'] = ' checked';
	}
	$vars['lsite'] = 'Ваш сайт: ';
	$vars['site'] = ($userAuth && $user->isDef('u_homepage') ? $user->Get('u_homepage') : '');
	$vars['licq'] = 'Ваш ICQ: ';
	$vars['icq'] = ($userAuth && $user->isDef('u_icq') ? $user->Get('u_icq') : '');
	$vars['ltext'] = 'Ваше сообщение*: ';
	$vars['text'] = '';
	$vars['scaption'] = 'Добавить';
	$vars['hcaption'] = 'Добавить сообщение';
	$vars['title'] = 'Добавьте ваше сообщение: ';
	$vars['action'] = Ufu('index.php?name=guestbook&op=save', 'guestbook/{op}/');

	// Капча
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

function IndexGBMain(){
	global $config, $site;

	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 10, int);
	}else{
		$page = 1;
	}
	$msgs = System::database()->Select('guestbook', "`premoderate`='1'");

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
		$site->AddTextBox('', '<center>Сообщений пока нет.</center>');
	}
	$site->AddTemplatedBox('', 'module/guestbook.html');
	IndexGBAddForm($config['gb']['formposition'] == 'top');
}

function IndexGBAddMsgSave(){
	global $config, $site;
	$r = array();
	$er = array();
	if(!isset($_GET['name']) || !isset($_POST['email']) || !isset($_POST['site']) || !isset($_POST['icq']) || !isset($_POST['text'])){
		$er[] = 'Данные не инициализированы.';
	}
	if(GBCheckFlood()){
		$er[] = 'Флуд защита, подождите немного.';
	}
	if(strlen($_POST['name']) == 0){
		$er[] = 'Вы не ввели имя.';
	}
	if(strlen($_POST['email']) == 0){
		$er[] = 'Вы не ввели свой e-mail.';
	}elseif(!CheckEmail($_POST['email'])){
		$er[] = 'Вы совершили ошибку при вводе e-mail.';
	}
	if(strlen($_POST['text']) == 0){
		$er[] = 'Вы не ввели текст сообщения, либо сообщение слишком короткое.';
	}
	if($_POST['icq'] != ''){
		if(!is_numeric($_POST['icq'])){
			$er[] = 'Ваш номер ICQ должен состоять только из чисел.';
		}
	}

	// Проверяем капчу
	if(!System::user()->Auth || (!System::user()->isAdmin() && $config['gb']['show_captcha'])){
		if(!System::user()->isDef('captcha_keystring') || System::user()->Get('captcha_keystring') != $_POST['keystr']){
			$er[] = 'Вы ошиблись при вводе кода с картинки.';
		}
	}

	if(count($er) == 0){
		if(isset($_POST['hideemail'])){
			$hideemail = '1';
		}else{
			$hideemail = '0';
		}
		if(System::user()->isAdmin() || !$config['gb']['moderation']){
			$moderated = 1;
		}else{
			$moderated = 0;
		}
		$name = SafeEnv($_POST['name'], 50, str, true);
		$email = SafeEnv($_POST['email'], 50, str, true);
		$_site = SafeEnv(Url($_POST['site']), 250, str, true);
		$icq = SafeEnv($_POST['icq'], 15, str, true);
		$text = SafeEnv($_POST['text'], $config['gb']['msgmaxlen'], str, true);
		$vals = Values('', $name, $email, $hideemail, $_site, $icq, $text, '', time(), getip(), $moderated);
		System::database()->Insert('guestbook', $vals);
		System::user()->ChargePoints($config['points']['gb_public']);
		if(System::user()->isAdmin() || !$config['gb']['moderation']){
			GO(GetSiteUrl().Ufu('index.php?name=guestbook', '{name}/'));
		}else{
			$text = '<center><br />Спасибо! Ваше сообщение будет добавлено после модерации.<br /><br />';
			$text .= '<input type="button" value="Назад" onclick="history.back();"><br /></center>';
			$site->AddTextBox('', $text);
		}
	}else{
		$text = 'Ваше сообщение не добавлено по следующим причинам:<br /><ul>';
		foreach($er as $error){
			$text .= '<li>'.$error;
		}
		$text .= '</ul><center><input type="button" value="Назад" onclick="history.back();"></center>';
		$site->AddTextBox('', $text);
	}
}
