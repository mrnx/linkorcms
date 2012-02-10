<?php

// ������ �������.

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('������');

if(!$user->CheckAccess2('polls', 'polls')){
	System::admin()->AccessDenied();
}

//������������� ��������
$editpolls = $user->CheckAccess2('polls', 'edit');
$editconf = $user->CheckAccess2('polls', 'config');

if(isset($_GET['a'])){
	$action = $_GET['a'];
}else{
	$action = 'main';
}

// ����
if($editconf || $editpolls){
	TAddToolLink('������', 'main', 'polls');
}
if($editpolls){
	TAddToolLink('�������� �����', 'editor', 'polls&a=editor');
}
if($editconf){
	TAddToolLink('������������', 'config', 'polls&a=config');
}
TAddToolBox($action);
//

switch($action){
	case 'main': AdminPollsMainFunc();
		break;
	case 'editor': AdminPollsEditor();
		break;
	case 'save': AdminPollsSave();
		break;
	case 'delete': AdminPollsDelete();
		break;
	case 'changestatus': AdminPollsChangeStatus();
		break;
	case 'config':
		if(!$editconf){
			System::admin()->AccessDenied();
		}
		System::admin()->AddCenterBox('������������ ������ "������"');
		if(isset($_GET['saveok'])){
			System::admin()->Highlight('��������� ���������.');
		}
		System::admin()->ConfigGroups('polls');
		System::admin()->AddConfigsForm(ADMIN_FILE.'?exe=polls&a=configsave');
		break;
	case 'configsave':
		if(!$editconf){
			System::admin()->AccessDenied();
		}
		System::admin()->SaveConfigs('polls');
		GO(ADMIN_FILE.'?exe=polls&a=config&saveok');
		break;
	default: AdminPollsMainFunc();
}

function AdminPollsMainFunc(){
	global $editpolls, $editcomments;
	$time = time();
	$polls = System::database()->Select('polls', '');
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable"><tr>';
	$text .= '<th>�����</th><th>����� �������������</th><th>�����������</th><th>������</th><th>������</th><th>�������</th></tr>';
	foreach($polls as $poll){
		$pid = SafeDB($poll['id'], 11, int);
		if($poll['active'] == '1'){
			$active = '<font color="#008000">���.</font></a>';
		}else{
			$active = '<font color="#FF0000">����.</font>';
		}
		if($editpolls){
			$active = System::admin()->SpeedStatus('���.', '����.', ADMIN_FILE.'?exe=polls&a=changestatus&id='.$pid, $poll['active'] == '1');
		}
		$answers = unserialize($poll['answers']);
		$c = count($answers);
		$num_voices = 0;
		for($i = 0; $i < $c; $i++){
			$num_voices += SafeDB($answers[$i][2], 11, int);
		}

		if($editpolls){
			$func = '';
			$func .= System::admin()->SpeedButton('�������������', ADMIN_FILE.'?exe=polls&a=editor&id='.$pid, 'images/admin/edit.png');
			$func .= System::admin()->SpeedConfirm('�������', ADMIN_FILE.'?exe=polls&a=delete&id='.$pid, 'images/admin/delete.png', '������� �����?');
		}else{
			$func = '-';
		}
		$text .= '<tr>
		<td><b>'.System::admin()->Link(SafeDB($poll['question'], 255, str), ADMIN_FILE.'?exe=polls&a=editor&id='.$pid).'</b></td>
		<td>'.$num_voices.'</td>
		<td>'.SafeDB($poll['com_counter'], 11, int).'</td>
		<td>'.ViewLevelToStr($poll['view']).'</td>
		<td>'.$active.'</td>
		<td>'.$func.'</td>
		</tr>';
	}
	$text .= '</table>';
	AddTextBox('������', $text);
}

function AdminPollsEditor(){
	global $site, $config, $editpolls;
	if(!$editpolls){
		System::admin()->AccessDenied();
	}
	$view = array(1=>false, 2=>false, 3=>false, 4=>false);
	if(isset($_GET['id'])){ //��������������
		$title = '�������������� ������';
		$btitle = '���������';
		$id = SafeEnv($_GET['id'], 11, int);
		System::database()->Select('polls', "`id`='$id'");
		$p = System::database()->FetchRow();
		$poll = SafeDB($p['question'], 255, str);
		$desc = SafeDB($p['description'], 255, str);
		$showinblock = SafeDB($p['showinblock'], 1, int);
		$multianswers = SafeDB($p['multianswers'], 1, int);
		//������
		$answers = unserialize($p['answers']);
		$allow_comments = SafeDB($p['allow_comments'], 1, int);
		$active = $p['active'];
		$view[$p['view']] = true;
		$uid = '&id='.$id;
	}else{ //����������
		$title = '�������� �����';
		$btitle = '��������';
		$poll = '';
		$desc = '';
		$end_date = time();
		$end_n = true;
		$showinblock = true;
		$multianswers = false;
		for($i = 0; $i < $config['polls']['editor_max_answers']; $i++){
			$answers[] = array('', '', '0');
		}
		$allow_comments = true;
		$active = true;
		$view[4] = true;
		$uid = '';
	}
	FormRow('������', $site->Edit('poll', $poll, false, 'maxlength="250" style="width:400px;"'));
	FormRow('��������', $site->Edit('desc', $desc, false, 'maxlength="250" style="width:400px;"'));
	FormRow('���������� � �����', $site->Select('showinblock', GetEnData($showinblock, '��', '���')));
	FormRow('������������', $site->Select('multianswers', GetEnData($multianswers, '��', '���')));
	$anwers_form = '';
	for($i = 0, $c = count($answers); $i < $c; $i++){
		$anwers_form .= '<tr>'
			.'<td>'.($i+1).'.&nbsp;'.$site->Edit('answer[]', SafeDB($answers[$i][0], 255, str), false, 'style="width:90%;"').'</td>'
			.'<td>'.$site->Edit('color[]', SafeDB($answers[$i][1], 255, str), false, 'style="width:80px;"').'</td>'
			.'<td>'.$site->Edit('voices[]', SafeDB(strval($answers[$i][2]), 11, int), false, 'style="width:50px; text-align:right;"').'</td>'
			.'</tr>';
	}
	FormRow('������', '<table cellspacing="0" cellpadding="0" class="cfgtable">'
		.'<tr><th style="width:400px;">�����</th><th style="width:100px;">����</th><th style="width:100px;">������</th></tr>'
		.$anwers_form
		.'</table>'
	);
	FormRow('��������� �����������', $site->Select('allow_comments', GetEnData($allow_comments, '��', '���')));
	FormRow('��������', $site->Select('active', GetEnData($active, '��', '���')));
	FormRow('��� ����� ��������', $site->Select('view', GetUserTypesFormData($view)));
	AddCenterBox($title);
	AddForm('<form action="'.ADMIN_FILE.'?exe=polls&a=save'.$uid.'" method="post">', $site->Button('������', 'onclick="history.go(-1)"').$site->Submit($btitle));
}

function AdminPollsSave(){
	global $editpolls;
	if(!$editpolls){
		System::admin()->AccessDenied();
	}
	$poll = SafeEnv($_POST['poll'], 255, str);
	$desc = SafeEnv($_POST['desc'], 255, str);
	$showinblock = EnToInt($_POST['showinblock']);
	$multianswers = EnToInt($_POST['multianswers']);
	$allow_comments = EnToInt($_POST['allow_comments']);
	$active = EnToInt($_POST['active']);
	$view = ViewLevelToInt($_POST['view']);
	$answer = SafeEnv($_POST['answer'], 255, str);
	$color = SafeEnv($_POST['color'], 8, str);
	$voices = SafeEnv($_POST['voices'], 11, int);
	$cnt = count($answer);
	$answers = array();
	for($i = 0; $i < $cnt; $i++){
		$answers[] = array($answer[$i], $color[$i], $voices[$i]);
	}
	$answers = serialize($answers);
	$answers = SafeEnv($answers, 0, str, false, true, false);
	if(isset($_GET['id'])){ //��������������
		$set = "question='$poll',description='$desc',multianswers='$multianswers',answers='$answers',showinblock='$showinblock',allow_comments='$allow_comments',view='$view',active='$active'";
		$poll_id = SafeEnv($_GET['id'], 11, int);
		System::database()->Update('polls', $set, "`id`='$poll_id'");
	}else{
		$vals = Values('', $poll, $desc, time(), $multianswers, $answers, $showinblock, $allow_comments, '0', $view, $active);
		System::database()->Insert('polls', $vals);
	}
	GO(ADMIN_FILE.'?exe=polls');
}

// �������� ������
function AdminPollsDelete(){
	global $editpolls;
	if(!$editpolls){
		System::admin()->AccessDenied();
	}
	if(!isset($_GET['id'])){
		GO(ADMIN_FILE.'?exe=polls');
	}
	$id = SafeEnv($_GET['id'], 11, int);
	System::database()->Delete('polls', "`id`='$id'");
	System::database()->Delete('polls_comments', "`object_id`='$id'");
	GO(ADMIN_FILE.'?exe=polls');
}

// ��������� �������
function AdminPollsChangeStatus(){
	global $editpolls;
	if(!$editpolls){
		System::admin()->AccessDenied();
	}
	$id = SafeEnv($_GET['id'], 11, int);
	System::database()->Select('polls', "`id`='$id'");
	$r = System::database()->FetchRow();
	if($r['active'] == '1'){
		$en = '0';
	}else{
		$en = '1';
	}
	System::database()->Update('polls', "active='$en'", "`id`='$id'");
	if(IsAjax()){
		exit("OK");
	}else{
		GO(ADMIN_FILE.'?exe=polls');
	}
}
