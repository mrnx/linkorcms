<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(!System::user()->CheckAccess2('modules', 'modules')){
	AddTextBox('Ошибка', 'Доступ запрещён');
	return;
}

System::admin()->AddSubTitle('Расширения');

$action = isset($_GET['a']) ? $_GET['a'] : 'main';

System::admin()->SideBarAddMenuItem('Расширения', 'exe=modules&a=main', 'main');
System::admin()->SideBarAddMenuItem('Установить', 'exe=modules&a=installlist', 'installlist');
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

	// Модули
	case 'changestatus':
		AdminModulesChangeStatus();
		break;
	case 'mod_config':
		AdminModulesConfig();
		break;
	case 'mod_configsave':
		AdminModulesConfigSave();
		break;

	case 'plug_config':
		if(isset($_GET['name'])){
			include_once System::config('inc_dir').'configuration/functions.php';
			$conf_config_table = 'plugins_config';
			$conf_config_groups_table = 'plugins_config_groups';
			$group = SafeEnv((isset($_GET['group']) ? $_GET['group'].'.' : '').$_GET['name'], 255, str);
			$url = (isset($_GET['group']) ? '&group='.SafeDB($_GET['group'], 255, str) : '').'&name='.SafeDB($_GET['name'], 255, str);
			AdminConfigurationEdit('modules'.$url, $group, false, false, 'Конфигурация плагина', 'a=plug_configsave');
		}
		break;
	case 'plug_configsave':
		if(isset($_GET['name'])){
			include_once System::config('inc_dir').'configuration/functions.php';
			$conf_config_table = 'plugins_config';
			$conf_config_groups_table = 'plugins_config_groups';
			$group = SafeEnv((isset($_GET['group']) ? $_GET['group'].'.' : '').$_GET['name'], 255, str);
			AdminConfigurationSave('modules#tabs-3', $group, false);
		}
		break;
	default:
		AdminModules();
}

/*
 * Список всех установленных расширений.
 */
function AdminModules(){
	UseScript('jquery_ui');
	$mod_dir = System::config('mod_dir');
	$blocks_dir = System::config('blocks_dir');
	$plug_dir = System::config('plug_dir');
	$tpl_dir = System::config('tpl_dir');

	// Стили
	$style = '<style>
	.ex-mod{ border-bottom: 1px #ccf solid; background-color: #DDEAF7; cursor: pointer; }
	.ex-mod:hover{ background-color: #F5F5FF; }
	.ex-mod-info { padding-top: 4px; }
	.ex-mod-info-description { padding-top: 0; margin-bottom: 5px; }
	.mod_info { margin-bottom: 5px; }
	</style>';

	// JS
	System::site()->AddJS('
	window.last_mod_id = "";
	ShowModInfo = function(id){
	  $(".mod_info").slideUp().parents(".ex-mod").css("background-color", "#DDEAF7");
	  if(last_mod_id != id){
	  	$("#mod_info_"+id).slideDown().parents(".ex-mod").css("background-color", "#FFF");
	  	last_mod_id = id;
	  }else{
	    last_mod_id = "";
	  }
	};
	window.last_block_id = "";
	ShowBlockInfo = function(id){
	  $(".mod_info").slideUp().parents(".ex-mod").css("background-color", "#DDEAF7");
	  if(last_block_id != id){
	  	$("#mod_info_"+id).slideDown().parents(".ex-mod").css("background-color", "#FFF");
	  	last_block_id = id;
	  }else{
	    last_block_id = "";
	  }
	};
	');

	// Модули
	$modules_html = '<div style="border-top: 1px #ccf solid; ">';
	$mods = System::database()->Select('modules', "`system`='0'");
	foreach($mods as $mod){
		$info = ExtLoadInfo($mod_dir.$mod['folder']);
		if($info === false) continue;

		$mid = SafeEnv($mod['id'], 11, int);
		$func = '';
		$func .= System::admin()->SpeedStatus(
			'Отключить', 'Подключить',
			ADMIN_FILE.'?exe=modules&a=changestatus&type='.EXT_MODULE.'&id='.$mid,
			$mod['enabled'] == '1',
			'images/bullet_green.png',
			'images/bullet_red.png'
		);
		$func .= System::admin()->SpeedButton('Настройки', ADMIN_FILE.'?exe=modules&a=mod_config&name='.SafeDB($mod['folder'], 255, str), 'images/admin/config.png');

		// Показываем кнопку удаления, только тогда, когда существует программа удаления
		if(is_file($mod_dir.$mod['folder'].'/uninstall.php')){
			$func .= System::admin()->SpeedConfirm(
				'Удалить',
				ADMIN_FILE.'?exe=modules&a=uninstall&type='.EXT_MODULE.'&name='.SafeDB($mod['folder'], 255, str),
				'images/admin/delete.png',
				'Полностью удалить модуль '.$mod['name'].'?'
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
			$description = 'Нет описания.';
		}

		$modules_html .= '<table width="100%" class="ex-mod">
		<tr onmousedown="ShowModInfo(\'mod'.$mid.'\');">
			<td style="padding-left: 11px; vertical-align: top;">
				<div style="float: left;">
					<div style="float:left; padding-top: 6px;"><img src="'.$icon.'"></div>
					<div style="float:left; padding-top: 7px;">&nbsp;'.$mod['name'].' (v'.$version.')</div>
				</div>
			</td>
			<td width="90" align="center" style="padding: 3px; padding-bottom: 2px;">
				<div style="float: left">'.$func.'</div>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<div class="mod_info" id="mod_info_mod'.$mid.'" style="display: none; padding: 4px; padding-left: 11px;">
					<div class="ex-mod-info-description">'.$description.'</div>
		      '.($author != '' ? '<div class="ex-mod-info">Автор: '.$author.'</div>' : '').'
		      '.($site != '' ? '<div class="ex-mod-info">Сайт: <a href="'.$site.'" target="_blank">'.$site.'</a></div>' : '').'
				</div>
			</td>
		</tr>
		</table>';
	}
	$modules_html .= '</div>';

	// Блоки
	$blocks_html = '<div style="border-top: 1px #ccf solid; ">';
	$mods = System::database()->Select('block_types');
	foreach($mods as $mod){
		$info = ExtLoadInfo($blocks_dir.$mod['folder']);
		if($info === false) continue;

		$mid = SafeEnv($mod['id'], 11, int);
		$func = '';
		if(is_file($blocks_dir.$mod['folder'].'/uninstall.php')){ // Показываем кнопку удаления, только тогда, когда существует программа удаления
			$func .= System::admin()->SpeedConfirm(
				'Удалить',
				ADMIN_FILE.'?exe=modules&a=uninstall&type='.EXT_BLOCK.'&name='.SafeDB($mod['folder'], 255, str),
				'images/admin/delete.png',
				'Полностью удалить модуль '.$mod['name'].'?'
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
			$description = 'Нет описания.';
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
		      '.($author != '' ? '<div class="ex-mod-info">Автор: '.$author.'</div>' : '').'
		      '.($site != '' ? '<div class="ex-mod-info">Сайт: <a href="'.$site.'" target="_blank">'.$site.'</a></div>' : '').'
				</div>
			</td>
		</tr>
		</table>';
	}
	$blocks_html .= '</div>';

	// Плагины
	$plugins_html = '<div style="border-top: 1px #ccf solid; ">';
	$mods = System::database()->Select('plugins');
	$configs_groups = PluginsConfigsGroups();
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
		if(isset($configs_groups[($mod['group'] != '' ? $mod['group'].'.' : '').$mod['name']])){
			$func .= System::admin()->SpeedButton('Настройки', ADMIN_FILE.'?exe=modules&a=plug_config&name='.SafeDB($mod['name'], 255, str).'&group='.SafeDB($mod['group'], 255, str), 'images/admin/config.png');
		}
		$func .= System::admin()->SpeedStatus(
			'Отключить', 'Подключить',
			ADMIN_FILE.'?exe=modules&a=changestatus&type='.EXT_PLUGIN.'&id='.$mid,
			$mod['enabled'] == '1',
			'images/bullet_green.png',
			'images/bullet_red.png'
		);
		// Показываем кнопку удаления, только тогда, когда существует программа удаления
		if(isset($info['1.3']) || is_file($path.'/uninstall.php')){
			$func .= System::admin()->SpeedConfirm(
				'Удалить',
				ADMIN_FILE.'?exe=modules&a=uninstall&type='.EXT_PLUGIN.'&name='.SafeDB($mod['name'], 255, str).($mod['group']!=''?'&group='.SafeDB($mod['group'], 255, str) : ''),
				'images/admin/delete.png',
				'Полностью удалить плагин '.$mod['name'].'?'
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
			$description = 'Нет описания.';
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
		      '.($author != '' ? '<div class="ex-mod-info">Автор: '.$author.'</div>' : '').'
		      '.($site != '' ? '<div class="ex-mod-info">Сайт: <a href="'.$site.'" target="_blank">'.$site.'</a></div>' : '').'
				</div>
			</td>
		</tr>
		</table>';
	}
	$plugins_html .= '</div>';

	// Шаблоны
	$templates_html = '<div style="border-top: 1px #ccf solid; ">';
	$mods = System::database()->Select('templates', "`default`='0'");
	foreach($mods as $mod){
		$info = ExtLoadInfo($tpl_dir.$mod['folder']);
		if($info === false) continue;

		$mid = SafeEnv($mod['id'], 11, int);
		$func = '';
		$func .= System::admin()->SpeedConfirm(
			'Удалить шаблон',
			ADMIN_FILE.'?exe=modules&a=uninstall&type='.EXT_TEMPLATE.'&name='.SafeDB($mod['folder'], 255, str),
			'images/admin/delete.png',
			'Удалить шаблон '.$info['name'].'?'
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
			$description = 'Нет описания.';
		}

		$templates_html .= '<table width="100%" class="ex-mod">
		<tr onmousedown="ShowBlockInfo(\'tpl'.$mid.'\');">
			<td style="padding-left: 11px; vertical-align: top;">
				<div style="float: left;">
					<div style="float:left; padding-top: 6px;"><img src="'.$icon.'"></div>
					<div style="float:left; padding-top: 7px;">&nbsp;'.$info['name'].($version != '' ?  ' (v'.$version.')' : '').($mod['admin'] == '1' ? ' (Админ-панель)' : '').'</div>
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
		      '.($author != '' ? '<div class="ex-mod-info">Автор: '.$author.'</div>' : '').'
		      '.($site != '' ? '<div class="ex-mod-info">Сайт: <a href="'.$site.'" target="_blank">'.$site.'</a></div>' : '').'
				</div>
			</td>
		</tr>
		</table>';
	}
	$templates_html .= '</div>';

	// Выводим расширения по вкладкам
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
		<li><a href="#tabs-1"><img src="images/widgets.png" style="vertical-align: bottom;">&nbsp;Модули</a></li>
		<li><a href="#tabs-2"><img src="images/block.png" style="vertical-align: bottom;">&nbsp;Блоки</a></li>
		<li><a href="#tabs-3"><img src="images/plugin.png" style="vertical-align: bottom;">&nbsp;Плагины</a></li>
		<li><a href="#tabs-4"><img src="images/skins.png" style="vertical-align: bottom;">&nbsp;Шаблоны</a></li>
	</ul>
	<div id="tabs-1">'.$modules_html.'</div>
	<div id="tabs-2">'.$blocks_html.'</div>
	<div id="tabs-3">'.$plugins_html.'</div>
	<div id="tabs-4">'.$templates_html.'</div>
</div>';

	System::admin()->AddTextBox('Расширения', $style.$html);
}

/*
 * Список расширений доступных для установки.
 */
function AdminModulesInstallList(){
	global $db, $config, $site;

	$mod_dir = $config['mod_dir'];
	$plug_dir = $config['plug_dir'];
	$block_dir = $config['blocks_dir'];
	$temp_dir = $config['tpl_dir'];
	$list = array();

	$title = 'Установка расширений';
	System::admin()->AddSubTitle($title);

	// Загружаем информацию об установленных модулях
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

	// Поиск модулей
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

	// Поиск плагинов
	$plug_folders = GetFolders($plug_dir);
	foreach($plug_folders as $folder){
		$info = false;
		if(is_file($plug_dir.$folder.'/info.php')){ // Возможно группа
			$info = ExtLoadInfo($plug_dir.$folder);
		}
		if(isset($info['1.3_old_plugins_group']) || $info === false){ // Группа
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
		}else{
			if(!in_array($folder, $installed_plugins)){
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
	}

	// Поиск блоков
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

	// Поиск шаблонов
	$temp_folders = GetFolders($temp_dir);
	foreach($temp_folders as $folder){
		if(!in_array($folder, $installed_templates)){
			$info = ExtLoadInfo($temp_dir.$folder);
			if($info !== false){
				$info['type'] = EXT_TEMPLATE;
				$info['path'] = $temp_dir.$folder.'/';
				$info['folder'] = $folder;
				$list[] = $info;
			}
		}
	}

	$count = count($list);
	$text = '<form action="'.ADMIN_FILE.'?exe=modules&a=install" method="post">';
	$text .= '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>Установка</th><th>Тип</th><th>Имя</th><th>Описание</th><th>Автор</th><th>Сайт</th></tr>';

	if(count($list) > 0){
		foreach($list as $i=>$ext){
			switch($ext['type']){
				case 1: $type = '<img src="images/widgets.png" title="Модуль">';
				break;
				case 2: $type = '<img src="images/plugin.png" title="Плагин">';
				break;
				case 3: $type = '<img src="images/block.png" title="Блок">';
				break;
				case 4: $type = '<img src="images/skins.png" title="Шаблон">';
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
		<td>'.(isset($ext['description']) && $ext['description'] != '' && $ext['description'] != ' - ' ? SafeDB($ext['description'], 255, str) : 'Нет описания').'</td>
		<td>'.(isset($ext['author']) && $ext['author'] != '' ? SafeDB($ext['author'], 255, str) : '&nbsp;').'</td>
		<td>'.(isset($ext['site']) && $ext['site'] != '' ? '<a href="'.$ext['site'].'" target="_blank">Перейти</a>' : 'Нет').'</td>
		</tr>';
		}
		$text .= '</table>';
		$text .= '<div style="margin-bottom: 25px;">'.$site->Hidden('count', $count).$site->Submit('Установить выделенные').'</div>';
		$text .= '</form>';
	}else{
		$text .= '<tr><td colspan="6" style="text-align: left;">Нет не установленных модулей.</td></tr>';
		$text .= '</table>';
		$text .= '</form>';
	}

	System::admin()->AddCenterBox($title.' ('.$count.' готово к установке)');
	System::admin()->AddText($text);

	if(System::user()->isSuperUser()){
		System::admin()->FormTitleRow('Загрузить новое');
		System::admin()->FormRow('', $site->FFile('extension'));
		System::admin()->AddForm(System::admin()->FormOpen(ADMIN_FILE.'?exe=modules&a=upload', 'post', true), System::admin()->Submit('Загрузить'));
	}
}

/*
 * Установка расширений.
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
					if(isset($info['1.3'])){ // Установка старой версии плагина
						ExtInstallPlugin($group, $folder, $info['function'], $info['type']);
						if(is_file($path.'/install.php')){
							require $path.'/install.php';
						}
						$new_installed[] = $info;
					}else{
						if(is_file($path.'/install.php')){  // Наличие install.php обязательно
							require $path.'/install.php';
							$new_installed[] = $info;
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
						// Добавляем пункт меню
						$folder = SafeEnv($folder, 255, str);
						$mod_name = SafeEnv($info['name'], 255, str);
						System::database()->Select('adminmenu', "`parent`='5'");
						$order = System::database()->NumRows();
						System::database()->Insert('adminmenu',"'','5','$order','$folder','$mod_name','images/application.png','exe=$folder','','0','','admin','1'");
					}
					$new_installed[] = $info;
				break;
				case EXT_TEMPLATE:
					// Установка тем оформления происходит автоматически
					$path = RealPath2(System::config('tpl_dir').$folder);
					$info = ExtLoadInfo($path);
					$admin = (isset($info['admin']) && $info['admin'] ? '1' : '0');
					ExtInstallTemplate($info['name'], $folder, $admin);
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
					<td style="width: 120px; padding: 5px;"><img src="images/admin/accept.png" style="vertical-align: middle;" /> Установлено</td>
				</tr>
			</table>';
		}
		$html .= '</div>'.System::admin()->Button('Назад', 'onclick="history.go(-1);"')
						 .System::admin()->Button('Далее', "onclick=\"Admin.LoadPage('".ADMIN_FILE."?exe=modules');\"");
		$cache = LmFileCache::Instance();
		$cache->Clear('config');
	}else{
		$html .= '<div style="padding:  5px;">Вы не выбрали расширения для установки. Нажмите "назад" и отметьте галочками расширения которые нужно установить.</div>';
		$html .= '</div>'.System::admin()->Button('Назад', 'onclick="history.go(-1);"');
	}
	System::admin()->AddTextBox('Установка расширений', $html);
}

/*
 * Загрузка и распаковка новых модулей.
 */
function AdminModulesUpload(){
	if(!System::user()->isSuperUser()){
		System::admin()->AddTextBox('Ошибка', 'Эта функция доступна только супер администраторам.');
		return;
	}
	if(!isset($_FILES['extension'])){
			System::admin()->AddTextBox('Ошибка', 'Файл не выбран.');
			return;
	}
	$extension = $_FILES['extension'];
	$file_ext = GetFileExt($extension['name']);
	$error_log = '';
	$to_unpack = array();

	if($extension['error'] != 0){
			System::admin()->AddTextBox('Ошибка', 'Ошибка при загрузке файла. Код ошибки: '.$extension['error'].'.');
			return;
		}
	if(strtolower($file_ext) == '.zip'){
		$path = '';
		$archive = $extension['tmp_name'];
		$zip = new ZipArchive;
		if($zip->open($archive) === true){
			for($i = 0; $i < $zip->numFiles; $i++){
				$filename = $zip->getNameIndex($i);
				$fileinfo = pathinfo($filename);
				if(!is_writable($path.$fileinfo['dirname'])){
					$errors_log .= 'Нет прав на запись в папку '.$path.$fileinfo['dirname']."\n";
					continue;
				}
				if(substr($path.$filename, -1) == '/'){
					if(!is_dir($path.$filename)){
						mkdir($path.$filename, 0777);
					}
				}else{
					$to_unpack[] = array('zip://'.$archive."#".$filename, $path.$filename);
				}
			}
			$zip->close();
		}else{
			$errors_log .= 'Не удалось прочитать файл. Неверный формат.';
		}
	}
	// Распаковываем архив
	if($errors_log != ''){
		System::admin()->AddTextBox('Ошибка, не удалось распаковать архив', nl2br($errors_log));
	}elseif(isset($_FILES['archive'])){
		// Распаковываем файлы
		foreach($to_unpack as $file){
			copy($file[0], $file[1]);
		}
		GO(ADMIN_FILE.'?exe=modules&a=installlist');
	}else{
		System::admin()->AddTextBox('Ошибка', 'Неверное расширение файла.');
	}
}

/*
 * Удаление расширения
 */
function AdminModulesUninstall(){
	global $db, $config, $user; // Для старых модулей
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
					if(isset($info['1.3'])){ // Удаляем пункт меню
						$folder = SafeEnv($folder, 255, str);
						System::database()->Delete('adminmenu', "`module`='$folder'");
					}
				}
				GO(ADMIN_FILE.'?exe=modules#tabs-1');
			}else{
				$folder = SafeEnv($folder, 255, str);
				System::database()->Select('modules', "`folder`='$folder'");
				if($db->NumRows() == 0){
					AddTextBox('Ошибка', 'Модуль не установлен.');
					return;
				}
				$mod = System::database()->FetchRow();
				$name = SafeDB($mod['name'], 255, str);
				$text = '';
				$text .= '<form method="post">';
				$text .= '<div style="padding: 10px 0 10px 25px;">';
				$text .= '<div style="padding-bottom: 10px">';
				$text .= '<label><input type="checkbox" name="delete_tables" checked>&nbsp;Удалить таблицы БД</label><br>';
				$text .= '<label><input type="checkbox" name="delete_files" checked>&nbsp;Удалить файлы модуля</label>';
				$text .= '</div>';
				$text .= System::admin()->Hidden('ok', '1');
				$text .= '<div>'.System::admin()->Button('Отмена', 'onclick="history.go(-1)"').System::admin()->Submit('Удалить').'</div>';
				$text .= '</div></form>';
				AddTextBox('Удаление модуля "'.$name.'"', $text);
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
					AddTextBox('Ошибка', 'Блок не установлен.');
					return;
				}
				$mod = System::database()->FetchRow();
				$name = SafeDB($mod['name'], 255, str);
				$text = '';
				$text .= '<form method="post">';
				$text .= '<div style="padding: 10px 0 10px 25px;">';
				$text .= '<div style="padding-bottom: 10px">';
				$text .= '<label><input type="checkbox" name="delete_tables" checked>&nbsp;Удалить таблицы БД</label><br>';
				$text .= '<label><input type="checkbox" name="delete_files" checked>&nbsp;Удалить файлы</label>';
				$text .= '</div>';
				$text .= System::admin()->Hidden('ok', '1');
				$text .= '<div>'.System::admin()->Button('Отмена', 'onclick="history.go(-1)"').System::admin()->Submit('Удалить').'</div>';
				$text .= '</div></form>';
				AddTextBox('Удаление модуля "'.$name.'"', $text);
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
				PluginsClearCache();
				GO(ADMIN_FILE.'?exe=modules#tabs-3');
			}else{
				$folder = SafeEnv($folder, 255, str);
				System::database()->Select('plugins', "`name`='$folder' and `group`='$groupenv'");
				if($db->NumRows() == 0){
					AddTextBox('Ошибка', 'Плагин не установлен.');
					return;
				}
				$mod = System::database()->FetchRow();
				$name = SafeDB($info['name'], 255, str);
				$text = '';
				$text .= '<form method="post">';
				$text .= '<div style="padding: 10px 0 10px 25px;">';
				$text .= '<div style="padding-bottom: 10px">';
				$text .= '<label><input type="checkbox" name="delete_tables" checked>&nbsp;Удалить таблицы БД</label><br>';
				$text .= '<label><input type="checkbox" name="delete_files" checked>&nbsp;Удалить файлы</label>';
				$text .= '</div>';
				$text .= System::admin()->Hidden('ok', '1');
				$text .= '<div>'.System::admin()->Button('Отмена', 'onclick="history.go(-1)"').System::admin()->Submit('Удалить').'</div>';
				$text .= '</div></form>';
				AddTextBox('Удаление модуля "'.$name.'"', $text);
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
				$text .= '<label><input type="checkbox" name="delete_files" checked>&nbsp;Удалить файлы</label>';
				$text .= '</div>';
				$text .= System::admin()->Hidden('ok', '1');
				$text .= '<div>'.System::admin()->Button('Отмена', 'onclick="history.go(-1)"').System::admin()->Submit('Удалить').'</div>';
				$text .= '</div></form>';
				AddTextBox('Удаление шаблона "'.$name.'"', $text);
			}
			break;
	}
}

/*
 * Обработка Ajax изменения статуса модуля
 */
function AdminModulesChangeStatus(){
	if($_GET['type'] == EXT_MODULE){
		$id = SafeEnv($_GET['id'], 11, int);
		System::database()->Select('modules', "`id`='$id'");
		$mod = System::database()->FetchRow();
		if($mod['enabled'] == 1){
			$en = '0';
		}else{
			$en = '1';
		}
		System::database()->Update('modules', "enabled='$en'", "`id`='$id'");
	}elseif($_GET['type'] == EXT_PLUGIN){
		$id = SafeEnv($_GET['id'], 11, int);
		System::database()->Select('plugins', "`id`='$id'");
		$mod = System::database()->FetchRow();
		if($mod['enabled'] == 1){
			$en = '0';
		}else{
			$en = '1';
		}
		System::database()->Update('plugins', "enabled='$en'", "`id`='$id'");
	}

	echo 'OK';
	exit();
}

/*
 * Страница общих настроек модуля
 */
function AdminModulesConfig(){
	System::admin()->AddSubTitle('Конфигурация модуля');
	$name = SafeEnv($_GET['name'], 255, str);
	System::database()->Select('modules', "`folder`='".$name."'");
	$mod = System::database()->FetchRow();

	System::admin()->FormRow('Имя', System::admin()->Edit('name', $mod['name'], false, 'style="width:200px;"'));
	$dir = System::config('tpl_dir').System::config('general/site_template').'/themes/';
	if(is_dir($dir)){
		$templates = GetFiles($dir, false, true, ".html");
	}else{
		$templates = array();
	}
	System::admin()->DataAdd($templates_data, '', 'Стандартный "theme.html"', $mod['theme'] == '');
	foreach($templates as $template){
		System::admin()->DataAdd($templates_data, $template, $template, $mod['theme'] == $template);
	}
	System::admin()->FormRow('Шаблон страницы', System::admin()->Select('theme', $templates_data));

	System::admin()->FormRow('Кто видит', System::admin()->Select('view', GetUserTypesFormData(SafeDB($mod['view'], 1, int))));
	System::admin()->FormRow('Включить', System::admin()->Select('enabled', GetEnData((bool)$mod['enabled'], 'Да', 'Нет')));

	System::admin()->AddCenterBox('Настройка модуля "'.SafeDB($mod['name'], 255, str).'"');
	System::admin()->AddForm(
		System::admin()->FormOpen(ADMIN_FILE.'?exe=modules&a=mod_configsave&name='.SafeDB($mod['folder'], 255, str)),
		System::admin()->Button('Отмена', 'onclick="history.go(-1)"')
		.System::admin()->Submit('Сохранить')
	);
}

/*
 * Сохранение настроек модуля
 */
function AdminModulesConfigSave(){
	$post = SafeR('view', 11,int)
	        +SafeR('name, theme', 255, str)
	        +SafeR('enabled', 3, onoff);
	System::database()->Update('modules', MakeSet($post), "`folder`='".SafeEnv($_GET['name'], 255, str)."'");
	GO(ADMIN_FILE.'?exe=modules');
}
