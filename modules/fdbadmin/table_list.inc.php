<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

AddCenterBox('����� ������');

$tables = System::database()->GetTableInfo();

$sort = 'name';
$sort_dec = false;
if(isset($_GET['sort'])) $sort = $_GET['sort'];
if(isset($_GET['dec'])) $sort_dec = true;
SortArray($tables, $sort, $sort_dec);

$top_text = '';
$top_text .= '<strong>��:</strong> '.System::database()->SelectDbName.'<br>';
if(System::database()->Name == 'FilesDB'){
	$mode = '�������� ���� ������';
}else{
	$mode = 'MySQL';
}
$top_text .= '<strong>�����</strong>: '.$mode.'<br>';

$text = '';
$text .= '<table cellspacing="0" cellpadding="0" class="cfgtable">'
.'<tr>
	<th>#</th>
	<th>'.System::admin()->Link('�������', ADMIN_FILE.'?exe=fdbadmin&sort=name'.($sort == 'name' && !$sort_dec ? '&dec=1' : ''), '�����������').'</th>
	<th>'.System::admin()->Link('�������', ADMIN_FILE.'?exe=fdbadmin&sort=num_rows'.($sort == 'num_rows' && !$sort_dec ? '&dec=1' : ''), '�����������').'</th>
	<th>'.System::admin()->Link('������', ADMIN_FILE.'?exe=fdbadmin&sort=size'.($sort == 'size' && !$sort_dec ? '&dec=1' : ''), '�����������').'</th>
	<th>���</th>
	<th>��������</th>
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
	$func .= SpeedButton('�������������', ADMIN_FILE.'?exe=fdbadmin&a=renametable&name='.$r['name'], 'images/admin/rename.png');
	$func .= System::admin()->SpeedConfirm('�������', ADMIN_FILE.'?exe=fdbadmin&a=droptable&name='.$r['name'].'&ok=0', 'images/admin/delete.png', '������� �������?');

	$text .= '<tr>'
	.'<td style="text-align:left; padding-left:10px;">'.$i.$a.'</td>'
	.'<td align="left" style="text-align:left; padding-left:10px;"><b>'.System::admin()->Link($r['name'], ADMIN_FILE.'?exe=fdbadmin&a=structure&name='.$r['name']).'</b></td>'
	.'<td>'.$r['num_rows'].'</td>'
	.'<td>'.FormatFileSize($r['size']).'</td>'
	.'<td>'.(isset($r['type'])?$r['type']:'�� ���������').'</td>'
	.'<td class="cfgtd">'.$func.'</td>'
	.'</tr>';
	$totalsize += $r['size'];
	$totalrows += $r['num_rows'];
}
$text .= '</table><br><br>';
$top_text .= '<strong>������</strong>: '.System::database()->NumRows().'<br>';
$top_text .= '<strong>����� �������</strong>: '.$totalrows.'<br>';
$top_text .= '<strong>����� ������</strong>: '.FormatFileSize($totalsize).'<br>';

AddText($text);
