<?php

// Создаем бекап БД и архивируем его в ZIP

$filename = System::config('backup_dir').date("Y.m.d").'_'.date("His").GenRandomString(8, 'abcdefghijklmnopqrstuvwxyz0123456789').'.'.System::database()->Name;
$backup_file = $filename.'.zip';

if(System::database()->Name == 'FilesDB'){
	$zip = new ZipArchive();
	$zip->open($backup_file, ZipArchive::CREATE);

	$infoext = System::database()->InfoFileExt;
	$tableext = System::database()->TableFileExt;
	$dbprefix = System::database()->Prefix();
	$dbhost = System::database()->DbAccess.$dbprefix;

	$tables = System::database()->GetTables();
	foreach($tables as $table){
		foreach($table as $table){}
		$zip->addFile($dbhost.$table.$infoext, $dbprefix.$table.$infoext);
		$zip->addFile($dbhost.$table.$tableext, $dbprefix.$table.$tableext);
	}
	$zip->close();
}elseif(System::database()->Name == 'MySQL'){
	$zip = new ZipArchive();
	$zip->open($backup_file, ZipArchive::CREATE);
	$tables = System::database()->GetTableInfo();
	foreach($tables as $table){
		$rdata = '';
		$rows = array();
		$rows = System::database()->Select($table['name']);
		$table = System::database()->Prefix().$table['name'];
		$rdata = "DROP TABLE IF EXISTS `$table`;\n";
		$query_result = System::database()->MySQLQueryResult('SHOW CREATE TABLE '.$table);
		$rdata .= $query_result[0]['Create Table'].";\n\n";
		foreach($rows as $row){
			$rdata .= 'INSERT INTO `'.$table.'` VALUES(';
			foreach($row as $field){
				$field = mysql_real_escape_string($field);
				$field = ereg_replace("\n", "\\n", $field);
				$rdata .= '"'.$field.'",';
			}
			$rdata = substr($rdata, 0, strlen($rdata)-1);
			$rdata .= ");\n";
		}
		$rdata = substr($rdata, 0, strlen($rdata)-1);
		$zip->addFromString($table.'.sql', $rdata);
	}
	$zip->close();
}else{
	// Выводим ошибку
}

exit("OK");