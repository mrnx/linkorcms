<?php

// Модуль глобальной модерации комментариев

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('Модерация комментариев');

function AdminConfigMarkPosts( &$posts, $table )
{
	foreach($posts as $id=>$post){
		$posts[$id]['_table'] = $table;
	}
}

function AdminCommentsMain()
{
	global $config, $db, $site;
	AddCenterBox('Глобальная модерация комментариев');

	$commentsOnPage = 50;
	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 10, int);
	}else{
		$page = 1;
	}

	// Выбираем комментарии из всех таблиц
	$where = '';
	$posts = array();
	$comments_tables = $db->Select('comments');
	foreach($comments_tables as $table){
		$temp_posts = $db->Select($table['table'], $where);
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
		$text = '<center>- На сайте нет комментариев -</center><br />';
	}else{
		$text = '<form action="'.ADMIN_FILE.'?exe=comments&a=delete&page='.$page.'" method="post">';
	}


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
			$user_name = '<a href="'.Ufu("index.php?name=user&op=userinfo&user=$user_id", 'user/{user}/info/').'">'.$user_name.'</a>';// it is very smart! :)
		}
		$text .= '
		<table cellspacing="0" cellpadding="0" width="90%" align="center" class="commtable">
			<tr>
				<th class="commth"><b>Автор:</b></th>
				<th class="commth">E-mail:</th>
				<th class="commth">Сайт:</th>
				<th class="commth">Добавлено:</th>
				<th class="commth">ip:</th>
				<th colspan="2" class="commth">Функции</th>
			</tr>
			<tr>
				<th class="commth"><b>'.$user_name.'</b></th>
				<th class="commth">'.$user_email.'</th>
				<th class="commth">'.$user_homepage.'</th>
				<th class="commth">'.$post_date.'</th>
				<th class="commth">'.$user_ip.'</th>
				<th class="commth">'
					.SpeedButton('Редактировать', $edit, 'images/admin/edit.png')
				.'</th>
				<th class="commth">'.$site->Check('delcomments[]', $post_id.'::'.$post['_table'].'::'.$object_id).'</th>
			</tr>
			<tr>
				<td valign="top" class="commtd" width="140">'
					.$avatar.$rank_image.'<br />'
					.$rank_name
				.'</td>
				<td colspan="6" class="commtext" valign="top">'.$post_message.'</td>
			</tr>
		</table>';
	}
	// ---

	// Подвал
	AddText($text);
	if($nav){
		AddNavigation();
	}
	$text = '';
	if(count($posts) > 0){
		$text .= '<p><center>'.$site->Submit('Удалить выделенные').'</senter></p><br /><br />';
	}
	$text .= '</form>';
	AddText($text);
}

// Редактирование комментария
function AdminCommentsEdit()
{
	global $config, $db, $site;
	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 10, int);
	}else{
		$page = 1;
	}
	$id = SafeEnv($_GET['id'], 11, int);
	$table = SafeEnv($_GET['table'], 255, str);

	$db->Select($table, "`id`='$id'");
	$post = $db->FetchRow();

	$user_name = SafeDB($post['user_name'], 255, str);
	$user_homepage = SafeDB($post['user_homepage'], 255, str);
	$user_email = SafeDB($post['user_email'], 255, str);
	$user_hideemail = $post['user_hideemail'] == 1 ? true : false;
	$user_ip = SafeDB($post['user_ip'], 19, str);

	$post_message = SafeDB($post['post_message'], 0, str);

	if($post['user_id'] == '0'){
		FormRow('Имя', $site->Edit('user_name', $user_name, false, 'style="width:400px;"'));
		FormRow('E-mail', $site->Edit('user_email', $user_email, false, 'style="width:400px;"'));
		FormRow('Скрыть e-mail', $site->Check('user_hideemail', '1', $user_hideemail));
		FormRow('Сайт', $site->Edit('user_homepage', $user_homepage, false, 'style="width:400px;"'));
	}
	FormRow('Текст', $site->TextArea('post_message', $post_message, 'style="width:400px;height:200px;"'));
	$action = ADMIN_FILE.'?exe=comments&a=save&id='.$id.'&table='.$table.'&page='.$page;
	AddCenterBox('Редактирование комментария');
	AddForm(
		'<form action="'.$action.'" method="post">',
		$site->Button('Отмена', 'onclick="history.go(-1)"').$site->Submit('Сохранить')
	);

}

// Сохранение комментария
function AdminCommentsSave()
{
	global $config, $db, $site;
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
	$db->Update($table, $set, "`id`='$id'");
	GO(ADMIN_FILE.'?exe=comments&page='.$page);
}

function AdminDownloadsDeleteComment()
{
	global $config, $db, $site, $user;
	if(!isset($_POST['delcomments'])){
		GO(ADMIN_FILE.'?exe=comments');
	}
	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 10, int);
	}else{
		$page = 1;
	}
	if(isset($_GET['ok']) && $_GET['ok'] == '1'){

		$com_tables = $db->Select('comments');
		foreach($com_tables as $table){
			$comments_tables[$table['table']] = $table;
		}
		unset($com_tables);

		$posts_tables = array();
		$del_posts = explode(',', $_POST['delcomments']);
		foreach($del_posts as $post){
			$a = explode('::', $post);
			$posts_tables[$a[1]][] = array($a[0],$a[2]);
 		}

		//Удаляем комментарии для каждой таблицы отдельно
 		foreach($posts_tables as $post_table=>$posts_id){
			$where = '';
			foreach($posts_id as $p){
				$post_id = SafeEnv($p[0], 11, int);
				$obj_id = SafeEnv($p[1], 11, int);
				$where .= "`id`='$post_id' or ";
				$t = $comments_tables[$post_table];
				CalcCounter(
					$t['objects_table'],
					"`{$t['id_coll']}`='$obj_id'",
					$t['counter_coll'],
					-1
				);
			}
			$where = substr($where, 0, strlen($where) - 4);
			$db->Delete($post_table, $where);
 		}
		GO(ADMIN_FILE.'?exe=comments&page='.$page);
	}else{
		$cmcnt = count($_POST['delcomments']);
		$hid = implode(',', $_POST['delcomments']);
		$text = 'Вы действительно хотите удалить выделенные ('.$cmcnt.') комментарии?<br />'
		.$site->FormOpen(ADMIN_FILE.'?exe=comments&a=delete&page='.$page.'&ok=1', 'post')
			.$site->Hidden('delcomments', $hid)
			.$site->Submit('Да').'&nbsp;&nbsp;&nbsp;'
			.$site->Button('Нет', 'onclick="history.go(-1)"')
		.$site->FormClose();
		AddTextBox("Внимание", $text);
	}
}

function AdminComments( $action )
{
	switch ($action){
		case 'main':
			AdminCommentsMain();
			break;
		case 'edit':
			AdminCommentsEdit();
			break;
		case 'save':
			AdminCommentsSave();
			break;
		case 'delete':
			AdminDownloadsDeleteComment();
			break;
		default:
			AdminCommentsMain();
			break;
	}
}

if(isset($_GET['a'])){
	AdminComments($_GET['a']);
}else{
	AdminComments('main');
}

?>