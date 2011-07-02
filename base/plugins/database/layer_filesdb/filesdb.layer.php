<?php

/*----------------------------------------------
// История версий

*** 1.0.0.

- Первая версия.

* 1.2.2.

- Добавлены функции добавления и удаления колонок в таблицах.


*** 2.0.0. - 3.02.07г.

- Данные в таблицах теперь хранятся в упакованном, функцией Serialize, виде.
- Немного увеличена скорость.

*** 2.2.1. - 25.02.08г.

- Стандартизованы все запросы.
- ShowTables переименована в GetTables
- GetTables выдает результат вида $tables = array(array('table1'),array('table2'),...).

*** 2.5.0 - 3.08.2008

- Результаты запросов теперь выдаются как с числовыми так и строковыми ключами.
- Переименован класс из Database в Database_FilesDB.

*** 2.6.0 - 15.07.2009

- Добавлена функция GetLastId().

----------------------------------------------*/

class LcDatabaseFilesDB
{
	//  Данные //
	public $DbAccess; // Имя папки базы данных(с последнем слэшем)
	public $Server; // Имя папки с базами данных(с последнем слэшем)
	public $User; // Имя пользователя базы данных. Не используется.
	public $Password; // Пароль базы данных. Не используется.
	public $SelectDbName; // Имя выбранной базы данных(просто имя папки без пути и последнего слэша)

	public $Prefix = 'table'; // Префикс таблиц базы данных
	public $Connected = false; // Показывает установлено ли соединение с базой данных
	public $DbSelected = false; // Показывает выбрана ли база данных

	//  Результат запроса //
	public $QueryResult; // Результат последнего запроса
	public $LastId = 0;

	//  Мониторинг //
	public $QueryTotalTime = 0; // Время выполнения всех проведённых запросов(сек.)
	public $QueryTime = 0; // Время выполнения последнего запроса(сек.)
	public $NumQueries = 0; // Всего количество запросов к базе данных/

	//  Ошибки //
	public $AllErrors; // Лог всех ошибок
	public $ErrorReporting = false; // Выводить ли сообщения об ошибках автоматически
	public $Error = false; // Показывает была ли ошибка при последней операции
	public $ErrorMsg = ''; // Если была ошибка при последней операции хранит сообщение о ней, иначе пустая строка

	//  Настройки //
	public $UseCache = true; // Использовать ли кэш. Сильно увеличит скорость при множественных запросах к одной таблице.
	public $TableFileExt = ".FRM"; // Расширения для файлов таблиц с записями(с точкой)
	public $InfoFileExt = ".MYD"; // Расширения для файлов с информацией о таблицах(с точкой)

	//  Системные //
	public $QueryLayer = 0; // Показывает уровень вложенности операции. Системная.
	public $Method = ''; // Показывает какой метод был вызван последним. Низкоуровневая, системная.
	public $Cache; // Кэш чтения файлов.

	public $Name = 'FilesDB'; // Имя базы данных
	public $Version = '2.6.0'; // Версия базы данных

	// Конструктор
	function __construct()
	{
		$this->QueryResult = array();
		$this->AllErrors = array();
	}

	// Точное системное время
	private function Now()
	{
		return microtime(true);
	}

	//  Обрабатывает ошибку
	protected function Error( $msg )
	{
		$msg = '<p><b>'.$this->Method.'</b>: '.$msg.'<p>';
		$this->ErrorMsg = $msg;
		$this->AllErrors[] = $msg;
		$this->Error = true;
		if($this->ErrorReporting){
			echo $msg;
		}
		$this->EndQ();
	}

	// Возвращает префикс таблиц если он есть
	public function Prefix()
	{
		return ($this->Prefix == '' ? '' : $this->Prefix.'_');
	}

	// Успешное завершение запроса
	protected function Good()
	{
		$this->EndQ();
		$this->ErrorMsg = '';
		$this->Error = false;
	}

	// Начало запроса (засекает время)
	protected function StartQ( $method = '' )
	{
		$this->QueryLayer++;
		if($this->QueryLayer == 1){
			$this->QueryTime = $this->now();
			$this->NumQueries++;
			$this->Method = $method;
		}
	}

	// Конец запроса
	protected function EndQ()
	{
		if($this->QueryLayer > 0){
			$this->QueryLayer--;
			if($this->QueryLayer == 0){
				$this->QueryTime = $this->Now() - $this->QueryTime;
				$this->QueryTotalTime += $this->QueryTime;
				$this->Method = '';
			}
		}
	}

	// Устанавливает результат запроса для использования в FetchRow
	protected function SetResult( $result )
	{
		$this->QueryResult = $result;
		if(is_array($result)){
			Reset($this->QueryResult);
		}
		return $this->QueryResult;
	}

	// Возвращает информацию о таблице
	// protected
	protected function _GetTableInfo( $name )
	{
		$this->StartQ('Database->_GetTableInfo()');
		$iname = $this->DbAccess.$this->Prefix().$name.$this->InfoFileExt;
		$tname = $this->DbAccess.$this->Prefix().$name.$this->TableFileExt;
		if(is_file($iname) && file_exists($iname)){
			if(isset($this->Cache['info'][$iname])){
				$info = $this->Cache['info'][$iname];
			}else{
				$info = Unserialize(file_get_contents($iname));
				$info['size'] = filesize($tname) + filesize($iname);
				$info['name'] = $name;
				if($this->UseCache){
					$this->Cache['info'][$iname] = $info;
				}
			}
		}else{
			$this->Error('Таблица "'.$name.'" не найдена');
			return false;
		}
		$this->Good();
		return $info;
	}

	// Обновляет информацию о таблице
	protected function UpdateTableInfo( $name, $info )
	{
		$this->StartQ('Database->UpdateTableInfo()');
		$name = $this->DbAccess.$this->Prefix().$name.$this->InfoFileExt;
		if(file_exists($name)){
			if($this->UseCache){
				$this->Cache['info'][$name] = $info;
			}
			file_put_contents($name, Serialize($info), LOCK_EX);
		}else{
			$this->Error('Таблица не найдена');
			return false;
		}
		$this->Good();
		return true;
	}

	// Функция возвращает все поля таблицы
	protected function GetTableData( $name )
	{
		$this->StartQ('Database->GetTableData()');
		$name = $this->DbAccess.$this->Prefix().$name.$this->TableFileExt;
		if(file_exists($name) && is_file($name)){
			if(isset($this->Cache['data'][$name])){
				$data = $this->Cache['data'][$name];
			}else{
				$data = Unserialize(file_get_contents($name));
				if($this->UseCache){
					$this->Cache['data'][$name] = $data;
				}
			}
		}else{
			$this->Error('Таблица с таким именем не существует.');
			return false;
		}
		$this->Good();
		return $data;
	}

	// Устанавливает параметры для доступа к БД
	// $hostdir с последним слэшем
	public function Connect( $hostdir, $user, $pass, $dbname = "" )
	{
		if(!is_dir($hostdir)){
			$this->Error('Не удалось подключится к серверу! "'.$hostdir.'"');
			return false;
		}
		if($this->Connected){
			$this->Disconnect();
		}
		$this->Server = $hostdir;
		$this->User = $user;
		$this->Password = $pass;
		$this->Connected = true;
		if(($dbname != "") && is_dir($hostdir.$dbname)){
			$this->DbAccess = $hostdir.$dbname.'/';
			$this->SelectDbName = $dbname;
			$this->DbSelected = true;
		}
		$this->Good();
		return true;
	}

	// Разъединяется с сервером базы данных
	public function Disconnect()
	{
		$this->StartQ('Database->Disconnect()');
		if(!$this->Connected){
			$this->Error('База данных не открыта');
			return false;
		}
		$this->Server = '';
		$this->User = '';
		$this->Password = '';
		if($this->DbSelected){
			$this->DbAccess = '';
			$this->SelectDbName = '';
			$this->DbSelected = false;
		}
		$this->Connected = false;
		$this->Good();
		return true;
	}

	// Создаёт базу данных.
	public function CreateDb( $ShortName, $dropIfExists = false )
	{
		$this->StartQ('Database->CreateDb()');
		$name = $this->Server.$ShortName;
		if(file_exists($name)){
			if(!$dropIfExists){
				$this->Error('База данных с таким именем уже существует');
				return false;
			}else{
				$this->DropDb($ShortName);
			}
		}
		$flag = mkdir($name, 0777);
		if(!$flag){
			$this->Error('Не удалось создать базу данных');
			return false;
		}else{
			$this->Good();
			return true;
		}
	}

	// Удаляет БД.
	public function DropDb( $ShortName )
	{
		$this->StartQ('Database->DropDb()');
		$name = $this->Server.$ShortName;
		$dir = @opendir($name);
		if(!$dir){
			$this->Error('Не удалось открыть базу данных');
			return false;
		}
		while($file = @readdir($dir)){
			$fn = $name.'/'.$file;
			if(is_file($fn)){
				if(!@unlink($fn)){
					$this->Error('Не удалось удалить '.$file);
					return false;
				}
			}else
				if(is_dir($fn) && ($file != ".") && ($file != "..")){
					if(!Drop_db($fn)){
						$this->Error('Не удалось удалить '.$file);
						return false;
					}
				}
		}
		@closedir($dir);
		$r = @rmdir($name);
		if(!$r){
			$this->Error('Не удалось удалить базу данных.');
			return false;
		}
		$this->Good();
		return true;
	}

	// Выбирает БД
	public function SelectDb( $name )
	{
		$this->StartQ('Database->SelectDb()');
		$bname = $this->Server.$name;
		if(is_dir($bname)){
			$this->DbAccess = $bname.'/';
			$this->SelectDbName = $name;
			$this->DbSelected = true;
			$this->Good();
			return true;
		}else{
			$this->Error('База данных "'.$name.'" не существует!');
			return false;
		}
	}

	// Далее функции которые работают с выбранной базой данных
	// Создаёт таблицу
	public function CreateTable( $name, $query, $dropex = false )
	{
		$this->StartQ('Database->CreateTable()');
		// Создаём файл таблицы
		$table = $this->DbAccess.$this->Prefix().$name.$this->TableFileExt;
		if(file_exists($table) && !$dropex){
			$this->Error('Таблица с таким именем уже существует!');
			return false;
		}
		$tf = fopen($table, "w");
		if(!$tf){
			$this->Error('Ошибка создания файла таблицы');
			return false;
		}else{
			$table = array();
			fwrite($tf, Serialize($table));
			@fclose($tf);
		}
		// Создаем информационный файл
		$info = $this->DbAccess.$this->Prefix().$name.$this->InfoFileExt;
		$query['num_rows'] = 0;
		$query['counter'] = 0;
		$query = array_reverse($query, true);
		file_put_contents($info, Serialize($query), LOCK_EX);
		$this->Good();
		return true;
	}

	// Удаляет таблицу
	public function DropTable( $name )
	{
		$this->StartQ('Database->DropTable()');
		$tfile = $this->DbAccess.$this->Prefix().$name.$this->TableFileExt;
		$ifile = $this->DbAccess.$this->Prefix().$name.$this->InfoFileExt;
		if(is_file($tfile) && !unlink($tfile)){
			$this->Error('Не удалось удалить таблицу '.$name.$this->TableFileExt.'.');
			return false;
		}
		if(is_file($ifile) && !unlink($ifile)){
			$this->Error('Не удалось удалить информационный файл таблицы '.$name.$this->InfoFileExt.'.');
			return false;
		}
		$this->Good();
		return true;
	}

	// Переименовывает таблицу
	public function RenameTable( $lastname, $newname )
	{
		$this->StartQ('Database->RenameTable()');
		$last = $this->DbAccess.$this->Prefix().$lastname.$this->TableFileExt;
		$lastinf = $this->DbAccess.$this->Prefix().$lastname.$this->InfoFileExt;
		$new = $this->DbAccess.$this->Prefix().$newname.$this->TableFileExt;
		$newinf = $this->DbAccess.$this->Prefix().$newname.$this->InfoFileExt;
		if(!rename($last, $new)){
			$this->Error('Ошибка переименования таблицы.');
			return false;
		}
		if(!rename($lastinf, $newinf)){
			$this->Error('Ошибка переименования информационного файла.');
			if(!rename($new, $last)){
				$this->Error('Критическая ошибка! Не удалось восстановить файл таблицы. База данных может работать некорректно!');
			}
			return false;
		}
		$this->Good();
		return true;
	}

	public function SetTableComment( $Name, $Comment )
	{
		$this->StartQ('Database->SetTableComment()');
		$info = $this->_GetTableInfo($Name);
		$info['comment'] = $Comment;
		$this->UpdateTableInfo($Name, $info);
		$this->Good();
		return true;
	}

	public function SetTableType( $Name, $Type )
	{
		$this->StartQ('Database->SetTableType()');
		$info = $this->_GetTableInfo($Name);
		$info['type'] = $Type;
		$this->UpdateTableInfo($Name, $info);
		$this->Good();
		return true;
	}

	// Выводит массив-список имен имеющихся таблиц
	public function GetTables()
	{
		$this->StartQ('Database->GetTables()');
		$dir = opendir($this->DbAccess);
		if(!$dir){
			$this->Error('Не удалось найти, или открыть базу данных или каталог.');
			return false;
		}
		$i = -1;
		$tables = array();
		while($file = readdir($dir)){
			$i++;
			$epos = strpos($file, $this->TableFileExt);
			if(!($epos === false)){
				$tname = substr($file, 0, $epos);
				$tables[] = array(substr($tname, strlen($this->Prefix) + 1));
			}
		}
		@closedir($dir);
		$this->Good();
		return $this->SetResult($tables);
	}

	// Возвращает информацию о всех таблицах в базе данных
	public function GetTableInfo( $Name = '' )
	{
		$this->StartQ('Database->GetTablesInfo()');
		if(!empty ($Name)){
			return array($this->_GetTableInfo($Name));
		}
		$tables = $this->GetTables();
		$infs = array();
		foreach($tables as $table){
			$infs[] = $this->_GetTableInfo($table[0]);
		}
		$this->Good();
		return $this->SetResult($infs);
	}

	public function GetTableColumns( $tablename )
	{
		$this->StartQ('Database->GetTableColumns()');
		$columns = $this->_GetTableInfo($tablename);
		$this->Good();
		return $this->SetResult($columns['cols']);
	}

	// Возвращает информацию о одной колонке таблицы
	public function GetColl( $name, $index )
	{
		$col = $this->GetTableColumns($name);
		if(isset($col[$index])){
			return $col[$index];
		}else{
			return false;
		}
	}

	// Добавляет новую колонку в таблицу
	public function InsertColl( $name, $coll, $collindex )
	// Если $collindex=-1 то колонка будет добавлена в конец
	// В $coll массив описания ячейки как в описании таблицы
	{
		$table = $this->_GetTableInfo($name);
		$cols = $this->GetTableColumns($name);
		$counter = $table['counter'];
		$newcols = array();
		$coll_cnt = count($cols);
		$collindex = intval($collindex);
		if($collindex > -1){
			$collindex = $collindex + 1;
		}elseif($collindex == -1 || $collindex >= $coll_cnt - 1){
			$collindex = $coll_cnt;
		}
		for($i = 0; $i <= $coll_cnt; $i++){
			if($collindex == $i){
				$newcols[] = $coll;
			}
			if($i == $coll_cnt){
				break;
			}else{
				$newcols[] = $cols[$i];
			}
		}
		$table['cols'] = $newcols;
		$this->UpdateTableInfo($name, $table);
		unset($table, $cols, $newcols);
		// Обновляем данные в базе данных добавляя в них пустую колонку
		$table = $this->GetTableData($name);
		///
		for($i = 0, $c = count($table); $i < $c; $i++){
			$new_row = array();
			for($j = 0; $j <= $coll_cnt; $j++){
				if($collindex == $j){
					$new_val = '';
					if(isset($coll['auto_increment']) && $coll['auto_increment'] == true){
						$new_val = $counter + $i;
					}else{
						if(isset($coll['default'])){
							$new_val = $coll['default'];
						}else{
							$new_val = '';
						}
					}
					$new_row[] = $new_val;
				}
				// Если новый элемент добавился не в конец,
				// если он вообще добавлялся, то заполняем массив дальше, гениально и просто!
				if($j == $coll_cnt){
					break;
				}else{
					$new_row[] = $table[$i][$j];
				}
			}
			$table[$i] = $new_row;
		}
		///
		$n = $this->DbAccess.$this->Prefix().$name.$this->TableFileExt;
		if(isset($this->Cache['data'][$n])){
			$this->Cache['data'] = $table;
		}
		file_put_contents($n, Serialize($table), LOCK_EX);
	}

	// Удаляет колонку таблицы
	public function DeleteColl( $name, $index )
	{
		$table = $this->_GetTableInfo($name);
		$cols = $table['cols'];
		$newcols = array();
		$coll_cnt = count($cols);
		for($i = 0; $i < $coll_cnt; $i++){
			if($index == $i){
				continue;
			}
			$newcols[] = $cols[$i];
		}
		$table['cols'] = $newcols;
		$this->UpdateTableInfo($name, $table);
		unset($table, $cols, $newcols);
		$table = $this->GetTableData($name);
		for($i = 0, $c = count($table); $i < $c; $i++){
			$new_row = array();
			for($j = 0; $j < $coll_cnt; $j++){
				if($index == $j){
					continue;
				}
				$new_row[] = $table[$i][$j];
			}
			$table[$i] = $new_row;
		}
		$n = $this->DbAccess.$this->Prefix().$name.$this->TableFileExt;
		if(isset($this->Cache['data'][$n])){
			$this->Cache['data'] = $table;
		}
		file_put_contents($n, Serialize($table), LOCK_EX);
	}

	// Изменяет формат колонки таблицы
	public function EditColl( $name, $index, $coll )
	{
		$table = $this->_GetTableInfo($name);
		if(isset($table['cols'][$index])){
			$table['cols'][$index] = $coll;
			$this->UpdateTableInfo($name, $table);
		}
	}

	public function RenameColl( $name, $index, $newCollName )
	{
		$coll = $this->GetColl($name, $index);
		$coll['name'] = $newCollName;
		$this->EditColl($name, $index, $coll);
	}

	protected function SetValsOrder( &$values, &$cols, &$info )
	{
		$a = array();
		foreach($cols as $i=>$col){
			$a[$col] = $values[$i];
		}
		$b = array();
		$maxlength = count($info['cols']);
		foreach($info['cols'] as $i=>$coll){
			if($i<$maxlength){
				$b[] = $a[$coll['name']];
			}
		}
		return $b;
	}

	// Добавляет запись в таблицу
	public function Insert( $name, $values, $cols = '' )
	{
		$this->StartQ('Database->Insert()');
		$info = $this->_GetTableInfo($name);
		$data = $this->GetTableData($name);

		$values = Parser_ParseValuesStr($values, $info);
		if($cols != ''){
			$values = $this->SetValsOrder($values, $cols, $info);
		}

		$data[] = $values;
		$info['num_rows']++;
		$info['counter']++;
		$this->LastId = $info['counter']; // сохраняем id

		$this->UpdateTableInfo($name, $info);
		$n = $this->DbAccess.$this->Prefix().$name.$this->TableFileExt;
		if(isset($this->Cache['data'][$n])){
			$this->Cache['data'][$n][] = $values;
		}
		file_put_contents($n, Serialize($data), LOCK_EX);
		$this->Good();
	}

	// Обновляет запись в таблице
	public function Update( $name, $set, $where = '', $setisvalues = false, $cols = '' )
	{
		$this->StartQ('Database->Update()');
		$data = $this->GetTableData($name);
		$info = $this->_GetTableInfo($name);
		foreach($data as $i=>$row){
			if(Parser_ParseWhereStr($where, $row, $info, $i)){
				if(!$setisvalues){
					$new = Parser_ParseSetStr($set, $row, $info);
				}else{
					$new = Parser_ParseValuesStr($set, $info, true, $row);
					if($cols != ''){
						$new = $this->SetValsOrder($set, $cols, $info);
					}
				}
				$data[$i] = $new;

			}
		}
		$n = $this->DbAccess.$this->Prefix().$name.$this->TableFileExt;
		if($this->UseCache && isset($this->Cache['data'][$n])){
			$this->Cache['data'][$n] = $data;
		}
		file_put_contents($n, Serialize($data), LOCK_EX);
		$this->Good();
	}

	// Удаляет запись из таблицы
	public function Delete( $name, $where = '' )
	{
		$this->StartQ('Database->Delete()');

		$data = $this->GetTableData($name);
		$info = $this->_GetTableInfo($name);

		$new = array();
		foreach($data as $i=>$row){
			if(!Parser_ParseWhereStr($where, $row, $info, $i)){
				$new[] = $row;
			}else{
				$info['num_rows']--;
				if($info['num_rows'] == 0){
					$info['counter'] = 0;
				}
			}
		}

		$n = $this->DbAccess.$this->Prefix().$name.$this->TableFileExt;
		// Обновляем кэш если нужно
		if($this->UseCache && isset($this->Cache['data'][$n])){
			$this->Cache['data'][$n] = $new;
		}
		$this->UpdateTableInfo($name, $info);
		file_put_contents($n, Serialize($new), LOCK_EX);
		$this->Good();
	}

	// Выбирает записи из таблицы
	public function Select( $name, $where = '' )
	{
		$this->StartQ('Database->Select()');
		$data = $this->GetTableData($name);
		$info = $this->_GetTableInfo($name);
		$result = array();
		$i = 0;
		foreach($data as $row){
			$row = str_replace('&#13', "\r", $row);
			$row = str_replace('&#10', "\n", $row);
			if(Parser_ParseWhereStr($where, $row, $info, $i)){
				// Добавляем строковые ключи
				foreach($info["cols"] as $cid=>$col){
					$row[$col["name"]] = &$row[$cid];
					unset($row[$cid]);
				}
				$result[] = $row;
			}
			$i++;
		}
		$this->Good();
		return $this->SetResult($result);
	}

	// Возвращает количество записей в результате
	public function NumRows()
	{
		return Count($this->QueryResult);
	}

	// Возвращает следующую запись результата
	public function FetchRow()
	{
		$fet = Each($this->QueryResult);
		return $fet['1'];
	}

	// Очищает запрос базы данных
	public function FreeResult()
	{
		$this->QueryResult = array();
	}

	// Возвращает последнюю ошибку
	public function GetError( $echoed = false )
	{
		if($echoed){
			echo $this->ErrorMsg;
		}
		return $this->ErrorMsg;
	}

	// Возвращает ID, сгенерированный при последнем INSERT-запросе.
	public function GetLastId()
	{
		return $this->LastId;
	}

	// Экранирует спец-символы
	public function EscapeString( $UnescapedString )
	{
		return str_replace("'", "\\'", $UnescapedString);
	}

}
?>