<?php
# LinkorCMS
# LinkorCMS Development Group
# www.linkorcms.ru
# Лицензия LinkorCMS 1.2.
# элементы навигации

$forum_navigation = '';

function Navigation_AppLink($title, $url) {
	global $forum_navigation, $forum_lang;
	$forum_navigation .= ($forum_navigation != '' ? $forum_lang['site_slas'] : '').'<b><a href="'.$url.'">'.$title.'</a></b>';
}

function Navigation_ShowNavMenu() {
	global $site, $forum_navigation;
	$site->AddTextBox('', $forum_navigation);
}

// Комбобокс  "Разделы форума"
function Navigation_GetForumCategoryComboBox( $cat, $form =true, $count = true, $start = true){
	global  $site, $config;
	//$ex_where = "'status`='1'";
	$all_table = Forum_Cache_AllDataTableForum();
	$table = ForumGetViewAccessTable($all_table);

	include_once($config['inc_dir'].'tree.class.php');
	$tree = new Tree($table ,  'id',  'parent_id', 'title', 'topics', 'posts');
	$tree->moduleName = 'forum_topics';
	$tree->catTemplate = '';
	$tree->id_par_name = 'forum_id';
	$tree->NumItemsCaption = '';
	$tree->TopCatName = 'Главная страница форума';
	$data = array();
	$data = $tree->GetCatsData($cat, $count, $start);
	$nav_url = 'index.php?name=forum&op=showforum';
	if($form){
		return '<form action="'.$nav_url.'" method="get">
<input type="hidden" name="name" value="forum">
<input type="hidden" name="op" value="showforum">
'.$site->Select('forum', $data, false, 'onchange="this.form.submit();"').'
</form>';
	}else{
		return $site->Select('forum',$data,false);
	}
}

function Navigation_Patch( $cat, $view_end_url = false){
	global  $site, $config, $forum_lib_dir, $forum_lang;
	$all_table = Forum_Cache_AllDataTableForum();
	$table = ForumGetViewAccessTable($all_table);
	include_once($forum_lib_dir.'tree_f.class.php');
	$tree = new ForumTree($table ,  'id',  'parent_id', 'title', 'topics', 'posts');
	$tree->moduleName = 'forum';
	$tree->TopCatName = 'Форум';
	$tree->Slach = $forum_lang['site_slas'];
	$nav_url = 'index.php?name=forum';
	$tree->ShowPath($cat, $view_end_url);
}
