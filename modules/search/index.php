<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$search_results = array();
$searchstr = '';
$site->Title = '����� �� �����';

function IndexSearchMain()
{
	global $site;
	if(isset($_GET['mod'])){
		$mods = $_GET['mod'];
		$all = false;
	}else{
		$mods = array();
		$all = true;
	}
	$vars = array();
	$site->AddTemplatedBox('����� �� �����', 'module/search_form.html');
	$site->AddBlock('search_form', true, false, 'form');
	$vars['form_name'] = 'search_form';
	$vars['url'] = 'index.php';
	$vars['lsubmit'] = '�����';
	$vars['searchstr'] = (isset($_GET['searchstr']) ? SafeEnv($_GET['searchstr'], 255, str) : '');
	$site->Blocks['search_form']['vars'] = $vars;
	$site->AddBlock('search_modules', true, true, 'mod');
	$plugins = LoadPlugins();
	$plugins = $plugins['groups']['search']['plugins'];
	foreach($plugins as $mod){
		$vars = array();
		$vars['title'] = $mod['mod_title'];
		$vars['name'] = $mod['function'];
		$vars['checked'] = in_array($vars['name'], $mods) || $all;
		if($all){
			$mods[] = $vars['name'];
		}
		$site->AddSubBlock('search_modules', true, $vars);
	}
	return $mods;
}

function IndexSearchSearch( $mods, $search_text )
{
	global $search_results;
	$search_results = array();
	foreach($mods as $mod){
		$plugins = IncludeSystemPluginsGroup('search', $mod, true, true);
		foreach($plugins as $plugin){
			include($plugin['path'].'index.php');
		}
	}
	return $search_results;
}

function IndexSearchResults()
{
	global $site, $searchstr;
	$mods = IndexSearchMain();
	$mods_str = '';
	foreach($mods as $i=>$mod){
		$mods[$i] = SafeEnv($mod, 255, str);
		$mods_str .= '&mod[]='.$mods[$i];
	}
	if(isset($_GET['searchstr'])){
		$searchstr = SafeEnv($_GET['searchstr'], 255, str);
	}else{
		$site->AddTextBox('', '<center>�� ������ ������� ������ �� �������.</center>');
		return;
	}
	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 10, int);
	}else{
		$page = 1;
	}
	$results = IndexSearchSearch($mods, $searchstr);
	SortArray($results, 'public', true);
	if(count($results) > 0){
		$num = 10; //���������� ����������� �� ��������
		$navigation = new Navigation($page);
		$navigation->GenNavigationMenu($results, $num, 'index.php?name=search&op=search&searchstr='.$searchstr.$mods_str);
		$site->AddTemplatedBox('���������� ������', 'module/search_results.html');
		$site->AddBlock('search_results', true, true, 'result');
		foreach($results as $i=>$result){
			$result['no'] = $i+1;
			$site->AddSubBlock('search_results', true, $result);
		}
	}else{
		$site->AddTextBox('', '<center>�� ������ ������� ������ �� �������.</center>');
	}
}

if(isset($_GET['op'])){
	$op = $_GET['op'];
}else{
	$op = 'main';
}

switch($op){
	case 'main':
		IndexSearchMain();
		break;
	case 'search':
		IndexSearchResults();
		break;
	default:
		HackOff();
}

?>