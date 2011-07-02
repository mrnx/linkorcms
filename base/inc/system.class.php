<?php

# LinkorCMS 1.4
# © 2006-2011 Александр Галицкий (linkorcms@yandex.ru)
# Файл: system.php
# Назначение: Ядро системы

abstract class System{

	static public $Errors = array();

	/**
	 * Объект для работы с базой данных
	 * @return LcDatabaseFilesDB
	 */
	static public function database(){
		return $GLOBALS['db'];
	}

	/**
	 * Объект для работы с данными пользователя и сессией
	 * @return User
	 */
	static public function user(){
		return $GLOBALS['user'];
	}

	/**
	 * Объект управления кэшем
	 * @return LmFileCache
	 */
	static public function cache(){
		return LmFileCache::Instance();
	}

	/**
	 * Объект управления страницей на сайте
	 * @return Page
	 */
	static public function site(){
		if($GLOBALS['site'] == null){
			$GLOBALS['site'] = new Page();
		}
		return $GLOBALS['site'];
	}

	/**
	 * Объект управления страницей в админ-панели
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
	 * Прочитать значение настройки или установить новое значение
	 * @static
	 * @param  $Path Группа и название настройки разделенные прямым слэшем
	 * @param null $SetValue Установить новое значение настройки (запись в БД)
	 * @return mixed При установке нового значения настройки, возвращает старое значение
	 */
	static public function config( $Path, $SetValue = null ){
		return self::configs('config', $Path, $SetValue);
	}

	/**
	 * Прочитать значение настройки плагина
	 * @static
	 * @param  $Path
	 * @return bool
	 */
	static public function plug_config( $Path ){
		return self::configs('plug_config', $Path);
	}

	/**
	 * Возвращает объект лога ошибок
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
	 * Возвращает объект лога сайта
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

