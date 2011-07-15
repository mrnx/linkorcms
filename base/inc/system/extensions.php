<?php

/**
 * Функции для работы с расширениями.
 */

// Константы для типов расширений
define('EXT_MODULE', '1');
define('EXT_PLUGIN', '2');
define('EXT_BLOCK', '3');
define('EXT_TEMPLATE', '4');

/**
 * Распаковка архива расширения или обновления в корневую директорию сайта
 * @param $ArchiveFileName
 * @return void
 */
function ExtExtract( $ArchiveFileName ){
	$zip = new ZipArchive();
	if($zip->open($ArchiveFileName) === true){
		$zip->extractTo(GetSiteRoot());
		$zip->close();
		return true;
	}else{
		return false;
	}
}

/**
 * Загружает информацию о расширении и возвращает массив
 * @param $ExtPath Путь к папке с информационным файлом
 * @return array
 */
function ExtLoadInfo( $ExtPath ){
	$result = false;
	$infoFile = RealPath2($ExtPath.'/info.php');
	if(is_file($infoFile)){ // Загружаем инфо из PHP файла
		$result = include $infoFile;
		if(!is_array($result)){
			if(isset($module)){ // Старая версия модуля
				foreach($module as $module){}
				return array(
					'name' => $module['name'],
					'description' => $module['comment'],
					'author' => $module['copyright'],
					'site' => '',
					'version' => '',
					'icon' => '',
					'1.3' => true
				);
			}elseif(isset($plugins)){ // Старая версия плагина
				foreach($plugins as $plugins){}
				return array(
					'name' => $plugins['name-ru'],
					'description' => $plugins['description-ru'],
					'author' => $plugins['author'],
					'site' => $plugins['site'],
					'version' => $plugins['version'],
					'icon' => '',
					'1.3' => true
				);
			}elseif(isset($groups)){
				return false;
			}
		}
		return $result;
	}
	$infoXML = RealPath2($ExtPath.'/info.xml');
	if(is_file($infoXML)){ // Загружаем инфо из XML файла(это для дизайнеров)
		$info = simplexml_load_file($infoXML);
		$result = get_object_vars($info);
		foreach($result as $f=>&$v) {
			$result[$f] = Utf8ToCp1251($v);
		}
		return $result;
	}else{
		return false;
	}
}

/**
 * Регистрация модуля в БД.
 * Рекомендуется использовать эту функцию вместо прямого внесения изменений в базу дыннх.
 * @param string $Name Имя модуля
 * @param string $Folder Имя папки модуля в директории модулей
 * @param string $IsIndex Модуль работает на index.php (1|0)
 * @param string $View Уровень видимости (1|2|3|4)
 * @param string $Enabled Включен (1|0)
 * @return void
 */
function ExtInstallModule( $Name, $Folder, $IsIndex, $View, $Enabled = '1' ){
	$Name = SafeEnv($Name, 255, str);
	$Folder = SafeEnv($Folder, 255, str);
	$IsIndex = SafeEnv($IsIndex, 1, int);
	$View = SafeEnv($View, 1, int);
	$Enabled = SafeEnv($Enabled, 1, int);
	System::database()->Insert('modules',"'','$Name','$Folder','0','$IsIndex','','','$View','$Enabled','0','1',''");
}

/**
 * Удаляет регистрацию модуля из базы данных.
 * @param string $Folder Имя папки модуля в директории модулей
 * @return void
 */
function ExtDeleteModule( $Folder ){
	$Folder = SafeEnv($Folder, 255, str);
	System::database()->Delete('modules', "`folder`='$Folder'");
}

/**
 * Регистрация плагина в БД.
 * Рекомендуется использовать эту функцию вместо прямого внесения изменений в базу дыннх.
 * @param string $Group Имя группы, может быть пустым если плагин не входит в группы
 * @param string $Name Имя папки плагина в директории плагинов или директории группы плагинов
 * @param string $Function Функция плагина. Плагины в группках могут быть разбиты по функциям
 * @param string $Type Тип плагина
 * @param string $Enabled Включён
 * @return void
 */
function ExtInstallPlugin( $Group, $Name, $Function, $Type, $Enabled = '1' ){
	$Group = SafeEnv($Group, 250, str);
	$Name = SafeEnv($Name, 255, str);
	$Function = SafeEnv($Function, 255, str);
	$Type = SafeEnv($Type, 1, int);
	$Enabled = SafeEnv($Enabled, 1, int);
	System::database()->Insert('plugins',"'','$Name','$Function','','$Type','$Group','0','$Enabled'");
	PluginsClearCache();
}

/**
 * Удаляет регистрацию плагина из базы данных.
 * @param string $Group Имя группы плагина (если есть)
 * @param string $Name Имя плагина
 * @return void
 */
function ExtDeletePlugin( $Group, $Name){
	$Group = SafeEnv($Group, 250, str);
	$Name = SafeEnv($Name, 255, str);
	System::database()->Delete('plugins', "`name`='$Name' and `group`='$Group'");
	PluginsClearCache();
}

/**
 * Регистрация блока в БД.
 * Рекомендуется использовать эту функцию вместо прямого внесения изменений в базу дыннх.
 * @param string $Name
 * @param string $Folder
 * @return void
 */
function ExtInstallBlock( $Name, $Folder ){
	$Name = SafeEnv($Name, 255, str);
	$Folder = SafeEnv($Folder, 255, str);
	System::database()->Insert('block_types',"'','$Name','','$Folder'");
}

/**
 * Удаляет регистрацию блока из базы данных.
 * @param string $Folder Имя папки блока в директории блоков
 * @return void
 */
function ExtDeleteBlock( $Folder ){
	$Folder = SafeEnv($Folder, 255, str);
	System::database()->Delete('block_types', "`folder`='$Folder'");
}

/**
 * Регистрация шаблона в БД.
 * Рекомендуется использовать эту функцию вместо прямого внесения изменений в базу дыннх.
 * @param $Folder Имя папка блока в директории блоков
 * @param string $Admin Шаблон для админ-панели
 * @return void
 */
function ExtInstallTemplate( $Folder, $Admin = '0' ){
	$Folder = SafeEnv($Folder, 255, str);
	$Admin = SafeEnv($Admin, 1, int);
	System::database()->Insert('templates',"'','$Folder','$Admin'");
}

/**
 * Удаляет регистрацию шаблона из базы данных.
 * @param string $Folder Имя папки шаблона в директории шаблонов
 * @return void
 */
function ExtDeleteTemplate( $Folder ){
	$Folder = SafeEnv($Folder, 255, str);
	System::database()->Delete('templates', "`folder`='$Folder'");
}
