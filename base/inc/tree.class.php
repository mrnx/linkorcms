<?php

# LinkorCMS
# � 2006-2010 �������� ��������� ���������� (linkorcms@yandex.ru)
# ����: tree.class.php
# ����������: ����� ��� ������ � ��������� �� ������ ������

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

define("TREE_CHILD_ID", 'child');
$FCatsData = array();

/**
 * ����� ��� ���������� ������ ��������� �� ������� ���� ������.
 * @author ���������
 */
class Tree{

	/**
	 * ��� ������� � �����������
	 * @var string
	 */
	public $Table;

	/**
	 * ������ �� ������������ ��������
	 * @var array
	 */
	public $Cats;

	/**
	 * ������ �� ����������� ��������
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
	 * ��� ��������� ��������
	 */
	public $TopCatName = ' /';

	/**
	 * ����������� �������, ��������� ������
	 *
	 * @param variable $Table ������ ������� ������� ��� ��� ������� ������
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
		if(!is_array($Table)){  // ���� �������� ��� �������
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
				// ���������� ���
				$cats_cache = array(&$cats, &$cats2);
				$cache->Write('tree', $Table, $cats_cache);
			}
		}else{ // ������� ������ ��� �������� ���������� ���������� ������ �� ��������� ���������
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
	 * ���������� �������� ������ ���������
	 *
	 * @param int $parent_id ������������� ������������� �������
	 * @param array $source ������ �������� ��������������� ��
	 * ������������ ��������������� ��� ���������� � ���� ������
	 * @return array
	 */
	public function NewTree( $parent_id, $source ){
		//���� ���� ��������
		if(isset($source[$parent_id])){
			//������� ��� �������� ��������
			for($i = 0, $c = count($source[$parent_id]); $i < $c; $i++){
				// �������
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
	 * ���������� �������� ������ ���������
	 *
	 * @param int $pid ������������� ������������� �������
	 * @return array ������ ��������, � ������� ������� ����
	 * ���� TREE_CHILD_ID � �������� �������� ��������
	 */
	public function GetChildTree( $pid ){
		return $this->NewTree($pid, $this->Cats);
	}

	/**
	 * ������� ������ � ������� ���������������� �������
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
	 * ������� ������ � ������� ���������������� �������
	 * ������ ���������������� �������
	 * function UserFunc($cat, $level){
	 *  $cat - ������� ������� ������
	 *  $level - ������� �����������
	 * }
	 *
	 * @param int $pid ������������� ������������� ������� � �������� ����� �������� �����
	 * @param string|array $callbackFunc ��� ������� ��� ������ � ������ ������ � ������
	 * @return bool ���������� true � ������ ��������� ������
	 */
	public function ListingTree( $pid, $callbackFunc ){
		$tree = $this->GetChildTree($pid);
		if((!is_array($callbackFunc) && !@function_exists($callbackFunc)) || (is_array($callbackFunc) && !@method_exists($callbackFunc[0], $callbackFunc[1]))){
			ErrorHandler(NOTICE, 'CallBack ������� �� ��������������.', 'Tree->ListingTree()');
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
	 * ��������� �������, ������������ � GetAllChildId
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
	 * ���������� ��� ��������� ������� �������� ������.
	 * ��� �� ���������� ������ ������������� �������� $pid.
	 *
	 * @param int $pid ������������� �������
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
	 * ���������� ������������� ������������� ��������
	 *
	 * @param int $curId ������������� �������
	 * @return int ������������� ������������� �������
	 */
	public function GetParentId( $curId ){
		if(isset($this->IdCats[$curId])){
			return $this->IdCats[$curId][$this->ParentKey];
		}else{
			return 0;
		}
	}

	/**
	 * ������� ���� �� ��������
	 *
	 * @param int $curId ������������� �������
	 * @param bool $reverse � �������� �������
	 * @return array|bool ������ �������� � ������������ �������
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
	 * ����������� ������ ���������� ��������� � ��������
	 * ��������� ��������� � ��������� ��������� ������
	 *
	 * @param int $id ������������� �������
	 * @param int $inc �������� ����������
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

			// ������� ���
			LmFileCache::Instance()->Delete('tree', $this->Table);
		}
	}

	/**
	 * ������������ ���������� �������� ��������� � ��������
	 *
	 * @param int $id ������������� �������
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
	 * ����������� ������ ���������� ������������ � ��������
	 * ��������� ��������� � ��������� ��������� ������
	 *
	 * @param int $id ������������� �������
	 * @param int $inc �������� ����������
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

			// ������� ���
			LmFileCache::Instance()->Delete('tree', $this->Table);
		}
	}

	/**
	 * ��� ��������� ������� ������������ � GetCatsData
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
	 * ���������� ������ ����� ��� ����������� ������ � ���� ������
	 *
	 * @param int $sel_id ������������� ����������� ��������
	 * @param boolean $viewitems ���������� ����� � ������� ���������� �������� ��������
	 * @param boolean $root �������� �������� �������
	 * @param int $id ������������� ������ � ������ ��������������� � ��� ��� �������� ��������
	 * @param boolean $xor �������� ������
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
