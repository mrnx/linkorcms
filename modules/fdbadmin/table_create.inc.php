<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

System::admin()->AddCenterBox('������� �������');

FormRow('��� ������� (��� ��������)', System::admin()->Edit('name', '', false, 'style="width: 200px;"'));
FormRow('���������� �����', System::admin()->Edit('cols', '', false, 'style="width: 50px;" title="������� ���� ���������� �������"'));
AddForm(
	'<form action="'.ADMIN_FILE.'?exe=fdbadmin&a=newtable" method="post">',
	System::admin()->Submit('�����','title="������� � ����. ���� �������� �������."')
);

?>