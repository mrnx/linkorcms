<?php

/* Скрипты облегчающие жизнь при разработке */


// PHP Info
if(isset($_GET['info'])){
    phpinfo();
    exit;
}


// Очищает и приводит в порядок файлы
if(isset($_GET['clean'])){ 
	function GetFileExt($file){
		$pos = strrpos($file,'.');
		if(!($pos===false)){
			return substr($file,$pos);
		}else{
			return '';
		}
	}
	function GetFiles($folder,$use_subfolders=false,$use_mask=false,$mask='',$newSearch=true,$parentf=''){
		static $sfiles = array();
		if($newSearch){
			$sfiles = array();
		}
		$mask = strtolower($mask);
		if($parentf==''){
			$parentf = $folder;
		}
		$dir = @opendir($folder);
		while($file = @readdir($dir)){
			if(is_dir($folder.$file) && ($file != '.') && ($file != '..')){
				if($use_subfolders){
					GetFiles($folder.$file.'/',$use_subfolders,$use_mask,$mask,false,$parentf);
				}
			}elseif(is_file($folder.'/'.$file) && ($file != '.') && ($file != '..')){
				$ext = GetFileExt($file);
				if(!$use_mask || stripos($mask,strtolower($ext)) !== false){
					$rf = str_replace($parentf, '', $folder.$file);
					$sfiles[] = $rf;
				}
			}
		}
		return $sfiles;
	}

	$allfiles = GetFiles('./', true, true, '.php,.html,.FRM,.MYD');
	//$allfiles = GetFiles('./', true, true, '.php');
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
			echo '<span style="color: green;">'.$i.': Обработан: '.$href.'</span><br />';
		}else{
			echo '<span style="color: red;">'.$i.': Нет прав на запись: '.$href.'</span><br />';
		}
	}
	exit;
}


//Готовит систему к переустановке
if(isset($_GET['reinstall'])){ 
	function RmDirRecursive( $Path ){
		if(!is_dir($Path)) return false;
		$dir = @opendir($Path);
		while($file = @readdir($dir)){
			$fn = $Path.'/'.$file;
			if(is_file($fn)){
				@unlink($fn);
			}elseif(is_dir($fn) && ($file != '.') && ($file != '..')){
				RmDirRecursive($fn);
			}
		}
		@closedir($dir);
		@rmdir($Path);
		return true;
	}
	if(is_file('config/db_config.php')){
		include 'config/db_config.php';
		unlink('config/db_config.php');
		unlink('config/salt.php');
		if($config['db_type'] == 'FilesDB'){
			RmDirRecursive($config['db_host'].$config['db_name']);
		}
	}
	Header("Location: setup.php");
	exit;
}

?>
