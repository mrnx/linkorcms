<?php

#������������ ������� ������������
function RegisterCommentTable($name, $objTable, $ObjIdColl, $objCounterColl, $objCounterCollIndex){
	global $db;
	$name = SafeEnv($name, 64, str);
	$db->Insert('comments', Values('', $name, $objTable, $ObjIdColl, $objCounterColl, $objCounterCollIndex));
}

#����������� ������� ������������
function UnRegisterCommentTable($name, $delete=false){
	global $db;
	$name = SafeEnv($name, 64, str);
	$db->Delete('comments', "`table`='$name'");
	if($delete){
		$db->DropTable($name);
	}
}

/**
 * ������� ��������� ������ ������������ �� ���� ������������.
 * ��� �������� ���������� ����������� �� �������� SafeEnv.
 * @global <type> $db
 * @param <type> $uid
 * @param <type> $newUid
 * @param <type> $Name
 * @param <type> $email
 * @param <type> $hEmail
 * @param <type> $homePage
 * @param <type> $uIP
 */
function UpdateUserComments($uid, $newUid, $Name, $email, $hEmail, $homePage, $uIP=null){
	global $db;
	$set = "user_id='$newUid',user_name='$Name',user_homepage='$homePage',user_email='$email',"
	."user_hideemail='$hEmail'".($uIP<>null?",user_ip='$uIP'":'');
	$where = "`user_id`='$uid'";
	$ctables = $db->Select('comments', '');
	foreach($ctables as $table){
		$db->Update($table['table'], $set, $where);
	}
}

/**
 * ������� ��� ���������� ������������
 * @param  $uid
 * @return void
 */
function DeleteAllUserComments( $uid ){
	global $db;
	$uid = SafeEnv($uid, 11, int);
	$where = "`user_id`='$uid'";
	$ctables = $db->Select('comments','');
	foreach($ctables as $table){
		$comms = $db->Select(SafeEnv($table['table'], 255, str), $where);
		$comments = array();
		$objects = array();
		//��������������� id ����������� �� ��������
		foreach($comms as $com){
			$comments[$com['object_id']] = SafeEnv($com['id'], 11, int);
			$objects[] = SafeEnv($com['object_id'], 11, int);
		}
		//������ ����� ������ ��� ������� �������� �������
		foreach($objects as $obj){
			$id_coll = SafeEnv($table['id_coll'], 11, int);
			CalcCounter(
				$table['objects_table'],
				"`$id_coll`='{$obj}'",
				$table['counter_coll'],
				count($comments[$obj]) * -1
			);
		}
		$db->Delete(SafeEnv($table['table'], 255, str), $where);
	}
}

// �������� ���� �������� � �������
function CalcCounter($objTable, $whereObj, $objCounterColl, $calcVal){
	global $db;
	$objCounterColl = SafeEnv($objCounterColl, 255, str);
	$db->Select($objTable, $whereObj);
	if($db->NumRows() > 0){
		$counterVal = $db->QueryResult[0][$objCounterColl] + $calcVal;
		$db->Update($objTable, "$objCounterColl='$counterVal'", $whereObj);
	}
}
