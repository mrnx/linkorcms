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
	case 'main': AdminGuestBookMain();
		break;
	case 'edit':AdminGuestBookMessageEditor();
		break;
	case 'save':AdminGuestBookSave();
		break;
	case 'delete':AdminGuestBookDeleteMessage();
		break;
	case 'addanswer':
	case 'editanswer':AdminGuestBookAnswerEditor();
		break;
	case 'delanswer':AdminGuestBookDeleteAnswer();
		break;
	case 'saveanswer':AdminGuestBookAnswerSave();
	case 'premoderation':AdminGuestBookPremoderationMain();
		break;
	case 'prem_yes':
	case 'prem_yes_all':
	case 'prem_del_all': AdminGuestBookPremoderationSave($action);
		break;
	case 'config':
		if(!$user->CheckAccess2('guestbook', 'guestbook_conf')){
			AddTextBox('Ошибка', 'Доступ запрещён!');
			return;
		}
		System::admin()->AddCenterBox('Конфигурация модуля "Гостевая книга"');
		if(isset($_GET['saveok'])){
			System::admin()->Highlight('Настройки сохранены.');
		}
		System::admin()->ConfigGroups('gb');
		System::admin()->AddConfigsForm(ADMIN_FILE.'?exe=guestbook&a=configsave');
		break;
	case 'configsave':
		if(!$user->CheckAccess2('guestbook', 'guestbook_conf')){
			AddTextBox('Ошибка', 'Доступ запрещён!');
			return;
		}
		System::admin()->SaveConfigs('gb');
		GO(ADMIN_FILE.'?exe=guestbook&a=config&saveok');
		break;
	default: AdminGuestBookMain();
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
		$func .= System::admin()->SpeedButton('Редактировать сообщение', ADMIN_FILE.'?exe=guestbook&a=edit&id='.$mid, 'images/admin/edit.png');
		$func .= System::admin()->SpeedConfirm('Удалить сообщение', ADMIN_FILE.'?exe=guestbook&a=delete&id='.$mid, 'images/admin/delete.png', 'Удалить сообщение?');

		if($msg['answers'] == ''){
			$answers = array();
		}else{
			$answers = unserialize($msg['answers']);
		}
		$func2 = '';
		if(key_exists(System::user()->Name(), $answers)){
			$func2 = (System::user()->CheckAccess2('guestbook', 'answer') ? System::admin()->Link('Редактировать ответ', ADMIN_FILE.'?exe=guestbook&a=editanswer&id='.$mid).' :: ' : '')
				.System::admin()->Link('Удалить ответ', ADMIN_FILE.'?exe=guestbook&a=delanswer&id='.$mid);
		}elseif(System::user()->CheckAccess2('guestbook', 'answer')){
			$func2 = System::admin()->Link('Ответить', ADMIN_FILE.'?exe=guestbook&a=addanswer&id='.$mid);
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
		$text .= '<tr><th>'.TimeRender($msg['date']).'</th><th colspan="4" style="text-align:right;">'.$func2.'</th></tr>';
		$text .= '</table>';
	}
	AddText($text);
	if($nav){
		AddNavigation();
	}
}

// Премодерация
function AdminGuestBookPremoderationMain(){
	global $config;
	System::admin()->AddCenterBox('Премодерация');

	$premoderation = System::user()->CheckAccess2('guestbook', 'premoderation');
	$premoderate = System::database()->Select('guestbook', "`premoderate`='0'");
	if(System::database()->NumRows() == 0){
		System::admin()->Highlight('В премодерации нет сообщений.');
		return;
	}

	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 10, int);
	}else{
		$page = 1;
	}

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
	$back = SaveRefererUrl();
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
		$del = System::admin()->SpeedConfirm('Удалить сообщение', ADMIN_FILE.'?exe=guestbook&a=delete&id='.$mid.'&back='.$back, 'images/admin/delete.png', 'Удалить сообщение?');

		$func2 = '';
		$func2 = System::admin()->Link('Разрешить', ADMIN_FILE.'?exe=guestbook&a=prem_yes&id='.$mid.'&back='.$back);

		$text .= '<table cellspacing="0" cellpadding="0" class="commtable" style="width:75%;">';
		$text .= '<tr>
			<th style="text-align: left; width: 180px;">'.$name.'</th>
			<th style="width: 160px;">Сайт: '.$url.'</th>
			<th style="width: 120px;">ICQ: '.SafeDB($pre['icq'], 15, str).'</th>
			<th style="width: 120px;">IP: '.SafeDB($pre['user_ip'], 20, str).'</th>
			<th> '.$del.' </th>
		</tr>';
		$text .= '<tr><td colspan="5" style="text-align:left;padding:10px;" class="commtable_text">'.SafeDB($pre['message'], 0, str).'</td></tr>';
		$text .= '<tr><th>Дата: '.TimeRender($pre['date']).'</th><th colspan="4" style="text-align:right;">'.$func2.'</th></tr>';
		$text .= '</table>';
	}
	AddText($text);
	if($nav){
		AddNavigation();
	}
	AddText('<div style="text-align: center;">'.System::admin()->SpeedConfirm('Разрешить все', ADMIN_FILE.'?exe=guestbook&a=prem_yes_all&back='.$back, 'images/admin/accept.png', 'Разрешить все сообщения?', true, true).'&nbsp;'
		.System::admin()->SpeedConfirm('Удалить все', ADMIN_FILE.'?exe=guestbook&a=prem_del_all&back='.$back, 'images/admin/delete.png', 'Удалить все сообщения?', true, true).'</div>');
}

function AdminGuestBookPremoderationSave( $action ){
	global $config;
	if(!System::user()->CheckAccess2('guestbook', 'premoderation')){
		AddTextBox('Ошибка', $config['general']['admin_accd']);
		return;
	}
	if($action == 'prem_yes'){
		System::database()->Update('guestbook', "`premoderate`='1'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	}elseif($action == 'prem_yes_all'){
		System::database()->Update('guestbook', "`premoderate`='1'");
	}elseif($action == 'prem_del_all'){
		System::database()->Delete('guestbook', "`premoderate`='0'");
	}
	if(isset($_GET['back'])){
		GoRefererUrl($_GET['back']);
	}else{
		GO(ADMIN_FILE.'?exe=guestbook&a=premoderation');
	}
}

function AdminGuestBookMessageEditor(){
	global $config, $site;
	if(!System::user()->CheckAccess2('guestbook', 'edit')){
		AddTextBox('Ошибка', $config['general']['admin_accd']);
		return;
	}
	$id = SafeEnv($_GET['id'], 11, int);
	System::database()->Select('guestbook', "`id`='".$id."'");
	if(System::database()->NumRows() > 0){
		$msg = System::database()->FetchRow();
		FormRow('Имя', $site->Edit('name', SafeDB($msg['name'], 50, str), false, 'maxlength="50"'));
		FormRow('E-mail', $site->Edit('email', SafeDB($msg['email'], 50, str), false, 'maxlength="50"').'Скрыть '.$site->Check('hideemail', 'ok', $msg['hide_email']));
		FormRow('Сайт', $site->Edit('url', SafeDB($msg['url'], 250, str), false, 'maxlength="250"'));
		FormRow('Номер ICQ', $site->Edit('icq', SafeDB($msg['icq'], 15, str), false, 'maxlength="15"'));
		FormRow('Сообщение', $site->TextArea('message', SafeDB($msg['message'], 0, str), 'style="width:400px;height:200px;"'));
		AddCenterBox('Редактирование записи');
		if(isset($_GET['back'])){
			$back = '&back='.SafeDB($_GET['back'], 255, str);
		}else{
			$back = '';
		}
		AddForm('<form action="'.ADMIN_FILE.'?exe=guestbook&a=save&id='.$id.$back.'" method="POST">', $site->Button('Отмена', 'onclick="history.go(-1);"').$site->Submit('Сохранить'));
	}else{
		GO(ADMIN_FILE.'?exe=guestbook');
	}
}

function AdminGuestBookSave(){
	global $config;
	if(!System::user()->CheckAccess2('guestbook', 'edit')){
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
	System::database()->Update('guestbook', "name='$name',email='$email',hide_email='$hideemail',url='$url',icq='$icq',message='$message'", "`id`='$id'");

	if(isset($_GET['back'])){
		GoRefererUrl($_GET['back']);
	}else{
		GO(ADMIN_FILE.'?exe=guestbook');
	}
}

// Удаление сообщения
function AdminGuestBookDeleteMessage(){
	if(!System::user()->CheckAccess2('guestbook', 'edit')){
		AddTextBox('Ошибка', System::config('general/admin_accd'));
		return;
	}
	if(IsAjax() || isset($_GET['ok']) && $_GET['ok'] == '1'){
		System::database()->Delete('guestbook', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
		if(isset($_GET['back'])){
			GoRefererUrl($_GET['back']);
		}else{
			GO(ADMIN_FILE.'?exe=guestbook');
		}
	}else{
		System::admin()->AddCenterBox('Удаление сообщения');
		System::admin()->HighlightConfirmNoAjax('Удалить сообщение?', ADMIN_FILE.'?exe=guestbook&a=delete&id='.SafeDB($_REQUEST['id'], 11, int).'&ok=1'.'&back='.SafeDB($_REQUEST['back'], 255, str));
	}
}

function AdminGuestBookAnswerEditor(){
	global $config, $user, $site;
	if(!$user->CheckAccess2('guestbook', 'answer')){
		AddTextBox('Ошибка', $config['general']['admin_accd']);
		return;
	}
	$id = SafeEnv($_GET['id'], 11, int);
	System::database()->Select('guestbook', "`id`='".$id."'");
	if(System::database()->NumRows() > 0){
		AddCenterBox('Ответ на сообщение');
		$msg = System::database()->FetchRow();
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
		if(isset($_GET['back'])){
			$back = '&back='.SafeDB($_GET['back'], 255, str);
		}else{
			$back = '';
		}
		AddForm('<form action="'.ADMIN_FILE.'?exe=guestbook&a=saveanswer&id='.$id.$back.'" method="POST">', $site->Button('Отмена', 'onclick="history.go(-1);"').$site->Submit('Сохранить'));
	}else{
		GO(ADMIN_FILE.'?exe=guestbook');
	}
}

function AdminGuestBookAnswerSave(){
	global $config, $user;
	if(!$user->CheckAccess2('guestbook', 'answer')){
		AddTextBox('Ошибка', $config['general']['admin_accd']);
		return;
	}
	$id = SafeEnv($_GET['id'], 11, int);
	System::database()->Select('guestbook', "`id`='".$id."'");
	if(System::database()->NumRows() > 0){
		$msg = System::database()->FetchRow();
		if($msg['answers'] == ''){
			$answers = array();
		}else{
			$answers = unserialize($msg['answers']);
		}
		$answers[$user->Name()] = stripslashes(SafeEnv($_POST['answer'], 0, str));
		$answers = serialize($answers);
		System::database()->Update('guestbook', "answers='$answers'", "`id`='".$id."'");
	}
	if(isset($_GET['back'])){
		GoRefererUrl($_GET['back']);
	}else{
		GO(ADMIN_FILE.'?exe=guestbook');
	}
}

function AdminGuestBookDeleteAnswer(){
	global $config, $user;
	$id = SafeEnv($_GET['id'], 11, int);
	System::database()->Select('guestbook', "`id`='".$id."'");
	if(System::database()->NumRows() > 0){
		$msg = System::database()->FetchRow();
		if($msg['answers'] == ''){
			$answers = array();
		}else{
			$answers = unserialize($msg['answers']);
		}
		if(isset($answers[$user->Name()])){
			unset($answers[$user->Name()]);
		}
		$answers = serialize($answers);
		System::database()->Update('guestbook', "answers='$answers'", "`id`='".$id."'");
	}
	if(isset($_GET['back'])){
		GoRefererUrl($_GET['back']);
	}else{
		GO(ADMIN_FILE.'?exe=guestbook');
	}
}
