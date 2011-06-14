<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('Плагины');

if(!$user->CheckAccess2('plugins', 'plugins')){
	AddTextBox('Ошибка', $config['general']['admin_accd']);
	return;
}

function AdminPluginsPluginType( $type )
{
	switch($type){
		case PLUG_AUTORUN:
			return 'Автозапуск';
			break;
		case PLUG_ADMIN_AUTORUN:
			return 'Автозапуск (Админпанель)';
			break;
		case PLUG_MAIN_AUTORUN:
			return 'Автозапуск (Сайт)';
			break;
		case PLUG_CALLEE:
			return 'Вызываемый';
			break;
		case PLUG_MANUAL:
			return 'Подключаемый';
			break;
		case PLUG_MANUAL_ONE:
			return 'Подключаемый';
			break;
		default:
			return 'Другой';
	}
}

function AdminPluginsRender( $name, $installed, $configex, $group = '' )
{
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
		$image .= 'Версия: '.SafeDB($plugins[$name]['version'], 255, str).'<br />';
	}
	if(isset($plugins[$name]['type'])){
		$text .= '<b>Тип:</b> '.AdminPluginsPluginType(SafeDB($plugins[$name]['type'], 2, int)).'<br />';
	}
	if($group != ''){
		$text .= '<b>Группа:</b> '.SafeDB($group, 250, str).'<br />';
	}
	if(isset($plugins[$name]['description-ru'])){
		$text .= '<b>Описание:</b> '.SafeDB($plugins[$name]['description-ru'], 255, str).'<br />';
	}
	if(isset($plugins[$name]['cms'])){
		$text .= '<b>LinkorCMS:</b> '.SafeDB($plugins[$name]['cms'], 11, str).'<br />';
	}
	if(isset($plugins[$name]['site'])){
		$text .= '<b>Сайт:</b> <a href="'.SafeDB($plugins[$name]['site'], 255, str).'" target="_blank">'.SafeDB($plugins[$name]['site'], 255, str).'</a><br />';
	}
	if($installed){
		$url = '<a href="'.$config['admin_file'].'?exe=plugins&a=uninstall'.($group != '' ? '&group='.$group : '').'&name='.SafeDB($name, 255, str).'">Отключить</a>';
		if($configex){
			$config_url = '<a href="'.$config['admin_file'].'?exe=plugins&a=config'.($group != '' ? '&group='.$group : '').'&name='.SafeDB($name, 255, str).'">Настройки</a>';
		}
	}else{
		$url = '<a href="'.$config['admin_file'].'?exe=plugins&a=install'.($group != '' ? '&group='.$group : '').'&name='.SafeDB($name, 255, str).'">Подключить</a>';
	}
	$options = '<b>Опции:</b> ['.$url.']'.(isset($config_url) ? ' ['.$config_url.']' : '');
	return '<tr><td id="image">'.$image.'</td><td id="text" valign="top">'.$text.$options.'</td></tr>';
}

function AdminPluginsMain()
{
	global $config, $db, $site;
	TAddSubTitle('Все плагины');

	$plugins = GetPlugins(true);
	$site->AddCSSFile('plugins.css');
	$text = '<table cellspacing="0" cellpadding="0" class="pluginstable">';
	$text .= '<tr><th>Название</th><th>Описание и функции</th></tr>';
	$configs_groups = PluginsConfigsGroups();
	foreach($plugins['plugins'] as $pl){
		$text .= AdminPluginsRender($pl['name'], $pl['installed'], isset($configs_groups[($pl['group'] != '' ? $pl['group'].'.' : '').$pl['name']]));
	}
	foreach($plugins['groups'] as $group){
		foreach($group['plugins'] as $pl){
			$text .= AdminPluginsRender($pl['name'], $pl['installed'], isset($configs_groups[($pl['group'] != '' ? $pl['group'].'.' : '').$pl['name']]), $pl['group']);
		}
	}
	$text .= '</table>';
	AddTextBox('Плагины', $text);
}

function AdminPluginsInstall()
{
	global $config, $db;
	if(isset($_GET['group'])){
		$group = SafeEnv($_GET['group'], 250, str);
	}else{
		$group = '';
	}
	if(isset($_GET['name'])){
		InstallPlugin(SafeEnv($_GET['name'], 255, str), $group);
	}
	GO($config['admin_file'].'?exe=plugins');
}

function AdminPluginsUninstall()
{
	global $config, $db;
	if(isset($_GET['group'])){
		$group = SafeEnv($_GET['group'], 250, str);
	}else{
		$group = '';
	}
	if(isset($_GET['name'])){
		UninstallPlugin(SafeEnv($_GET['name'], 255, str), $group);
	}
	GO($config['admin_file'].'?exe=plugins');
}

if(isset($_GET['a'])){
	$action = $_GET['a'];
}else{
	$action = 'main';
}

include_once ($config['inc_dir'].'configuration/functions.php');
$conf_config_table = 'plugins_config';
$conf_config_groups_table = 'plugins_config_groups';

function AdminPlugins()
{
	global $action, $site;
	$site->AddCSSFile('plugins.css');
	switch($action){
		case 'main':
			AdminPluginsMain();
			break;
		case 'install':
			AdminPluginsInstall();
			break;
		case 'uninstall':
			AdminPluginsUninstall();
			break;
		case 'config':
			if(isset($_GET['name'])){
				$group = SafeEnv((isset($_GET['group']) ? $_GET['group'].'.' : '').$_GET['name'], 255, str);
				$url = (isset($_GET['group']) ? '&group='.SafeEnv($_GET['group'], 255, str) : '').'&name='.SafeEnv($_GET['name'], 255, str);
				AdminConfigurationEdit('plugins'.$url, $group, false, false, 'Конфигурация плагина');
			}
			break;
		case 'configsave':
			if(isset($_GET['name'])){
				$group = SafeEnv((isset($_GET['group']) ? $_GET['group'].'.' : '').$_GET['name'], 255, str);
				$url = (isset($_GET['group']) ? '&group='.SafeEnv($_GET['group'], 255, str) : '').'&name='.SafeEnv($_GET['name'], 255, str);
				AdminConfigurationSave('plugins&a=config'.$url, $group, false);
			}
			break;
		default:
			AdminPluginsMain();
	}
}

AdminPlugins();

?>