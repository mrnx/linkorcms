<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

//����� ����� ��������������� ������� ����� ��������� � ���������� ������

$vars['title'] = SafeDB($title, 255, str);
$vars['content'] = SafeDB($block_config, 0, str, false, false);

?>