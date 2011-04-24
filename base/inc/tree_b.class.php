<?php

# LinkorCMS
# © 2006-2010 Галицкий Александр Николаевич (linkorcms@yandex.ru)
# Файл: tree_b.class.php
# Назначение: Высокоуровневый класс для работы с деревьями на главной странице

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

include ($config['inc_dir'].'tree.class.php'); //class Tree

class IndexTree extends Tree
{
	public $catTemplate = 'module/cat.html';
	public $moduleName = '';
	public $id_par_name = 'cat';
	public $NumItems = '';
	public $NumItemsCaption = '';
	public $TopCatName = 'Начало архива';

	public function Catalog( $cat_id = 0, $CallBackNumItems = '' )
	{
		$this->NumItems = $CallBackNumItems;
		if($cat_id != 0){
			$this->ShowPath($cat_id);
		}
		$this->ShowCats($cat_id);
	}

	public function ShowCats( $cat_id )
	{
		global $db, $config, $site;
		$vars = array();
		$cats = $this->GetChildTree($cat_id);
		$c = count($cats);
		if($c > 0){
			$site->AddTemplatedBox('', $this->catTemplate);
			$site->AddBlock('cats', true, true, 'cat');
			for($i = 0; $i < $c; $i++){
				$id = SafeDB($cats[$i]['id'], 11, int);

				$vars['url'] = Ufu('index.php?name='.$this->moduleName.'&'.$this->id_par_name.'='.$id, $this->moduleName.'/{'.$this->id_par_name.'}/');
				$vars['title2'] = SafeDB($cats[$i]['title'], 255, str);
				$vars['title'] = '<a href="'.$vars['url'].'">'.$vars['title2'].'</a>';
				
				if(file_exists($cats[$i]['icon'])){
					$vars['icon_url'] = SafeDB(RealPath2($cats[$i]['icon']), 255, str);
				}else{
					$vars['icon_url'] = 'images/cat.gif';
				}
				$vars['icon'] = '<img border="0" src="'.$vars['icon_url'].'" />';
				
				$vars['description'] = $cats[$i]['description'];

				$counters = $this->GetCountersRecursive($id);
				$vars['count'] = $counters['files'];
				$vars['cat_count'] = $counters['cats'];

				// Выодим подкатегории
				$childs = '';
				$sub = '';
				if(isset($cats[$i][TREE_CHILD_ID])){
					for($j = 0, $k = count($cats[$i][TREE_CHILD_ID]); $j < $k; $j++){
						$child_id = SafeDB($cats[$i][TREE_CHILD_ID][$j]['id'], 11, int);
						$child_counters = $this->GetCountersRecursive($child_id);
						$link = Ufu('index.php?name='.$this->moduleName.'&'.$this->id_par_name.'='.$child_id, $this->moduleName.'/{'.$this->id_par_name.'}/');
						$sub .= '<a href="'.$link.'">'.$cats[$i][TREE_CHILD_ID][$j]['title'].'</a>'.'&nbsp;('.$child_counters['files'].'), ';
					}
					$childs .= substr($sub, 0, -2).'.';
				}
				$vars['childs_cats'] = $childs;

				$site->AddSubBlock('cats', true, $vars);
			}
			if($cat_id == 0 && function_exists($this->NumItems)){
				$text = '<br />'.$this->NumItemsCaption.call_user_func($this->NumItems);
			}else{
				$text = '';
			}
			$site->AddBlock('cat_caption', true, false, '', '', $text);
		}elseif($cat_id == 0){
			$site->AddTextBox('', '<center>Категорий пока нет.</center>');
		}
	}

	public function ShowPath( $id, $view_obj = false, $obj_title = '' )
	{
		global $site;
		$vars = array();
		$parents = array();
		$parents = $this->GetAllParent($id);
		$parent = $this->GetParentId($id);
		if($parent == 0 && !$view_obj){
			$burl = Ufu('index.php?name='.$this->moduleName, '{name}/');
		}elseif($parent != 0 && !$view_obj){
			$burl = Ufu('index.php?name='.$this->moduleName.'&'.$this->id_par_name.'='.$parent, $this->moduleName.'/{'.$this->id_par_name.'}/');;
		}elseif($view_obj == true){
			$burl = Ufu('index.php?name='.$this->moduleName.'&'.$this->id_par_name.'='.$id, $this->moduleName.'/{'.$this->id_par_name.'}/');;
		}
		$vars['back'] = '[<a href="'.$burl.'">&lt;&lt;&lt;</a>]';
		$vars['back_url'] = $burl;

		$path = '<b><a href="'.Ufu('index.php?name='.$this->moduleName, '{name}/').'">'.$this->TopCatName.'</a></b>';
		if(!$view_obj){
			$c = count($parents) - 1;
		}else{
			$c = count($parents);
		}
		for($i = 0; $i < $c; $i++){
			$link = Ufu('index.php?name='.$this->moduleName.'&'.$this->id_par_name.'='.$parents[$i]['id'], $this->moduleName.'/{'.$this->id_par_name.'}/');
			$path .= '/<a href="'.$link.'">'.$parents[$i]['title'].'</a>';
		}
		if(!$view_obj){
			$path .= '/<b>'.$parents[$c]['title'].'</b>';
		}else{
			$path .= '/<b>'.$obj_title.'</b>';
		}
		$vars['path'] = $path;

		if($site->TemplateExists('module/cat_path.html') !== false){
			$site->AddTemplatedBox('', 'module/cat_path.html');
			$site->AddBlock('cat_path', true, false, 'path');
			$site->Blocks['cat_path']['vars'] = $vars;
		}else{
			$site->AddTextBox('', $vars['back'].'&nbsp;'.$path);
		}
	}
}

?>