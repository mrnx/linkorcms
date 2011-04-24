<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(!$user->CheckAccess2('news', 'edit_topics')){
	AddTextBox('������', '������ ��������!');
	return;
}

$id = SafeEnv($_GET['id'], 11, int);
$db->Select('news_topics', "`id`='".$id."'");
$topic = $db->FetchRow();

FormRow('�������� �������', $site->Edit('topic_name', SafeDB($topic['title'], 255, str), false, 'maxlength="255" style="width:400px;"'));
FormTextRow('�������� (HTML)', $site->HtmlEditor('topic_description', SafeDB($topic['description'], 255, str), 600, 200));
AdminImageControl('�����������', '��������� �����������', RealPath2(SafeDB($topic['image'], 255, str)), RealPath2(SafeDB($config['news']['icons_dirs'], 255, str)), 'topic_image', 'up_photo', 'topicsform');
AddCenterBox('�������������� �������');
AddForm(
	'<form name="topicsform" action="'.$config['admin_file'].'?exe=news&a=savetopic&id='.$id.'" method="post" enctype="multipart/form-data">',
	$site->Button('������', 'onclick="history.go(-1);"').$site->Submit('���������')
);

?>