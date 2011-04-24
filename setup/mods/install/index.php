<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

global $user;

if(!$user->isDef('setup_type')){
	$user->Def('setup_type', 'install');
}

if(isset($_GET['p'])){
	$p = SafeEnv($_GET['p'], 1, int);
}else{
	$p = 1;
}

switch($p){
	case 1: // ��������
		$this->SetTitle(_STEP1);
		Plugins('license');
		$this->AddButton('�����', 'main&p=2');
		$this->AddButton('��������', 'install&p=2');
		break;
	case 2: // ����� ���� ���������
		$this->SetTitle("����� ���� ���� ������.");
		$this->OpenForm('install&p=3');
		global $db_types;
		$db_types = array();
		Plugins('db_types');
		$text = '<p>�������� ��� ���� ������:</p>';
		global $site;
		$text .= $site->Select('db_type', $db_types);
		$this->SetContent($text);
		$this->AddButton('�����', 'install&p=3');
		$this->AddSubmitButton('�����');
		break;
	case 3: // ��������������� �������������� ���������� ���� ��
		$smod = SafeEnv($_POST['db_type'], 255, str);
		$user->Def('db_type', $smod);
		GO('setup.php?mod='.$smod);
		break;
}

?>