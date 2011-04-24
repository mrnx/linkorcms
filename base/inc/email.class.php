<?php

# LinkorCMS
# © 2006-2010 Галицкий Александр Николаевич (linkorcms@yandex.ru)
# Файл: email.class.php
# Назначение: Класс для организации рассылки электронных писем.

class LcEmail
{

	public $From;
	public $FromName;

	public $Reply;
	public $ReplyName;

	public $Subject = ''; // Заголовок
	public $Body = ''; // Текст письма
	public $HtmlBody = ''; // Текст письма в Html

	public $Encoding = '8bit';  //Content-Transfer-Encoding
	public $Priority = 3;

	protected $CRLF = "\r\n";
	protected $Address = array();

	private $Mailer = '';

	// Параметры SMTP
	public $SmtpSend = false; // Использовать SMTP для отправки писем?
	public $SmtpHost = ''; // Имя сервера
	public $SmtpPort = 25; // Порт
	public $SmtpLogin = ''; // Имя пользователя
	public $SmtpPassword = ''; // Пароль
	public $SmtpConnectionTimeout = 30;

	public $ErrorMessage = ''; // Сообщение об ошибке



	function __construct()
	{
		global $config;
		$this->Mailer = CMS_VERSION_STR;
		if(isset($config['smtp']['use_smtp']) && $config['smtp']['use_smtp']){
			$this->SmtpSend = true;
			$this->SmtpHost = $config['smtp']['host'];
			$this->SmtpPort = $config['smtp']['port'];
			$this->SmtpLogin = $config['smtp']['login'];
			$this->SmtpPassword = $config['smtp']['password'];
		}
	}

	public function SetFrom( $Address, $Name = '' )
	{
		$this->From = $Address;
		$this->FromName = $Name;
	}

	public function SetReply( $Address, $Name = '' )
	{
		$this->Reply = $Address;
		$this->ReplyName = $Name;
	}

	public function SetSubject( $Subject )
	{
		$this->Subject = $Subject;
	}

	public function SetBody( $Text, $TextHtml )
	{
		$this->Body = $Text;
		$this->HtmlBody = $TextHtml;
	}

	public function SetPriority( $Priority = 3 )
	{
		$this->Priority = $Priority;
	}

	public function AddAddress( $Address, $Name = '', $Html = false )
	{
		$this->Address[] = array( $Address, $Name, $Html );
	}

	protected function Base64WrapMb( $String )
	{
		mb_internal_encoding('utf-8');
		$start = '=?UTF-8?B?';
		$end = '?=';
		$max_length = 64;

		$mb_length = mb_strlen($String);

		$str = '';
		$base = '';
		$base2 = '';
		$encoded = '';
		for($i=0; $i<$mb_length; $i++){
			$s = mb_substr($String, $i, 1);
			$base = base64_encode($str.$s);
			if(strlen($base) > $max_length){
				$encoded .= $start.$base2.$end.$this->CRLF;
				$str = '';
			}else{
				$base2 = $base;
			}
			$str .= $s;
		}
		if($str != ''){
			$encoded .= $start.$base2.$end.$this->CRLF;
		}
		$encoded = substr($encoded, 0, -strlen($this->CRLF));
		return $encoded;
	}

	protected function Base64( $String )
	{
		return '=?UTF-8?B?'.base64_encode($String).'?=';
	}

	protected function HeaderLine( $Name, $Value )
	{
	    return $Name.': '.$Value.$this->CRLF;
	}

	protected function CreateHeader()
	{
		$headers = '';

		$headers .= $this->HeaderLine('Date', date("D, d M Y H:i:s").' UT');
		$headers .= $this->HeaderLine('Return-Path', $this->From);
		$headers .= $this->HeaderLine('From', $this->Base64($this->FromName).' <'.$this->From.'>');

		if($this->Reply != ''){
			$headers .= $this->HeaderLine('Reply-to', $this->Base64($this->ReplyName).' <'.$this->Reply.'>');
		}
		$headers .= $this->HeaderLine('X-Mailer', $this->Mailer);
		$headers .= $this->HeaderLine('Content-Transfer-Encoding', $this->Encoding);
		$headers .= $this->HeaderLine('X-Priority', $this->Priority);

		return $headers;
	}

	private function SmtpParseResponse( $Socket, $Response )
	{
		$Response .= ' ';
		while($serverText = fgets($Socket, 256)){
			if(substr($serverText,0,4) == $Response){
				return true;
			}
		}
		return false;
	}

	public function SmtpMail( $To, $Subject, $Message, $Headers )
	{

		// Соединяемся с сервером
		if(!$socket = fsockopen($this->SmtpHost, $this->SmtpPort, $errno, $errstr, $this->SmtpConnectionTimeout)){
			$this->ErrorMessage = $errno."&nbsp;".$errstr;
			fclose($socket);
			return false;
		}elseif(!$this->SmtpParseResponse($socket, '220')){
			fclose($socket);
			return false;
		}

		// Отправляем приглашение
		fputs($socket, 'EHLO '.$_SERVER['SERVER_ADDR'].$this->CRLF);
		if(!$this->SmtpParseResponse($socket, '250')){
			fputs($socket, 'HELO '.$_SERVER['SERVER_ADDR'].$this->CRLF);

			if(!$this->SmtpParseResponse($socket, '250')){
				$this->ErrorMessage = 'Ошибка SMTP - Не удалось отправить HELO.';
				fclose($socket);
				return false;
			}
		}

		// Запрашиваем авторизацию
		fputs($socket, 'AUTH LOGIN'.$this->CRLF);
		if(!$this->SmtpParseResponse($socket, '334')){
			$this->ErrorMessage = 'Ошибка SMTP - Нет ответа на запрос авторизации.';
			fclose($socket);
			return false;
		}

		// Отправляем имя пользователя/пароль
		fputs($socket, base64_encode($this->SmtpLogin).$this->CRLF);
		if(!$this->SmtpParseResponse($socket, '334')){
			$this->ErrorMessage = 'Ошибка SMTP - Логин не был принят сервером.';
			fclose($socket);
			return false;
		}
		fputs($socket, base64_encode($this->SmtpPassword).$this->CRLF);
		if(!$this->SmtpParseResponse($socket, '235')){
			$this->ErrorMessage = 'Ошибка SMTP - Ошибка авторизации.';
			fclose($socket);
			return false;
		}

		fputs($socket, 'MAIL FROM:<'.$this->SmtpLogin.'>'.$this->CRLF);
		if(!$this->SmtpParseResponse($socket, '250')){
			$this->ErrorMessage = 'Ошибка SMTP - Не удалось отправить MAIL FROM.';
			fclose($socket);
			return false;
		}

		fputs($socket, 'RCPT TO:<'.$To[0].'>'.$this->CRLF);
		if(!$this->SmtpParseResponse($socket, '250')){
			$this->ErrorMessage = 'Ошибка SMTP - Не удалось отправить RCPT TO.';
			fclose($socket);
			return false;
		}

		fputs($socket, 'DATA'.$this->CRLF);
		if(!$this->SmtpParseResponse($socket, '354')){
			$this->ErrorMessage = 'Ошибка SMTP - Не удалось отправить DATA.';
			fclose($socket);
			return false;
		}

		$Headers .= 'To: '.$this->Base64($To[1]).' <'.$To[0].'>'.$this->CRLF;
		$Headers .= 'Subject: '.$Subject.$this->CRLF.$this->CRLF;
		$Headers .= $Message.$this->CRLF.'.';

		fputs($socket, $Headers.$this->CRLF);
		if(!$this->SmtpParseResponse($socket, '250')){
			$this->ErrorMessage = 'Ошибка SMTP - Не удалось отправить тело письма.';
			fclose($socket);
			return false;
		}

		fputs($socket, 'QUIT'.$this->CRLF);
		fclose($socket);

		return true;
	}

	public function Send()
	{
		$headers = $this->CreateHeader();
		$params = '-oi -f '.$this->Base64($this->FromName).' <'.$this->From.'>';
		foreach($this->Address as $to){
			if($to[2] == true){
				$ContentType = 'text/html';
				$message = $this->HtmlBody;
			}else{
				$ContentType = 'text/plain';
				$message = $this->Body;
			}
			$headers .= $this->HeaderLine('Content-Type', $ContentType.'; charset="UTF-8"');

			if(!$this->SmtpSend){
				return mail(
					$this->Base64($to[1]).' <'.$to[0].'>',
					$this->Base64WrapMb($this->Subject),
					$message,
					$headers,
					$params
				);
			}else{
				return $this->SmtpMail(
					$to,
					$this->Base64WrapMb($this->Subject),
					$message,
					$headers
				);
			}
		}
		return false;
	}

}

?>