<?php

/**
 * Регистрирует таблицу комментариев.
 * @param $Name
 * @param $ObjTable
 * @param $ObjIdColl
 * @param $ObjCounterColl
 * @param $ObjCounterCollIndex
 */
function RegisterCommentTable( $Name, $ObjTable, $ObjIdColl, $ObjCounterColl, $ObjCounterCollIndex ){
	global $db;
	$name = SafeEnv($Name, 255, str);
	$db->Insert('comments', Values('', $Name, $ObjTable, $ObjIdColl, $ObjCounterColl, $ObjCounterCollIndex));
}

/**
 * Освобождает таблицу комментариев
 * @param $Name
 * @param bool $Delete
 * @return void
 */
function UnRegisterCommentTable( $Name, $Delete=false ){
	global $db;
	$Name = SafeEnv($Name, 255, str);
	$db->Delete('comments', "`table`='$Name'");
	if($Delete){
		$db->DropTable($Name);
	}
}

/**
 * Функция обновляет данные пользователя во всех комментариях.
 * При передаче параметров необходимо фильтровать их функцией SafeEnv.
 * @param $UserId
 * @param $NewUserId
 * @param $Name
 * @param $Email
 * @param $HideEmail
 * @param $HomePage
 * @param null $UserIp
 */
function UpdateUserComments( $UserId, $NewUserId, $Name, $Email, $HideEmail, $HomePage, $UserIp=null ){
	global $db;
	$set = "user_id='$NewUserId',user_name='$Name',user_homepage='$HomePage',user_email='$Email',"
	."user_hideemail='$HideEmail'".($UserIp<>null?",user_ip='$UserIp'":'');
	$where = "`user_id`='$UserId'";
	$ctables = $db->Select('comments', '');
	foreach($ctables as $table){
		$db->Update($table['table'], $set, $where);
	}
}

/**
 * Удаляет все коментарии пользователя.
 * @param int $UserId
 * @return void
 */
function DeleteAllUserComments( $UserId ){
	global $db;
	$UserId = SafeEnv($UserId, 11, int);
	$where = "`user_id`='$UserId'";
	$ctables = $db->Select('comments','');
	foreach($ctables as $table){
		$comms = $db->Select(SafeEnv($table['table'], 255, str), $where);
		$comments = array();
		$objects = array();
		//Отсортировываем id комментарий по объектам
		foreach($comms as $com){
			$comments[$com['object_id']] = SafeEnv($com['id'], 11, int);
			$objects[] = SafeEnv($com['object_id'], 11, int);
		}
		//теперь нужно обойти все объекты уменьшая счетчик
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

/**
 * Изменяет поле счетчика в БД.
 * @param $ObjTable
 * @param $WhereObj
 * @param $ObjCounterColl
 * @param $CalcVal
 */
function CalcCounter( $ObjTable, $WhereObj, $ObjCounterColl, $CalcVal ){
	global $db;
	$ObjCounterColl = SafeEnv($ObjCounterColl, 255, str);
	$db->Select($ObjTable, $WhereObj);
	if($db->NumRows() > 0){
		$counterVal = $db->QueryResult[0][$ObjCounterColl] + $CalcVal;
		$db->Update($ObjTable, "$ObjCounterColl='$counterVal'", $WhereObj);
	}
}
