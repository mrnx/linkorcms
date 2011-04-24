<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('�������');
$site->AddCSSFile('news.css');
$num = $config['news']['newsonpage']; //���������� �������� �� ��������
AddCenterBox('�������');

#��������� ������
$status = 0;
$topic_id = -1;
$auth_id = -1;
$menuurl = GenMenuUrl($status, $topic_id, $auth_id);
$site->DataAdd($status_data, '0', '���', ($status == 0));
$site->DataAdd($status_data, '1', '��������', ($status == 1));
$site->DataAdd($status_data, '2', '�����������', ($status == 2));


$db->Select('news_topics', '');
$site->DataAdd($topics_data, '-1', '���', ($topic_id == -1));
while($topic = $db->FetchRow()){
	$topics[SafeDB($topic['id'], 11, int)] = SafeDB($topic['title'], 255, str);
	$site->DataAdd($topics_data, SafeDB($topic['id'], 11, int), SafeDB($topic['title'], 255, str).'('.SafeDB($topic['counter'], 255, str).')', (SafeDB($topic['id'], 11, int) == $topic_id));
}

#�������� ������� ������� ����� ��������� �������
$db->Select('usertypes', '');
while($ut = $db->FetchRow()){
	if($ut['access'] == 'ALL'){
		$u_types[] = $ut;
	}else{
		$acc = unserialize($ut['access']);
		if(isset($acc['news']) && in_array('news_edit', $acc['news'])){
			$u_types[] = $ut;
		}
	}
}

$where = '';
for($i = 0, $cnt = count($u_types); $i < $cnt; $i++){
	$where .= " or `access`='".SafeDB($u_types[$i]['id'], 11, int)."'";
}
$where = substr($where, 4);

$db->Select('users', $where);
$site->DataAdd($authors_data, '-1', '���', ($topic_id == -1));
$authors = array();
while($us = $db->FetchRow()){
	$authors[SafeDB($us['id'], 11, int)] = SafeDB($us['name'], 50, str);
	$site->DataAdd($authors_data, SafeDB($us['id'], 11, int), SafeDB($us['name'], 255, str), (SafeDB($us['id'], 11, str) == $auth_id));
}

$text = '<form method="GET">'.$site->Hidden('exe', 'news');
$text .= '<table cellspacing="0" cellpadding="0" border="0" class="contenttd" width="100%"><tr>';
$text .= '<th valign="top">������:</th>';
$text .= '<td valig="top" align="center">������: '.$site->Select('status', $status_data).'&nbsp;&nbsp;����: '.$site->Select('topic_id', $topics_data).'&nbsp;&nbsp;�����: '.$site->Select('auth_id', $authors_data).'</td>';
$text .= '<td>'.$site->Submit('��������').'</td></tr></table></form>';
AddText($text);

#���������� ������ �������
$where = '';
if($auth_id != -1){
	$where .= " and `author`='".$authors[$auth_id]."'";
}
if($topic_id != -1){
	$where .= " and `topic_id`='".$topic_id."'";
}
switch($status){
	case 1:
		$where .= " and `enabled`='1'";
		break;
	case 2:
		$where .= " and `enabled`='0'";
		break;
}
$where = substr($where, 5);
$news = $db->Select('news', $where); // ����������� ������� �� ����
SortArray($news, 'date', true); // ��������� �������
#
# ������������ ���������
if(count($news) > $num){
	$navigator = new Navigation($page);
	$navigator->GenNavigationMenu($news, $num, $config['admin_file'].'?exe=news'.$menuurl);
	AddNavigation();
	$nav = true;
}else{
	$nav = false;
	AddText('<br />');
}
#
#������� �������
$text = '';
if($config['news']['view_mode'] == 'advanced'){
	foreach($news as $s){
		$text .= AdminRenderNews($s, false, $page, $topics[$s['topic_id']], $menuurl);
	}
}else{
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>���������</th><th>����</th><th>����������</th><th>�����������</th><th>��� �����</th><th>������</th><th>�������</th></tr>';
	foreach($news as $s){
		$text .= AdminRenderNews2($s, false, $page, $topics[$s['topic_id']], $menuurl);
	}
	$text .= '</table>';
}
#
AddText($text);
if($nav){
	AddNavigation();
}

?>