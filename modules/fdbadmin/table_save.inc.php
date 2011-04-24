<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$table = SafeEnv($_POST['tablename'], 250, str);
$num_cols = SafeEnv($_POST['cols'], 11, int);
$cols = array();
$col = array();

for($i = 0; $i < $num_cols; $i++){
	$col['name'] = SafeEnv($_POST['name'.$i], 250, str);
	$col['type'] = SafeEnv($_POST['type'.$i], 250, str);
	if($_POST['length'.$i] != ''){
		$col['length'] = SafeEnv($_POST['length'.$i], 11, int);
	}
	if($_POST['default'.$i] != ''){
		$col['default'] = $_POST['default'.$i];
	}else{
		switch($col['type']){
			case "varchar":
			case "char":
				$col['default'] = '';
				break;
		}
	}
	if($_POST['attributes'.$i] != 'none'){
		$col['attributes'] = $_POST['attributes'.$i];
	}
	if(!isset($_POST['notnull'.$i])){
		$col['notnull'] = true;
	}
	if(isset($_POST['auto_increment'.$i])){
		$col['auto_increment'] = true;
	}
	switch($_POST['params'.$i]){
		case "primary":
			$col['primary'] = true;
			break;
		case "index":
			$col['index'] = true;
			break;
		case "unique":
			$col['unique'] = true;
			break;
		case "fulltext":
			$col['fulltext'] = true;
			break;
	}
	$cols[] = $col;
	$col = array();
}

if($_POST['tabletype'] != 'default'){
	$query['type'] = $_POST['tabletype'];
}
$query['comment'] = $_POST['comment'];
$query['cols'] = $cols;

if($action == 'savetable'){
	$db->CreateTable($table, $query);
	GO($config['admin_file'].'?exe=fdbadmin');
}elseif($action == 'editsavetable'){
	$info = $db->GetTableInfo($table);
	$info = $info[0];

	$db->SetTableComment($table, $query['comment']);

	if(isset($query['type'])
			&& strtoupper($info['type']) != strtoupper($query['type'])){
		$db->SetTableType( $table, $query['type'] );
	}

	foreach($query['cols'] as $i=>$col){
		$db->EditColl( $table, $i, $col );
	}
	GO($config['admin_file'].'?exe=fdbadmin&a=structure&name='.SafeEnv($_POST['tablename'], 250, str));
}

GO($config['admin_file'].'?exe=fdbadmin');

?>