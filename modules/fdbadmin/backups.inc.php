<?php

AddCenterBox('��������� �����');

$backup_dir = System::config('backup_dir');
if(!is_writable($backup_dir)){
	System::admin()->HighlightError('<strong style="color: #FF0000;">��������!</strong> ��� ���� �� ������ � ����� '.$backup_dir.'. �������� ��������� ����� �� ��������.');
}

function BackupCheckDbType( $Name ){
	$pos = strrpos($Name, '.');
	if($pos === false){
		return false;
	}
	$ext = substr($Name, $pos+1); // zip
	$pos2 = strrpos($Name, '.', -strlen($ext)-2);
	if($pos2 === false){
		return false;
	}
	$ext2 = substr($Name, $pos2+1, $pos-$pos2-1); // MySQL
	if($ext != 'zip' || $ext2 != System::database()->Name){
		return false;
	}
	return true;
}

System::admin()->AddJS('
CreateBackup = function(){
	Admin.ShowSplashScreen("�������� ��������� �����");
	$.ajax({
		type: "POST",
		url: "'.ADMIN_FILE.'?exe=fdbadmin&a=backup_create",
		data: {},
		success: function(data){
			Admin.LoadPage("'.ADMIN_FILE.'?exe=fdbadmin&a=backups", undefined, "���������� ��������");
			Admin.HideSplashScreen();
		}
	});
};
');

$backup_files = GetFiles($backup_dir, false, true, '.zip');
rsort($backup_files, SORT_STRING);
$backup_files2 = array();
foreach($backup_files as $file){
	if(BackupCheckDbType($file)){
		$backup_files2[] = $file;
	}
}

$text = '';
$text .= '<table cellspacing="0" cellpadding="0" class="cfgtable">';
$text .= '<tr><th>��� �����</th><th>�������</th></tr>';

foreach($backup_files2 as $file){
	$file = SafeDB($file, 255, str);
	$func = System::admin()->SpeedConfirm('������������', ADMIN_FILE.'?exe=fdbadmin&a=backup_restore&name='.$file, 'images/admin/restore.png', '��� ������� ������ ����� ������. ������������ �� �� ��������� �����?');
	$func .= System::admin()->SpeedConfirm('�������', ADMIN_FILE.'?exe=fdbadmin&a=backup_delete&name='.$file, 'images/admin/delete.png', '�������?');
	$text .= '<tr>
	<td><a href="'.$backup_dir.$file.'">'.$file.'</a></td>
	<td>'.$func.'</td>
	</tr>';
}

if(count($backup_files2) == 0){
	$text .= '<tr><td colspan="2" style="text-align: left;">��� ��������� �����.</td></tr>';
}
$text .= '</table>';
$text .= '<a href="#" id="backup_button" class="button" onclick="CreateBackup(); return false;">������� ��������� �����</a>';

AddText($text);
