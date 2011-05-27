<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

global $db, $user, $config;

$vars['title'] = $title;
$tempvars['content'] = 'block/content/menu.html';

$uri = $_SERVER['REQUEST_URI'];

// Кэширование
$bcache = LmFileCache::Instance();
$bcache_name = 'menu'.$user->AccessLevel();
if($bcache->HasCache('block', $bcache_name)){
	$block_menu_items = $bcache->Get('block', $bcache_name);
	foreach($block_menu_items['sub'] as $k=>$item){
		$top_selected = (strpos($uri, $item['vars']['link']) !== false);
		$subitem_selected = false;
		foreach($item['child']['block_menu_subitems']['sub'] as $n=>$subitem){
			$selected = (strpos($uri, $subitem['vars']['link']) !== false);
			if(!$subitem_selected && $selected){
				$subitem_selected = $selected;
			}
			$block_menu_items['sub'][$k]['child']['block_menu_subitems']['sub'][$n]['vars']['selected'] = $selected;

		}
	}
	$block_menu_items['sub'][$k]['vars']['selected'] = $top_selected || $subitem_selected;
	$childs['block_menu_items'] = $block_menu_items;
	return;
}

// Выборка
$where = "`enabled`='1'";
$w2 = GetWhereByAccess('view');
if($w2 != ''){
	$where .= ' and ('.$w2.')';
}
$pages = $db->Select('pages', $where);
SortArray($pages, 'order');
$catsPid = array();
$catsId = array();
foreach($pages as $page){
	$catsPid[$page['parent']][] = $page;
}
if(isset($catsPid[0])){
	$pages = $catsPid[0];
}else{
	$pages = array();
}

// Генерация меню
$block_menu_items = Starkyt::CreateBlock(true, true, 'menu_item');
foreach($pages as $page){
	if($page['showinmenu'] == '1'){
		if($page['type'] == 'page'){
			$link = Ufu('index.php?name=pages&file='.SafeDB($page['link'], 255, str), 'pages/{file}.html');
		}elseif($page['type'] == 'link'){
			$link = SafeDB($page['text'], 255, str);
			if(substr($link, 0, 6) == 'mod://'){
				$link = Ufu('index.php?name='.substr($link, 6), '{name}/');
			}
		}else{
			$link = false;
		}
		$link = str_replace('&amp;', '&', $link);
		$vars_item = array('title'=>$page['title'], 'link'=> $link, 'subitems'=>false);
		$vars_item['selected'] = $link != '' && (strpos($uri, $link) !== false);
		$selected = false;

		$block_menu_subitems = Starkyt::CreateBlock(true, true, 'menu_subitem');
		if(isset($catsPid[$page['id']]) && $page['showinmenu'] == '1'){
			$subpages = $catsPid[$page['id']];
			$vars_item['subitems'] = count($subpages) > 0;
			foreach($subpages as $subpage){
				if($subpage['showinmenu'] == '1'){
					if($subpage['type'] == 'page'){
						$link = Ufu('index.php?name=pages&file='.SafeDB($subpage['link'], 255, str), 'pages/{file}.html');
					}elseif($subpage['type'] == 'link'){
						$link = SafeDB($subpage['text'], 255, str);
						if(substr($link, 0, 6) == 'mod://'){
							$link = Ufu('index.php?name='.substr($link, 6), '{name}/');
						}
					}else{
						continue;
					}
					$link = str_replace('&amp;', '&', $link);
					$vars_subitem = array('title'=>$subpage['title'], 'link'=>$link);
					$vars_subitem['selected'] = (strpos($uri, $link) !== false);
					if(!$selected && $vars_subitem['selected']){
						$selected = true;
					}
					$block_menu_subitems['sub'][] = Starkyt::CreateSubBlock(true, $vars_subitem);
				}
			}
		}
		$vars_item['selected'] = $vars_item['selected'] || $selected;
		$block_menu_items['sub'][] = Starkyt::CreateSubBlock(true, $vars_item, array(), '', '', array('block_menu_subitems'=>$block_menu_subitems));
	}
}

$childs['block_menu_items'] = $block_menu_items;
$bcache->Write('block', $bcache_name, $block_menu_items);

?>