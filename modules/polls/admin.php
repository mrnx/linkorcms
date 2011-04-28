<?php

// ������ �������.

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('������');

if(!$user->CheckAccess2('polls', 'polls')){
	AddTextBox('������', $config['general']['admin_accd']);
	return;
}

//������������� ��������
$editpolls = $user->CheckAccess2('polls', 'edit');
$editconf = $user->CheckAccess2('polls', 'config');

function AdminPollsMainFunc()
{
	global $db, $config, $editpolls, $editcomments;
	$time = time();
	$polls = $db->Select('polls', '');
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable"><tr>';
	$text .= '<th>�����</th><th>����� �������������</th><th>�����������</th><th>�������</th><th>������</th><th>�������</th></tr>';
	foreach($polls as $poll){
		$pid = SafeDB($poll['id'], 11, int);
		switch($poll['active']){
			case '1':
				$active = '<font color="#008000">���.</font></a>';
				break;
			case '0':
				$active = '<font color="#FF0000">����.</font>';
				break;
		}
		if($editpolls){
			$active = '<a href="'.$config['admin_file'].'?exe=polls&a=changestatus&id='.$pid.'">'.$active.'</a>';
		}
		$answers = unserialize($poll['answers']);
		$c = count($answers);
		$num_voices = 0;
		for($i = 0; $i < $c; $i++){
			$num_voices += SafeDB($answers[$i][2], 11, int);
		}

		if($editpolls){
			$func = '';
			$func .= SpeedButton('�������������', $config['admin_file'].'?exe=polls&a=editor&id='.$pid, 'images/admin/edit.png');
			$func .= SpeedButton('�������', $config['admin_file'].'?exe=polls&a=delete&id='.$pid.'&ok=0', 'images/admin/delete.png');
		}else{
			$func = '-';
		}
		$text .= '<tr>
		<td><a href="'.$config['admin_file'].'?exe=polls&a=editor&id='.$pid.'"><b>'.SafeDB($poll['question'], 255, str).'</b></a></td>
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

function AdminPollsEditor()
{
	global $db, $site, $config, $editpolls;
	if(!$editpolls){
		AddTextBox('������', $config['general']['admin_accd']);
		return;
	}
	$view = array(1=>false, 2=>false, 3=>false, 4=>false);
	if(isset($_GET['id'])){ //��������������
		$title = '�������������� ������';
		$btitle = '���������';
		$id = SafeEnv($_GET['id'], 11, int);
		$db->Select('polls', "`id`='$id'");
		$p = $db->FetchRow();
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
	$anstext = '';
	for($i = 0, $c = count($answers); $i < $c; $i++){
		$anstext .= '<tr>'
			.'<td style="width:400px;">'.$site->Edit('answer[]', SafeDB($answers[$i][0], 255, str), false, 'style="width:100%;"').'</td>'
			.'<td>'.$site->Edit('color[]', SafeDB($answers[$i][1], 255, str), false, 'style="width:60px;"').'</td>'
			.'<td>'.$site->Edit('voices[]', SafeDB(strval($answers[$i][2]), 11, int), false, 'style="width:44px;text-align:right;"').'</td>'
			.'</tr>';
	}
	FormRow('������', '<table cellspacing="0" cellpadding="0" class="cfgtable">'
		.'<tr><th>�����</th><th>����</th><th>������</th></tr>'
		.$anstext
		.'</table>'
	);
	FormRow('��������� �����������', $site->Select('allow_comments', GetEnData($allow_comments, '��', '���')));
	FormRow('��������', $site->Select('active', GetEnData($active, '��', '���')));
	FormRow('��� ����� ��������', $site->Select('view', GetViewData($view)));
	AddCenterBox($title);
	AddForm('<form action="'.$config['admin_file'].'?exe=polls&a=save'.$uid.'" method="post">', $site->Button('������', 'onclick="history.go(-1)"').$site->Submit($btitle));
}

function AdminPollsSave()
{
	global $db, $editpolls;
	if(!$editpolls){
		AddTextBox('������', $config['general']['admin_accd']);
		return;
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
		$db->Update('polls', $set, "`id`='$poll_id'");
	}else{
		$vals = Values('', $poll, $desc, time(), $multianswers, $answers, $showinblock, $allow_comments, '0', $view, $active);
		$db->Insert('polls', $vals);
	}
	global $config;
	GO($config['admin_file'].'?exe=polls');
}

function AdminPollsDelete()
{
	global $config, $db, $editpolls;
	if(!$editpolls){
		AddTextBox('������', $config['general']['admin_accd']);
		return;
	}
	if(!isset($_GET['id'])){
		GO($config['admin_file'].'?exe=polls');
		exit();
	}
	if(isset($_GET['ok']) && $_GET['ok'] == '1'){
		$id = SafeEnv($_GET['id'], 11, int);
		$db->Delete('polls', "`id`='$id'");
		$db->Delete('polls_comments', "`object_id`='$id'");
		GO($config['admin_file'].'?exe=polls');
		exit();
	}else{
		$r = $db->Select('polls', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
		$text = '�� ������������� ������ ������� ����� "'.SafeDB($r[0]['question'], 255, str).'" ?<br />'
		.'<a href="'.$config['admin_file'].'?exe=polls&a=delete&id='.SafeEnv($_GET['id'], 11, int).'&ok=1">��</a>'
		.' &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">���</a>';
		AddTextBox("��������!", $text);
	}
}

function AdminPollsChangeStatus()
{
	global $config, $db, $editpolls;
	if(!$editpolls){
		AddTextBox('������', $config['general']['admin_accd']);
		return;
	}
	$db->Select('polls', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	$r = $db->FetchRow();
	if($r['active'] == 1){
		$en = '0';
	}else{
		$en = '1';
	}
	$db->Update('polls', "active='$en'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	GO($config['admin_file'].'?exe=polls');
}
if(isset($_GET['a'])){
	$action = $_GET['a'];
}else{
	$action = 'main';
}

include_once ($config['inc_dir'].'configuration/functions.php');

function AdminPolls()
{
	global $action, $editpolls, $editconf;

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
	switch($action){
		case 'main':
			AdminPollsMainFunc();
			break;
		case 'editor':
			AdminPollsEditor();
			break;
		case 'save':
			AdminPollsSave();
			break;
		case 'delete':
			AdminPollsDelete();
			break;
		case 'changestatus':
			AdminPollsChangeStatus();
			break;
		///////////////// ���������
		case 'config':
			AdminConfigurationEdit('polls', 'polls', true, false, '������������ ������ "������"');
			break;
		case 'configsave':
			AdminConfigurationSave('polls&a=config', 'polls', true, false);
			break;
		default:
			AdminPollsMainFunc();
	}
}

AdminPolls();

?>