<?php
/**
 * Обработка CSS файлов
 */

define('COMPRESS_SCRIPT', true);
define('VALID_RUN', true);

// Проверяем параметры
if(isset($_GET['href'])){
	$href = $_GET['href'];
}else{
	exit('/* Bad Params */');
}

function __SetCssVar( $vn, $vv ){
	eval('$GLOBALS[$vn] = '.str_replace(array('\"', "\\'"), array('"', "'"), $vv).';');
	return '';
}

function __CssPack( $FileName, $import = false ){
	global $buffer_size, $href;
	if($import){
		$FileName = str_replace(array('\"', '"', "\\'", "'"), '', $FileName);
		$FileName = dirname($href).'/'.$FileName;
	}
	// Загрузка файла
	$buffer = file_get_contents($FileName);
	$buffer_size += strlen($buffer);
	// Обьявление переменной @var name: value; и @import "filename";
	$buffer = preg_replace('#^@import[\s]+([^\r\n]*);#meU', "__CssPack('\\1', true)", $buffer);
	$buffer = preg_replace('#^@var[\s]+([A-Za-z_0-9]+):([^\r\n]*);#meU', "__SetCssVar('\\1', '\\2')", $buffer);
	// Замена переменных var(name)
	$buffer = preg_replace('/var[\s]*\([\s]*([A-Za-z_0-9]+)[\s]*\)[\s]*/ie', "\$GLOBALS['\\1']", $buffer);
	return $buffer;
}

// Проверка кэша

// Загрузка файла и уменьшение(обфускация)
$buffer = "";
$packed = false;
$buffer_size = 0;
$error = false;
if(is_file($href)){
	if(substr($href, -4) == '.css'){
		$buffer = __CssPack($href);
	}else{
		$buffer .= "\n\n/* \"$href\" Bad File Extension. */\n\n";
		$error = 'Bad File Extension';
	}
}else{
	$buffer .= "\n\n/* File \"$href\" Not Found. */\n\n";
	$error = 'File Not Found';
}

header('Cache-Control: public');
header('Expires: '.gmdate('D, d M Y H:i:s', time() + 86400) . ' GMT');
header("Content-type: text/css");
echo $buffer;

?>
