<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

System::admin()->AddCenterBox('Создать таблицу');

FormRow('Имя таблицы (без префикса)', System::admin()->Edit('name', '', false, 'style="width: 200px;"'));
FormRow('Количество полей', System::admin()->Edit('cols', '', false, 'style="width: 50px;" title="Введите сюда количество колонок"'));
AddForm(
	'<form action="'.ADMIN_FILE.'?exe=fdbadmin&a=newtable" method="post">',
	System::admin()->Submit('Далее','title="Перейти к след. шагу создания таблицы."')
);

?>