<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

include_once($config['inc_dir'].'index_template.inc.php');

$url = SafeDB($_GET['url'], 255, str);
$url = Url(SafeDB($url, 255, str));
$site->OtherMeta .= '<meta http-equiv="REFRESH" content="2; URL=http://'.$url.'">';
$site->AddTextBox(
'Переход по внешней ссылке',
'<noindex>
	<center><br />
	Сейчас откроется нужная вам страница.<br />
	<a href="http://'.$url.'">Нажмите сюда, если не хотите ждать.</a><br />
	<br />
	<a href="javascript:history.go(-1)">Назад</a>
	</center>
</noindex>'
);

$site->TEcho();

?>