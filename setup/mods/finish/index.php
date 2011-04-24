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
		$this->SetTitle("Установка завершена!");
		$text = "<h2 class=\"title\">"."Поздравляем!"."</h2><br />"."Система LinkorCMS была успешно установлена на Ваш сервер.<br />Теперь Вы можете перейти в панель администратора и настроить систему по своему вкусу <br />или перейти на только что установленный сайт.<br /><br />"."<font color=\"#FF0000\">!!! В целях безопасности <b>удалите файл setup.php c сервера.</b> !!!</font>";
		$this->SetContent($text);
		$this->AddButton('Админ-панель', 'finish&p=3');
		$this->AddButton('На сайт', 'finish&p=2');
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