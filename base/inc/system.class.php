<?php

# LinkorCMS 1.4
# � 2006-2011 ��������� �������� (linkorcms@yandex.ru)
# ����: system.php
# ����������: ���� �������

abstract class System{

	static public $Errors = array();

	/**
	 * ������ ��� ������ � ����� ������
	 * @return LcDatabaseFilesDB
	 */
	static public function database(){
		return $GLOBALS['db'];
	}

	/**
	 * ������ ��� ������ � ������� ������������ � �������
	 * @return User
	 */
	static public function user(){
		return $GLOBALS['user'];
	}

	/**
	 * ������ ���������� �����
	 * @return LmFileCache
	 */
	static public function cache(){
		return LmFileCache::Instance();
	}

	/**
	 * ������ ���������� ��������� �� �����
	 * @return Page
	 */
	static public function site(){
		if($GLOBALS['site'] == null){
			$GLOBALS['site'] = new Page();
		}
		return $GLOBALS['site'];
	}

	/**
	 * ������ ���������� ��������� � �����-������
	 * @return AdminPage
	 */
	static public function admin(){
		if($GLOBALS['site'] == null){
			$GLOBALS['site'] = new AdminPage();
		}
		return $GLOBALS['site'];
	}

	static private function configs( $globalVarName, $Path, $SetValue = null ){
		static $Cache = array();
		if(!isset($SetValue) && isset($Cache[$Path])){
			return $Cache[$Path];
		}
		$Paths = explode('/', $Path);
		if(isset($Paths[1])){
			if(isset($SetValue)){
				$old_value = $GLOBALS[$globalVarName][$Paths[0]][$Paths[1]];
				$GLOBALS[$globalVarName][$Paths[0]][$Paths[1]] = $SetValue;
				ConfigSetValue($Paths[0], $Paths[1], $SetValue);
				if(isset($Cache[$Path])) $Cache[$Path] = $SetValue;
				return $old_value;
			}else{
				if(isset($GLOBALS[$globalVarName][$Paths[0]][$Paths[1]])){
					$Cache[$Path] = $GLOBALS[$globalVarName][$Paths[0]][$Paths[1]];
					return $GLOBALS[$globalVarName][$Paths[0]][$Paths[1]];
				}else{
					return false;
				}
			}
		}else{
			if(isset($GLOBALS[$globalVarName][$Path])){
				$Cache[$Path] = $GLOBALS[$globalVarName][$Path];
				return $GLOBALS[$globalVarName][$Path];
			}else{
				return false;
			}
		}
	}

	/**
	 * ��������� �������� ��������� ��� ���������� ����� ��������
	 * @static
	 * @param  $Path ������ � �������� ��������� ����������� ������ ������
	 * @param null $SetValue ���������� ����� �������� ��������� (������ � ��)
	 * @return mixed ��� ��������� ������ �������� ���������, ���������� ������ ��������
	 */
	static public function config( $Path, $SetValue = null ){
		return self::configs('config', $Path, $SetValue);
	}

	/**
	 * ��������� �������� ��������� �������
	 * @static
	 * @param  $Path
	 * @return bool
	 */
	static public function plug_config( $Path ){
		return self::configs('plug_config', $Path);
	}

	/**
	 * ���������� ������ ���� ������
	 * @static
	 * @param string $Message
	 * @param bool $exit
	 * @return Logi
	 */
	static public function log_errors( $Message = '', $exit = false ){
		if($Message != ''){
			$GLOBALS['ErrorsLog']->Write($Message, $exit);
		}
		return $GLOBALS['ErrorsLog'];
	}

	/**
	 * ���������� ������ ���� �����
	 * @static
	 * @param string $Message
	 * @param bool $exit
	 * @return Logi
	 */
	static public function log( $Message = '', $exit = false ){
		if($Message != ''){
			$GLOBALS['SiteLog']->Write($Message, $exit);
		}
		return $GLOBALS['SiteLog'];
	}

	static public function error( $No, $Error, $File, $Line = -1 ){
		ErrorHandler($No, $Error, $File, $Line);
	}

}

