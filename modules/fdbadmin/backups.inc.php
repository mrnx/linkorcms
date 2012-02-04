<?php

AddCenterBox('Резервные копии');

$backup_dir = System::config('backup_dir');
if(!is_writable($backup_dir)){
	System::admin()->HighlightError('<strong style="color: #FF0000;">Внимание!</strong> Нет прав на запись в папку '.$backup_dir.'. Создание резервных копий не возможно.');
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
	Admin.ShowSplashScreen("Создание резервной копии");
	$.ajax({
		type: "POST",
		url: "'.ADMIN_FILE.'?exe=fdbadmin&a=backup_create",
		data: {},
		success: function(data){
			Admin.LoadPage("'.ADMIN_FILE.'?exe=fdbadmin&a=backups", undefined, "Обновление страницы");
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
$text .= '<tr><th>Имя файла</th><th>Функции</th></tr>';

foreach($backup_files2 as $file){
	$file = SafeDB($file, 255, str);
	$func = System::admin()->SpeedConfirm('Восстановить', ADMIN_FILE.'?exe=fdbadmin&a=backup_restore&name='.$file, 'images/admin/restore.png', 'Все текущие данные будут затёрты. Восстановить БД из резервной копии?');
	$func .= System::admin()->SpeedConfirm('Удалить', ADMIN_FILE.'?exe=fdbadmin&a=backup_delete&name='.$file, 'images/admin/delete.png', 'Удалить?');
	$text .= '<tr>
	<td><a href="'.$backup_dir.$file.'">'.$file.'</a></td>
	<td>'.$func.'</td>
	</tr>';
}

if(count($backup_files2) == 0){
	$text .= '<tr><td colspan="2" style="text-align: left;">Нет резервных копий.</td></tr>';
}
$text .= '</table>';
$text .= '<a href="#" id="backup_button" class="button" onclick="CreateBackup(); return false;">Создать резервную копию</a>';

AddText($text);
