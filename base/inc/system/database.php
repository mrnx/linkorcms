<?php

$Parser_WhereCache = array();
$Parser_SetCache = array();
$Parser_UseCache = true;

/**
 * Создаёт запрос значений для базы данных из переданных аргементов.
 * Аргументы должны быть предварительно проэкранированы с помощью функции SafeEnv или SafeR.
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
 * Генерирует SET запрос из переданных ассоциативных массивов.
 * Значения должны быть предварительно проэкранированы с помощью функции SafeEnv или SafeR.
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
 * Заменяет в строке имена ключей массивов их значениями.
 * Удобно использовать для формирования VALUES запроса.
 *
 * @param string $Values Строка вида "'','login','pass','order'"
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
 * Преобразует SET строку запроса в упорядоченный массив.
 *
 * @param string $set Строка вида "name='name',login='root',pass=''".
 * @param array $row Упорядоченный массив из значений поля таблицы.
 * @param array $info Структура информации о таблице.
 * @return array Упорядоченный массив значений вида array('name','root','').
 */
function Parser_ParseSetStr( &$set, &$row, &$info ){
	$s = str_replace("\\'",'<&#39;>',$set);
	$maxlength=count($info['cols']);
	for($i=0;$i<$maxlength;$i++){
		$args[$i] = $row[$i];
		$names[$info['cols'][$i]['name']] = $i;
	}
	for($i=0;$i<$maxlength;$i++){
		$pos = strpos($s, '='); //Ищем первое вхождение =
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
			echo "Ошибка в синтаксисе запроса. Поле ".$col." в таблице не найдено!";
			return $row;
		}
	}
	return $args;
}

/**
 * Преобразует SET строку запроса в упорядоченный массив.
 *
 * @param string $values Строка вида "'name','root',''".
 * @param array $Info Структура информации о таблице.
 * @param bool $isUpdateMethod Обновление, влияет на то устанавливать ли свое новое значение инкремента.
 * @param bool $lastvals Значения которые были до обновления, при обновлении строки.
 * @return Упорядоченный массив значений вида array('name','root','').
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
 * Выполняет простые WHERE запросы соответствующие синтаксису SQL.
 *
 * @param  $where Строка вида "`pass`='1' and `login`='admin'"
 * @param  $row Упорядоченный массив из значений поля таблицы.
 * @param  $info Структура информации о таблице.
 * @param int $index Порядковый номер строки в таблице. Добавляет переменную `index`,
 * которую можно использовать в запросе.
 * @return bool Возвращает логическое значение, соответствующее результату выполненного запроса.
 */
function Parser_ParseWhereStr( $where, $row, $info, $index = 0 ){
	if($where == ''){ return true; };
	global $Parser_UseCache, $Parser_WhereCache;
	$vars = array();
	// Значение переменных и имена колонок
	// fixme: Можно кэшировать или ускорить выборку имен
	for($j=0,$ccnt=count($info['cols']);$j<$ccnt;$j++){
		$n = $info['cols'][$j]['name'];
		$r = str_replace('&#13', "\r", $row[$j]); // Чтобы данные соотвествовали данным из $db->Select
		$r = str_replace('&#10', "\n", $r);
		$vars[$n] = $r;
		$names[] = $n;
	}
	$vars['index'] = $index;
	$names[] = 'index';
	$ccnt++;
	// Проверяем есть ли такой результат преобразования в оперативном кеше
	if(isset($Parser_WhereCache[$where])){
		return Parser_ParseWhereStr2($Parser_WhereCache[$where], $vars);
	}
	// Чтобы разбить запрос по кавычкам и преобразовать его в PHP формат заменяем экранированные кавычки
	$where2 = str_replace("\\'", '<&#39;>', $where);
	$chs = explode("'", $where2);
	for($i=1,$cnt=count($chs)+1; $i<$cnt; $i++){
		if($i%2>0){
			$chs[$i-1] = str_replace("=", "==", $chs[$i-1]);
			for($j=0;$j<$ccnt;$j++){
				$chs[$i-1] = str_replace('`'.$names[$j].'`', '$'.$names[$j], $chs[$i-1]);
			}
		}else{
			// Экранируем экранирование
			$chs[$i-1] = str_replace("\\", "\\\\", $chs[$i-1]);
		}
	}
	$where2 = implode("'", $chs);
	$where2 = str_replace('<&#39;>', "\\'", $where2);
	// Записываем результат преобразования в оперативный кеш
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
 * Возвращает массив с информацией о ячейке, который понимают классы для работы с БД.
 *
 * @param string $cname Имя ячейки (формат SQL).
 * @param string $type Тип (формат SQL).
 * @param int $length Максимальная длина.
 * @param bool $auto_increment Автоматическое приращение (инкремент) значения.
 * @param string $default Значение ячейки по умолчанию.
 * @param string $attributes Атрибуты (формат SQL).
 * @param bool $notnull Не может быть null.
 * @param bool $primary Индекс колонка по умолчанию.
 * @param bool $index Простое индексирование.
 * @param bool $unique Уникальный индекс.
 * @param bool $fulltext Полнотекстовый индекс.
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
