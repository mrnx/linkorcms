<?php

//Здесь нужно получить данные формы и сохранить их в переменную $block_config

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$block_config = SafeEnv($_POST['mod_text'], 0, str);

?>