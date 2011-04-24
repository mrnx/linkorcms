<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(!$user->CheckAccess2('news', 'edit_topics')){
	AddTextBox('������', '������ ��������!');
	return;
}

if(isset($_GET['ok']) && SafeEnv($_GET['ok'], 1, int) == '1'){
	$id = SafeEnv($_GET['id'], 11, int);
	$db->Select('news', "`topic_id`='".$id."'");
	$num_news = $db->NumRows();
	if($num_news == 0){
		$db->Delete('news_topics', "`id`='".$id."'");
		GO($config['admin_file'].'?exe=news&a=topics');
	}else{
		if(!isset($_GET['news'])){
			$text = '� ���� ������� �������� �������. �� ������:<br />'.'<a href="'.$config['admin_file'].'?exe=news&a=deltopic&id='.$id.'&ok=1&news=del">������� �������.</a> <br /> <a href="'.$config['admin_file'].'?exe=news&a=deltopic&id='.SafeEnv($_GET['id'], 11, int).'&ok=1&news=move">����������� ������� � ������ ������.</a>';
			AddTextBox('��������!', $text);
		}else{
			if($_GET['news'] == 'del'){
				$db->Delete('news', "`topic_id`='$id'");
				$db->Delete('news_coments', "`object`='$id'");
				$db->Delete('news_topics', "`id`='$id'");
				GO($config['admin_file'].'?exe=news&a=topics');
			}elseif($_GET['news'] == 'move' && !isset($_POST['to'])){
				$text = '�������� ������ � ������� �� ������� ����������� �������:<br />'.'<form action="'.$config['admin_file'].'?exe=news&a=deltopic&id='.$id.'&ok=1&news=move" method="post">';
				$db->Select('news_topics', "`id`<>'".$id."'");
				while($tp = $db->FetchRow()){
					$site->DataAdd($topic_data, SafeDB($tp['id'], 11, int), SafeDB($tp['title'], 255, str));
				}
				$text .= $site->Select('to', $topic_data).'<br />';
				$text .= $site->Submit('����������').'<br />';
				$text .= '</form>';
				AddTextBox('��������!', $text);
			}elseif($_GET['news'] == 'move' && isset($_POST['to'])){
				$to = SafeEnv($_POST['to'], 11, int);
				$db->Update('news', "topic_id='".$to."'", "`topic_id`='".$id."'");
				CalcNewsCounter($to, $num_news);
				GO($config['admin_file'].'?exe=news&a=deltopic&id='.$id.'&ok=1');
			}
		}
	}
}else{
	$text = '�� ������������� ������ ������� ���� ������?<br />'.'<a href="'.$config['admin_file'].'?exe=news&a=deltopic&id='.SafeDB($_GET['id'], 11, int).'&ok=1">��</a> &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">���</a>';
	AddTextBox("��������������", $text);
}

?>