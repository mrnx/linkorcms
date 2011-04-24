<?php

# LinkorCMS
# � 2006-2008 �������� ��������� ���������� (linkorcms@yandex.ru)
# LinkorCMS Development Group
# www.linkorcms.ru
# �������� LinkorCMS 1.2.
# ���������� ������ - ������� �������� (smilesoft@yandex.ru)


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

$db->Insert('config_groups',"'','forum','�����','','0'");
$db->Select('config_groups',"`name`='forum'");
$group = $db->FetchRow();
$db->Insert('config', "'','{$group['id']}','topics_on_page','10','1','���������� ��� �� ��������','','edit:w60px','','','3,int,false','1'");
$db->Insert('config', "'','{$group['id']}','posts_on_page','10','1','���������� ��������� �� ��������','','edit:w60px','','','3,int,false','1'");
$db->Insert('config', "'','{$group['id']}','cache','0','1','������������ ��� ������ ������ \"���\".','��� ������������� \"����\" ����� ����� ������ ��������� ���� ������ ��������� � ������ ��������� ���������� ���������� ������ ������. ��� ���� �������� ������ ����������.','combo','1:��,0:���','','0,bool,false','1'");
$db->Insert('config', "'','{$group['id']}' ,'maxi_cache_duration','600','1','������������ ����� \"�����\" ���� � ��������.','����� ��������� ���������� ������ ����� ����������� ���������� �� ���� - ���������� ��� ��� ���.','Edit:w60px','','','11,integer,true','1'");
$db->Insert('config', "'','{$group['id']}','update_cache_in_add','0','1','��������� \"���\"  ����������� ��� ��������� ����� ���� ��� ���������.','����������� ����� ���� ��� ������- �� �������� �� ���� ������� �������� ������� �� ��� ���������� .','combo','1:��,0:���','','0,bool,false','1'");
$db->Insert('config', "'','{$group['id']}','basket','1','1','������������ �������� ����� \"�������\".','���� �� - �� ��������� ���� � ��������� ����� ������������ � \"�������\" � ������ ����� ��������� � ���������� ���-�� ���� ����� ��������� ��� ����������� �������������.','combo','1:��,0:���','','0,bool,false','1'");
$db->Insert('config', "'','{$group['id']}' ,'clear_basket_day','7','1','����������� �������.','����� ������� ���� ������� ��� ����������� �������������  ���� � ��������� �� \"�������\".','Edit:w60px','','','11,integer,true','1'");
$db->Insert('config', "'','{$group['id']}' ,'del_auto_time','0','0','��������� ������ ������ ������� ������','��������� ��������� - �� ������ � ������� ������  ','Edit:w60px','','','11,integer,true','1'");

$db->Insert('config', "'','{$group['id']}','ufu','0','1','������������ �.�.�. ��� ������','�������� �������� ������ - ������ <BR>index.php?name=forum&op=showtopic&topic=28&view=lastpost<BR> �����<BR>  forum/1/3/topic28-new.html.','combo','1:��,0:���','','0,bool,false','1'");

$db->Insert('config', "'','{$group['id']}','max_word_length','80','1','������������ ����� �����','��� ���������� - ����� ����� ��������� ����� BR. 0 - ����� ����� �� ����������','Edit:w60px','','','11,integer,true','1'");

$db->Insert('config',"'','9','forum_post','5','1','��������� �� ������','','edit:w50','','','10,int,false','1'");
$db->Insert('access',"'','forum','forum','�����'");
$db->Insert('modules',"'','�����','forum','0','1','LinkorCMS Development Group','','4','1','15','1',''");

?>