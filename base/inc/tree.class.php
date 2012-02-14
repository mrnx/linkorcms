<?php

# LinkorCMS
# © 2006-2010 Галицкий Александр Николаевич (linkorcms@yandex.ru)
# Файл: tree.class.php
# Назначение: Класс для работы с деревьями на низком уровне

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

define("TREE_CHILD_ID", 'child');
$FCatsData = array();

/**
 * Класс для построения дерева категорий по таблице базы данных.
 * @author Александр
 */
class Tree{

	/**
	 * Имя таблицы с категориями
	 * @var string
	 */
	public $Table;

	/**
	 * Записи по родительским индексам
	 * @var array
	 */
	public $Cats;

	/**
	 * Записи по собственным индексам
	 * @var array
	 */
	public $IdCats;

	public $childs = array();
	public $sel_id = 0;

	public $IdKey = 'id';
	public $ParentKey = 'parent';
	public $TitleKey = 'title';
	public $FileCounterKey = 'file_counter';
	public $CatCounterKey = 'cat_counter';

	/*
	 * Имя корневого элемента
	 */
	public $TopCatName = ' /';

	/**
	 * Конструктор объекта, загружает дерево
	 *
	 * @param variable $Table Массив записей таблицы или имя таблицы дерева
	 * @param string $IdKey
	 * @param string $ParentKey
	 * @param string $TitleKey
	 * @param string $FileCounterKey
	 * @param string $CatCounterKey
	 * @return \Tree
	 */
	function  __construct( $Table, $IdKey = 'id', $ParentKey = 'parent', $TitleKey = 'title', $FileCounterKey = 'file_counter',
			$CatCounterKey = 'cat_counter')
	{
		global $db;
		$this->IdKey = $IdKey;
		$this->ParentKey = $ParentKey;
		$this->TitleKey = $TitleKey;
		$this->FileCounterKey = $FileCounterKey;
		$this->CatCounterKey = $CatCounterKey;

		$cats = array();
		$cats2 = array();
		if(!is_array($Table)){  // Если передано имя таблицы
			$this->Table = $Table;
			$cache = LmFileCache::Instance();
			if($cache->HasCache('tree', $Table)){
				$cats_cache = $cache->Get('tree', $Table);
				$cats = &$cats_cache[0];
				$cats2 = &$cats_cache[1];
			}else{
				$db->Select($Table);
				foreach($db->QueryResult as $cat){
					$cats[$cat[$ParentKey]][] = $cat;
					$cats2[$cat[$IdKey]] = $cat;
				}
				// Записываем кеш
				$cats_cache = array(&$cats, &$cats2);
				$cache->Write('tree', $Table, $cats_cache);
			}
		}else{ // Передан массив для быстрого построения статичного дерева из выбранных элементов
			$this->Table = '';
			foreach($Table as $cat){
				$cats[$cat[$ParentKey]][] = $cat;
				$cats2[$cat[$IdKey]] = $cat;
			}
		}

		$this->Cats = &$cats;
		$this->IdCats = &$cats2;
	}

	/**
	 * Возвращает дочернее дерево элементов
	 *
	 * @param int $parent_id Идентификатор родительского объекта
	 * @param array $source Массив объектов отсортированных по
	 * родительским идентификаторам для сортировки в виде дерева
	 * @return array
	 */
	public function NewTree( $parent_id, $source ){
		//Если есть дочерние
		if(isset($source[$parent_id])){
			//Обходим все дочерние каталоги
			for($i = 0, $c = count($source[$parent_id]); $i < $c; $i++){
				// Элемент
				$cat = $source[$parent_id][$i];
				if(isset($source[$cat['id']])){
					$cat[TREE_CHILD_ID] = $this->NewTree($cat['id'], $source);
				}
				$tree[] = $cat;
			}
			return $tree;
		}else{
			return array();
		}
	}

	/**
	 * Возвращает дочернее дерево элементов
	 *
	 * @param int $pid Идентификатор родительского объекта
	 * @return array Массив объектов, у каждого объекта есть
	 * поле TREE_CHILD_ID с массивом дочерних объектов
	 */
	public function GetChildTree( $pid ){
		return $this->NewTree($pid, $this->Cats);
	}

	/**
	 * Обходит дерево с вызовом пользовательской функции
	 *
	 * @param array $tree
	 * @param int $level
	 * @param string|array $callbackFunc
	 * @see ListingTree
	 * @return void
	 */
	protected function ToCatsTree( $tree, $level, $callbackFunc ){
		if(isset($tree[TREE_CHILD_ID])){
			for($i = 0, $c = count($tree[TREE_CHILD_ID]); $i < $c; $i++){
				call_user_func_array($callbackFunc, array($tree[TREE_CHILD_ID][$i], $level));
				$this->ToCatsTree($tree[TREE_CHILD_ID][$i], $level + 1, $callbackFunc);
			}
		}
	}

	/**
	 * Обходит дерево с вызовом пользовательской функции
	 * Пример пользовательской функции
	 * function UserFunc($cat, $level){
	 *  $cat - Текущий элемент дерева
	 *  $level - Уровень вложенности
	 * }
	 *
	 * @param int $pid Идентификатор родительского объекта с которого нужно начинать обход
	 * @param string|array $callbackFunc Имя функции или массив с именем класса и метода
	 * @return bool Возвращает true в случае успешного обхода
	 */
	public function ListingTree( $pid, $callbackFunc ){
		$tree = $this->GetChildTree($pid);
		if((!is_array($callbackFunc) && !@function_exists($callbackFunc)) || (is_array($callbackFunc) && !@method_exists($callbackFunc[0], $callbackFunc[1]))){
			ErrorHandler(NOTICE, 'CallBack функция не предопределена.', 'Tree->ListingTree()');
			return false;
		}else{
			if(count($tree) == 0){
				return false;
			}
			for($i = 0, $c = count($tree); $i < $c; $i++){
				call_user_func_array($callbackFunc, array($tree[$i], 0));
				$this->ToCatsTree($tree[$i], 1, $callbackFunc);
			}
			return true;
		}
	}

	/**
	 * Системная функция, используется в GetAllChildId
	 *
	 * @param array $tree
	 * @param array $result
	 * @see GetAllChildId
	 * @return void
	 */
	protected function GetIds( $tree, &$result ){
		if(isset($tree[TREE_CHILD_ID])){
			for($i = 0, $c = count($tree[TREE_CHILD_ID]); $i < $c; $i++){
				$result[] = $tree[TREE_CHILD_ID][$i][$this->IdKey];
				$this->GetIds($tree[TREE_CHILD_ID][$i], $result);
			}
		}
	}

	/**
	 * Возвращает все вложенные индексы элемента дерева.
	 * Так же возвращает индекс родительского каталога $pid.
	 *
	 * @param int $pid Идентификатор объекта
	 * @return array
	 */
	public function GetAllChildId( $pid ){
		$tree = $this->GetChildTree($pid);
		$result = array();
		$c = count($tree);
		if($c > 0){
			for($i = 0; $i < $c; $i++){
				$result[] = $tree[$i][$this->IdKey];
				$this->GetIds($tree[$i], $result);
			}
		}
		$result[] = $pid;
		return $result;
	}

	/**
	 * Возвращает идентификатор родительского элемента
	 *
	 * @param int $curId Идентификатор объекта
	 * @return int Идентификатор родительского объекта
	 */
	public function GetParentId( $curId ){
		if(isset($this->IdCats[$curId])){
			return $this->IdCats[$curId][$this->ParentKey];
		}else{
			return 0;
		}
	}

	/**
	 * Выводит путь до элемента
	 *
	 * @param int $curId Идентификатор объекта
	 * @param bool $reverse В обратном порядке
	 * @return array|bool Массив объектов в определенном порядке
	 */
	public function GetAllParent( $curId, $reverse = true ){
		if(count($this->IdCats) > 0){
			$find = true;
			$result = array();
			$result2 = array();
			while($find){
				if(isset($this->IdCats[$curId])){
					$result[] = $this->IdCats[$curId];
					$curId = $this->IdCats[$curId][$this->ParentKey];
				}else{
					$find = false;
				}
			}
			if($reverse){
				$c = count($result) - 1;
				for($i = $c; $i >= 0; $i--){
					$result2[] = $result[$i];
					unset($result[$i]);
				}
				$result = $result2;
			}
			return $result;
		}else{
			return false;
		}
	}

	/**
	 * Увеличивает индекс количества элементов в каталоге
	 * используя параметры и указанные настройки класса
	 *
	 * @param int $id Идентификатор объекта
	 * @param int $inc Значение инкремента
	 * @return void
	 */
	public function CalcFileCounter( $id, $inc ){
		global $db;
		$db->Select($this->Table, "`".$this->IdKey."`='$id'");
		if($db->NumRows() > 0){
			$cat = $db->FetchRow();
			if($inc === true){
				$counter_val = $cat[$this->FileCounterKey] + 1;
			}elseif($inc === false){
				$counter_val = $cat[$this->FileCounterKey] - 1;
			}else{
				$counter_val = $cat[$this->FileCounterKey] + intval($inc);
			}
			$db->Update($this->Table, $this->FileCounterKey."='$counter_val'", "`".$this->IdKey."`='$id'");

			// Очищаем кеш
			LmFileCache::Instance()->Delete('tree', $this->Table);
		}
	}

	/**
	 * Подсчитывает количество дочерних категорий и объектов
	 *
	 * @param int $id Идентификатор объекта
	 * @return array
	 */
	public function GetCountersRecursive( $id ){
		$childs = $this->GetAllChildId($id);
		$file_counter = 0;
		$cat_counter = 0;
		foreach($childs as $id){
			$file_counter += $this->IdCats[$id][$this->FileCounterKey];
			$cat_counter += $this->IdCats[$id][$this->CatCounterKey];
		}
		return array('files'=>$file_counter, 'cats'=>$cat_counter);
	}

	/**
	 * Увеличивает индекс количества субкаталогов в каталоге
	 * используя параметры и указанные настройки класса
	 *
	 * @param int $id Идентификатор объекта
	 * @param int $inc Значение инкремента
	 * @return void
	 */
	public function CalcCatCounter( $id, $inc ){
		global $db;
		$db->Select($this->Table, "`".$this->IdKey."`='$id'");
		if($db->NumRows() > 0){
			$cat = $db->FetchRow();
			if($inc === true){
				$counter_val = $cat[$this->CatCounterKey] + 1;
			}elseif($inc === false){
				$counter_val = $cat[$this->CatCounterKey] - 1;
			}else{
				$counter_val = $cat[$this->CatCounterKey] + intval($inc);
			}
			$db->Update($this->Table, $this->CatCounterKey."='$counter_val'", "`".$this->IdKey."`='$id'");

			// Очищаем кеш
			LmFileCache::Instance()->Delete('tree', $this->Table);
		}
	}

	/**
	 * Это системная фунцкци используется в GetCatsData
	 *
	 * @param  $tree
	 * @param  $level
	 * @return void
	 */
	public function CatsData( $tree, $level ){
		global $site, $FCatsData;
		if(in_array($tree[$this->IdKey], $this->childs) === false){
			$levs = str_repeat('&nbsp;-&nbsp;', $level);
			$title = $levs.$tree[$this->TitleKey];
			if($this->viewitems && isset($tree[$this->FileCounterKey]) && $tree[$this->FileCounterKey] > 0){
				$ccc = $this->GetCountersRecursive($tree[$this->IdKey]);
				$title .= ' ('.$ccc['files'].')';
			}
			$site->DataAdd($FCatsData, $tree[$this->IdKey], $title, ($tree[$this->IdKey] == $this->sel_id));
		}
	}

	/**
	 * Возвращает данные формы для выпадающего списка в виде дерева
	 *
	 * @param int $sel_id Идентификатор выделенного элемента
	 * @param boolean $viewitems Показывать рядом в скобках количество дочерних объектов
	 * @param boolean $root Добавить корневой элемент
	 * @param int $id Отфильтровать объект с данным идентификатором и все его дочерние элементы
	 * @param boolean $xor Включить фильтр
	 * @return array
	 */
	public function GetCatsData( $sel_id, $viewitems = false, $root = false, $id = 0, $xor = false ){
		global $site, $FCatsData;
		$this->childs = array();
		$this->sel_id = $sel_id;
		$this->viewitems = $viewitems;
		if($xor){
			$this->childs = $this->GetAllChildId($id);
		}
		if($root){
			$site->DataAdd($FCatsData, '0', $this->TopCatName, $sel_id == 0);
		}
		$this->ListingTree(0, array($this, 'CatsData'));
		return $FCatsData;
	}
}
