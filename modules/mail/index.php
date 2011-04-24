<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$site->Title = 'Рассылки';

include_once (MOD_DIR.'functions.php');

// Проверяет, используется ли данный  e-mail пользователями
function MailIsSetEmail( $email )
{
	global $db;
	$db->Select('users', "`email`='$email'");
	return $db->NumRows() > 0;
}

function IndexMailIsSelected()
{
	global $user;
	return $user->isDef('mail_selected');
}

function IndexMailEnterMail( $message = '' )
{
	global $site;
	$site->AddTemplatedBox('Рассылки', 'module/mail.html');
	$site->AddBlock('mail');
	$vars['message'] = $message;
	$vars['form_action'] = Ufu('index.php?name=mail&op=topics', 'mail/{op}/');
	$vars['lemail'] = 'Ваш e-mail';
	$vars['lsubmit'] = 'Далее';
	$site->Blocks['mail']['vars'] = $vars;
}

if(isset($_POST['mail_block_form'])){
	$user->UnDef('mail_selected');
}

if(!IndexMailIsSelected()){
	if(isset($_POST['mail_form']) || isset($_POST['mail_block_form'])){
		$mail_selected = SafeEnv($_POST['email'], 50, str);
		if(!CheckEmail($mail_selected)){
			IndexMailEnterMail('E-mail указан в неверном формате.');
			return;
		}elseif(MailIsSetEmail($mail_selected)){
			$site->Login('Адрес <b>'.$mail_selected.'</b> уже используется, пожалуйста авторизируйтесь.');
			return;
		}else{
			$user->Def('mail_selected', $mail_selected);
		}
	}elseif($user->Auth && $user->Get('u_email') != ''){
		$mail_selected = $user->Get('u_email');
		$user->Def('mail_selected', $mail_selected);
	}else{
		IndexMailEnterMail();
		$site->Login();
		return;
	}
}else{
	$mail_selected = $user->Get('mail_selected');
}

if(isset($_GET['op'])){
	$op = $_GET['op'];
}else{
	$op = 'topics';
}

switch($op){
	case 'topics':
		IndexMailTopics();
		break;
	case 'topics_save':
		IndexMailTopicsSave();
		break;
	case 'change':
		IndexMailChangeEmail();
		break;
	case 'new':
		IndexMailNewEmail();
		break;
	case 'subscribe':
		IndexMailSubscribe();
		break;
	case 'history':
		IndexMailHistory();
		break;
	case 'showid':
		IndexMailShowId();
		break;
	default:
		HackOff();
}

function IndexMailTopics()
{
	global $site, $db, $mail_selected;
	$site->AddTemplatedBox('Рассылки', 'module/mail_topics.html');
	$site->AddBlock('mail');
	$vars['form_action'] = Ufu('index.php?name=mail&op=topics_save', 'mail/{op}/');
	$vars['lfor'] = 'Рассылки для';
	$vars['lnew'] = 'выбрать другой';
	$vars['lhtml'] = 'HTML';
	$vars['ltext'] = 'Текст';
	$vars['email'] = $mail_selected;
	$vars['lcount'] = 'Рассылок';
	$vars['lhistory'] = 'История';
	$vars['lsubmit'] = 'Сохранить';
	$site->Blocks['mail']['vars'] = $vars;
	$db->Select('mail_list', "`email`='$mail_selected'");
	$ads = array();
	while($ad = $db->FetchRow()){
		$ads[$ad['topic_id']] = $ad;
	}
	$site->AddBlock('mail_topics', true, true, 'topic');
	$db->Select('mail_topics', '');
	while($topic = $db->FetchRow()){
		$topic_vars = array();
		$topic_vars['id'] = SafeDB($topic['id'], 11, int);
		$topic_vars['title'] = SafeDB($topic['title'], 255, str);
		$topic_vars['description'] = SafeDB($topic['description'], 0, str);
		$topic_vars['count'] = SafeDB($topic['send_count'], 11, int);
		$topic_vars['history_url'] = Ufu('index.php?name=mail&op=history&topic_id='.SafeDB($topic['id'], 11, int), 'mail/history/topic{topic_id}/');
		$topic_vars['checked'] = isset($ads[$topic['id']]);
		if($topic_vars['checked']){
			$topic_vars['text_checked'] = $ads[$topic['id']]['html'] == '0';
		}else{
			$topic_vars['text_checked'] = false;
		}
		$site->AddSubBlock('mail_topics', true, $topic_vars);
	}
}

function IndexMailTopicsSave()
{
	global $site, $db, $mail_selected, $user;
	if(!isset($_POST['mail_topics_form'])){
		GO(GetSiteUrl().Ufu('index.php?name=mail&op=topics', 'mail/{op}/'));
	}
	if(isset($_POST['topic'])){
		$topics = SafeEnv($_POST['topic'], 11, int);
		$html = SafeEnv($_POST['html'], 1, int);
		$mls = $db->Select('mail_list', "`email`='$mail_selected'");
		$mail_list = array();
		foreach($mls as $mail){
			if(!isset($topics[$mail['topic_id']])){
				$db->Delete('mail_list', "`email`='{$mail['email']}'");
				CalcListCounter($mail['topic_id'], false);
				continue;
			}
			$mail_list[$mail['topic_id']] = $mail;
		}
		if($user->Auth){
			$user_id = $user->Get('u_id');
		}else{
			$user_id = 0;
		}
		$c = count($topics);
		for($i = 0; $i < $c; $i++){
			if(!isset($mail_list[$topics[$i]])){
				$vals = Values($user_id, $topics[$i], $mail_selected, $html[$i]);
				$db->Insert('mail_list', $vals);
				CalcListCounter($topics[$i], true);
			}
		}
	}
	$back = Ufu('index.php?name=mail&op=topics', 'mail/{op}/');
	$site->AddTextBox('Рассылки', "<center><br />Ваш список рассылки сохранен.<br /><br /><a href=\"$back\">Назад</a><br /><br /></center>");
}

function IndexMailNewEmail()
{
	global $user, $site;
	$user->UnDef('mail_selected');
	IndexMailEnterMail();
	$site->Login();
}

function IndexMailHistory()
{
	global $site, $db, $config;
	if(isset($_GET['topic_id'])){
		$topic_id = SafeEnv($_GET['topic_id'], 11, int);
	}else{
		GO(GetSiteUrl().Ufu('index.php?name=mail&op=topics', 'mail/{op}/'));
	}
	$db->Select('mail_topics', "`id`='$topic_id'");

	if($db->NumRows() > 0){
		$topic = $db->FetchRow();
	}else{
		GO(GetSiteUrl().Ufu('index.php?name=mail&op=topics', 'mail/{op}/'));
	}
	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 10, int);
	}else{
		$page = 1;
	}
	$mails = $db->Select('mail_history', "`topic_id`='$topic_id'");
	if($db->NumRows() == 0){
		$site->AddTextBox('Рассылки', '<center><br />Рассылок на тему "'.SafeDB($topic['title'], 250, str).'" не проводилось.<br /><br /><a href="javascript:history.go(-1)">Назад</a><br /><br /></center>');
		return;
	}
	SortArray($mails, 'date', false);

	$num = 20; // FIXME: $config['mail']['mail_on_page'];
	$navigation = new Navigation($page);
	$navigation->FrendlyUrl = $config['general']['ufu'];
	$navigation->GenNavigationMenu($mails, $num, Ufu("index.php?name=mail&op=history&topic_id=$topic_id", 'mail/history/topic{topic_id}/page{page}/'));

	$site->AddTemplatedBox('', 'module/mail_showid_nav.html');
	$site->AddBlock('mail_nav');
	$vars['lprev'] = 'Список рассылок';
	$vars['prev_id'] = true;
	$vars['next_id'] = false;
	$vars['back'] = false;
	$vars['prev_url'] = 'index.php?name=mail&amp;op=topics';
	$site->Blocks['mail_nav']['vars'] = $vars;
	$site->AddTemplatedBox('Архив рассылки', 'module/mail_mail.html');
	$site->AddBlock('mail_history');
	$vars['topic'] = SafeDB($topic['title'], 255, str);
	$vars['ldate'] = 'Дата выпуска';
	$vars['lsubject'] = 'Тема выпуска';
	$site->Blocks['mail_history']['vars'] = $vars;
	$site->AddBlock('mail', true, true);
	foreach($mails as $mail){
		$vars = array();
		$vars['subject'] = SafeDB($mail['subject'], 250, str);
		$vars['date'] = TimeRender(SafeDB($mail['date'], 11, int));
		$vars['url'] = Ufu("index.php?name=mail&op=showid&topic_id=$topic_id&id=".(SafeDB($mail['id'], 11, int)), 'mail/show/topic{topic_id}/id{id}/');
		$site->AddSubBlock('mail', true, $vars);
	}
}

function IndexMailShowId()
{
	global $site, $db;
	if(isset($_GET['topic_id'])){
		$topic_id = SafeEnv($_GET['topic_id'], 11, int);
	}else{
		GO(GetSiteUrl().Ufu('index.php?name=mail&op=topics', 'mail/{op}/'));
	}
	if(isset($_GET['id'])){
		$id = SafeEnv($_GET['id'], 11, int);
	}else{
		GO(GetSiteUrl().Ufu('index.php?name=mail&op=topics', 'mail/{op}/'));
	}
	$mails = $db->Select('mail_history', "`topic_id`='$topic_id'");
	SortArray($mails, 'date', false);
	$prev_id = false;
	$next_id = false;
	$find = false;
	for($i = 0, $c = count($mails); $i < $c; $i++){
		if($mails[$i]['id'] == $id){
			if($i < $c - 1){
				$next_id = $mails[$i + 1]['id'];
			}
			if($i > 0){
				$prev_id = $mails[$i - 1]['id'];
			}
			$mail = $mails[$i];
			$find = true;
			break;
		}
	}
	if(!$find){
		GO(GetSiteUrl().Ufu('index.php?name=mail&op=topics', 'mail/{op}/'));
	}
	$site->AddTemplatedBox('', 'module/mail_showid_nav.html');
	$site->AddBlock('mail_nav');
	$vars['lprev'] = 'Предыдущий выпуск';
	$vars['lnext'] = 'Следующий выпуск';
	$vars['lback'] = 'Назад к списку';
	$vars['prev_id'] = $prev_id;
	$vars['next_id'] = $next_id;
	$vars['back'] = true;
	$vars['prev_url'] = Ufu("index.php?name=mail&op=showid&topic_id=$topic_id&id=$prev_id", 'mail/show/topic{topic_id}/id{id}/');
	$vars['back_url'] = Ufu("index.php?name=mail&op=history&topic_id=$topic_id", 'mail/history/topic{topic_id}/');
	$vars['next_url'] = Ufu("index.php?name=mail&op=showid&topic_id=$topic_id&id='.$next_id", 'mail/show/topic{topic_id}/id{id}/');
	$site->Blocks['mail_nav']['vars'] = $vars;
	$site->AddTemplatedBox('Архив рассылки ', 'module/mail_showid.html');
	$site->AddBlock('mail');
	$vars['subject'] = SafeDB($mail['subject'], 255, str);
	$vars['date'] = TimeRender(SafeDB($mail['date'], 11, int));
	$vars['ldate'] = 'Дата выпуска';
	$vars['text'] = nl2br(SafeDB($mail['plain_text'], 0, str));
	// HTML //($mail[8]?nl2br(SafeDB($mail[7],0,str)):SafeDB($mail[7],0,str));
	$site->Blocks['mail']['vars'] = $vars;
	$site->AddTemplatedBox('', 'module/mail_down_tab.html');
	$site->AddBlock('mail_down_tab');
	$vars['lsubscribe'] = 'Подписаться на эту рассылку.';
	$vars['subscribe_url'] = Ufu("index.php?name=mail&op=subscribe&topic_id=$topic_id", 'mail/subscribe/topic{topic_id}/');
	$site->Blocks['mail_down_tab']['vars'] = $vars;
}

function IndexMailSubscribe()
{
	global $site, $db, $mail_selected, $user;
	if(isset($_POST['topic_id'])){
		$topic_id = SafeEnv($_POST['topic_id'], 11, int);
		if(!isset($_POST['mail_block_form'])){
			GO(GetSiteUrl().Ufu('index.php?name=mail&op=topics', 'mail/{op}/'));
		}
		$html = SafeEnv($_POST['html'], 1, int);
	}elseif(isset($_GET['topic_id'])){
		$topic_id = SafeEnv($_GET['topic_id'], 11, int);
		$html = 1;
	}else{
		GO(GetSiteUrl().Ufu('index.php?name=mail&op=topics', 'mail/{op}/'));
	}
	$db->Delete('mail_list', "`email`='$mail_selected' and `topic_id`='$topic_id'");
	if($user->Auth){
		$user_id = $user->Get('u_id');
	}else{
		$user_id = 0;
	}

	$db->Select('mail_topics', "`id`='$topic_id'");
	$topic = $db->FetchRow();
	$count = SafeDB($topic['count'], 11, int) + 1;
	$db->Update('mail_topics', "count='$count'", "`id`='$topic_id'");

	$vals = Values($user_id, $topic_id, $mail_selected, $html);
	$db->Insert('mail_list', $vals);

	$back = Ufu('index.php?name=mail&op=topics', 'mail/{op}/');
	$site->AddTextBox('Рассылки', '<center><br />Спасибо, что Вы подписались на нашу рассылку.<br />Список других рассылок сайта можете посмотреть <a href="">Здесь</a>.<br /><br /><a href="javascript:history.go(-1)">Вернуться назад</a>.<br /><br /></center>');
}

?>