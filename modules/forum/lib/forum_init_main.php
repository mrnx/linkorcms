<?php

if(!defined('VALID_RUN')) {
	Header('Location: http://'.getenv("HTTP_HOST").'/index.php');
	exit();
}

$site->AddCSSFile($config['tpl_dir'].$config['general']['site_template'].'/style/forum.css', true);

include_once $config['inc_dir'].'LmFileCache.php';
include_once('scripts/bbcode_editor/script.php');
include_once($forum_lib_dir.'forum_add.php');
include_once($forum_lib_dir.'forum_cache.php');
include_once($forum_lib_dir.'forum_subscription.php');
include_once($forum_lib_dir.'forum_java.php');
include_once($forum_lib_dir.'forum_lang.php');
include_once($forum_lib_dir.'forum_last_topics.php');
include_once($forum_lib_dir.'forum_marker.php');
include_once($forum_lib_dir.'forum_moderation.php');
include_once($forum_lib_dir.'forum_navigation.php');
include_once($forum_lib_dir.'forum_online.php');
include_once($forum_lib_dir.'forum_posts.php');
include_once($forum_lib_dir.'forum_rang.php');
include_once($forum_lib_dir.'forum_render_topics.php');
include_once($forum_lib_dir.'forum_statistics.php');
include_once($forum_lib_dir.'forum_topic.php');
include_once($forum_lib_dir.'functions.php');

include_once($forum_lib_dir.'forum_basket.php');
include_once($forum_lib_dir.'forum_autoclear_basket.php');

global $user;
$user->Def('u_add_rating', true);
$user->Def('u_add_comment', true);
$user->Def('u_read_comment', true);
$user->Def('u_add_forum', true);
$user->Def('u_read_forum',true);

Index_Forum_Add_Java();

if(!isset($config['forum']['cache'])) {
	$config['forum']['cache'] = false;
	$config['forum']['maxi_cache_duration'] = 600;
	$config['forum']['update_cache_in_add'] = true;
}

$site->Title = $lang['forum'];
$forum_navigation = '';
