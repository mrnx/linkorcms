<?php

	if(!defined('VALID_RUN')){
		header("HTTP/1.1 404 Not Found");
		exit;
	}

	TAddSubTitle('�������');
	AddCenterBox('�������');

	$num = $config['news']['newsonpage']; //���������� �������� �� ��������

	$news = $db->Select('news');
	SortArray($news, 'date', true);

	// ������� �������
	UseScript('jquery_ui_table');

	$text = '';
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>���������</th><th>����</th><th>����������</th><th>�����������</th><th>��� �����</th><th>������</th><th>�������</th></tr>';
	foreach($news as $s){
		$text .= AdminRenderNews2($s, false, $page, $topics[$s['topic_id']]);
	}
	$text .= '</table>';

	AddText($text);

?>