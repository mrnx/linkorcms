<?php

/*----------------------------------------------
// ������� ������

*** 1.0.0.

*** 1.1.0 - 3.08.2008

- ���������� �������� ������ �������� ��� � ��������� ��� � ���������� �������.
- ������������ ����� �� Database � Database_MySQL

*** 1.2.0 - 15.07.2009

- ��������� ������� GetLastId().


----------------------------------------------*/

class LcDatabaseMySQL
{
	//  ������ //
	public $Server; // ��� ����� � ������ ������(� ��������� ������)
	public $User; // ��� ������������ ���� ������. �� ������������.
	public $Password; // ������ ���� ������. �� ������������.
	public $SelectDbName; // ��� ��������� ���� ������(������ ��� ����� ��� ���� � ���������� �����)
	public $Prefix = 'table'; // ������� ������ ���� ������
	public $Connected = false; // ���������� ����������� �� ���������� � ����� ������
	public $DbSelected = false; // ���������� ������� �� ���� ������

	//  ��������� ������� //
	public $QueryResult; // ��������� ���������� �������
	public $MySQLQueryResult; // ��������� �� ������ ���������� ������� MySQL

	//  ���������� //
	public $QueryTotalTime = 0; // ����� ���������� ���� ���������� ��������(���.)
	public $QueryTime = 0; // ����� ���������� ���������� �������(���.)
	public $NumQueries = 0; // ����� ���������� �������� � ���� ������

	//  ������ //
	public $AllErrors; // ��� ���� ������
	public $ErrorReporting = false; // �������� �� ��������� �� ������� �������������
	public $Error = false; // ���������� ���� �� ������ ��� ��������� ��������
	public $ErrorMsg = ''; // ���� ���� ������ ��� ��������� �������� ������ ��������� � ���, ����� ������ ������

	//  ��������� //
	public $QueryLayer = 0; // ���������� ������� ����������� ��������. ���������.
	public $Method = ''; // ���������� ����� ����� ��� ������ ���������. ��������������, ���������.
	public $Name = 'MySQl'; // ��� ���� ������
	public $Version = ''; // ������ ���� ������

	function __construct()
	{
		$this->QueryResult = array();
		$this->AllErrors = array();
	}

	private function Now()
	{
		return microtime(true);
	}

	protected function Error( $msg )
	{
		$msg = '<p><b>'.$this->Method.'</b>: '.$msg.'<p>';
		$this->ErrorMsg = $msg;
		$this->AllErrors[] = $msg;
		$this->Error = true;
		if($this->ErrorReporting){
			error_handler(USER_WARNING, $msg, __FILE__, __LINE__);

		}
		$this->EndQ();
	}

	public function Prefix()
	{
		return ($this->Prefix == '' ? '' : $this->Prefix.'_');
	}

	private function MySQLError( $err = 0, $msg = '' )
	{
		if($this->Connected){
			$this->Error(($err == 0 ? '<p><b>Database->MySQLError()</b> '.mysql_errno($this->DbAccess) : $err).': '.($msg == '' ? mysql_error($this->DbAccess) : $msg).'</p>');
		}
	}

	private function MySQLGetErrNo()
	{
		return mysql_errno($this->DbAccess);
	}

	private function MySQLGetErrMsg()
	{
		return mysql_error($this->DbAccess);
	}

	// private
	public function MySQLQuery( $query )
	{
		if($query != '' && $this->Connected){
			return $this->QueryResult = $this->MySQLQueryResult = @mysql_query($query, $this->DbAccess);
		}
	}

	private function MySQLQuery2( $query, $error_msg )
	{
		if($this->MySQLQuery($query)){
			$this->Good();
			return true;
		}else{
			$this->Error($error_msg);
			$this->MySQLError();
			return false;
		}
	}

	protected function Good()
	{
		$this->EndQ();
		$this->ErrorMsg = '';
		$this->Error = false;
	}

	protected function StartQ( $method = '' )
	{
		$this->QueryLayer++;
		if($this->QueryLayer == 1){
			$this->QueryTime = $this->now();
			$this->NumQueries++;
			$this->Method = $method;
		}
	}

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

	// ������������� ��������� �������
	protected function SetResult( $result )
	{
		$result_array = array();
		$nr = mysql_num_rows($result);
		if($nr > 0){
			mysql_data_seek($result, 0);
		}
		while($r = mysql_fetch_assoc($result)){
			$result_array[] = $r;
		}
		if($nr > 0){
			mysql_data_seek($result, 0);
		}
		$this->QueryResult = $result_array;
		Reset($this->QueryResult);
		return $this->QueryResult;
	}

	// ������������� ��������� �������
	protected function SetResult2( $result )
	{
		$this->QueryResult = $result;
		if(is_array($result)){
			Reset($this->QueryResult);
		}
		return $this->QueryResult;
	}

	protected function CollToSql( $coll, $oldCollName = '' )
	{
		$sql = '';
		if($oldCollName != ''){
			$sql .= '  `'.$oldCollName.'`';
		}
		$sql .= '  `'.$coll['name'].'`';
		$sql .= ' '.$coll['type'];
		if(isset($coll['length'])){
			$sql .= '('.$coll['length'].')';
		}
		if(isset($coll['attributes'])){
			$sql .= ' '.$coll['attributes'];
		}
		if(isset($coll['notnull'])){
			$sql .= ' NOT NULL';
		}
		if(isset($coll['auto_increment'])){
			$sql .= ' AUTO_INCREMENT';
		}elseif(isset($coll['default']) && $coll['default'] != ''){
			$sql .= ' DEFAULT \''.$coll['default'].'\'';
		}
		if(isset($coll['primary'])){
			$sql .= ' PRIMARY KEY';
		}
		return $sql;
	}

	protected function GetIndexes( $name )
	{
		$return = array();
		$result = $this->MySQLQuery('SHOW INDEX FROM `'.$this->Prefix().$name.'`');
		$result = $this->SetResult($this->QueryResult);
		if($result){
			foreach($result as $row ){
				if($row['Non_unique'] ==  '0' && $row['Key_name'] == 'PRIMARY'){
					$return[$row['Column_name']]['primary'] = $row['Key_name'];
				}elseif($row['Non_unique'] ==  '0' && $row['Key_name'] !== 'PRIMARY'){
					$return[$row['Column_name']]['unique'] = $row['Key_name'];
				}elseif($row['Non_unique'] ==  '1' && $row['Key_name'] !== 'PRIMARY' && $row['Index_type'] != 'FULLTEXT'){
					$return[$row['Column_name']]['index'] = $row['Key_name'];
				}elseif($row['Index_type'] == 'FULLTEXT'){
					$return[$row['Column_name']]['fulltext'] = $row['Key_name'];
				}
			}
		}
		return $return;
	}

	// ������������� ��������� ��� ����������� � ������� ��
	public function Connect( $host, $user, $pass, $dbname = "" )
	{
		if($this->Connected){
			$this->Disconnect();
		}
		$this->Server = $host;
		$this->User = $user;
		$this->Password = $pass;
		ErrorsOff();
		$this->DbAccess = @mysql_connect($this->Server, $this->User, $this->Password);
		ErrorsOn();
		if($this->DbAccess){
			$this->Connected = true;
			mysql_query("SET NAMES 'cp1251'");
			mysql_query("SET CHARACTER SET �cp1251'");
			//@mysql_query("set character_set_client='cp1251'");
			//@mysql_query("set character_set_results='cp1251'");
			//@mysql_query("set collation_connection='cp1251_general_ci'");
			$this->Version = mysql_get_server_info();
			if($dbname != "" && @mysql_select_db($dbname, $this->DbAccess)){
				$this->SelectDbName = $dbname;
				$this->DbSelected = true;
			}
		}else{
			$this->Error('�� ������� ������������ � �������!');
			$this->MySQLError();
			return false;
		}
		$this->Good();
		return true;
	}

	// ������������� � �������� ���� ������
	public function Disconnect()
	{
		$this->StartQ('Database->Disconnect()');
		if(!$this->Connected){
			$this->Error('��� ����������� � ���� ������.');
			return false;
		}
		if($this->QueryResult){
			@mysql_free_result($this->QueryResult);
		}
		$result = @mysql_close($this->DbAccess);
		if($result){
			$this->Server = '';
			$this->User = '';
			$this->Password = '';
			if($this->DbSelected){
				$this->SelectDbName = '';
				$this->DbSelected = false;
			}
			$this->Connected = false;
			$this->Good();
			return true;
		}
	}

	// ������ ���� ������
	public function CreateDb( $Name, $dropIfExists = false )
	{
		$this->StartQ('Database->CreateDb()');
		$query = "CREATE DATABASE $Name";
		if(version_compare(mysql_get_server_info(), '4.1', '>') ? '1' : '0'){
			$query .= ' DEFAULT CHARACTER SET cp1251 COLLATE cp1251_general_ci';
		}
		;
		$this->MySQLQuery($query);
		$test = $this->MySQLGetErrNo();
		$msg = $this->MySQLGetErrMsg();
		if($dropIfExists && $test == 1007){
			$this->DropDb($Name);
			return $this->CreateDb($Name, false);
		}
		if($test != 0){
			$this->Error('������ ��� �������� ���� ������ '.$test.'.');
			$this->MySQLError($test, $msg);
			return false;
		}else{
			$this->Good();
			return true;
		}
	}

	// ������� ��.
	public function DropDb( $Name )
	{
		$this->StartQ('Database->DropDb()');
		$this->MySQLQuery("DROP DATABASE IF EXISTS $Name");
		if($this->MySQLGetErrNo() != 0){
			$this->Error('�� ������� ������� ���� ������.');
			$this->MySQLError();
			return false;
		}else{
			$this->Good();
			return true;
		}
	}

	//�������� ��
	public function SelectDb( $name )
	{
		$this->StartQ('Database->SelectDb()');
		if($this->DbAccess){
			if(@mysql_select_db($name)){
				$this->SelectDbName = $name;
				$this->DbSelected = true;
				$this->Good();
				return true;
			}else{
				$this->Error('���� ������ "'.$name.'" �� ����������!');
				return false;
			}
		}else{
			$this->Error('��� ���������� � �������� ���� ������!');
			return false;
		}
	}

	// ������ �������
	public function CreateTable( $name, $query, $dropex = false )
	{
		$this->StartQ('Database->CreateTable()');
		$sql = 'CREATE TABLE `'.$this->Prefix().$name.'` ('."\n";
		$ccnt = count($query['cols']);

		$ckeys = 0;
		$cunique = 0;
		$cfulltext = 0;
		$keys = '';
		$unique = '';
		$fulltext = '';

		$primary = '';
		for($i = 0; $i < $ccnt; $i++){
			$sql .= $this->CollToSql($query['cols'][$i]);
			if(isset($query['cols'][$i]['primary'])){
				
			}elseif(isset($query['cols'][$i]['index'])){
				if($ckeys > 0){
					$keys .= ",\n";
				}
				$keys .= '  INDEX `'.$query['cols'][$i]['name'].'` (`'.$query['cols'][$i]['name'].'`)';
				$ckeys++;
			}elseif(isset($query['cols'][$i]['unique'])){
				if($cunique > 0){
					$unique .= ",\n";
				}
				$unique .= '  UNIQUE KEY `'.$query['cols'][$i]['name'].'` (`'.$query['cols'][$i]['name'].'`)';
				$cunique++;
			}elseif(isset($query['cols'][$i]['fulltext'])){
				if($cfulltext > 0){
					$fulltext .= ",\n";
				}
				$fulltext .= '  FULLTEXT KEY `'.$query['cols'][$i]['name'].'` (`'.$query['cols'][$i]['name'].'`)';
				$cfulltext++;
			}
			if($i < $ccnt - 1 || $primary != '' || $keys != ''){
				$sql .= ','."\n";
			}
		}
		if($unique != ''){
			$sql .= $unique."\n";
		}
		if($keys != ''){
			$sql .= $keys."\n";
		}
		if($fulltext != ''){
			$sql .= $fulltext."\n";
		}
		$sql .= "\n".')';
		if(isset($query['type']) && $query['type'] != ''){
			$sql .= ' ENGINE='.$query['type'];
		}else{
			$sql .= ' ENGINE=MYISAM';
		}
		if(isset($query['comment']) && $query['comment'] != ''){
			$sql .= ' COMMENT="'.$query['comment'].'"';
		}

		$sql .= ' DEFAULT CHARSET=cp1251 COLLATE=cp1251_general_ci;'."\n\n";
		// ��������� ������
		if($dropex){
			$this->DropTable($name);
		}
		$result = $this->MySQLQuery($sql);
		if(!$result){
			$this->Error('������ ��� �������� ������� '.$name);
			$this->MySQLError();
			echo '������ ��� �������� �������<br /> '."\n".$sql."<br />\n".$this->ErrorMsg;
			exit;
		}
		$this->Good();
		return true;
	}

	// ������� �������
	public function DropTable( $name )
	{
		$this->StartQ('Database->DropTable()');
		$sql = 'DROP TABLE IF EXISTS '.$this->Prefix().$name;
		if($this->MySQLQuery2($sql, '������ ��� �������� �������.')){
			$this->Good();
			return true;
		}else{
			return false;
		}
	}

	public function RenameTable( $LastName, $NewName )
	{
		$this->StartQ('Database->RenameTable()');
		$sql = 'ALTER TABLE `'.$this->Prefix().$LastName.'` RENAME `'.$this->Prefix().$NewName.'`';
		if($this->MySQLQuery2($sql, '������ ��� �������������� �������.')){
			$this->Good();
			return true;
		}else{
			return false;
		}
	}

	public function SetTableComment( $Name, $Comment )
	{
		$this->StartQ('Database->SetTableComment()');
		$sql = 'ALTER TABLE `'.$this->Prefix().$Name.'` COMMENT=\''.$Comment.'\'';
		if($this->MySQLQuery2($sql, '������ ��� ��������� ����������� �������.')){
			$this->Good();
			return true;
		}else{
			return false;
		}
	}

	public function SetTableType( $Name, $Type )
	{
		$this->StartQ('Database->SetTableType()');
		$sql = 'ALTER TABLE `'.$this->Prefix().$Name.'` ENGINE = \''.$Type.'\';';
		if($this->MySQLQuery2($sql, '������ ��� ��������� ����������� �������.')){
			$this->Good();
			return true;
		}else{
			return false;
		}
	}

	// ������� ������-������ ���� ��������� ������
	public function GetTables()
	{
		$this->StartQ('Database->GetTables()');
		$sql = 'SHOW TABLES';
		if(!$this->MySQLQuery2($sql, '������. ������ �� ��������.')){
			return false;
		}
		$this->Good();
		return $this->SetResult($this->QueryResult);
	}

	// ���������� ���������� � ���� �������� � ���� ������
	public function GetTableInfo( $Name = '' )
	{
		$this->StartQ('Database->GetAllTablesInfo()');

		$sql = 'SHOW TABLE STATUS'.($Name != '' ? ' LIKE \''.$this->Prefix().$Name.'\'' : '');
		if(!$this->MySQLQuery2($sql, '������. ������ �� ��������.')){
			return array();
		}
		$infs = $this->SetResult($this->QueryResult);

		$tables = array();
		foreach($infs as $i){
			$info = array();
			$info['name'] = ($this->Prefix != '' ? substr($i['Name'], strlen($this->Prefix) + 1) : $i['Name']);
			$info['type'] = $i['Engine'];
			$info['comment'] = $i['Comment'];
			$info['num_rows'] = $i['Rows'];
			$info['counter'] = $i['Auto_increment'];
			$info['size'] = $i['Data_length'] + $i['Index_length'];
			$tables[] = $info;
		}
		reset($tables);
		$this->QueryResult = $tables;
		$this->Good();
		return $tables;
	}

	// �������� �������� �������
	public function GetTableColumns( $name )
	{
		$this->StartQ('Database->GetTableColumns()');
		$indexes = $this->GetIndexes($name);

		$this->MySQLQuery('SHOW FULL COLUMNS FROM `'.$this->Prefix().$name.'`');
		$result = $this->SetResult($this->QueryResult);
		if($result){
			foreach($result as $row){
				$type = $row['Type'];
				preg_match('~^([^( ]+)(?:\\((.+)\\))?( unsigned)?( zerofill)?$~', $row['Type'], $match);
				if(preg_match('@^(set|enum)\((.+)\)$@i', $type, $tmp)){
					$type = $tmp[1];
					$length = substr(preg_replace('@([^,])\'\'@', '\\1\\\'', ',' . $tmp[2]), 1);
				}else{
					$type = preg_replace('@BINARY([^\(])@i', '', $type);
					$type = preg_replace('@ZEROFILL@i', '', $type);
					$type = preg_replace('@UNSIGNED@i', '', $type);

					if(strpos($type, '(')){
						$length = chop(substr($type, (strpos($type, '(') + 1), (strpos($type, ')') - strpos($type, '(') - 1)));
						$type = chop(substr($type, 0, strpos($type, '(')));
					} else {
						$length = '';
					}
				}
				if(preg_match('@^(set|enum)$@i', $type)){
					$binary  = 0;
					$unsigned = 0;
					$zerofill  = 0;
				}else{
					if(!preg_match('@BINARY[\(]@i', $row['Type'])) {
						$binary = stristr($row['Type'], 'binary');
					} else {
						$binary = false;
					}
					$unsigned = stristr($row['Type'], 'unsigned');
					$zerofill = stristr($row['Type'], 'zerofill');
				}

				$Attribute = '';
				if($binary){ $Attribute = 'binary'; }
				if($unsigned){ $Attribute = 'unsigned'; }
				if($zerofill){ $Attribute = 'unsigned zerofill'; }

				$def = ($row["Default"] <> '' ? $row["Default"] : ($row["Null"] == "YES" ? 'NULL' : ''));
				
				$col = array();
				$col = array('name' => $row["Field"], 'type' => $match[1]);
				if(!empty($length)){
					$col['length'] = $length;
				}
				if(($row["Extra"] == "auto_increment")){
					$col['auto_increment'] = true;
				}
				if($def != ''){
					$col['default'] = $def;
				}
				if(!empty($Attribute)){
					$col['attributes'] = $Attribute;
				}
				if($row["Null"] != "YES"){
					$col['notnull'] = true;
				}

				// �������
				if(isset($indexes[$row["Field"]]['primary'])){
					$col['primary'] = true;
				}elseif(isset($indexes[$row["Field"]]['index'])){
					$col['index'] = true;
				}elseif(isset($indexes[$row["Field"]]['unique'])){
					$col['unique'] = true;
				}elseif(isset($indexes[$row["Field"]]['fulltext'])){
					$col['fulltext'] = true;
				}
				$return[] = $col;
			}
		}else{
			$this->Error('������. ������ �� ��������.');
		}
		$this->Good();
		return $return;
	}

	protected function DropIndex( $name, $IndexName )
	{
		$this->MySQLQuery2('ALTER TABLE `'.$this->Prefix().$name.'` DROP INDEX `'.$IndexName.'`', '������ ��� �������� �������');
	}

	protected function UpdateIndexes( $name, $OldColl, $NewColl )
	{
		$indexes = $this->GetIndexes($name);
		$table = '`'.$this->Prefix().$name.'`';
		if(isset($OldColl['primary']) && !isset($NewColl['primary'])){
			$this->MySQLQuery2('ALTER TABLE '.$table.' DROP PRIMARY KEY', '������, ������ ������� ��������� ����');
		}elseif(isset($OldColl['unique']) && !isset($NewColl['unique'])){
			$this->DropIndex($name, $indexes[$OldColl['name']]['unique']);
		}elseif(isset($OldColl['index']) && !isset($NewColl['index'])){
			$this->DropIndex($name, $indexes[$OldColl['name']]['index']);
		}elseif(isset($OldColl['fulltext']) && !isset($NewColl['fulltext'])){
			$this->DropIndex($name, $indexes[$OldColl['name']]['fulltext']);
		}

		if(isset($NewColl['primary']) && !isset($OldColl['primary'])){
			$this->MySQLQuery2('ALTER TABLE '.$table.' ADD PRIMARY KEY ( `'.$NewColl['name'].'` )', '������, ���������� ������� ��������� ����');
		}elseif(isset($NewColl['unique']) && !isset($OldColl['unique'])){
			$this->MySQLQuery2('ALTER TABLE '.$table.' ADD UNIQUE `'.$NewColl['name'].'` (`'.$NewColl['name'].'`)', '������, �� ������� ������� ���������� ������');
		}elseif(isset($NewColl['index']) && !isset($OldColl['index'])){
			$this->MySQLQuery2('ALTER TABLE '.$table.' ADD INDEX `'.$NewColl['name'].'` (`'.$NewColl['name'].'`)', '������, ���������� ������� ����');
		}elseif(isset($NewColl['fulltext']) && !isset($OldColl['fulltext'])){
			$this->MySQLQuery2('ALTER TABLE '.$table.' ADD FULLTEXT `'.$NewColl['name'].'` (`'.$NewColl['name'].'`)', '������, ���������� ������� �������������� ������');
		}
	}

	// ���������� ���������� � ����� ������� �������
	public function GetColl( $name, $index )
	{
		$colls = $this->GetTableColumns($name);
		if(isset($colls[$index])){
			return $colls[$index];
		}else{
			return false;
		}
	}

	public function InsertColl( $name, $coll, $collindex )
	// ���� $collindex=-1 �� ������� ����� ��������� � �����
	// � $coll ������ �������� ������ ��� � �������� �������
	{
		$coll_sql = $this->CollToSql($coll);
		$after = '';
		if($collindex != -1){
			$columns = $this->GetTableColumns($name);
			if(isset($columns[$collindex])){
				$after = ' AFTER `'.$columns[$collindex]['name'].'`';
			}
		}
		$sql = 'ALTER TABLE `'.$this->Prefix().$name.'` ADD COLUMN '.$coll_sql.$after.';';
		if($this->MySQLQuery($sql)){
			$this->UpdateIndexes($name, array(), $coll);
			return true;
		}else{
			$this->Error('������. ������ �� ��������.');
			return false;
		}
	}

	public function DeleteColl( $name, $index )
	{
		$columns = $this->GetTableColumns($name);
		if(isset($columns[$index])){
			$column_name = $columns[$index]['name'];
			$sql = 'ALTER TABLE `'.$this->Prefix().$name.'` DROP COLUMN `'.$column_name.'`;';
			if($this->MySQLQuery($sql)){
				return true;
			}else{
				$this->Error('������. ������ �� ��������.');
				return false;
			}
		}else{
			return false;
		}
	}

	public function EditColl( $name, $index, $coll )
	{
		$columns = $this->GetTableColumns($name);
		if(isset($columns[$index])){
			$this->UpdateIndexes($name, $columns[$index], $coll);
			$sql = $this->CollToSql($coll, $columns[$index]['name']);
			$sql = 'ALTER TABLE `'.$this->Prefix().$name."` CHANGE $sql;";
			if($this->MySQLQuery($sql)){
				return true;
			}else{
				$this->Error('������. ������ �� ��������.');
				return false;
			}
			
		}else{
			return false;
		}
	}

	public function RenameColl( $name, $index, $newCollName )
	{
		$columns = $this->GetTableColumns($name);
		if(isset($columns[$index])){
			$column_name = $columns[$index]['name'];
			$type = $columns[$index]['type'];
			$sql = 'ALTER TABLE `'.$this->Prefix().$name."` CHANGE `$column_name` `$newCollName` $type;";
			if($this->MySQLQuery($sql)){
				return true;
			}else{
				$this->Error('������. ������ �� ��������.');
				return false;
			}
		}else{
			return false;
		}
	}

	// ��������� ������ � �������
	public function Insert( $name, $values, $cols = '' )
	{
		$this->StartQ('Database->Insert()');
		$sql = 'INSERT INTO '.$this->Prefix().$name.($cols != '' ?' ('.implode(',',$cols).')' : '').' VALUES ('.$values.')';
		if($this->MySQLQuery($sql)){
			$this->Good();
			return true;
		}else{
			$this->Error('������. ������ �� ��������.');
			$this->MySQLError();
			return false;
		}
	}

	private function Values2Set( $values, $columns )
	{
		$set = '';
		$values = str_replace("\'", "&#39;", $values);
		$values = str_replace('\"', "&#34;", $values);
		$values = trim($values);
		$maxlength = count($columns);
		for($i = 0; $i < $maxlength; $i++){
			$pos = strpos($values, "'");
			if($pos === false){
				break;
			}
			$values = substr($values, $pos + 1);
			$pos = strpos($values, "'");
			$val = substr($values, 0, $pos);
			$values = substr($values, $pos + 1);
			$val = str_replace('&#34;', '\"', $val);
			$val = str_replace("&#39;", "\'", $val);
			if($columns[$i]['Extra'] == ''){
				$set .= '`'.$columns[$i]['Field'].'`=\''.$val.'\', ';
			}
		}
		$set = substr($set, 0, -2);
		return $set;
	}

	// ��������� ������ � �������
	public function Update( $name, $set, $where = '', $setisvalues = false, $cols = '' )
	{
		$this->StartQ('Database->Update()');
		if($setisvalues){
			$cols1 = $this->SetResult($this->MySQLQuery('SHOW COLUMNS FROM '.$this->Prefix().$name));
			if($cols != ''){
				$cols2 = array();
				foreach($cols1 as $value){
					$cols2[$value['Field']] = $value;
				}
				$cols3 = array();
				foreach($cols as $coll){
					$cols3[] = $cols2[$coll];
				}
			}else{
				$cols3 = &$cols1;
			}
			$set = $this->Values2Set($set, $cols3);
		}
		$sql = 'UPDATE '.$this->Prefix().$name.' SET '.$set.' WHERE '.$where;
		if($this->MySQLQuery($sql)){
			$this->Good();
			return true;
		}else{
			$this->Error('������. ������ �� ��������.');
			$this->MySQLError();
			return false;
		}
	}

	// ������� ������ �� �������
	public function Delete( $name, $where = '' )
	{
		$this->StartQ('Database->Delete()');
		$sql = 'DELETE FROM '.$this->Prefix().$name.($where != '' ? ' WHERE '.$where : '');
		if($this->MySQLQuery($sql)){
			$this->Good();
			return true;
		}else{
			$this->Error('������. ������ �� ��������.');
			$this->MySQLError();
			return false;
		}
	}

	// �������� ������ �� �������
	public function Select( $name, $where = '' )
	{
		$this->StartQ('Database->Select()');
		$sql = 'SELECT * FROM '.$this->Prefix().$name.($where != '' ? ' WHERE '.$where : '');
		if($this->MySQLQuery($sql)){
			$this->Good();
			return $this->SetResult($this->QueryResult);
		}else{
			$this->Error('������. ������ �� ��������.');
			$this->MySQLError();
			return false;
		}
	}

	// ���������� ���������� ������� � ����������
	public function NumRows()
	{
		return mysql_num_rows($this->MySQLQueryResult);
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
		mysql_free_result($this->MySQLQueryResult);
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
		return mysql_insert_id($this->DbAccess);
	}

	// ���������� SQL ����-������� ��� mysql_query
	public function EscapeString( $UnescapedString )
	{
		return mysql_real_escape_string($UnescapedString);
	}

}
?>