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
			$id_coll = SafeEnv($table['id_coll'], 255, str);
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

function CommentsAddPost( $ObjectId, $CommentsTable, $ObjectsTable, $CounterField, $AlloyField, $BackUrl, $BackUrlUfu, $PageParam = 'page' ){
	$parent_id = explode('_', $_POST['parent_id'], 2);
	if($parent_id[1] == 0){
		if(System::config('comments/decreasesort')){
			$sp = true;
			$_GET[$PageParam] = 0;
		}else{
			$sp = false;
		}
	}else{
		$sp = true;
	}
	$back_url = GetSiteUrl().Ufu($BackUrl.($sp ? "&$PageParam=".$_GET[$PageParam] : ''), $BackUrlUfu.($sp ? $PageParam.'{'.$PageParam.'}/' : ''));
	// -----------------------------------------------------
	System::database()->Select($ObjectsTable, "`id`='$ObjectId'");
	$obj = System::database()->FetchRow();
	$alloy_comments = $obj[$AlloyField] == '1';
	$posts = new Posts($CommentsTable, $alloy_comments);
	if($posts->SavePost($ObjectId, false)){
		$post_id = System::database()->GetLastId();
		$counter = $obj[$CounterField] + 1;
		System::database()->Update($ObjectsTable, "`$CounterField`='$counter'", "`id`='$ObjectId'");
		$parent_id = explode('_', $_POST['parent_id'], 2);
		$parent_id = SafeDB($parent_id[1], 11, int);
		$post_anchor = ($parent_id != 0 ? "#post_$parent_id" : '#post_'.$post_id);
		GO($back_url.$post_anchor);
	}else{
		System::site()->AddTextBox('Ошибка', $posts->PrintErrors());
	}
}

function CommentsEditPost( $CommentsTable, $SaveUrl ){
	System::site()->AddTemplatedBox('','edit_comment.html');
	$posts = new Posts($CommentsTable);
	$posts->PostFormAction = $SaveUrl;
	$posts->RenderForm(true, 'post_form');
}

function CommentsEditPostSave( $ObjectId, $CommentsTable ){
	$posts = new Posts($CommentsTable);
	if($posts->SavePost($ObjectId, true)){
		$post_anchor = "#post_".SafeDB($_GET['post_id'], 11, int);
		GoRefererUrl($_REQUEST['back'], $post_anchor);
	}else{
		$site->AddTextBox('Ошибка', $posts->PrintErrors());
		return false;
	}
}

function CommentsDeletePost( $ObjectId, $CommentsTable, $ObjectsTable, $CounterField, $DeleteUrl, $Anchor = '#comments' ){
	$posts = new Posts($CommentsTable);
	$posts->DeletePageUrl = $DeleteUrl;
	$deleted_posts_count = $posts->DeletePost();
	if($deleted_posts_count > 0){
		System::database()->Select($ObjectsTable, "`id`='$ObjectId'");
		$obj = System::database()->FetchRow();
		$counter = $obj[$CounterField] - $deleted_posts_count;
		System::database()->Update($ObjectsTable, "`$CounterField`='$counter'", "`id`='$ObjectId'");
		GoRefererUrl($_REQUEST['back'], $Anchor);
	}
}
