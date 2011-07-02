<?php

/**
 * Функция автоматически подключает скрипты из папки script к странице. Принимает произвольное количество параметров или массив.
 * @return void
 */
function UseScript( $FileName1, $FileName2 = '', $FileName3 = '' ){
	static $included = array();
	$args = func_get_args();
	if(is_array($args[0])){
		$args = $args[0];
	}
	foreach($args as $script){
		if(isset($included[$script])) continue;
		$file = RealPath2('scripts/'.$script.'/script.php');
		if(is_file($file)){
			include_once $file;
			$included[$script] = true;
		}else{
			// TODO: WARNING
		}
	}
}
