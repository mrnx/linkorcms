<?php

# LinkorCMS
# © 2006-2010 Галицкий Александр Николаевич (linkorcms@yandex.ru)
# Файл: tree_a.class.php
# Назначение: Высокоуровневый класс для работы с деревьями

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$AdminTreeText = '';

class AdminTree extends Tree
{
	public $obj_table = 'cats';
	public $module = '';
	public $edit_met = 'editcat';
	public $save_met = 'catsave';
	public $del_met = 'delcat';
	public $showcats_met = 'cat';
	public $action_par_name = 'a';
	public $obj_cat_coll = 'cat';
	public $id_par_name = 'id';

	// Функция для обхода дерева категорий, обрабатывает каждый элемент дерева и генерирует html
	public function HtmlTreeItem( $tree, $level )
	{
		global $config, $AdminTreeText;

		$id = SafeDB($tree['id'], 11, int);
		$counters = $this->GetCountersRecursive($id);

		$levs = str_repeat('<td class="treelevel">&nbsp; - &nbsp;</td>', $level);
		if(isset($tree[TREE_CHILD_ID]) && count($tree[TREE_CHILD_ID]) > 0){
			$img = 'images/admin/cat_open.gif';
		}else{
			$img = 'images/admin/cat_close.gif';
		}

		$func = '';
		$func .= SpeedButton('Редактировать', $config['admin_file'].'?exe='.$this->module.'&'.$this->action_par_name.'='.$this->edit_met.'&'.$this->id_par_name.'='.$id, 'images/admin/edit.png');
		$func .= SpeedButton('Удалить', $config['admin_file'].'?exe='.$this->module.'&'.$this->action_par_name.'='.$this->del_met.'&'.$this->id_par_name.'='.$id.'&ok=0', 'images/admin/delete.png');

		$AdminTreeText .= "\n".'<tr><td align="left"><table cellspacing="0" cellpadding="0" border="0" width="100%"><tr>'."\n";
		$AdminTreeText .= $levs.'<td class="treetd"><img src="'.$img.'" width="24" height="24" />&nbsp;'.SafeDB($tree['title'], 250, str).' ('.$counters['files'].')&nbsp;&nbsp;&nbsp;'.$func.'</td>';
		$AdminTreeText .= "\n".'</tr></table></td></tr>'."\n";
	}

	// Выводит дерево в html-коде для отображения в админ-панели
	public function ShowCats( $pid = 0 )
	{
		global $site, $AdminTreeText;
		$site->AddCSSFile('tree.css');
		$AdminTreeText = "\n\n".'<table cellspacing="0" cellpadding="0" class="treetable">';
		$result = $this->ListingTree($pid, array($this, 'HtmlTreeItem'));
		$AdminTreeText .= '</table>'."\n\n";
		if($result == false){
			return false;
		}else{
			return $AdminTreeText;
		}
	}

	// Редактор категорий
	public function CatEditor( $cat_id = null, $to_id = null )
	{
		global $db, $config, $site;
		$title = '';
		$desc = '';
		$icon = '';
		$parent = 0;
		$boxtitle = 'Добавление категории';
		$save_met = $this->save_met;
		if($cat_id != null){
			$db->Select($this->Table, "`id`='$cat_id'");
			$cat = $db->FetchRow();
			$title = $cat['title'];
			$desc = $cat['description'];
			$icon = $cat['icon'];
			$parent = $cat['parent'];
			$boxtitle = 'Редактирование категории';
			$save_met = $this->save_met.'&'.$this->id_par_name.'='.$cat_id;
			$cmd = 'Сохранить изменения';
		}elseif($to_id != null){
			$parent = $to_id;
			$cmd = 'Создать';
		}else{
			$parent = -1;
			$id = -1;
			$cmd = 'Создать';
		}
		$cats_data = array();
		$cats_data = $this->GetCatsData($parent, false, true, $cat_id, true);
		FormRow('В категорию', $site->Select('cat', $cats_data));
		FormRow('Имя категории', $site->Edit('title', $title, false, 'maxlength="250" style="width:400px;"'));
		FormRow('Иконка', $site->Edit('icon', $icon, false, 'maxlength="250" style="width:400px;"'));
		FormRow('Описание', $site->TextArea('desc', $desc, 'maxlength="255" style="width:400px;height:160px;"'));
		AddCenterBox($boxtitle);
		AddForm('<form action="'.$config['admin_file'].'?exe='.$this->module.'&'.$this->action_par_name.'='.$save_met.'" method="post">', $site->Button('Отмена', 'onclick="history.go(-1);"').$site->Submit($cmd));
	}

	// Сохранение категории
	public function EditorSave( $id = null )
	{
		global $db, $config;
		$title = SafeEnv($_POST['title'], 250, str);
		$desc = SafeEnv($_POST['desc'], 255, str);
		$icon = SafeEnv($_POST['icon'], 250, str);
		$parent = SafeEnv($_POST['cat'], 11, int);
		if($id == null){
			$query = Values('', $title, $desc, $icon, 0, 0, $parent);
			$db->Insert($this->Table, $query);
			$this->CalcCatCounter($parent, true);
		}else{
			if(in_array($id, $this->GetAllChildId($id))){
				$query = "title='$title',description='$desc',icon='$icon',parent='$parent'";
				$db->Update($this->Table, $query, "`id`='$id'");
			}
		}
		$cache = LmFileCache::Instance();
		$cache->Delete('tree', $this->Table);
	}

	// Удаление категории
	public function DeleteCat( $id )
	{
		global $config, $db;
		if(isset($_GET['ok']) && SafeEnv($_GET['ok'], 1, int) == '1'){
			$r = $db->Select($this->Table, "`id`='$id'");
			$childs = $this->GetAllChildId($id);
			for($i = 0, $c = count($childs); $i < $c; $i++){
				$db->Delete($this->obj_table, "`$this->obj_cat_coll`='".$childs[$i]."'");
				$db->Delete($this->Table, "`id`='".$childs[$i]."'");
			}
			$this->CalcCatCounter($r[0]['parent'], false);
			$cache = LmFileCache::Instance();
			$cache->Delete('tree', $this->Table);
			return true;
		}else{
			$r = $db->Select($this->Table, "`id`='".SafeEnv($_GET['id'], 11, int)."'");
			$text = 'Вы действительно хотите удалить категорию "'.$r[0]['title'].'".'
			.' Все вложенные категории и файлы будут удалены. Продолжить?<br />'
			.'<a href="'.$config['admin_file'].'?exe='.$this->module.'&'.$this->action_par_name.'='.$this->del_met.'&'.$this->id_par_name.'='.SafeEnv($_GET[$this->id_par_name], 11, int).'&ok=1">Да</a>'
			.' &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">Нет</a>';
			AddTextBox('Внимание', $text);
			return false;
		}
	}
}

?>