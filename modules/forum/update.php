<?php

# LinkorCMS
# LinkorCMS Development Group
# www.linkorcms.ru
# Лицензия LinkorCMS 1.2.
# функции	 обновления форума

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

/* Исправления бага с правами пользователей 
и востановление ин-фы для перенесённых тем в другую категорию .
В прошлом релизе не было реализовано обновление ин-фы для перенесённых тем в другую категорию
*/
function Forum_Update_Delete_Bag() {
	global $db, $config, $forum_lib_dir;

	$mdba = $db->select('forums',"`admin_theme_add`='f_admin_theme_add'");
	if(count($mdba)>0)
		$db->Update('forums', "`admin_theme_add`='0'","`admin_theme_add`='f_admin_theme_add'");

	$mdba = $db->select('forums',"`no_link_guest`='f_no_link_guest'");
	if(count($mdba)>0)
		$db->Update('forums', "`no_link_guest`='0'","`no_link_guest`='f_no_link_guest'");

	$mdba = $db->select('forums',"`new_message_email`='f_new_message_email'");
	if(count($mdba)>0)
		$db->Update('forums', "`new_message_email`='0'","`new_message_email`='f_new_message_email'");

	$mdba = $db->select('forums', "`close_topic`='0' and `status`='1'");
	if(count($mdba)>0) {
		include_once($forum_lib_dir.'functions.php');
		foreach($mdba as $mdb)
			ForumSetLastPostInfo($mdb['id']);
	}
}


function Forum_Update( $auto_update = false) {
	global $db, $config;
	$what = '';
	$mdb = $db->GetTableColumns('forums');
	$endupdate = false;
	$m_column = array();
	foreach($mdb as $column) {
		$m_column[] = $column['name'];
	}

	if(!in_array('admin_theme_add', $m_column)) {
		$coll = GetCollDescription('admin_theme_add','int','1',false,'0');
		$db->InsertColl('forums', $coll, -1);
		$endupdate=true;
		$what .= 'Добавлена возможность - темы в категорию могут создавать только админы<BR>';
	}


	if(!in_array('no_link_guest', $m_column)) {
		$coll = GetCollDescription('no_link_guest','int','1',false,'0');
		$db->InsertColl('forums',$coll,-1);
		$endupdate=true;
		$what .= 'Добавлена опция - скрывать ссылки от гостей<BR>';
	}


	if(!in_array('new_message_email', $m_column)) {
		$coll = GetCollDescription('new_message_email','int','1',false,'0');
		$db->InsertColl('forums',$coll,-1);
		$endupdate=true;
	}


	if(!in_array('rang_access', $m_column)) {
		$coll = GetCollDescription('rang_access','int','11',false,'0');
		$db->InsertColl('forums',$coll,-1);
		$endupdate=true;
		$what .= 'Добавлена возможность доступа по рангу <BR>';

	}

	if(!in_array('rang_message', $m_column)) {
		$coll = GetCollDescription('rang_message','int','11',false,'0');
		$db->InsertColl('forums',$coll,-1);
		$endupdate=true;
		$what .= 'Добавлена возможность отвечать в темах в зависимости отранга <BR>';
	}

	if(!in_array('rang_add_theme', $m_column)) {
		$coll = GetCollDescription('rang_add_theme','int','11',false,'0');
		$db->InsertColl('forums',$coll,-1);
		$endupdate=true;
		$what .= 'Добавлена возможность создавать темы только тем кому разрешено <BR>';
	}

	if(!in_array('close_topic', $m_column)) {
		$coll = GetCollDescription('close_topic','int','1',false,'0');
		$db->InsertColl('forums',$coll,-1);
		$endupdate=true;
		$what .= 'Добавлена возможность закрывать темы для обсуждения - только чтение <BR>';
	}


	$mdb = $db->GetTableColumns('forum_topics');
	$m_column	= array();
	foreach($mdb as $column) {
		$m_column[] = $column['name'];
	}

	if(!in_array('close_topics', $m_column)) {
		$coll = GetCollDescription('close_topics','int','1',false,'0');
		$db->InsertColl('forum_topics',$coll,-1);
		$endupdate=true;
		$what .= 'Добавлена возможность закрывать категории для обсуждения - только чтение <BR>';
	}

	if(!in_array('stick', $m_column)) {
		$coll = GetCollDescription('stick','int','1',false,'0');
		$db->InsertColl('forum_topics',$coll,-1);
		$endupdate=true;
		$what .= 'Добавлена возможность выставлять важные темы вверх списка <BR>';
	}


	if(!in_array('delete', $m_column)) {
		$coll = GetCollDescription('delete','int','1',false,'0');
		$db->InsertColl('forum_topics',$coll,-1);
		$db->Update('forum_topics',"`delete`='0'","`id`>'0'");
		$endupdate=true;
	}


	$mdb = $db->GetTableColumns('forum_posts');
	$m_column	= array();
	foreach($mdb as $column) {
		$m_column[] = $column['name'];
	}
	if(!in_array('delete', $m_column)) {
		$coll = GetCollDescription('delete','int','1',false,'0');
		$db->InsertColl('forum_posts',$coll,-1);
		$db->Update('forum_posts',"`delete`='0'","`id`>'0'");
		$endupdate=true;
	}


	if(!isset($config['forum']['basket'])) {
		$db->Select('config_groups', "`name`='forum'");
		$group = $db->FetchRow();
		$db->Insert('config', "'','{$group['id']}','basket','1','1','Использовать удаление через \"корзину\".','Если да - то удаляемые темы и сообщения будут перемещаться в \"корзину\" и только после указаного в настройках кол-ва дней будут удаляться без возможности востановления.','combo','1:Да,0:Нет','','0,bool,false','1'");
		$db->Insert('config', "'','{$group['id']}' ,'clear_basket_day','7','1','Автоочистка корзины.','Через сколько дней удалять без возможности востановления  темы и сообщения из \"корзины\".','Edit:w60px','','','11,integer,true','1'");

		$db->Insert('config', "'','{$group['id']}' ,'del_auto_time','0','0','Последний запуск чистки корзины форума','служебная настройка - не видима в админке модуля  ','Edit:w60px','','','11,integer,true','1'");

		$forum_basket_post = 'a:6:{s:4:"type";s:6:"MyISAM";s:4:"cols";a:5:{i:0;a:6:{s:4:"name";s:2:"id";s:4:"type";s:3:"int";s:6:"length";i:11;s:7:"notnull";b:1;s:14:"auto_increment";b:1;s:7:"primary";b:1;}i:1;a:5:{s:4:"name";s:4:"date";s:4:"type";s:3:"int";s:6:"length";i:11;s:7:"notnull";b:1;s:5:"index";b:1;}i:2;a:4:{s:4:"name";s:4:"user";s:4:"type";s:3:"int";s:6:"length";i:11;s:7:"notnull";b:1;}i:3;a:5:{s:4:"name";s:6:"reason";s:4:"type";s:7:"varchar";s:6:"length";i:255;s:7:"default";s:0:"";s:7:"notnull";b:1;}i:4;a:4:{s:4:"name";s:6:"obj_id";s:4:"type";s:3:"int";s:6:"length";i:11;s:7:"notnull";b:1;}}s:8:"num_rows";i:1;s:7:"counter";i:1;s:4:"size";i:677;s:4:"name";s:17:"forum_basket_post";}'	 ;
		$forum_basket_post = unserialize($forum_basket_post);
		$db->CreateTable('forum_basket_post',$forum_basket_post,true);

		$forum_basket_topics = 'a:6:{s:7:"counter";i:2;s:8:"num_rows";i:2;s:4:"cols";a:5:{i:0;a:6:{s:4:"name";s:2:"id";s:4:"type";s:3:"int";s:6:"length";i:11;s:7:"notnull";b:1;s:14:"auto_increment";b:1;s:7:"primary";b:1;}i:1;a:5:{s:4:"name";s:4:"date";s:4:"type";s:3:"int";s:6:"length";i:11;s:7:"notnull";b:1;s:5:"index";b:1;}i:2;a:4:{s:4:"name";s:4:"user";s:4:"type";s:3:"int";s:6:"length";i:11;s:7:"notnull";b:1;}i:3;a:5:{s:4:"name";s:6:"reason";s:4:"type";s:7:"varchar";s:6:"length";i:255;s:7:"default";s:0:"";s:7:"notnull";b:1;}i:4;a:4:{s:4:"name";s:6:"obj_id";s:4:"type";s:3:"int";s:6:"length";i:11;s:7:"notnull";b:1;}}s:4:"type";s:6:"MyISAM";s:4:"size";i:862;s:4:"name";s:19:"forum_basket_topics";}';

		$forum_basket_topics = unserialize($forum_basket_topics);
		$db->CreateTable('forum_basket_topics',$forum_basket_topics,true);

		$config['forum']['basket'] = true;
		$config['forum']['clear_basket_day'] = 7;

		$endupdate=true;
		$what .= 'Добавлена корзина для форума и возможнсть "мягкого " удаления тем и сообщений<BR>';
	}

	$mdb = false;
	$mdb = $db->GetTableColumns('forum_subscription');
	if($mdb == false) {
		$forum_subscription = 'a:6:{s:7:"counter";i:0;s:8:"num_rows";i:0;s:4:"cols";a:3:{i:0;a:6:{s:4:"name";s:2:"id";s:4:"type";s:3:"int";s:6:"length";i:11;s:7:"notnull";b:1;s:14:"auto_increment";b:1;s:7:"primary";b:1;}i:1;a:5:{s:4:"name";s:5:"topic";s:4:"type";s:3:"int";s:6:"length";i:11;s:7:"notnull";b:1;s:5:"index";b:1;}i:2;a:5:{s:4:"name";s:4:"user";s:4:"type";s:3:"int";s:6:"length";i:11;s:7:"notnull";b:1;s:5:"index";b:1;}}s:4:"type";s:6:"MyISAM";s:4:"size";i:530;s:4:"name";s:18:"forum_subscription";}'	 ;
		$forum_subscription = unserialize($forum_subscription);
		$db->CreateTable('forum_subscription',$forum_subscription,true);

		$endupdate=true;
		$what .= 'Добавлена возможность подписываться на получения уведомления о новом сообщении <BR>';
	}

	if(!isset($config['forum']['cache'])) {
		$db->Select('config_groups', "`name`='forum'");
		$group = $db->FetchRow();
		$db->Insert('config', "'','{$group['id']}','cache','0','1','Использовать при работе форума \"кеш\".','При использовании \"кеша\" форум будет меньше нагружать базу данных запросами и меньше загружать обработкой полученных данных сервер. При этом скорость работы увеличится.','combo','1:Да,0:Нет','','0,bool,false','1'");
		$db->Insert('config', "'','{$group['id']}' ,'maxi_cache_duration','600','1','Максимальное время \"жизни\" кеша в секундах.','Через указанный промежуток данные будут обновляться независимо от того - изменились они или нет.','Edit:w60px','','','11,integer,true','1'");
		$db->Insert('config', "'','{$group['id']}','update_cache_in_add','0','1','Обновлять \"кеш\"  моментально при добалении новой темы или сообщения.','Обновляться будет весь кеш форума- не зависимо от того сколько осталось времени до его обновления .','combo','1:Да,0:Нет','','0,bool,false','1'");
		$config['forum']['cache'] = true;
		$config['forum']['maxi_cache_duration'] = 600;
		$config['forum']['update_cache_in_add'] = true;

		$endupdate=true;
		$what .= 'Добавлен кеш для форума - позволяет снизить нагрузку на сервер .<BR>';

		UnRegisterCommentTable('forum_posts');
	}

	if(!isset($config['forum']['ufu'])) {
		$db->Select('config_groups', "`name`='forum'");
		$group = $db->FetchRow();
		$db->Insert('config', "'','{$group['id']}','ufu','0','1','Генерировать Ч.П.У. для ссылок','Человеко понятные ссылки - вместо <BR>index.php?name=forum&op=showtopic&topic=28&view=lastpost<BR> будет<BR>  forum/1/3/topic28-new.html.','combo','1:Да,0:Нет','','0,bool,false','1'");
		$endupdate=true;
		$what .= 'Добавлена возможность генерировать ЧПУ для ссылок форума.<BR>';

	}

	if(!isset($config['forum']['max_word_length'])) {
		$db->Select('config_groups', "`name`='forum'");
		$group = $db->FetchRow();
		$db->Insert('config', "'','{$group['id']}','max_word_length','80','1','Максимальная длина слова','При превышении - слово будет разделено тегом BR. 0 - длина слова не ограничена','Edit:w60px','','','11,integer,true','1'");
		$endupdate=true;
		$what .= 'Добавлена возможность вставлять разрыв в сверх длинные слова.<BR>';

	}

	if($endupdate ) {
		AddTextBox('Обновление ', '<FONT SIZE="3" COLOR="#FF0000"><B>Обновление завершено. </B></FONT><BR><BR>'.$what);
	}else{
		AddTextBox('Обновление ', '<FONT SIZE="3" COLOR="#FF0000"><B>Обновление не требуется</B></FONT>');
	}

	Forum_Update_Delete_Bag();

}
