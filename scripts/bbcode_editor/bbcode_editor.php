<?php

# LinkorCMS
# � 2006-2010 �������� ��������� ���������� (linkorcms@yandex.ru)
# LinkorCMS Development Group
# www.linkorcms.ru
# �������� LinkorCMS 1.3

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

// ���������� css ��������� bb-����
$bb_cssfile = $site->Root.'/style/bbcode.css';
if(file_exists($bb_cssfile)){
	$site->AddCSSFile('bbcode.css');
}else{
	$site->AddCSSFile('scripts/bbcode_editor/style.css', true);
}
$site->AddJSFile('scripts/bbcode_editor/bbcode_editor.js', true, false);

?>