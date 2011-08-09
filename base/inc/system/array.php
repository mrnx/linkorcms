<?php

/**
 * Функции для работы с массивами
 */

/**
 * Функция возвращает часть массива, как если бы
 * он был разбит на определенное количество частей.
 * Иногда удобно использовать для организации постраничной навигации.
 * @param  $ObjArray Массив
 * @param  $OnPage Количество элементов массива в одной части
 * @param  $Page Какую часть вернуть начиная с единицы
 * @return array
 */
function ArrayPage( &$ObjArray, $OnPage, $Page ){
	$pages_count = ceil(count($ObjArray) / $OnPage);
	if($Page < 1){
		$Page = 1;
	}elseif($Page > $pages_count){
		$Page = $pages_count;
	}
	$start = $OnPage * $Page - $OnPage;
	return array_slice($ObjArray, $start, $OnPage);
}

/**
 * Сортирует массив по ключу. Для вывода массива отсортированного этой функцией
 * рекомендуется использовать цикл для обхода массива foreach.
 *
 * @param Array $Array // массив типа $array = array(array(col1=1,col2=2,...),arr...)
 * массивы такого вида выдаются при запросах к БД
 * @param Integer $Coll // номер колонки в массиве по которой его сортировать
 * @param Boolean $OnDecrease // если true то сортировка будет осуществляться в обратном порядке
 * @return void
 */
function SortArray( &$Array, $Coll, $OnDecrease=false ){
	global $SATempVar;
	if(!function_exists('SorterUp')){
		function SorterUp($a,$b)
		{
			global $SATempVar;
			if ($a[$SATempVar] == $b[$SATempVar]) return 0;
			return ($a[$SATempVar] < $b[$SATempVar]) ? -1 : 1;
		}

		function SorterDown($a,$b)
		{
			global $SATempVar;
			if ($a[$SATempVar] == $b[$SATempVar]) return 0;
			return ($a[$SATempVar] > $b[$SATempVar]) ? -1 : 1;
		}
	}
	$SATempVar = $Coll;
	if(!$OnDecrease){
		usort($Array, 'SorterUp');
	}else{
		usort($Array, 'SorterDown');
	}
	unset($SATempVar);
}

/**
 * Добавляет элемент в определенную позицию массива
 * @param $Array
 * @param $Value
 * @param null $AfterKey
 * @param null $Key
 * @return array
 */
function InsertToArray( $Array, $Value, $AfterKey = null, $Key = null ){
	$newarray = array();
	foreach($Array as $sk=>$sv){
		if($Key == null){
			$newarray[] = $sv;
		}else{
			$newarray[$sk] = $sv;
		}
		if($sk == $AfterKey){
			if($Key == null){
				$newarray[] = $Value;
			}else{
				$newarray[$Key] = $Value;
			}
		}
	}
	return $newarray;
}

/**
 * Добавляет элемент в начало массива.
 * @param $Array
 * @param $Value
 * @param null $Key
 * @return array
 */
function InsertToArrayFirst( $Array, $Value, $Key = null){
	$newarray = array();
	if($Key == null){
		$newarray[] = $Value;
	}else{
		$newarray[$Key] = $Value;
	}
	foreach($Array as $sk=>$sv){
		if($Key == null){
			$newarray[] = $sv;
		}else{
			$newarray[$sk] = $sv;
		}
	}
	return $newarray;
}

/**
 * Удаляет элемент с определенным ключом из массива
 * @param $Array Массив
 * @param $Key Ключ элемента который нужно удалить
 * @param bool $SaveKeys Сохранять ключи элементов исходного массива
 * @return void
 */
function DeleteFromArray( $Array, $Key, $SaveKeys = true ){
	$newarray = array();
	foreach($Array as $k=>$row){
		if($k == $Key) continue;
		if($SaveKeys){
			$newarray[$k] = $row;
		}else{
			$newarray[] = $row;
		}
	}
	return $newarray;
}

