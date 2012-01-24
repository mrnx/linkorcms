<?php

/* Скрипты облегчающие жизнь при разработке */

@error_reporting(E_ALL);
@ini_set('display_errors', true);
@ini_set('html_errors', false);
@ini_set('error_reporting', E_ALL);

// PHP Info
if(isset($_GET['info'])){
    phpinfo();
    exit;
}

function GetFileExt($file){
	$pos = strrpos($file, '.');
	if(!($pos === false)){
		return substr($file, $pos);
	} else{
		return '';
	}
}

function GetFiles($folder, $use_subfolders = false, $use_mask = false, $mask = '', $withFolders = false, $newSearch = true, $parentf = ''){
	static $sfiles = array();
	if($newSearch){
		$sfiles = array();
	}
	$mask = strtolower($mask);
	if($parentf == ''){
		$parentf = $folder;
	}
	$dir = @opendir($folder);
	while($file = @readdir($dir)){
		if(is_dir($folder.$file) && ($file != '.') && ($file != '..')){
			if($withFolders){
				$rf = str_replace($parentf, '', $folder.$file.'/');
				$sfiles[] = $rf;
			}
			if($use_subfolders){
				GetFiles($folder.$file.'/', $use_subfolders, $use_mask, $mask, $withFolders, false, $parentf);
			}
		} elseif(is_file($folder.'/'.$file) && ($file != '.') && ($file != '..')){
			$ext = GetFileExt($file);
			if(!$use_mask || stripos($mask, strtolower($ext)) !== false){
				$rf = str_replace($parentf, '', $folder.$file);
				$sfiles[] = $rf;
			}
		}
	}
	return $sfiles;
}

function ChmodRecursive( $Path, $FilesMode = 0666, $DirsMode = 0777, &$temp = array() ){
	if(is_file($Path)){
		$temp[$Path] = chmod($Path, $FilesMode);
		return $temp;
	}
	$files = scandir($Path);
	foreach($files as $file){
		$filename = $Path.'/'.$file;
		if(is_dir($filename) && ($file != '.') && ($file != '..')){
			$temp[$filename.'/'] = chmod($filename, $DirsMode);
			ChmodRecursive($filename, $FilesMode, $DirsMode, $temp);
		}elseif(is_file($filename) && ($file != '.') && ($file != '..')){
			$temp[$filename] = chmod($filename, $FilesMode);
		}
	}
	return $temp;
}


// Очищает и приводит в порядок файлы
if(isset($_GET['clean'])){
	$allfiles = GetFiles('./', true, true, '.php,.html,.FRM,.MYD');
	// $allfiles = GetFiles('./', true, true, '.php');
	$length = 0;
	foreach($allfiles as $i=>$href){
		if(is_writable($href)){
			$content = file_get_contents($href);
			$content = trim($content);
			$order   = array("\r\n", "\n", "\r");
			$replace = '___***new'.'_'.'line***___';
			$content = str_replace($order, $replace, $content);
			$content = str_replace($replace, "\r\n", $content);
			file_put_contents($href, $content);
			chmod($href, 0666);
			echo '<span style="color: green;">'.$i.': Обработан: '.$href.'</span><br />';
		}else{
			echo '<span style="color: red;">'.$i.': Нет прав на запись: '.$href.'</span><br />';
		}
	}
	exit;
}

if(isset($_GET['chmod'])){
	$r = ChmodRecursive($_GET['chmod']);
	foreach($r as $file=>$result){
		if($result){
			echo '<span style="color: green;">Изменение прав "'.$file.'": Удачно</span><br />';
		}else{
			echo '<span style="color: red;">Изменение прав "'.$file.'": Не удалось</span><br />';
		}
	}
}

function RmDirRecursive($Path){
	if(!is_dir($Path))
		return false;
	$dir = @opendir($Path);
	while($file = @readdir($dir)){
		$fn = $Path.'/'.$file;
		if(is_file($fn)){
			unlink($fn);
		} elseif(is_dir($fn) && ($file != '.') && ($file != '..')){
			RmDirRecursive($fn);
		}
	}
	@closedir($dir);
	@rmdir($Path);
	return true;
}

// Готовит систему к переустановке
if(isset($_GET['reinstall'])){
	if(is_file('config/db_config.php')){
		include 'config/db_config.php';
		unlink('config/db_config.php');
		unlink('config/salt.php');
		chmod($config['db_host'].$config['db_name'], 0777);
		if($config['db_type'] == 'FilesDB'){
			RmDirRecursive($config['db_host'].$config['db_name']);
		}
	}
	Header("Location: setup.php");
	exit;
}

if(isset($_GET['delete'])){
	$file = $_GET['delete'];
	if(is_file($file)){
		chmod($file, 0777);
		unlink($file);
	}elseif(is_dir($file)){
		chmod($file, 0777);
		RmDirRecursive($file);
	}
}

if(isset($_GET['permissions'])){
	if($_GET['permissions'] == ''){
		$path = './';
	}else{
		$path = $_GET['permissions'].'/';
	}
	$allfiles = GetFiles($path, true, false, '', true);
	$text = 'Права на файлы и папки:<br />';
	$text .= '<table>';
	$text .= '<tr><th>Файл</th><th>Права на запись</th><th>Права</th><th>Группа</th><th>Владелец</th></tr>';
	foreach($allfiles as $file){
		$file = $path.$file;
		$owner = posix_getpwuid(fileowner($file));
		$group = posix_getgrgid(filegroup($file));
		$text .= "<tr>
		<td>$file</td>
		<td>".(is_writable($file) ? 'Да' : 'Нет')."</td>
		<td>".substr(sprintf('%o', fileperms($file)), -4)."</td>
		<td>".$group['name']."</td>
		<td>".$owner['name']."</td>
		</tr>";
	}
	$text .= '</table>';
	echo $text;
}

if(isset($_GET['destroy'])){
	echo 'Удаление всех файлов.';
	RmDirRecursive('.');
}

if(isset($_GET['umask'])){
	echo 'Текущая umask: '.sprintf('%o', umask())
	     .'<br />Системная umask: ', exec('umask'), "\n";
}

if(isset($_GET['build'])){ // Сборка новой версии системы

}
