<?php

//������ ��� ��������� ������� ������� �������������

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(isset($_GET['code'])){
	$code = SafeEnv($_GET['code'], 32, str);
	$db->Select('users', "`activate`='".$code."'");
	if($db->NumRows() > 0){
		$auser = $db->FetchRow();
		$db->Update('users', "active='1',activate=''", "`id`='".SafeDB($auser['id'], 11, int)."'");
		include_once($config['inc_dir'].'index_template.class.php');
		$site->AddTextBox('����������', '<center>���� ����������� ���� ������� ������������.</center>');
		$site->TEcho();
	}else{
		include_once($config['inc_dir'].'index_template.class.php');
		$site->AddTextBox('������', '<center>�������� ��� ���������.</center>');
		$site->TEcho();
	}
}else{
	GO(Ufu('index.php'));
}

?>