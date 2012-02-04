<?php

// Восстановление БД из резервной копии

System::admin()->AddCenterBox('Восстановление базы данных');
$name = RealPath2(System::config('backup_dir').$_GET['name']);
$iferrors = false;

// Функция получает тип БД из имени файла
// Бекап из файловой БД нельзя применить к MySQL
function BackupGetDbType( $Name ){
	$pos = strrpos($Name, '.');
	if($pos === false){
		return false;
	}
	$ext = substr($Name, $pos+1); // zip
	$pos2 = strrpos($Name, '.', -strlen($ext)-2);
	if($pos2 === false){
		return false;
	}
	$ext2 = substr($Name, $pos2+1, $pos-$pos2-1); // MySQL
	if($ext != 'zip' || $ext2 != System::database()->Name){
		return false;
	}
	return true;
}

$zip = new ZipArchive();
if(BackupGetDbType($name) && $zip->open($name) === true){
	if(System::database()->Name == 'FilesDB'){ // Файловая БД
		$dbhost = System::database()->DbAccess;
		$to_unpack = array();
		for($i = 0; $i < $zip->numFiles; $i++){
			$filename = $zip->getNameIndex($i);
			$to_unpack[] = array('zip://'.$name."#".$filename, $path.$dbhost);
		}
		foreach($to_unpack as $file){
			copy($file[0], $file[1]);
		}
	}elseif(System::database()->Name == 'MySQL'){ // БД MySQL
		for($i = 0; $i < $zip->numFiles; $i++){
			$filename = $zip->getNameIndex($i);
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
	$zip->close();
	if($iferrors){
		System::admin()->Highlight('Произошли ошибки при восстановлении некоторых таблиц.');
	}else{
		System::admin()->Highlight('База данных успешно восстановлена из резервной копии.');
	}
}else{
	// Выводим сообщение об ошибке
	System::admin()->HighlightError('Ошибка. Неверный формат файла, либо это бекап другого типа Базы данных.');
}