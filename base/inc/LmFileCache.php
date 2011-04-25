<?php

// ������ ����������� � ������ LinkorCMS 2.0.

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
 * ����� �����������.
 */
class LmFileCache{

	static protected $_instance;
	public $Path; // ��� ����� ���� � ��������� ������
	public $FileSuffix = '.cache'; // ���������� ������ ����
	public $ExpirySuffix = '.expiry'; // ���������� ��������������� �����
	public $Enabled = false; // �������� �����������

	function  __construct( CacheOptions $options ){
		$this->Initialize($options);
	}

	/**
	 * ������� ������������� ������.
	 * @param CacheOptions $options
	 */
	public function Initialize( CacheOptions $options ){
		$this->Path = $options->path;
		$this->FileSuffix = $options->cache_suffix;
		$this->ExpirySuffix = $options->expiry_suffix;
		$this->Enabled = $options->enabled;
	}

	/**
	 * ���������� ������ ��� ����� ���� � ������������ �����, �� ����� ������ � �����.
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
	 * ���������� ������ � ���� ����.
	 *
	 * @param string $Group  ��� ������/����� � ������� �������� ����� ����
	 * @param string $Key    ��� ����
	 * @param string $Value  ����������
	 * @param int $Expiry    ����� ����� ���� � ��������
	 * @return bool
	 */
	public function Write( $Group, $Key, &$Value, $Expiry = 0 ){
		// ����������� �������� ������ ���� ����� ���� � ����� ������ ���������� � �������� ��� ������.
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
	 * ������� ���� ����
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
	 * ��������� �������� �� ��� ��� ������� �����.
	 * @param <type> $Group
	 * @param <type> $Key
	 * @return <type>
	 */
	public function HasCache( $Group, $Key ){
		if(!$this->Enabled)
			return false;
		$files = $this->GetFiles($Group, $Key);
		if(!is_dir($this->Path.$Group) || !is_writable($this->Path.$Group) || !is_file($files[0])){ // ���� ���������� ������ �� ������� ��� ������
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
	 * ���������� ����� ������������ ������.
	 *
	 * @param string $CacheGroup  ��� ������/����� � ������� �������� ����� ����
	 * @param string $CacheName   ��� ����.
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
	 * ������� ������ ����
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
	 * ���������� ������ ���� ��� �����.
	 */
	public function GetGroups(){
		return GetFolders($this->Path);
	}

	/**
	 * ��������� ������ ���� ��������� ������ � ������
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
	 * ���������� ��������������������� ������ ����
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