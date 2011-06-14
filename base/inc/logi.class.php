<?php

//Класс для ведения логов

class Logi{

	public $filename = '';

	/**
	 * Конструктор
	 * @param  $filename Имя файла лога
	 */
	public function __construct( $filename ){
		$this->filename = $filename;
		if(!is_file($filename)){
			$this->CreateLogFile();
		}
	}

	/**
	 * Создать лог файл
	 * @return bool
	 */
	public function CreateLogFile(){
		$tf = fopen($this->filename, "w");
		if(!$tf) return false;
		@fclose($tf);
		return true;
	}

	/**
	 * Очистить лог файл
	 * @return void
	 */
	public function Clear(){
		$this->CreateLogFile();
	}

	/**
	 * Записать строку в лог файл
	 * @param  $log
	 * @param bool $exit
	 * @return void
	 */
	public function Write( $log, $exit = false ){
		$fp = fopen($this->filename, "a+");
		flock($fp, LOCK_EX);
		fwrite($fp, $log."\n");
		flock($fp, LOCK_UN);
		fclose($fp);
		if($exit) die();
	}

	/**
	 * Вывести дамп переменной в лог файл
	 * @param  $Var
	 * @param bool $exit
	 * @return void
	 */
	public function Dump( $Var, $exit = false ){
		$this->Write(var_export($Var, true), $exit);
	}

}
?>