<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$name = SafeDB($_GET['name'], 255, str);
$id = SafeDB($_GET['collid'], 11, int);
$coll = System::database()->GetColl($name, $id);
$install = "System::database()->InsertColl('$name', Unserialize('".Serialize($coll)."'), ".($id - 1).");";
$install2 = "System::database()->EditColl('$name', $id, Unserialize('".Serialize($coll)."'));";

AddCenterBox('���������� ��� ��������� ������� �������');
FormRow('���������', $site->TextArea('code', $install, 'style="width: 800px; height: 100px;"'));
FormRow('��������������', $site->TextArea('code', $install2, 'style="width: 800px; height: 100px;"'));
AddForm('', $site->Button('�����', 'onclick="history.go(-1);"'));

AdminFdbAdminGenTableMenu($name);
