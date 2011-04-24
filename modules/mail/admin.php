<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('Рассылка писем');

include_once (MOD_DIR.'functions.php');

function AdminMailMain()
{
	global $config, $db;
	$db->Select('mail_topics', '');
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>Тема</th><th>Последняя рассылка</th><th>Подписчиков</th><th>Рассылок</th><th>Состояние</th><th>Статус</th><th>Функции</th></tr>';
	while($row = $db->FetchRow()){
		$tid = SafeDB($row['id'], 11, int);
		switch($row['status']){
			case '1':
				$st = '<a href="'.$config['admin_file'].'?exe=mail&a=change_status&id='.$tid.'" title="Выключить"><font color="#008000">Вкл.</font></a>';
				break;
			case '0':
				$st = '<a href="'.$config['admin_file'].'?exe=mail&a=change_status&id='.$tid.'" title="Включить"><font color="#FF0000">Выкл.</font></a>';
				break;
		}
		switch($row['active']){
			case '1':
				$active = '<a href="'.$config['admin_file'].'?exe=mail&a=change_active&id='.$tid.'" title="Тема открыта. Новые рассылки выходят по этой теме."><font color="#008000">Открыта</font></a>';
				break;
			case '0':
				$active = '<a href="'.$config['admin_file'].'?exe=mail&a=change_active&id='.$tid.'" title="Тема закрыта. Новые рассылки не выходят по этой теме."><font color="#FF0000">Закрыта</font></a>';
				break;
		}

		$func = '';
		$func .= SpeedButton('Подготовить рассылку', $config['admin_file'].'?exe=mail&a=mail&topic_id='.$tid, 'images/admin/mail.png');
		$func .= SpeedButton('Редактировать', $config['admin_file'].'?exe=mail&a=edit_topic&id='.$tid, 'images/admin/edit.png');
		$func .= SpeedButton('Удалить', $config['admin_file'].'?exe=mail&a=delete_topic&id='.$tid.'&ok=0', 'images/admin/delete.png');

		$text .= '
		<tr>
		<td><b><a href="'.$config['admin_file'].'?exe=mail&a=edit_topic&id='.$tid.'">'.SafeDB($row['title'], 255, str).'</a></b></td>
		<td>'.TimeRender(SafeDB($row['last_send'], 11, int)).'</td>
		<td>'.SafeDB($row['count'], 11, int).' / <a href="'.$config['admin_file'].'?exe=mail&a=list&topic_id='.$tid.'">Просмотр</a></td>
		<td>'.SafeDB($row['send_count'], 11, int).' / <a href="'.$config['admin_file'].'?exe=mail&a=history&topic_id='.$tid.'">Просмотр</a></td>
		<td>'.$active.'</td>
		<td>'.$st.'</td>
		<td>'.$func.'</td>
		</tr>';
	}
	$text .= '</table>';
	AddTextBox('Темы', $text);
}

function AdminMailEditTopic()
{
	global $config, $db, $site;
	$title = '';
	$description = '';
	$active = array(false, false);
	$status = array(false, false);
	if(!isset($_GET['id'])){
		$active[1] = true;
		$status[1] = true;
		$action = 'save_topic';
		$top = 'Добавление темы';
		$cap = 'Добавить';
	}else{
		$id = SafeEnv($_GET['id'], 11, int);
		$db->Select('mail_topics', "`id`='$id'");
		$topic = $db->FetchRow();
		$title = SafeDB($topic['title'], 250, str);
		$description = SafeDB($topic['description'], 250, str);
		$active[SafeDB($topic['active'], 1, int)] = true;
		$status[SafeDB($topic['status'], 1, int)] = true;
		$action = 'save_topic&id='.$id;
		$top = 'Редактирование темы';
		$cap = 'Сохранить изменения';
		unset($topic);
	}
	FormRow('Заголовок', $site->Edit('title', $title, false, 'maxlength="250" style="width:200px;"'), 140);
	FormTextRow('Описание', $site->HtmlEditor('description', $description, 600, 200));
	FormRow('Активна', $site->Select('active', GetEnData($active[1])));
	FormRow('Включить', $site->Select('status', GetEnData($status[1])));
	AddCenterBox($top);
	AddForm('<form action="'.$config['admin_file'].'?exe=mail&a='.$action.'" method="post">',
		$site->Button('Отмена', 'onclick="history.go(-1)"').$site->Submit($cap));
}

function AdminMailTopicSave()
{
	global $db, $config;
	$title = SafeEnv($_POST['title'], 250, str);
	$description = SafeEnv($_POST['description'], 0, str);
	$active = EnToInt($_POST['active']);
	$status = EnToInt($_POST['status']);
	if(isset($_GET['id'])){
		$id = SafeEnv($_GET['id'], 11, int);
		$set = "title='$title',description='$description',active='$active',status='$status'";
		$db->Update('mail_topics', $set, "`id`='$id'");
	}else{
		$vals = Values('', $title, $description, '0', '0', '0', $active, $status, '0');
		$db->Insert('mail_topics', $vals);
	}
	$cache = LmFileCache::Instance();
	$cache->Delete('block', 'mail');
	GO($config['admin_file'].'?exe=mail');
}

function AdminMailChangeTopicActive()
{
	global $config, $db;
	$db->Select('mail_topics', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	$r = $db->FetchRow();
	if($r['active'] == 1){
		$active = '0';
	}else{
		$active = '1';
	}
	$db->Update('mail_topics', "active='$active'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	$cache = LmFileCache::Instance();
	$cache->Delete('block', 'mail');
	GO($config['admin_file'].'?exe=mail');
}

function AdminMailChangeTopicStatus()
{
	global $config, $db;
	$db->Select('mail_topics', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	$r = $db->FetchRow();
	if($r['status'] == 1){
		$status = '0';
	}else{
		$status = '1';
	}
	$db->Update('mail_topics', "status='$status'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	$cache = LmFileCache::Instance();
	$cache->Delete('block', 'mail');
	GO($config['admin_file'].'?exe=mail');
}

function AdminMailDeleteTopic()
{
	global $config, $db;
	if(isset($_GET['ok']) && $_GET['ok'] == '1'){
		$db->Delete('mail_topics', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
		$cache = LmFileCache::Instance();
		$cache->Delete('block', 'mail');
		GO($config['admin_file'].'?exe=mail');
	}else{
		TAddSubTitle('Удаление темы');
		$r = $db->Select('mail_topics', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
		$text = 'Вы действительно хотите удалить тему "'.$r[0]['title'].'"<br />'.'<a href="'.$config['admin_file'].'?exe=mail&a=delete_topic&id='.SafeEnv($_GET['id'], 11, int).'&ok=1">Да</a>'
			.' &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">Нет</a>';
		AddTextBox("Внимание!", $text);
	}
}

function AdminMailMail()
{
	global $db, $config, $site;
	if(isset($_GET['topic_id'])){
		$topic_id = SafeEnv($_GET['topic_id'], 11, int);
	}else{
		$topic_id = 0;
	}
	$subject = '';
	$from = $config['general']['site_name'];
	$from_email = $config['general']['site_email'];
	$text = '';
	$text_html = '';
	$auto_br = array(false, false);
	if(!isset($_GET['id'])){
		AddCenterBox('Создание новой рассылки');
		$auto_br[0] = true;
		$b = 'Отправить';
	}else{
		AddCenterBox('Редактирование');
		$id = SafeEnv($_GET['id'], 11, int);
		$db->Select('mail_history', "`id`='$id'");
		$msg = $db->FetchRow();
		$topic_id = SafeDB($msg['topic_id'], 11, int);
		$subject = SafeDB($msg['subject'], 255, str);
		$from = $config['general']['site_name'];
		$from_email = $config['general']['site_email'];
		$text = SafeDB($msg['plain_text'], 0, str);
		$text_html = SafeDB($msg['text_html'], 0, str);
		$auto_br[SafeDB($msg['auto_br'], 11, int)] = true;
		$b = 'Сохранить изменения';
	}
	$db->Select('mail_topics', '');
	$topicdata = array();
	while($topic = $db->FetchRow()){
		$site->DataAdd($topicdata, SafeDB($topic['id'], 11, int), SafeDB($topic['title'], 250, str), ($topic['id'] == $topic_id));
	}
	FormRow('Тема рассылки', $site->Select('topic', $topicdata, false, ''));
	FormRow('Заголовок', $site->Edit('subject', $subject, false, 'style="width:400px;"'));
	FormRow('От кого', $site->Edit('from', $from, false, 'style="width:400px;"'));
	FormRow('E-mail отправителя', $site->Edit('from_email', $from_email, false, 'style="width:400px;"'));
	FormTextRow('Текст письма', $site->TextArea('text', $text, 'style="width:600px;height:400px;"'));
	FormTextRow('Текст HTML', $site->HtmlEditor('html', $text_html, 600, 400));
	FormRow('Вставлять тег &lt;br&gt;<br />автоматически', $site->Check('auto_br', '1', $auto_br[1]));
	AddForm('<form action="'.$config['admin_file'].'?exe=mail&a=send'.(isset($id) ? '&id='.$id : '').'" method="POST">',
		$site->Button('Отмена', 'onclick="history.go(-1);"').$site->Submit($b));
}

function AdminMailSend()
{
	global $db, $config;
	$topic = SafeEnv($_POST['topic'], 11, int);
	$subject = SafeEnv($_POST['subject'], 255, str);
	$from = SafeEnv($_POST['from'], 255, str);
	$from_email = SafeEnv($_POST['from_email'], 255, str);
	$text = SafeEnv($_POST['text'], 0, str);
	$text_html = SafeEnv($_POST['html'], 0, str);
	if(isset($_POST['auto_br'])){
		$auto_br = '1';
	}else{
		$auto_br = '0';
	}
	if(!isset($_GET['id'])){
		$list = $db->Select('mail_list', "`topic_id`='$topic'");
		$vals = Values('', $topic, $subject, time(), $from, $from_email, $text, $text_html, $auto_br, count($list));
		$db->Insert('mail_history', $vals);

		// Отправка
		$mail = LmEmailExtended::Instance();
		$mail->SetSubject(Cp1251ToUtf8($subject));
		$mail->SetFrom($from_email, Cp1251ToUtf8($from));

		if(trim($text) != ''){
			$mail->AddTextPart($text);
		}
		if(trim(strip_tags($text_html))){
			$mail->AddHtmlPart(Cp1251ToUtf8($text_html));
		}

		$result = true;
		foreach($list as $row){
			$mail->SetTo($row['email']);
			if(!$mail->Send()){
				$result = false;
			}
		}
		if($result){
			AddTextBox("Рассылка", '<p><br />Ваша рассылка успешно разослана.<br /><br /></p>');
		}else{
			AddTextBox("Рассылка", '<p><br />При рассылке произошли ошибки. Письмо сохранено в истории.<br /><br /></p>');
		}
		CalcMailCounter($topic, true);
	}else{
		$id = SafeEnv($_GET['id'], 11, int);
		$set = "topic_id='$topic',subject='$subject',from='$from',from_email='$from_email',plain_text='$text',text_html='$text_html',auto_br='$auto_br'";
		$db->Update('mail_history', $set, "`id`='$id'");
		GO($config['admin_file'].'?exe=mail&a=history&topic_id='.$topic);
	}
}

function AdminMailHistory()
{
	global $db, $config;
	if(isset($_GET['topic_id'])){
		$topic = SafeEnv($_GET['topic_id'], 11, int);
	}elseif(isset($_POST['topic'])){
		$topic = SafeEnv($_POST['topic_id'], 11, int);
	}else{
		$text = '<center>Тема не указана.</center>';
		AddTextBox('Список рассылки', $text);
		return;
	}
	$msgs = $db->Select('mail_history', '');
	if($db->NumRows() == 0){
		$text = '<center>Рассылок по данной теме не проводилось.</center>';
		AddTextBox('Список рассылки', $text);
		return;
	}
	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 10, int);
	}else{
		$page = 1;
	}
	AddCenterBox('Рассылка почты: Список рассылки');
	SortArray($msgs, 'date', true);
	$num = 10;
	if(count($msgs) > $num){
		$nav = new Navigation($page);
		$nav->GenNavigationMenu($msgs, $num, $config['admin_file'].'?exe=mail&a=history&topic_id='.$topic);
		AddNavigation();
		$nav = true;
	}else{
		$nav = false;
	}
	$text = '';
	foreach($msgs as $msg){
		$mid = SafeDB($msg['id'], 11, int);
		$subject = SafeDB($msg['subject'], 255, str);
		$date = SafeDB($msg['date'], 11, int);
		$from = SafeDB($msg['from'], 255, str);
		$from_email = SafeDB($msg['from_email'], 255, str);
		$mailtext = nl2br(SafeDB($msg['plain_text'], 0, str));

		$func = '';
		$func .= SpeedButton('Редактировать письмо', $config['admin_file'].'?exe=mail&a=edit&id='.$mid.'&topic_id='.$topic, 'images/admin/edit.png');
		$func .= SpeedButton('Удалить письмо', $config['admin_file'].'?exe=mail&a=delete&id='.$mid.'&topic_id='.$topic.'&ok=0', 'images/admin/delete.png');

		$text .= '<table cellspacing="0" cellpadding="0" class="cfgtable" style="width:80%;">';
		$text .= '<tr>
			<td style="text-align:left;padding-left:10px;">'.TimeRender($date).'</td>
			<td style="text-align:left;padding-left:10px;">'.$subject.'</td>
			<td>'.$from.'</td>
			<td>'.$from_email.'</td>
			<td>'.$func.'</td>
		</tr>';
		$text .= '<tr><td colspan="5" style="text-align:left;padding:10px;">'.$mailtext.'</td></tr>';
		$text .= '</table><br />';
	}
	AddText($text);
	if($nav){
		AddNavigation();
	}
}

function AdminMailDelete()
{
	global $db, $config;
	if(isset($_GET['ok']) && SafeEnv($_GET['ok'], 1, int) == '1'){
		$db->Delete('mail_history', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
		CalcMailCounter(SafeEnv($_GET['topic_id'], 11, int), false);
		GO($config['admin_file'].'?exe=mail&a=history&topic_id='.SafeEnv($_GET['topic_id'], 11, int));
		exit();
	}else{
		$id = SafeEnv($_GET['id'], 11, int);
		$topic_id = SafeEnv($_GET['topic_id'], 11, int);
		$r = $db->Select('mail_history', "`id`='".$id."'");
		$text = 'Вы действительно хотите удалить письмо с темой: '.$r[0]['subject'].'<br />'
		.'<a href="'.$config['admin_file'].'?exe=mail&a=delete&id='.SafeEnv($_GET['id'], 11, int).'&topic_id='.$topic_id.'&ok=1">Да</a>'
			.' &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">Нет</a>';
		AddTextBox("Внимание!", $text);
	}
}

function AdminMailList()
{
	global $db, $config, $site;
	if(!isset($_GET['topic_id'])){
		GO($config['admin_file'].'?exe=mail');
	}
	$topic_id = SafeEnv($_GET['topic_id'], 11, int);
	$db->Select('mail_topics', "`id`='$topic_id'");
	if($db->NumRows() == 0){
		AddTextBox("Внимание!", 'Тема не найдена.');
		return;
	}
	$topic = $db->FetchRow();
	$db->Select('mail_list', "`topic_id`='$topic_id'");
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>E-mail</th><th>Пользователь</th><th>Формат</th><th>Функции</th></tr>';
	$c_all = 0;
	$c_users = 0;
	$c_html = 0;
	while($row = $db->FetchRow()){
		$c_all++;
		if($row['user_id'] == '0'){
			$isuser = 'Нет';
		}else{
			$isuser = 'Да';
			$c_users++;
		}
		if($row['html'] == '0'){
			$html = 'Текст';
		}else{
			$html = 'HTML';
			$c_html++;
		}

		$func = '';
		$func .= SpeedButton('Удалить', $config['admin_file'].'?exe=mail&a=delete_email&topic_id='.SafeDB($row['topic_id'], 11, int).'&email='.SafeDB($row['email'], 50, str).'&ok=0', 'images/admin/delete.png');
		$text .= '<tr><td>'.PrintEmail($row['email']).'</a></td><td>'.$isuser.'</td><td>'.$html.'</td><td>'.$func.'</td></tr>';
	}
	$text .= '<tr><td>'.$c_all.'</a></td><td>'.$c_users.'</td><td>'.$c_html.'</td><td>&nbsp;</td></tr>';
	$text .= '</table>';
	$text .= '<br />.: Добавить E-mail :.';
	AddCenterBox('Список подписчиков на рассылку "'.SafeDB($topic['title'], 250, str).'"');
	AddText($text);
	FormRow('E-mail', $site->Edit('email'));
	$format = array();
	$site->DataAdd($format, '1', 'HTML');
	$site->DataAdd($format, '0', 'Текст');
	FormRow('Формат рассылки', $site->Select('html', $format));
	AddForm('<form name="addemail" action="'.$config['admin_file'].'?exe=mail&a=add_email&topic_id='.$topic_id.'" method="post">', $site->Submit('Добавить'));
}

function AdminMailAddEmail()
{
	global $config, $db;
	if(!isset($_GET['topic_id'])){
		GO($config['admin_file'].'?exe=mail');
	}
	$topic_id = SafeEnv($_GET['topic_id'], 11, int);
	if(!isset($_POST['email'])){
		GO($config['admin_file'].'?exe=mail');
	}
	if(CheckEmail($_POST['email'])){
		$email = SafeEnv($_POST['email'], 50, str, true);
	}else{
		$text = 'Не правильный формат E-mail. Он должен быть вида: <b>domain@host.ru</b>.<br />'.'<a href="javascript:history.go(-1)">Назад</a>';
		AddTextBox("Внимание!", $text);
		return;
	}
	$html = SafeEnv($_POST['html'], 1, int);
	$vals = Values('0', $topic_id, $email, $html);
	$db->Insert('mail_list', $vals);
	CalcListCounter($topic_id, true);
	GO($config['admin_file'].'?exe=mail&a=list&topic_id='.$topic_id);
}

function AdminMailDeleteEmail()
{
	global $db, $config;
	if(isset($_GET['ok']) && SafeEnv($_GET['ok'], 1, int) == '1'){
		$db->Delete('mail_list', "`topic_id`='".SafeEnv($_GET['topic_id'], 11, int)."' and `email`='".SafeEnv($_GET['email'], 50, str)."'");
		CalcListCounter(SafeEnv($_GET['topic_id'], 11, int), false);
		GO($config['admin_file'].'?exe=mail&a=list&topic_id='.SafeEnv($_GET['topic_id'], 11, int));
	}else{
		$id = SafeEnv($_GET['email'], 50, str);
		$text = 'Вы действительно хотите удалить E-mail: <b>'.$id.'</b><br />'.'<a href="'.$config['admin_file'].'?exe=mail&a=delete_email&topic_id='.SafeEnv($_GET['topic_id'], 11, int).'&email='.$id.'&ok=1">Да</a> &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">Нет</a>';
		AddTextBox("Внимание!", $text);
	}
}

include_once ($config['apanel_dir'].'configuration/functions.php');

if(isset($_GET['a'])){
	AdminMail($_GET['a']);
}else{
	AdminMail('main');
}

function AdminMail( $action )
{
	global $user;
	TAddToolLink('Темы', 'main', 'mail');
	TAddToolLink('Добавить тему', 'add_topic', 'mail&a=add_topic');
	TAddToolLink('Создать рассылку', 'mail', 'mail&a=mail');
	//TAddToolLink('Конфигурация','config','mail&a=config');
	TAddToolBox($action);
	switch($action){
		case 'main':
			AdminMailMain();
			break;
		case 'edit_topic':
			AdminMailEditTopic();
			break;
		case 'add_topic':
			AdminMailEditTopic();
			break;
		case 'save_topic':
			AdminMailTopicSave();
			break;
		case 'change_active':
			AdminMailChangeTopicActive();
			break;
		case 'change_status':
			AdminMailChangeTopicStatus();
			break;
		case 'delete_topic':
			AdminMailDeleteTopic();
			break;
		case 'mail':
		case 'edit':
			AdminMailMail();
			break;
		case 'send':
			AdminMailSend();
			break;
		case 'history':
			AdminMailHistory();
			break;
		case 'delete':
			AdminMailDelete();
			break;
		case 'list':
			AdminMailList();
			break;
		case 'add_email':
			AdminMailAddEmail();
			break;
		case 'delete_email':
			AdminMailDeleteEmail();
			break;
		case 'config':
			AdminConfigurationEdit('mail', 'mail', true, false, 'Конфигурация модуля "Рассылка"');
			break;
		case 'configsave':
			AdminConfigurationSave('mail&a=config', 'mail', true);
			break;
		default:
			AdminMailMain();
	}
}

?>