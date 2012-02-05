<?php

// �������������� �� �� ��������� �����


$name = RealPath2(System::config('backup_dir').$_GET['name']);
if(isset($_GET['table'])){
	$table = $_GET['table'];
	System::admin()->AddCenterBox('�������������� ������� "'.$table.'"');
	$table = System::database()->Prefix().$table;
}else{
	$table = '';
	System::admin()->AddCenterBox('�������������� ���� ������');
}
$iferrors = false;

$zip = new ZipArchive();
if(BackupCheckDbType($name) && $zip->open($name) === true){
	if(System::database()->Name == 'FilesDB'){ // �������� ��
		$dbhost = System::database()->DbAccess;
		$to_unpack = array();
		for($i = 0; $i < $zip->numFiles; $i++){
			$filename = $zip->getNameIndex($i);
			if($table == '' || $table == GetFileName($filename)){
				$to_unpack[] = array('zip://'.$name."#".$filename, $path.$dbhost);
			}
		}
		foreach($to_unpack as $file){
			copy($file[0], $file[1]);
		}
	}elseif(System::database()->Name == 'MySQL'){ // �� MySQL
		for($i = 0; $i < $zip->numFiles; $i++){
			$filename = $zip->getNameIndex($i);
			if($table == '' || $table == GetFileName($filename)){
				$sql = $zip->getFromIndex($i);
				$sql = explode(";\n", $sql);
				foreach($sql as $query){
					if(trim($query) == '') continue;
					if(System::database()->MySQLQuery($query) === false){
						System::admin()->HighlightError(System::database()->MySQLGetErrMsg().' ('.$filename.')');
						$iferrors = true;
					}
				}
			}
		}
	}
	$zip->close();
	if($iferrors){
		System::admin()->Highlight('��������� ������ ��� �������������� ��������� ������.');
	}else{
		if($table == ''){
			System::admin()->Highlight('���� ������ ������� ������������� �� ��������� �����.');
		}else{
			System::admin()->Highlight('������� "'.$table.'" ������� ������������� �� ��������� �����.');
		}
	}
}else{
	// ������� ��������� �� ������
	System::admin()->HighlightError('������. �������� ������ �����, ���� ��� ����� ������� ���� ���� ������.');
}