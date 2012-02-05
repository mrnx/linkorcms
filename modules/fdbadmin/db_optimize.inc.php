<?php

// Оптимизация всех таблиц БД

AddCenterBox('Оптимизация');

if(System::database()->Name != 'MySQL'){
	System::admin()->HighlightError('Только MySQL базы данных.');
	return;
}

$iferrors = false;
$tables = System::database()->GetTableInfo();
System::database()->MySQLQuery('LOCK TABLES');
foreach($tables as $table){
	$table = System::database()->Prefix().$table['name'];
	if(System::database()->MySQLQuery('OPTIMIZE TABLE `'.$table.'`') == false){
		System::admin()->HighlightError(System::database()->MySQLGetErrMsg().' ('.$table.')');
		$iferrors = true;
	}
}
System::database()->MySQLQuery('UNLOCK TABLES');
if($iferrors){
	System::admin()->Highlight('Произошли ошибки при оптимизации некоторых таблиц.');
}else{
	System::admin()->Highlight('Все таблицы успешно оптимизированы.');
}