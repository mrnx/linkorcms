<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$site->Title = '�������� �����';

function IndexFeedBackForm()
{
	global $site, $db, $config;
	$site->AddTemplatedBox('�������� �����', 'module/feedback.html');

	$site->AddBlock('feedback');
	$vars['url'] = Ufu('index.php?name=feedback&op=send', 'feedback/{op}/');
	$vars['top_text'] = $config['feedback']['top_text'];
	$vars['bottom_text'] = $config['feedback']['bottom_text'];
	$vars['max_attachment_size'] = ini_get('upload_max_filesize');

	// �����
	$vars['show_kaptcha'] = true;
	$vars['kaptcha_url'] = 'index.php?name=plugins&p=antibot';
	$vars['kaptcha_width'] = '120';
	$vars['kaptcha_height'] = '40';

	$site->Blocks['feedback']['vars'] = $vars;

	$site->AddBlock('departments', true, true, 'department');
	$db->Select('feedback', "`active`='1'");
	$vars = array();
	$vars['id'] = 0;
	$vars['hname'] = '';
	$site->AddSubBlock('departments', true, $vars);
	while($dp = $db->FetchRow()){
		$vars = array();
		$vars['id'] = SafeDB($dp['id'], 11, int);
		$vars['hname'] = SafeDB($dp['name'], 255, str);
		$site->AddSubBlock('departments', true, $vars);
	}
}

function IndexFeedBackSend()
{
	global $db, $config, $site, $user;
	$err = array();
	if(!isset($_POST['name'])
		|| !isset($_POST['email'])
		|| !isset($_POST['subject'])
		|| !isset($_POST['department'])
		|| !isset($_POST['message'])
		|| !isset($_POST['feedback_form'])
	){
		GO(Ufu('index.php'));
	}else{
		if($_POST['name'] != ''){
			$name = SafeDB($_POST['name'], 250, str);
		}else{
			$err[] = '����������, ������� ���� ���!';
		}
		if($_POST['email'] != ''){
			$email = SafeDB($_POST['email'], 50, str);
		}else{
			$err[] = '����������, ������� ��� �������������� ����� E-mail!';
		}
		if($_POST['subject'] != ''){
			$subject = SafeDB($_POST['subject'], 250, str, false, false, false);
		}else{
			$err[] = '����������, ������� ���� ���������!';
		}

		// ��������� �����
		if(!$user->isDef('captcha_keystring') || $user->Get('captcha_keystring') != $_POST['keystr']){
			$err[] = '�� �������� ��� ����� ���� � ��������.';
		}

		if($_POST['department'] != ''){
			$department = SafeEnv($_POST['department'], 11, int);
			$db->Select('feedback', "`active`='1' and `id`='$department'");
			if($db->NumRows() > 0){
				$dep = $db->FetchRow();
				$dep_email = SafeDB($dep['email'], 255, str);
				$department = SafeDB($dep['name'], 255, str);
			}else{
				$err[] = '����������� ������ �� ���������� ��� �������� ����� � ���� ������������� ���������.';
			}
		}else{
			$err[] = '����������, �������� �����������!';
		}
		if($_POST['message'] != ''){
			$message = SafeDB($_POST['message'], 65535, str, false, false, false);
		}else{
			$err[] = '����������, ������� ���������!';
		}
	}

	if(count($err) == 0){

		$mail = LmEmailExtended::Instance();
		$mail->SetTo($dep_email, Cp1251ToUtf8($department));
		$mail->SetFrom($email, Cp1251ToUtf8($name));
		$mail->SetSubject(Cp1251ToUtf8($subject));

		$text = "������������!\n\n"
		."� ������� ����� �������� ����� �� ����� \"".$config['general']['site_name']."\".\n"
		."��� ���� ���������� ���������.\n\n"
		."�����������: ".$department."\n"
		."���: ".$name."\n"
		."E-mail: ".$email."\n"
		."���� ���������: ".$subject."\n"
		."���� ��������: ".TimeRender(time(), true, false)."\n"
		."���������:\n".$message."\n";

		$mail->AddTextPart(Cp1251ToUtf8($text));

		if($_FILES['attach']['error'] == UPLOAD_ERR_OK){
			$mail->AddAttachmentPart(file_get_contents($_FILES['attach']['tmp_name']), $_FILES['attach']['name']);
		}

		if($mail->Send()){
			$site->AddTextBox('�������� �����', '<div style="text-align: center;">���� ��������� ������� ����������!</div>');
		}else{
			$site->AddTextBox('�������� �����', '<div style="text-align: center;">��� �������� ������ ��������� ��������� ������, ��������� ������� ��� ���������� � ��������������.</div>');
		}

	}else{
		$text = '��������� �� ����������! :<br /><ul>';
		foreach($err as $error){
			$text .= '<li>'.$error.'</li>';
		}
		$text .= '</ul>';
		$site->AddTextBox('������', $text);
	}

}

if(isset($_GET['op'])){
	$op = $_GET['op'];
}else{
	$op = 'main';
}

switch($op){
	case 'main':
		IndexFeedBackForm();
		break;
	case 'send':
		IndexFeedBackSend();
		break;
	default:
		HackOff();
}

?>