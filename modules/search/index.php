<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$search_results = array();
$searchstr = '';
$site->Title = '����� �� �����';

function IndexSearchMain(){
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

	$plugins = PluginsGetInfo('search');
	foreach($plugins as $mod){
		$vars = array();
		$vars['title'] = $mod['name'];
		$vars['name'] = $mod['folder'];
		$vars['checked'] = in_array($vars['folder'], $mods) || $all;
		if($all){
			$mods[] = $vars['folder'];
		}
		$site->AddSubBlock('search_modules', true, $vars);
	}
	return $mods;
}

function IndexSearchSearch( $mods, $search_text ){
	global $search_results;
	$search_results = array();
	foreach($mods as $mod){
		$plugins = IncludePluginsGroup('search', $mod, true);
		foreach($plugins as $plugin){
			include($plugin.'index.php');
		}
	}
	return $search_results;
}

// ������� ��� ���������� ����������� ������
function IndexSearchSortResults($a, $b){
	global $searchstr;
	return stripos($a['title'], $searchstr) !== false ? -1 : 1;
}

function IndexSearchResults(){
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

	// ��������� ���������� ������
	usort($results, 'IndexSearchSortResults');

	if(count($results) > 0){
		$num = 10; //���������� ����������� �� ��������
		$si = ($num * ($page-1));
		$navigation = new Navigation($page);
		$navigation->GenNavigationMenu($results, $num, 'index.php?name=search&op=search&searchstr='.$searchstr.$mods_str);
		$site->AddTemplatedBox('���������� ������', 'module/search_results.html');
		$site->AddBlock('search_results', true, true, 'result');
		foreach($results as $i=>$result){
			$result['no'] = $si+$i+1;
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
