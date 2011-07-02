<?php

$Parser_WhereCache = array();
$Parser_SetCache = array();
$Parser_UseCache = true;

/**
 * ������ ������ �������� ��� ���� ������ �� ���������� ����������.
 * ��������� ������ ���� �������������� ��������������� � ������� ������� SafeEnv ��� SafeR.
 *
 * @return string
 * @example Values('a','b','c'); => "'a','b','c'";
 */
function Values(){
	$args = func_get_args();
	if(is_array($args[0])){
		$args = $args[0];
	}
	$result = '';
	foreach($args as $var){
		$result .= ",'".$var."'";
	}
	return substr($result, 1);
}

/**
 * ���������� SET ������ �� ���������� ������������� ��������.
 * �������� ������ ���� �������������� ��������������� � ������� ������� SafeEnv ��� SafeR.
 *
 * @return string
 */
function MakeSet(){
	$args = func_get_args();
	$set = '';
	foreach($args as $a){
		foreach($a as $name=>$value){
			$set .= ",`$name`='$value'";
		}
	}
	return substr($set, 1);
}

/**
 * �������� � ������ ����� ������ �������� �� ����������.
 * ������ ������������ ��� ������������ VALUES �������.
 *
 * @param string $Values ������ ���� "'','login','pass','order'"
 * @return string
 */
function MakeValues( $Values ){
	$num = func_num_args();
	for($i = 1; $i < $num; $i++){
		$a = func_get_arg($i);
		foreach($a as $name=>$value){
			$Values = str_replace($name, $value, $Values);
		}
	}
	return $Values;
}

/**
 * ����������� SET ������ ������� � ������������� ������.
 *
 * @param string $set ������ ���� "name='name',login='root',pass=''".
 * @param array $row ������������� ������ �� �������� ���� �������.
 * @param array $info ��������� ���������� � �������.
 * @return array ������������� ������ �������� ���� array('name','root','').
 */
function Parser_ParseSetStr( &$set, &$row, &$info ){
	$s = str_replace("\\'",'<&#39;>',$set);
	$maxlength=count($info['cols']);
	for($i=0;$i<$maxlength;$i++){
		$args[$i] = $row[$i];
		$names[$info['cols'][$i]['name']] = $i;
	}
	for($i=0;$i<$maxlength;$i++){
		$pos = strpos($s, '='); //���� ������ ��������� =
		if($pos===false){
			break;
		}
		$col = trim(substr($s, 0, $pos));
		if(substr($col, 0, 1)=='`'){
			$col = substr($col, 1, strlen($col)-2);
		}
		$s = substr($s,$pos+1);
		$pos = strpos($s,"'");
		$s = substr($s,$pos+1);
		$pos = strpos($s,"'");
		$val = substr($s,0,$pos);
		$s = substr($s,$pos+1);
		$pos = strpos($s,",");
		$s = substr($s,$pos+1);
		if(isset($names[$col])){
			$val = str_replace('<&#39;>',"'",$val);
			$args[$names[$col]] = $val;
		}else{
			echo "������ � ���������� �������. ���� ".$col." � ������� �� �������!";
			return $row;
		}
	}
	return $args;
}

/**
 * ����������� SET ������ ������� � ������������� ������.
 *
 * @param string $values ������ ���� "'name','root',''".
 * @param array $Info ��������� ���������� � �������.
 * @param bool $isUpdateMethod ����������, ������ �� �� ������������� �� ���� ����� �������� ����������.
 * @param bool $lastvals �������� ������� ���� �� ����������, ��� ���������� ������.
 * @return ������������� ������ �������� ���� array('name','root','').
 */
function Parser_ParseValuesStr(&$values, &$Info, $isUpdateMethod = false, $lastvals = false){
	$values2 = str_replace("\\'",'<&#39;>',$values);
	$values2 = trim($values2);
	$maxlength = Count($Info['cols']);
	for($i=0; $i<$maxlength; $i++){
		$pos = strpos($values2, "'");
		if($pos === false){
			break;
		}
		$values2 = substr($values2, $pos + 1);
		$pos = strpos($values2, "'");
		$val = substr($values2, 0, $pos);
		$values2 = substr($values2, $pos + 1);

		if((isset($Info['cols'][$i]['auto_increment']))&&($Info['cols'][$i]['auto_increment'])){
			if(!$isUpdateMethod){
				$args[$i] = $Info['counter']+1;
				continue;
			}else{
				if(isset($lastvals[$i])){
					$args[$i] = $lastvals[$i];
					continue;
				}
			}
		}
		$val = str_replace('<&#39;>',"'",$val);
		$args[$i] = $val;
	}
	return $args;
}

/**
 * ��������� ������� WHERE ������� ��������������� ���������� SQL.
 *
 * @param  $where ������ ���� "`pass`='1' and `login`='admin'"
 * @param  $row ������������� ������ �� �������� ���� �������.
 * @param  $info ��������� ���������� � �������.
 * @param int $index ���������� ����� ������ � �������. ��������� ���������� `index`,
 * ������� ����� ������������ � �������.
 * @return bool ���������� ���������� ��������, ��������������� ���������� ������������ �������.
 */
function Parser_ParseWhereStr( $where, $row, $info, $index = 0 ){
	if($where == ''){ return true; };
	global $Parser_UseCache, $Parser_WhereCache;
	$vars = array();
	// �������� ���������� � ����� �������
	// fixme: ����� ���������� ��� �������� ������� ����
	for($j=0,$ccnt=count($info['cols']);$j<$ccnt;$j++){
		$n = $info['cols'][$j]['name'];
		$r = str_replace('&#13', "\r", $row[$j]); // ����� ������ �������������� ������ �� $db->Select
		$r = str_replace('&#10', "\n", $r);
		$vars[$n] = $r;
		$names[] = $n;
	}
	$vars['index'] = $index;
	$names[] = 'index';
	$ccnt++;
	// ��������� ���� �� ����� ��������� �������������� � ����������� ����
	if(isset($Parser_WhereCache[$where])){
		return Parser_ParseWhereStr2($Parser_WhereCache[$where], $vars);
	}
	// ����� ������� ������ �� �������� � ������������� ��� � PHP ������ �������� �������������� �������
	$where2 = str_replace("\\'", '<&#39;>', $where);
	$chs = explode("'", $where2);
	for($i=1,$cnt=count($chs)+1; $i<$cnt; $i++){
		if($i%2>0){
			$chs[$i-1] = str_replace("=", "==", $chs[$i-1]);
			for($j=0;$j<$ccnt;$j++){
				$chs[$i-1] = str_replace('`'.$names[$j].'`', '$'.$names[$j], $chs[$i-1]);
			}
		}else{
			// ���������� �������������
			$chs[$i-1] = str_replace("\\", "\\\\", $chs[$i-1]);
		}
	}
	$where2 = implode("'", $chs);
	$where2 = str_replace('<&#39;>', "\\'", $where2);
	// ���������� ��������� �������������� � ����������� ���
	if($Parser_UseCache){
		$Parser_WhereCache[$where] = $where2;
	}
	return Parser_ParseWhereStr2($where2, $vars);
}

function Parser_ParseWhereStr2(){
	extract(func_get_arg(1), EXTR_OVERWRITE);
	eval('if('.func_get_arg(0).'){ $result = true; }else{ $result = false; }');
	return $result;
}

/**
 * ���������� ������ � ����������� � ������, ������� �������� ������ ��� ������ � ��.
 *
 * @param string $cname ��� ������ (������ SQL).
 * @param string $type ��� (������ SQL).
 * @param int $length ������������ �����.
 * @param bool $auto_increment �������������� ���������� (���������) ��������.
 * @param string $default �������� ������ �� ���������.
 * @param string $attributes �������� (������ SQL).
 * @param bool $notnull �� ����� ���� null.
 * @param bool $primary ������ ������� �� ���������.
 * @param bool $index ������� ��������������.
 * @param bool $unique ���������� ������.
 * @param bool $fulltext �������������� ������.
 * @return array
 */
function GetCollDescription( $cname, $type, $length, $auto_increment=false, $default='', $attributes='', $notnull=true, $primary=false, $index=false, $unique=false, $fulltext=false ){
	$newcoll = array(
		'name'=>$cname,
		'type'=>$type,
		'length'=>$length,
	);
	if($auto_increment == true){
		$newcoll['auto_increment'] = true;
	}
	if($default <> ''){
		$newcoll['default'] = $default;
	}
	if($attributes <> ''){
		$newcoll['attributes'] = $attributes;
	}
	if($notnull == true){
		$newcoll['notnull'] = $notnull;
	}
	if($primary <> ''){
		$newcoll['primary'] = $primary;
	}
	if($index <> ''){
		$newcoll['index'] = $index;
	}
	if($unique <> ''){
		$newcoll['unique'] = $unique;
	}
	if($fulltext <> ''){
		$newcoll['fulltext'] = $fulltext;
	}
	return $newcoll;
}
