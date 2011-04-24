<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('������������');

if(!$user->CheckAccess2('user', 'user')){
	AddTextBox('������', $config['general']['admin_accd']);
	return;
}

$editing = $user->CheckAccess2('user', 'editing');
$rankedit = $user->CheckAccess2('user', 'ranks');
$galeryedit = $user->CheckAccess2('user', 'avatars_gallery');
$confedit = $user->CheckAccess2('user', 'config');

function AdminUserGetUsers( $where = '`type`=\'2\'' )
{
	global $config, $db;
	return $db->Select('users', $where);
}

function AdminUserQueryStristrFilter( $str, $inez )
{
	global $db;
	if($str == ''){
		return;
	}
	$newResult = array();
	foreach($db->QueryResult as $user){
		if(stristr($user[$inez], $str) !== false){
			$newResult[] = $user;
		}
	}
	$db->QueryResult = $newResult;
}

function AdminUserMain()
{
	global $db, $config, $site, $user, $editing;
	$db->FreeResult();
	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 10, int);
	}else{
		$page = 1;
	}
	if(isset($_GET['show'])){
		$show = $_GET['show'];
	}else{
		$show = '';
	}
	$showd = array();
	$site->DataAdd($showd, 'all', '��� ������������', $show == '');
	$site->DataAdd($showd, 'online', '������������ OnLine', $show == 'online');

	//������������ online
	$sonline = false;
	$onlwhere = '';
	$where = '`type`=\'2\'';
	if(isset($_GET['show'])){
		if($_GET['show'] == 'online'){
			$donline = $user->Online();
			$donline = $donline['members'];
			$onlwhere = '';
			foreach($donline as $memb){
				$onlwhere .= "or `id`='".SafeDB($memb['u_id'], 11, int)."'";
			}
			$onlwhere = substr($onlwhere, 3);
			$sonline = true;
			if(count($donline) > 0){
				$where = '`type`=\'2\' and ('.$onlwhere.')';
			}else{
				$where = '`type`=\'2\' and `id`=\'-1\'';
			}
			AdminUserGetUsers($where);
		}
	}

	//�����
	$searchm = false;
	$criterion = '';
	$sstr = '';
	if(!$sonline && isset($_GET['criterion']) && isset($_GET['stext'])){
		$searchm = true;
		$criterion = $_GET['criterion'];
		$sstr = $_GET['stext'];
		//���������� where
		switch($criterion){
			case 'nikname':
				$sstr = SafeEnv($sstr, 50, str);
				AdminUserGetUsers();
				AdminUserQueryStristrFilter($sstr, 3);
				break;
			case 'email':
				$sstr = SafeEnv($sstr, 50, str);
				AdminUserGetUsers();
				AdminUserQueryStristrFilter($sstr, 6);
				break;
			case 'rname':
				$sstr = SafeEnv($sstr, 250, str);
				AdminUserGetUsers();
				AdminUserQueryStristrFilter($sstr, 4);
				break;
			case 'age':
				$sstr = SafeEnv($sstr, 1, int);
				AdminUserGetUsers('`type`=\'2\' and `age`=\''.$sstr.'\'');
				break;
			case 'city':
				$sstr = SafeEnv($sstr, 100, str);
				AdminUserGetUsers();
				AdminUserQueryStristrFilter($sstr, 8);
				break;
			case 'site':
				$sstr = SafeEnv($sstr, 250, str);
				AdminUserGetUsers();
				AdminUserQueryStristrFilter($sstr, 10);
				break;
			case 'icq':
				$sstr = SafeEnv($sstr, 15, str);
				AdminUserGetUsers();
				AdminUserQueryStristrFilter($sstr, 9);
				break;
			case 'gmt':
				$sstr = SafeEnv($sstr, 3, str);
				AdminUserGetUsers('`type`=\'2\' and `timezone`=\''.$sstr.'\'');
				break;
			case 'active':
				$sstr = SafeEnv($sstr, 1, int);
				AdminUserGetUsers('`type`=\'2\' and `active`=\''.$sstr.'\'');
				break;
			case 'points':
				$sstr = SafeEnv($sstr, 11, int);
				AdminUserGetUsers("`type`='2' and (`points`='$sstr' or `points`>'$sstr')");
				break;
			case 'ip':
				$sstr = SafeEnv($sstr, 15, str);
				AdminUserGetUsers();
				AdminUserQueryStristrFilter($sstr, 9);
				break;
		}
	}
	$sstr = strval($sstr);
	$searchd = array();
	$site->DataAdd($searchd, 'nikname', '���', $criterion == 'nikname');
	$site->DataAdd($searchd, 'email', 'E-mail', $criterion == 'email');
	$site->DataAdd($searchd, 'rname', '��������� ���', $criterion == 'rname');
	$site->DataAdd($searchd, 'age', '�������', $criterion == 'age');
	$site->DataAdd($searchd, 'city', '�����', $criterion == 'city');
	$site->DataAdd($searchd, 'site', '����', $criterion == 'site');
	$site->DataAdd($searchd, 'icq', 'ICQ', $criterion == 'icq');
	$site->DataAdd($searchd, 'gmt', '������� ����', $criterion == 'gmt');
	$site->DataAdd($searchd, 'active', '�������', $criterion == 'active');
	$site->DataAdd($searchd, 'points', '������� �����', $criterion == 'points');
	$site->DataAdd($searchd, 'ip', 'IP', $criterion == 'ip');
	if(!$sonline && !$searchm){
		AdminUserGetUsers();
	}
	TAddSubTitle('�������');
	AddCenterBox('������������������ ������������');
	if($searchm){
		$c = '�������: '.$db->NumRows();
	}else{
		$c = '������������������ �������������: '.$db->NumRows();
	}
	$serchtool = '<style>.ustd td{ border: none; padding: 0; }</style>';
	$serchtool .= '<table cellspacing="0" cellpadding="0" border="0" class="cfgtable"><tr><td>'."\n";
	$serchtool .= '<form method="get">'.$site->Hidden('exe', 'user').'<table cellspacing="0" cellpadding="0" border="0" width="100%" class="ustd"><tr><td>'.$c.'</td><td>��������: '.$site->Select('show', $showd).'</td><td>'.$site->Submit('�������� ������').'</td></tr></table></form>'."\n";
	$serchtool .= '</td></tr><tr><td>'."\n";
	$serchtool .= '<form method="get">'.$site->Hidden('exe', 'user').'<table cellspacing="0" cellpadding="0" border="0" width="100%" class="ustd"><tr><td>�����: </td><td>'.$site->Select('criterion', $searchd).$site->Edit('stext', $sstr).'</td><td>'.$site->Submit('�����').'</td></tr></table></form>'."\n";
	$serchtool .= '</td></tr></table>'."\n";
	AddText($serchtool);
	SortArray($db->QueryResult, 'regdate', true); // ��������� �� ���� �����������
	if(count($db->QueryResult) > $config['user']['users_on_page']){
		$navigator = new Navigation($page);
		$navigator->GenNavigationMenu($db->QueryResult, $config['user']['users_on_page'], $config['admin_file'].'?exe=user'.($searchm ? '&criterion='.$criterion.'&stext='.$sstr : ''));
		AddNavigation();
		$nav = true;
	}else{
		$nav = false;
		AddText('<br />');
	}
	$text = '';
	$text .= '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>���</th><th>E-mail</th><th>���� ����c������</th><th>����. ���������</th><th>���������</th><th>�������</th><th>���������</th><th>IP</th><th>�������</th></tr>';
	while($row = $db->FetchRow()){
		$uid = SafeDB($row['id'], 11, int);
		if($row['active'] == '1'){
			$active = '��';
		}elseif($row['active'] == '0' && $row['activate'] == ''){
			$active = '���';
		}elseif($row['active'] == '0' && $row['activate'] != ''){
			$active = '���������';
		}
		$funcs = '';
		if($editing){
			$funcs .= SpeedButton('�������������', $config['admin_file'].'?exe=user&a=edituser&id='.$uid, 'images/admin/edit.png');
		}
		$funcs .= SpeedButton('�������', $config['admin_file'].'?exe=user&a=deluser&id='.$uid, 'images/admin/delete.png');

		$text .= '
		<tr>
		<td>'.($editing ? '<a href="'.$config['admin_file'].'?exe=user&a=edituser&id='.$uid.'">' : '').'<b>'.SafeDB($row['name'], 50, str).'</b>'.($editing ? '</a>' : '').'</td>
		<td>'.PrintEmail($row['email'], $row['name']).'</td>
		<td>'.TimeRender($row['regdate']).'</td>
		<td>'.TimeRender($row['lastvisit']).'</td>
		<td>'.SafeDB($row['visits'], 11, int).'</td>
		<td>'.SafeDB($row['points'], 11, int).'</td>
		<td>'.$active.'</td>
		<td>'.SafeDB($row['lastip'], 20, str).'</td>
		<td>'.$funcs.'</td>
		</tr>';
	}
	$text .= '</table>';
	AddText($text);
	if($nav){
		AddNavigation();
	}
}

function AdminUserDelUser()
{
	global $config, $db, $site;
	if(isset($_GET['ok']) && $_GET['ok'] == '1'){
		$id = SafeEnv($_GET['id'], 11, int);
		if(!isset($_POST['del_comments'])){
			$db->Select('users', "`id`='$id'");
			$guser = $db->FetchRow();
			UpdateUserComments($id, '0', SafeEnv($guser['name'], 50, str), SafeEnv($guser['email'], 50, str), SafeEnv($guser['hideemail'], 1, bool), SafeEnv($guser['url'], 250, str));
		}else{
			DeleteAllUserComments($id);
		}
		$db->Delete('users', "`id`='$id'");

		// ������� ��� �������������
		$cache = LmFileCache::Instance();
		$cache->Delete(system_cache, 'users');

		GO($config['admin_file'].'?exe=user');
	}else{
		$r = $db->Select('users', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
		$text = '<form action="'.$config['admin_file'].'?exe=user&a=deluser&id='.SafeEnv($_GET['id'], 11, int).'&ok=1" method="post">
			<br />�� ������������� ������ ������� ������������ "'.$r[0]['name'].'"?<br />'
			.$site->Check('del_comments', '1', false, 'id="del_comments"').'<label for="del_comments">������� ��� ����������� ����� ������������.</label><br /><br />'
			.$site->Button('������', 'onclick="history.go(-1)"').'&nbsp;'.$site->Submit('�������').'</form><br />';
		AddTextBox("��������������", $text);
	}
}

function AdminUserRanks()
{
	global $config, $db, $site, $rankedit;
	TAddSubTitle('����� �������������');

	$users = $db->Select('users', "`type`='2'");
	foreach($users as $u){
		$r = GetUserRank($u['points'], $u['type'], $u['access']);
		if(!isset($rcounts[$r[2]])){
			$rcounts[$r[2]] = 0;
		}
		$rcounts[$r[2]]++;
	}

	$ranks = $db->Select('userranks', '');
	SortArray($ranks, 'min');
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>����</th><th>���. �������</th><th>����������</th><th>�����������</th><th>�������</th></tr>';
	foreach($ranks as $rank){
		if(file_exists($config['general']['ranks_dir'].$rank['image']) && is_file($config['general']['ranks_dir'].$rank['image'])){
			$image = '<img src="'.RealPath2(SafeDB($config['general']['ranks_dir'].$rank['image'], 255, str)).'" border="0" />';
		}else{
			$image = '';
		}

		$funcs = '';
		if($rankedit){
			$funcs .= SpeedButton('�������������', $config['admin_file'].'?exe=user&a=editrank&id='.SafeDB($rank['id'], 11, int), 'images/admin/edit.png');
			$funcs .= SpeedButton('�������', $config['admin_file'].'?exe=user&a=delrank&id='.SafeDB($rank['id'], 11, int), 'images/admin/delete.png');
		}else{
			$funcs .= '&nbsp;';
		}

		$text .= '<tr>
			<td>'.SafeDB($rank['title'], 250, str).'</td>
			<td>'.SafeDB($rank['min'], 11, int).'</td>
			<td>'.(isset($rcounts[$rank['id']]) ? $rcounts[$rank['id']] : '0').'</td>
			<td>'.$image.'</td>
			<td>'.$funcs.'</td>
			</tr>';
	}
	$text .= '</table>';
	AddCenterBox('����� �������������');
	AddText($text);
	if($rankedit){
		FormRow('�������� �����', $site->Edit('rankname', '', false, 'style="width:140px;"'));
		FormRow('�����������', $site->Edit('rankimage', '', false, 'style="width:180px;"'));
		FormRow('����������� ���������� �������<br />��� ����������', $site->Edit('minpoints', '0', false, 'style="width:60px;"'));
		AddText('<br /><center>.: �������� ���� :.</center>');
		AddForm('<form name="addrang" method="post" action="'.$config['admin_file'].'?exe=user&a=addrank">', $site->Submit('��������')).'<br />';
	}
}

function AdminUserEditRank()
{
	global $db, $config, $site;
	$id = SafeEnv($_GET['id'], 11, int);
	$db->Select('userranks', "`id`='$id'");
	$thrank = $db->FetchRow();
	FormRow('�������� �����', $site->Edit('rankname', SafeDB($thrank['title'], 250, str), false, 'style="width:140px;"'));
	FormRow('�����������', $site->Edit('rankimage', SafeDB($thrank['image'], 250, str), false, 'style="width:180px;"'));
	FormRow('����������� ���������� �������<br />��� ����������', $site->Edit('minpoints', SafeDB($thrank['min'], 11, int), false, 'style="width:60px;"'));
	AddCenterBox('�������������� �����');
	AddForm('<form name="addrang" method="post" action="'.$config['admin_file'].'?exe=user&a=saverank&id='.$id.'">', $site->Button('������', 'onclick="history.go(-1)"').$site->Submit('��������� ���������'));
}

function AdminUserRankSave( $action )
{
	global $config, $db;
	$rankname = SafeEnv($_POST['rankname'], 250, str);
	$rankimage = SafeEnv($_POST['rankimage'], 250, str);
	$minpoints = SafeEnv($_POST['minpoints'], 11, int);
	if($action == 'addrank'){
		$db->Insert('userranks', Values('', $rankname, $minpoints, $rankimage));
	}elseif($action == 'saverank'){
		$db->Update('userranks', "title='$rankname',min='$minpoints',image='$rankimage'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	}

	// ������� ���
	$cache = LmFileCache::Instance();
	$cache->Delete(system_cache, 'userranks');

	GO($config['admin_file'].'?exe=user&a=ranks');
}

function AdminUserDeleteRank()
{
	global $config, $db;
	if(isset($_GET['ok']) && $_GET['ok'] == '1'){
		$db->Delete('userranks', "`id`='".SafeEnv($_GET['id'], 11, int)."'");

		// ������� ���
		$cache = LmFileCache::Instance();
		$cache->Delete(system_cache, 'userranks');

		GO($config['admin_file'].'?exe=user&a=ranks');
	}else{
		TAddSubTitle('�������� �����');
		$r = $db->Select('userranks', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
		$text = '�� ������������� ������ ������� ���� "'.SafeDB($r[0]['title'], 250, str).'"<br />'
			.'<a href="'.$config['admin_file'].'?exe=user&a=delrank&id='.SafeEnv($_GET['id'], 11, int).'&ok=1">��</a>'
			.' &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">���</a>';
		AddTextBox("��������!", $text);
	}
}

function AdminUserAvatarsGallery()
{
	global $config, $site, $galeryedit, $db;
	TAddSubTitle('������� ������');
	if(isset($_GET['user']) && $_GET['user'] == '1'){
		$personal = true;
		$dir = $config['general']['personal_avatars_dir'];
		$dirlink = '<a href="'.$config['admin_file'].'?exe=user&a=avatars">�������� ������� �� �������</a>';
		$users = $db->Select('users', "`type`='2'");
		$c = sizeof($users);
		for($i = 0; $i < $c; $i++){
			$users[$users[$i]['avatar']] = $i;
		}
	}else{
		$personal = false;
		$dir = $config['general']['avatars_dir'];
		$dirlink = '<a href="'.$config['admin_file'].'?exe=user&a=avatars&user=1">�������� ������� �������������</a>';
	}
	$avatars2 = GetFiles($dir, false, true, '.gif.jpg.jpeg.png');
	$avatars = array();
	foreach($avatars2 as $av){
		$name = GetFileName($av);
		$sub = substr($name, -3);
		if($sub != 'x24' && $sub != 'x64'){
			$avatars[] = $av;
		}
	}
	$c = count($avatars);
	$allsize = 0;
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	if($c > 0){
		$col = 0;
		for($i = 0; $i < $c; $i++){
			if($col == 0){
				$text .= '<tr>';
			}
			$col++;
			$imagfn = $dir.$avatars[$i];
			$size = getimagesize($imagfn);
			$fsize = filesize($imagfn);
			$allsize = $allsize + $fsize;
			if($galeryedit){
				$funcs = SpeedButton('�������', $config['admin_file'].'?exe=user&a=delavatar&filename='.$avatars[$i].($personal ? '&personal' : ''), 'images/admin/delete.png');
			}else{
				$funcs = '&nbsp;';
			}
			
			$text .= '
			<td align="center">
				<table cellspacing="0" cellpadding="0" align="center">
				<tr>
				<td style="border:none"><a href="'.$imagfn.'" target="_blank"><img src="'.$imagfn.'" border="0" width="64" title="('.$size[0].' x '.$size[1].', '.FormatFileSize($fsize).') '.$avatars[$i].'" /></a></td>
				<td valign="top" style="border:none">'.$funcs.'</td>
				</tr>
				<tr>
				<td colspan="2" align="left" style="border:none">'.(($personal && isset($users[$avatars[$i]])) ? '<a href="'.$config['admin_file'].'?exe=user&a=edituser&id='.SafeDB($users[$users[$avatars[$i]]]['id'], 11, int).'">'.SafeDB($users[$users[$avatars[$i]]]['name'], 255, str).'</a>' : '').'</td>
				</tr>
				</table>
			</td>';
			if($col == 5){
				$text .= '</tr>';
				$col = 0;
			}
		}
		if($col < 5){
			$text .= '</tr>';
		}
	}else{
		$text .= '<tr><td>� ������� ��� �� ������ �������.</td></tr>';
	}
	$text .= '</table>';
	$info = '<table cellspacing="0" cellpadding="0" border="0" class="cfgtable">
		<tr>
		<td width="34%">������ � �������: '.$c.'</td>
		<td width="33%">����� ������: '.FormatFileSize($allsize).'</td>
		<td>'.$dirlink.'</td>
		</tr>
	</table>';
	$text = $info.$text;
	AddCenterBox('������� ������', $text);
	AddText($text);
	if(!$personal && $galeryedit){
		$text .= '<br />.: ��������� ������ :.';
		FormRow('��� �����', $site->FFile('avatar'));
		AddForm($site->FormOpen($config['admin_file'].'?exe=user&a=saveavatar', 'post', 'multipart/form-data'), $site->Submit('���������'));
	}
	AddText('<br />');
}

function AdminUserSaveAvatar()
{
	global $config;
	$alloy_mime = array('image/gif'=>'.gif', 'image/jpeg'=>'.jpg', 'image/pjpeg'=>'.jpg', 'image/png'=>'.png', 'image/x-png'=>'.png');
	if(isset($_FILES['avatar'])){
		if(isset($alloy_mime[$_FILES['avatar']['type']]) && $alloy_mime[$_FILES['avatar']['type']] == strtolower(GetFileExt($_FILES['avatar']['name']))){
			copy($_FILES['avatar']['tmp_name'], $config['general']['avatars_dir'].$_FILES['avatar']['name']);
		}elseif(!file_exists($_FILES['avatar']['tmp_name'])){
			AddTextBox('������', '<center>�� �� ������� ���� ��� ��������.<br /><a href="javascript:history.go(-1)">����� � �������</a></center>');
			return;
		}else{
			AddTextBox('������', '<center>������������ ������ �����. ����� ��������� ������ ����������� ������� GIF, JPEG ��� PNG.<br /><a href="javascript:history.go(-1)">����� � �������</a></center>');
			return;
		}
	}
	GO($config['admin_file'].'?exe=user&a=avatars');
}

function AdminUserDeleteAvatar()
{
	global $config, $db;
	if(isset($_GET['personal'])){
		$dir = $config['general']['personal_avatars_dir'];
		$personal = true;
	}else{
		$dir = $config['general']['avatars_dir'];
		$personal = false;
	}
	$avatar = SafeEnv($_GET['filename'], 250, str);
	$filename = RealPath2($dir.$avatar);
	if(isset($_GET['ok']) && $_GET['ok'] == '1'){
		if(file_exists($filename) && is_file($filename)){
			unlink($filename);
		}
		if($personal){
			$db->Update('users', "a_personal='0',avatar=''", "`a_personal`='1' and `avatar`='$avatar'");
		}
		GO($config['admin_file'].'?exe=user&a=avatars');
		exit();
	}else{
		TAddSubTitle('�������� �������');
		if(file_exists($filename) && is_file($filename)){
			$text = '<table cellspacing="0" cellpadding="5" border="0" align="center"><tr><td align="center">'.'<img src="'.$filename.'" border="0" /></tr></td><tr><td align="center">'.'������ ����� ������ ��������� � �������� �����. ����������?<br />'.'<a href="'.$config['admin_file'].'?exe=user&a=delavatar&filename='.SafeEnv($_GET['filename'], 250, str).'&ok=1'.($personal ? '&personal' : '').'">��</a> &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">���</a><br /><br />'.'</td></tr></table>';
		}else{
			$text = '<center>������, ������� �� ��������� �������, �� ������ � ����� � ���������.<br /><a href="javascript:history.go(-1)">����� � �������</a></center>';
		}
		AddTextBox("��������!", $text);
	}
}
include_once ($config['apanel_dir'].'configuration/functions.php');

function AdminUser( $action )
{
	global $config, $editing, $rankedit, $galeryedit, $confedit;
	TAddToolLink('�������', 'main', 'user');
	if($editing){
		TAddToolLink('�������� ������������', 'add', 'user&a=add');
	}
	if($confedit){
		TAddToolLink('������������ ������', 'config', 'user&a=config');
	}
	TAddToolBox($action);
	TAddToolLink('����� �������������', 'ranks', 'user&a=ranks');
	if($rankedit){
		TAddToolLink('������� �������', 'points', 'user&a=points');
	}
	TAddToolLink('������� ������', 'avatars', 'user&a=avatars');
	TAddToolBox($action);
	switch($action){
		case 'main':
			AdminUserMain();
			return true;
			break;
		case 'add':
			if($editing){
				include_once ($config['apanel_dir'].'members.php');
				AdminUserEditor('user&a=addsave', 'add', 0, false);
				return true;
			}
			break;
		case 'addsave':
			if($editing){
				include_once ($config['apanel_dir'].'members.php');
				AdminUserEditSave('user', 'addsave', 0, false);
				return true;
			}
			break;
		case 'edituser':
			if($editing){
				include_once ($config['apanel_dir'].'members.php');
				AdminUserEditor('user&a=editsave', 'edit', SafeEnv($_GET['id'], 11, int), false);
				return true;
			}
			break;
		case 'editsave':
			if($editing){
				include_once ($config['apanel_dir'].'members.php');
				AdminUserEditSave('user', 'update', SafeEnv($_GET['id'], 11, int), false);
				return true;
			}
			break;
		case 'deluser':
			if($editing){
				AdminUserDelUser();
				return true;
			}
			break;
		case 'ranks':
			AdminUserRanks();
			return true;
			break;
		case 'editrank':
			if($rankedit){
				AdminUserEditRank();
				return true;
			}
			break;
		case 'saverank':
		case 'addrank':
			if($rankedit){
				AdminUserRankSave($action);
				return true;
			}
			break;
		case 'delrank':
			if($rankedit){
				AdminUserDeleteRank();
				return true;
			}
			break;
		case 'avatars':
			AdminUserAvatarsGallery();
			return true;
			break;
		case 'delavatar':
			if($galeryedit){
				AdminUserDeleteAvatar();
				return true;
			}
			break;
		case 'saveavatar':
			if($galeryedit){
				AdminUserSaveAvatar();
				return true;
			}
			break;
		case 'config':
			if($confedit){
				global $config, $site;
				include_once ($config['apanel_dir'].'configuration/functions.php');
				AdminConfigurationEdit('user', 'user', true, false, '������������ ������ "������������"');
				return true;
			}
			break;
		case 'configsave':
			if($confedit){
				global $config;
				include_once ($config['apanel_dir'].'configuration/functions.php');
				AdminConfigurationSave('user&a=config', 'user', true);
				return true;
			}
			break;
		case 'points':
			if($rankedit){
				AdminConfigurationEdit('user', 'points', true, false, '������� �������', 'a=pointsave');
				return true;
			}
			break;
		case 'pointsave':
			if($rankedit){
				AdminConfigurationSave('user&a=points', 'points', true);
				return true;
			}
			break;
		default:
			return false;
	}
	return false;
}

if(isset($_GET['a'])){
	$a = $_GET['a'];
}else{
	$a = 'main';
}

if(!AdminUser($a)){
	AddTextBox('������', $config['general']['admin_accd']);
	return;
}

?>