<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

global $search_results, $searchstr, $db;

$where = "`show`='1'";
$ex_where = GetWhereByAccess('view');
if($ex_where != ''){
	$where .= ' and ('.$ex_where.')';
}
$objects = $db->Select('gallery', $where);

foreach($objects as $object){
	$result = array();
	$result['mod'] = $plugin['mod_title']; // Имя модуля
	$result['coincidence'] = ''; // Показывает где было совпадение
	$result['title'] = SafeDB($object['title'], 255, str); // Заголовок
	$result['public'] = TimeRender(SafeDB($object['public'], 11, int)); // Дата публикации
	// Ссылка на просмотр обьекта
	$result['link'] = Ufu('index.php?name=gallery&op=view&img='.SafeDB($object['id'], 11, int).'&cat='.SafeDB($object['cat_id'], 11, int), 'gallery/{cat}/{img}/');
	$result['text'] = SafeDB($object['description'], 0, str);
	if(strlen($result['text']) > 255){
		$result['text'] = substr($result['text'], 0, 255).'&nbsp; ...';
	}
	if(SSearch($object['title'], $searchstr) !== false){
		$result['coincidence'] = 'Заголовок изображения';
		$search_results[] = $result;
	}elseif(SSearch($object['description'], $searchstr) !== false){
		$result['text'] = SCoincidence($object['description'], $searchstr);
		$result['coincidence'] = 'Описание изображения';
		$search_results[] = $result;
	}
}

?>