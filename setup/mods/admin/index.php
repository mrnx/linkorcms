<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(!System::user()->isDef('setup_type')){
	System::user()->Def('setup_type', 'install');
}

if(isset($_GET['p'])){
	$p = SafeEnv($_GET['p'], 1, int);
}else{
	$p = 1;
}

switch($p){
	case 1:
		$this->SetTitle("�������� ������� ������ �������� ��������������");
		$this->OpenForm('admin&p=2');
		$text = '<table width="80%">
			<tr>
				<td id="l">�����: </td>
				<td><input type="text" name="login" value="admin"></td>
			</tr>
			<tr>
				<td id="l">������: </td>
				<td><input type="text" name="pass" value="'.GenBPass(8).'"></td>
			</tr>
			<tr>
				<td id="l">E-mail: </td>
				<td><input type="text" name="email" value="support@'.getenv("HTTP_HOST").'"></td>
			</tr>
		</table>
		<br /><br />�������� ��� ��������� ��������� ������!';
		$this->SetContent($text);
		$this->AddSubmitButton('�����');
		break;
	case 2:
		$errors = array();
		if(isset($_POST['login']) && CheckLogin($_POST['login'], $errors, false, 0)){
			$login = SafeEnv($_POST['login'], 15, str);
		}else{
			$login = '';
		}
		$pass = '';
		if(isset($_POST['pass']) && CheckPass($_POST['pass'], $errors)){
			$pass = SafeEnv($_POST['pass'], 30, str);
		}else{
			$pass = '';
		}
		$pass2 = md5($pass);
		if(isset($_POST['email']) && $_POST['email'] != ''){
			if(CheckEmail($_POST['email'])){
				$email = SafeEnv($_POST['email'], 50, str, true);
			}else{
				$errors[] = '������ E-mail �� ����������. �� ������ ���� ����: <b>domain@host.ru</b> .';
			}
		}else{
			$errors[] = '�� �� ����� E-mail.';
		}
		if(count($errors) > 0){
			$this->SetTitle("�������� ������� ������ �������� ��������������");
			$text = '������:<br /><ul>';
			foreach($errors as $error){
				$text .= '<li>'.$error;
			}
			$text .= '</ul>';
			$this->SetContent($text);
			$this->AddButton('�����', 'admin&p=1');
		}else{
			// ��������������� � ������.
			global $db, $config;
			System::database()->Update('users', "login='$login',pass='$pass2',email='$email'", "`id`='7'");
			// ������������� ���������� � ������������� URL ����� � ����������.
			ConfigSetValue('general', 'site_url', GetSiteUrl());
			GO('setup.php?mod=finish');
		}
		break;
}

?>