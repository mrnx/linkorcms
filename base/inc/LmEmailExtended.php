<?php

# LinkorCMS
# © 2006-2010 Галицкий Александр Николаевич (galitsky@pochta.ru)
# Файл: email.class.php
# Назначение: Класс для организации рассылки электронных писем.

class EmailOptions{
	public $XMailer;
	public $SmtpSend = false; // Использовать SMTP для отправки писем?
	public $SmtpHost = ''; // Имя сервера
	public $SmtpPort = 25; // Порт
	public $SmtpLogin = ''; // Имя пользователя
	public $SmtpPassword = ''; // Пароль

	public $CRLF = "\r\n";

	function __construct()
	{
		global $config;
		$this->XMailer = CMS_VERSION_STR;
		if(isset($config['smtp']['use_smtp']) && $config['smtp']['use_smtp']){
			$this->SmtpSend = true;
			$this->SmtpHost = $config['smtp']['host'];
			$this->SmtpPort = $config['smtp']['port'];
			$this->SmtpLogin = $config['smtp']['login'];
			$this->SmtpPassword = $config['smtp']['password'];
		}
	}
}

class LmEmailExtended{
	static protected $_instance;

	public $From;
	public $FromName;
	public $ReplyTo;
	public $ReplyToName;
	public $Subject = ''; // Заголовок письма

	public $XPriority = 3;
	public $XMailer = 'LinkorCMS LmEmail Extended';

	public $ToAddress = array();

	public $BodyParts = array();

	// Параметры SMTP
	public $SmtpSend = false; // Использовать SMTP для отправки писем?
	public $SmtpHost = ''; // Имя сервера
	public $SmtpPort = 25; // Порт
	public $SmtpLogin = ''; // Имя пользователя
	public $SmtpPassword = ''; // Пароль
	public $SmtpConnectionTimeout = 30;
	public $SmtpLog = '';

	public $CRLF = "\r\n";
	protected $Boundary = '';

	function __construct( EmailOptions $options ){
		$this->Initialize($options);
	}

	/**
	 * Функция инициализации класса.
	 * @param CacheOptions $options
	 */
	public function Initialize( EmailOptions $options ){
		$this->XMailer = $options->XMailer;
		$this->SmtpSend = $options->SmtpSend;
		$this->SmtpHost = $options->SmtpHost;
		$this->SmtpPort = $options->SmtpPort;
		$this->SmtpLogin = $options->SmtpLogin;
		$this->SmtpPassword = $options->SmtpPassword;
	}

	/**
	 * Установить поле "От кого"
	 * @param <type> $Address
	 * @param <type> $Name
	 */
	public function SetFrom( $Address, $NameUtf8 = '' ){
		$this->From = $Address;
		$this->FromName = $NameUtf8;
	}

	/**
	 * Установить адрес ответа
	 * @param <type> $Address
	 * @param <type> $Name
	 */
	public function SetReply( $Address, $NameUtf8 = '' ){
		$this->ReplyTo = $Address;
		$this->ReplyToName = $NameUtf8;
	}

	/**
	 * Установить тему письма
	 * @param <type> $Subject
	 */
	public function SetSubject( $SubjectUtf8 ){
		$this->Subject = $SubjectUtf8;
	}

	/**
	 * Устанавливает значение необязательного заголовка обозначающего приоритет письма
	 * @param <type> $Priority
	 */
	public function SetPriority( $Priority = 3 ){
		$this->Priority = $Priority;
	}

	/**
	 * Добавить адрес получателя
	 * @param <type> $Address
	 * @param <type> $Name
	 */
	public function AddTo( $Address, $NameUtf8 = '' ){
		$this->ToAddress[] = array( $Address, $NameUtf8 );
	}

	/**
	 * Очистить список адресов и установить новый адрес получателя
	 * @param <type> $Address
	 * @param <type> $Name
	 */
	public function SetTo( $Address, $NameUtf8 = '' )
	{
		$this->ToAddress = array();
		$this->ToAddress[] = array( $Address, $NameUtf8 );
	}

	protected function GetBoundary(){
		if($this->Boundary == ''){
			$tag = md5(uniqid(time()).(isset($this->ToAddress[0][0]) ? $this->ToAddress[0][0] : ''));
			$this->Boundary = '----NextPart_'.substr($tag, 0, 3).'-'.substr($tag, 4, 11).'-'.substr($tag, 12, 19);
		}
		return $this->Boundary;
	}

	public function AddTextPart( $TextUtf8 ){
		$part = array();
		$part['Content-Type'] = 'text/plain; charset=UTF-8';
		$part['Content-Transfer-Encoding'] = '8bit';
		$part['data'] = $TextUtf8;
		$this->BodyParts[] = $part;
	}

	public function AddHtmlPart( $TextHtmlUtf8 ){
		$part = array();
		$part['Content-Type'] = 'text/html; charset=UTF-8';
		$part['Content-Transfer-Encoding'] = '8bit';
		$part['data'] = $TextHtmlUtf8;
		$this->BodyParts[] = $part;
	}

	public function AddAttachmentPart( $FileContents, $FileName ){
		$part = array();
		$part['Content-Type'] = 'application/octet-stream; name="'.$FileName.'"';
		$part['Content-Transfer-Encoding'] = 'base64';
		$part['Content-Description'] = $FileName;
		$part['Content-Disposition'] = 'attachment; filename="'.$FileName.'"';
		$part['data'] = base64_encode($FileContents);
		$this->BodyParts[] = $part;
	}

	/**
	 * Формирует Mime закодированную строку с разбивкой длинных строк
	 * @param <type> $String
	 * @return <type>
	 */
	protected function Base64WrapMb( $String ){
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
				$encoded .= $start.$base2.$end.'  ';
				$str = '';
			}else{
				$base2 = $base;
			}
			$str .= $s;
		}
		if($str != ''){
			$encoded .= $start.$base2.$end.'  ';
		}
		$encoded = substr($encoded, 0, -2);
		return $encoded;
	}

	/**
	 * Формирует mime заколированную строку для использования в заголовках
	 * @param <type> $String
	 * @return <type>
	 */
	protected function Base64( $String ){
		return '=?UTF-8?B?'.base64_encode($String).'?=';
	}

	protected function GetTo(){
		$to = '';
		foreach($this->ToAddress as $a){
			$to .= $this->Base64($a[1]).' <'.$a[0].'>, ';
		}
		return substr($to, 0, -2);
	}

	protected function GetSmtpRecipients(){
		$rcpts = array();
		foreach($this->ToAddress as $a){
			$rcpts[] = $a[0];
		}
		return $rcpts;
	}

	/**
	 * Генерирует строку заголовка
	 * @param <type> $Name
	 * @param <type> $Value
	 * @return <type>
	 */
	protected function HeaderLine( &$Headers, $Name, $Value ){
	    $Headers .= $Name.': '.$Value.$this->CRLF;
	}

	public function BuildMessage( &$Envelope, &$Headers, &$Body ){
		$this->HeaderLine($Envelope, 'To', $this->GetTo());
		$this->HeaderLine($Envelope, 'Subject', $this->Base64WrapMb($this->Subject));

		$this->HeaderLine($Headers, 'From', $this->Base64($this->FromName).' <'.$this->From.'>');

		$this->HeaderLine($Headers, 'Return-Path', $this->From);
		$this->HeaderLine($Headers, 'Date', date("D, d M Y H:i:s").' UT');
		if($this->ReplyTo != ''){
			$this->HeaderLine($Headers, 'Reply-to', $this->Base64($this->ReplyToName).' <'.$this->ReplyTo.'>');
		}
		$this->HeaderLine($Headers, 'X-Mailer', $this->XMailer);
		$this->HeaderLine($Headers, 'X-Priority', $this->XPriority);

		if(count($this->BodyParts) > 1){ // Письмо с несколькими частями
			$this->HeaderLine($Headers, 'MIME-Version', '1.0');
			$this->HeaderLine($Headers, 'Content-Type', 'multipart/mixed; boundary="'.$this->GetBoundary().'"');
			$Body = $this->CRLF.$this->CRLF.'This is a multi-part message in MIME format.'.$this->CRLF;
			foreach($this->BodyParts as $part){
				$Body .= $this->CRLF.$this->CRLF.'--'.$this->GetBoundary().$this->CRLF;
				$this->HeaderLine($Body, 'Content-Type', $part['Content-Type']);
				$this->HeaderLine($Body, 'Content-Transfer-Encoding', $part['Content-Transfer-Encoding']);
				if(isset($part['Content-Description'])){
					$this->HeaderLine($Body, 'Content-Description', $part['Content-Description']);
					$this->HeaderLine($Body, 'Content-Disposition', $part['Content-Disposition']);
				}
				$Body .= $this->CRLF.$part['data'];
			}
			$Body .= $this->CRLF.$this->CRLF.'--'.$this->GetBoundary().'--'.$this->CRLF;
		}else{
			$part = &$this->BodyParts[0];
			$this->HeaderLine($Headers, 'Content-Type', $part['Content-Type']);
			$this->HeaderLine($Headers, 'Content-Transfer-Encoding', $part['Content-Transfer-Encoding']);
			$Body = $this->CRLF.$this->CRLF.$part['data'];
		}
	}

	private function SmtpParseResponse( $Socket, $Response ){
		$serverText = fgets($Socket, 256);
		if(trim(substr($serverText, 0, 3)) == $Response){
			return true;
		}
		return false;
	}

	public function SmtpMail( $Recipients, $Mail ){
		$this->SmtpLog = '';

		// Соединяемся с сервером
		if(!$socket = fsockopen($this->SmtpHost, $this->SmtpPort, $errno, $errstr, $this->SmtpConnectionTimeout)){
			$this->SmtpLog .= 'fsockopen: '.$errstr.'<br />';
			fclose($socket);
			return false;
		}
		while($response = fgets($socket, 1024)){
			$this->SmtpLog .= 'fsockopen'.': '.$response.'<br />';
			if(substr($response,3,1) == " ") { break; }
		}

		// Отправляем приветствие
 		fputs($socket, 'EHLO '.getenv("HTTP_HOST").$this->CRLF);
		while($response = fgets($socket, 1024)){
			$this->SmtpLog .= 'EHLO'.': '.$response.'<br />';
			if(substr($response,3,1) == " ") { break; }
		}

		// Запрашиваем авторизацию
		fputs($socket, 'AUTH LOGIN'.$this->CRLF);
		while($response = fgets($socket, 1024)){
			$this->SmtpLog .= 'AUTH LOGIN'.': '.$response.'<br />';
			if(substr($response,3,1) == " ") { break; }
		}
		// Отправляем имя пользователя/пароль
		fputs($socket, base64_encode($this->SmtpLogin).$this->CRLF);
		while($response = fgets($socket, 1024)){
			$this->SmtpLog .= 'SEND LOGIN'.': '.$response.'<br />';
			if(substr($response,3,1) == " ") { break; }
		}
		fputs($socket, base64_encode($this->SmtpPassword).$this->CRLF);
		while($response = fgets($socket, 1024)){
			$this->SmtpLog .= 'SEND PASS'.': '.$response.'<br />';
			if(substr($response,3,1) == " ") { break; }
		}

		fputs($socket, 'MAIL FROM: '.$this->SmtpLogin.$this->CRLF);
		while($response = fgets($socket, 1024)){
			$this->SmtpLog .= 'MAIL FROM'.': '.$response.' ("'.$this->SmtpLogin.'")'.'<br />';
			if(substr($response,3,1) == " ") { break; }
		}

		foreach($Recipients as $to){
			fputs($socket, 'RCPT TO:'.$to.$this->CRLF);
			while($response = fgets($socket, 1024)){
				$this->SmtpLog .= 'RCPT TO'.': '.$response.' ("'.$to.'")'.'<br />';
				if(substr($response,3,1) == " ") { break; }
			}
		}

		fputs($socket, 'DATA'.$this->CRLF);
		while($response = fgets($socket, 1024)){
			$this->SmtpLog .= 'DATA'.': '.$response.'<br />';
			if(substr($response,3,1) == " ") { break; }
		}

		fputs($socket, $Mail.$this->CRLF);
		fputs($socket, '.'.$this->CRLF);
		while($response = fgets($socket, 1024)){
			$this->SmtpLog .= 'SEND MAIL'.': '.$response.'<br />';
			if(substr($response,3,1) == " ") { break; }
		}

		fputs($socket, 'QUIT'.$this->CRLF);
		fclose($socket);
		return true;
	}

	public function Send(){
		$this->BuildMessage($envelope, $headers, $body);
		if(!$this->SmtpSend){
			return mail(
				$this->GetTo(),
				$this->Base64WrapMb($this->Subject),
				'',
				$headers.$body
			);
		}else{
			return $this->SmtpMail($this->GetSmtpRecipients(), $envelope.$headers.$body);
		}

		return false;
	}

	/**
	 * Возвращает проинициализированный объект
	 * @return LmEmailExtended
	 */
	static public function Instance(){
		if(!(self::$_instance instanceof LmEmailExtended)){
			self::$_instance = new LmEmailExtended(new EmailOptions());
		}
		self::$_instance->ToAddress = array();
		self::$_instance->BodyParts = array();
		self::$_instance->Boundary = '';
		self::$_instance->From = '';
		self::$_instance->FromName = '';
		self::$_instance->ReplyTo = '';
		self::$_instance->ReplyToName = '';
		self::$_instance->Subject = '';
		return self::$_instance;
	}
}

?>