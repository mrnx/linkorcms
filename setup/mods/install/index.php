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
	case 1: // Заставка
		$this->SetTitle(_STEP1);
		Plugins('license');
		$this->AddButton('Назад', 'main&p=2');
		$this->AddButton('Принимаю', 'install&p=2');
		break;
	case 2: // Выбор типа установки
		$this->SetTitle("Выбор типа Базы данных.");
		$this->OpenForm('install&p=3');
		global $db_types;
		$db_types = array();
		Plugins('db_types');
		$text = '<p>Выберите тип Базы данных:</p>';
		global $site;
		$text .= $site->Select('db_type', $db_types);
		$this->SetContent($text);
		$this->AddButton('Назад', 'install&p=3');
		$this->AddSubmitButton('Далее');
		break;
	case 3: // Перенаправление соответственно выбранному типу БД
		$smod = SafeEnv($_POST['db_type'], 255, str);
		$user->Def('db_type', $smod);
		GO('setup.php?mod='.$smod);
		break;
}

?>