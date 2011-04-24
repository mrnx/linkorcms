<?php

// Блок Топ Новостей
// LinkorCMS Development Group
if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$vars['title'] = $title;

$bcache = LmFileCache::Instance();
$bcache_name = 'news'.$user->AccessLevel();
if($bcache->HasCache('block', $bcache_name)){
	$news = $bcache->Get('block', $bcache_name);
	$count = count($news);
}else{
	$block_config = unserialize($block_config);
	$topic = SafeDB($block_config['topic'], 11, int); // Тема новостей
	$max_news = SafeDB($block_config['count'], 11, int); // Количество новостей в блоке
	if($topic != 0){
		$where = "`enabled`='1' and `topic_id`='$topic'";
	}else{
		$where = "`enabled`='1'";
	}
	$ex_where = GetWhereByAccess('view');
	if($ex_where != ''){
		$where .= ' and ('.$ex_where.')';
	}
	$news = $db->Select('news', $where);
	$count = count($news);
	SortArray($news, 'date', true);
	if($count > $max_news){
		$news = array_chunk($news, $max_news);
		$news = $news[0];
		$count = $max_news;
	}
	$bcache->Write('block', $bcache_name, $news);
}

if($count == 0){
	$en = false;
}else{
	$en = true;
}
$tempvars['content'] = 'block/content/news.html';
$site->AddBlock('no_news', !$en);
$site->AddBlock('block_news', $en);
$site->AddBlock('block_news_news', true, true, 'news');

$news_vars = array();
foreach($news as $new){
	$news_vars['title'] = SafeDB($new['title'], 255, str);
	$news_vars['url'] = Ufu('index.php?name=news&op=readfull&news='.SafeDB($new['id'], 11, int).'&topic='.SafeDB($new['topic_id'], 11, int), 'news/{topic}/{news}/');
	$news_vars['text'] = SafeDB($new['start_text'], 255, str, true, false);
	$news_vars['date'] = TimeRender($new['date']);
	$site->AddSubBlock('block_news_news', true, $news_vars);
}

?>