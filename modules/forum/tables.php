<?php

# LinkorCMS
# © 2006-2008 Галицкий Александр Николаевич (linkorcms@yandex.ru)
# LinkorCMS Development Group
# www.linkorcms.ru
# Лицензия LinkorCMS 1.2.

$table_forums = Array(
		"type" => 'MyISAM',
		"cols" => Array
		(
				"0" => Array
				(
						"name" => 'id',
						"type" => 'int',
						"length" => '11',
						"notnull" => '1',
						"auto_increment" => '1',
						"primary" => '1'
				),
				"1" => Array
				(
						"name" => 'parent_id',
						"type" => 'int',
						"length" => '11',
						"notnull" => '1',
						"index" => '1'
				),
				"2" => Array
				(
						"name" => 'title',
						"type" => 'varchar',
						"length" => '255',
						"default" => '',
						"notnull" => '1'
				),
				"3" => Array
				(
						"name" => 'description',
						"type" => 'text',
						"notnull" => '1'
				),
				"4" => Array
				(
						"name" => 'topics',
						"type" => 'int',
						"length" => '11',
						"notnull" => '1'
				),
				"5" => Array
				(
						"name" => 'posts',
						"type" => 'int',
						"length" => '11',
						"notnull" => '1'
				),
				"6" => Array
				(
						"name" => 'last_post',
						"type" => 'int',
						"length" => '11',
						"notnull" => '1'
				),
				"7" => Array
				(
						"name" => 'last_poster_id',
						"type" => 'int',
						"length" => '11',
						"notnull" => '1'
				),
				"8" => Array
				(
						"name" => 'last_poster_name',
						"type" => 'varchar',
						"length" => '255',
						"default" => '',
						"notnull" => '1'
				),
				"9" => Array
				(
						"name" => 'last_title',
						"type" => 'varchar',
						"length" => '255',
						"default" => '',
						"notnull" => '1'
				),
				"10" => Array
				(
						"name" => 'last_id',
						"type" => 'int',
						"length" => '11',
						"notnull" => '1'
				),
				"11" => Array
				(
						"name" => 'order',
						"type" => 'int',
						"length" => '11',
						"notnull" => '1',
						"index" => '1'
				),
				"12" => Array
				(
						"name" => 'status',
						"type" => 'int',
						"length" => '1',
						"notnull" => '1',
						"index" => '1'
				),
				"13" => Array
				(
						"name" => 'view',
						"type" => 'int',
						"length" => '1',
						"notnull" => '1'
				)
		),
		"size" => '5071',
		"name" => 'forums'
);

$table_forum_topics = Array(
		"cols" => Array
		(
				"0" => Array
				(
						"name" => 'id',
						"type" => 'int',
						"length" => '11',
						"notnull" => '1',
						"auto_increment" => '1',
						"primary" => '1'
				),
				"1" => Array
				(
						"name" => 'forum_id',
						"type" => 'int',
						"length" => '11',
						"notnull" => '1',
						"index" => '1'
				),
				"2" => Array
				(
						"name" => 'title',
						"type" => 'varchar',
						"length" => '255',
						"default" => '',
						"notnull" => '1'
				),
				"3" => Array
				(
						"name" => 'state',
						"type" => 'int',
						"length" => '1',
						"notnull" => '1'
				),
				"4" => Array
				(
						"name" => 'posts',
						"type" => 'int',
						"length" => '11',
						"notnull" => '1'
				),
				"5" => Array
				(
						"name" => 'hits',
						"type" => 'int',
						"length" => '11',
						"notnull" => '1'
				),
				"6" => Array
				(
						"name" => 'start_date',
						"type" => 'int',
						"length" => '11',
						"notnull" => '1'
				),
				"7" => Array
				(
						"name" => 'starter_id',
						"type" => 'int',
						"length" => '11',
						"notnull" => '1',
						"index" => '1'
				),
				"8" => Array
				(
						"name" => 'starter_name',
						"type" => 'varchar',
						"length" => '255',
						"default" => '',
						"notnull" => '1'
				),
				"9" => Array
				(
						"name" => 'last_post',
						"type" => 'int',
						"length" => '11',
						"notnull" => '1'
				),
				"10" => Array
				(
						"name" => 'last_poster_id',
						"type" => 'int',
						"length" => '11',
						"notnull" => '1'
				),
				"11" => Array
				(
						"name" => 'last_poster_name',
						"type" => 'varchar',
						"length" => '255',
						"default" => '',
						"notnull" => '1'
				),
				"12" => Array
				(
						"name" => 'uniq_code',
						"type" => 'char',
						"length" => '12',
						"notnull" => '1',
						"index" => '1'
				),
		),
		"type" => 'MyISAM',
		"size" => '2009',
		"name" => 'forum_topics'
);

$table_forum_posts = Array
		(
		"type" => 'MyISAM',
		"cols" => Array
		(
				"0" => Array
				(
						"name" => 'id',
						"type" => 'int',
						"length" => '11',
						"attributes" => 'unsigned',
						"notnull" => '1',
						"auto_increment" => '1',
						"primary" => '1'
				),
				"1" => Array
				(
						"name" => 'object',
						"type" => 'int',
						"length" => '11',
						"attributes" => 'unsigned',
						"notnull" => '1',
						"index" => '1'
				),
				"2" => Array
				(
						"name" => 'user_id',
						"type" => 'int',
						"length" => '11',
						"attributes" => 'unsigned',
						"notnull" => '1',
						"index" => '1'
				),
				"3" => Array
				(
						"name" => 'public',
						"type" => 'int',
						"length" => '11',
						"notnull" => '1',
						"index" => '1'
				),
				"4" => Array
				(
						"name" => 'name',
						"type" => 'varchar',
						"length" => '50',
						"default" => '',
						"notnull" => '1'
				),
				"5" => Array
				(
						"name" => 'homepage',
						"type" => 'varchar',
						"length" => '250',
						"default" => '',
						"notnull" => '1'
				),
				"6" => Array
				(
						"name" => 'email',
						"type" => 'varchar',
						"length" => '50',
						"default" => '',
						"notnull" => '1'
				),
				"7" => Array
				(
						"name" => 'hide_email',
						"type" => 'int',
						"length" => '1',
						"attributes" => 'unsigned',
						"notnull" => '1'
				),
				"8" => Array
				(
						"name" => 'icq',
						"type" => 'varchar',
						"length" => '15',
						"default" => '',
						"notnull" => '1'
				),
				"9" => Array
				(
						"name" => 'message',
						"type" => 'text',
						"notnull" => '1'
				),
				"10" => Array
				(
						"name" => 'user_ip',
						"type" => 'varchar',
						"length" => '19',
						"default" => '',
						"notnull" => '1'
				),
		),
		"size" => '4350',
		"name" => 'forum_posts'
);

$table_forum_topics_read = Array
		(
		"cols" => Array
		(
				"0" => Array
				(
						"name" => 'mid',
						"type" => 'int',
						"length" => '11',
						"notnull" => '1'
				),
				"1" => Array
				(
						"name" => 'tid',
						"type" => 'int',
						"length" => '11',
						"notnull" => '1'
				),
				"2" => Array
				(
						"name" => 'date',
						"type" => 'int',
						"length" => '11',
						"notnull" => '1'
				),
		),
		"type" => 'MyISAM',
		"size" => '626',
		"name" => 'forum_topics_read'
);

