<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$name = SafeEnv($_GET['name'], 255, str);

$info = $db->GetTableColumns($name);
$rows = $db->Select($name, '');

AddCenterBox('Обзор таблицы "'.$name.'"');

if(isset($_GET['page'])){
	$page = SafeEnv($_GET['page'], 11, int);
}else{
	$page = 1;
}
$rows_on_page = 20;
if(count($rows) > $rows_on_page){
	$navigator = new Navigation($page);
	$navigator->GenNavigationMenu($rows, $rows_on_page, $config['admin_file'].'?exe=fdbadmin&a=review&name='.$name);
	AddNavigation();
	$nav = true;
}else{
	$nav = false;
	AddText('<br />');
}

$text = '
<table cellspacing="0" cellpadding="0" class="cfgtable" valign="top">
	<tr>
		<th>Действие</th>
		<th width="2">
	</th>';

$nc = count($info);
foreach($info as $col){
	$text .= '<th>'.$col['name'].'</th>';
}
$text .= '</tr>';

$i = ($rows_on_page * $page) - $rows_on_page;
foreach($rows as $col){
	$func = '';
	$func .= SpeedButton('Редактировать', $config['admin_file'].'?exe=fdbadmin&a=editfield&name='.SafeEnv($_GET['name'], 255, str).'&index='.$i, 'images/admin/edit.png');
	$func .= System::admin()->SpeedConfirm('Удалить', ADMIN_FILE.'?exe=fdbadmin&a=deleterow&name='.SafeEnv($_GET['name'], 255, str).'&index='.$i, 'images/admin/delete.png', 'Удалить запись?');
	$func .= SpeedButton('PHP код вставки', $config['admin_file'].'?exe=fdbadmin&a=viewcode&name='.SafeEnv($_GET['name'], 255, str).'&index='.$i, 'images/admin/php.png');

	$text .= '<tr><td nowrap="nowrap">'.$func.'</td><td></td>';
	for($j = 0; $j < $nc; $j++){
		$col_name = $info[$j]['name'];
		(strlen($col[$col_name]) > 255 ? $p = '... ...' : $p = '');
		$text .= '<td>'.substr(htmlspecialchars($col[$col_name]), 0, 255).$p.'</td>';
	}
	$text .= '</tr>';
	$i++;
}

$text .= '</table>';
AddText($text);
if($nav){
	AddNavigation();
}

AdminFdbAdminGenTableMenu($name);
