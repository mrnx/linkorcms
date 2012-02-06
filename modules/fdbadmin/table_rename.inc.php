<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(isset($_POST['newname'])){
	$s = $_POST['newname'];
}else{
	$s = false;
}

if($s == false){
	FormRow('Новое имя', $site->Edit('newname', SafeEnv($_GET['name'], 255, str)));
	AddCenterBox('Переименование таблицы "'.SafeEnv($_GET['name'], 255, str).'"');
	AddForm('<form action="'.ADMIN_FILE.'?exe=fdbadmin&a=renametable&name='.SafeEnv($_GET['name'], 255, str).'" method="post">', $site->Submit('Переименовать'));
}else{
	$db->RenameTable(SafeEnv($_GET['name'], 255, str), SafeEnv($_POST['newname'], 255, str));
	GO(ADMIN_FILE.'?exe=fdbadmin');
}

AdminFdbAdminGenTableMenu(SafeEnv($_GET['name'], 255, str));

?>