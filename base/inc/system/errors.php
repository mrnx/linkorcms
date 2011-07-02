<?php

// ������
define('ERROR_HANDLER', true);
define('ERROR', 1);
define('WARNING', 2);
define('PARSE', 4);
define('NOTICE', 8);
define('CORE_ERROR', 16);
define('CORE_WARNING', 32);
define('COMPILE_ERROR', 64);
define('COMPILE_WARNING', 128);
define('USER_ERROR', 256);
define('USER_WARNING', 512);
define('USER_NOTICE', 1024);

/**
 * �������� ����� ������
 * @return void
 */
function ErrorsOn(){
	global $SITE_ERRORS;
	$SITE_ERRORS = true;
}

/**
 * �������� ��������� ����� ������
 * @return void
 */
function ErrorsOff(){
	global $SITE_ERRORS;
	$SITE_ERRORS = false;
}

/**
 * ���������� ������
 * @param  $No
 * @param  $Error
 * @param  $File
 * @param  $Line
 * @return void
 */
function ErrorHandler( $No, $Error, $File, $Line = -1 ){
	static $LogedErrors = array();
	static $First = true;
	global $SITE_ERRORS;
	$errortype = array(
		1 => '������', 2 => '��������������!', 4 => '������ ����������', 8 => '���������', 16 => '������ ����', 32 => '�������������� ����!', 64 => '������ ����������',
		128 => '�������������� ����������!', 256 => '���������������� ������', 512 => '����������������� ��������������!', 1024 => '����������������� ���������', 2048 => '��������� ���������',
		8192 => '���������� ���'
	);
	$ErrorHtml = '<br /><b>'.$errortype[$No].'</b>: '.$Error.' � <b>'.$File.($Line > -1 ? '</b> �� ����� <b>'.$Line.'</b>' : '').'.<br />'."\n";
	if(!defined('SETUP_SCRIPT') && System::config('debug/log_errors')){
		$ErrorText = '"'.$errortype[$No].'" "'.$Error.'" "'.$File.'"'.($Line > -1 ? ' "'.$Line.'"' : '');
		if(!in_array($ErrorText, $LogedErrors)){ // ��������� ���������� ������
			$LogedErrors[] = $ErrorText;
			if($First){
				$First = false;
				$ErrorText = '---- '.date("d.m.y G:i", time())."\n".$ErrorText;
			}
			System::log_errors($ErrorText);
		}
	}
	if(PRINT_ERRORS){
		print $ErrorHtml;
	}
	if(PRINT_ERRORS || ($SITE_ERRORS && System::config('debug/php_errors'))){
		System::$Errors[] = $ErrorHtml."\n";
	}
}
