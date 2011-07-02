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
$objects = $db->Select('downloads', "`active`='1'");

foreach($objects as $object){
	$result = array();
	$result['mod'] = $plugin['mod_title']; //'Архив файлов';  // Имя модуля
	$result['coincidence'] = ''; // Показывает где было совпадение
	$result['title'] = SafeDB($object['title'], 255, str); // Дата публикации
	$result['public'] = TimeRender(SafeDB($object['public'], 11, int));
	$result['link'] = Ufu('index.php?name=downloads&op=full&cat='.SafeDB($object['category'], 11, int).'&file='.SafeDB($object['id'], 11, int), 'downloads/{cat}/{file}/');
	$result['text'] = SafeDB($object['shortdesc'], 0, str);
	if(strlen($result['text']) > 255){
		$result['text'] = substr($result['text'], 0, 255).'&nbsp; ...';
	}
	if(SSearch($object['title'], $searchstr) !== false){
		$result['coincidence'] = 'Заголовок файла';
		$search_results[] = $result;
	}elseif(SSearch($object['shortdesc'], $searchstr) !== false){
		$result['text'] = SCoincidence($object['shortdesc'], $searchstr);
		$result['coincidence'] = 'Краткое описание файла';
		$search_results[] = $result;
	}elseif(SSearch($object['description'], $searchstr) !== false){
		$result['text'] = SCoincidence($object['description'], $searchstr);
		$result['coincidence'] = 'Полное описание файла';
		$search_results[] = $result;
	}
}

?>