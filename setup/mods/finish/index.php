<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(isset($_GET['p'])){
	$p = SafeEnv($_GET['p'], 1, int);
}else{
	$p = 1;
}

switch($p){
	case 1: // ��������
		$this->SetTitle("��������� ���������!");
		$text = "<h2 class=\"title\">"."�����������!"."</h2><br />"."������� LinkorCMS ���� ������� ����������� �� ��� ������.<br />������ �� ������ ������� � ������ �������������� � ��������� ������� �� ������ ����� <br />��� ������� �� ������ ��� ������������� ����.<br /><br />"."<font color=\"#FF0000\">!!! � ����� ������������ <b>������� ���� setup.php c �������.</b> !!!</font>";
		$this->SetContent($text);
		$this->AddButton('�����-������', 'finish&p=3');
		$this->AddButton('�� ����', 'finish&p=2');
		break;
	case 2:
		GO('index.php');
		break;
	case 3:
		global $config;
		GO($config['admin_file']);
		break;
}

?>