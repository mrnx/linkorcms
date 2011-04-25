<?php

// Модуль кэширования в файлах LinkorCMS 2.0.

class CacheOptions{

	public $engine = 'LmFileCache';
	public $path = '';
	public $cache_suffix = '.cache';
	public $expiry_suffix = '.expiry';
	public $servers = array();
	public $enabled = false;

	function  __construct() {
		global $config;
		$this->path = $config['cache_dir'];
		$this->enabled = (USE_CACHE && is_dir($this->path) && is_writable($this->path) && !defined('SETUP_SCRIPT'));
	}

}

/**
 * Класс кэширования.
 */
class LmFileCache{

	static protected $_instance;
	public $Path; // Имя папки кэша с последним слэшем
	public $FileSuffix = '.cache'; // Расширение файлов кэша
	public $ExpirySuffix = '.expiry'; // Расширение информационного файла
	public $Enabled = false; // Включить кэширование

	function  __construct( CacheOptions $options ){
		$this->Initialize($options);
	}

	/**
	 * Функция инициализации класса.
	 * @param CacheOptions $options
	 */
	public function Initialize( CacheOptions $options ){
		$this->Path = $options->path;
		$this->FileSuffix = $options->cache_suffix;
		$this->ExpirySuffix = $options->expiry_suffix;
		$this->Enabled = $options->enabled;
	}

	/**
	 * Генерирует полное имя файла кэша и контрольного файла, по имени группы и ключу.
	 * @param <type> $Group
	 * @param <type> $Key
	 * @return <type>
	 */
	public function GetFiles( $Group, $Key ){
		$cacheGroupPath = $this->Path.$Group;
		if(!is_dir($cacheGroupPath)){
			mkdir($cacheGroupPath, 0777, true);
		}
		$cacheGroupPath .= '/';
		$Key = rawurlencode($Key);
		return array(
			$cacheGroupPath.$Key.$this->FileSuffix,
			$cacheGroupPath.$Key.$this->ExpirySuffix
		);
	}

	/**
	 * Записывает строку в файл кэша.
	 *
	 * @param string $Group  Имя группы/папки в которой хранятся файлы кэша
	 * @param string $Key    Имя кэша
	 * @param string $Value  Переменная
	 * @param int $Expiry    Время жизни кэша в секундах
	 * @return bool
	 */
	public function Write( $Group, $Key, &$Value, $Expiry = 0 ){
		// Кэширование включено только если папка кэша и папка группы существуют и доступны для записи.
		if($this->Enabled){
			$files = $this->GetFiles($Group, $Key);
			$umask = umask();
			umask(0000);
			$existed = is_file($files[0]);
			if($Expiry != 0){
				$Expiry = time() + $Expiry;
			}
			file_put_contents($files[0], Serialize($Value), LOCK_SH);
			file_put_contents($files[1], $Expiry, LOCK_SH);
			if(!$existed){
				@chmod($files[0], 0666);
				@chmod($files[1], 0666);
			}
			umask($umask);
		}
	}

	/**
	 * Удаляет ключ кэша
	 * @param <type> $Group
	 * @param <type> $Key
	 */
	public function Delete( $Group, $Key ){
		if($this->Enabled){
			$files = $this->GetFiles($Group, $Key);
			if(is_file($files[0])){
				unlink($files[0]);
				unlink($files[1]);
			}
		}
	}

	/**
	 * Проверяет доступен ли кэш для данного ключа.
	 * @param <type> $Group
	 * @param <type> $Key
	 * @return <type>
	 */
	public function HasCache( $Group, $Key ){
		if(!$this->Enabled)
			return false;
		$files = $this->GetFiles($Group, $Key);
		if(!is_dir($this->Path.$Group) || !is_writable($this->Path.$Group) || !is_file($files[0])){ // Если директория группы не дступна для записи
			return false;
		}
		$expiry = file_get_contents($files[1]);
		if($expiry != 0 && (time()>$expiry)){
			$this->Delete($Group, $Key);
			return false;
		}else{
			return true;
		}
	}

	/**
	 * Возвращает ранее кэшированную строку.
	 *
	 * @param string $CacheGroup  Имя группы/папки в которой хранятся файлы кэша
	 * @param string $CacheName   Имя кэша.
	 * @return string
	 */
	public function Get( $Group, $Key ){
		if(!$this->Enabled)
			return false;
		if($this->HasCache($Group, $Key)){
			$files = $this->GetFiles($Group, $Key);
			return Unserialize(file_get_contents($files[0]));
		}else{
			return false;
		}
	}

	/**
	 * Очищает группу кэша
	 * @param <type> $Group
	 */
	public function Clear( $Group ){
		$cacheGroupPath = $this->Path.$Group.'/';
		if(is_dir($cacheGroupPath)){
			$files = GetFiles($cacheGroupPath, false, true, "{$this->FileSuffix},{$this->ExpirySuffix}");
			foreach($files as $file){
				@unlink($cacheGroupPath.$file);
			}
		}
	}

	/**
	 * Возвращает список всех кэш групп.
	 */
	public function GetGroups(){
		return GetFolders($this->Path);
	}

	/**
	 * Возвращае список всех доступных ключей в группе
	 * @param <type> $Group
	 * @return <type>
	 */
	public function GetKeys( $Group ){
		$files = GetFiles($this->Path.$Group.'/');
		foreach($files as $key=>$file){
			$files[$key] = GetFileName($file);
		}
		return $files;
	}

	/**
	 * Возвращает проинициализированный объект кэша
	 * @return LmFileCache
	 */
	static public function Instance(){
		if(!(self::$_instance instanceof LmFileCache)){
			self::$_instance = new LmFileCache( new CacheOptions() );
		}
		return self::$_instance;
	}

}

?>