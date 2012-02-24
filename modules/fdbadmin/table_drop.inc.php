<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(isset($_GET['ok'])){
	$ok = $_GET['ok'];
}else{
	$ok = false;
}

if($ok){
	System::database()->DropTable(SafeEnv($_GET['name'], 255, str));
	GO(ADMIN_FILE.'?exe=fdbadmin');
}else{
	$name = SafeDB($_GET['name'], 255, str);
	AddCenterBox('Удаление таблицы');
	System::admin()->HighlightConfirm('Вы действительно хотите удалить таблицу "'.$name.'"?', ADMIN_FILE.'?exe=fdbadmin&a=droptable&name='.$name.'&ok=1');
	AdminFdbAdminGenTableMenu($name);
}
