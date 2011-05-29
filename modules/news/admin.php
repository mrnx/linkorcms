<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('Новости');

if(!$user->CheckAccess2('news', 'news')){
	AddTextBox('Ошибка', $config['general']['admin_accd']);
	return;
}

include_once ($config['inc_dir'].'configuration/functions.php');

if(isset($_GET['a'])){
	$action = $_GET['a'];
}else{
	$action = 'main';
}
TAddToolLink('Главная', 'main', 'news');
if($user->CheckAccess2('news', 'news_edit')){
	TAddToolLink('Добавить новость', 'add', 'news&a=add');
}
if($user->CheckAccess2('news', 'edit_topics')){
	TAddToolLink('Управление разделами', 'topics', 'news&a=topics');
}
if($user->CheckAccess2('news', 'news_conf')){
	TAddToolLink('Конфигурация', 'config', 'news&a=config');
}
TAddToolBox($action);
if(isset($_GET['page'])){
	$page = SafeEnv($_GET['page'], 11, int);
}else{
	$page = 1;
}

switch($action){
	case 'main':
		include (MOD_DIR.'admin/main.adm.php');
		break;
	case 'add':
		include (MOD_DIR.'admin/editor.adm.php');
		break;
	case 'edit':
		include (MOD_DIR.'admin/editor.adm.php');
		break;
	case 'save':
		$method = $_GET['m'];
		if($_POST['action'] == 'save'){
			include (MOD_DIR.'admin/save.adm.php');
		}else{
			if($method == 'add'){
				$action = 'addpreview';
			}else{
				$action = 'editpreview';
			}
			include (MOD_DIR.'admin/editor.adm.php');
		}
		break;
	case 'addpreview':
		include (MOD_DIR.'admin/editor.adm.php');
		break;
	case 'delnews':
		include (MOD_DIR.'admin/delete.adm.php');
		break;
	case 'changestatus':
		include (MOD_DIR.'admin/status.adm.php');
		break;
	case 'topics':
		include (MOD_DIR.'admin/topics.adm.php');
		break;
	case 'savetopic':
	case 'addtopic':
		include (MOD_DIR.'admin/savetopic.adm.php');
		break;
	case 'deltopic':
		include (MOD_DIR.'admin/topic_delete.php');
		break;
	case 'edittopic':
		include (MOD_DIR.'admin/topic_edit.php');
		break;
	case 'config':
		if(!$user->CheckAccess2('news', 'news_conf')){
			AddTextBox('Ошибка', 'Доступ запрещён!');
			return;
		}
		AdminConfigurationEdit('news', 'news', false, false, 'Конфигурация модуля "Новости"');
		break;
	case 'configsave':
		if(!$user->CheckAccess2('news', 'news_conf')){
			AddTextBox('Ошибка', 'Доступ запрещён!');
			return;
		}
		AdminConfigurationSave('news&a=config', 'news', false);
		break;
}

function AdminRenderPreviewNews( $title, $stext, $ctext, $auto_br ){
	if($auto_br){
		$stext = nl2br($stext);
		$ctext = nl2br($ctext);
	}
	$news = '<table cellspacing="0" cellpadding="0" class="newspreview">'.'<tr><td><b>'.$title.'</b><br /><br /></td></tr>'.'<tr><td>'.$stext.' '.$ctext.'</td></tr>'.'</table>';
	return $news;
}

function CalcNewsCounter( $topic_id, $inc ){
	global $db, $config;
	$db->Select('news_topics', "`id`='".$topic_id."'");
	$topic = $db->FetchRow();
	if($inc == true){
		$counter_val = $topic['counter'] + 1;
	}else{
		$counter_val = $topic['counter'] - 1;
	}
	$db->Update('news_topics', "counter='".$counter_val."'", "`id`='".$topic_id."'");
}

function GenMenuUrl( &$status, &$topic_id, &$author_id ){
	global $config;
	$menuurl = '';
	if(isset($_GET['status'])){
		$status = SafeEnv($_GET['status'], 1, int);
		if($config['news']['def_status_view'] != 0 || $status != 0){
			$menuurl .= '&status='.$status;
		}
	}else{
		$status = $config['news']['def_status_view'];
		if($status != 0){
			$menuurl .= '&status='.$status;
		}
	}
	if(isset($_GET['topic_id'])){
		$topic_id = SafeEnv($_GET['topic_id'], 11, int);
		if($topic_id != -1){
			$menuurl .= '&topic_id='.$topic_id;
		}
	}else{
		$topic_id = -1;
	}
	if(isset($_GET['auth_id'])){
		$author_id = SafeEnv($_GET['auth_id'], 11, int);
		if($author_id != -1){
			$menuurl .= '&auth_id='.$author_id;
		}
	}else{
		$auth_id = -1;
	}
	return $menuurl;
}

?>