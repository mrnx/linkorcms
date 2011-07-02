<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

global $search_results, $searchstr, $db;

$where = "`active`='1'";
$ex_where = GetWhereByAccess('view');
if($ex_where != ''){
	$where .= ' and ('.$ex_where.')';
}
$objects = $db->Select('articles', $where);

foreach($objects as $object){
	$result = array();
	$result['mod'] = $plugin['mod_title']; // Имя модуля
	$result['coincidence'] = ''; // Показывает где было совпадение
	$result['title'] = SafeDB($object['title'], 255, str); // Дата публикации
	$result['public'] = TimeRender(SafeDB($object['public'], 11, int));
	$result['link'] = Ufu('index.php?name=articles&op=read&art='.SafeDB($object['id'], 11, int).'&cat='.SafeDB($object['cat_id'], 11, int), 'articles/{cat}/{art}/');
	$result['text'] = SafeDB($object['description'], 0, str);
	if(strlen($result['text']) > 255){
		$result['text'] = substr($result['text'], 0, 255).'&nbsp; ...';
	}
	if(SSearch($object['seo_keywords'], $searchstr) != false){
		$result['coincidence'] = 'Ключевые слова статьи';
		$search_results[] = $result;
	}elseif(SSearch($object['seo_description'], $searchstr) != false){
		$result['coincidence'] = 'Описание статьи';
		$search_results[] = $result;
	}elseif(SSearch($object['title'], $searchstr) != false){
		$result['coincidence'] = 'Заголовок статьи';
		$search_results[] = $result;
	}elseif(SSearch($object['description'], $searchstr) != false){
		$result['text'] = SCoincidence($object['description'], $searchstr);
		$result['coincidence'] = 'Вводный текст статьи';
		$search_results[] = $result;
	}elseif(SSearch($object['article'], $searchstr) != false){
		$result['text'] = SCoincidence($object['article'], $searchstr);
		$result['coincidence'] = 'Полный текст статьи';
		$search_results[] = $result;
	}
}

?>