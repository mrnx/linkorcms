<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('Гостевая книга');

if(!$user->CheckAccess2('guestbook', 'guestbook')){
	AddTextBox('Ошибка', $config['general']['admin_accd']);
	return;
}

include_once ($config['inc_dir'].'configuration/functions.php');

function countmess( $where, $that, $value ){
	System::database()->Select($where, "`$that`='$value'");
	return System::database()->NumRows();
}

if(isset($_GET['a'])){
	$action = $_GET['a'];
}else{
	$action = 'main';
}

if($config['gb']['moderation'] == '1'){
	$countmess = countmess('guestbook', 'premoderate', 0);
}
if($user->CheckAccess2('guestbook', 'guestbook_conf')){
	TAddToolLink('Гостевая книга', 'main', 'guestbook');
	TAddToolLink('Премодерация'.($config['gb']['moderation'] ? ' ('.$countmess.')' : ' (Отключена)'), 'premoderation', 'guestbook&a=premoderation');
	TAddToolLink('Конфигурация', 'config', 'guestbook&a=config');
}

TAddToolBox($action);
switch($action){
	case 'main':
		AdminGuestBookMain();
		break;
	case 'edit':
		AdminGuestBookMessageEditor();
		break;
	case 'save':
		AdminGuestBookSave();
		break;
	case 'delete':
		AdminGuestBookDeleteMessage();
		break;
	case 'addanswer':
	case 'editanswer':
		AdminGuestBookAnswerEditor();
		break;
	case 'delanswer':
		AdminGuestBookDeleteAnswer();
		break;
	case 'saveanswer':
		AdminGuestBookAnswerSave();
	case 'premoderation':
		AdminGuestBookPremoderationMain();
		break;
	case 'prem_yes':
	case 'prem_yes_all':
	case 'prem_del_all':
		AdminGuestBookPremoderationSave($action);
		break;
	case 'config':
		if(!$user->CheckAccess2('guestbook', 'guestbook_conf')){
			AddTextBox('Ошибка', 'Доступ запрещён!');
			return;
		}
		AdminConfigurationEdit('guestbook', 'gb', true, false, 'Конфигурация модуля гостевой');
		break;
	case 'configsave':
		if(!$user->CheckAccess2('guestbook', 'guestbook_conf')){
			AddTextBox('Ошибка', 'Доступ запрещён!');
			return;
		}
		AdminConfigurationSave('guestbook&a=config', 'gb', true);
		break;
	default:
		AdminGuestBookMain();
}

// Сообщения
function AdminGuestBookMain(){
	AddCenterBox('Гостевая книга');

	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 10, int);
	}else{
		$page = 1;
	}

	$editing = System::user()->CheckAccess2('guestbook', 'edit');
	$msgs = System::database()->Select('guestbook', "`premoderate`='1'");
	if(System::database()->NumRows() == 0){
		System::admin()->Highlight('В гостевой книге нет сообщений.');
		return;
	}

	SortArray($msgs, 'date', true);
	$num = System::config('gb/msgonpage');
	if(count($msgs) > $num){
		$navigator = new Navigation($page);
		$navigator->GenNavigationMenu($msgs, $num, ADMIN_FILE.'?exe=guestbook');
		AddNavigation();
		$nav = true;
	}else{
		$nav = false;
	}
	$text = '';
	foreach($msgs as $msg){
		if($msg['url'] == ''){
			$url = 'Нет';
		}else{
			$url = '<a href="http://'.SafeDB($msg['url'], 250, str).'" target="_blank">'.SafeDB($msg['url'], 250, str).'</a>';
		}
		if($msg['email'] == ''){
			$name = SafeDB($msg['name'], 50, str);
		}else{
			$name = PrintEmail($msg['name'], $msg['email']);
		}

		$mid = SafeDB($msg['id'], 11, int);
		$func = '';
		$func .= SpeedButton('Редактировать сообщение', ADMIN_FILE.'?exe=guestbook&a=edit&id='.$mid, 'images/admin/edit.png');
		$func .= System::admin()->SpeedConfirm('Удалить сообщение', ADMIN_FILE.'?exe=guestbook&a=delete&id='.$mid, 'images/admin/delete.png', 'Удалить сообщение?');

		if($msg['answers'] == ''){
			$answers = array();
		}else{
			$answers = unserialize($msg['answers']);
		}
		$func2 = '';
		if(key_exists(System::user()->Name(), $answers)){
			$func2 = (System::user()->CheckAccess2('guestbook', 'answer') ? '<a href="'.ADMIN_FILE.'?exe=guestbook&a=editanswer&id='.$mid.'">Редактировать ответ</a> :: ' : '')
				.'<a href="'.ADMIN_FILE.'?exe=guestbook&a=delanswer&id='.$mid.'">Удалить ответ</a>';
		}elseif(System::user()->CheckAccess2('guestbook', 'answer')){
			$func2 = '<a href="'.ADMIN_FILE.'?exe=guestbook&a=addanswer&id='.$mid.'">Ответить</a>';
		}
		$keys = array_keys($answers);
		$answerstext = '';
		if(count($answers) > 0){
			$answerstext = '<br /><br />Ответы: <ul style="margin:3px;margin-left:16px;">'."\n";
			foreach($keys as $key){
				$answerstext .= '<li>'.$key.' - '.$answers[$key]."\n";
			}
			$answerstext .= '</ul>'."\n";
		}
		$text .= '<table cellspacing="0" cellpadding="0" class="commtable" style="width:75%;">';
		$text .= '
		<tr>
			<th style="text-align:left; width: 180px;">'.$name.'</th>
			<th style="width: 160px;">Сайт: '.$url.'</th>
			<th style="width: 120px;">ICQ: '.SafeDB($msg['icq'], 15, str).'</th>
			<th style="width: 120px;">IP: '.SafeDB($msg['user_ip'], 20, str).'</th>
			<th>'.($editing ? $func : '&nbsp;').'</th>
		</tr>';
		$text .= '<tr><td colspan="5" style="text-align:left;padding:10px;" class="commtable_text">'.SafeDB($msg['message'], 0, str).$answerstext.'</td></tr>';
		$text .= '<tr><th>Дата: '.TimeRender($msg['date']).'</th><th colspan="4" style="text-align:right;">'.$func2.'</th></tr>';
		$text .= '</table>';
	}
	AddText($text);
	if($nav){
		AddNavigation();
	}
}

// Премодерация
function AdminGuestBookPremoderationMain(){
	global $db, $config, $user;
	$premoderation = $user->CheckAccess2('guestbook', 'premoderation');
	$premoderate = $db->Select('guestbook', "`premoderate`='0'");
	if($db->NumRows() == 0){
		$text = '<center>В премодерации нет сообщений.</center>';
		AddTextBox('Премодерация', $text);
		return;
	}
	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 10, int);
	}else{
		$page = 1;
	}
	AddCenterBox('Премодерация');
	SortArray($premoderate, 'date', true);
	$num = $config['gb']['msgonpage'];
	if(count($premoderate) > $num){
		$navigator = new Navigation($page);
		$navigator->GenNavigationMenu($premoderate, $num, ADMIN_FILE.'?exe=guestbook&a=premoderation');
		AddNavigation();
		$nav = true;
	}else{
		$nav = false;
	}
	$text = '';
	$countmess = countmess('guestbook', 'premoderate', 0);
	$text = 'В премодерации '.$countmess.' сообщений.<br /><br />';
	foreach($premoderate as $pre){
		if($pre['url'] == ''){
			$url = 'Нет';
		}else{
			$url = '<a href="http://'.SafeDB($pre['url'], 250, str).'" target="_blank">'.SafeDB($pre['url'], 250, str).'</a>';
		}
		if($pre['email'] == ''){
			$name = SafeDB($pre['name'], 50, str);
		}else{
			$name = PrintEmail($pre['name'], $pre['email']);
		}
		$mid = SafeDB($pre['id'], 11, int);
		$del = '<a href="'.ADMIN_FILE.'?exe=guestbook&a=delete&id='.$mid.'&ok=0"><img src="images/admin/delete.png" title="Удалить сообщение" /></a>';
		$func2 = '';
		$func2 = '<a href="'.ADMIN_FILE.'?exe=guestbook&a=prem_yes&id='.$mid.'">Разрешить</a>';
		$text .= '<table cellspacing="0" cellpadding="0" class="cfgtable" style="width:75%;">';
		$text .= '<tr><td style="text-align:left;padding-left:10px;">Сообщение от '.$name.'</td><td>Сайт: '.$url.'</td><td>ICQ: '.SafeDB($pre['icq'], 15, str).'</td><td>IP: '.SafeDB($pre['user_ip'], 20, str).'</td><td> '.$del.' </td></tr>';
		$text .= '<tr><td colspan="5" style="text-align:left;padding:10px;">'.SafeDB($pre['message'], 0, str).'</td></tr>';
		$text .= '<tr><td>Дата: '.TimeRender($pre['date']).'</td><td colspan="4" style="text-align:right;">'.$func2.'</td></tr>';
		$text .= '</table>';
	}
	$text_all_prem_del = '<a href="'.ADMIN_FILE.'?exe=guestbook&a=prem_yes_all">Разрешить все</a> | <a href="'.ADMIN_FILE.'?exe=guestbook&a=prem_del_all">Удалить все</a>';
	AddText($text);
	AddText($text_all_prem_del);
	if($nav){
		AddNavigation();
	}
}

function AdminGuestBookPremoderationSave( $action ){
	global $db, $config, $user;
	if(!$user->CheckAccess2('guestbook', 'premoderation')){
		AddTextBox('Ошибка', $config['general']['admin_accd']);
		return;
	}
	if($action == 'prem_yes'){
		$premoderate = '1';
		$db->Update('guestbook', "premoderate='$premoderate'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	}elseif($action == 'prem_yes_all'){
		$premoderate = '1';
		$db->Update('guestbook', "premoderate='$premoderate'");
	}elseif($action == 'prem_del_all'){
		$db->Delete('guestbook', "`premoderate`='0'");
	}
	GO(ADMIN_FILE.'?exe=guestbook&a=premoderation');
}

function AdminGuestBookMessageEditor(){
	global $db, $config, $site, $user;
	if(!$user->CheckAccess2('guestbook', 'edit')){
		AddTextBox('Ошибка', $config['general']['admin_accd']);
		return;
	}
	$id = SafeEnv($_GET['id'], 11, int);
	$db->Select('guestbook', "`id`='".$id."'");
	if($db->NumRows() > 0){
		$msg = $db->FetchRow();
		FormRow('Имя', $site->Edit('name', SafeDB($msg['name'], 50, str), false, 'maxlength="50"'));
		FormRow('E-mail', $site->Edit('email', SafeDB($msg['email'], 50, str), false, 'maxlength="50"').'Скрыть'.$site->Check('hideemail', 'ok', $msg['hide_email']));
		FormRow('Сайт', $site->Edit('url', SafeDB($msg['url'], 250, str), false, 'maxlength="250"'));
		FormRow('Номер ICQ', $site->Edit('icq', SafeDB($msg['icq'], 15, str), false, 'maxlength="15"'));
		FormRow('Сообщение', $site->TextArea('message', SafeDB($msg['message'], 0, str), 'style="width:400px;height:200px;"'));
		AddCenterBox('Редактирование записи');
		AddForm('<form action="'.ADMIN_FILE.'?exe=guestbook&a=save&id='.$id.'&back='.SaveRefererUrl().'" method="POST">', $site->Button('Отмена', 'onclick="history.go(-1);"').$site->Submit('Сохранить'));
	}else{
		GO(ADMIN_FILE.'?exe=guestbook');
	}
}

function AdminGuestBookSave(){
	global $db, $config, $user;
	if(!$user->CheckAccess2('guestbook', 'edit')){
		AddTextBox('Ошибка', $config['general']['admin_accd']);
		return;
	}
	$id = SafeEnv($_GET['id'], 11, int);
	$name = SafeEnv($_POST['name'], 50, str);
	$email = SafeEnv($_POST['email'], 50, str);
	if(isset($_POST['hideemail'])){
		$hideemail = '1';
	}else{
		$hideemail = '0';
	}
	$url = SafeEnv($_POST['url'], 50, str);
	$icq = SafeEnv($_POST['icq'], 50, str);
	$message = SafeEnv($_POST['message'], 0, str);
	$db->Update('guestbook', "name='$name',email='$email',hide_email='$hideemail',url='$url',icq='$icq',message='$message'", "`id`='$id'");
	//GO(ADMIN_FILE.'?exe=guestbook');
	GoRefererUrl($_GET['back']);
	AddTextBox('Сообщение', 'Изменения успешно сохранены.');
}

function AdminGuestBookAnswerEditor(){
	global $db, $config, $user, $site;
	if(!$user->CheckAccess2('guestbook', 'answer')){
		AddTextBox('Ошибка', $config['general']['admin_accd']);
		return;
	}
	$id = SafeEnv($_GET['id'], 11, int);
	$db->Select('guestbook', "`id`='".$id."'");
	if($db->NumRows() > 0){
		$msg = $db->FetchRow();
		if($msg['answers'] == ''){
			$answers = array();
		}else{
			$answers = unserialize($msg['answers']);
		}
		if(key_exists($user->Name(), $answers)){
			$ans = $answers[$user->Name()];
		}else{
			$ans = '';
		}
		FormRow('Ответ', $site->TextArea('answer', htmlspecialchars($ans), 'style="width:400px;height:200px;"'));
		AddCenterBox('Ответ на сообщение');
		AddForm('<form action="'.ADMIN_FILE.'?exe=guestbook&a=saveanswer&id='.$id.'&back='.SaveRefererUrl().'" method="POST">', $site->Button('Отмена', 'onclick="history.go(-1);"').$site->Submit('Сохранить'));
	}else{
		GO(ADMIN_FILE.'?exe=guestbook');
	}
}

function AdminGuestBookAnswerSave(){
	global $db, $config, $user;
	if(!$user->CheckAccess2('guestbook', 'answer')){
		AddTextBox('Ошибка', $config['general']['admin_accd']);
		return;
	}
	$id = SafeEnv($_GET['id'], 11, int);
	$db->Select('guestbook', "`id`='".$id."'");
	if($db->NumRows() > 0){
		$msg = $db->FetchRow();
		if($msg['answers'] == ''){
			$answers = array();
		}else{
			$answers = unserialize($msg['answers']);
		}
		$answers[$user->Name()] = stripslashes(SafeEnv($_POST['answer'], 0, str));
		$answers = serialize($answers);
		$db->Update('guestbook', "answers='$answers'", "`id`='".$id."'");
	}
	//GO(ADMIN_FILE.'?exe=guestbook');
	GoRefererUrl($_GET['back']);
	AddTextBox('Сообщение', 'Изменения успешно сохранены.');
}

function AdminGuestBookDeleteAnswer(){
	global $db, $config, $user;
	$id = SafeEnv($_GET['id'], 11, int);
	$db->Select('guestbook', "`id`='".$id."'");
	if($db->NumRows() > 0){
		$msg = $db->FetchRow();
		if($msg['answers'] == ''){
			$answers = array();
		}else{
			$answers = unserialize($msg['answers']);
		}
		if(isset($answers[$user->Name()])){
			unset($answers[$user->Name()]);
		}
		$answers = serialize($answers);
		$db->Update('guestbook', "answers='$answers'", "`id`='".$id."'");
	}
	//GO(ADMIN_FILE.'?exe=guestbook');
	GoBack();
}

function AdminGuestBookDeleteMessage(){
	if(!System::user()->CheckAccess2('guestbook', 'edit')){
		AddTextBox('Ошибка', System::config('general/admin_accd'));
		return;
	}
	System::database()->Delete('guestbook', "`id`='".SafeEnv($_GET['id'], 11, int)."'");

	GoRefererUrl($_GET['back']);
	AddTextBox('Сообщение', 'Сообщение удалено.');
}
