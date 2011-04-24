<?php

# LinkorCMS
# © 2006-2008 Галицкий Александр Николаевич (linkorcms@yandex.ru)
# LinkorCMS Development Group
# www.linkorcms.ru
# Лицензия LinkorCMS 1.2.
# Дополненая версия - Муратов Вячеслав (smilesoft@yandex.ru)


if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

include($config['mod_dir'].'forum/tables.php');

$db->CreateTable($table_forums['name'], $table_forums);
$db->CreateTable($table_forum_topics['name'], $table_forum_topics);
$db->CreateTable($table_forum_posts['name'], $table_forum_posts);
$db->CreateTable($table_forum_topics_read['name'], $table_forum_topics_read);

$forum_basket_post = 'a:6:{s:4:"type";s:6:"MyISAM";s:4:"cols";a:5:{i:0;a:6:{s:4:"name";s:2:"id";s:4:"type";s:3:"int";s:6:"length";i:11;s:7:"notnull";b:1;s:14:"auto_increment";b:1;s:7:"primary";b:1;}i:1;a:5:{s:4:"name";s:4:"date";s:4:"type";s:3:"int";s:6:"length";i:11;s:7:"notnull";b:1;s:5:"index";b:1;}i:2;a:4:{s:4:"name";s:4:"user";s:4:"type";s:3:"int";s:6:"length";i:11;s:7:"notnull";b:1;}i:3;a:5:{s:4:"name";s:6:"reason";s:4:"type";s:7:"varchar";s:6:"length";i:255;s:7:"default";s:0:"";s:7:"notnull";b:1;}i:4;a:4:{s:4:"name";s:6:"obj_id";s:4:"type";s:3:"int";s:6:"length";i:11;s:7:"notnull";b:1;}}s:8:"num_rows";i:1;s:7:"counter";i:1;s:4:"size";i:677;s:4:"name";s:17:"forum_basket_post";}'	 ;
$forum_basket_post = unserialize($forum_basket_post);
$db->CreateTable('forum_basket_post',$forum_basket_post,true);

$forum_basket_topics = 'a:6:{s:7:"counter";i:2;s:8:"num_rows";i:2;s:4:"cols";a:5:{i:0;a:6:{s:4:"name";s:2:"id";s:4:"type";s:3:"int";s:6:"length";i:11;s:7:"notnull";b:1;s:14:"auto_increment";b:1;s:7:"primary";b:1;}i:1;a:5:{s:4:"name";s:4:"date";s:4:"type";s:3:"int";s:6:"length";i:11;s:7:"notnull";b:1;s:5:"index";b:1;}i:2;a:4:{s:4:"name";s:4:"user";s:4:"type";s:3:"int";s:6:"length";i:11;s:7:"notnull";b:1;}i:3;a:5:{s:4:"name";s:6:"reason";s:4:"type";s:7:"varchar";s:6:"length";i:255;s:7:"default";s:0:"";s:7:"notnull";b:1;}i:4;a:4:{s:4:"name";s:6:"obj_id";s:4:"type";s:3:"int";s:6:"length";i:11;s:7:"notnull";b:1;}}s:4:"type";s:6:"MyISAM";s:4:"size";i:862;s:4:"name";s:19:"forum_basket_topics";}';

$forum_subscription = 'a:6:{s:7:"counter";i:0;s:8:"num_rows";i:0;s:4:"cols";a:3:{i:0;a:6:{s:4:"name";s:2:"id";s:4:"type";s:3:"int";s:6:"length";i:11;s:7:"notnull";b:1;s:14:"auto_increment";b:1;s:7:"primary";b:1;}i:1;a:5:{s:4:"name";s:5:"topic";s:4:"type";s:3:"int";s:6:"length";i:11;s:7:"notnull";b:1;s:5:"index";b:1;}i:2;a:5:{s:4:"name";s:4:"user";s:4:"type";s:3:"int";s:6:"length";i:11;s:7:"notnull";b:1;s:5:"index";b:1;}}s:4:"type";s:6:"MyISAM";s:4:"size";i:530;s:4:"name";s:18:"forum_subscription";}'	 ;
$forum_subscription = unserialize($forum_subscription);
$db->CreateTable('forum_subscription',$forum_subscription,true);


$forum_basket_topics = unserialize($forum_basket_topics);
$db->CreateTable('forum_basket_topics',$forum_basket_topics,true);


$coll = GetCollDescription('admin_theme_add','int','1',false,'0');
$db->InsertColl('forums',$coll,-1);

$coll = GetCollDescription('no_link_guest','int','1',false,'0');
$db->InsertColl('forums',$coll,-1);

$coll = GetCollDescription('new_message_email','int','1',false,'0');
$db->InsertColl('forums',$coll,-1);

$coll = GetCollDescription('rang_access','int','11',false,'0');
$db->InsertColl('forums',$coll,-1);

$coll = GetCollDescription('rang_message','int','11',false,'0');
$db->InsertColl('forums',$coll,-1);

$coll = GetCollDescription('rang_add_theme','int','11',false,'0');
$db->InsertColl('forums',$coll,-1);

$coll = GetCollDescription('close_topic','int','1',false,'0');
$db->InsertColl('forums',$coll,-1);

$coll = GetCollDescription('close_topics','int','1',false,'0');
$db->InsertColl('forum_topics',$coll,-1);


$coll = GetCollDescription('stick','int','1',false,'0');
$db->InsertColl('forum_topics',$coll,-1);


$coll = GetCollDescription('delete','int','1',false,'0');
$db->InsertColl('forum_topics',$coll,-1);

$coll = GetCollDescription('delete','int','1',false,'0');
$db->InsertColl('forum_posts',$coll,-1);



//RegisterCommentTable($table_forum_posts['name'], $table_forum_topics['name'], 'id', 'posts', '4');

$db->Insert('config_groups',"'','forum','Форум','','0'");
$db->Select('config_groups',"`name`='forum'");
$group = $db->FetchRow();
$db->Insert('config', "'','{$group['id']}','topics_on_page','10','1','Количество тем на страницу','','edit:w60px','','','3,int,false','1'");
$db->Insert('config', "'','{$group['id']}','posts_on_page','10','1','Количество сообщений на страницу','','edit:w60px','','','3,int,false','1'");
$db->Insert('config', "'','{$group['id']}','cache','0','1','Использовать при работе форума \"кеш\".','При использовании \"кеша\" форум будет меньше нагружать базу данных запросами и меньше загружать обработкой полученных данных сервер. При этом скорость работы увеличится.','combo','1:Да,0:Нет','','0,bool,false','1'");
$db->Insert('config', "'','{$group['id']}' ,'maxi_cache_duration','600','1','Максимальное время \"жизни\" кеша в секундах.','Через указанный промежуток данные будут обновляться независимо от того - изменились они или нет.','Edit:w60px','','','11,integer,true','1'");
$db->Insert('config', "'','{$group['id']}','update_cache_in_add','0','1','Обновлять \"кеш\"  моментально при добалении новой темы или сообщения.','Обновляться будет весь кеш форума- не зависимо от того сколько осталось времени до его обновления .','combo','1:Да,0:Нет','','0,bool,false','1'");
$db->Insert('config', "'','{$group['id']}','basket','1','1','Использовать удаление через \"корзину\".','Если да - то удаляемые темы и сообщения будут перемещаться в \"корзину\" и только после указаного в настройках кол-ва дней будут удаляться без возможности востановления.','combo','1:Да,0:Нет','','0,bool,false','1'");
$db->Insert('config', "'','{$group['id']}' ,'clear_basket_day','7','1','Автоочистка корзины.','Через сколько дней удалять без возможности востановления  темы и сообщения из \"корзины\".','Edit:w60px','','','11,integer,true','1'");
$db->Insert('config', "'','{$group['id']}' ,'del_auto_time','0','0','Последний запуск чистки корзины форума','служебная настройка - не видима в админке модуля  ','Edit:w60px','','','11,integer,true','1'");

$db->Insert('config', "'','{$group['id']}','ufu','0','1','Генерировать Ч.П.У. для ссылок','Человеко понятные ссылки - вместо <BR>index.php?name=forum&op=showtopic&topic=28&view=lastpost<BR> будет<BR>  forum/1/3/topic28-new.html.','combo','1:Да,0:Нет','','0,bool,false','1'");

$db->Insert('config', "'','{$group['id']}','max_word_length','80','1','Максимальная длина слова','При превышении - слово будет разделено тегом BR. 0 - длина слова не ограничена','Edit:w60px','','','11,integer,true','1'");

$db->Insert('config',"'','9','forum_post','5','1','Сообщение на форуме','','edit:w50','','','10,int,false','1'");
$db->Insert('access',"'','forum','forum','Форум'");
$db->Insert('modules',"'','Форум','forum','0','1','LinkorCMS Development Group','','4','1','15','1',''");

?>