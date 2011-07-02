<?php

/**
 * Проверяет адрес электронной почты на корректность
 *
 * @param String $email // e-mail адрес
 * @return Boolean
 */
function CheckEmail( $email ){
	return (preg_match('/^[-!#$%&\'*+\\.\/0-9=?A-Z^_`{|}~]+@([-0-9A-Z]+\.)+([0-9A-Z]){2,4}$/i',trim($email)));
}

/**
 * Отправляет E-mail
 * @param $ToName
 * @param $ToEmail
 * @param $Subject
 * @param $Text
 * @param bool $Html
 * @param string $From
 * @param string $FromEmail
 */
function SendMail( $ToName, $ToEmail, $Subject, $Text, $Html=false, $From='', $FromEmail='' ){
	global $config;
	$mail = LmEmailExtended::Instance();

	if($From == '' && $FromEmail == ''){
		$mail->SetFrom($config['general']['site_email'], Cp1251ToUtf8($config['general']['site_name']));
	}else{
		$mail->SetFrom($FromEmail, Cp1251ToUtf8($From));
	}
	$mail->SetSubject(Cp1251ToUtf8($Subject));

	if(!$Html){
		$mail->AddTextPart(Cp1251ToUtf8($Text));
	}else{
		$mail->AddHtmlPart(Cp1251ToUtf8($Text));
	}

	$mail->AddTo($ToEmail, Cp1251ToUtf8($ToName));
	if(!$mail->Send()){
		 ErrorHandler(USER_ERROR, $mail->ErrorMessage, __FILE__);
	}
}

function AntispamEmail($email, $addjava=true){
	global $site;
	static $javaAdd = false;
	if(!$javaAdd && $addjava){
		$site->AddJS('
		function email(login,domain){
			mail = login+"@"+domain;
			mail = \'<a href="mailto:\'+mail+\'" target="_blank">\'+mail+\'</a>\';
			document.write(mail);
		}
		');
		$javaAdd = true;
	}
	$email = explode('@', $email);
	if(count($email) == 2){
		return '<script>email(\''.$email[0].'\',\''.$email[1].'\');</script>';
	}else{
		return '';
	}
}
