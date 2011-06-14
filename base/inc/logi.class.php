<?php

//����� ��� ������� �����

class Logi{

	public $filename = '';

	/**
	 * �����������
	 * @param  $filename ��� ����� ����
	 */
	public function __construct( $filename ){
		$this->filename = $filename;
		if(!is_file($filename)){
			$this->CreateLogFile();
		}
	}

	/**
	 * ������� ��� ����
	 * @return bool
	 */
	public function CreateLogFile(){
		$tf = fopen($this->filename, "w");
		if(!$tf) return false;
		@fclose($tf);
		return true;
	}

	/**
	 * �������� ��� ����
	 * @return void
	 */
	public function Clear(){
		$this->CreateLogFile();
	}

	/**
	 * �������� ������ � ��� ����
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
	 * ������� ���� ���������� � ��� ����
	 * @param  $Var
	 * @param bool $exit
	 * @return void
	 */
	public function Dump( $Var, $exit = false ){
		$this->Write(var_export($Var, true), $exit);
	}

}
?>