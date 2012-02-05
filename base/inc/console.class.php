<?php

/**
 * ����� ������, �������������� � ����� � ������� ��� ������� FireBug
 */

//error_reporting(E_ALL);
//set_error_handler('LmDebug::ErrorHandler');

class LmDebug{

	const LEVEL_DEBUG = 1;
	const LEVEL_STRICT = 2;
	const LEVEL_NOTICE = 3;
	const LEVEL_WARNING = 4;
	const LEVEL_ERROR = 5;
	const LEVEL_CORE_ERROR = 6;

	/**
	 * ������ � ������� ������������ ��� ������
	 *
	 * @var array
	 */
	static public $Messages = array();

	static public $errortypes = array(
		1 => '������',
		2 => '��������������!',
		4 => '������ ����������',
		8 => '���������',
		16 => '������ ����',
		32 => '�������������� ����!',
		64 => '������ ����������',
		128 => '�������������� ����������!',
		256 => '���������������� ������',
		512 => '����������������� ��������������!',
		1024 => '����������������� ���������',
		2048 => '��������� ���������',
		8192 => '���������� ���'
	);

	/**
	 * ���������� ������ PHP
	 *
	 * @param int    $ErrNo
	 * @param string $ErrStr
	 * @param string $ErrFile
	 * @param int    $ErrLine
	 */
	static public function ErrorHandler( $ErrNo, $ErrStr, $ErrFile, $ErrLine ){

		$str = LsI18n::Translate("%type: <i>%error</i>; ����: <b>%file</b>; �����: <b>%line</b>;",
			array('type'=>LsI18n::Translate(LmDebug::$errortypes[$ErrNo]), 'error'=>$ErrStr,'file'=>$ErrFile,'line'=>$ErrLine));

		switch($ErrNo){
			case E_CORE_ERROR:
			    LmDebug::CoreError($str, 'PHP');
			case E_ERROR:
			case E_PARSE:
			case E_COMPILE_ERROR:
			case E_USER_ERROR:{
				LmDebug::Error($str, 'PHP');
			} break;
			case E_WARNING:
			case E_CORE_WARNING:
			case E_COMPILE_WARNING:
			case E_USER_WARNING:{
				LmDebug::Warning($str, 'PHP');
			} break;
			case E_NOTICE:
			case E_USER_NOTICE:{
				LmDebug::Notice($str, 'PHP');
			} break;
			case E_STRICT:{
				LmDebug::Strict($str, 'PHP');
			} break;
		}

	}

	static public function NumErrors(){
		return count(LmDebug::Messages);
	}

	/**
	 * ��������� ��������� ���������
	 *
	 * @param string $String
	 * @param int    $Level
	 * @param string $Label
	 */
	static public function Write( $Msg, $Level = LmDebug::LEVEL_ERROR, $Label='' ){
		LmDebug::$Messages[] = array($Label, $Msg, $Level);
	}

	/**
	 * ��������� ���������� ���������
	 *
	 * @param string $String
	 * @param string $Label
	 */
	static public function Debug( $Msg, $Label='' ){
		LmDebug::Write($Msg, LmDebug::LEVEL_DEBUG, $Label);
	}

	/**
	 * ��������� ��������� ���������
	 *
	 * @param string $String
	 * @param string $Label
	 */
	static public function Strict( $Msg, $Label='' ){
		LmDebug::Write($Msg, LmDebug::LEVEL_STRICT, $Label);
	}

	/**
	 * ��������� ���������
	 *
	 * @param string $String
	 * @param string $Label
	 */
	static public function Notice( $Msg, $Label='' ){
		LmDebug::Write($Msg, LmDebug::LEVEL_NOTICE, $Label);
	}

	/**
	 * ��������� ��������������
	 *
	 * @param string $String
	 * @param string $Label
	 */
	static public function Warning( $Msg, $Label='' ){
		LmDebug::Write($Msg, LmDebug::LEVEL_WARNING, $Label);
	}

	/**
	 * ��������� ������
	 *
	 * @param string $String
	 * @param string $Label
	 */
	static public function Error( $Msg, $Label='' ){
		LmDebug::Write($Msg, LmDebug::LEVEL_ERROR, $Label);
	}

	/**
	 * ��������� ��������� �� ������ ����
	 *
	 * @param string $Msg
	 * @param string $Label
	 */
	static public function CoreError( $Msg, $Label = '' ){
		LmDebug::Write($Msg, LmDebug::LEVEL_CORE_ERROR, $Label);
	}
}
