<?php

/**
 * Вывод ошибок, предупреждений и меток в браузер или консоль FireBug
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
	 * Массив в который записываются все ошибки
	 *
	 * @var array
	 */
	static public $Messages = array();

	static public $errortypes = array(
		1 => 'Ошибка',
		2 => 'Предупреждение!',
		4 => 'Ошибка разборщика',
		8 => 'Замечание',
		16 => 'Ошибка ядра',
		32 => 'Предупреждение ядра!',
		64 => 'Ошибка компиляции',
		128 => 'Предупреждение компиляции!',
		256 => 'Пользовательская Ошибка',
		512 => 'Пользовательскаое Предупреждение!',
		1024 => 'Пользовательскаое Замечание',
		2048 => 'Небольшое замечание',
		8192 => 'Устаревший код'
	);

	/**
	 * Обработчик ошибок PHP
	 *
	 * @param int    $ErrNo
	 * @param string $ErrStr
	 * @param string $ErrFile
	 * @param int    $ErrLine
	 */
	static public function ErrorHandler( $ErrNo, $ErrStr, $ErrFile, $ErrLine ){

		$str = LsI18n::Translate("%type: <i>%error</i>; Файл: <b>%file</b>; Линия: <b>%line</b>;",
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
	 * Добавляет сообщение отладчика
	 *
	 * @param string $String
	 * @param int    $Level
	 * @param string $Label
	 */
	static public function Write( $Msg, $Level = LmDebug::LEVEL_ERROR, $Label='' ){
		LmDebug::$Messages[] = array($Label, $Msg, $Level);
	}

	/**
	 * Добавляет отладочное сообщение
	 *
	 * @param string $String
	 * @param string $Label
	 */
	static public function Debug( $Msg, $Label='' ){
		LmDebug::Write($Msg, LmDebug::LEVEL_DEBUG, $Label);
	}

	/**
	 * Добавляет небольшое замечание
	 *
	 * @param string $String
	 * @param string $Label
	 */
	static public function Strict( $Msg, $Label='' ){
		LmDebug::Write($Msg, LmDebug::LEVEL_STRICT, $Label);
	}

	/**
	 * Добавляет замечание
	 *
	 * @param string $String
	 * @param string $Label
	 */
	static public function Notice( $Msg, $Label='' ){
		LmDebug::Write($Msg, LmDebug::LEVEL_NOTICE, $Label);
	}

	/**
	 * Добавляет предупреждение
	 *
	 * @param string $String
	 * @param string $Label
	 */
	static public function Warning( $Msg, $Label='' ){
		LmDebug::Write($Msg, LmDebug::LEVEL_WARNING, $Label);
	}

	/**
	 * Добавляет ошибку
	 *
	 * @param string $String
	 * @param string $Label
	 */
	static public function Error( $Msg, $Label='' ){
		LmDebug::Write($Msg, LmDebug::LEVEL_ERROR, $Label);
	}

	/**
	 * Добавляет сообщение об ошибке ядра
	 *
	 * @param string $Msg
	 * @param string $Label
	 */
	static public function CoreError( $Msg, $Label = '' ){
		LmDebug::Write($Msg, LmDebug::LEVEL_CORE_ERROR, $Label);
	}
}
