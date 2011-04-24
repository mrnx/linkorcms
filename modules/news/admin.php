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

include_once ($config['apanel_dir'].'configuration/functions.php');

function AdminRenderNews( $news, $FullFormat = false, $page = 1, $topic_neme = '', $menuurl = '' )
{
	global $config, $user;
	$aed = $user->CheckAccess2('news', 'news_edit');

	if(!$aed){
		$enc = 'Выключена';
		$denc = 'Включена';
	}else{
		$enc = 'Включить';
		$denc = 'Выключить';
	}

	switch($news['enabled']){
		case '0':
			$en = '<font color="#FF0000">'.$enc.'</font>';
			break;
		case '1':
			$en = $denc;
			break;
	}

	$view = ViewLevelToStr(SafeDB($news['view'], 1, int));

	if(isset($_GET['a'])){
		$a = SafeEnv($_GET['a'], 8, str);
	}else{
		$a = 'main';
	}

	if($page > 1){
		$pageparams = '&page='.$page;
	}else{
		$pageparams = '';
	}

	if($aed){
		#ссылка включить выключить
		switch($a){
			case 'main':
				$en = '<a href="'.$config['admin_file'].'?exe=news&a=changestatus&id='.SafeDB($news['id'], 11, int).'&pv=main'.$pageparams.$menuurl.'">'.$en.'</a>';
				break;
			case 'readfull':
				$en = '<a href="'.$config['admin_file'].'?exe=news&a=changestatus&id='.SafeDB($news['id'], 11, int).'&pv=readfull">'.$en.'</a>';
				break;
		}
	}
	$AllowComments = SafeDB($news['allow_comments'], 1, bool);
	if(SafeDB($news['auto_br'], 1, bool)){
		$news['start_text'] = nl2br(SafeDB($news['start_text'], 0, str, false, false));
		$news['end_text'] = nl2br(SafeDB($news['end_text'], 0, str, false, false));
	}
	$image = SafeDB(RealPath2($news['icon']), 255, str);
	$icons_dir = RealPath2($config['news']['icons_dirs']);
	$img_view = SafeDB(RealPath2($news['img_view']), 255, str);
	if(!is_file($icons_dir.$image)){
		$vars['image'] = '';
		$vars['image_url'] = false;
	}elseif($img_view == 1){ // Исходная картинка
		$vars['image'] = $icons_dir.$image;
		$vars['image_url'] = false;
	}elseif($img_view == 2){ // Эскиз
		$vars['image'] = $icons_dir.'thumbs/'.$image;
		$vars['image_url'] = $icons_dir.$image;
	}elseif($img_view == 0){ // Авто
		$size = ImageSize($icons_dir.$image);
		if($size['width'] > $config['news']['thumb_max_width']){
			$vars['image'] = $icons_dir.'thumbs/'.$image;
			$vars['image_url'] = $icons_dir.$image;
		}else{
			$vars['image'] = $icons_dir.$image;
			$vars['image_url'] = false;
		}
	}
	$coms = SafeDB($news['comments_counter'], 11, int); // Количество комментарий

	$func = '';
	$func .= SpeedButton('Редактировать', $config['admin_file'].'?exe=news&a=edit&id='.SafeDB($news['id'], 11, int).$menuurl, 'images/admin/edit.png');
	$func .= SpeedButton('Удалить', $config['admin_file'].'?exe=news&a=delnews&id='.SafeDB($news['id'], 11, int).$menuurl, 'images/admin/delete.png');

	$text = '<table cellspacing="0" cellpadding="0" class="newstable" width="90%">';
	$text .= '<tr><th>Пиктограмма</th><th>Раздел</th><th>Заголовок</th><th>Автор</th><th>Дата</th></tr>';
	$text .= ($vars['image'] ? '<tr><td rowspan="3" align="center">'.($vars['image_url'] ? '<a href="'.$vars['image_url'].'" target="_blank">' : '').'<img src="'.$vars['image'].'" />'.($vars['image_url'] ? '</a>' : '').'</td></tr>' : '<tr><td rowspan="3" align="center">Нет</td></tr>');
	$text .= '<tr><td><b>'.$topic_neme.'</b></td><td><b>'.SafeDB($news['title'], 255, str).'</b></td><td><b>'.SafeDB($news['author'], 255, str).'</b></td><td><b>'.TimeRender(SafeDB($news['date'], 11, int)).'</b></td></tr>'.'<tr><td colspan="4" style="width:100%;text-align:left;padding:6px;">'.$news['start_text'].($FullFormat ? ' '.$news['end_text'] : '').'</td></tr>'.'<tr>'.'<td width="200">Просмотров:&nbsp;'.SafeDB($news['hit_counter'], 11, int).'</td>'.'<td>'.($AllowComments ? 'Коментарии:&nbsp;'.$coms : 'Обсуждение закрыто').'<td width="200">Просматривают:&nbsp;'.$view.'</td>'.'<td width="200">'.$en.'</td>'.(($aed) ? '<td width="50">'.$func.'</td>' : '').'</tr></table>';
	return $text;
}
function AdminRenderNews2( $news, $FullFormat = false, $page = 1, $topic_neme = '', $menuurl = '' )
{
	global $config, $user;
	$aed = $user->CheckAccess2('news', 'news_edit');
	switch($news['enabled']){
		case '1':
			$st = '<font color="#008000">Вкл.</font></a>';
			break;
		case '0':
			$st = '<font color="#FF0000">Выкл.</font>';
			break;
	}
	$view = ViewLevelToStr(SafeDB($news['view'], 1, int));
	if($page > 1){
		$pageparams = '&page='.$page;
	}else{
		$pageparams = '';
	}
	if($aed){
		$st = '<a href="'.$config['admin_file'].'?exe=news&a=changestatus&id='.SafeDB($news['id'], 11, int).'&pv=main'.$pageparams.$menuurl.'">'.$st.'</a>';
	}
	$AllowComments = SafeDB($news['allow_comments'], 1, bool);
	if(SafeDB($news['auto_br'], 1, bool)){
		$news['start_text'] = nl2br(SafeDB($news['start_text'], 0, str, false, false));
		$news['end_text'] = nl2br(SafeDB($news['end_text'], 0, str, false, false));
	}
	$coms = SafeDB($news['comments_counter'], 11, int); // Количество комментарий

	$func = '';
	$func .= SpeedButton('Редактировать', $config['admin_file'].'?exe=news&a=edit&id='.SafeDB($news['id'], 11, int).$menuurl, 'images/admin/edit.png');
	$func .= SpeedButton('Удалить', $config['admin_file'].'?exe=news&a=delnews&id='.SafeDB($news['id'], 11, int).$menuurl, 'images/admin/delete.png');

	$text = '<tr><td><b><a href="'.$config['admin_file'].'?exe=news&a=edit&id='.SafeDB($news['id'], 11, int).$menuurl.'">'.SafeDB($news['title'], 255, str).'</a></b></td>
	<td>'.TimeRender(SafeDB($news['date'], 11, int)).'</td>
	<td>'.SafeDB($news['hit_counter'], 11, int).'</td>
	<td>'.($AllowComments ? $coms : 'Обсуждение закрыто').'</td>
	<td>'.$view.'</td>
	<td>'.$st.'</td>
	<td>'.$func.'</td>
	</tr>';
	return $text;
}
function AdminRenderPreviewNews( $title, $stext, $ctext, $auto_br )
{
	if($auto_br){
		$stext = nl2br($stext);
		$ctext = nl2br($ctext);
	}
	$news = '<table cellspacing="0" cellpadding="0" class="newspreview">'.'<tr><td><b>'.$title.'</b><br /><br /></td></tr>'.'<tr><td>'.$stext.' '.$ctext.'</td></tr>'.'</table>';
	return $news;
}
function CalcNewsCounter( $topic_id, $inc )
{
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

function GenMenuUrl( &$status, &$topic_id, &$author_id )
{
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

?>