<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

global $user;

if(!$user->isDef('setup_type')){
	$user->Def('setup_type', 'update12_13');
}

if(isset($_GET['p'])){
	$p = SafeEnv($_GET['p'], 1, int);
}else{
	$p = 1;
}

switch($p){
	// ���������� ����������:::
	case 1:
		global $db, $config;
		include_once ($config['s_inc_dir'].'database.php');
		$this->SetTitle('���������� 1.2->1.3');
		if(!isset($config['db_version'])){
			$config['db_version'] = '1.1';
		}
		if($config['db_version'] == CMS_VERSION){
			$this->SetContent('���������� �� ���������. ������ �� � ������ ������� ���������.');
			$this->AddButton('�� ����', 'finish&p=2');
		}elseif($config['db_version'] != '1.2' || substr(CMS_VERSION, 0, 3) != '1.3'){
			$this->SetContent('������ �� ������ ���� 1.2, � ������ ������� 1.3.');
		}elseif(!is_writable($config['config_dir']."db_config.php")){
			$this->SetContent('���� ������������ "'.$config['config_dir']."db_config.php".'" �� �������� ��� ������, ���������� �� ���� �����������. ��������� ����� 666 ��� ����� ����� � ��������� �����.');
			$this->AddButton('���������', 'update12_13');
		}elseif(!is_writable($config['config_dir'])){
			$this->SetContent('����� "'.$config['config_dir'].'" �� ��������� ��� ������, ���������� �� ���� �����������. ��������� ����� 777 �� ��� �����.');
			$this->AddButton('���������', 'update12_13');
		}else{
			// ����������
			include_once($config['s_mod_dir'].'update12_13/update.php');
			$this->SetContent('���������� ���� ������ ������ �������!');
			// ���������� ����� �������
			$filename = $config['config_dir']."db_config.php";
			copy($filename, $config['config_dir']."db_config-backup.php");
			WriteConfigFile(
				$filename,
				$config['db_type'],
				$config['db_host'],
				$config['db_user'],
				$config['db_pass'],
				$config['db_name'],
				$config['db_pref'],
				CMS_VERSION
			);
			$saltfilename = $config['config_dir']."salt.php";
			WriteSaltFile($saltfilename);
			$this->AddButton('�� ����', 'finish&p=2');
		}
		break;
}

?>