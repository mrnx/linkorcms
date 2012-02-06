<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('���������');

if(!$user->CheckAccess2('messages', 'messages')){
	AddTextBox('������', $config['general']['admin_accd']);
	return;
}

function AdminSiteMessagesMain()
{
	global $db, $config;
	$db->Select('messages', '');
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>��������</th><th>�������� �������</th><th>��� �����</th><th>������</th><th>�������</th></tr>';
	while($msg = $db->FetchRow()){
		$mid = SafeDB($msg['id'], 11, int);
		switch($msg['active']){
			case "1":
				$st = '<a href="'.ADMIN_FILE.'?exe=messages&a=changestatus&id='.$mid.'" title="�������� ������"><font color="#008000">���.</font></a>';
				break;
			case "0":
				$st = '<a href="'.ADMIN_FILE.'?exe=messages&a=changestatus&id='.$mid.'" title="�������� ������"><font color="#FF0000">����.</font></a>';
				break;
		}
		$resettime = '';
		if($msg['expire'] != '0'){
			$total = TotalTime(time(), SafeDB($msg['date'], 11, int) + (Day2Sec * SafeDB($msg['expire'], 11, int)));
			if($total['days'] > 0 || $total['hours'] > 0){
				if($total['days'] > 0){
					$resettime .= $total['sdays'];
					if($total['hours'] > 0){
						$resettime .= ' � ';
					}
				}
				if($total['hours'] > 0){
					$resettime .= $total['shours'];
				}
			}else{
				$resettime = '����� �����';
			}
		}else{
			$resettime = '�������������';
		}

		$func = '';
		$func .= SpeedButton('�������������', ADMIN_FILE.'?exe=messages&a=msgeditor&id='.$mid, 'images/admin/edit.png');
		$func .= SpeedButton('�������', ADMIN_FILE.'?exe=messages&a=delete&id='.$mid.'&ok=0', 'images/admin/delete.png');

		$text .= '<tr><td><b><a href="'.ADMIN_FILE.'?exe=messages&a=msgeditor&id='.$mid.'" title="�������������">'.SafeDB($msg['title'], 250, str).'</a></b></td>
		<td>'.$resettime.'</td>
		<td>'.ViewLevelToStr(SafeDB($msg['view'], 1, int)).'</td>
		<td>'.$st.'</td>
		<td>'.$func.'</td>
		</tr>
		';
	}
	$text .= '</table>';
	AddTextBox('��� ���������', $text);
}

function AdminSiteMessagesEditor()
{
	global $site, $db, $config;
	$title = '';
	$text = '';
	$showin = array();
	$extrauri = '';
	$time = '0';
	$view = array(1=>false, 2=>false, 3=>false, 4=>false);
	$enabled = array(false, false);
	$view_title = array(false, false);
	$position = array(false, false);
	$resettime = '';
	$a = 'add';
	if(!isset($_GET['id'])){
		$view[4] = true;
		$enabled[1] = true;
		$view_title[1] = true;
		$position[1] = true;
		$url = '';
		$btitle = '���������� ���������';
		$method = '��������';
		$a = 'add';
	}else{
		$id = SafeEnv($_GET['id'], 11, int);
		$db->Select('messages', "`id`='$id'");
		$msg = $db->FetchRow();
		$title = SafeDB($msg['title'], 250, str);
		$text = SafeDB($msg['text'], 0, str, false);
		$time = SafeDB($msg['expire'], 11, int);
		if($time != '0'){
			$total = TotalTime(time(), SafeDB($msg['date'], 11, int) + (Day2Sec * SafeDB($msg['expire'], 11, int)));
			$resettime = '�������� ������� '.$total['sdays'].' � '.$total['shours'];
		}
		$showin = unserialize($msg['showin']);
		$extrauri = unserialize($msg['showin_uri']);
		$extrauri = implode("\r\n", $extrauri);
		$extrauri = SafeDB($extrauri, 250, str);
		$view_title[SafeDB($msg['view_title'], 1, int)] = true;
		$position[SafeDB($msg['position'], 1, int)] = true;
		$view[SafeDB($msg['view'], 1, int)] = true;
		$enabled[SafeDB($msg['active'], 1, int)] = true;
		$url = '&id='.$id;
		$btitle = '�������������� ���������';
		$method = '��������� ���������';
		$a = 'edit';
	}
	unset($msg);
	// ������� ������ ����������
	// ��� �����
	$site->DataAdd($visdata, 'all', '���', $view['4']);
	$site->DataAdd($visdata, 'members', '������ ������������', $view['2']);
	$site->DataAdd($visdata, 'guests', '������ �����', $view['3']);
	$site->DataAdd($visdata, 'admins', '������ ��������������', $view['1']);
	FormRow('���������', $site->Edit('title', $title, false, 'maxlength="250" style="width:400px;"'));
	FormRow('���������� ���������', $site->Radio('vtitle', 'on', $view_title[1]).'��&nbsp;'.$site->Radio('vtitle', 'off', $view_title[0]).'���');
	FormTextRow('����� ���������', $site->HtmlEditor('text', $text, 625, 300));
	FormRow('����������&nbsp;����:<br /><small> (0 - �������������)</small>', $site->Edit('time', $time, false, 'maxlength="3" style="width:40px;"').$resettime);
	if($a == 'edit'){
		FormRow('�������� ������', $site->Check('resettime', '1', false));
	}
	//������� ��������� ��� ������ �� ���� ������ ������� �������� � �������������
	$mods = $db->Select('modules', "`isindex`='1'");
	array_unshift($mods, array('name'=>'������� ��������', 'folder'=>'INDEX'));
	//$showin = AdminsGetAccessArray($showin);
	$ac = '';
	$num = 0;
	$ac .= '<table width="100%" cellspacing="0" cellpadding="0" style="border:0px #ABC5D8 solid;margin-bottom:1px;"><tr>';
	$ac .= '<td style="border:none;">'.$site->Radio('showin[]', 'SELECT_ONLY', in_array('SELECT_ONLY', $showin) || !in_array('ALL_EXCEPT', $showin)).'���������� ������ �:</td><td style="border:none;">'.$site->Radio('showin[]', 'ALL_EXCEPT', in_array('ALL_EXCEPT', $showin)).'���������� �����, �����:</td>';
	$ac .= '</tr></table>';
	$ac .= '<table width="100%" cellspacing="0" cellpadding="2" style="border:1px #ABC5D8 solid;margin-bottom:1px;">';
	foreach($mods as $a){
		if($num == 0){
			$ac .= '<tr>';
		}
		$num++;
		$ac .= '<td style="border:none;">'.$site->Check('showin[]', SafeDB($a['folder'], 255, str), in_array(SafeDB($a['folder'], 255, str), $showin)).SafeDB($a['name'], 255, str).'</td>';
		if($num == 3){
			$ac .= '</tr>';
			$num = 0;
		}
	}
	if($num != 0){
		$ac .= '</tr>';
	}
	$ac .= '</table>';
	$ac .= '<table width="100%" cellspacing="0" cellpadding="2" style="border:1px #ABC5D8 solid;margin-bottom:1px;">';
	$ac .= '<tr><td style="border:none;">�������������� URI<br /><small>��������: /index.php?name=pages&amp;file=page ��� /pages/page.html. �� ������ �� ������.</small></td></tr>';
	$ac .= '<tr><td style="border:none;">'.$site->TextArea('extra_uri', $extrauri, 'style="width:400px;height:100px;"').'</td></tr>';
	$ac .= '</table>';
	FormRow('��������', $ac);
	$site->DataAdd($posd, 'top', '������', $position[1]);
	$site->DataAdd($posd, 'bottom', '�����', $position[0]);
	FormRow('���������', $site->Select('position', $posd));
	FormRow('��� �����', $site->Select('view', $visdata));
	FormRow('��������', $site->Select('enabled', GetEnData($enabled[1])));
	AddCenterBox($btitle);
	AddForm('<form action="'.ADMIN_FILE.'?exe=messages&a=save'.$url.'" method="post">', $site->Button('������', 'onclick="history.go(-1)"').$site->Submit($method));
}

function AdminSiteMessagesSave()
{
	global $config, $db;
	$title = SafeEnv($_POST['title'], 250, str);
	$view_title = EnToInt($_POST['vtitle']);
	$text = SafeEnv($_POST['text'], 0, str);
	$time = SafeEnv($_POST['time'], 3, int);
	$date = time();
	$view = ViewLevelToInt($_POST['view']);
	$active = EnToInt($_POST['enabled']);
	switch($_POST['position']){
		case 'top':
			$pos = '1';
			break;
		case 'bottom':
			$pos = '0';
			break;
		default:
			$pos = '1';
	}
	$showin = SafeEnv($_POST['showin'], 255, str);
	$showin = serialize($showin);
	//������ ������������ URI
	$extra_uri = explode("\r\n", $_POST['extra_uri']);
	$extra_uri = SafeEnv($extra_uri, 255, str);
	$extra_uri = serialize($extra_uri);
	//���������� ��� ��������� ������ � ���� ������
	if(!isset($_GET['id'])){
		$vals = "'','$title','$text','$date','$time','$showin','$extra_uri','$pos','$view_title','$view','$active'";
		$db->Insert('messages', $vals);
	}else{
		$id = SafeEnv($_GET['id'], 11, int);
		if(!isset($_POST['resettime'])){
			$db->Select('messages', "`id`='$id'");
			if($db->NumRows() > 0){
				$msg = $db->FetchRow();
				$date = $msg['date'];
			}else{
				$date = time();
			}
		}
		$vals = "'','$title','$text','$date','$time','$showin','$extra_uri','$pos','$view_title','$view','$active'";
		$db->Update('messages', $vals, "`id`='$id'", true);
	}
	GO(ADMIN_FILE.'?exe=messages');
	exit();
}

function AdminSiteMessagesDelete()
{
	global $config, $db;
	if(!isset($_GET['id'])){
		GO(ADMIN_FILE.'?exe=messages');
		exit();
	}
	if(isset($_GET['ok']) && $_GET['ok'] == '1'){
		$db->Delete('messages', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
		GO(ADMIN_FILE.'?exe=messages');
		exit();
	}else{
		$r = $db->Select('messages', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
		$text = '�� ������������� ������ ������� ��������� '.$r[0]['title'].'?<br />'.'<a href="'.ADMIN_FILE.'?exe=messages&a=delete&id='.SafeEnv($_GET['id'], 11, int).'&ok=1">��</a> &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">���</a>';
		AddTextBox("��������!", $text);
	}
}

function AdminSiteMessagesChangeStatus()
{
	global $config, $db;
	$db->Select('messages', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	if($db->NumRows() > 0){
		$r = $db->FetchRow();
		if($r['active'] == 1){
			$en = '0';
		}else{
			$en = '1';
		}
		$db->Update('messages', "active='$en'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	}
	GO(ADMIN_FILE.'?exe=messages');
	exit();
}

function AdminSiteMessages( $action )
{
	TAddToolLink('��� ���������', 'main', 'messages');
	TAddToolLink('�������� ���������', 'msgeditor', 'messages&a=msgeditor');
	TAddToolBox($action);
	switch($action){
		case 'main':
			AdminSiteMessagesMain();
			break;
		case 'msgeditor':
			AdminSiteMessagesEditor();
			break;
		case 'save':
			AdminSiteMessagesSave();
			break;
		case 'delete':
			AdminSiteMessagesDelete();
			break;
		case 'changestatus':
			AdminSiteMessagesChangeStatus();
			break;
		default:
			die('No Hack');
	}
}

if(isset($_GET['a'])){
	AdminSiteMessages($_GET['a']);
}else{
	AdminSiteMessages('main');
}

?>