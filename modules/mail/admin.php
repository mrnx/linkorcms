<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('�������� �����');
include_once MOD_DIR.'functions.php';

if(isset($_GET['a'])){
	$action = $_GET['a'];
}else{
	$action = 'main';
}

TAddToolLink('����', 'main', 'mail');
TAddToolLink('�������� ����', 'add_topic', 'mail&a=add_topic');
TAddToolLink('������� ��������', 'mail', 'mail&a=mail');
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
	default:
		AdminMailMain();
}


function AdminMailMain(){
	System::database()->Select('mail_topics', '');
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>����</th><th>��������� ��������</th><th>�����������</th><th>��������</th><th>���������</th><th>������</th><th>�������</th></tr>';
	while($row = System::database()->FetchRow()){
		$tid = SafeDB($row['id'], 11, int);
		$st = System::admin()->SpeedStatus('���.', '<span style="color: #f00;">����.</span>', ADMIN_FILE.'?exe=mail&a=change_status&id='.$tid, $row['status'] == '1', '', '', true);
		$active = System::admin()->SpeedStatus('�������', '<span style="color: #f00;">�������</span>', ADMIN_FILE.'?exe=mail&a=change_active&id='.$tid, $row['active'] == '1', '', '', true);

		$func = '';
		$func .= System::admin()->SpeedButton('����������� ��������', ADMIN_FILE.'?exe=mail&a=mail&topic_id='.$tid, 'images/admin/mail.png');
		$func .= System::admin()->SpeedButton('�������������', ADMIN_FILE.'?exe=mail&a=edit_topic&id='.$tid, 'images/admin/edit.png');
		$func .= System::admin()->SpeedConfirm('�������', ADMIN_FILE.'?exe=mail&a=delete_topic&id='.$tid.'&ok=0', 'images/admin/delete.png', '������� ��� ������ ������� ��������?');

		$text .= '
		<tr>
		<td><b>'.System::admin()->Link(SafeDB($row['title'], 255, str), ADMIN_FILE.'?exe=mail&a=edit_topic&id='.$tid).'</b></td>
		<td>'.TimeRender(SafeDB($row['last_send'], 11, int)).'</td>
		<td>'.SafeDB($row['count'], 11, int).' / '.System::admin()->Link('��������', ADMIN_FILE.'?exe=mail&a=list&topic_id='.$tid).'</td>
		<td>'.SafeDB($row['send_count'], 11, int).' / '.System::admin()->Link('��������', ADMIN_FILE.'?exe=mail&a=history&topic_id='.$tid).'</td>
		<td>'.$active.'</td>
		<td>'.$st.'</td>
		<td>'.$func.'</td>
		</tr>';
	}
	$text .= '</table>';
	AddTextBox('����', $text);
}

function AdminMailEditTopic(){
	global $site;
	$title = '';
	$description = '';
	$active = array(false, false);
	$status = array(false, false);
	if(!isset($_GET['id'])){
		$active[1] = true;
		$status[1] = true;
		$action = 'save_topic';
		$top = '�������� ����';
		$cap = '��������';
	}else{
		$id = SafeEnv($_GET['id'], 11, int);
		System::database()->Select('mail_topics', "`id`='$id'");
		$topic = System::database()->FetchRow();
		$title = SafeDB($topic['title'], 250, str);
		$description = SafeDB($topic['description'], 250, str);
		$active[SafeDB($topic['active'], 1, int)] = true;
		$status[SafeDB($topic['status'], 1, int)] = true;
		$action = 'save_topic&id='.$id;
		$top = '�������������� ����';
		$cap = '��������� ���������';
		unset($topic);
	}
	FormRow('���������', $site->Edit('title', $title, false, 'maxlength="250" style="width:400px;"'), 140);
	FormTextRow('��������', $site->HtmlEditor('description', $description, 600, 200));
	FormRow('�������', $site->Select('active', GetEnData($active[1])));
	FormRow('��������', $site->Select('status', GetEnData($status[1])));
	AddCenterBox($top);
	AddForm('<form action="'.ADMIN_FILE.'?exe=mail&a='.$action.'" method="post">',
		$site->Button('������', 'onclick="history.go(-1)"').$site->Submit($cap));
}

function AdminMailTopicSave(){
	$title = SafeEnv($_POST['title'], 250, str);
	$description = SafeEnv($_POST['description'], 0, str);
	$active = EnToInt($_POST['active']);
	$status = EnToInt($_POST['status']);
	if(isset($_GET['id'])){
		$id = SafeEnv($_GET['id'], 11, int);
		$set = "title='$title',description='$description',active='$active',status='$status'";
		System::database()->Update('mail_topics', $set, "`id`='$id'");
	}else{
		$vals = Values('', $title, $description, '0', '0', '0', $active, $status, '0');
		System::database()->Insert('mail_topics', $vals);
	}
	$cache = LmFileCache::Instance();
	$cache->Delete('block', 'mail');
	GO(ADMIN_FILE.'?exe=mail');
}

function AdminMailChangeTopicActive(){
	System::database()->Select('mail_topics', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	$r = System::database()->FetchRow();
	if($r['active'] == 1){
		$active = '0';
	}else{
		$active = '1';
	}
	System::database()->Update('mail_topics', "active='$active'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	$cache = LmFileCache::Instance();
	$cache->Delete('block', 'mail');
	if(IsAjax()){
		exit("OK");
	}
	GO(ADMIN_FILE.'?exe=mail');
}

function AdminMailChangeTopicStatus(){
	System::database()->Select('mail_topics', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	$r = System::database()->FetchRow();
	if($r['status'] == 1){
		$status = '0';
	}else{
		$status = '1';
	}
	System::database()->Update('mail_topics', "status='$status'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	$cache = LmFileCache::Instance();
	$cache->Delete('block', 'mail');
	if(IsAjax()){
		exit("OK");
	}
	GO(ADMIN_FILE.'?exe=mail');
}

function AdminMailDeleteTopic(){
	System::database()->Delete('mail_topics', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	System::database()->Delete('mail_list', "`topic_id`='".SafeEnv($_GET['id'], 11, int)."'");
	System::database()->Delete('mail_history', "`topic_id`='".SafeEnv($_GET['id'], 11, int)."'");
	$cache = LmFileCache::Instance();
	$cache->Delete('block', 'mail');
	GO(ADMIN_FILE.'?exe=mail');
}

function AdminMailMail(){
	global $config, $site;
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
		AddCenterBox('�������� ����� ��������');
		$auto_br[0] = true;
		$submit = $site->Submit('���������');
	}else{
		AddCenterBox('��������������');
		$id = SafeEnv($_GET['id'], 11, int);
		System::database()->Select('mail_history', "`id`='$id'");
		$msg = System::database()->FetchRow();
		$topic_id = SafeDB($msg['topic_id'], 11, int);
		$subject = SafeDB($msg['subject'], 255, str);
		$from = $config['general']['site_name'];
		$from_email = $config['general']['site_email'];
		$text = SafeDB($msg['plain_text'], 0, str);
		$text_html = SafeDB($msg['text_html'], 0, str);
		$auto_br[SafeDB($msg['auto_br'], 11, int)] = true;
		$b = '��������� ���������';
		$submit = $site->Button('������', 'onclick="history.go(-1);"').$site->Submit('��������� ���������');
	}
	System::database()->Select('mail_topics', '');
	$topicdata = array();
	while($topic = System::database()->FetchRow()){
		$site->DataAdd($topicdata, SafeDB($topic['id'], 11, int), SafeDB($topic['title'], 250, str), ($topic['id'] == $topic_id));
	}
	FormRow('���� ��������', $site->Select('topic', $topicdata, false, ''));
	FormRow('��������� �����', $site->Edit('subject', $subject, false, 'style="width:400px;"'));
	FormRow('��� �����������', $site->Edit('from', $from, false, 'style="width:400px;"'));
	FormRow('����� Email �����������', $site->Edit('from_email', $from_email, false, 'style="width:400px;"'));
	FormTextRow('����� ������ � ��������� �������', $site->TextArea('text', $text, 'style="width:600px;height:400px;"'));
	FormTextRow('����� ������ � HTML �������', $site->HtmlEditor('html', $text_html, 600, 400));
	FormRow('', '<label>'.$site->Check('auto_br', '1', $auto_br[1]).'&nbsp;������������� ����� � HTML (���� �� �� ����������� ���������� ��������)</label>');
	AddForm('<form action="'.ADMIN_FILE.'?exe=mail&a=send'.(isset($id) ? '&id='.$id : '').'" method="POST">', $submit);
}

function AdminMailSend(){
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
		System::admin()->AddCenterBox('��������');
		$list = System::database()->Select('mail_list', "`topic_id`='$topic'");

		// ��������� ������
		$vals = Values('', $topic, $subject, time(), $from, $from_email, $text, $text_html, $auto_br, count($list));
		System::database()->Insert('mail_history', $vals);

		// ��������
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
				System::admin()->HighlightError('������ '.SafeDB($row['email'], 255, str).': ������ ��������� �� �������.'); // todo ����� �������� ������ ������ ������ �� �����������
				$result = false;
			}
		}
		if($result){
			System::admin()->Highlight('��� ������ ������� ����������.');
		}else{
			System::admin()->HighlightError('��� �������� ��������� ������. ������ ��������� � �������.');
		}
		CalcMailCounter($topic, true);
	}else{
		$id = SafeEnv($_GET['id'], 11, int);
		$set = "topic_id='$topic',subject='$subject',from='$from',from_email='$from_email',plain_text='$text',text_html='$text_html',auto_br='$auto_br'";
		System::database()->Update('mail_history', $set, "`id`='$id'");
		GO(ADMIN_FILE.'?exe=mail&a=history&topic_id='.$topic);
	}
}

function AdminMailHistory(){
	System::admin()->AddCenterBox('������� ��������');
	if(isset($_GET['topic_id'])){
		$topic = SafeEnv($_GET['topic_id'], 11, int);
	}elseif(isset($_POST['topic'])){
		$topic = SafeEnv($_POST['topic_id'], 11, int);
	}else{
		System::admin()->Highlight('���� �� �������.');
		return;
	}
	$msgs = System::database()->Select('mail_history', '');
	if(System::database()->NumRows() == 0){
		System::admin()->Highlight('�������� �� ������ ���� �� �����������.');
		return;
	}
	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 10, int);
	}else{
		$page = 1;
	}
	SortArray($msgs, 'date', true);
	$num = 10;
	if(count($msgs) > $num){
		$nav = new Navigation($page);
		$nav->GenNavigationMenu($msgs, $num, ADMIN_FILE.'?exe=mail&a=history&topic_id='.$topic);
		AddNavigation();
		$nav = true;
	}else{
		$nav = false;
	}
	$text = '<table cellspacing="0" cellpadding="0" align="center" class="commtable_header" style="width:80%;">
	<tr>
	<th style="width: 120px;">����</th>
	<th style="width: 510px;">����</th>
	<th>�������</th>
	</tr></table>';
	foreach($msgs as $msg){
		$mid = SafeDB($msg['id'], 11, int);
		$subject = SafeDB($msg['subject'], 255, str);
		$date = SafeDB($msg['date'], 11, int);
		$from = SafeDB($msg['from'], 255, str);
		$from_email = SafeDB($msg['from_email'], 255, str);
		$mailtext = nl2br(SafeDB($msg['plain_text'], 0, str));

		$func = '';
		$func .= System::admin()->SpeedButton('������������� ������', ADMIN_FILE.'?exe=mail&a=edit&id='.$mid.'&topic_id='.$topic, 'images/admin/edit.png');
		$func .= System::admin()->SpeedConfirm('������� ������', ADMIN_FILE.'?exe=mail&a=delete&id='.$mid.'&topic_id='.$topic.'&ok=0', 'images/admin/delete.png', '������� ������ �� �������?');

		$text .= '<table cellspacing="0" cellpadding="0" class="commtable" style="width:80%;">';
		$text .= '<tr>
			<th style="text-align: left; width: 120px;">'.TimeRender($date).'</td>
			<th style="text-align: left; width: 510px;">'.$subject.'</td>
			<th>'.$func.'</td>
		</tr>';
		$text .= '<tr><td colspan="3" class="commtable_text">'.$mailtext.'</td></tr>';
		$text .= '</table>';
	}
	AddText($text);
	if($nav){
		AddNavigation();
	}
}

function AdminMailDelete(){
	System::database()->Delete('mail_history', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	CalcMailCounter(SafeEnv($_GET['topic_id'], 11, int), false);
	GO(ADMIN_FILE.'?exe=mail&a=history&topic_id='.SafeEnv($_GET['topic_id'], 11, int));
}


// ������ �����������
function AdminMailList(){
	if(!isset($_GET['topic_id'])){
		GO(ADMIN_FILE.'?exe=mail');
	}
	$topic_id = SafeEnv($_GET['topic_id'], 11, int);
	System::database()->Select('mail_topics', "`id`='$topic_id'");
	if(System::database()->NumRows() == 0){
		AddTextBox("��������!", '���� �� �������.');
		return;
	}
	$topic = System::database()->FetchRow();
	System::database()->Select('mail_list', "`topic_id`='$topic_id'");
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>E-mail</th><th>������������</th><th>������</th><th>�������</th></tr>';
	$c_all = 0;
	$c_users = 0;
	$c_html = 0;
	while($row = System::database()->FetchRow()){
		$c_all++;
		if($row['user_id'] == '0'){
			$isuser = '���';
		}else{
			$isuser = '��';
			$c_users++;
		}
		if($row['html'] == '0'){
			$html = '�����';
		}else{
			$html = 'HTML';
			$c_html++;
		}

		$func = '';
		$func .= System::admin()->SpeedConfirm('�������', ADMIN_FILE.'?exe=mail&a=delete_email&topic_id='.SafeDB($row['topic_id'], 11, int).'&email='.SafeDB($row['email'], 50, str).'&ok=0', 'images/admin/delete.png', '������� ����������?');
		$text .= '<tr><td>'.PrintEmail($row['email']).'</a></td><td>'.$isuser.'</td><td>'.$html.'</td><td>'.$func.'</td></tr>';
	}
	$text .= '<tr><td>'.$c_all.'</a></td><td>'.$c_users.'</td><td>'.$c_html.'</td><td>&nbsp;</td></tr>';
	$text .= '</table>';

	AddCenterBox('������ ����������� �� �������� "'.SafeDB($topic['title'], 250, str).'"');
	AddText($text);

	$format = array();
	System::admin()->DataAdd($format, '1', 'HTML');
	System::admin()->DataAdd($format, '0', '�����');

	System::admin()->FormTitleRow('�������� E-mail');
	System::admin()->FormRow('E-mail', System::admin()->Edit('email', '', false, 'style="width: 260px;"'));
	System::admin()->FormRow('������ ��������', System::admin()->Select('html', $format));
	System::admin()->AddForm('<form action="'.ADMIN_FILE.'?exe=mail&a=add_email&topic_id='.$topic_id.'" method="post">', System::admin()->Submit('��������'));
}

function AdminMailAddEmail(){
	if(!isset($_GET['topic_id'])){
		GO(ADMIN_FILE.'?exe=mail');
	}
	$topic_id = SafeEnv($_GET['topic_id'], 11, int);
	if(!isset($_POST['email'])){
		GO(ADMIN_FILE.'?exe=mail');
	}
	if(CheckEmail($_POST['email'])){
		$email = SafeEnv($_POST['email'], 50, str, true);
	}else{
		$text = '�� ���������� ������ E-mail. �� ������ ���� ����: <b>domain@host.ru</b>.<br />'.'<a href="javascript:history.go(-1)">�����</a>';
		AddTextBox("��������!", $text);
		return;
	}
	$html = SafeEnv($_POST['html'], 1, int);
	$vals = Values('0', $topic_id, $email, $html);
	System::database()->Insert('mail_list', $vals);
	CalcListCounter($topic_id, true);
	GO(ADMIN_FILE.'?exe=mail&a=list&topic_id='.$topic_id);
}

function AdminMailDeleteEmail(){
	System::database()->Delete('mail_list', "`topic_id`='".SafeEnv($_GET['topic_id'], 11, int)."' and `email`='".SafeEnv($_GET['email'], 50, str)."'");
	CalcListCounter(SafeEnv($_GET['topic_id'], 11, int), false);
	GO(ADMIN_FILE.'?exe=mail&a=list&topic_id='.SafeEnv($_GET['topic_id'], 11, int));
}
