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
'������� �� ������� ������',
'<noindex>
	<center><br />
	������ ��������� ������ ��� ��������.<br />
	<a href="http://'.$url.'">������� ����, ���� �� ������ �����.</a><br />
	<br />
	<a href="javascript:history.go(-1)">�����</a>
	</center>
</noindex>'
);

$site->TEcho();

?>