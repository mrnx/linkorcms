<?php

// �������������� �� �� ��������� �����

System::admin()->AddCenterBox('�������������� ���� ������');
$name = RealPath2(System::config('backup_dir').$_GET['name']);

// ������� �������� ��� �� �� ����� �����
// ����� �� �������� �� ������ ��������� � MySQL
function BackupGetDbType( $Name ){
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

$zip = new ZipArchive();
if(BackupGetDbType($name) && $zip->open($name) === true){ // ���������������
	if(System::database()->Name == 'FilesDB'){ // �������� ��
		$dbhost = System::database()->DbAccess;
		$to_unpack = array();
		for($i = 0; $i < $zip->numFiles; $i++){
			$filename = $zip->getNameIndex($i);
			$to_unpack[] = array('zip://'.$name."#".$filename, $path.$dbhost);
		}
		foreach($to_unpack as $file){
			copy($file[0], $file[1]);
		}
	}elseif(System::database()->Name == 'MySQL'){
		// �������������� �� �� ��������� �����



	}
	$zip->close();
	System::admin()->Highlight('���� ������ ������� ������������� �� ��������� �����.');
}else{
	// ������� ��������� �� ������
	System::admin()->HighlightError('������. �������� ������ �����, ���� ��� ����� ������� ���� ���� ������.');
}