<?php

// ����������� ���� ������ ��

AddCenterBox('�����������');

if(System::database()->Name != 'MySQL'){
	System::admin()->HighlightError('������ MySQL ���� ������.');
	return;
}

$iferrors = false;
$tables = System::database()->GetTableInfo();
System::database()->MySQLQuery('LOCK TABLES');
foreach($tables as $table){
	$table = System::database()->Prefix().$table['name'];
	if(System::database()->MySQLQuery('OPTIMIZE TABLE `'.$table.'`') == false){
		System::admin()->HighlightError(System::database()->MySQLGetErrMsg().' ('.$table.')');
		$iferrors = true;
	}
}
System::database()->MySQLQuery('UNLOCK TABLES');
if($iferrors){
	System::admin()->Highlight('��������� ������ ��� ����������� ��������� ������.');
}else{
	System::admin()->Highlight('��� ������� ������� ��������������.');
}