<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$site->SetTitle('Опросы');

if(isset($_GET['op'])){
	$op = $_GET['op'];
}else{
	$op = 'main';
}

switch($op){
	case 'main':
		IndexPollsViewPolls();
		break;
	case 'voice':
		IndexPollsVoice();
		break;
	case 'viewpoll':
	case 'viewresult':
		IndexPollsViewPoll();
		break;
	// Комментарии
	case 'addpost': IndexPollsAddPost();
		break;
	case 'editpost': IndexPollsEditPost();
		break;
	case 'savepost': IndexPollsEditPostSave();
		break;
	case 'deletepost': IndexPollsDeletePost();
		break;
	// //
	default:
		HackOff();
}

function IndexPollsViewPolls(){
	global $site, $config, $user;
	$polls = System::database()->Select('polls', GetWhereByAccess('view', "`active`='1'"));

	$time = time();
	if(count($polls) > 0){
		$site->AddTemplatedBox('Опросы', 'module/polls_main.html');
		$site->AddBlock('polls_title', true, false, 'ptitle');
		$site->Blocks['polls_title']['vars'] = array('public'=>'Добавлен', 'title'=>'Опрос', 'comments'=>'Комментарий', 'voices'=>'Всего ответов');
		$site->AddBlock('polls', true, true, 'poll');
		SortArray($polls, 'date', true);
		foreach($polls as $poll){
			$answers = unserialize($poll['answers']);
			$c = count($answers);
			$num_voices = 0;
			for($i = 0; $i < $c; $i++){
				$num_voices += SafeDB($answers[$i][2], 11, int);
			}
			$show_results_link = $config['polls']['show_results'];
			$vars = array();
			$vars['title'] = SafeDB($poll['question'], 255, str);
			$vars['url'] = Ufu('index.php?name=polls&op=viewpoll&poll_id='.SafeDB($poll['id'], 11, int), 'polls/{poll_id}/');
			$vars['public'] = TimeRender($poll['date'], false, false);
			$vars['num_voices'] = $num_voices;
			$vars['allow_comments'] = SafeDB($poll['allow_comments'], 1, int);
			$vars['comments'] = SafeDB($poll['com_counter'], 11, int);
			$site->AddSubBlock('polls', true, $vars);
		}
	}else{
		$site->AddTextBox('', '<center>Опросов пока нет.</center>');
	}
}

function IndexPollsViewPoll(){
	global $db, $site, $user, $op, $config;

	$id = SafeEnv($_GET['poll_id'], 11, int);
	System::database()->Select('polls', GetWhereByAccess('view', "`id`='$id' and `active`='1'"));
	if($db->NumRows() == 0){
		HackOff();
	}
	$poll = $db->FetchRow();

	$site->SetTitle(SafeDB($poll['question'], 255, str));

	//Отвечал ли пользователь
	$ip = getip();
	if($user->Auth){
		$uid = $user->Get('u_id');
	}else{
		$uid = -1;
	}
	$db->Select('polls_voices', "`poll_id`='$id' and (`user_ip`='$ip' or `user_id`='$uid')");
	$viewresult = $db->NumRows() != 0;
	$viewresult = $viewresult || ($op == 'viewresult' && ($config['polls']['show_results'] == '1' || $user->isAdmin()));

	$answers = unserialize($poll['answers']);
	$c = count($answers);
	$num_voices = 0;
	for($i = 0; $i < $c; $i++){
		$num_voices += SafeDB($answers[$i][2], 11, int);
	}

	if($viewresult){
		if($num_voices != 0){
			$per_c = 100 / $num_voices;
		}else{
			$per_c = 0;
		}
		$site->AddTemplatedBox('', 'module/poll_result.html');
		$vars = array('lresults_title'=>'Результаты опроса', 'lrvalue'=>'гол.');
		$site->AddBlock('poll_result_rows', true, true, 'pr');
		$c = count($answers);
		for($i = 0; $i < $c; $i++){
			if($answers[$i][0] != ''){
				$title = SafeDB($answers[$i][0], 255, str);
				$color = SafeDB($answers[$i][1], 255, str);
				$value = (round($per_c * $answers[$i][2]));
				$num_voices2 = SafeDB($answers[$i][2], 11, int);
				$site->AddSubBlock('poll_result_rows', true, array('answertext'=>$title, 'value'=>$value, 'num_voices'=>$num_voices2, 'color'=>$color));
			}
		}
	}else{
		$site->AddTemplatedBox('', 'module/poll.html');
		$vars = array();
		$vars['form_action'] = Ufu('index.php?name=polls&op=voice&poll_id='.SafeDB($poll['id'], 11, int), 'polls/{poll_id}/voice/');
		$site->AddBlock('poll_variants', true, true, 'variant');
		for($i = 0; $i < $c; $i++){
			if($answers[$i][0] != ''){
				$color = SafeDB($answers[$i][1], 255, str);
				$title = SafeDB($answers[$i][0], 255, str);
				if($poll['multianswers'] == '1'){
					$control = $site->Check('voice[]', $i);
				}else{
					$control = $site->Radio('voice[]', $i);
				}
				$site->AddSubBlock('poll_variants', true, array('title'=>$title, 'control'=>$control, 'color'=>$color));
			}
		}
		$vars['poll_showresults'] = ($config['polls']['show_results'] == '1' || $user->isAdmin());
		$vars['showresults_url'] = Ufu('index.php?name=polls&op=viewresult&poll_id='.$id, 'polls/{poll_id}/results/');
		$vars['others_url'] = Ufu('index.php?name=polls', '{name}/');
	}
	$vars['title'] = SafeDB($poll['question'], 255, str);
	$vars['back_url'] = Ufu('index.php?name=polls', '{name}/');
	$vars['back_caption'] = 'Назад к списку';
	$vars['lnum_voices'] = 'Ответов';
	$vars['num_voices'] = $num_voices;
	$vars['lcomments'] = 'Комментариев';
	$vars['comments'] = SafeDB($poll['com_counter'], 11, int);
	$site->AddBlock('poll', true, false, '');
	$site->Blocks['poll']['vars'] = $vars;

	// Выводим комментарии
	if(isset($_GET['page'])){
		$nav_page = SafeEnv($_GET['page'], 11, int);
	}else{
		$nav_page = 0;
	}
	include_once($config['inc_dir'].'posts.class.php');
	$posts = new Posts('polls_comments', $poll['allow_comments'] == '1');
	$posts->PostFormAction = "index.php?name=polls&op=addpost&poll_id=$id&page=$nav_page";
	$posts->EditPageUrl = "index.php?name=polls&op=editpost&poll_id=$id";
	$posts->DeletePageUrl = "index.php?name=polls&op=deletepost&poll_id=$id";

	$posts->NavigationUrl = Ufu("index.php?name=polls&op=viewpoll&poll_id=$id", 'polls/{poll_id}/page{page}/', true);

	$posts->RenderPosts($id, 'poll_comments', 'comments_navigation', false, $nav_page);
	$posts->RenderForm(false, 'poll_comments_form');
}

function IndexPollsVoice(){
	global $user, $site, $config;
	if(!isset($_GET['poll_id'])){
		GoBack();
	}
	if(!isset($_POST['voice'])){
		$site->AddTextBox('', '<center>Вы не выбрали ни одного варианта ответа.</center>');
	}else{
		$pid = SafeEnv($_GET['poll_id'], 11, int);

		System::database()->Select('polls', GetWhereByAccess('view', "`id`='$pid' and `active`='1'"));

		if(System::database()->NumRows() == 0){
			GoBack();
		}
		$poll = System::database()->FetchRow();
		$answers = unserialize($poll['answers']);
		$multianswers = SafeDB($poll['multianswers'], 1, int);
		$voice = SafeEnv($_POST['voice'], 11, int);
		if(!$multianswers){
			$voice = $voice[0];
		}
		//Проверяем, учавствовал ли данный пользователь в этом опросе
		$ip = getip();
		if($user->Auth){
			$uid = $user->Get('u_id');
		}else{
			$uid = -1;
		}
		System::database()->Select('polls_voices', "`poll_id`='$pid' and (`user_ip`='$ip' or `user_id`='$uid')");
		if(System::database()->NumRows() == 0){
			if(!$multianswers){
				if(isset($answers[$voice])){
					$answers[$voice][2] = $answers[$voice][2] + 1;
					$answers = serialize($answers);
					System::database()->Update('polls', "answers='$answers'", "`id`='$pid'");
				}else{
					GoBack();
				}
			}else{
				$c = count($voice);
				for($i = 0; $i < $c; $i++){
					if(isset($answers[$voice[$i]])){
						$answers[$voice[$i]][2] = $answers[$voice[$i]][2] + 1;
					}else{
						GoBack();
					}
				}
				$answers = serialize($answers);
				System::database()->Update('polls', "answers='$answers'", "`id`='$pid'");
			}
			$voice = serialize($voice);
			if($user->Auth){
				$user_id = $user->Get('u_id');
			}else{
				$user_id = 0;
			}
			System::database()->Insert('polls_voices', "'','$pid','".getip()."','$voice','$user_id'");
			$user->ChargePoints($config['points']['polls_answer']);
			GoBack();
		}else{
			$site->AddTextBox('', '<center>Извините, Вы уже принимали участие в этом опросе.</center>');
		}
	}
}

function IndexPollsAddPost(){
	global $db, $config, $site;
	$get_id        = 'poll_id'; // Имя параметра в get для получения id объекта
	$table         = 'polls_comments'; // Таблица комментариев
	$object_table  = 'polls'; // Таблица объектов
	$counter_field = 'com_counter'; // Поле счетчик комментариев в таблице объекта
	$alloy_field   = 'allow_comments'; // Поле разрешить комментирии для этого объекта

	$id = SafeEnv($_GET[$get_id], 11, int);
	$db->Select($object_table, "`id`='$id'");
	$obj = $db->FetchRow();
	$alloy_comments = $obj[$alloy_field] == '1';
	// Добавляем комментарий
	include_once($config['inc_dir'].'posts.class.php');
	$posts = new Posts($table, $alloy_comments);
	if($posts->SavePost($id, false)){
		$db->Select($object_table, "`id`='$id'");
		$obj = $db->FetchRow();
		$counter = $obj[$counter_field] + 1;
		$db->Update($object_table, "`$counter_field`='$counter'", "`id`='$id'");
		// Генерируем обратную ссылку
		$parent = explode('_', $_POST['parent_id'], 2);
		$parent_id = SafeEnv($parent[1], 11, int);
		$page = ($parent_id != 0 && $_GET['page'] != 0 ? "&page={$_GET['page']}" : '');
		$parent = ($parent_id != 0 ? "#post_$parent_id" : '#post_'.$db->GetLastId());
		GO(GetSiteUrl().Ufu("index.php?name=polls&op=viewpoll&poll_id=$id$page$parent", 'polls/{poll_id}/'.($page != '' ? 'page{page}/' : '')));
		// --------------------------
	}else{
		$site->AddTextBox('Ошибка', $posts->PrintErrors());
	}
}

function IndexPollsEditPost( $back_id = null ){
	global $site, $config;
	$get_id = 'poll_id';       // Имя параметра в get для получения id объекта
	$table = 'polls_comments'; // Таблица комментариев
	if($back_id == null){
		$back_id = SaveRefererUrl();
	}
	$action_url = "index.php?name=polls&op=savepost&poll_id=".SafeEnv($_GET[$get_id], 11, int)."&back=$back_id";

	$site->AddTemplatedBox('','edit_comment.html');
	include_once($config['inc_dir'].'posts.class.php');
	$posts = new Posts($table);
	$posts->PostFormAction = $action_url;
	$posts->RenderForm(true, 'post_form');
}

function IndexPollsEditPostSave(){
	global $config;
	$get_id = 'poll_id';       // Имя параметра в get для получения id объекта
	$table = 'polls_comments'; // Таблица комментариев

	include_once($config['inc_dir'].'posts.class.php');
	$posts = new Posts($table);
	if($posts->SavePost(SafeEnv($_GET[$get_id], 11, int), true)){
		GoRefererUrl($_GET['back']);
	}else{
		$site->AddTextBox('Ошибка', $posts->PrintErrors());
		IndexPollsEditPost($_GET['back']);
	}
}

function IndexPollsDeletePost(){
	global $config, $db;
	$get_id = 'poll_id'; // Имя параметра в get для получения id объекта
	$table = 'polls_comments'; // Таблица комментариев
	$object_table = 'polls'; // Таблица объектов
	$counter_field = 'com_counter'; // Поле счетчик комментариев в таблице объекта

	if(!isset($_GET['back'])){
		$back_id = SaveRefererUrl();
	}else{
		$back_id = $_GET['back'];
	}
	$id = SafeEnv($_GET[$get_id], 11, int);
	$delete_url = "index.php?name=polls&op=deletepost&poll_id=$id&back=$back_id";

	include_once($config['inc_dir'].'posts.class.php');
	$posts = new Posts($table);
	$posts->DeletePageUrl = $delete_url;
	$deleted_posts_count = $posts->DeletePost();
	if($deleted_posts_count > 0){
		$db->Select($object_table, "`id`='$id'");
		$obj = $db->FetchRow();
		$counter = $obj[$counter_field] - $deleted_posts_count;
		$db->Update($object_table, "`$counter_field`='$counter'", "`id`='$id'");
		GoRefererUrl($back_id);
	}
}
