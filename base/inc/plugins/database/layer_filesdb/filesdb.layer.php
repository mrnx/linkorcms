<?php

/*----------------------------------------------
// ������� ������

*** 1.0.0.

- ������ ������.

* 1.2.2.

- ��������� ������� ���������� � �������� ������� � ��������.


*** 2.0.0. - 3.02.07�.

- ������ � �������� ������ �������� � �����������, �������� Serialize, ����.
- ������� ��������� ��������.

*** 2.2.1. - 25.02.08�.

- ��������������� ��� �������.
- ShowTables ������������� � GetTables
- GetTables ������ ��������� ���� $tables = array(array('table1'),array('table2'),...).

*** 2.5.0 - 3.08.2008

- ���������� �������� ������ �������� ��� � ��������� ��� � ���������� �������.
- ������������ ����� �� Database � Database_FilesDB.

*** 2.6.0 - 15.07.2009

- ��������� ������� GetLastId().

----------------------------------------------*/

class LcDatabaseFilesDB
{
	//  ������ //
	public $DbAccess; // ��� ����� ���� ������(� ��������� ������)
	public $Server; // ��� ����� � ������ ������(� ��������� ������)
	public $User; // ��� ������������ ���� ������. �� ������������.
	public $Password; // ������ ���� ������. �� ������������.
	public $SelectDbName; // ��� ��������� ���� ������(������ ��� ����� ��� ���� � ���������� �����)

	public $Prefix = 'table'; // ������� ������ ���� ������
	public $Connected = false; // ���������� ����������� �� ���������� � ����� ������
	public $DbSelected = false; // ���������� ������� �� ���� ������

	//  ��������� ������� //
	public $QueryResult; // ��������� ���������� �������
	public $LastId = 0;

	//  ���������� //
	public $QueryTotalTime = 0; // ����� ���������� ���� ���������� ��������(���.)
	public $QueryTime = 0; // ����� ���������� ���������� �������(���.)
	public $NumQueries = 0; // ����� ���������� �������� � ���� ������/

	//  ������ //
	public $AllErrors; // ��� ���� ������
	public $ErrorReporting = false; // �������� �� ��������� �� ������� �������������
	public $Error = false; // ���������� ���� �� ������ ��� ��������� ��������
	public $ErrorMsg = ''; // ���� ���� ������ ��� ��������� �������� ������ ��������� � ���, ����� ������ ������

	//  ��������� //
	public $UseCache = true; // ������������ �� ���. ������ �������� �������� ��� ������������� �������� � ����� �������.
	public $TableFileExt = ".FRM"; // ���������� ��� ������ ������ � ��������(� ������)
	public $InfoFileExt = ".MYD"; // ���������� ��� ������ � ����������� � ��������(� ������)

	//  ��������� //
	public $QueryLayer = 0; // ���������� ������� ����������� ��������. ���������.
	public $Method = ''; // ���������� ����� ����� ��� ������ ���������. ��������������, ���������.
	public $Cache; // ��� ������ ������.

	public $Name = 'FilesDB'; // ��� ���� ������
	public $Version = '2.6.0'; // ������ ���� ������

	// �����������
	function __construct()
	{
		$this->QueryResult = array();
		$this->AllErrors = array();
	}

	// ������ ��������� �����
	private function Now()
	{
		return microtime(true);
	}

	//  ������������ ������
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

	// ���������� ������� ������ ���� �� ����
	public function Prefix()
	{
		return ($this->Prefix == '' ? '' : $this->Prefix.'_');
	}

	// �������� ���������� �������
	protected function Good()
	{
		$this->EndQ();
		$this->ErrorMsg = '';
		$this->Error = false;
	}

	// ������ ������� (�������� �����)
	protected function StartQ( $method = '' )
	{
		$this->QueryLayer++;
		if($this->QueryLayer == 1){
			$this->QueryTime = $this->now();
			$this->NumQueries++;
			$this->Method = $method;
		}
	}

	// ����� �������
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

	// ������������� ��������� ������� ��� ������������� � FetchRow
	protected function SetResult( $result )
	{
		$this->QueryResult = $result;
		if(is_array($result)){
			Reset($this->QueryResult);
		}
		return $this->QueryResult;
	}

	// ���������� ���������� � �������
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
			$this->Error('������� "'.$name.'" �� �������');
			return false;
		}
		$this->Good();
		return $info;
	}

	// ��������� ���������� � �������
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
			$this->Error('������� �� �������');
			return false;
		}
		$this->Good();
		return true;
	}

	// ������� ���������� ��� ���� �������
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
			$this->Error('������� � ����� ������ �� ����������.');
			return false;
		}
		$this->Good();
		return $data;
	}

	// ������������� ��������� ��� ������� � ��
	// $hostdir � ��������� ������
	public function Connect( $hostdir, $user, $pass, $dbname = "" )
	{
		if(!is_dir($hostdir)){
			$this->Error('�� ������� ����������� � �������! "'.$hostdir.'"');
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

	// ������������� � �������� ���� ������
	public function Disconnect()
	{
		$this->StartQ('Database->Disconnect()');
		if(!$this->Connected){
			$this->Error('���� ������ �� �������');
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

	// ������ ���� ������.
	public function CreateDb( $ShortName, $dropIfExists = false )
	{
		$this->StartQ('Database->CreateDb()');
		$name = $this->Server.$ShortName;
		if(file_exists($name)){
			if(!$dropIfExists){
				$this->Error('���� ������ � ����� ������ ��� ����������');
				return false;
			}else{
				$this->DropDb($ShortName);
			}
		}
		$flag = mkdir($name, 0777);
		if(!$flag){
			$this->Error('�� ������� ������� ���� ������');
			return false;
		}else{
			$this->Good();
			return true;
		}
	}

	// ������� ��.
	public function DropDb( $ShortName )
	{
		$this->StartQ('Database->DropDb()');
		$name = $this->Server.$ShortName;
		$dir = @opendir($name);
		if(!$dir){
			$this->Error('�� ������� ������� ���� ������');
			return false;
		}
		while($file = @readdir($dir)){
			$fn = $name.'/'.$file;
			if(is_file($fn)){
				if(!@unlink($fn)){
					$this->Error('�� ������� ������� '.$file);
					return false;
				}
			}else
				if(is_dir($fn) && ($file != ".") && ($file != "..")){
					if(!Drop_db($fn)){
						$this->Error('�� ������� ������� '.$file);
						return false;
					}
				}
		}
		@closedir($dir);
		$r = @rmdir($name);
		if(!$r){
			$this->Error('�� ������� ������� ���� ������.');
			return false;
		}
		$this->Good();
		return true;
	}

	// �������� ��
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
			$this->Error('���� ������ "'.$name.'" �� ����������!');
			return false;
		}
	}

	// ����� ������� ������� �������� � ��������� ����� ������
	// ������ �������
	public function CreateTable( $name, $query, $dropex = false )
	{
		$this->StartQ('Database->CreateTable()');
		// ������ ���� �������
		$table = $this->DbAccess.$this->Prefix().$name.$this->TableFileExt;
		if(file_exists($table) && !$dropex){
			$this->Error('������� � ����� ������ ��� ����������!');
			return false;
		}
		$tf = fopen($table, "w");
		if(!$tf){
			$this->Error('������ �������� ����� �������');
			return false;
		}else{
			$table = array();
			fwrite($tf, Serialize($table));
			@fclose($tf);
		}
		// ������� �������������� ����
		$info = $this->DbAccess.$this->Prefix().$name.$this->InfoFileExt;
		$query['num_rows'] = 0;
		$query['counter'] = 0;
		$query = array_reverse($query, true);
		file_put_contents($info, Serialize($query), LOCK_EX);
		$this->Good();
		return true;
	}

	// ������� �������
	public function DropTable( $name )
	{
		$this->StartQ('Database->DropTable()');
		$tfile = $this->DbAccess.$this->Prefix().$name.$this->TableFileExt;
		$ifile = $this->DbAccess.$this->Prefix().$name.$this->InfoFileExt;
		if(is_file($tfile) && !unlink($tfile)){
			$this->Error('�� ������� ������� ������� '.$name.$this->TableFileExt.'.');
			return false;
		}
		if(is_file($ifile) && !unlink($ifile)){
			$this->Error('�� ������� ������� �������������� ���� ������� '.$name.$this->InfoFileExt.'.');
			return false;
		}
		$this->Good();
		return true;
	}

	// ��������������� �������
	public function RenameTable( $lastname, $newname )
	{
		$this->StartQ('Database->RenameTable()');
		$last = $this->DbAccess.$this->Prefix().$lastname.$this->TableFileExt;
		$lastinf = $this->DbAccess.$this->Prefix().$lastname.$this->InfoFileExt;
		$new = $this->DbAccess.$this->Prefix().$newname.$this->TableFileExt;
		$newinf = $this->DbAccess.$this->Prefix().$newname.$this->InfoFileExt;
		if(!rename($last, $new)){
			$this->Error('������ �������������� �������.');
			return false;
		}
		if(!rename($lastinf, $newinf)){
			$this->Error('������ �������������� ��������������� �����.');
			if(!rename($new, $last)){
				$this->Error('����������� ������! �� ������� ������������ ���� �������. ���� ������ ����� �������� �����������!');
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

	// ������� ������-������ ���� ��������� ������
	public function GetTables()
	{
		$this->StartQ('Database->GetTables()');
		$dir = opendir($this->DbAccess);
		if(!$dir){
			$this->Error('�� ������� �����, ��� ������� ���� ������ ��� �������.');
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

	// ���������� ���������� � ���� �������� � ���� ������
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

	// ���������� ���������� � ����� ������� �������
	public function GetColl( $name, $index )
	{
		$col = $this->GetTableColumns($name);
		if(isset($col[$index])){
			return $col[$index];
		}else{
			return false;
		}
	}

	// ��������� ����� ������� � �������
	public function InsertColl( $name, $coll, $collindex )
	// ���� $collindex=-1 �� ������� ����� ��������� � �����
	// � $coll ������ �������� ������ ��� � �������� �������
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
		// ��������� ������ � ���� ������ �������� � ��� ������ �������
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
				// ���� ����� ������� ��������� �� � �����,
				// ���� �� ������ ����������, �� ��������� ������ ������, ��������� � ������!
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

	// ������� ������� �������
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

	// �������� ������ ������� �������
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

	// ��������� ������ � �������
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
		$this->LastId = $info['counter']; // ��������� id

		$this->UpdateTableInfo($name, $info);
		$n = $this->DbAccess.$this->Prefix().$name.$this->TableFileExt;
		if(isset($this->Cache['data'][$n])){
			$this->Cache['data'][$n][] = $values;
		}
		file_put_contents($n, Serialize($data), LOCK_EX);
		$this->Good();
	}

	// ��������� ������ � �������
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

	// ������� ������ �� �������
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
		// ��������� ��� ���� �����
		if($this->UseCache && isset($this->Cache['data'][$n])){
			$this->Cache['data'][$n] = $new;
		}
		$this->UpdateTableInfo($name, $info);
		file_put_contents($n, Serialize($new), LOCK_EX);
		$this->Good();
	}

	// �������� ������ �� �������
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
				// ��������� ��������� �����
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

	// ���������� ���������� ������� � ����������
	public function NumRows()
	{
		return Count($this->QueryResult);
	}

	// ���������� ��������� ������ ����������
	public function FetchRow()
	{
		$fet = Each($this->QueryResult);
		return $fet['1'];
	}

	// ������� ������ ���� ������
	public function FreeResult()
	{
		$this->QueryResult = array();
	}

	// ���������� ��������� ������
	public function GetError( $echoed = false )
	{
		if($echoed){
			echo $this->ErrorMsg;
		}
		return $this->ErrorMsg;
	}

	// ���������� ID, ��������������� ��� ��������� INSERT-�������.
	public function GetLastId()
	{
		return $this->LastId;
	}

	// ���������� ����-�������
	public function EscapeString( $UnescapedString )
	{
		return str_replace("'", "\\'", $UnescapedString);
	}

}
?>