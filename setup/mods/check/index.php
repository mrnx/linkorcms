<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(
	extension_loaded('gd')
	&& extension_loaded('mbstring')
	&& extension_loaded('iconv')
){
	$smod = SafeEnv($_POST['setup_type'], 255, str);
	GO('setup.php?mod='.$smod);
}else{
	$ok = '<img src="images/admin/accept.png" alt="��">';
	$fail = '<img src="images/admin/delete.png" alt="���������� �� �����������">';
	$this->SetTitle('�������� �������');
	$text = '<table width="80%">
		<tr>
			<td id="l" width="50%">Gd:</td>
			<td>'.(extension_loaded('gd') ? $ok : $fail).'</td>
		</tr>
		<tr>
			<td id="l">MbString:</td>
			<td>'.(extension_loaded('mbstring') ? $ok : $fail).'</td>
		</tr>
		<tr>
			<td id="l">Iconv:</td>
			<td>'.(extension_loaded('iconv') ? $ok : $fail).'</td>
		</tr>
		<tr>
			<td colspan="2"><br />��������� ���������� PHP ������� ����������
			LinkorCMS �� �����������. LinkorCMS �� ����� �������� �� ���� �������
			���� �� ����� ����������� ��� ������������ ��������� ���������� PHP.
			����������� ��������� ���������� ��� ���������� � ������ �������
			����������.</td>
		</tr>
		</table>';
	$this->SetContent($text);
	$this->AddButton('�����', 'main&p=2');
}

?>