<?php

//Класс для ведения логов
class Logi
{
	public $filename = '';
	public $cryptLogs = true;

	public function Logi( $filename )
	{
		$this->filename = $filename;
		if(!is_file($filename)){
			$this->CreateLogFile();
		}
	}

	public function CreateLogFile()
	{
		$tf = fopen($this->filename, "w");
		if(!$tf){
			return false;
		}else{
			fwrite($fp, '<? exit; ?>'."\n");
			@fclose($tf);
		}
		@chmod($this->filename, 0777);
	}

	public function Write( $log, $exit = false )
	{
		$fp = fopen($this->filename, "a+");
		flock($fp, LOCK_EX);
		fwrite($fp, $log."\n");
		flock($fp, LOCK_UN);
		fclose($fp);
		if($exit){
			die();
		}
	}

	public function Clear()
	{
		$this->CreateLogFile();
	}
}
?>