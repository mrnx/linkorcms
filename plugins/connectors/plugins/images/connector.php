<?php

require 'sessioncheck.php';
require 'config.php';

class TinyImageManager{

	public $firstAct = false; // Активна первая папка
	public $folderAct = false; // Активна какая-то из вложенных папок
	public $ALLOWED_IMAGES;
	public $ALLOWED_FILES;

	/**
	 * Конструктор
	 * @return TinyImageManager
	 */
	public function __construct(){
		if(GZIP) ob_start("ob_gzhandler");

		$this->ALLOWED_IMAGES = explode(',', ALLOWED_IMAGES);
		$this->ALLOWED_FILES = explode(',', ALLOWED_FILES);

		// Обработка Ajax запросов
		switch($_POST['action']){

			// Создать папку
			case 'newfolder':
				$result = array();
				$dir = DIR_FILES.'/'.$_POST['path'];
				$name = Translit4Url(Utf8ToCp1251($_POST['name']));
				$path = RealPath2($dir.'/'.$name);
				$path2 = RealPath2($_POST['path'].'/'.$name);
				if($dir){
					if(preg_match('/[a-z0-9-_]+/sim', $name)){
						if(is_dir($path)){
							$result['error'] = 'Такая папка уже существует';
						}else{
							if(mkdir($path)){
								if(!is_dir($path.'/.thumbs')){
									mkdir($path.'/.thumbs');
								}
								$result['tree'] = $this->DirStructure('first', $path2);
								$result['addr'] = $this->DirPath($path2);
								$result['error'] = '';
							}else{
								$result['error'] = 'Ошибка создания папки';
							}
						}
					}else{
						$result['error'] = 'Название папки может содержать только латинские буквы, цифры, тире и знак подчеркивания';
					}
				}else{
					$result['error'] = 'Отказ в доступе';
				}
				echo JsonEncode($result);
				exit();
			break;

			// Показать путь (хлебные крошки вверху)
			case 'showpath':
				if(isset($_POST['default'])){
					$path = DIR_DEFAULT_PATH;
				}else{
					$path = $_POST['path'];
				}
				echo $this->DirPath(RealPath2($path));
				exit();
			break;

			// Показать дерево папок (слева)
			case 'showtree':
				if(isset($_POST['default'])){
					$path = DIR_DEFAULT_PATH;
					$this->firstAct = true;
				}else{
					$path = $_POST['path'];
				}
				echo $this->DirStructure('first', RealPath2($path));
				exit();
			break;

			// Показать файлы
			case 'showdir':
				if(isset($_POST['default'])){
					$path = DIR_DEFAULT_PATH;
				}else{
					$path = $_POST['path'];
				}
				echo $this->ShowDir(RealPath2($path));
				exit();
			break;

			// Загрузить изображение
			case 'uploadfile':
				echo $this->UploadFile();
				exit();
			break;

			// Удалить файл, или несколько файлов
			case 'delfile':
				if(is_array($_POST['md5']) && is_array($_POST['filename'])){
					echo $this->DelFiles($_POST['path'], $_POST['md5'], $_POST['filename']);
				}else{
					echo JsonEncode(array('error' => 'Ошибка'));
				}
				exit();
			break;

			// Удалить папку
			case 'delfolder':
				echo $this->DelFolder($_POST['path']);
				exit();
			break;

			// Переименовать файл
			case 'renamefile':
				echo $this->RenameFile($_POST['path'], $_POST['filename'], $_POST['newname']);
				exit();
			break;

			// Отправить номер сессии
			case 'SID':
				echo session_id();
				exit();
			break;
		}
	}


	/**
	 * Дерево каталогов
	 * функция рекурсивная
	 * @param $beginFolder
	 * @return array
	 */
	public function Tree( $beginFolder ){
		$struct = array();
		$handle = opendir($beginFolder);
		if($handle){
			$struct[$beginFolder]['path'] = $beginFolder;
			$tmp = preg_split('[\\/]', $beginFolder);
			$tmp = array_filter($tmp);
			end($tmp);
			$struct[$beginFolder]['name'] = current($tmp);
			$struct[$beginFolder]['count'] = 0;
			while(false !== ($file = readdir($handle))){
				if($file != "." && $file != ".." && $file != '.thumbs'){
					if(is_dir($beginFolder.'/'.$file)){
						$struct[$beginFolder]['childs'][] = $this->Tree($beginFolder.'/'.$file);
					}else{
						$struct[$beginFolder]['count']++;
					}
				}
			}
			closedir($handle);
			asort($struct);
			return $struct;
		}
		return false;
	}

	/**
	 * Визуализация дерева каталогов
	 * функция рекурсивная
	 * @param first|String $innerDirs
	 * @param String $currentDir
	 * @param int $level
	 * @internal param \files|\images $type
	 * @return html
	 */
	public function DirStructure( $innerDirs='first', $currentDir='', $level=0 ){
		$currentDirArr = array();
		$ret = '';
		if($currentDir != ''){
			$currentDirArr = preg_split('[\\/]', $currentDir);
			$currentDirArr = array_filter($currentDirArr);
		}

		if(!is_array($innerDirs)){
			$innerDirs = array();
			$innerDirs = $this->Tree(DIR_FILES);
			if($innerDirs == false) return 'Неверно задана корневая директория ('.DIR_FILES.')';

			$firstAct = $this->firstAct ? 'folderAct' : '';
			foreach($innerDirs as $v){
				$ret = '<div class="folderFiles '.$firstAct.'" path="">Файлы '.($v['count']>0 ? '<span id="count_files"> ('.$v['count'].')</span>' : '').'</div>';
				$ret .= '<div class="folderOpenSection" style="display:block;">';
				if(isset($v['childs'])){
					$ret .= $this->DirStructure($v['childs'], $currentDir, $level);
				}
				$ret .= '</div>';
				break;
			}
			return $ret;
		}

		if(sizeof($innerDirs)==0){
			return '';
		}
		foreach($innerDirs as $v){
			foreach($v as $v){} // В $v массив из одного элемента со строковым ключем, берем его и присв. в $v
			// $v = array( path => '', name => '', count => 0, [childs => array()])

			$files = 'Файлов: '.$v['count'];
			$count_childs = isset($v['childs']) ? sizeof($v['childs']) : 0;
			if($count_childs != 0){
				$files .= ', папок: '.$count_childs;
			}

			// Избавляемся от лишнего
			if(substr($v['path'], 0, strlen(DIR_FILES)) == DIR_FILES){
				$v['path'] = substr($v['path'], strlen(DIR_FILES));
			}
			$v['path'] = RealPath2($v['path']);

			if(isset($v['childs'])){
				$folderOpen = '';
				$folderAct = '';
				$folderClass = 'folderS';
				if(isset($currentDirArr[$level+1])){
					if($currentDirArr[$level+1] == $v['name']){
						$folderOpen = 'style="display:block;"';
						$folderClass = 'folderOpened';
						if($currentDirArr[sizeof($currentDirArr)]==$v['name'] && !$this->folderAct){
							$folderAct = 'folderAct';
							$this->folderAct = true;
						}else{
							$folderAct = '';
						}
					}
				}
				$ret .= '<div class="'.$folderClass.' '.$folderAct.'" path="'.$v['path'].'" title="'.$files.'">'.$v['name'].($v['count']>0?'<span id="count_files"> ('.$v['count'].')</span>':'').'</div><div class="folderOpenSection" '.$folderOpen.'>';
				$ret .= $this->DirStructure($v['childs'], $currentDir, $level+1);
				$ret .= '</div>';
			}else{
				$soc = sizeof($currentDirArr);
				if($soc>0 && $currentDirArr[$soc-1]==$v['name']){
					$folderAct = 'folderAct';
				}else{
					$folderAct = '';
				}
				$ret .= '<div class="folderClosed '.$folderAct.'" path="'.$v['path'].'" title="'.$files.'">'.$v['name'].($v['count']>0?' ('.$v['count'].')':'').'</div>';
			}
		}
		return $ret;
	}

	/**
	 * Путь (хлебные крошки)
	 * @param String $path
	 * @internal param \files|\images $type
	 * @return html
	 */
	public function DirPath( $path = '' ){
		if($path != '') {
			$path = preg_split('[\\/]', $path);
			$path = array_filter($path); // Удаляет пустоты
		}
		$ret = '<div class="addrItem" path="" title="">'
		       .'<img src="img/folder_open_document.png" width="16" height="16" alt="Корневая директория" />'
		       .'</div>';

		$i = 0;
		$addPath = '';
		$size = sizeof($path);
		if(is_array($path)){
			foreach($path as $v){
				$i++;
				$addPath .= '/'.$v;
				if($i == $size){
					$ret .= '<div class="addrItemEnd" path="'.RealPath2($addPath).'" title="">'.$v.'</div>';
				}else{
					$ret .= '<div class="addrItem" path="'.RealPath2($addPath).'" title="">'.$v.'</div>';
				}
			}
		}
		return $ret;
	}

	public function UploadFile(){
		$dir = RealPath2(DIR_FILES.'/'.$_POST['path']);
		if(!is_dir($dir)) return false;

		//Файл из flash-мультизагрузки
		if(isset($_POST['Filename'])){
			$filename = Translit4Url(Utf8ToCp1251($_POST['Filename']));
			$extension = GetFileExt($filename, true);
			$filename = GetFileName($filename);

			// Проверка расширения
			if($extension == '' || !in_array(strtolower($extension), $this->ALLOWED_FILES)){
				header('HTTP/1.1 403 Forbidden');
				exit();
			}

			$md5 = md5_file($_FILES['Filedata']['tmp_name']);
			$file = $dir.'/'.$filename.'.'.$extension;

			// Сохраняем файл
			if(!copy($_FILES['Filedata']['tmp_name'], $file)){
				header('HTTP/1.0 500 Internal Server Error');
				exit();
			}
		}else{
			header('HTTP/1.1 403 Forbidden');
			exit();
		}
		return 'OK';
	}

	public function RenameFile( $dir, $filename, $newname ){
		$dir = RealPath2(DIR_FILES.'/'.$dir);
		$filename = RealPath2($dir.'/'.$filename);
		$newname = RealPath2($dir.'/'.Translit4Url(Utf8ToCp1251($newname))).GetFileExt($filename);

		if(!is_file($filename)) return JsonEncode(array('error' => 'Ошибка. Файл не существует'));

		if($filename == $newname)
			return JsonEncode(array('ok' => GetFileName($newname),'ok2' => GetFileName($newname, false),'linkto' => $newname));

		if(is_file($newname)) return JsonEncode(array('error' => 'Ошибка. Невозможно переименовать, файл с таким именем уже существует'));
		if(!is_dir($dir.'/.thumbs')) return JsonEncode(array('error' => 'Ошибка. Нет папки с эскизами'));

		// Переименование
		if(!rename($filename, $newname)){
			return JsonEncode(array('error' => 'Ошибка переименования файла'));
		}

		return JsonEncode(array('ok' => GetFileName($newname), 'ok2' => GetFileName($newname, false), 'linkto' => $newname));
	}

	public function CallDir( $dir ){
		$old_dir = $dir;
		$dir = RealPath2(DIR_FILES.'/'.$dir);
		if(!is_dir($dir)){
			return false;
		}

		set_time_limit(120);
		if(!is_dir($dir.'/.thumbs')){
			mkdir($dir.'/.thumbs');
		}

		$files = array();
		if(!($handle = opendir($dir))) return $files;

		while(false !== ($file = readdir($handle))){
			$filename = $dir.'/'.$file;
			if($file != "." && $file != ".." && is_file($filename)){
				$file_info = pathinfo($filename);
				$file_info['extension'] = strtolower($file_info['extension']);
				$path = RealPath2($file_info['dirname']);
				$link = RealPath2(URL_FILES.'/'.$old_dir.'/'.$file);
				$type = 'file';
				if(in_array(strtolower($file_info['extension']), $this->ALLOWED_IMAGES)){ // Изображение
					$type = 'image';
					$imageinfo = getimagesize($filename);
					$files[$file]['imageinfo'] = array(
						'width'	=> $imageinfo[0],
						'height'=> $imageinfo[1],
					);
				}
				$files[$file]['general'] = array(
					'filename' => $file,
					'name'	=> basename(strtolower($file_info['basename']), '.'.$file_info['extension']),
					'ext'	=> $file_info['extension'],
					'path'	=> $path,
					'link'	=> $link,
					'size'	=> filesize($filename),
					'date'	=> filemtime($filename),
					'md5'	=> md5_file($filename),
					'type' => $type
				);
			}
		}
		closedir($handle);
		return $files;
	}

	public function ShowDir( $dir ){
		$dir_files = $this->CallDir($dir);
		if($dir_files == false){
			return '';
		}
		$ret = '';
		$middle_thumb_attr = '';
		foreach($dir_files as $v){
			$thumb = $this->GetThumbUrl($dir, $v['general']['md5'], $v['general']['filename'], 100, 100);

			$imageinfo = '';
			if($v['general']['type'] ==  'image'){
				$imageinfo = ' fwidth="'.$v['imageinfo']['width'].'" fheight="'.$v['imageinfo']['height'].'" ';
				if($v['imageinfo']['width'] > WIDTH_TO_LINK || $v['imageinfo']['height'] > HEIGHT_TO_LINK){
					if($v['imageinfo']['width'] > $v['imageinfo']['height']){
						$middle_thumb = $this->GetThumbUrl($dir, $v['general']['md5'], $v['general']['filename'], WIDTH_TO_LINK, 0);
					}else{
						$middle_thumb = $this->GetThumbUrl($dir, $v['general']['md5'], $v['general']['filename'], 0, HEIGHT_TO_LINK);
					}
					$middle_thumb = RealPath2(DIR_FILES.'/'.$dir.'/.thumbs/'.basename($middle_thumb));
					list($middle_width, $middle_height) = getimagesize($middle_thumb);
					$middle_thumb_attr = 'fmiddle="'.$middle_thumb.'" fmiddlewidth="'.$middle_width.'" fmiddleheight="'.$middle_height.'" fclass="'.CLASS_LINK.'" frel="'.REL_LINK.'"';
				}
			}

			$ret .= '<table class="imageBlock0" cellpadding="0" cellspacing="0"
						filename="'.$v['general']['filename'].'"
						fname="'.$v['general']['name'].'"
						ext="'.strtoupper($v['general']['ext']).'"
						path="'.$v['general']['path'].'"
						linkto="'.$v['general']['link'].'"
						linkto_url="'.URL_FILES.'/'.($dir != '' ? $dir.'/' : '').$v['general']['filename'].'"
						fsize="'.$v['general']['size'].'"
						fsizetext="'.FormatFileSize($v['general']['size']).'"
						date="'.date('d.m.Y H:i',$v['general']['date']).'"
						type="'.$v['general']['type'].'"
						md5="'.$v['general']['md5'].'"'.$imageinfo.$middle_thumb_attr.'>
				<tr>
					<td valign="bottom" align="center">
						<div class="imageBlock1">
							<div class="imageImage">
								<img src="'.$thumb.'" alt="'.$v['general']['name'].'">
							</div>
							<div class="imageName">'.$v['general']['name'].'</div>
						</div>
					</td>
				</tr>
				</table>';
		}
		return $ret;
	}

	/**
	 * Возвращает URL эскиза изображения или иконки файла
	 * @param  $dir
	 * @param  $md5
	 * @param  $filename
	 * @param int $width
	 * @param int $height
	 * @internal param $type
	 * @return string
	 */
	public function GetThumbUrl( $dir, $md5, $filename, $width=100, $height=100 ){
		$path = DIR_FILES.'/'.RealPath2($dir);
		$url_path = URL_FILES.'/'.RealPath2($dir);
		if(substr($url_path, -1) == '/'){
			$url_path = substr($url_path, 0, -1);
		}

		if(is_file($path.'/.thumbs/'.$md5.'_'.$width.'_'.$height.'.jpg')){
			return $url_path.'/.thumbs/'.$md5.'_'.$width.'_'.$height.'.jpg';
		}

		$ext = strtolower(GetFileExt($filename, true));
		if(in_array($ext, $this->ALLOWED_IMAGES)){
			$src = $path.'/'.$filename;
			$thumb = '/.thumbs/'.$md5.'_'.$width.'_'.$height.'.jpg';
			CreateThumb($src, $path.$thumb, $width, $height);
			return $url_path.$thumb;
		}else{
			if($ext != '' && file_exists(DIR_ICONS.'/'.$ext.'.png')){
				return URL_ICONS.'/'.$ext.'.png';
			}else{
				return URL_ICONS.'/'.'none.png';
			}
		}
	}

	public function GetThumbFile( $dir, $md5, $filename, $mode, $width=100, $height=100 ){
		$path = DIR_FILES.'/'.RealPath2($dir);
		if(is_file($path.'/.thumbs/'.$md5.'_'.$width.'_'.$height.'_'.$mode.'.jpg')){
			return $url_path.'/.thumbs/'.$md5.'_'.$width.'_'.$height.'_'.$mode.'.jpg';
		}else{
			return false;
		}
	}


	public function DelFiles( $path, $md5array, $filenames ){
		$result = array();

		$path2 = $path;
		$path = RealPath2(DIR_FILES.'/'.$path);
		if(!is_dir($path)){
			$result['error'] = 'Путь не существует';
			return JsonEncode($result);
		}

		// Удаляем эскизы
		if(is_dir($path.'/.thumbs')){
			$handle = opendir($path.'/.thumbs');
			if($handle){
				while(false !== ($file = readdir($handle))){
					if($file != "." && $file != ".." && in_array(substr($file, 0, 32), $md5array)){
						unlink($path.'/.thumbs/'.$file);
					}
				}
			}
		}

		// Удаляем файлы
		foreach($filenames as $filename){
			$file = RealPath2($path.'/'.$filename);
			if(is_file($file)){
				if(!unlink($file)){
					$result['error'][] = 'Не удалось удалить: '.$filename;
				}
			}else{
				$result['error'][] = 'Файл не существует: '.$filename;
			}
		}

		if(isset($result['error'])){
			$result['error'] = implode("\n", $result['error']);
		}else{
			$result['ok'] = $this->ShowDir($path2);
		}
		return JsonEncode($result);
	}

	public function DelFolder( $path ){
		$result = array();
		if(RealPath2($path).'/' == RealPath2(DIR_FILES).'/'){
			return $result['error'] = 'Нельзя удалить корневую папку!';
			return JsonEncode($result);
		}
		$path = RealPath2(DIR_FILES.'/'.$path);
		if(!is_dir($path)){
			$result['error'] = 'Папка не существует';
			return JsonEncode($result);
		}
		if(RmDirRecursive($path)){
			$result['ok'] = true;
		}else{
			$result['error'] = 'Ошибка при удалении папки';
		}
		return JsonEncode($result);
	}

}

$letsGo = new TinyImageManager();

?>