<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

AddCenterBox('Обзор таблиц');

$tables = $db->GetTableInfo();

$sort = 'name';
$sort_dec = false;
if(isset($_GET['sort'])) $sort = $_GET['sort'];
if(isset($_GET['dec'])) $sort_dec = true;
SortArray($tables, $sort, $sort_dec);

$top_text = '';
$top_text .= '<strong>БД:</strong> '.System::db()->SelectDbName.'<br>';
if(System::db()->Name == 'FilesDB'){
	$mode = 'Файловая база данных';
}else{
	$mode = 'MySQL';
}
$top_text .= '<strong>Режим</strong>: '.$mode.'<br>';

$text = '';
$text .= '<table cellspacing="0" cellpadding="0" class="cfgtable">'
.'<tr>
	<th>#</th>
	<th><a href="'.$config['admin_file'].'?exe=fdbadmin&sort=name'.($sort == 'name' && !$sort_dec ? '&dec=1' : '').'">Таблица</a></th>
	<th><a href="'.$config['admin_file'].'?exe=fdbadmin&sort=num_rows'.($sort == 'num_rows' && !$sort_dec ? '&dec=1' : '').'">Записей</a></th>
	<th><a href="'.$config['admin_file'].'?exe=fdbadmin&sort=size'.($sort == 'size' && !$sort_dec ? '&dec=1' : '').'">Размер</a></th>
	<th>Тип</th>
	<th>Действия</th>
</tr>';
$totalsize = 0;
$totalrows = 0;
$light = array();

$i = 0;
foreach($tables as $r){
	$i++;
	$a = '';
	if($sort == 'name'){
		if(!isset($light[SafeDb($r['name'], 1, str)])){
			$light[SafeDb($r['name'], 1, str)] = SafeDb($r['name'], 1, str);
			$a = '<span style="float:right; font-size:18px; margin-right:10px;"><b>'.strtoupper(SafeDb($r['name'], 1, str)).'</b></span>';
		}
	}

	$func = '';
	$func .= SpeedButton('Переименовать', $config['admin_file'].'?exe=fdbadmin&a=renametable&name='.$r['name'], 'images/admin/rename.png');
	$func .= SpeedButton('Удалить', $config['admin_file'].'?exe=fdbadmin&a=droptable&name='.$r['name'].'&ok=0', 'images/admin/delete.png');

	$text .= '<tr>'
	.'<td style="text-align:left; padding-left:10px;">'.$i.$a.'</td>'
	.'<td align="left" style="text-align:left; padding-left:10px;"><b><a href="'.$config['admin_file'].'?exe=fdbadmin&a=structure&name='.$r['name'].'">'.$r['name'].'</a></b></td>'
	.'<td>'.$r['num_rows'].'</td>'
	.'<td>'.FormatFileSize($r['size']).'</td>'
	.'<td>'.(isset($r['type'])?$r['type']:'По умолчанию').'</td>'
	.'<td class="cfgtd">'.$func.'</td>'
	.'</tr>';
	$totalsize += $r['size'];
	$totalrows += $r['num_rows'];
}
$text .= '</table><br><br>';
$top_text .= '<strong>Таблиц</strong>: '.$db->NumRows().'<br>';
$top_text .= '<strong>Всего записей</strong>: '.$totalrows.'<br>';
$top_text .= '<strong>Общий размер</strong>: '.FormatFileSize($totalsize).'<br>';

AddText($text);
System::admin()->SideBarAddTextBlock('', $top_text);

?>