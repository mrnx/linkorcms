<?php

/**
 * ������� ��� ������ � ���������
 */

/**
 * ������� ���������� ����� �������, ��� ���� ��
 * �� ��� ������ �� ������������ ���������� ������.
 * ������ ������ ������������ ��� ����������� ������������ ���������.
 * @param  $ObjArray ������
 * @param  $OnPage ���������� ��������� ������� � ����� �����
 * @param  $Page ����� ����� ������� ������� � �������
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
 * ��������� ������ �� �����. ��� ������ ������� ���������������� ���� ��������
 * ������������� ������������ ���� ��� ������ ������� foreach.
 *
 * @param Array $array // ������ ���� $array = array(array(col1=1,col2=2,...),arr...)
 * ������� ������ ���� �������� ��� �������� � ��
 * @param Integer $coll // ����� ������� � ������� �� ������� ��� �����������
 * @param Boolean $OnDecrease // ���� true �� ���������� ����� �������������� � �������� �������
 * @return void
 */
function SortArray( &$array, $coll, $OnDecrease=false ){
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
	$SATempVar = $coll;
	if(!$OnDecrease){
		usort($array, 'SorterUp');
	}else{
		usort($array, 'SorterDown');
	}
	unset($SATempVar);
}

/**
 * ��������� ������� � ������������ ������� �������
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
 * ��������� ������� � ������ �������
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
}

