<?php

/**
 * Извлекает из полного имени файла его расширение
 *
 * @param String $FileName Полное имя файла
 * @param bool $RemoveDot Удалить ведущую точку
 * @return String
 */
function GetFileExt( $FileName, $RemoveDot = false ){
	$pos = strrpos($FileName, '.');
	if($RemoveDot) $pos++;
	if($pos !== false){
		return substr($FileName, $pos);
	}else{
		return '';
	}
}

/**
 * Извлекает из полного имени файла его имя
 * @param string $FileName Полное имя файла
 * @param bool $RemoveExt Удалить расширение
 * @return String
 */
function GetFileName( $FileName, $RemoveExt = false ){
	$pos = strrpos($FileName, '/');
	if($pos !== false){
		$FileName = substr($FileName, $pos+1);
	}
	if($RemoveExt){
		$pos = strrpos($FileName, '.');
		if($pos !== false){
			$FileName = substr($FileName, 0, $pos);
		}
	}
	return $FileName;
}

function GetPathName( $FileName ){
	$pos = strrpos($FileName, '/');
	if($pos !== false){
		return substr($FileName, 0, $pos+1);
	}else{
		return '';
	}
}

/**
 * Рекурсивно обходит каталог и возвращает массив с относительными именами файлов просматриваемого каталога
 *
 * @param String $folder Имя папки с последним слэшем
 * @param Boolean $use_subfolders Искать в подпапках
 * @param Boolean $use_mask Использовать маску поиска
 * @param String $mask Маска поиска. Вы можете указать расширения (с точкой) через запятую.
 * @param Boolean $newSearch Начать ли новый поиск (статическая переменная будет перезаписана)
 * @param String $parentf Не обращайте внимания. Нужна для работы функции.
 * @return Array Список найденных файлов
 */
function GetFiles( $folder, $use_subfolders = false, $use_mask = false, $mask = '', $newSearch = true, $parentf = '' ){
	static $sfiles = array();
	if(!is_dir($folder)){
		return $sfiles;
	}
	if($newSearch){
		$sfiles = array();
	}
	$mask = strtolower($mask);
	if($parentf==''){
		$parentf = $folder;
	}
	$files = scandir($folder);
	foreach($files as $file){
		if(is_dir($folder.$file) && ($file != '.') && ($file != '..')){
			if($use_subfolders){
				GetFiles($folder.$file, $use_subfolders, $use_mask, $mask, false, $parentf);
			}
		}elseif(is_file($folder.$file) && ($file != '.') && ($file != '..')){
			$ext = GetFileExt($file);
			if(!$use_mask || stripos($mask, strtolower($ext)) !== false){
				$rf = str_replace($parentf, '', $folder.$file);
				$sfiles[] = $rf;
			}
		}
	}
	return $sfiles;
}

/**
 * Возвращает список поддиректорий из заданной директории
 * @param string $Folder Путь до папки с последним слешем
 * @return array
 */
function GetFolders( $Folder ){
	$result = array();
	if(!is_dir($Folder)){
		return $result;
	}
	$files = scandir($Folder);
	foreach($files as $p){
		if(($p != ".") && ($p != "..")){
			if(is_dir($Folder.$p)){
				$result[] = $p;
			}
		}
	}
	return $result;
}

function GetFolderSize( $Folder ){
	$file_size = 0;
	$files = scandir($Folder);
	foreach($files as $file){
		if (($file!='.') && ($file!='..')){
			$f = $Folder.$file;
			if(is_dir($f)){
				$file_size += GetFolderSize($f);
			}else{
				$file_size += filesize($f);
			}
		}
	}
	return $file_size;
}

/**
 * Удаляет папку со всеми вложениями
 * @param string $Path
 * @return bool
 */
function RmDirRecursive( $Path ){
	if(!is_dir($Path)) return false;
	$dir = @opendir($Path);
	if(!$dir) return false;
	while($file = @readdir($dir)){
		$fn = $Path.$file;
		if(is_file($fn) || is_link($fn)){
			if(!unlink($fn)) return false;
		}elseif(is_dir($fn) && ($file != '.') && ($file != '..')){
			if(!RmDirRecursive($fn)) return false;
		}
	}
	@closedir($dir);
	if(!rmdir($Path)) return false;
	return true;
}

function ChmodRecursive( $Path, $FilesMode = 0666, $DirsMode = 0777 ){
	if(is_file($Path)){
		chmod($Path, $FilesMode);
		return true;
	}
	$files = scandir($Path);
	foreach($files as $file){
		if(is_dir($Path.'/'.$file) && ($file != '.') && ($file != '..')){
			chmod($Path.'/'.$file, $DirsMode);
			ChmodRecursive($Path.'/'.$file, $FilesMode, $DirsMode);
		}elseif(is_file($Path.'/'.$file) && ($file != '.') && ($file != '..')){
			chmod($Path.'/'.$file, $FilesMode);
		}
	}
	return true;
}

/**
 * Возвращает канонизированный путь к папке,
 * удаляет слэши из начала и конца пути
 * @param  $Path
 * @return mixed|string
 */
function RealPath2( $Path ){
	$Path = str_replace('\\', '/',$Path);
	$path_array = explode('/', $Path);
	$path_result = array();
	foreach($path_array as $name){
		$name2 = str_replace('.', '', $name);
		if($name2 != ''){
			$path_result[] = $name;
		}
	}
	return implode('/', $path_result);
}

function FormatFileSize( $Size, $SizeType = 'b' ){
	if($SizeType == 'b'){
		$mb = 1024*1024;
		if($Size>$mb){$Size = sprintf("%01.2f",$Size/$mb).' Мб';
		}elseif($Size>=1024){$Size = sprintf("%01.2f",$Size/1024).' Кб';
		}else{$Size = $Size.' Байт';}
	}else{
		if($SizeType == 'k'){
			$Size = $Size.' Кб';
		}elseif($SizeType == 'm'){
			$Size = $Size.' Мб';
		}else{
			$Size = $Size.' Гб';
		}
	}
	return $Size;
}
