<?php

# LinkorCMS
# © 2006-2010 Галицкий Александр Николаевич (linkorcms@yandex.ru)
# Файл: tree_a.class.php
# Назначение: Высокоуровневый класс для работы с деревьями

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

/**
 * Высокоуровневый класс для работы с деревьями
 */
class AdminTree extends Tree{

	public $obj_table = 'cats';
	public $module = '';
	public $edit_met = 'editcat';
	public $save_met = 'catsave';
	public $del_met = 'delcat';
	public $showcats_met = 'cat';
	public $action_par_name = 'a';
	public $obj_cat_coll = 'cat';
	public $id_par_name = 'id';

	/**
	 * Выводит дерево в html-коде для отображения в админ-панели
	 * @param int $pid
	 * @return bool|string
	 */
	public function ShowCats( $pid = 0 ){
		UseScript('jquery_ui_treeview');
		if($pid == 0 && isset($_GET['_cat_parent'])){
			$pid = SafeEnv($_GET['_cat_parent'], 11, int);
		}
		$elements = array();
		foreach($this->Cats[$pid] as $cat){
			$id = SafeDB($cat['id'], 11, int);
			if(trim($cat['icon']) != ''){
				$info = '<img src="'.SafeDB($cat['icon'], 255, str).'">';
			}else{
				$info = '';
			}
			$icon = 'images/folder.png';

			$add_cat_link = ADMIN_FILE.'?exe='.$this->module.'&'.$this->action_par_name.'='.$this->edit_met.'&_cat_adto='.$id;
			$edit_cat_link = ADMIN_FILE.'?exe='.$this->module.'&'.$this->action_par_name.'='.$this->edit_met.'&'.$this->id_par_name.'='.$id;

			$func = '';
			$func .= System::admin()->SpeedButton('Добавить дочернюю категорию', $add_cat_link, 'images/admin/folder_add.png');
			$func .= System::admin()->SpeedButton('Редактировать', $edit_cat_link, 'images/admin/edit.png');
			$func .= System::admin()->SpeedConfirmJs(
				'Удалить категорию',
				'$(\'#cats_tree_container\').treeview(\'deleteNode\', '.$id.');',
				'images/admin/delete.png',
				'Уверены что хотите удалить? Все дочерние объекты так-же будут удалены.'
			);

			$obj_counts = $this->GetCountersRecursive($id);
			$elements[] = array(
				'id'=>$id,
				'icon'=>$icon,
				'title'=>'<b>'.System::admin()->Link(SafeDB($cat['title'], 255, str).' ('.$obj_counts['files'].')', $edit_cat_link).'</b>',
				'info'=>$info,
				'func'=>$func,
				'isnode'=>isset($this->Cats[$id]),
				'child_url'=>ADMIN_FILE.'?exe='.$this->module.'&'.$this->action_par_name.'='.$this->showcats_met.'&_cat_parent='.$id
			);
		}
		if($pid == 0){
			return '<div id="cats_tree_container"></div><script>$("#cats_tree_container").treeview({del: \''.ADMIN_FILE.'?exe='.$this->module.'&'.$this->action_par_name.'='.$this->del_met.'&ok=1\', delRequestType: \'GET\', tree: '.JsonEncode($elements).'});</script>';
		}else{
			echo JsonEncode($elements);
			exit;
		}
	}

	/**
	 * Редактор категорий
	 * @param null $cat_id
	 * @param null $to_id
	 */
	public function CatEditor( $cat_id = null, $to_id = null ){
		global $site;
		$title = '';
		$desc = '';
		$icon = '';
		$parent = 0;
		$boxtitle = 'Добавить категорию';
		$save_met = $this->save_met;
		if($cat_id != null){
			System::database()->Select($this->Table, "`id`='$cat_id'");
			$cat = System::database()->FetchRow();
			$title = SafeDB($cat['title'], 255, str);
			$desc = SafeDB($cat['description'], 255, str);
			$icon = SafeDB($cat['icon'], 255, str);
			$parent = SafeDB($cat['parent'], 11, int);
			$boxtitle = 'Редактирование категории';
			$save_met = $this->save_met.'&'.$this->id_par_name.'='.$cat_id;
			$cmd = 'Сохранить изменения';
		}else{
			$parent = -1;
			$id = -1;
			$cmd = 'Добавить';
			if($to_id != null){
				$parent = $to_id;
			}elseif(isset($_GET['_cat_adto'])){
				$parent = SafeEnv($_GET['_cat_adto'], 11, int);
			}
		}
		$cats_data = array();
		$cats_data = $this->GetCatsData($parent, false, true, $cat_id, true);
		FormRow('В категорию', $site->Select('cat', $cats_data));
		FormRow('Имя категории', $site->Edit('title', $title, false, 'maxlength="250" style="width:400px;"'));
		FormRow('Иконка', $site->Edit('icon', $icon, false, 'maxlength="250" style="width:400px;"'));
		FormRow('Описание', $site->TextArea('desc', $desc, 'maxlength="255" style="width:400px;height:160px;"'));
		AddCenterBox($boxtitle);
		AddForm('<form action="'.ADMIN_FILE.'?exe='.$this->module.'&'.$this->action_par_name.'='.$save_met.'" method="post">', $site->Button('Отмена', 'onclick="history.go(-1);"').$site->Submit($cmd));
	}

	/**
	 * Сохранение категории
	 * @param null $id
	 */
	public function EditorSave( $id = null ){
		$title = SafeEnv($_POST['title'], 250, str);
		$desc = SafeEnv($_POST['desc'], 255, str);
		$icon = SafeEnv($_POST['icon'], 250, str);
		$parent = SafeEnv($_POST['cat'], 11, int);
		if($id == null){
			$query = Values('', $title, $desc, $icon, 0, 0, $parent);
			System::database()->Insert($this->Table, $query);
			$this->CalcCatCounter($parent, true);
		}else{
			if(in_array($id, $this->GetAllChildId($id))){
				$query = "title='$title',description='$desc',icon='$icon',parent='$parent'";
				System::database()->Update($this->Table, $query, "`id`='$id'");
			}
		}
		LmFileCache::Instance()->Delete('tree', $this->Table);
	}

	/**
	 * Удаление категории
	 * @param $id
	 * @return bool
	 */
	public function DeleteCat( $id ){
		$r = System::database()->Select($this->Table, "`id`='$id'");
		$childs = $this->GetAllChildId($id);
		for($i = 0, $c = count($childs); $i < $c; $i++){
			System::database()->Delete($this->obj_table, "`$this->obj_cat_coll`='".$childs[$i]."'");
			System::database()->Delete($this->Table, "`id`='".$childs[$i]."'");
		}
		$this->CalcCatCounter($r[0]['parent'], false);
		LmFileCache::Instance()->Delete('tree', $this->Table);
		return true;
	}

} // End Class;
