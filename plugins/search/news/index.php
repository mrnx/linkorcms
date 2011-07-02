<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

global $search_results, $searchstr, $db;

$where = "`enabled`='1'";
$ex_where = GetWhereByAccess('view');
if($ex_where != ''){
	$where .= ' and ('.$ex_where.')';
}
$news_array = $db->Select('news', $where);

foreach($news_array as $news){
	$result = array();
	$result['mod'] = $plugin['mod_title']; // ��� ������
	$result['coincidence'] = ''; // ���������� ��� ���� ����������
	$result['title'] = SafeDB($news['title'], 255, str); // ���� ����������
	$result['public'] = TimeRender(SafeDB($news['date'], 11, int));
	$result['link'] = Ufu('index.php?name=news&op=readfull&news='.SafeDB($news['id'], 11, int).'&topic='.SafeDB($news['topic_id'], 11, int), 'news/{topic}/{news}/');
	$result['text'] = SafeDB($news['start_text'], 0, str);
	if($news['auto_br'] == '1'){
		$result['text'] = SafeDB(nl2br($result['text']), 0, str, false, false);
	}else{
		$result['text'] = SafeDB($result['text'], 0, str, false, false);
	}
	if(strlen($result['text']) > 255){
		$result['text'] = substr($result['text'], 0, 255).'&nbsp; ...';
	}
	if(SSearch($news['seo_keywords'], $searchstr) != false){
		$result['coincidence'] = '�������� ����� �������';
		$search_results[] = $result;
	}elseif(SSearch($news['seo_description'], $searchstr) != false){
		$result['coincidence'] = '�������� �������';
		$search_results[] = $result;
	}elseif(SSearch($news['title'], $searchstr) != false){
		$result['coincidence'] = '��������� �������';
		$search_results[] = $result;
	}elseif(SSearch($news['start_text'], $searchstr) != false){
		$result['text'] = SCoincidence($news['start_text'], $searchstr);
		$result['coincidence'] = '������� ����� �������';
		$search_results[] = $result;
	}elseif(SSearch($news['end_text'], $searchstr) != false){
		$result['text'] = SCoincidence($news['end_text'], $searchstr);
		$result['coincidence'] = '������ ����� �������';
		$search_results[] = $result;
	}
}

?>