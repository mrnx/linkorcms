<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(!$user->CheckAccess2('news', 'edit_topics')){
	AddTextBox('Ошибка', 'Доступ запрещён!');
	return;
}

$site->AddCSSFile('news.css');
$topics = $db->Select('news_topics');
$text = '<table cellspasing="0" cellpadding="0" border="1" class="topics_view">';
$cnt = $db->NumRows();
$cntr = 0;
for($i = 0; $i < $cnt; $i++){
	if($cntr % 4 == 0){
		$text .= '<tr>';
	}
	$text .=
	'<td valign="top" align="center">'
	. SafeDB($topics[$i]['title'], 255, str).' ('. SafeDB($topics[$i]['counter'], 11, int).')'
	.(is_file($config['news']['icons_dirs'].SafeDB($topics[$i]['image'], 255, str)) ? '<br /><img src="'.$config['news']['icons_dirs'].SafeDB($topics[$i]['image'], 255, str).'" width="80" height="80" title="'.SafeDB($topics[$i]['description'], 255, str).'" />' : '')
	.'<br />(<a href="'.$config['admin_file'].'?exe=news&a=edittopic&id='.SafeDB($topics[$i]['id'], 11, int).'">редактировать</a> : <a href="'.$config['admin_file'].'?exe=news&a=deltopic&id='.SafeDB($topics[$i]['id'], 11, int).'">удалить</a>)'
	.'</td>';
	$cntr++;
	if($cntr % 4 == 0){
		$text .= '</tr>';
	}
}
if($cntr % 4 != 0){
	$text .= '</tr>';
}

$text .= '</table>';
$text .= '<br />.:Создать новый раздел:.<br />';

FormRow('Название раздела', $site->Edit('topic_name', '', false, 'maxlength="255" style="width:400px;"'));
FormTextRow('Описание (HTML)', $site->HtmlEditor('topic_description', '', 600, 200));
AdminImageControl('Изображение', 'Загрузить изображение', '', $config['news']['icons_dirs'], 'topic_image', 'up_photo', 'topicsform');
AddCenterBox('Текущие новостные разделы');
AddText($text);
AddForm('<form name="topicsform" action="'.$config['admin_file'].'?exe=news&a=addtopic" method="post" enctype="multipart/form-data">', $site->Submit('Создать'));

?>