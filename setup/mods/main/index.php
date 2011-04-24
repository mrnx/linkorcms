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
	case 1: // Заставка
		$this->SetTitle(_STEP1);
		$text = "<h2 class=\"title\">"._SETUP."</h2><br />"._SCRIPT.'.';
		$this->SetContent($text);
		$this->AddButton('Далее', 'main&p=2');
		break;
	case 2: // Выбор типа установки
		$this->SetTitle("Выбор типа установки");
		$this->OpenForm('check');
		global $setup_types;
		$setup_types = array();
		Plugins('setup_types');
		$text = '<p>Выберите действие:</p>';
		global $site;
		$text .= $site->Select('setup_type', $setup_types);
		$this->SetContent($text);
		$this->AddButton('Назад', 'main&p=1');
		$this->AddSubmitButton('Далее');
		break;
}

?>