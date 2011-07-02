<?php

/**
 * ��������� �� ������� ����� ����� ��� ����������
 *
 * @param String $file // ������ ��� �����
 * @param bool $removeDot // ������� ������� �����
 * @return String
 */
function GetFileExt( $file, $removeDot = false ){
	$pos = strrpos($file, '.');
	if($removeDot) $pos++;
	if(!($pos===false)){
		return substr($file, $pos);
	}else{
		return '';
	}
}

/**
 * ��������� �� ������� ����� ����� ��� ��� ��� ����������
 *
 * @param Itring $Name // ������ ��� �����
 * @return String
 */
function GetFileName( $Name, $RemoveExt = true ){
	if($RemoveExt){
		$suffix = substr($Name, strrpos($Name, '.'));
	}else{
		$suffix = null;
	}
	return basename($Name, $suffix);
}

/**
 * ���������� ������� ������� � ���������� ������ � �������������� ������� ������ ���������������� ��������
 *
 * @param String $folder // ��� ����� � ��������� ������
 * @param Boolean $use_subfolders // ������ � ���������
 * @param Boolean $use_mask // ������������ ����� ������
 * @param String $mask // ����� ������. �� ������ ������� ���������� (� ������) ����� �������.
 * @param Boolean $newSearch // ������ �� ����� ����� (����������� ���������� ����� ������������)
 * @param String $parentf // �� ��������� ��������. ����� ��� ������ �������.
 * @return Array // ������ ��������� ������
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
				GetFiles($folder.$file.'/', $use_subfolders, $use_mask, $mask, false, $parentf);
			}
		}elseif(is_file($folder.'/'.$file) && ($file != '.') && ($file != '..')){
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
 * ���������� ������ ������������� �� �������� ����������.
 *
 * @param String $folder ���� �� ����� � ��������� ������.
 */
function GetFolders( $folder ){
	$result = array();
	if(!is_dir($folder)){
		return $result;
	}

	$files = scandir($folder);
	foreach($files as $p){
		if(($p != ".") && ($p != "..")){
			if(is_dir($folder.$p)){
				$result[] = $p;
			}
		}
	}
	return $result;
}

function GetFolderSize( $folder ){
	$file_size = 0;
	$files = scandir($folder);
	foreach($files as $file){
		if (($file!='.') && ($file!='..')){
			$f = $folder.'/'.$file;
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
 * ������� ����� �� ����� ����������
 * @param  $Path
 * @return bool
 */
function RmDirRecursive( $Path ){
	if(!is_dir($Path)) return false;
	$dir = @opendir($Path);
	if(!$dir) return false;
	while($file = @readdir($dir)){
		$fn = $Path.'/'.$file;
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

/**
 * ���������� ���������������� ���� � �����,
 * ������� ����� �� ������ � ����� ����
 * @param  $path
 * @return mixed|string
 */
function RealPath2($path){
	$path = str_replace('\\', '/',$path);
	$path_array = explode('/', $path);
	$path_result = array();
	foreach($path_array as $name){
		$name2 = str_replace('.', '', $name);
		if($name2 != ''){
			$path_result[] = $name;
		}
	}
	return implode('/', $path_result);
}

function FormatFileSize( $size, $sizeType = 'b' ){
	if($sizeType == 'b'){
		$mb = 1024*1024;
		if($size>$mb){$size = sprintf("%01.2f",$size/$mb).' ��';
		}elseif($size>=1024){$size = sprintf("%01.2f",$size/1024).' ��';
		}else{$size = $size.' ����';}
	}else{
		if($sizeType == 'k'){
			$size = $size.' ��';
		}elseif($sizeType == 'm'){
			$size = $size.' ��';
		}else{
			$size = $size.' ��';
		}
	}
	return $size;
}
