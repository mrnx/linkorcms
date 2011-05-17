<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('��������� �����');

if(!$user->CheckAccess2('config', 'config')){
	AddTextBox('������', $config['general']['admin_accd']);
	return;
}

include_once ($config['inc_dir'].'configuration/functions.php');

// ��������� �������� �� �� � ����������� ��������
function AdminConfigPlugins()
{
	return (isset($_GET['plugins']) && $_GET['plugins'] == '1');
}

// ���������� ������� ��� �����
function AdminConfigGroupTable()
{
	if(AdminConfigPlugins()){
		return 'plugins_config_groups';
	}else{
		return 'config_groups';
	}
}

// ���������� ������� ��� ��������
function AdminConfigConfigTable()
{
	if(AdminConfigPlugins()){
		return 'plugins_config';
	}else{
		return 'config';
	}
}

// ���������� ������ ����� ��� �����
function AdminConfigGetGroupsFormData( $group = 0 )
{
	global $config, $db, $site;
	$db->Select(AdminConfigGroupTable(), '');
	$result = array();
	while($g = $db->FetchRow()){
		$site->DataAdd($result, SafeDB($g['id'], 11, int), SafeDB($g['hname'], 255, str).' ('.SafeDB($g['name'], 255, str).')', $group == $g['id']);
	}
	return $result;
}

// �������� ��������
function AdminConfigAdd()
{
	global $site, $config, $cl_plugins, $cs_plugins, $db;

	if(isset($_GET['id'])){ // ��������������
		$id = SafeEnv($_GET['id'], 11, int);
		$db->Select(AdminConfigConfigTable(), "`id`='$id'");
		$ret = $db->FetchRow();

		$group = SafeDB($ret['group_id'], 11, int);
		$name = SafeDB($ret['name'], 255, str);
		$hname = SafeDB($ret['hname'], 255, str);
		$description = SafeDB($ret['description'], 255, str);
		$value = SafeDB($ret['value'], 0, str, false);

		$control = explode(':', $ret['kind']);
		$control[0] = trim(strtolower($control[0]));
		$control = FormsParseParams($control);

		$values = SafeDB($ret['values'], 0, str);
		$vals = explode(':', $values);
		if(count($vals) == 2 && FormsConfigCheck2Func($vals[0], $vals[1])){
			$valuesfunc = trim($vals[1]);
			$values = '';
		}else{
			$valuesfunc = '';
		}
		$savefunc = SafeDB($ret['savefunc'], 250, str);

		$type = SafeDB($ret['type'], 60, str);
		if($type != ''){
			$type = explode(',', $type);
			settype($type[0], int); //maxlength
			settype($type[1], str); //type
			if($type[2] == 'false'){
				$type[2] = false;
			}else{
				$type[2] = true;
			}
		}else{
			$type = array(255, str, false);
		}

		$visible = SafeDB($ret['visible'], 1, int);
		$autoload = SafeDB($ret['autoload'], 1, int);

	}else{ // ����������
		$group = 0;
		$name = '';
		$hname = '';
		$description = '';
		$value = '';

		$control = array('cols'=>1, 'style'=>'', 'control'=>'', 'width'=>'', 'height'=>'');

		$values = '';
		$valuesfunc = '';
		$savefunc = '';
		$type = array(255, str, false);
		$visible = 0;
		$autoload = 0;
	}

	// ������� ����������
	$controls_array = array('edit', 'password', 'text', 'combo', 'list', 'check', 'radio');
	$controls_array2 = array('��������� ����', '������', '������� ��������������',
	 '�������������� ������', '������ (���������������)', '������', '�����������');
	$controls = array();
	foreach($controls_array as $c=>$contol_name){
		$site->DataAdd($controls, $contol_name, $controls_array2[$c], $contol_name == $control['control']);
	}

	// ���������� �������
	$collsd = array();
	for($i = 1; $i < 11; $i++){
		$site->DataAdd($collsd, $i, $i, $i==$control['cols']);
	}

	// ������� ���������� ��������
	$getfuncdata = array();
	$site->DataAdd($getfuncdata, '', '');
	foreach($cl_plugins as $pl){
		$site->DataAdd($getfuncdata, $pl[0], $pl[0], $pl[0] == $valuesfunc);
	}

	// ������� ���������
	$savefuncdata = array();
	$site->DataAdd($savefuncdata, '', '');
	foreach($cs_plugins as $pl){
		$site->DataAdd($savefuncdata, $pl[0], $pl[0], $pl[0] == $savefunc);
	}

	// ��� ������
	$types_array = array('int', 'float', 'string', 'bool');
	$types_array2 = array('�������������', '������������', '���������', '����������');
	$datatypes = array();
	foreach($types_array as $c=>$type_name){
		$site->DataAdd($datatypes, $type_name, $types_array2[$c], $type_name == $type[1]);
	}

	AddCenterBox('�������� ���������');
	FormRow('������', $site->Select('group', AdminConfigGetGroupsFormData($group)));
	FormRow('���', $site->Edit('name', $name, false, 'style="width:400px;" maxlength="255"'));
	FormRow('���������', $site->Edit('hname', $hname, false, 'style="width:400px;" maxlength="255"'));
	FormRow('��������', $site->Edit('description', $description, false, 'style="width:400px;" maxlength="255"'));
	FormRow('��������', $site->TextArea('value', $value, 'style="width:400px;height:200px;"'));
	FormRow(
		'������� ����������<br /><small>������� ������� ���������<br />����� ������ � ������</small>',
		$site->Select('control', $controls).'<table cellspacing="3" cellpadding="0" border="0">'
		.'<tr><td style="border:none">������:</td><td style="border:none">'.$site->Edit('cwidth', $control['width'], false, 'style="width:200px;"').'</td></tr>'
		.'<tr><td style="border:none">������:</td><td style="border:none">'.$site->Edit('cheight', $control['height'], false, 'style="width:200px;"').'</td></tr>'
		.'<tr><td style="border:none">�������:</td><td style="border:none">'.$site->Select('ccols', $collsd).'</td></tr>'.'</table>'
	);
	FormRow(
		'��������� ��������<br /><small>��������:<br />name:���, name:���, ...<br />������ ��� ��������� ������.</small>',
		$site->TextArea('values', $values, 'style="width:400px;height:100px;"')
	);
	FormRow('������� ���������� ��������', $site->Select('valuesfunc', $getfuncdata));
	FormRow('������� ����������', $site->Select('savefunc', $savefuncdata));
	FormRow('��� ������', $site->Select('datatype', $datatypes));
	FormRow('����� ����<br /><small>0 - �� ����������</small>', $site->Edit('maxlength', $type[0], false, 'style="width:200px;" maxlength="11"'));
	FormRow('�������� html-���� �<br />�������� �����������<br />html-�������������', $site->Check('striptags', '1', $type[2]));
	FormRow('�������', $site->Check('visible', '1', $visible));
	FormRow('������������', $site->Check('autoload', '1', $autoload));
	AddForm(
		$site->FormOpen(
			$config['admin_file'].'?exe=config&a=save'
			.(AdminConfigPlugins() ? '&plugins=1' : '')
			.(isset($_GET['id']) ? '&id='.$id : '')
		),
		$site->Submit((isset($_GET['id']) ? '���������' : '��������'))
	);
}

// ���������� ���������
function AcAddRetrofitting()
{
	global $db, $config;
	$db->Select(AdminConfigGroupTable(), '');
	$groups = array();
	while($g = $db->FetchRow()){
		$groups[$g['id']] = $g['name'];
	}
	$group = SafeEnv($_POST['group'], 11, int);
	$hname = SafeEnv($_POST['hname'], 255, str, true);
	$name = SafeEnv($_POST['name'], 255, str);
	$value = SafeEnv($_POST['value'], 0, str);
	$description = SafeEnv($_POST['description'], 255, str, true);
	if(isset($_POST['visible'])){
		$visible = '1';
	}else{
		$visible = '0';
	}
	if(isset($_POST['autoload'])){
		$autoload = '1';
	}else{
		$autoload = '0';
	}
	//���������� kind
	$kind = '';
	$values = '';
	$savefunc = '';
	$type = '';
	if($visible == '1'){
		$kind .= SafeEnv($_POST['control'], 25, str);
		$width = SafeEnv($_POST['cwidth'], 14, str);
		$height = SafeEnv($_POST['cheight'], 14, str);
		$cols = SafeEnv($_POST['ccols'], 11, int);
		if($width != ''){
			$kind .= ':w'.$width;
		}
		if($height != ''){
			$kind .= ':h'.$height;
		}
		if($cols > 1){
			$kind .= ':c'.$cols;
		}
		$getfunc = SafeEnv($_POST['valuesfunc'], 255, str);
		if($getfunc == '' || !function_exists(CONF_GET_PREFIX.$getfunc)){
			$values = SafeEnv($_POST['values'], 0, str);
		}else{
			$values = 'function:'.$getfunc;
		}
	}

	if(function_exists(CONF_SAVE_PREFIX.$_POST['savefunc'])){
		$savefunc = SafeEnv($_POST['savefunc'], 255, str);
	}else{
		$savefunc = '';
	}
	$maxlenght = SafeEnv($_POST['maxlength'], 11, int);
	$type = SafeEnv($_POST['datatype'], 255, str);
	if(isset($_POST['striptags'])){
		$striptags = 'true';
	}else{
		$striptags = 'false';
	}
	$type = $maxlenght.','.$type.','.$striptags;


	//���������
	if(!AdminConfigPlugins()){
		$access_config = '$config';
	}else{
		$access_config = '$plug_config';
	}

	$to_db = Values('', $group, $name, $value, $visible, $hname, $description, $kind, $values,
		$savefunc, $type, $autoload);

	if(isset($_GET['id'])){
		$id = SafeEnv($_GET['id'], 11, str);
		$db->Update(AdminConfigConfigTable(), $to_db, "`id`='$id'", true);
		AddTextBox('�������������', '<center>��������� ���������.</center>');
	}else{
		$db->Insert(AdminConfigConfigTable(), $to_db);
		AddTextBox('�������������', '<center>����� ��������� ������� ���������.<br />'
			.'��� ������� � �������� ��������� ����������� ����������:<br />'
			."System::<span style=\"color: #660000;\">".$access_config.'</span>'
			."[<span style=\"color: #008200\">'".$groups[$group]."'</span>]"
			."['<span style=\"color: #008200\">".$name."'</span>]<br />"
			.'<br /></center>'
		);
	}
}

// ������ ��������
function AdminViewRetrofittingList()
{
	global $db, $config;
	if(!AdminConfigPlugins()){
		$access_config = '$config';
	}else{
		$access_config = '$plug_config';
	}
	$groups = array();
	$db->Select(AdminConfigGroupTable());
	while($group = $db->FetchRow()){
		$groups[$group['id']] = $group;
	}
	$db->Select(AdminConfigConfigTable(), '');
	SortArray($db->QueryResult, 'group_id');
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr>
	<th>�</th>
	<th>������</th>
	<th>���������</th>
	<th>PHP ���</th>'
//	.'<th>���������/��������<th>'
	.'<th>�������</th>
	<th>�������</th>'
	.'</tr>';
	$id = 0;
	while($conf = $db->FetchRow()){
		$id++;
		$func = '';
		$func .= SpeedButton('�������������', $config['admin_file'].'?exe=config&a=edit&id='.SafeDB($conf['id'], 11, int)
		.(AdminConfigPlugins() ? '&plugins=1' : ''), 'images/admin/edit.png');
		$func .= SpeedButton('�������', $config['admin_file'].'?exe=config&a=delete&id='.SafeDB($conf['id'], 11, int).'&ok=0'
		.(AdminConfigPlugins() ? '&plugins=1' : ''), 'images/admin/delete.png');

		$access = ''
		."System::<span style=\"color: #660000;\">".$access_config.'</span>'
		."[<span style=\"color: #008200\">'".$groups[$conf['group_id']]['name']."'</span>]"
		."['<span style=\"color: #008200\">".$conf['name']."'</span>]"
		.'';

		$install_vals = Values('', $conf['group_id'], $conf['name'], $conf['value'],
			$conf['visible'], $conf['hname'], $conf['description'], $conf['kind'],
			$conf['values'], $conf['savefunc'], $conf['type'], $conf['autoload']);
		$install = '$db->Insert("'.AdminConfigConfigTable().'","'.$install_vals.'");';

		if($conf['visible'] == '1'){
			$visible = '<font color="#008000">��</font>';
		}else{
			$visible = '<font color="#FF0000">���</font>';
		}

		$text .= '<tr><td>'.$id.'</td>
		<td>'.$groups[$conf['group_id']]['hname'].'</td>
		<td style="text-align:left;padding-left:10px;">'.$conf['hname'].'</td>
		<td style="text-align:left;padding-left:10px;">'.$access.'</td>'
	//	.'<td>'.$install.'</td>'
		.'<td>'.$visible.'</td>
		<td>'.$func.'</td>
		</tr>';
	}
	$text .= '</table>';
	AddTextBox('��� ���������', $text);
}

// �������� ���������
function AdminConfigDeleteRetrofitting()
{
	global $config, $db;
	$back_url = '';

	if(!AdminConfigPlugins()){
		$back_url = $config['admin_file'].'?exe=config&a=view_all';
	}else{
		$back_url = $config['admin_file'].'?exe=config&a=view_all_plugins&plugins=1';
	}

	if(!isset($_GET['id'])){
		GO($back_url);
	}else{
		$id = SafeEnv($_GET['id'], 11, int);
	}

	if(isset($_GET['ok']) && $_GET['ok'] == '1'){
		$db->Delete(AdminConfigConfigTable(), "`id`='$id'");
		GO($back_url);
	}else{
		$r = $db->Select(AdminConfigConfigTable(), "`id`='$id'");
		$text = '�� ������������� ������ ������� ��������� "'.SafeDB($r[0]['hname'], 255, str)
		.'"<br />'
		.'<a href="'.$config['admin_file'].'?exe=config&a=delete&id='.$id.'&ok=1'
		.(AdminConfigPlugins() ? '&plugins=1' : '').'">��</a>'
		.' &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">���</a>';
		AddTextBox("��������!", $text);
	}
}

// ������ �����
function AdminConfigViewGroups()
{
	global $db, $config, $site;

	$db->Select(AdminConfigGroupTable());

	AddCenterBox('������ ��������');

	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr>
	<th>��� ������</th>
	<th>���������</th>
	<th>��������</th>'
	//<th>���������</th>
	.'<th>�������</th>
	<th>�������</th>'
	.'</tr>';

	while($group = $db->FetchRow()){

		$func = '';
		$func .= SpeedButton('�������������', $config['admin_file'].'?exe=config&a=editgroup&id='.SafeDB($group['id'], 11, int)
		.(AdminConfigPlugins() ? '&plugins=1' : ''), 'images/admin/edit.png');
		$func .= SpeedButton('�������', $config['admin_file'].'?exe=config&a=deletegroup&id='.SafeDB($group['id'], 11, int).'&ok=0'
		.(AdminConfigPlugins() ? '&plugins=1' : ''), 'images/admin/delete.png');

		if($group['visible'] == '1'){
			$visible = '<font color="#008000">��</font>';
		}else{
			$visible = '<font color="#FF0000">���</font>';
		}

		$install_vals = Values('', $group['name'], $group['hname'], $group['description'],
			$group['visible']);
		$install = '$db->Insert("'.AdminConfigGroupTable().'","'.$install_vals.'");';

		$text .= '<tr>
		<td>'.SafeDB($group['name'], 255, str).'</td>
		<td>'.SafeDB($group['hname'], 255, str).'</td>
		<td>'.SafeDB($group['description'], 255, str).'</td>'
		//<td>'.$install.'</td>
		.'<td>'.$visible.'</td>
		<td>'.$func.'</td>
		</tr>';
	}
	$text .= '</table><br />';
	$text .= '.:�������� ������:.';
	AddText($text);

	FormRow('���', $site->Edit('name', '', false, 'style="width:400px;"'));
	FormRow('���������', $site->Edit('hname', '', false, 'style="width:400px;"'));
	FormRow('��������', $site->TextArea('description', '', 'style="width:400px;height:100px;"'));
	FormRow('�������', $site->Check('visible', '1', false));

	AddForm(
		'<form action="'.$config['admin_file'].'?exe=config&a=savegroup'.
			(AdminConfigPlugins() ? '&plugins=1' : '').'" method="post">',
		$site->Submit('��������')
	);
}

// �������������� ������
function AdminConfigGroupEdit()
{
	global $db, $site, $config;

	$id = SafeEnv($_GET['id'], 11, int);
	$db->Select(AdminConfigGroupTable(), "`id`='$id'");
	$group = $db->FetchRow();

	FormRow('���', $site->Edit('name', SafeDB($group['name'],255,str), false, 'style="width:400px;"'));
	FormRow('���������', $site->Edit('hname', SafeDB($group['hname'],255,str), false, 'style="width:400px;"'));
	FormRow('��������', $site->TextArea('description', SafeDB($group['description'],255,str), 'style="width:400px;height:100px;"'));
	FormRow('�������', $site->Check('visible', '1', $group['visible']=='1'));

	AddCenterBox('�������������� ������');
	AddForm(
		'<form action="'.$config['admin_file'].'?exe=config&a=savegroup'
			.'&id='.$id
			.(AdminConfigPlugins() ? '&plugins=1' : '').'" method="post">',
		$site->Button('������', 'onclick="history.go(-1)"').$site->Submit('���������')
	);
}

// ���������� ������
function AdminConfigGroupSave()
{
	global $db, $config;
	$name = SafeEnv($_POST['name'], 255, str);
	$hname = SafeEnv($_POST['hname'], 255, str);
	$description = SafeEnv($_POST['description'], 255, str);
	if(isset($_POST['visible'])){
		$visible = '1';
	}else{
		$visible = '0';
	}

	$vals = Values('', $name, $hname, $description, $visible);
	if(isset($_GET['id'])){
		$id = SafeEnv($_GET['id'], 11, int);
		$db->Update(AdminConfigGroupTable(), $vals, "`id`='$id'", true);
	}else{
		$db->Insert(AdminConfigGroupTable(), $vals);
	}
	GO(ADMIN_FILE.'?exe=config'.(AdminConfigPlugins() ? '&a=view_groups_plugins&plugins=1' : '&a=view_groups'));
}

// �������� ������
function AdminConfigGroupDelete()
{
	global $config, $db;
	$back_url = '';

	if(!AdminConfigPlugins()){
		$back_url = $config['admin_file'].'?exe=config&a=view_groups';
	}else{
		$back_url = $config['admin_file'].'?exe=config&a=view_groups_plugins&plugins=1';
	}

	if(!isset($_GET['id'])){
		GO($back_url);
	}else{
		$id = SafeEnv($_GET['id'], 11, int);
	}

	if(isset($_GET['ok']) && $_GET['ok'] == '1'){
		$db->Delete(AdminConfigGroupTable(), "`id`='$id'");
		$db->Delete(AdminConfigConfigTable(), "`group_id`='$id'");
		GO($back_url);
	}else{
		$r = $db->Select(AdminConfigGroupTable(), "`id`='$id'");
		$text = '�� ������������� ������ ������� ������ "'.SafeDB($r[0]['hname'], 255, str)
		.'"?<br />��� ��������� ������ ����� �������.<br />'
		.'<a href="'.$config['admin_file'].'?exe=config&a=deletegroup&id='.$id.'&ok=1'
		.(AdminConfigPlugins() ? '&plugins=1' : '').'">��</a>'
		.' &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">���</a>';
		AddTextBox("��������!", $text);
	}
}

function AdminConfig( $action )
{
	TAddToolLink('��������� �����', 'main', 'config');
	TAddToolLink('��� ���������', 'view_all', 'config&a=view_all');
	TAddToolLink('�������� ���������', 'add', 'config&a=add');
	TAddToolLink('������ ��������', 'view_groups', 'config&a=view_groups');
	TAddToolBox($action);
	TAddToolLink('��� ��������� ��������','view_all_plugins','config&a=view_all_plugins&plugins=1');
	TAddToolLink('�������� ��������� �������', 'add_plugins', 'config&a=add_plugins&plugins=1');
	TAddToolLink('������ �������� ��������', 'view_groups_plugins', 'config&a=view_groups_plugins&plugins=1');
	TAddToolBox($action);
	switch($action){
		case 'main':
			AdminConfigurationEdit('config', 0, false, true, '��������� �����');
		break;

		case 'configsave':
			AdminConfigurationSave('config');
		break;

		case 'add':
		case 'add_plugins':
		case 'edit':
			AdminConfigAdd();
		break;

		case 'save':
			AcAddRetrofitting();
		break;

		case 'view_all':
		case 'view_all_plugins':
			AdminViewRetrofittingList();
		break;

		case 'delete':
			AdminConfigDeleteRetrofitting();
		break;

		case 'view_groups':
		case 'view_groups_plugins':
			AdminConfigViewGroups();
		break;

		case 'editgroup':
			AdminConfigGroupEdit();
		break;

		case 'savegroup':
			AdminConfigGroupSave();
		break;

		case 'deletegroup':
			AdminConfigGroupDelete();
		break;

		default:
			AdminConfigurationEdit('config');
	}
}

if(isset($_GET['a'])){
	AdminConfig($_GET['a']);
}else{
	AdminConfig('main');
}

?>