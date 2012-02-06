<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('��������');

if(!$user->CheckAccess2('smilies', 'smilies')){
	AddTextBox('������', $config['general']['admin_accd']);
	return;
}

$smilies_dir = 'uploads/smilies/';
$mod = ADMIN_FILE.'?exe=smilies';

function AdminSmilesGetAllSmiles( &$sid, $dir_name, $selected = '', $smilies = array() )
{
	global $site, $smilies_dir;
	static $i = -1;
	static $sfiles = array();
	static $xor_smilies = array();
	static $xsm = false;
	if(!$xsm){
		foreach($smilies as $sm){
			$xor_smilies[$sm['file']] = true;
		}
	}
	$dir = @opendir($dir_name);
	while($file = @readdir($dir)){
		if(is_dir($dir_name.$file) && ($file != '.') && ($file != '..')){
			AdminSmilesGetAllSmiles($sid, $dir_name.$file.'/', $selected, $smilies);
		}else{
			$ext = GetFileExt($file);
			if($ext == '.gif' || $ext == '.png'){
				$rf = str_replace($smilies_dir, '', $dir_name).$file;
				if(!isset($xor_smilies[$rf]) || $selected == $rf){
					$site->DataAdd($sfiles, $rf, $rf, $selected == $rf);
					$i++;
				}
				if($selected != $rf){
					$sel = false;
				}else{
					$sel = true;
					$sid = $i;
				}
			}
		}
	}
	return $sfiles;
}

function AdminSmilesMain()
{
	global $db, $config, $site, $smilies_dir, $mod;
	$smilies = $db->Select('smilies', '');
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>�����������</th><th>���</th><th>��������</th><th>��� �����</th><th>��������</th><th>�������</th></tr>';
	while($row = $db->FetchRow()){
		$sid = SafeDB($row['id'], 11, int);
		if(!is_file($smilies_dir.$row['file'])){
			$db->Delete('smilies', "`file`='".SafeEnv($row['file'], 255, str)."'");
			continue;
		}
		switch($row['enabled']){
			case "1":
				$en = '<a href="'.$mod.'&a=changestatus&id='.$sid.'" title="�������� ������"><font color="#008000">��</font></a>';
				break;
			case "0":
				$en = '<a href="'.$mod.'&a=changestatus&id='.$sid.'" title="�������� ������"><font color="#FF0000">���</font></a>';
				break;
		}

		$func = '';
		$func .= SpeedButton('�������������', $mod.'&a=editsmile&id='.$sid, 'images/admin/edit.png');
		$func .= SpeedButton('�������', $mod.'&a=delsmile&sid='.$sid, 'images/admin/delete.png');

		$text .= "<tr><td><a href=\"$mod&a=editsmile&id=$sid\"><img src=\"$smilies_dir{$row['file']}\" /></a></td><td>{$row['code']}</td><td>{$row['desc']}</td><td>{$row['file']}</td><td>$en</td><td>$func</td></tr>";
	}
	$text .= '</table><br />.:�������� �������:.';
	$sfiles = AdminSmilesGetAllSmiles($id, $smilies_dir, '', $smilies);
	if(isset($sfiles[0])){
		$fname = $sfiles[0]['name'];
	}else{
		$fname = '';
	}
	AddCenterBox('��������');
	AddText($text);
	// ----------------------------------
	if(count($sfiles) > 0){
		FormRow('�����������', $site->Select('file', $sfiles, false, "onchange=\"document.newsmile.image.src='$smilies_dir'+document.newsmile.file.value\""));
		FormRow('������������', "<img id=\"image\" src=\"$smilies_dir$fname\" />");
		FormRow('���', $site->Edit('code'));
		FormRow('��������', $site->Edit('desc'));
		FormRow('����������', $site->Radio('indexview', 'on', true).'��&nbsp;'.$site->Radio('indexview', 'off').'���');
		AddForm("<form name=\"newsmile\" action=\"$mod&a=addsmile\" method=\"post\">", $site->Submit('��������'));
	}else{
		AddText('<br />����� ������ �� �������.');
	}
}

function AdminSmilesEditSmile()
{
	global $db, $config, $site, $smilies_dir, $mod;
	$id = SafeEnv($_GET['id'], 11, int);
	$smilies = $db->Select('smilies', '');
	$db->Select('smilies', "`id`='$id'");
	$smd = $db->FetchRow();
	$sfiles = AdminSmilesGetAllSmiles($sid, $smilies_dir, $smd['file'], $smilies);
	$en = array(false, false);
	$en[$smd['enabled']] = true;
	FormRow('�����������', $site->Select('file', $sfiles, false, "style=\"width:130px;\" onchange=\"document.newsmile.image.src='$smilies_dir'+document.newsmile.file.value\""));
	FormRow('������������', '<img id="image" src="'.$smilies_dir.$sfiles[$sid]['name'].'" />');
	FormRow('���', $site->Edit('code', $smd['code']));
	FormRow('��������', $site->Edit('desc', $smd['desc']));
	FormRow('����������', $site->Radio('indexview', 'on', $en[1]).'��&nbsp;'.$site->Radio('indexview', 'off', $en[0]).'���');
	AddCenterBox('�������������� ��������');
	AddForm('<form name="newsmile" action="'.$mod.'&a=seditsave&id='.$id.'" method="post">', $site->Button('������', 'onclick="history.go(-1)"').$site->Submit('���������'));
}

function AdminSmilesEditSave()
{
	global $db, $config, $mod;
	$id = SafeEnv($_GET['id'], 11, int);
	$disp = EnToInt(SafeEnv($_POST['indexview'], 3, str));
	$vals = Values('', SafeEnv($_POST['code'], 30, str), SafeEnv($_POST['desc'], 255, str), SafeEnv($_POST['file'], 255, str), $disp);
	$db->Update('smilies', $vals, "`id`='$id'", true);
	GO($mod);
}

function AdminSmilesAddSave()
{
	global $db, $config, $mod;
	$disp = EnToInt(SafeEnv($_POST['indexview'], 3, str));
	$vals = Values('', SafeEnv($_POST['code'], 30, str), SafeEnv($_POST['desc'], 255, str), SafeEnv($_POST['file'], 255, str), $disp);
	$db->Insert('smilies', $vals);
	GO($mod);
}

function AdminSmilesDeleteSmile()
{
	global $config, $db, $smilies_dir, $mod;
	if(isset($_GET['ok']) && $_GET['ok'] == '1'){
		$db->Delete('smilies', "`id`='".SafeEnv($_GET['sid'], 11, int)."'");
		GO($mod);
	}else{
		$r = $db->Select('smilies', "`id`='".SafeEnv($_GET['sid'], 11, int)."'");
		$text = '�� ������������� ������ ������� ������� <img src="'.$smilies_dir.$r[0]['file'].'" /> ?<br />'
		."<a href=\"$mod&a=delsmile&sid=".SafeEnv($_GET['sid'], 11, int).'&ok=1">��</a>'
		.' &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">���</a>
		<br />
		<br />';
		AddTextBox("��������", $text);
	}
}

function AdminBlocksChangeStatus()
{
	global $config, $db, $mod;
	$db->Select('smilies', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	if($db->NumRows() > 0){
		$r = $db->FetchRow();
		if(SafeDB($r['enabled'], 1, int) == 1){
			$en = '0';
		}else{
			$en = '1';
		}
		$db->Update('smilies', "enabled='$en'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	}
	GO($mod);
}

function AdminSmiliesAutoAdd()
{
	global $mod, $smilies_dir, $db, $site, $config;

	$smilies = $db->Select('smilies', '');
	$sfiles = AdminSmilesGetAllSmiles($id, $smilies_dir, '', $smilies);
	if(count($sfiles) == 0){
		AddTextBox('������' ,'<br />����� ������ �� �������. ��������� ����������� ��������� � �����: <b>'.$smilies_dir.'</b>.<br /><br />');
		return;
	}

	$text = '';
	$text .= $site->FormOpen(ADMIN_FILE.'?exe=smilies&a=autosave');
	$text .= '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr>
		<th>��������</th>
		<th>�����������</th>
		<th>���</th>
		<th>��������</th>
		<th>��� �����</th>
		<th>����� �� �������</th>
	</tr>';

	foreach($sfiles as $sm){
		$text .= '<tr>'
		.'<td>'.$site->Check('smilies[]', $sm['name'], true).'</td>'
		.'<td><img src="'.$smilies_dir.$sm['name'].'" /></td>'
		.'<td>'.$site->Edit('code['.$sm['name'].']', '*'.GetFileName($sm['name']).'*', false, 'style="width:160px;"').'</td>'
		.'<td>'.$site->Edit('desc['.$sm['name'].']', '', false, 'style="width:160px;"').'</td>'
		.'<td>'.$sm['name'].'</td>'
		.'<td>'.$site->Check('en['.$sm['name'].']', '1', true).'</td>'
		.'</tr>';
	}
	$text .= '</table><br />';
	$text .= $site->Submit('��������').'<br /><br />';
	$text .= $site->FormClose();

	AddCenterBox('����-���������� ���������');
	AddText($text);
}

function AdminSmiliesAutoSave()
{
	global $db, $mod;
	foreach($_POST['smilies'] as $file){
		$file = RealPath2(SafeEnv($file, 255, str));
		$code = SafeEnv($_POST['code'][$file], 255, str);
		$desc = SafeEnv($_POST['desc'][$file], 255, str);
		$disp = (isset($_POST['en'][$file]) ? '1' : '0');
		$vals = Values('', $code, $desc, $file, $disp);
		$db->Insert('smilies', $vals);
	}
	GO($mod);
}

if(isset($_GET['a'])){
	$action = $_GET['a'];
}else{
	$action = 'main';
}

function AdminSmiles()
{
	global $action;

	TAddToolLink('��������', 'main', 'smilies');
	TAddToolLink('����-����������', 'auto', 'smilies&a=auto');
	TAddToolBox($action);
	
	switch($action){
		case 'main':
			AdminSmilesMain();
			break;
		case 'addsmile':
			AdminSmilesAddSave();
			break;
		case 'delsmile':
			AdminSmilesDeleteSmile();
			break;
		case 'editsmile':
			AdminSmilesEditSmile();
			break;
		case 'seditsave':
			AdminSmilesEditSave();
			break;
		case 'changestatus':
			AdminBlocksChangeStatus();
			break;
		case 'auto':
			AdminSmiliesAutoAdd();
			break;
		case 'autosave':
			AdminSmiliesAutoSave();
			break;
		default:
			AdminSmilesMain();
	}
}

AdminSmiles();

?>