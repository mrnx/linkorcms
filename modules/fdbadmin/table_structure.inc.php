<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$name = SafeEnv($_GET['name'], 255, str);

$info = $db->GetTableColumns($name);

$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">
	<tr>
		<th></th>
		<th>Поле</th>
		<th>Тип</th>
		<th>Атрибуты</th>
		<th>Ноль</th>
		<th>По умолчанию</th>
		<th>Авто приращение</th>
		<th>Функции</th>
	</tr>';
$i = 0;

foreach($info as $col){
	if(!isset($col['notnull'])){
		$col['notnull'] = false;
	}
	if(!isset($col['default'])){
		$col['default'] = '';
	}
	if(!isset($col['attributes'])){
		$col['attributes'] = '';
	}
	if(!isset($col['auto_increment'])){
		$col['auto_increment'] = false;
	}

	$func = '';
	$func .= SpeedButton('Просмотреть информацию для установки', ADMIN_FILE.'?exe=fdbadmin&a=viewcollinfo&name='.$name.'&collid='.$i, 'images/admin/info.png');
	$func .= System::admin()->SpeedConfirm('Удалить колонку', ADMIN_FILE.'?exe=fdbadmin&a=deletecoll&name='.$name.'&collid='.$i, 'images/admin/delete.png', 'Удалить колонку?');

	$text .= '<tr>
	<td>'.$i.'</td>
	<td>'.$col['name'].'</td>
	<td>'.$col['type'].(isset($col['length']) ? '('.$col['length'].')' : '').'</td>
	<td>'.$col['attributes'].'</td>
	<td>'.($col['notnull'] ? '<font color="#0000FF">Нет</font>' : '<font color="#FF0000">Да</font>').'</td>
	<td>'.$col['default'].'</td>
	<td>'.($col['auto_increment'] ? '<font color="#FF0000">Да</font>' : '<font color="0000FF">Нет</a>').'</td>
	<td>'.$func.'</td>';
	$i++;
}

$text .= '</table><br />';

AddCenterBox('Структура таблицы "'.$name.'"');
AddText($text);

System::admin()->FormTitleRow('Вставить колонку');
FormRow('После (индекс поля)', $site->Edit('toindex', '', false, 'style="width: 100px;"'), 160);
AddForm('<form action="'.ADMIN_FILE.'?exe=fdbadmin&a=newcoll&name='.$name.'" method="post">', $site->Submit('Далее', 'title="Перейти к след. шагу добавления колонки."'));
AdminFdbAdminGenTableMenu($name);
