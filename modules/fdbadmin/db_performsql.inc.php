<?php

if(System::database()->Name != 'MySQL'){
	System::admin()->HighlightError('Только базы данных с поддержкой SQL.');
	return;
}

if(isset($_POST['code'])){
	$sql = $_POST['code'];
}else{
	echo "Code is Empty";
	exit();
}

$result = '';
$sql = explode(";\n", $sql);
foreach($sql as $query){
	if(trim($query) == '') continue;
	$qr = System::database()->MySQLQueryResult($query);
	if($qr === false){
		echo System::database()->MySQLGetErrNo().': '.System::database()->MySQLGetErrMsg()."\n";
	}elseif(count($qr) > 0){
		print_r($qr);
		echo "\n";
	}else{
		echo "Запрос успешно выполнен.";
	}
}
exit();
