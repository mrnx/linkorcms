<?php

# LinkorCMS
# © 2006-2010 Галицкий Александр Николаевич (linkorcms@yandex.ru)
# LinkorCMS Development Group
# www.linkorcms.ru
# Лицензия LinkorCMS 1.3

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$db->Delete('modules', "`folder`='cache'");

?>