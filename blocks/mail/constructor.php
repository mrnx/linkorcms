<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$topic_id = 0;

if($a == 'edit'){
	$topic_id = SafeDB($block_config, 11, int);
}

$db->Select('mail_topics', '');
$topicdata = array();

while($topic = $db->FetchRow()){
	$site->DataAdd($topicdata, $topic['id'], $topic['title'], ($topic['id'] == $topic_id));
}

FormRow('Тема рассылки', $site->Select('topic', $topicdata, false, ''));
$title = 'Конфигурация блока рассылки';

?>