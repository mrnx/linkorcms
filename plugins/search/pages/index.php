<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

global $search_results, $searchstr, $db;

$where = "`enabled`='1' and `type`='page'";
$ex_where = GetWhereByAccess('view');
if($ex_where != ''){
	$where .= ' and ('.$ex_where.')';
}
$objects = $db->Select('pages', $where);

foreach($objects as $object){
	$result = array();
	$result['mod'] = $plugin['mod_title']; // ��� ������
	$result['coincidence'] = ''; // ���������� ��� ���� ����������
	$result['title'] = SafeDB($object['title'], 255, str); // ���������
	$result['public'] = TimeRender(SafeDB($object['modified'], 11, int)); // ���� ����������
	// ������ �� �������� �������
	$result['link'] = Ufu('index.php?name=pages&file='.SafeDB($object['link'], 255, str), 'pages/{file}.html');
	$result['text'] = SafeDB($object['text'], 0, str);
	if(strlen($result['text']) > 255){
		$result['text'] = substr($result['text'], 0, 255).'&nbsp; ...';
	}
	if(SSearch($object['seo_keywords'], $searchstr) !== false){
		$result['coincidence'] = '�������� ����� ��������';
		$search_results[] = $result;
	}elseif(SSearch($object['seo_description'], $searchstr) !== false){
		$result['coincidence'] = '�������� ��������';
		$search_results[] = $result;
	}elseif(SSearch($object['title'], $searchstr) !== false){
		$result['coincidence'] = '��������� ��������';
		$search_results[] = $result;
	}elseif(SSearch($object['text'], $searchstr) !== false){
		$result['text'] = SCoincidence($object['text'], $searchstr);
		$result['coincidence'] = '����� ��������';
		$search_results[] = $result;
	}
}

?>