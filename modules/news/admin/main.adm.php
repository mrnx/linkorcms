<?php

	if(!defined('VALID_RUN')){
		header("HTTP/1.1 404 Not Found");
		exit;
	}

	TAddSubTitle('Главная');
	AddCenterBox('Новости');

	$num = $config['news']['newsonpage']; //Количество новостей на страницу

	$news = $db->Select('news');
	SortArray($news, 'date', true);

	// Выводим новости
	UseScript('jquery_ui_table');

	$text = '';
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>Заголовок</th><th>Дата</th><th>Просмотров</th><th>Комментарии</th><th>Кто видит</th><th>Статус</th><th>Функции</th></tr>';
	foreach($news as $s){
		$text .= AdminRenderNews2($s, false, $page, $topics[$s['topic_id']]);
	}
	$text .= '</table>';

	AddText($text);

?>