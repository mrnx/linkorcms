<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('������');

if(!$user->CheckAccess2('modules', 'modules')){
	AddTextBox('������', $config['general']['admin_accd']);
	return;
}

function AdminModulesList( $system )
{
	global $db, $config, $site;
	if($system){
		$title = '��������� ������';
	}else{
		$title = '������������� ������';
	}
	TAddSubTitle($title);
	$db->Select('modules', ($system ? '`system`=\'1\'' : '`system`=\'0\''));
	$text = '';
	$text .= $site->FormOpen($config['admin_file'].'?exe=modules&a=setorder'.($system ? '&system=1' : ''));
	$text .= '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>��������</th><th>�����</th><th>��������� � ����</th>'.($system ? '' : '<th>��� �����</th><th>������</th><th>�������</th>').'</tr>';
	$i = 0;
	SortArray($db->QueryResult, 'order');
	while($row = $db->FetchRow()){
		$mid = SafeDB($row['id'], 11, int);
		$vi = ViewLevelToStr(SafeDB($row['view'], 1, int));
		switch($row['enabled']){
			case "1":
				$st = '<a href="'.$config['admin_file'].'?exe=modules&a=changestatus&id='.$mid.'" title="�������� ������"><font color="#008000">���.</font></a>';
				break;
			case "0":
				$st = '<a href="'.$config['admin_file'].'?exe=modules&a=changestatus&id='.$mid.'" title="�������� ������"><font color="#FF0000">����.</font></a>';
				break;
		}
		if(!$system){
			$funcs = '';
			$funcs .= SpeedButton('������������', $config['admin_file'].'?exe=modules&a=config&name='.SafeDB($row['folder'], 255, str), 'images/admin/config.png');
			if(is_file($config['mod_dir'].SafeDB($row['folder'], 255, str).'/uninstall.php')){
				$funcs .= SpeedButton('�������', $config['admin_file'].'?exe=modules&a=uninstall&name='.SafeDB($row['folder'], 255, str), 'images/admin/delete.png');
			}
		}
		$text .= '
		<tr>
		<td>'.(!$system ? '<a href="'.$config['admin_file'].'?exe=modules&a=config&name='.SafeDB($row['folder'], 255, str).'">' : '').'<b>'.SafeDB($row['name'], 255, str).'</b>'.(!$system ? '</a>' : '').'</td>
		<td>'.SafeDB($row['folder'], 255, str).'</td>
		<td>'.$site->Edit(SafeDB($row['folder'], 255, str), SafeDB($row['order'], 11, int), false, 'style="width:32px;" maxlength="11"').'</td>'.($system ? '' : '<td>'.$vi.'</td>
		<td>'.$st.'</td>
		<td>'.$funcs.'</td>').'
		</tr>';
		$i++;
	}
	$text .= '</table><br />';
	$text .= $site->Submit('������������� ���������').'<br /><br />';
	$text .= $site->FormClose();
	AddTextBox($title, $text);
}

function AdminModulesOrderSave()
{
	global $db, $config;
	$mods = $db->Select('modules');
	$count = count($mods);
	for($i = 1; $i <= $count; $i++){
		if(isset($_POST[$mods[$i]['folder']]) && $_POST[$mods[$i]['folder']] != $mods[$i]['order']){
			$db->Update('modules', "`order`='".SafeEnv($_POST[$mods[$i]['folder']], 11, str)."'", "`folder`='".SafeEnv($mods[$i]['folder'], 255, str)."'");
		}
	}
	if(isset($_GET['system'])){
		GO($config['admin_file'].'?exe=modules&a=system');
	}else{
		GO($config['admin_file'].'?exe=modules');
	}
}

function AdminModulesInstallList()
{
	global $db, $config, $site;
	TAddSubTitle('���������');
	$db->Select('modules', '');
	while($mod = $db->FetchRow()){
		$imod[] = SafeDB($mod['folder'], 255, str);// ��������� �� ������
	}
	$list = array(); //������ ��������������� �������
	$dir = opendir($config['mod_dir']);
	while($file = @readdir($dir)){
		$fn = $config['mod_dir'].$file;
		if(is_dir($fn) && ($file != ".") && ($file != "..")){
			if(array_search($file, $imod) === FALSE){ // ���� �� ����������
				if(is_file($fn.'/info.php')){
					$list[] = $file;
				}
			}
		}
	}
	@closedir($dir);
	$cnt = count($list);
	if($cnt > 0){
		$text = '<form action="'.$config['admin_file'].'?exe=modules&a=install" method="post"><table cellspacing="0" cellpadding="0" class="cfgtable">';
		$text .= '<tr><th>�</th><th>���</th><th>�����������</th><th>�����</th><th>���������</th><th>�����</th></tr>';
		for($i = 0; $i < $cnt; $i++){
			include ($config['mod_dir'].$list[$i].'/info.php');
			switch($module[$list[$i]]['system']){
				case '1':
					$sys = "��";
				case '0':
					$sys = "���";
			}
			$text .= '<tr><td>'.$site->Check('no'.$i, $list[$i]).'</td><td>'.SafeDB($module[$list[$i]]['name'], 255, str).'</td><td>'.SafeDB($module[$list[$i]]['comment'], 255, str).'</td><td>'.$list[$i].'</td><td>'.$sys.'</td><td>'.SafeDB($module[$list[$i]]['copyright'], 255, str).'</td></tr>';
		}
		$text .= '</table>'.$site->Hidden('count', $cnt).$site->Submit('���������� ����������');
	}else{
		$text = '<br />����� ������� �� �������!<br /><br />';
	}
	AddTextBox('��������� �������', $text);
}

function AdminModulesInstall()
{
	global $config, $db;
	$cnt = SafeEnv($_POST['count'], 11, int);
	for($i = 0; $i < $cnt; $i++){
		if(isset($_POST['no'.$i])){
			$n = $config['mod_dir'].$_POST['no'.$i].'/install.php';
			if(file_exists($n)){
				include_once ($config['mod_dir'].SafeEnv($_POST['no'.$i], 255, str).'/install.php');
				$cache = LmFileCache::Instance();
				$cache->Clear('config');
			}
		}
	}
	GO($config['admin_file'].'?exe=modules');
}

// �������� ������
function AdminModulesUninstall()
{
	global $db, $config;
	$name = SafeEnv($_GET['name'], 255, str);
	if(isset($_GET['ok']) && $_GET['ok'] == '1'){
		$u_name = RealPath2($config['mod_dir'].$name.'/uninstall.php');
		if(file_exists($u_name)){
			include_once($u_name);
			$cache = LmFileCache::Instance();
			$cache->Clear('config');
		}
		GO($config['admin_file'].'?exe=modules');
	}else{
		$db->Select('modules', "`folder`='$name'");
		$mod = $db->FetchRow();
		$text = '�� ������������� ������ ������� ������ "'.SafeDB($mod['name'], 255, str).'"?<br />'
		.'<a href="'.$config['admin_file'].'?exe=modules&a=uninstall&name='.$name.'&ok=1">��</a>'
		.' &nbsp;&nbsp;&nbsp; '
		.'<a href="javascript:history.go(-1)">���</a>';
		AddTextBox("��������", $text);
	}
}

function AdminModulesChangeStatus()
{
	global $config, $db;
	$db->Select('modules', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	$r = $db->FetchRow();
	if($r['enabled'] == 1){
		$en = '0';
	}else{
		$en = '1';
	}
	$db->Update('modules', "enabled='$en'", "`id`='".SafeEnv($_GET['id'], 11, int, false, false)."'");
	GO($config['admin_file'].'?exe=modules');
}

function AdminModulesConfig()
{
	global $config, $db, $site;
	TAddSubTitle('������������ ������');
	$db->Select('modules', "`folder`='".SafeEnv($_GET['name'], 255, str)."'");
	$r = $db->FetchRow();

	FormRow('���', $site->Edit('name', $r['name'], false, 'style="width:200px;"'));

	$dir = $config['tpl_dir'].$config['general']['site_template'].'/themes/';
	if(is_dir($dir)){
		$templates = GetFiles($dir, false, true, ".html");
	}else{
		$templates = array();
	}
	$site->DataAdd($templates_data, '', '����������� "theme.html"', $r['theme'] == '');
	foreach($templates as $template){
		$site->DataAdd($templates_data, $template, $template, $r['theme'] == $template);
	}
	FormRow('������ ��������', $site->Select('theme', $templates_data));

	$m_vi = array(false, false, false, false, false);
	$m_vi[$r['view']] = true;
	$site->DataAdd($visdata, '1', '������ ��������������', $m_vi[1]);
	$site->DataAdd($visdata, '2', '������ ������������', $m_vi[2]);
	$site->DataAdd($visdata, '3', '������ �����', $m_vi[3]);
	$site->DataAdd($visdata, '4', '���', $m_vi[4]);
	FormRow('��� �����', $site->Select('view', $visdata));

	$m_en = array(false, false);
	$m_en[$r['enabled']] = true;
	$site->DataAdd($endata, '1', '��', $m_en[1]);
	$site->DataAdd($endata, '0', '���', $m_en[0]);
	FormRow('��������', $site->Select('enabled', $endata));

	AddCenterBox('��������� ������ "'.SafeDB($r['name'], 255, str).'"');
	AddForm($site->FormOpen($config['admin_file'].'?exe=modules&a=configsave&name='.SafeDB($r['name'], 255, str)), $site->Submit('���������'));
}

function AdminModulesConfigSave()
{
	global $config, $db;
	$set = "name='".SafeEnv($_POST['name'], 255, str)."',"
	."view='".SafeEnv($_POST['view'], 1, int)."',"
	."enabled='".SafeEnv($_POST['enabled'], 1, int)."',"
	."theme='".RealPath2(SafeEnv($_POST['theme'], 255, str))."'";
	$db->Update('modules', $set, "`name`='".SafeEnv($_GET['name'], 255, str)."'");
	GO($config['admin_file'].'?exe=modules');
}

function AdminModules( $action )
{
	TAddToolLink('������������� ������', 'main', 'modules');
	TAddToolLink('��������� ������', 'system', 'modules&a=system');
	TAddToolLink('��������� �������', 'installlist', 'modules&a=installlist');
	TAddToolBox($action);
	switch($action){
		case 'main':
			AdminModulesList(false);
			break;
		case 'system':
			AdminModulesList(true);
			break;
		case 'installlist':
			AdminModulesInstallList();
			break;
		case 'install':
			AdminModulesInstall();
			break;
		case 'uninstall':
			AdminModulesUninstall();
			break;
		case 'changestatus':
			AdminModulesChangeStatus();
			break;
		case 'config':
			AdminModulesConfig();
			break;
		case 'configsave':
			AdminModulesConfigSave();
			break;
		case 'setorder':
			AdminModulesOrderSave();
			break;
		default:
			AdminModulesList(false);
	}
}

if(isset($_GET['a'])){
	AdminModules($_GET['a']);
}else{
	AdminModules('main');
}

?>