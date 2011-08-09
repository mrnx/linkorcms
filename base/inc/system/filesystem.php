<?php

/**
 * ��������� �� ������� ����� ����� ��� ����������
 *
 * @param String $File // ������ ��� �����
 * @param bool $RemoveDot // ������� ������� �����
 * @return String
 */
function GetFileExt( $File, $RemoveDot = false ){
	$pos = strrpos($File, '.');
	if($RemoveDot) $pos++;
	if(!($pos===false)){
		return substr($File, $pos);
	}else{
		return '';
	}
}

/**
 * ��������� �� ������� ����� ����� ��� ���
 * @param string $Name ������ ��� �����
 * @param bool $RemoveExt ������� ����������
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
 * @param String $folder ��� ����� � ��������� ������
 * @param Boolean $use_subfolders ������ � ���������
 * @param Boolean $use_mask ������������ ����� ������
 * @param String $mask ����� ������. �� ������ ������� ���������� (� ������) ����� �������.
 * @param Boolean $newSearch ������ �� ����� ����� (����������� ���������� ����� ������������)
 * @param String $parentf �� ��������� ��������. ����� ��� ������ �������.
 * @return Array ������ ��������� ������
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
 * ���������� ������ ������������� �� �������� ����������
 * @param string $Folder ���� �� ����� � ��������� ������
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
 * ������� ����� �� ����� ����������
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

/**
 * ���������� ���������������� ���� � �����,
 * ������� ����� �� ������ � ����� ����
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
		if($Size>$mb){$Size = sprintf("%01.2f",$Size/$mb).' ��';
		}elseif($Size>=1024){$Size = sprintf("%01.2f",$Size/1024).' ��';
		}else{$Size = $Size.' ����';}
	}else{
		if($SizeType == 'k'){
			$Size = $Size.' ��';
		}elseif($SizeType == 'm'){
			$Size = $Size.' ��';
		}else{
			$Size = $Size.' ��';
		}
	}
	return $Size;
}
