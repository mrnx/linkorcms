<?php

$system_autoload = array(
	'System'          => $config['inc_dir'].'system.class.php',
	'Logi'            => $config['inc_dir'].'logi.class.php',
	'LmFileCache'     => $config['inc_dir'].'LmFileCache.php',
	'LmEmailExtended' => $config['inc_dir'].'LmEmailExtended.php',
	'User'            => $config['inc_dir'].'user.class.php',
	'RssChannel'      => $config['inc_dir'].'rss.class.php',
	'TPicture'        => $config['inc_dir'].'picture.class.php',

	'HTML'         => $config['inc_dir'].'html.class.php',
	'Starkyt'      => $config['inc_dir'].'starkyt.class.php',
	'PageTemplate' => $config['inc_dir'].'page_template.class.php',
	'Page'         => $config['inc_dir'].'index_template.class.php',
	'AdminPage'    => $config['inc_dir'].'admin_template.class.php',

	'Navigation' => $config['inc_dir'].'navigation.class.php',
	'Tree'       => $config['inc_dir'].'tree.class.php',
	'AdminTree'  => $config['inc_dir'].'tree_a.class.php',
	'IndexTree'  => $config['inc_dir'].'tree_b.class.php',
	'Posts'      => $config['inc_dir'].'posts.class.php'
);

$system_modules = array(
	'access.php','admin.php','ajax.php','array.php','autoload.php',
	'bbcode.php','comments.php','config.php','database.php','datetime.php',
	'email.php','errors.php','extensions.php','filesystem.php','forms.php',
	'images.php','location.php','plugins.php','plugins_system.php','request.php',
	'scripts.php','search.php','smilies.php','string.php','translit.php',
	'ufu.php','url.php','user.php','utf8.php','version.php'
);

include 'config/autoload_user.php'; // Пользовательские классы