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
	global $SITE_ERRORS;
	$errortype = array(
		1 => '������', 2 => '��������������!', 4 => '������ ����������', 8 => '���������', 16 => '������ ����', 32 => '�������������� ����!', 64 => '������ ����������',
		128 => '�������������� ����������!', 256 => '���������������� ������', 512 => '����������������� ��������������!', 1024 => '����������������� ���������', 2048 => '��������� ���������',
		4096=> '������������ ������', 8192 => '���������� ���', 16384 => '���������� ��� (����������������)'
	);
	$ErrorHtml = '<b>'.$errortype[$No].'</b>: '.$Error.' � <b>'.$File.($Line > -1 ? '</b> �� ����� <b>'.$Line.'</b>' : '').'.';
	if($SITE_ERRORS){
		System::$Errors[] = $ErrorHtml;
	}
}
