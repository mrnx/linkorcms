<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(isset($_POST['newname'])){
	$db->RenameTable(SafeEnv($_GET['name'], 255, str), SafeEnv($_POST['newname'], 255, str));
	GO(ADMIN_FILE.'?exe=fdbadmin');
}else{
	AddCenterBox('Переименовать таблицу "'.SafeDB($_GET['name'], 255, str).'"');
	FormRow('Новое имя', $site->Edit('newname', SafeDB($_GET['name'], 255, str), false, 'style="width: 210px;"'));
	AddForm('<form action="'.ADMIN_FILE.'?exe=fdbadmin&a=renametable&name='.SafeEnv($_GET['name'], 255, str).'" method="post">',
		$site->Button('Отмена', 'onclick="history.go(-1);"').$site->Submit('Переименовать'));
}

