<?php

// Скрипт автоматически проверяет и обновляет базу данных

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}
if(defined('SETUP_SCRIPT')) return;
$updated = false;

// LinkorCMS 1.3.2
if(!isset($config['gb']['show_captcha'])){
	$db->Insert("config","'','4','show_captcha','1','1','Капча для пользователей','Показывать тест Капча для зарегистрированных пользователей','combo','function:yes_no','','255,int,true','1'");
	$config['gb']['show_captcha'] = true;
	$updated = true;
}

// LinkorCMS 1.3.4
if(!isset($config['general']['ufu'])){
	$db->Insert("config","'','1','ufu','1','1','Генерировать ЧПУ ссылки','Генерировать ссылки вида /pages/linkorcms.html','combo','function:yes_no','clear_cache','1,bool,false','1'");
	$config['general']['ufu'] = true;
	$db->Insert("config","'','1','private_site','0','1','Закрыть сайт для пользователей','Доступ к сайту будут иметь только Администраторы','combo','function:yes_no','','1,bool,false','1'");
	$config['general']['private_site'] = false;

	$db->CreateTable('rewrite_rules', unserialize('a:7:{s:7:"counter";i:47;s:8:"num_rows";i:32;s:4:"cols";a:4:{i:0;a:6:{s:4:"name";s:2:"id";s:4:"type";s:3:"int";s:6:"length";i:11;s:7:"notnull";b:1;s:14:"auto_increment";b:1;s:7:"primary";b:1;}i:1;a:5:{s:4:"name";s:3:"ufu";s:4:"type";s:7:"varchar";s:6:"length";i:255;s:7:"notnull";b:1;s:5:"index";b:1;}i:2;a:5:{s:4:"name";s:7:"pattern";s:4:"type";s:7:"varchar";s:6:"length";i:255;s:7:"default";s:0:"";s:7:"notnull";b:1;}i:3;a:5:{s:4:"name";s:6:"params";s:4:"type";s:7:"varchar";s:6:"length";i:255;s:7:"default";s:0:"";s:7:"notnull";b:1;}}s:7:"comment";s:0:"";s:4:"type";s:6:"MYISAM";s:4:"size";i:4785;s:4:"name";s:13:"rewrite_rules";}'), true);
	$db->EditColl('downloads', 7, Unserialize('a:3:{s:4:"name";s:9:"shortdesc";s:4:"type";s:4:"text";s:7:"notnull";b:1;}'));

	// Обновляем настройки обратной связи
	$db->Update('config', "`kind`='text:w400px:h200px'", "`group_id`='17'");
	// Убираем вырезание html тегов в условиях регистрации
	$db->Update('config', "`type`='0,string,false',`description`='Текст будет показан при регистрации. Текст должен содержать условия, которые пользователь должен будет соблюдать при работе с сайтом. Для форматирования можно использовать HTML теги.'", "`group_id`='7' and `name`='reg_condition'"); 

	$db->Insert("modules","'','Кэш','cache','1','0','','','1','1','15','1',''");
	$updated = true;
}

if($updated){ // Очищаем весь кэш
	$cache = LmFileCache::Instance();
	$groups = $cache->GetGroups();
	foreach($groups as $g){
		$cache->Clear($g);
	}
}

?>