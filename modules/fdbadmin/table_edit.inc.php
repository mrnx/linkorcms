<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$columns = array();

if($action == 'edittable'){
	$name = SafeEnv($_GET['name'], 250, str);
	$info = $db->GetTableInfo($name);
	$info = $info[0];
	$columns = $db->GetTableColumns($name);
	$columns_count = count($columns);
	$bcaption = 'Сохранить изменения';
	$tcaption = 'Редактирование таблицы "'.$name.'"';
	$param = 'editsavetable';
	AdminFdbAdminGenTableMenu($name);
}else{
	$name = SafeEnv($_POST['name'], 250, str);
	$info = array(
		'num_rows'=>0,
		'counter'=>0,
		'cols'=>array(),
		'type'=>'default',
		'comment'=>''
	);
	$columns_count = SafeEnv($_POST['cols'], 11, int);
	for($i = 0; $i < $columns_count; $i++){
		$columns[] = array(
			'name'=>'',
			'type'=>'varchar',
			'length'=>'',
			'default'=>'',
			'attrabutes'=>'',
			'notnull'=>true,
			'auto_increment'=>false,
			'primary'=>false,
			'index'=>false,
			'unique'=>false,
			'fulltext'=>false
		);
	}
	$bcaption = 'Создать';
	$tcaption = 'Создать таблицу';
	$param = 'savetable';
}

$text = '<form action="'.ADMIN_FILE.'?exe=fdbadmin&a='.$param.'" method="post">
	<table cellspacing="0" cellspacing="0" class="cfgtable">
		<tr>
			<th>Поле</th>
			<th>Тип</th>
			<th>Длина/значения</th>
			<th>Атрибуты</th>
			<th>Ноль</th>
			<th>По умолчанию</th>
			<th>Авто приращение</th>
			<th>Первичный</th>
			<th>Уникальное</th>
			<th>Индекс</th>
			<th>Полный текст</th>
			<th> - </th>
		</tr>';

$types = array();
$site->DataAdd($types, 'varchar', 'varchar');
$site->DataAdd($types, 'tinyint', 'tinyint');
$site->DataAdd($types, 'text', 'text');
$site->DataAdd($types, 'date', 'date');
$site->DataAdd($types, 'smallint', 'smallint');
$site->DataAdd($types, 'mediumint', 'mediumint');
$site->DataAdd($types, 'int', 'int');
$site->DataAdd($types, 'bigint', 'bigint');
$site->DataAdd($types, 'float', 'float');
$site->DataAdd($types, 'double', 'double');
$site->DataAdd($types, 'decimal', 'decimal');
$site->DataAdd($types, 'datetime', 'datetime');
$site->DataAdd($types, 'timestamp', 'timestamp');
$site->DataAdd($types, 'time', 'time');
$site->DataAdd($types, 'year', 'year');
$site->DataAdd($types, 'char', 'char');
$site->DataAdd($types, 'tinyblob', 'tinyblob');
$site->DataAdd($types, 'tinytext', 'tinytext');
$site->DataAdd($types, 'blob', 'blob');
$site->DataAdd($types, 'mediumblob', 'mediumblob');
$site->DataAdd($types, 'mediumtext', 'mediumtext');
$site->DataAdd($types, 'longblob', 'longblob');
$site->DataAdd($types, 'longtext', 'longtext');
$site->DataAdd($types, 'enum', 'enum');
$site->DataAdd($types, 'set', 'set');

$atr = array();
$site->DataAdd($atr, 'none', '');
$site->DataAdd($atr, 'binary', 'binary');
$site->DataAdd($atr, 'unsigned', 'unsigned');
$site->DataAdd($atr, 'unsigned zerofill', 'unsigned zerofill');

$tabletypes = array();
$site->DataAdd($tabletypes, 'MYISAM', 'MyISAM');
$site->DataAdd($tabletypes, 'INNODB', 'InnoDB');
$tabletypes['selected'] = trim(strtoupper($info['type']));

$primary_exists = false;
foreach($columns as $col){
	if(isset($col['primary'])){
		$primary_exists = true;
		break;
	}
}

foreach($columns as $i=>$col){
	$primary = (isset($col['primary']) ? $col['primary'] : false);
	if($action == 'edittable'){
		$types['selected'] = $col['type'];
		if(isset($col['attributes'])){
			$atr['selected'] = $col['attributes'];
		}else{
			$atr['selected'] = '';
		}
		if($primary){
			$primary_disabled = '';
			$disabled = 'disabled';
			$fulltext_disabled = 'disabled';
			$index_disabled = 'disabled';
		}else{
			$disabled = '';
			if($primary_exists) $primary_disabled = 'disabled';
			else $primary_disabled = '';
			if(strpos($col['type'], 'text') === false && strpos($col['type'], 'char') === false){
				$fulltext_disabled = 'disabled';
				$index_disabled = '';
			}else{
				$fulltext_disabled = '';
				$index_disabled = 'disabled';
			}
			if(strpos($col['type'], 'text') !== false || strpos($col['type'], 'blob') !== false){
				$index_disabled = 'disabled';
			}else{
				$index_disabled = '';
			}
		}
	}else{
		$primary_disabled = '';
		$disabled = '';
		$fulltext_disabled = '';
		$index_disabled = '';
	}
	$unique = (isset($col['unique']) ? $col['unique'] : false);
	$index = (isset($col['index']) ? $col['index'] : false);
	$fulltext = (isset($col['fulltext']) ? $col['fulltext'] : false);

	$text .= '<tr>
		<td>'.$site->Edit('name'.$i, (isset($col['name']) ? $col['name'] : ''), false, 'style="width:80px;"').'</td>
		<td>'.$site->Select('type'.$i, $types).'</td>
		<td>'.$site->Edit('length'.$i, (isset($col['length']) ? $col['length'] : ''), false, 'style="width:50px"').'</td>
		<td>'.$site->Select('attributes'.$i, $atr).'</td>
		<td>'.$site->Check('notnull'.$i, 'notnull', (isset($col['notnull']) ? false : true)).'</td>
		<td>'.$site->Edit('default'.$i, (isset($col['default']) ? $col['default'] : ''), false, 'style="width:80px;"').'</td>
		<td>'.$site->Check('auto_increment'.$i, 'val', (isset($col['auto_increment']) ? $col['auto_increment'] : false)).'</td>
		<td>'.$site->Radio('params'.$i, 'primary', $primary, $primary_disabled).'</td>
		<td>'.$site->Radio('params'.$i, 'unique', $unique, $index_disabled).'</td>
		<td>'.$site->Radio('params'.$i, 'index', $index, $index_disabled).'</td>
		<td>'.$site->Radio('params'.$i, 'fulltext', $fulltext, $fulltext_disabled).'</td>
		<td>'.$site->Radio('params'.$i, 'noparams', (!$primary && !$unique && !$index && !$fulltext), $disabled).'</td>
	</tr>';
}

$table_comment = (isset($info['comment']) ? SafeDB($info['comment'], 255, str) : '');

$text .= '</table><font size="2">Комментарий к таблице:&nbsp;'
	.$site->Edit('comment', $table_comment, false, 'style="width: 300px;"').' &nbsp;&nbsp;&nbsp;&nbsp; Тип:&nbsp;'
	.$site->Select('tabletype', $tabletypes).'</font>'.$site->Hidden('tablename', $name).$site->Hidden('cols', $columns_count)."<br /><br />";

if($action == 'edittable'){
	$text .= $site->Hidden('num_rows', $info['num_rows']).$site->Hidden('counter', $info['counter']);
}

$text .= $site->Submit($bcaption).'</form>';
AddTextBox($tcaption, $text);
