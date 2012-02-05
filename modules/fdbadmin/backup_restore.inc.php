<?php

// Восстановление БД из резервной копии


$name = RealPath2(System::config('backup_dir').$_GET['name']);
if(isset($_GET['table'])){
	$table = $_GET['table'];
	System::admin()->AddCenterBox('Восстановление таблицы "'.$table.'"');
	$table = System::database()->Prefix().$table;
}else{
	$table = '';
	System::admin()->AddCenterBox('Восстановление базы данных');
}
$iferrors = false;

$zip = new ZipArchive();
if(BackupCheckDbType($name) && $zip->open($name) === true){
	if(System::database()->Name == 'FilesDB'){ // Файловая БД
		$dbhost = System::database()->DbAccess;
		$to_unpack = array();
		for($i = 0; $i < $zip->numFiles; $i++){
			$filename = $zip->getNameIndex($i);
			if($table == '' || $table == GetFileName($filename)){
				$to_unpack[] = array('zip://'.$name."#".$filename, $path.$dbhost);
			}
		}
		foreach($to_unpack as $file){
			copy($file[0], $file[1]);
		}
	}elseif(System::database()->Name == 'MySQL'){ // БД MySQL
		for($i = 0; $i < $zip->numFiles; $i++){
			$filename = $zip->getNameIndex($i);
			if($table == '' || $table == GetFileName($filename)){
				$sql = $zip->getFromIndex($i);
				$sql = explode(";\n", $sql);
				foreach($sql as $query){
					if(trim($query) == '') continue;
					if(System::database()->MySQLQuery($query) === false){
						System::admin()->HighlightError(System::database()->MySQLGetErrMsg().' ('.$filename.')');
						$iferrors = true;
					}
				}
			}
		}
	}
	$zip->close();
	if($iferrors){
		System::admin()->Highlight('Произошли ошибки при восстановлении некоторых таблиц.');
	}else{
		if($table == ''){
			System::admin()->Highlight('База данных успешно восстановлена из резервной копии.');
		}else{
			System::admin()->Highlight('Таблица "'.$table.'" успешно восстановлена из резервной копии.');
		}
	}
}else{
	// Выводим сообщение об ошибке
	System::admin()->HighlightError('Ошибка. Неверный формат файла, либо это бекап другого типа Базы данных.');
}