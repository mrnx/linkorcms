<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$title = 'Конфигурация новостного блока';

$topic_id = 0;
$count = 10;
$template = 'standart.html';

if($a == 'edit'){
	$bconf = unserialize($block_config);
	$topic_id = SafeDB($bconf['topic'], 11, int);
	$count = SafeDB($bconf['count'], 11, int);
}

$db->Select('news_topics', '');
$topicdata = array();
$site->DataAdd($topicdata, '0', 'Все разделы', ($topic_id == 0));
while($topic = $db->FetchRow()){
	$site->DataAdd($topicdata, $topic['id'], $topic['title'], ($topic['id'] == $topic_id));
}

FormRow('Раздел новостей', $site->Select('topic', $topicdata, false, ''));
FormRow('Количество новостей', $site->Edit('count', $count, false, 'style="width: 40px;" maxlength="11"'));

?>