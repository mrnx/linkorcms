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
System::admin()->SideBarAddMenuItem('����������', 'exe=modules&a=installlist', 'installlist');
System::admin()->SideBarAddMenuBlock('', $action);

switch($action){
	case 'main':
		AdminModules();
		break;
	case 'installlist':
		AdminModulesInstallList();
		break;
	case 'install':
		AdminModulesInstall();
		break;
	case 'upload':
		AdminModulesUpload();
		break;
	case 'uninstall':
		AdminModulesUninstall();
		break;

	// ������
	case 'changestatus':
		AdminModulesChangeStatus();
		break;
	case 'config':
		AdminModulesConfig();
		break;
	case 'configsave':
		AdminModulesConfigSave();
		break;

//////////////////////////////////
//////////////////////////////////
//////////////////////////////////

	case 'system':
		AdminModulesList(true);
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
			$url = (isset($_GET['group']) ? '&group='.SafeDB($_GET['group'], 255, str) : '').'&name='.SafeDB($_GET['name'], 255, str);
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

/*
 * ������ ���� ������������� ����������.
 */
function AdminModules(){
	UseScript('jquery_ui');
	$mod_dir = System::config('mod_dir');
	$blocks_dir = System::config('blocks_dir');
	$plug_dir = System::config('plug_dir');
	$tpl_dir = System::config('tpl_dir');

	// �����
	$style = '<style>
	.ex-mod{ border-bottom: 1px #ccf solid; background-color: #F5F5FF; cursor: pointer; }
	.ex-mod:hover{ background-color: #DDEAF7; }
	.ex-mod-info { padding-top: 4px; }
	.ex-mod-info-description { padding-top: 0; margin-bottom: 5px; }
	.mod_info { margin-bottom: 5px; }
	</style>';

	// JS
	System::site()->AddJS('
	window.last_mod_id = "";
	function ShowModInfo(id){
	  $(".mod_info").slideUp().parents().css("cursor", "pointer");
	  if(last_mod_id != id){
	  	$("#mod_info_"+id).slideDown().parents().css("cursor", "default");
	  	last_mod_id = id;
	  }else{
	    last_mod_id = "";
	  }
	}
	window.last_block_id = "";
	function ShowBlockInfo(id){
	  $(".mod_info").slideUp().parents().css("cursor", "pointer");
	  if(last_block_id != id){
	  	$("#mod_info_"+id).slideDown().parents().css("cursor", "default");
	  	last_block_id = id;
	  }else{
	    last_block_id = "";
	  }
	}
	');

	// ������
	$modules_html = '<div style="border-top: 1px #ccf solid; ">';
	$mods = System::database()->Select('modules', "`system`='0'");
	foreach($mods as $mod){
		$info = ExtLoadInfo($mod_dir.$mod['folder']);
		if($info === false) continue;

		$mid = SafeEnv($mod['id'], 11, int);
		$func = '';
		$func .= System::admin()->SpeedStatus(
			'���������', '����������',
			ADMIN_FILE.'?exe=modules&a=changestatus&id='.$mid,
			$mod['enabled'] == '1',
			'images/bullet_green.png', 'images/bullet_red.png'
		);

		// ���������� ������ ��������, ������ �����, ����� ���������� ��������� ��������
		if(is_file($mod_dir.$mod['folder'].'/uninstall.php')){
			$func .= System::admin()->SpeedConfirm(
				'�������',
				ADMIN_FILE.'?exe=modules&a=uninstall&type='.EXT_MODULE.'&name='.SafeDB($mod['folder'], 255, str),
				'images/admin/delete.png',
				'��������� ������� ������ '.$mod['name'].'?'
			);
		}
		if(isset($info['icon'])){
			$icon = SafeDB($info['icon'], 255, str);
		}else{
			$icon = 'images/application.png';
		}
		if(isset($info['version'])){
			$version = SafeDB($info['version'], 255, str);
		}else{
			$version = CMS_VERSION;
		}
		if(isset($info['author'])){
			$author = SafeDB($info['author'], 255, str);
		}else{
			$author = '';
		}
		if(isset($info['site'])){
			$site = SafeDB($info['site'], 255, str);
		}else{
			$site = '';
		}
		if(isset($info['description'])){
			$description = SafeDB($info['description'], 0, str, false, false);
		}else{
			$description = '��� ��������.';
		}

		$modules_html .= '<table width="100%" class="ex-mod">
		<tr onmousedown="ShowModInfo(\'mod'.$mid.'\');">
			<td style="padding-left: 11px; vertical-align: top;">
				<div style="float: left;">
					<div style="float:left; padding-top: 6px;"><img src="'.$icon.'"></div>
					<div style="float:left; padding-top: 7px;">&nbsp;'.$mod['name'].' (v'.$version.')</div>
				</div>
			</td>
			<td width="62" align="center" style="padding: 3px; padding-bottom: 2px;">
				<div style="float: left">'.$func.'</div>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<div class="mod_info" id="mod_info_mod'.$mid.'" style="display: none; padding: 4px; padding-left: 11px;">
					<div class="ex-mod-info-description">'.$description.'</div>
		      '.($author != '' ? '<div class="ex-mod-info">�����: '.$author.'</div>' : '').'
		      '.($site != '' ? '<div class="ex-mod-info">����: <a href="'.$site.'" target="_blank">'.$site.'</a></div>' : '').'
				</div>
			</td>
		</tr>
		</table>';
	}
	$modules_html .= '</div>';

	// �����
	$blocks_html = '<div style="border-top: 1px #ccf solid; ">';
	$mods = System::database()->Select('block_types');
	foreach($mods as $mod){
		$info = ExtLoadInfo($blocks_dir.$mod['folder']);
		if($info === false) continue;

		$mid = SafeEnv($mod['id'], 11, int);
		$func = '';
		// ���������� ������ ��������, ������ �����, ����� ���������� ��������� ��������
		if(is_file($blocks_dir.$mod['folder'].'/uninstall.php')){
			$func .= System::admin()->SpeedConfirm(
				'�������',
				ADMIN_FILE.'?exe=modules&a=uninstall&type='.EXT_BLOCK.'&name='.SafeDB($mod['folder'], 255, str),
				'images/admin/delete.png',
				'��������� ������� ������ '.$mod['name'].'?'
			);
		}
		if(isset($info['icon']) && $info['icon'] != ''){
			$icon = SafeDB($info['icon'], 255, str);
		}else{
			$icon = 'images/application.png';
		}
		if(isset($info['version'])){
			$version = SafeDB($info['version'], 255, str);
		}else{
			$version = CMS_VERSION;
		}
		if(isset($info['author']) && $info['author'] != ''){
			$author = SafeDB($info['author'], 255, str);
		}else{
			$author = '';
		}
		if(isset($info['site']) && $info['site'] != ''){
			$site = SafeDB($info['site'], 255, str);
		}else{
			$site = '';
		}
		if(isset($info['description']) && $info['description'] != '' && $info['description'] != ' - '){
			$description = SafeDB($info['description'], 0, str, false, false);
		}else{
			$description = '��� ��������.';
		}

		$blocks_html .= '<table width="100%" class="ex-mod">
		<tr onmousedown="ShowBlockInfo(\'block'.$mid.'\');">
			<td style="padding-left: 11px; vertical-align: top;">
				<div style="float: left;">
					<div style="float:left; padding-top: 6px;"><img src="'.$icon.'"></div>
					<div style="float:left; padding-top: 7px;">&nbsp;'.$mod['name'].' (v'.$version.')</div>
				</div>
			</td>
			<td width="62" align="center" style="padding: 3px; padding-bottom: 2px;">
				<div style="float: left">'.$func.'</div>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<div class="mod_info" id="mod_info_block'.$mid.'" style="display: none; padding: 4px; padding-left: 11px;">
					<div class="ex-mod-info-description">'.$description.'</div>
		      '.($author != '' ? '<div class="ex-mod-info">�����: '.$author.'</div>' : '').'
		      '.($site != '' ? '<div class="ex-mod-info">����: <a href="'.$site.'" target="_blank">'.$site.'</a></div>' : '').'
				</div>
			</td>
		</tr>
		</table>';
	}
	$blocks_html .= '</div>';

	// �������
	$plugins_html = '<div style="border-top: 1px #ccf solid; ">';
	$mods = System::database()->Select('plugins');
	foreach($mods as $mod){
		if($mod['group'] != ''){
			$path = $plug_dir.$mod['group'].'/'.$mod['name'];
		}else{
			$path = $plug_dir.$mod['name'];
		}
		$info = ExtLoadInfo($path);
		if($info === false) continue;

		$mid = SafeEnv($mod['id'], 11, int);
		$func = '';
		// ���������� ������ ��������, ������ �����, ����� ���������� ��������� ��������
		if(isset($info['1.3']) || is_file($plug_dir.$mod['name'].'/uninstall.php')){
			$func .= System::admin()->SpeedConfirm(
				'�������',
				ADMIN_FILE.'?exe=modules&a=uninstall&type='.EXT_PLUGIN.'&name='.SafeDB($mod['name'], 255, str).($mod['group']!=''?'&group='.SafeDB($mod['group'], 255, str) : ''),
				'images/admin/delete.png',
				'��������� ������� ������ '.$mod['name'].'?'
			);
		}
		$name = SafeDB($info['name'], 255, str);
		if(isset($info['icon']) && $info['icon'] != ''){
			$icon = SafeDB($info['icon'], 255, str);
		}else{
			$icon = 'images/application.png';
		}
		if(isset($info['version'])){
			$version = SafeDB($info['version'], 255, str);
		}else{
			$version = CMS_VERSION;
		}
		if(isset($info['author']) && $info['author'] != ''){
			$author = SafeDB($info['author'], 255, str);
		}else{
			$author = '';
		}
		if(isset($info['site']) && $info['site'] != ''){
			$site = SafeDB($info['site'], 255, str);
		}else{
			$site = '';
		}
		if(isset($info['description']) && $info['description'] != '' && $info['description'] != ' - '){
			$description = SafeDB($info['description'], 0, str, false, false);
		}else{
			$description = '��� ��������.';
		}

		$plugins_html .= '<table width="100%" class="ex-mod">
		<tr onmousedown="ShowBlockInfo(\'plug'.$mid.'\');">
			<td style="padding-left: 11px; vertical-align: top;">
				<div style="float: left;">
					<div style="float:left; padding-top: 6px;"><img src="'.$icon.'"></div>
					<div style="float:left; padding-top: 7px;">&nbsp;'.$name.' (v'.$version.')</div>
				</div>
			</td>
			<td width="62" align="center" style="padding: 3px; padding-bottom: 2px;">
				<div style="float: left">'.$func.'</div>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<div class="mod_info" id="mod_info_plug'.$mid.'" style="display: none; padding: 4px; padding-left: 11px;">
					<div class="ex-mod-info-description">'.$description.'</div>
		      '.($author != '' ? '<div class="ex-mod-info">�����: '.$author.'</div>' : '').'
		      '.($site != '' ? '<div class="ex-mod-info">����: <a href="'.$site.'" target="_blank">'.$site.'</a></div>' : '').'
				</div>
			</td>
		</tr>
		</table>';
	}
	$plugins_html .= '</div>';

	// �������
	$templates_html = '<div style="border-top: 1px #ccf solid; ">';
	$mods = System::database()->Select('templates');
	foreach($mods as $mod){
		$info = ExtLoadInfo($tpl_dir.$mod['folder']);
		if($info === false) continue;

		$mid = SafeEnv($mod['id'], 11, int);
		$func = '';
		$func .= System::admin()->SpeedConfirm(
			'�������',
			ADMIN_FILE.'?exe=modules&a=uninstall&type='.EXT_TEMPLATE.'&name='.SafeDB($mod['folder'], 255, str),
			'images/admin/delete.png',
			'������� ������ '.$info['name'].'?'
		);
		if(isset($info['icon']) && $info['icon'] != ''){
			$icon = $info['icon'];
		}else{
			$icon = 'images/application.png';
		}
		if(isset($info['version'])){
			$version = SafeDB($info['version'], 255, str);
		}else{
			$version = '';
		}
		if(isset($info['author']) && $info['author'] != ''){
			$author = SafeDB($info['author'], 255, str);
		}else{
			$author = '';
		}
		if(isset($info['site']) && $info['site'] != ''){
			$site = SafeDB($info['site'], 255, str);
		}else{
			$site = '';
		}
		if(isset($info['description']) && $info['description'] != '' && $info['description'] != ' - '){
			$description = SafeDB($info['description'], 0, str, false, false);
		}else{
			$description = '��� ��������.';
		}

		$templates_html .= '<table width="100%" class="ex-mod">
		<tr onmousedown="ShowBlockInfo(\'tpl'.$mid.'\');">
			<td style="padding-left: 11px; vertical-align: top;">
				<div style="float: left;">
					<div style="float:left; padding-top: 6px;"><img src="'.$icon.'"></div>
					<div style="float:left; padding-top: 7px;">&nbsp;'.$info['name'].($version != '' ?  ' (v'.$version.')' : '').($mod['admin'] == '1' ? ' (�����-������)' : '').'</div>
				</div>
			</td>
			<td width="62" align="center" style="padding: 3px; padding-bottom: 2px;">
				<div style="float: left">'.$func.'</div>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<div class="mod_info" id="mod_info_tpl'.$mid.'" style="display: none; padding: 4px; padding-left: 11px;">
					<div class="ex-mod-info-description">'.$description.'</div>
		      '.($author != '' ? '<div class="ex-mod-info">�����: '.$author.'</div>' : '').'
		      '.($site != '' ? '<div class="ex-mod-info">����: <a href="'.$site.'" target="_blank">'.$site.'</a></div>' : '').'
				</div>
			</td>
		</tr>
		</table>';
	}
	$templates_html .= '</div>';

	// ������� ���������� �� ��������
	System::admin()->AddOnLoadJS('
	$("#tabs").tabs({event: "mousedown"}).css("width", "700px");
	$(".ui-tabs .ui-tabs-panel")
		.css("padding", "0")
		.css("padding-top","3px")
		.css("height", "400px")
		.css("overflow-y", "auto");
');
	$html = '<div id="tabs">
	<ul>
		<li><a href="#tabs-1"><img src="images/widgets.png" style="vertical-align: bottom;">&nbsp;������</a></li>
		<li><a href="#tabs-2"><img src="images/block.png" style="vertical-align: bottom;">&nbsp;�����</a></li>
		<li><a href="#tabs-3"><img src="images/plugin.png" style="vertical-align: bottom;">&nbsp;�������</a></li>
		<li><a href="#tabs-4"><img src="images/skins.png" style="vertical-align: bottom;">&nbsp;�������</a></li>
	</ul>
	<div id="tabs-1">'.$modules_html.'</div>
	<div id="tabs-2">'.$blocks_html.'</div>
	<div id="tabs-3">'.$plugins_html.'</div>
	<div id="tabs-4">'.$templates_html.'</div>
</div>';

	System::admin()->AddTextBox('����������', $style.$html);
}

/*
 * ������ ���������� ��������� ��� ���������.
 */
function AdminModulesInstallList(){
	global $db, $config, $site;

	$mod_dir = $config['mod_dir'];
	$plug_dir = $config['plug_dir'];
	$block_dir = $config['blocks_dir'];
	$temp_dir = $config['tpl_dir'];
	$list = array();

	$title = '��������� ����������';
	System::admin()->AddSubTitle($title);

	// ��������� ���������� �� ������������� �������
	$installed_mods = array();
	$installed_plugins = array();
	$installed_blocks = array();
	$installed_templates = array();
	System::database()->Select('modules');
	while($mod = System::database()->FetchRow()){
		$installed_mods[] = $mod['folder'];
	}
	System::database()->Select('plugins');
	while($mod = System::database()->FetchRow()){
		$installed_plugins[] = ($mod['group'] != '' ? $mod['group'].'/' : '').$mod['name'];
	}
	System::database()->Select('block_types');
	while($mod = System::database()->FetchRow()){
		$installed_blocks[] = $mod['folder'];
	}
	System::database()->Select('templates');
	while($mod = System::database()->FetchRow()){
		$installed_templates[] = $mod['folder'];
	}

	// ����� �������
	$mod_folders = GetFolders($mod_dir);
	foreach($mod_folders as $folder){
		if(!in_array($folder, $installed_mods)){
			$info = ExtLoadInfo($mod_dir.$folder);
			if($info !== false){
				$info['type'] = EXT_MODULE;
				$info['path'] = $mod_dir.$folder.'/';
				$info['folder'] = $folder;
				if(is_file($info['path'].'install.php') && is_file($info['path'].'uninstall.php')){
					$list[] = $info;
				}
			}
		}
	}

	// ����� ��������
	$plug_folders = GetFolders($plug_dir);
	foreach($plug_folders as $folder){
		if(!is_file($plug_dir.$folder.'/info.php')){ // �������� ������
			$plug_folders2 = GetFolders($plug_dir.$folder.'/');
			foreach($plug_folders2 as $folder2){
				if(!in_array($folder.'/'.$folder2, $installed_plugins)){
					$info = ExtLoadInfo($plug_dir.$folder.'/'.$folder2);
					if($info !== false){
						$info['type'] = EXT_PLUGIN;
						$info['path'] = $plug_dir.$folder.'/'.$folder2.'/';
						$info['group'] = $folder;
						$info['folder'] = $folder2;
						if(isset($info['1.3']) || (is_file($info['path'].'install.php') && is_file($info['path'].'uninstall.php'))){
							$list[] = $info;
						}
					}
				}
			}
		}
		if(!in_array($folder, $installed_plugins)){
			$info = ExtLoadInfo($plug_dir.$folder);
			if($info !== false){
				$info['type'] = EXT_PLUGIN;
				$info['path'] = $plug_dir.$folder.'/';
				$info['folder'] = $folder;
				if(isset($info['1.3']) || (is_file($info['path'].'install.php') && is_file($info['path'].'uninstall.php'))){
					$list[] = $info;
				}
			}
		}
	}

	// ����� ������
	$block_folders = GetFolders($block_dir);
	foreach($block_folders as $folder){
		if(!in_array($folder, $installed_blocks)){
			$info = ExtLoadInfo($block_dir.$folder);
			if($info !== false){
				$info['type'] = EXT_BLOCK;
				$info['path'] = $block_dir.$folder.'/';
				$info['folder'] = $folder;
				if(is_file($info['path'].'install.php') && is_file($info['path'].'uninstall.php')){
					$list[] = $info;
				}
			}
		}
	}

	// ����� ��������
	$temp_folders = GetFolders($temp_dir);
	foreach($temp_folders as $folder){
		$info = ExtLoadInfo($temp_dir.$folder);
		if($info !== false){
			$info['type'] = EXT_TEMPLATE;
			$info['path'] = $temp_dir.$folder.'/';
			$info['folder'] = $folder;
			$list[] = $info;
		}
	}

	$count = count($list);
	$text = '<form action="'.ADMIN_FILE.'?exe=modules&a=install" method="post">';
	$text .= '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>���������</th><th>���</th><th>���</th><th>��������</th><th>�����</th><th>����</th></tr>';

	foreach($list as $i=>$ext){
		switch($ext['type']){
			case 1: $type = '<img src="images/widgets.png" title="������">';
				break;
			case 2: $type = '<img src="images/plugin.png" title="������">';
				break;
			case 3: $type = '<img src="images/block.png" title="����">';
				break;
			case 4: $type = '<img src="images/skins.png" title="������">';
				break;
		}
		$text .= '<tr>
		<td>'
			.$site->Check('install_'.$i, $list[$i])
			.$site->Hidden('folder_'.$i, $ext['folder'])
			.$site->Hidden('type_'.$i, $ext['type'])
			.($ext['type'] == EXT_PLUGIN ? $site->Hidden('group_'.$i, $ext['group']) : '')
		.'</td>
		<td>'.$type.'</td>
		<td>'.SafeDB($ext['name'], 255, str).'</td>
		<td>'.(isset($ext['description']) && $ext['description'] != '' && $ext['description'] != ' - ' ? SafeDB($ext['description'], 255, str) : '��� ��������').'</td>
		<td>'.(isset($ext['author']) && $ext['author'] != '' ? SafeDB($ext['author'], 255, str) : '&nbsp;').'</td>
		<td>'.(isset($ext['site']) && $ext['site'] != '' ? '<a href="'.$ext['site'].'" target="_blank">�������</a>' : '���').'</td>
		</tr>';
	}

	$text .= '</table>';
	$text .= '<div style="margin-bottom: 25px;">'.$site->Hidden('count', $count).$site->Submit('���������� ����������').'</div>';
	$text .= '</form>';

	System::admin()->AddCenterBox($title.' ('.$count.' ������ � ���������)');
	System::admin()->AddText($text);

	System::admin()->FormTitleRow('��������� �����');
	System::admin()->FormRow('', $site->FFile('extension'));
	System::admin()->AddForm(System::admin()->FormOpen(ADMIN_FILE.'?exe=modules&a=upload', 'post', true), System::admin()->Submit('���������'));
}

/*
 * ��������� ����������.
 */
function AdminModulesInstall(){
	global $db, $config, $site;
	$count = SafeEnv($_POST['count'], 11, int);
	$new_installed = array();
	for($i = 0; $i < $count; $i++){
		if(isset($_POST['install_'.$i])){
			$type = $_POST['type_'.$i];
			$folder = RealPath2($_POST['folder_'.$i], 255, str);
			switch($type){
				case EXT_PLUGIN:
					$group = $_POST['group_'.$i];
					$path = RealPath2(System::config('plug_dir').$group.'/'.$folder);
					$info = ExtLoadInfo($path);
					if(isset($info['1.3'])){ // ��������� ������ ������ �������
						ExtInstallPlugin($group, $folder, $info['function'], $info['type']);
						if(is_file($path.'/install.php')){
							require $path.'/install.php';
						}
						$new_installed[] = $info; // ����� � ����� ������
					}else{
						if(is_file($path.'/install.php')){
							require $path.'/install.php';
							$new_installed[] = $info;  // ������� install.php �����������
						}
					}
				break;
				case EXT_BLOCK:
					$path = RealPath2(System::config('blocks_dir').$folder);
					$info = ExtLoadInfo($path);
					require $path.'/install.php';
					$new_installed[] = $info;
				break;
				case EXT_MODULE:
					$path = RealPath2(System::config('mod_dir').$folder);
					$info = ExtLoadInfo($path);
					require $path.'/install.php';
					if(isset($info['1.3'])){
						// ��������� ����� ����
						$folder = SafeEnv($folder, 255, str);
						$mod_name = SafeEnv($info['name'], 255, str);
						System::database()->Select('adminmenu', "`parent`='5'");
						$order = System::database()->NumRows();
						System::database()->Insert('adminmenu',"'','5','$order','$folder','$mod_name','images/application.png','exe=$folder','','0','','admin','1'");
					}
					$new_installed[] = $info;
				break;
				case EXT_TEMPLATE:
					// ��������� ��� ���������� ���������� �������������
					$path = RealPath2(System::config('tpl_dir').$folder);
					$info = ExtLoadInfo($path);
					$admin = is_file($path.'/theme_admin.html') ? '1' : '0';
					ExtInstallTemplate($folder, $admin);
					$new_installed[] = $info;
				break;
			}
		}
	}

	$html = '';
	$html .= '<div style="border: 1px #cfcfcf solid; width: 50%; background-color: #fff;">';
	if(count($new_installed) > 0){
		foreach($new_installed as $info){
			$html .= '
			<table style="width: 100%;">
				<tr>
					<td style="padding: 5px;">'.SafeDB($info['name'], 255, str).'</td>
					<td style="width: 120px; padding: 5px;"><img src="images/admin/accept.png" style="vertical-align: middle;" /> �����������</td>
				</tr>
			</table>';
		}
		$html .= '</div>'.System::admin()->Button('�����', 'onclick="history.go(-1);"')
						 .System::admin()->Button('�����', "onclick=\"Admin.LoadPage('".ADMIN_FILE."?exe=modules');\"");
		$cache = LmFileCache::Instance();
		$cache->Clear('config');
	}else{
		$html .= '<div style="padding:  5px;">�� �� ������� ���������� ��� ���������. ������� "�����" � �������� ��������� ���������� ������� ����� ����������.</div>';
		$html .= '</div>'.System::admin()->Button('�����', 'onclick="history.go(-1);"');
	}
	System::admin()->AddTextBox('��������� ����������', $html);
}

/*
 * �������� � ���������� ����� �������.
 */
function AdminModulesUpload(){
	$extension = $_FILES['extension'];
	$file_ext = GetFileExt($extension['name']);
	if(strtolower($file_ext) == '.zip'){
		if($extension['error'] != 0){
			System::admin()->AddTextBox('������', '������ ��� �������� �����. ��� ������: '.$extension['error'].'.');
			return;
		}
		// ������������� �����
		if(!ExtExtract($extension['tmp_name'])){
			System::admin()->AddTextBox('������', '�� ������� ��������� ����. �������� ������.');
			return;
		}
	}else{
		System::admin()->AddTextBox('������', '�������� ���������� �����.');
	}
}

/*
 * �������� ����������
 */
function AdminModulesUninstall(){
	global $db, $config, $user; // ��� ������ �������
	$ext_type = $_GET['type'];
	$folder = $_GET['name'];
	if(isset($_GET['group'])) $group = $_GET['group'];
	switch($ext_type){
		case EXT_MODULE:
			$mod_path = RealPath2(System::config('mod_dir').$folder);
			$info = ExtLoadInfo($mod_path);
			if(isset($_POST['ok']) || isset($info['1.3'])){
				$uninstall = $mod_path.'/uninstall.php';
				if(file_exists($uninstall)){
					$delete_tables = isset($_POST['delete_tables']);
					$delete_files = isset($_POST['delete_files']);
					include $uninstall;
					LmFileCache::Instance()->Clear('config');
					if(isset($info['1.3'])){ // ������� ����� ����
						$folder = SafeEnv($folder, 255, str);
						System::database()->Delete('adminmenu', "`module`='$folder'");
					}
				}
				GO(ADMIN_FILE.'?exe=modules#tabs-1');
			}else{
				$folder = SafeEnv($folder, 255, str);
				System::database()->Select('modules', "`folder`='$folder'");
				if($db->NumRows() == 0){
					AddTextBox('������', '������ �� ����������.');
					return;
				}
				$mod = System::database()->FetchRow();
				$name = SafeDB($mod['name'], 255, str);
				$text = '';
				$text .= '<form method="post">';
				$text .= '<div style="padding: 10px 0 10px 25px;">';
				$text .= '<div style="padding-bottom: 10px">';
				$text .= '<label><input type="checkbox" name="delete_tables" checked>&nbsp;������� ������� ��</label><br>';
				$text .= '<label><input type="checkbox" name="delete_files" checked>&nbsp;������� ����� ������</label>';
				$text .= '</div>';
				$text .= System::admin()->Hidden('ok', '1');
				$text .= '<div>'.System::admin()->Button('������', 'onclick="history.go(-1)"').System::admin()->Submit('�������').'</div>';
				$text .= '</div></form>';
				AddTextBox('�������� ������ "'.$name.'"', $text);
			}
			break;
		case EXT_BLOCK:
			$mod_path = RealPath2(System::config('blocks_dir').$folder);
			if(isset($_POST['ok'])){
				$uninstall = $mod_path.'/uninstall.php';
				if(file_exists($uninstall)){
					$delete_tables = isset($_POST['delete_tables']);
					$delete_files = isset($_POST['delete_files']);
					include $uninstall;
					LmFileCache::Instance()->Clear('config');
				}
				GO(ADMIN_FILE.'?exe=modules#tabs-2');
			}else{
				$folder = SafeEnv($folder, 255, str);
				System::database()->Select('block_types', "`folder`='$folder'");
				if($db->NumRows() == 0){
					AddTextBox('������', '���� �� ����������.');
					return;
				}
				$mod = System::database()->FetchRow();
				$name = SafeDB($mod['name'], 255, str);
				$text = '';
				$text .= '<form method="post">';
				$text .= '<div style="padding: 10px 0 10px 25px;">';
				$text .= '<div style="padding-bottom: 10px">';
				$text .= '<label><input type="checkbox" name="delete_tables" checked>&nbsp;������� ������� ��</label><br>';
				$text .= '<label><input type="checkbox" name="delete_files" checked>&nbsp;������� �����</label>';
				$text .= '</div>';
				$text .= System::admin()->Hidden('ok', '1');
				$text .= '<div>'.System::admin()->Button('������', 'onclick="history.go(-1)"').System::admin()->Submit('�������').'</div>';
				$text .= '</div></form>';
				AddTextBox('�������� ������ "'.$name.'"', $text);
			}
			break;
		case EXT_PLUGIN:
			if(isset($_GET['group'])){
				$group = $_GET['group'].'/';
				$groupenv = SafeEnv($_GET['group'], 255, str);
			}else{
				$group = '';
				$groupenv = '';
			}
			$mod_path = RealPath2(System::config('plug_dir').$group.$folder);
			$info = ExtLoadInfo($mod_path);
			if(isset($_POST['ok']) || isset($info['1.3'])){
				$uninstall = $mod_path.'/uninstall.php';
				if(file_exists($uninstall)){
					$delete_tables = isset($_POST['delete_tables']);
					$delete_files = isset($_POST['delete_files']);
					include $uninstall;
					LmFileCache::Instance()->Clear('config'); // FIXME: plugin config
				}
				if(isset($info['1.3'])){
					$folder = SafeEnv($folder, 255, str);
					System::database()->Delete('plugins', "`name`='$folder' and `group`='$groupenv'");
				}
				// FIXME: delete plugins cache
				GO(ADMIN_FILE.'?exe=modules#tabs-3');
			}else{
				$folder = SafeEnv($folder, 255, str);
				System::database()->Select('plugins', "`name`='$folder' and `group`='$groupenv'");
				if($db->NumRows() == 0){
					AddTextBox('������', '������ �� ����������.');
					return;
				}
				$mod = System::database()->FetchRow();
				$name = SafeDB($info['name'], 255, str);
				$text = '';
				$text .= '<form method="post">';
				$text .= '<div style="padding: 10px 0 10px 25px;">';
				$text .= '<div style="padding-bottom: 10px">';
				$text .= '<label><input type="checkbox" name="delete_tables" checked>&nbsp;������� ������� ��</label><br>';
				$text .= '<label><input type="checkbox" name="delete_files" checked>&nbsp;������� �����</label>';
				$text .= '</div>';
				$text .= System::admin()->Hidden('ok', '1');
				$text .= '<div>'.System::admin()->Button('������', 'onclick="history.go(-1)"').System::admin()->Submit('�������').'</div>';
				$text .= '</div></form>';
				AddTextBox('�������� ������ "'.$name.'"', $text);
			}
			break;
		case EXT_TEMPLATE:
			$mod_path = RealPath2(System::config('tpl_dir').$folder);
			if(isset($_POST['ok'])){
				ExtDeleteTemplate($folder, isset($_POST['delete_files']));
				GO(ADMIN_FILE.'?exe=modules#tabs-4');
			}else{
				$info = ExtLoadInfo($mod_path);
				$name = SafeDB($info['name'], 255, str);
				$text = '';
				$text .= '<form method="post">';
				$text .= '<div style="padding: 10px 0 10px 25px;">';
				$text .= '<div style="padding-bottom: 10px">';
				$text .= '<label><input type="checkbox" name="delete_files" checked>&nbsp;������� �����</label>';
				$text .= '</div>';
				$text .= System::admin()->Hidden('ok', '1');
				$text .= '<div>'.System::admin()->Button('������', 'onclick="history.go(-1)"').System::admin()->Submit('�������').'</div>';
				$text .= '</div></form>';
				AddTextBox('�������� ������� "'.$name.'"', $text);
			}
			break;
	}
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
