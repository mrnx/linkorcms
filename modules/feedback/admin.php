<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('�������� �����');

if(isset($_GET['a'])){
	$action = $_GET['a'];
}else{
	$action = 'main';
}

TAddToolLink('������������', 'main', 'feedback');
TAddToolLink('�������� �����������', 'add', 'feedback&a=add');
TAddToolLink('���������', 'config', 'feedback&a=config');
TAddToolBox($action);
switch($action){
	case 'main':
		AdminFeedBackDepartments();
		break;
	case 'add':
	case 'edit':
		AdminFeedBackEditor();
		break;
	case 'save':
		AdminFeedBackSave();
		break;
	case 'changestatus':
		AdminFeedBackChangeStatus();
		break;
	case 'delete':
		AdminFeedBackDelete();
		break;
	case 'config':
		System::admin()->AddCenterBox('������������ ������ "�������� �����"');
		if(isset($_GET['saveok'])){
			System::admin()->Highlight('��������� ���������.');
		}
		System::admin()->ConfigGroups('feedback');
		System::admin()->AddConfigsForm(ADMIN_FILE.'?exe=feedback&a=configsave');
		break;
	case 'configsave':
		System::admin()->SaveConfigs('feedback');
		GO(ADMIN_FILE.'?exe=feedback&a=config&saveok');
		break;
	default:
		AdminFeedBackDepartments();
}

function AdminFeedBackDepartments(){
	System::database()->Select('feedback', '');
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>��������</th><th>E-mail</th><th>������</th><th>�������</th></tr>';
	while($row = System::database()->FetchRow()){
		$fid = SafeDB($row['id'], 11, int);
		$st = System::admin()->SpeedStatus('���.', '����.', ADMIN_FILE.'?exe=feedback&a=changestatus&id='.$fid, $row['active'] == '1');
		$func = System::admin()->SpeedButton('�������������', ADMIN_FILE.'?exe=feedback&a=edit&id='.$fid, 'images/admin/edit.png')
			.System::admin()->SpeedConfirm('�������', ADMIN_FILE.'?exe=feedback&a=delete&id='.$fid, 'images/admin/delete.png', '������� �����������?');

		$text .= '<tr>
		<td><b>'.System::admin()->Link(SafeEnv($row['name'], 255, str), ADMIN_FILE.'?exe=feedback&a=edit&id='.$fid, '�������������').'</b></td>
		<td>'.PrintEmail($row['email'], $row['name']).'</td>
		<td>'.$st.'</td>
		<td>'.$func.'</td>
		</tr>';
	}
	$text .= '</table>';
	AddTextBox('������������', $text);
}

function AdminFeedBackEditor(){
	global $site;
	$name = '';
	$email = '';
	$active = array(false, false);
	if(!isset($_GET['id'])){
		$active[1] = true;
		$headt = '����������';
		$bbb = '��������';
	}elseif(isset($_GET['id'])){
		$id = SafeEnv($_GET['id'], 11, int);
		System::database()->Select('feedback', "`id`='".$id."'");
		$fb = System::database()->FetchRow();
		$name = SafeDB($fb['name'], 255, str);
		$email = SafeDB($fb['email'], 50, str);
		$active[$fb['active']] = true;
		$headt = '��������������';
		$bbb = '��������� ���������';
		unset($fb);
	}
	FormRow('��������', $site->Edit('name', $name, false, 'style="width:200px;"'));
	FormRow('E-mail', $site->Edit('email', $email, false, 'style="width:200px;"'));
	FormRow('��������', $site->Select('enabled', GetEnData($active[1])));
	AddCenterBox($headt);
	AddForm('<form action="'.ADMIN_FILE.'?exe=feedback&a=save'.(isset($id) ? '&id='.$id : '').'" method="post">', $site->Submit($bbb));
}

function AdminFeedBackSave(){
	$name = SafeEnv($_POST['name'], 255, str);
	$email = SafeEnv($_POST['email'], 50, str);
	$active = EnToInt($_POST['enabled']);
	if(!isset($_GET['id'])){
		$vals = Values('', $name, $email, $active);
		System::database()->Insert('feedback', $vals);
	}else{
		$id = SafeEnv($_GET['id'], 11, int);
		$set = "name='$name',email='$email',active='$active'";
		System::database()->Update('feedback', $set, "`id`='".$id."'");
	}
	GO(ADMIN_FILE.'?exe=feedback');
}

function AdminFeedBackChangeStatus(){
	System::database()->Select('feedback', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	if(System::database()->NumRows() > 0){
		$r = System::database()->FetchRow();
		if($r['active'] == 1){
			$en = '0';
		}else{
			$en = '1';
		}
		System::database()->Update('feedback', "active='$en'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	}
	if(IsAjax()){
		exit("OK");
	}else{
		GO(ADMIN_FILE.'?exe=feedback');
	}
}

function AdminFeedBackDelete(){
	if(!isset($_GET['id'])){
		GO(ADMIN_FILE.'?exe=feedback');
	}
	System::database()->Delete('feedback', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	GO(ADMIN_FILE.'?exe=feedback');
}
