<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(isset($_GET['ok'])){
	$ok = $_GET['ok'];
}else{
	$ok = false;
}

if($ok){
	$db->DropTable(SafeEnv($_GET['name'], 255, str));
	GO($config['admin_file'].'?exe=fdbadmin');
}else{
	$text = "�� ������������� ������ ������� ������� \"".SafeEnv($_GET['name'], 255, str)."\"<br />".'<a href="'.$config['admin_file'].'?exe=fdbadmin&a=droptable&name='.SafeEnv($_GET['name'], 255, str).'&ok=1">��</a> &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">���</a>';
	AddTextBox("��������������", $text);
}

?>