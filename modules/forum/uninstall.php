<?php

# LinkorCMS
# © 2006-2008 Галицкий Александр Николаевич (linkorcms@yandex.ru)
# LinkorCMS Development Group
# www.linkorcms.ru
# Лицензия LinkorCMS 1.2.

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

include($config['mod_dir'].'forum/tables.php');
$db->DropTable($table_forums['name']);
$db->DropTable($table_forum_topics['name']);
$db->DropTable($table_forum_posts['name']);
$db->DropTable($table_forum_topics_read['name']);
$db->DropTable('forum_basket_post');
$db->DropTable('forum_basket_topics');
$db->DropTable('forum_subscription');

$db->Select('config_groups',"`name`='forum'");
$group = $db->FetchRow();
$db->Delete('config_groups',"`name`='forum'");
$db->Delete('config',"`group_id`='{$group['id']}'");
$db->Delete('config',"`group_id`='9' and `name`='forum_post'");

$db->Delete('access',"`group`='forum'");
$db->Delete('modules', "`folder`='forum'");

?>