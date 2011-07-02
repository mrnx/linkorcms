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
		$Path = explode('/', $Path);
		if(isset($Path[1])){
			if(isset($SetValue)){
				$old_value = $GLOBALS[$globalVarName][$Path[0]][$Path[1]];
				$GLOBALS[$globalVarName][$Path[0]][$Path[1]] = $SetValue;
				ConfigSetValue($Path[0], $Path[1], $SetValue);
				return $old_value;
			}else{
				if(isset($GLOBALS[$globalVarName][$Path[0]][$Path[1]])){
					return $GLOBALS[$globalVarName][$Path[0]][$Path[1]];
				}else{
					return false;
				}
			}
		}else{
			if(isset($GLOBALS[$globalVarName][$Path[0]])){
				return $GLOBALS[$globalVarName][$Path[0]];
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

