<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

include_once $config['inc_dir'].'LmFileCache.php';
include_once($forum_lib_dir.'functions.php');
include_once($forum_lib_dir.'forum_subscription.php');
include_once($forum_lib_dir.'forum_cache.php');
include_once($forum_lib_dir.'forum_add.php');
include_once($forum_lib_dir.'forum_java.php');
include_once($forum_lib_dir.'forum_lang.php');
//include_once($forum_lib_dir.'forum_last_topics.php');
include_once($forum_lib_dir.'forum_marker.php');
include_once($forum_lib_dir.'forum_moderation.php');
include_once($forum_lib_dir.'forum_navigation.php');
include_once($forum_lib_dir.'forum_online.php');
include_once($forum_lib_dir.'forum_posts.php');
include_once($forum_lib_dir.'forum_rang.php');
//include_once($forum_lib_dir.'forum_render_topics.php');
//include_once($forum_lib_dir.'forum_statistics.php');
include_once($forum_lib_dir.'forum_topic.php');

include_once($forum_lib_dir.'forum_autoclear_basket.php');
include_once($forum_lib_dir.'forum_basket.php');

Index_Forum_Add_Java();
