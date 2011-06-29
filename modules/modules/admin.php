<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(!System::user()->CheckAccess2('modules', 'modules')){
	AddTextBox('������', '������ ��������');
	return;
}

System::admin()->AddSubTitle('������');

$action = isset($_GET['a']) ? $_GET['a'] : 'main';

System::admin()->SideBarAddMenuItem('����������', 'exe=modules&a=main', 'main');
System::admin()->SideBarAddMenuItem('�������� ����������', 'exe=modules&a=addons', 'addons');
System::admin()->SideBarAddMenuItem('��������� ����������', 'exe=modules&a=installlist', 'installlist');

//System::admin()->SideBarAddMenuItem('������', 'exe=modules&a=main', 'main');
//System::admin()->SideBarAddMenuItem('�����', 'exe=modules&a=block_types', 'block_types');
//System::admin()->SideBarAddMenuItem('�������', 'exe=modules&a=plugins', 'plugins');
System::admin()->SideBarAddMenuBlock('������', $action);

switch($action){
	case 'main':
		AdminModules(false);
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
	case 'plugins':
		AdminPluginsMain();
		break;
	case 'changestatus_plugin':
		AdminPluginsChangeStatus();
		break;
	case 'config_plugin':
		if(isset($_GET['name'])){
			include_once System::config('inc_dir').'configuration/functions.php';
			$conf_config_table = 'plugins_config';
			$conf_config_groups_table = 'plugins_config_groups';
			$group = SafeEnv((isset($_GET['group']) ? $_GET['group'].'.' : '').$_GET['name'], 255, str);
			$url = (isset($_GET['group']) ? '&group='.SafeEnv($_GET['group'], 255, str) : '').'&name='.SafeEnv($_GET['name'], 255, str);
			AdminConfigurationEdit('modules&a=plugins'.$url, $group, false, false, '������������ �������', 'a=configsave_plugin');
		}
		break;
	case 'configsave_plugin':
		if(isset($_GET['name'])){
			include_once System::config('inc_dir').'configuration/functions.php';
			$conf_config_table = 'plugins_config';
			$conf_config_groups_table = 'plugins_config_groups';
			$group = SafeEnv((isset($_GET['group']) ? $_GET['group'].'.' : '').$_GET['name'], 255, str);
			AdminConfigurationSave('modules&a=plugins', $group, false);
		}
		break;
	case 'block_types':
		AdminBlockTypes();
		break;
	case 'block_type_save':
		AdminBlockTypesSave();
		break;
	default:
		AdminModulesList(false);
}

function AdminModules(){
	UseScript('jquery_ui');
	// ��������� ����� ����������


	$modules = '';
	$blocks = '';
	$plugins = '';
	$themes = '';

	// ������� ���������� �� ��������
	System::admin()->AddOnLoadJS('
	$("#tabs").tabs();
	$(".ui-tabs .ui-tabs-panel")
		.css("padding", "0")
		.css("height", "400px")
		.css("overflow-y", "auto");
');
	$html = '<div id="tabs">
	<ul>
		<li><a href="#tabs-1">������</a></li>
		<li><a href="#tabs-2">�����</a></li>
		<li><a href="#tabs-3">�������</a></li>
		<li><a href="#tabs-4">�������</a></li>
	</ul>
	<div id="tabs-1">1</div>
	<div id="tabs-2">2</div>
	<div id="tabs-3">3</div>
	<div id="tabs-4">4</div>
</div>';

	System::admin()->AddTextBox('����������', $html);
}

function AdminModulesList( $system ){
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

function AdminModulesOrderSave(){
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

function AdminModulesInstallList(){
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

function AdminModulesInstall(){
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
function AdminModulesUninstall(){
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

function AdminModulesChangeStatus(){
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

function AdminModulesConfig(){
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

function AdminModulesConfigSave(){
	global $config, $db;
	$set = "name='".SafeEnv($_POST['name'], 255, str)."',"
	."view='".SafeEnv($_POST['view'], 1, int)."',"
	."enabled='".SafeEnv($_POST['enabled'], 1, int)."',"
	."theme='".RealPath2(SafeEnv($_POST['theme'], 255, str))."'";
	$db->Update('modules', $set, "`name`='".SafeEnv($_GET['name'], 255, str)."'");
	GO($config['admin_file'].'?exe=modules');
}

// �������

function AdminPluginsPluginType( $type ){
	switch($type){
		case PLUG_AUTORUN:
			return '����������';
			break;
		case PLUG_ADMIN_AUTORUN:
			return '���������� (�����������)';
			break;
		case PLUG_MAIN_AUTORUN:
			return '���������� (����)';
			break;
		case PLUG_CALLEE:
			return '����������';
			break;
		case PLUG_MANUAL:
			return '������������';
			break;
		case PLUG_MANUAL_ONE:
			return '������������';
			break;
		default:
			return '������';
	}
}

function AdminPluginsRender( $name, $installed, $configex, $group = '' ){
	global $config;
	if($group != ''){
		$name2 = $group.'/'.$name;
	}else{
		$name2 = $name;
	}
	include ($config['plug_dir'].$name2.'/info.php');
	$image = '';
	$text = '';
	$options = '';
	if(isset($plugins[$name]['name-ru'])){
		$image .= '<b>'.SafeDB(SafeDB($plugins[$name]['name-ru'], 255, str), 255, str).'</b><br />';
	}
	if(isset($plugins[$name]['logo'])){
		$imagename = RealPath2($config['plug_dir'].$name.'/'.SafeDB($plugins[$name]['logo'], 250, str));
		if(!is_dir($imagename) && file_exists($imagename)){
			$image .= '<img width="64" height="64" src="'.$imagename.'" /><br />';
		}
	}
	if(isset($plugins[$name]['version'])){
		$image .= '������: '.SafeDB($plugins[$name]['version'], 255, str).'<br />';
	}
	if(isset($plugins[$name]['type'])){
		$text .= '<b>���:</b> '.AdminPluginsPluginType(SafeDB($plugins[$name]['type'], 2, int)).'<br />';
	}
	if($group != ''){
		$text .= '<b>������:</b> '.SafeDB($group, 250, str).'<br />';
	}
	if(isset($plugins[$name]['description-ru'])){
		$text .= '<b>��������:</b> '.SafeDB($plugins[$name]['description-ru'], 255, str).'<br />';
	}
	if(isset($plugins[$name]['cms'])){
		$text .= '<b>LinkorCMS:</b> '.SafeDB($plugins[$name]['cms'], 11, str).'<br />';
	}
	if(isset($plugins[$name]['site'])){
		$text .= '<b>����:</b> <a href="'.SafeDB($plugins[$name]['site'], 255, str).'" target="_blank">'.SafeDB($plugins[$name]['site'], 255, str).'</a><br />';
	}
	if($installed){
		$url = '<a href="'.$config['admin_file'].'?exe=plugins&a=uninstall'.($group != '' ? '&group='.$group : '').'&name='.SafeDB($name, 255, str).'">���������</a>';
		if($configex){
			$config_url = '<a href="'.$config['admin_file'].'?exe=plugins&a=config'.($group != '' ? '&group='.$group : '').'&name='.SafeDB($name, 255, str).'">���������</a>';
		}
	}else{
		$url = '<a href="'.$config['admin_file'].'?exe=plugins&a=install'.($group != '' ? '&group='.$group : '').'&name='.SafeDB($name, 255, str).'">����������</a>';
	}
	$options = '<b>�����:</b> ['.$url.']'.(isset($config_url) ? ' ['.$config_url.']' : '');
	return '<tr><td id="image">'.$image.'</td><td id="text" valign="top">'.$text.$options.'</td></tr>';
}

function AdminPluginsMain(){
	System::admin()->AddSubTitle('��� �������');
	UseScript('jquery_ui_table');

	$plug_dir = System::config('plug_dir');
	$plugins_find = GetPlugins(true);
	$configs_groups = PluginsConfigsGroups();

	$plugins_all = array();
	foreach($plugins_find['plugins'] as $pl){
		$name = $pl['name'];
		include($plug_dir.$name.'/info.php');
		$plugins = $plugins[$name];
		$plugins['type'] = AdminPluginsPluginType($plugins['type']);
		$plugins['group'] = '';
		$plugins['name'] = $name;
		$plugins['installed'] = $pl['installed'];
		$plugins['configex'] = isset($configs_groups[$name]);
		$plugins_all[] = $plugins;
	}
	foreach($plugins_find['groups'] as $group){
		foreach($group['plugins'] as $pl){
			$group = $pl['group'];
			$name = $pl['name'];
			include($plug_dir.$group.'/'.$name.'/info.php');
			$plugins = $plugins[$name];
			$plugins['type'] = AdminPluginsPluginType($plugins['type']);
			$plugins['group'] = $group;
			$plugins['name'] = $name;
			$plugins['installed'] = $pl['installed'];
			$plugins['configex'] = isset($configs_groups[$group.'.'.$name]);
			$plugins_all[] = $plugins;
		}
	}

	if(isset($_REQUEST['onpage'])){
		$num = intval($_REQUEST['onpage']);
	}else{
		$num = 20;
	}
	if(isset($_REQUEST['page'])){
		$page = intval($_REQUEST['page']);
	}else{
		$page = 1;
	}

	$columns = array('name-ru', 'group', 'type', 'version', 'installed');
	$sortby = '';
	$sortbyid = -1;
	$desc = true;
	if(isset($_REQUEST['sortby'])){
		$sortby = $columns[$_REQUEST['sortby']];
		$sortbyid = intval($_REQUEST['sortby']);
		$desc = $_REQUEST['desc'] == '1';
	}
	if($sortby != ''){
		SortArray($plugins_all, $sortby, $desc);
	}

	$table = new jQueryUiTable();
	$table->listing = ADMIN_FILE.'?exe=modules&a=plugins&ajax';
	$table->del = '';
	$table->total = count($plugins_all);
	$table->onpage = $num;
	$table->page = $page;
	$table->sortby = $sortbyid;
	$table->sortdesc = $desc;

	$table->AddColumn('���');
	$table->AddColumn('������', 'center');
	$table->AddColumn('���', 'center');
	$table->AddColumn('������', 'center');
	$table->AddColumn('������', 'center');
	$table->AddColumn('�������', 'center', false, true);

	$plugins_all = ArrayPage($plugins_all, $num, $page);
	foreach($plugins_all as $plugin){
		$name = SafeDB($plugin['name'], 255, str);
		$status = System::admin()->SpeedStatus(
			'���������', '��������',
			ADMIN_FILE.'?exe=modules&a=changestatus_plugin&group='.$plugin['group'].'&name='.$plugin['name'], $plugin['installed'],
			'images/bullet_green.png', 'images/bullet_red.png'
		);
		$func = '';
		if($plugin['configex']){
			$conf_url = ADMIN_FILE.'?exe=modules&a=config_plugin'.($plugin['group'] != '' ? '&group='.$plugin['group'] : '').'&name='.$name;
			$func .= System::admin()->SpeedButton('������������', $conf_url, 'images/admin/config.png');
		}
		$table->AddRow(
			$name,
			SafeDB($plugin['name-ru'], 255, str),
			SafeDB($plugin['group'], 255, str),
			SafeDB($plugin['type'], 255, str),
			SafeDB($plugin['version'], 255, str),
			$status,
			$func
		);
	}
	if(isset($_GET['ajax'])){
		echo $table->GetOptions();
		exit;
	}else{
		System::admin()->AddTextBox('�������', $table->GetHtml());
	}
}

function AdminPluginsChangeStatus(){
	$name = SafeEnv($_GET['name'], 255, str);
	$group = isset($_GET['group']) ? $_GET['group'] : '';
	System::database()->Select('plugins', "`name`='$name' and `group`='$group'");
	if(System::database()->NumRows() > 0){
		UninstallPlugin($name, $group);
	}else{
		InstallPlugin($name, $group);
	}
	exit('OK');
}

/*
 * ���� ������
 */
function AdminBlockTypes(){
	System::admin()->AddSubTitle('���� ������');
	UseScript('jquery_ui_table');
	$blocks_db = System::database()->Select('block_types');

	if(isset($_REQUEST['onpage'])){
		$num = intval($_REQUEST['onpage']);
	}else{
		$num = 20;
	}
	if(isset($_REQUEST['page'])){
		$page = intval($_REQUEST['page']);
	}else{
		$page = 1;
	}

	$columns = array('name', 'folder', 'comment');
	$sortby = '';
	$sortbyid = -1;
	$desc = true;
	if(isset($_REQUEST['sortby'])){
		$sortby = $columns[$_REQUEST['sortby']];
		$sortbyid = intval($_REQUEST['sortby']);
		$desc = $_REQUEST['desc'] == '1';
	}
	if($sortby != ''){
		SortArray($blocks_db, $sortby, $desc);
	}

	$table = new jQueryUiTable();
	$table->listing = ADMIN_FILE.'?exe=modules&a=block_types&ajax';
	$table->del = ADMIN_FILE.'?exe=modules&a=block_type_delete&ajax';
	$table->total = count($blocks_db);
	$table->onpage = $num;
	$table->page = $page;
	$table->sortby = $sortbyid;
	$table->sortdesc = $desc;

	$table->AddColumn('���');
	$table->AddColumn('�����', 'center');
	$table->AddColumn('��������', 'center');
	$table->AddColumn('�������', 'center', false, true);

	$blocks_db = ArrayPage($blocks_db, $num, $page);
	foreach($blocks_db as $block){
		$id = SafeDB($block['id'], 11, int);
		$name = SafeDB($block['name'], 255, str);
		$desc = SafeDB($block['comment'], 0, str);
		$folder = SafeDB($block['folder'], 255, str);

		$editlink = ADMIN_FILE.'?exe=modules&a=block_type_edit&id='.$id;

		$func = '';
		$func .= System::admin()->SpeedButton('�������������', $editlink, 'images/admin/edit.png');
		$func .= System::admin()->SpeedConfirmJs(
			'�������',
			'$(\'#jqueryuitable\').table(\'deleteRow\', '.$id.');',
			'images/admin/delete.png',
			'�������, ��� ������ ������� ���� ��� ����� �� ���� ������?'
		);

		$table->AddRow(
			$id,
			'<b><a href="'.$editlink.'">'.$name.'</a></b>',
			$folder,
			$desc,
			$func
		);
	}

	if(isset($_GET['ajax'])){
		echo $table->GetOptions();
		exit;
	}else{
		System::admin()->AddCenterBox('������������� ���� ������');
		System::admin()->AddText($table->GetHtml());

		System::admin()->FormTitleRow('�������� ��� �����');
		FormRow('���', System::admin()->Edit('name', '', false, 'style="width: 220px;"'));
		FormRow('����� (������������ blocks_dir)', System::admin()->Edit('folder', '', false, 'style="width: 220px;"'));
		System::admin()->FormTextRow('��������', System::site()->TextArea('comment', '', 'style="width:400px;height:100px;"'));
		AddForm(
			'<form action="'.ADMIN_FILE.'?exe=modules&a=block_type_save" method="post">',
			System::admin()->Submit('��������')
		);
	}
}

function AdminBlockTypesSave(){
	$block = SafeR('name, folder', 255, str) + SafeR('comment', 0, str);
	if(isset($_GET['id'])){ // ��������������
		$id = SafeEnv($_GET['id'], 11, int);
		System::database()->Update('block_types', MakeSet($block), "`id`='$id'");
	}else{
		System::database()->Insert('block_types', MakeValues("'','name','comment','folder'", $block));
	}
	GO(ADMIN_FILE.'?exe=modules&a=block_types');
}

?>