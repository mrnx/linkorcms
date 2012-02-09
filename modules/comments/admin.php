<?php

// Модуль глобальной модерации комментариев

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('Модерация комментариев');

if(isset($_GET['a'])){
	$action = $_GET['a'];
}else{
	$action = 'main';
}
switch($action){
	case 'main': AdminCommentsMain();
		break;
	case 'edit': AdminCommentsEdit();
		break;
	case 'save': AdminCommentsSave();
		break;
	case 'delete': AdminDownloadsDeleteComment();
		break;
}


function AdminConfigMarkPosts( &$posts, $table ){
	foreach($posts as $id=>$post){
		$posts[$id]['_table'] = $table;
	}
}

function AdminCommentsMain(){
	System::admin()->AddCenterBox('Глобальная модерация комментариев');

	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 10, int);
	}else{
		$page = 1;
	}

	System::admin()->AddJS('
		UpdateSelectComment = function(){
			$(".comment_check").each(function(){
				$("#comment"+$(this).val()).removeClass("commtable_selected");
			});
			$(".comment_check:checked").each(function(){
				$("#comment"+$(this).val()).addClass("commtable_selected");
			});
		};
		SelectAllComments = function(){
			$(".comment_check").each(function(){
				$(this).attr("checked", true);
			});
			UpdateSelectComment();
		};
		DeleteComments = function(){
			var del = "";
			$(".comment_check:checked").each(function(){
				del += "#"+$(this).val();
			});
			Admin.LoadPagePost("'.ADMIN_FILE.'?exe=comments&a=delete&page='.$page.'", {delcomments: del}, "Удаление...");
		};
	');

	$commentsOnPage = 50;

	// Выбираем комментарии из всех таблиц
	$where = '';
	$posts = array();
	$comments_tables = System::database()->Select('comments');
	foreach($comments_tables as $table){
		$temp_posts = System::database()->Select($table['table'], $where);
		AdminConfigMarkPosts($temp_posts, $table['table']);
		$posts = array_merge($posts, $temp_posts);
	}


	// Сортируем комментарии по дате(Новые сверху)
	SortArray($posts, 'post_date', true);

	// Добавляем постраничную навигацию
	if(count($posts) > $commentsOnPage){
		$navigator = new Navigation($page);
		$navigator->GenNavigationMenu($posts, $commentsOnPage, ADMIN_FILE.'?exe=comments');
		AddNavigation();
		$nav = true;
	}else{
		$nav = false;
		AddText('<br />');
	}

	// Шапка
	if(count($posts) == 0){
		System::admin()->Highlight('На сайте нет комментариев.');
		return;
	}else{
		$text = '';
	}

	$text .= '<table cellspacing="0" cellpadding="0" width="90%" align="center" class="commtable_header">
	<tr>
	<th style="width: 160px;">Автор</th>
	<th style="width: 260px;">E-mail</th>
	<th style="width: 260px;">Сайт</th>
	<th style="width: 260px;">Добавлено</th>
	<th style="width: 70px;">IP</th>
	<th>Функции</th>
	</tr></table>';

	// Выводим комментарии
	foreach($posts as $post){
		$post_id = SafeDB($post['id'], 11, int);
		$object_id = SafeDB($post['object_id'], 11, int);

		$user_id = SafeDB($post['user_id'], 11, int);
		$user_name = SafeDB($post['user_name'], 255, str);
		$user_homepage = SafeDB($post['user_homepage'], 255, str);
		$user_email = SafeDB($post['user_email'], 255, str);
		$user_ip = SafeDB($post['user_ip'], 19, str);

		$post_date = TimeRender($post['post_date']);
		$post_message = SafeDB($post['post_message'], 0, str);

		$edit = ADMIN_FILE.'?exe=comments&a=edit&id='.$post_id.'&table='.$post['_table'].'&page='.$page;

		if($user_id != 0){
			$userinfo = GetUserInfo($user_id);

			$user_name = $userinfo['name'];
			$user_homepage = $userinfo['url'];
			$user_email = PrintEmail($userinfo['email']);

			if($userinfo['online']){
				$online = '<b>Сейчас на сайте.</b>';
			}else{
				$online = '';
			}
			$avatar = '<img src="'.$userinfo['avatar_file'].'" />';
			$rank_image = '<img src="'.$userinfo['rank_image'].'" />';
			$rank_name = $userinfo['rank_name'];

			$regdate = 'Зарегистрирован: '.TimeRender($userinfo['regdate'], false);
			$ruser = true;
		}else{
			$user_name = $user_name;
			$user_homepage = $user_homepage;
			$user_email = PrintEmail($user_email);

			$online = '';
			$avatar = '<img src="'.GetPersonalAvatar(0).'" />';
			$rank_image = '';
			$rank_name = '';

			$regdate = '';
			$ruser = false;
		}

		if($user_homepage != ''){
			$user_homepage = '<a href="http://'.$user_homepage.'" target="_blank">'.$user_homepage.'</a>';
		}else{
			$user_homepage = '&nbsp;';
		}
		if($ruser){
			$user_name = '<a href="'.Ufu("index.php?name=user&op=userinfo&user=$user_id", 'user/{user}/info/').'" target="_blank">'.$user_name.'</a>';
		}
		$text .= '
		<table cellspacing="0" cellpadding="0" width="90%" align="center" class="commtable" id="comment'.$post_id.'--'.$post['_table'].'--'.$object_id.'">
			<tr>
				<th style="width: 160px;"><b>'.$user_name.'</b></th>
				<th style="width: 260px;">'.$user_email.'</th>
				<th style="width: 260px;">'.$user_homepage.'</th>
				<th style="width: 260px;">'.$post_date.'</th>
				<th style="width: 70px;">'.$user_ip.'</th>
				<th>'.SpeedButton('Редактировать комментарий', $edit, 'images/admin/edit.png').'</th>
				<th>'.System::admin()->Check('delcomments[]', $post_id.'--'.$post['_table'].'--'.$object_id, false, 'class="comment_check" onchange="UpdateSelectComment();"').'</th>
			</tr>
			<tr>
				<td valign="top" width="140">'.$avatar.$rank_image.'<br>'.$rank_name.'</td>
				<td colspan="6" class="commtable_text">'.$post_message.'</td>
			</tr>
		</table>';
	}

	// Подвал
	AddText($text);
	if($nav){
		AddNavigation();
	}
	$text = '';
	if(count($posts) > 0){
		$text .= '<div style="text-align: right;">'.System::admin()->SpeedConfirmJs('Выделить все', 'SelectAllComments();', '', '', true).'&nbsp;'
			.System::admin()->SpeedConfirmJs('Удалить выделенные', 'DeleteComments();', 'images/admin/delete.png', 'Удалить выделенные комментарии?', true).'</div>';
	}
	AddText($text);
}

// Редактирование комментария
function AdminCommentsEdit(){
	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 10, int);
	}else{
		$page = 1;
	}
	$id = SafeEnv($_GET['id'], 11, int);
	$table = SafeEnv($_GET['table'], 255, str);

	System::database()->Select($table, "`id`='$id'");
	$post = System::database()->FetchRow();

	$user_name = SafeDB($post['user_name'], 255, str);
	$user_homepage = SafeDB($post['user_homepage'], 255, str);
	$user_email = SafeDB($post['user_email'], 255, str);
	$user_hideemail = $post['user_hideemail'] == 1 ? true : false;
	$user_ip = SafeDB($post['user_ip'], 19, str);

	$post_message = SafeDB($post['post_message'], 0, str);

	if($post['user_id'] == '0'){
		FormRow('Имя', System::admin()->Edit('user_name', $user_name, false, 'style="width:400px;"'));
		FormRow('E-mail', System::admin()->Edit('user_email', $user_email, false, 'style="width:400px;"'));
		FormRow('Скрыть e-mail', System::admin()->Check('user_hideemail', '1', $user_hideemail));
		FormRow('Сайт', System::admin()->Edit('user_homepage', $user_homepage, false, 'style="width:400px;"'));
	}
	FormRow('Текст', System::admin()->TextArea('post_message', $post_message, 'style="width:400px;height:200px;"'));
	$action = ADMIN_FILE.'?exe=comments&a=save&id='.$id.'&table='.$table.'&page='.$page;
	AddCenterBox('Редактирование комментария');
	AddForm(
		'<form action="'.$action.'" method="post">',
		System::admin()->Button('Отмена', 'onclick="history.go(-1)"').System::admin()->Submit('Сохранить')
	);

}

// Сохранение комментария
function AdminCommentsSave(){
	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 10, int);
	}else{
		$page = 1;
	}
	$id = SafeEnv($_GET['id'], 11, int);
	$table = SafeEnv($_GET['table'], 255, str);

	$post_message = SafeEnv($_POST['post_message'], 0, str, false, false);

	$set = "`post_message`='$post_message'";

	if(isset($_POST['user_name'])){
		$user_name = SafeEnv($_POST['user_name'], 255, str);
		$user_email = SafeEnv($_POST['user_email'], 255, str);
		if(isset($_POST['user_hideemail'])){
			$user_hideemail = '1';
		}else{
			$user_hideemail = '0';
		}
		$user_homepage = SafeEnv($_POST['user_homepage'], 255, str);

		$set .= ",`user_name`='$user_name',`user_email`='$user_email',`user_hideemail`='$user_hideemail',`user_homepage`='$user_homepage'";
	}
	System::database()->Update($table, $set, "`id`='$id'");
	GO(ADMIN_FILE.'?exe=comments&page='.$page);
}

function AdminDownloadsDeleteComment(){
	if(!isset($_POST['delcomments'])){
		GO(ADMIN_FILE.'?exe=comments');
	}
	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 10, int);
	}else{
		$page = 1;
	}

	$com_tables = System::database()->Select('comments');
	foreach($com_tables as $table){
		$comments_tables[$table['table']] = $table;
	}

	$posts_tables = array();
	$del_posts = explode('#', $_POST['delcomments']);
	foreach($del_posts as $post){
		if($post != ''){
			$a = explode('--', $post);
			$posts_tables[$a[1]][] = array($a[0],$a[2]);
		}
    }

	// Удаляем комментарии для каждой таблицы отдельно
    foreach($posts_tables as $post_table=>$posts_id){
		$where = '';
		foreach($posts_id as $p){
			$post_id = SafeEnv($p[0], 11, int);
			$obj_id = SafeEnv($p[1], 11, int);
			$where .= "`id`='$post_id' or ";
			$t = $comments_tables[$post_table];
			CalcCounter($t['objects_table'], "`{$t['id_coll']}`='$obj_id'", $t['counter_coll'], -1);
		}
		$where = substr($where, 0, strlen($where) - 4);
		System::database()->Delete($post_table, $where);
    }
	GO(ADMIN_FILE.'?exe=comments&page='.$page);
}
