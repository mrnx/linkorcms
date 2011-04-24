<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

global $bases_path, $db;

//$db->UseCache = false;
// Создаем таблицу smilies
$table_info = file_get_contents($bases_path.'table_smilies.MYD');
$table_info = unserialize($table_info);
$db->CreateTable('smilies', $table_info, true);
// Новые типы блоков blocks_types
$db->Insert('block_types', Values('', 'Меню', '', 'menu'));
// Новые настройки config
$db->Insert('config', Values('', '1', 'smilies_dir', 'images/smilies/', '0', 'Директория для смайликов', '', '', '', '', '', '1'));
$db->Insert('config', Values('', '5', 'images_dir', 'images/downloads/', '0', 'Папка для загрузки изображений', '', '', '', '', '', '1'));
$db->Insert('config', Values('', '5', 'thumb_max_width', '250', '1', 'Максимальная ширина эскизов', '(пикселей)', 'edit:w60px', '', '', '4,int,false', '1'));
$db->Insert('config', Values('', '5', 'thumb_max_height', '250', '1', 'Максимальная высота эскизов', '(пикселей)', 'edit:w60px', '', '', '4,int,false', '1'));
$db->Insert('config', Values('', '10', 'images_dir', 'images/articles/', '0', 'Папка для изображений к статьям', '', '', '', '', '', '1'));
$db->Insert('config', Values('', '10', 'thumb_max_width', '400', '1', 'Максимальная ширина эскизов', '(пикселей)', 'edit:w60px', '', '', '4,int,false', '1'));
$db->Insert('config', Values('', '10', 'thumb_max_height', '400', '1', 'Максимальная высота эскизов', '(пикселей)', 'edit:w60px', '', '', '4,int,false', '1'));
$db->Insert('config_groups', Values('', 'pages', 'Страницы', '', '0'));
$db->Insert('config', Values('', '18', 'default_page', '', '1', 'Страница на главной', 'Страница по умолчанию которая показывается на главной странице модуля.', 'combo', 'function:pages', '', '255,string,true', '1'));
$db->Delete('config', "`id`='18'");
// modules
// Добавляем колонку showinmenu
$coll = GetCollDescription('showinmenu', 'int', '1', false, '1', '', true, false, true);
$db->InsertColl('modules', $coll, -1);
//Добавляем записи о новый модулях
$db->Insert('modules', Values('', 'Поиск', 'search', '1', '1', '', '', '4', '1', '13', '0'));
$db->Insert('modules', Values('', 'Смайлики', 'smilies', '1', '0', '', '', '1', '1', '14', '1'));
// Подключаем плагин генерирующий капчу
$db->Insert('plugins', Values('', 'antibot', '', '4', ''));
// Добавляем кнопку в tiny_mce
$where = "`name`='theme_advanced_buttons3'";
$db->Select('plugins_config', $where);
if($db->NumRows() > 0){
	$row = $db->FetchRow();
	$db->Update('plugins_config', "value='{$row['value']},|,images'", $where);
}

// Обновляем таблицу news
$db->DeleteColl('news', 3); //end_date
$db->DeleteColl('news', 3); //on_end_event
$db->DeleteColl('news', 5); //show_in_home
$db->DeleteColl('news', 12); //use_poll
$db->DeleteColl('news', 12); //answers_counter
$coll = GetCollDescription('img_view', 'int', '1', false, '0');
$db->InsertColl('news', $coll, -1);
$coll = GetCollDescription('seo_title', 'varchar', '250');
$db->InsertColl('news', $coll, -1);
$coll = GetCollDescription('seo_keywords', 'varchar', '250');
$db->InsertColl('news', $coll, -1);
$coll = GetCollDescription('seo_description', 'varchar', '250');
$db->InsertColl('news', $coll, -1);

// Обновляем pages
// Добавляем колонку parent
$coll = GetCollDescription('parent', 'int', '11', false, '0');
$db->InsertColl('pages', $coll, 0);

// Переименовываем колонки 5,6,7.
$db->RenameColl('pages', 5, 'date');
$db->RenameColl('pages', 6, 'modified');
$db->RenameColl('pages', 7, 'hits');

// Добавляем новые колонки
$coll = GetCollDescription('seo_title', 'varchar', '250');
$db->InsertColl('pages', $coll, -1);
$coll = GetCollDescription('seo_keywords', 'varchar', '250');
$db->InsertColl('pages', $coll, -1);
$coll = GetCollDescription('seo_description', 'varchar', '250');
$db->InsertColl('pages', $coll, -1);
$coll = GetCollDescription('type', 'varchar', '4', false, 'page', '', true, false, true);
$db->InsertColl('pages', $coll, -1);
$coll = GetCollDescription('order', 'int', '11', false, '0');
$db->InsertColl('pages', $coll, -1);
$coll = GetCollDescription('showinmenu', 'int', '1', false, '1', '', true, false, true);
$db->InsertColl('pages', $coll, -1);

// Обновляем таблицу articles (seo модуль)
$coll = GetCollDescription('seo_title', 'varchar', '250');
$db->InsertColl('articles', $coll, -1);
$coll = GetCollDescription('seo_keywords', 'varchar', '250');
$db->InsertColl('articles', $coll, -1);
$coll = GetCollDescription('seo_description', 'varchar', '250');
$db->InsertColl('articles', $coll, -1);

?>