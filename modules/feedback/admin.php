<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('�������� �����');

function AdminFeedBackDepartments()
{
	global $config, $db;
	$db->Select('feedback', '');
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>��������</th><th>E-mail</th><th>������</th><th>�������</th></tr>';
	while($row = $db->FetchRow()){
		$fid = SafeDB($row['id'], 11, int);
		switch($row['active']){
			case '1':
				$st = '<a href="'.ADMIN_FILE.'?exe=feedback&a=changestatus&id='.$fid.'" title="���������"><font color="#008000">���.</font></a>';
				break;
			case '0':
				$st = '<a href="'.ADMIN_FILE.'?exe=feedback&a=changestatus&id='.$fid.'" title="��������"><font color="#FF0000">����.</font></a>';
				break;
		}
		$func = '';
		$func .= SpeedButton('�������������', ADMIN_FILE.'?exe=feedback&a=edit&id='.$fid, 'images/admin/edit.png');
		$func .= SpeedButton('�������', ADMIN_FILE.'?exe=feedback&a=delete&id='.$fid.'&ok=0', 'images/admin/delete.png');

		$text .= '<tr><td><b><a href="'.ADMIN_FILE.'?exe=feedback&a=edit&id='.$fid.'" title="�������������">'.SafeEnv($row['name'], 255, str).'</b></td>
		<td>'.PrintEmail($row['email'], $row['name']).'</td>
		<td>'.$st.'</td>
		<td>'.$func.'</td>
		</tr>';
	}
	$text .= '</table>';
	AddTextBox('������������', $text);
}

function AdminFeedBackEditor()
{
	global $config, $db, $site;
	$name = '';
	$email = '';
	$active = array(false, false);
	if(!isset($_GET['id'])){
		$active[1] = true;
		$headt = '����������';
		$bbb = '��������';
	}elseif(isset($_GET['id'])){
		$id = SafeEnv($_GET['id'], 11, int);
		$db->Select('feedback', "`id`='".$id."'");
		$fb = $db->FetchRow();
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

function AdminFeedBackSave()
{
	global $db, $config;
	$name = SafeEnv($_POST['name'], 255, str);
	$email = SafeEnv($_POST['email'], 50, str);
	$active = EnToInt($_POST['enabled']);
	if(!isset($_GET['id'])){
		$vals = Values('', $name, $email, $active);
		$db->Insert('feedback', $vals);
	}else{
		$id = SafeEnv($_GET['id'], 11, int);
		$set = "name='$name',email='$email',active='$active'";
		$db->Update('feedback', $set, "`id`='".$id."'");
	}
	GO(ADMIN_FILE.'?exe=feedback');
}

function AdminFeedBackChangeStatus()
{
	global $config, $db;
	$db->Select('feedback', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	if($db->NumRows() > 0){
		$r = $db->FetchRow();
		if($r['active'] == 1){
			$en = '0';
		}else{
			$en = '1';
		}
		$db->Update('feedback', "active='$en'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	}
	GO(ADMIN_FILE.'?exe=feedback');
}

function AdminFeedBackDelete()
{
	global $config, $db;
	if(!isset($_GET['id'])){
		GO(ADMIN_FILE.'?exe=feedback');
	}
	if(isset($_GET['ok']) && $_GET['ok'] == '1'){
		$db->Delete('feedback', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
		GO(ADMIN_FILE.'?exe=feedback');
	}else{
		$r = $db->Select('feedback', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
		$text = '�� ������������� ������ ������� ����������� "'.SafeDB($r[0]['name'], 255, str).'"<br />'
			.'<a href="'.ADMIN_FILE.'?exe=feedback&a=delete&id='.SafeEnv($_GET['id'], 11, int).'&ok=1">��</a> &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">���</a>';
		AddTextBox("��������!", $text);
	}
}
include_once ($config['inc_dir'].'configuration/functions.php');

function AdminFeedBack( $action )
{

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
			AdminConfigurationEdit('feedback', 'feedback', true, false, '������������ ������ "�������� �����"');
			break;
		case 'configsave':
			AdminConfigurationSave('feedback&a=config', 'feedback', true);
			break;
		default:
			AdminFeedBackDepartments();
	}
}

if(isset($_GET['a'])){
	AdminFeedBack($_GET['a']);
}else{
	AdminFeedBack('main');
}

?>