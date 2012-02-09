<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('Пользователи');

if(!$user->CheckAccess2('user', 'user')){
	AddTextBox('Ошибка', $config['general']['admin_accd']);
	return;
}

$editing = $user->CheckAccess2('user', 'editing');
$rankedit = $user->CheckAccess2('user', 'ranks');
$galeryedit = $user->CheckAccess2('user', 'avatars_gallery');
$confedit = $user->CheckAccess2('user', 'config');

if(isset($_GET['a'])){
	$action = $_GET['a'];
}else{
	$action = 'main';
}

TAddToolLink('Главная', 'main', 'user');
if($editing){
	TAddToolLink('Добавить пользователя', 'add', 'user&a=add');
}
if($confedit){
	TAddToolLink('Конфигурация модуля', 'config', 'user&a=config');
}
TAddToolBox($action);
TAddToolLink('Ранги пользователей', 'ranks', 'user&a=ranks');
if($rankedit){
	TAddToolLink('Система пунктов', 'points', 'user&a=points');
}
TAddToolLink('Галерея аватар', 'avatars', 'user&a=avatars');
TAddToolBox($action);

switch($action){
	case 'main':
		AdminUserMain();
		break;
	case 'add':
		if($editing){
			AdminUserEditor('user&a=addsave', 'add', 0, false);
			break;
		}
	case 'addsave':
		if($editing){
			AdminUserEditSave('user', 'addsave', 0, false);
			break;
		}
	case 'edituser':
		if($editing){
			AdminUserEditor('user&a=editsave', 'edit', SafeEnv($_GET['id'], 11, int), false);
			break;
		}
	case 'editsave':
		if($editing){
			AdminUserEditSave('user', 'update', SafeEnv($_GET['id'], 11, int), false);
			break;
		}
	case 'deluser':
		if($editing){
			AdminUserDelUser();
			break;
		}
	case 'ranks':
		AdminUserRanks();
		break;
	case 'editrank':
		if($rankedit){
			AdminUserEditRank();
			break;
		}
	case 'saverank':
	case 'addrank':
		if($rankedit){
			AdminUserRankSave($action);
			break;
		}
	case 'delrank':
		if($rankedit){
			AdminUserDeleteRank();
			break;
		}
	case 'avatars':
		AdminUserAvatarsGallery();
		break;
	case 'delavatar':
		if($galeryedit){
			AdminUserDeleteAvatar();
			break;
		}
	case 'saveavatar':
		if($galeryedit){
			AdminUserSaveAvatar();
			break;
		}
	case 'config':
		if($confedit){
			System::admin()->AddCenterBox('Конфигурация модуля "Пользователи"');
			if(isset($_GET['saveok'])){
				System::admin()->Highlight('Настройки сохранены.');
			}
			System::admin()->ConfigGroups('user');
			System::admin()->AddConfigsForm(ADMIN_FILE.'?exe=user&a=configsave');
			break;
		}
	case 'configsave':
		if($confedit){
			System::admin()->SaveConfigs('user');
			GO(ADMIN_FILE.'?exe=user&a=config&saveok');
			break;
		}
	case 'points':
		if($rankedit){
			System::admin()->AddCenterBox('Система пунктов');
			if(isset($_GET['saveok'])){
				System::admin()->Highlight('Настройки сохранены.');
			}
			System::admin()->ConfigGroups('points');
			System::admin()->AddConfigsForm(ADMIN_FILE.'?exe=user&a=pointsave');
			break;
		}
	case 'pointsave':
		if($rankedit){
			System::admin()->SaveConfigs('points');
			GO(ADMIN_FILE.'?exe=user&a=points&saveok');
			break;
		}
	default: System::admin()->HighlightError($config['general']['admin_accd']);
}


function AdminUserGetUsers( $where = "`type`='2'" ){
	return System::database()->Select('users', $where);
}

function AdminUserQueryStristrFilter( &$users, $str, $inez ){
	if($str == ''){
		return;
	}
	$newResult = array();
	foreach($users as $user){
		if(stristr($user[$inez], $str) !== false){
			$newResult[] = $user;
		}
	}
	$users = $newResult;
}

function AdminUserQueryStristrFilter2( &$users, $int, $inez ){
	if($str == ''){
		return;
	}
	$newResult = array();
	foreach($users as $user){
		if($user[$inez] >= $int){
			$newResult[] = $user;
		}
	}
	$users = $newResult;
}

function AdminUserMain(){
	global $db, $config, $site, $user, $editing;
	$searchm = false;

	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 10, int);
	}else{
		$page = 1;
	}
	if(isset($_GET['show'])){
		$show = $_GET['show'];
	}else{
		$show = '';
	}

	//Пользователи online
	$sonline = false;
	$onlwhere = '';
	$where = '`type`=\'2\'';
	if($show == 'online'){
		$donline = $user->Online();
		$donline = $donline['members'];
		$onlwhere = '';
		foreach($donline as $memb){
			$onlwhere .= "or `id`='".SafeDB($memb['u_id'], 11, int)."'";
		}
		$onlwhere = substr($onlwhere, 3);
		$sonline = true;
		if(count($donline) > 0){
			$where = "`type`='2' and ($onlwhere)";
			$users = AdminUserGetUsers($where);
		}else{
			$users = array();
		}
		$searchm = true;
	}else{
		$users = AdminUserGetUsers();
	}

	//Поиск
	$criterion = '';
	$sstr = '';
	if(isset($_GET['criterion']) && isset($_GET['stext']) && $_GET['stext'] != ''){
		$searchm = true;
		$criterion = $_GET['criterion'];
		$sstr = SafeEnv($_GET['stext'], 255, str);
		switch($criterion){
			case 'nikname': AdminUserQueryStristrFilter($users, $sstr, 'name');
				break;
			case 'email': AdminUserQueryStristrFilter($users, $sstr, 'email');
				break;
			case 'rname': AdminUserQueryStristrFilter($users, $sstr, 'truename');
				break;
			case 'age': AdminUserQueryStristrFilter($users, $sstr, 'age');
				break;
			case 'city': AdminUserQueryStristrFilter($users, $sstr, 'city');
				break;
			case 'site': AdminUserQueryStristrFilter($users, $sstr, 'url');
				break;
			case 'icq': AdminUserQueryStristrFilter($users, $sstr, 'icq');
				break;
			case 'gmt': AdminUserQueryStristrFilter($users, $sstr, 'timezone');
				break;
			case 'active': AdminUserQueryStristrFilter($users, $sstr, 'active');
				break;
			case 'points': AdminUserQueryStristrFilter2($users, $sstr, 'points');
				break;
			case 'ip': AdminUserQueryStristrFilter($users, $sstr, 'lastip');
				break;
		}
	}

	$sstr = strval($sstr);

	$showd = array();
	$site->DataAdd($showd, 'all', 'Все пользователи', $show == '');
	$site->DataAdd($showd, 'online', 'Пользователи OnLine', $show == 'online');

	$searchd = array();
	$site->DataAdd($searchd, 'nikname', 'Ник', $criterion == 'nikname');
	$site->DataAdd($searchd, 'email', 'E-mail', $criterion == 'email');
	$site->DataAdd($searchd, 'rname', 'Настоящее имя', $criterion == 'rname');
	$site->DataAdd($searchd, 'age', 'Возраст', $criterion == 'age');
	$site->DataAdd($searchd, 'city', 'Город', $criterion == 'city');
	$site->DataAdd($searchd, 'site', 'Сайт', $criterion == 'site');
	$site->DataAdd($searchd, 'icq', 'ICQ', $criterion == 'icq');
	$site->DataAdd($searchd, 'gmt', 'Часовой пояс', $criterion == 'gmt');
	$site->DataAdd($searchd, 'active', 'Активен', $criterion == 'active');
	$site->DataAdd($searchd, 'points', 'Пунктов более', $criterion == 'points');
	$site->DataAdd($searchd, 'ip', 'IP', $criterion == 'ip');

	System::admin()->AddJS('
	SearchUsers = function(){
		var cri = "&criterion="+$("#criterion").val();
		var stext = "&stext="+$("#stext").val();
		var online = "&show="+$("#online").val();
		Admin.LoadPage("'.ADMIN_FILE.'?exe=user"+cri+stext+online, undefined, "Идёт поиск");
	}
	');

	TAddSubTitle('Главная');
	AddCenterBox('Зарегистрированные пользователи ('.count($users).')');

	$serchtool = '<style>.ustd td{ border: none; padding: 0; }</style>';
	$serchtool .= '<table cellspacing="0" cellpadding="0" border="0" class="cfgtable"><tr><td>'."\n";
	$serchtool .= '<table cellspacing="0" cellpadding="0" border="0" width="100%" class="ustd">
	<tr>
	<td>Поиск: </td>
	<td>'.$site->Select('criterion', $searchd, false, 'id="criterion"').$site->Edit('stext', $sstr, false, 'id="stext"').$site->Select('show', $showd, false, 'id="online"').'</td>
	<td>'.System::admin()->SpeedConfirmJs('Поиск', 'SearchUsers();', 'images/search.png', '', true).'</td>
	</tr>
	</table>'."\n";
	$serchtool .= '</td></tr></table>'."\n";
	AddText($serchtool);

	SortArray($users, 'regdate', true); // Сортируем по дате регистрации
	if(count($users) > $config['user']['users_on_page']){
		$navigator = new Navigation($page);
		$navigator->GenNavigationMenu($users, $config['user']['users_on_page'], ADMIN_FILE.'?exe=user'.($searchm ? '&criterion='.$criterion.'&stext='.$sstr.'&show='.$show : ''));
		AddNavigation();
		$nav = true;
	}else{
		$nav = false;
		AddText('<br />');
	}

	$text = '';
	$text .= '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>Ник</th><th>E-mail</th><th>Дата региcтрации</th><th>Посл. посещение</th><th>Посещений</th><th>Пунктов</th><th>Активация</th><th>IP</th><th>Функции</th></tr>';
	foreach($users as $row){
		$uid = SafeDB($row['id'], 11, int);
		if($row['active'] == '1'){
			$active = 'Да';
		}elseif($row['active'] == '0' && $row['activate'] == ''){
			$active = 'Нет';
		}elseif($row['active'] == '0' && $row['activate'] != ''){
			$active = 'Ожидается';
		}
		$funcs = '';
		if($editing){
			$funcs .= System::admin()->SpeedButton('Редактировать', ADMIN_FILE.'?exe=user&a=edituser&id='.$uid, 'images/admin/edit.png');
			$funcs .= System::admin()->SpeedButton('Удалить', ADMIN_FILE.'?exe=user&a=deluser&id='.$uid, 'images/admin/delete.png'); // Всё верно
		}
		$text .= '<tr>
		<td>'.($editing ? '<b>'.System::admin()->Link(SafeDB($row['name'], 50, str), ADMIN_FILE.'?exe=user&a=edituser&id='.$uid).'</b>' : SafeDB($row['name'], 50, str)).'</td>
		<td>'.PrintEmail($row['email'], $row['name']).'</td>
		<td>'.TimeRender($row['regdate']).'</td>
		<td>'.TimeRender($row['lastvisit']).'</td>
		<td>'.SafeDB($row['visits'], 11, int).'</td>
		<td>'.SafeDB($row['points'], 11, int).'</td>
		<td>'.$active.'</td>
		<td>'.SafeDB($row['lastip'], 20, str).'</td>
		<td>'.$funcs.'</td>
		</tr>';
	}
	$text .= '</table>';

	AddText($text);
	if($nav){
		AddNavigation();
	}
}

function AdminUserDelUser(){
	global $config, $db, $site;

	if(isset($_GET['ok']) && $_GET['ok'] == '1'){
		$userid = SafeEnv($_GET['id'], 11, int);
		if(isset($_POST['del_comments']) && $_POST['del_comments'] == '1'){
			DeleteAllUserComments($userid);
		}else{
			$db->Select('users', "`id`='$userid'");
			$guser = $db->FetchRow();
			UpdateUserComments($userid, '0', SafeEnv($guser['name'], 50, str), SafeEnv($guser['email'], 50, str), SafeEnv($guser['hideemail'], 1, bool), SafeEnv($guser['url'], 250, str));
		}
		$db->Delete('users', "`id`='$userid'");
		// Очищаем кэш пользователей
		$cache = LmFileCache::Instance();
		$cache->Delete(system_cache, 'users');
		if(IsAjax()) exit("OK");
		GO(ADMIN_FILE.'?exe=user');
	}else{
		$userid = SafeEnv($_GET['id'], 11, int);
		$r = $db->Select('users', "`id`='".$userid."'");

		$userid = SafeDB($_GET['id'], 11, int);
		System::admin()->AddJS('
		AjaxDeleteUser = function(){
			Admin.ShowSplashScreen("Удаление пользователя");
			var del = $("#del_comments:checked").val();
			if(del == null){
				del = "0";
			}
			$.ajax({
				type: "POST",
				url: "'.ADMIN_FILE.'?exe=user&a=deluser&id='.$userid.'&ok=1",
				data: {del_comments: del},
				success: function(data){
					Admin.LoadPage("'.ADMIN_FILE.'?exe=user", undefined, "Обновление страницы");
					Admin.HideSplashScreen();
				}
			});
		};
		');

		AddCenterBox('Удаление пользователя');
		$Text = 'Вы действительно хотите удалить пользователя "'.$r[0]['name'].'"?';
		$Text .= '<br /><br />'
			.System::admin()->Check('del_comments', '1', false, 'id="del_comments"').'<label for="del_comments" style="cursor: pointer;">Удалить все комментарии этого пользователя</label><br /><br />'
			.System::admin()->SpeedButton('Отмена', 'javascript:history.go(-1)', 'images/admin/delete.png', false, true)
			.'&nbsp;&nbsp;'
			.System::admin()->SpeedConfirmJs('Да', 'AjaxDeleteUser();', 'images/admin/accept.png', '', true);
		System::admin()->Highlight($Text);
	}
}

function AdminUserRanks(){
	global $config, $site, $rankedit;
	TAddSubTitle('Ранги пользователей');
	$users = System::database()->Select('users', "`type`='2'");
	foreach($users as $u){
		$r = GetUserRank($u['points'], $u['type'], $u['access']);
		if(!isset($rcounts[$r[2]])){
			$rcounts[$r[2]] = 0;
		}
		$rcounts[$r[2]]++;
	}

	$ranks = System::database()->Select('userranks', '');
	SortArray($ranks, 'min');
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>Ранг</th><th>Мин. пунктов</th><th>Участников</th><th>Изображение</th><th>Функции</th></tr>';
	foreach($ranks as $rank){
		if(file_exists($config['general']['ranks_dir'].$rank['image']) && is_file($config['general']['ranks_dir'].$rank['image'])){
			$image = '<img src="'.RealPath2(SafeDB($config['general']['ranks_dir'].$rank['image'], 255, str)).'" border="0" />';
		}else{
			$image = '';
		}
		$funcs = '';
		if($rankedit){
			$funcs .= System::admin()->SpeedButton('Редактировать', ADMIN_FILE.'?exe=user&a=editrank&id='.SafeDB($rank['id'], 11, int), 'images/admin/edit.png');
			$funcs .= System::admin()->SpeedConfirm('Удалить', ADMIN_FILE.'?exe=user&a=delrank&id='.SafeDB($rank['id'], 11, int), 'images/admin/delete.png', 'Удалить ранг?');
		}else{
			$funcs .= '&nbsp;';
		}
		$text .= '<tr>
			<td>'.SafeDB($rank['title'], 250, str).'</td>
			<td>'.SafeDB($rank['min'], 11, int).'</td>
			<td>'.(isset($rcounts[$rank['id']]) ? $rcounts[$rank['id']] : '0').'</td>
			<td>'.$image.'</td>
			<td>'.$funcs.'</td>
			</tr>';
	}
	$text .= '</table>';
	AddCenterBox('Ранги пользователей');
	AddText($text);
	if($rankedit){
		System::admin()->FormTitleRow('Добавить ранг');
		FormRow('Название ранга', $site->Edit('rankname', '', false, 'style="width:180px;"'));
		FormRow('Изображение', $site->Edit('rankimage', '', false, 'style="width:180px;"'));
		FormRow('Минимальное количество пунктов для вступления', $site->Edit('minpoints', '0', false, 'style="width:60px;"'));
		AddForm('<form name="addrang" method="post" action="'.ADMIN_FILE.'?exe=user&a=addrank">', $site->Submit('Добавить')).'<br />';
	}
}

function AdminUserEditRank(){
	global $site;
	$id = SafeEnv($_GET['id'], 11, int);
	System::database()->Select('userranks', "`id`='$id'");
	$thrank = System::database()->FetchRow();
	FormRow('Название ранга', $site->Edit('rankname', SafeDB($thrank['title'], 250, str), false, 'style="width:180px;"'));
	FormRow('Изображение', $site->Edit('rankimage', SafeDB($thrank['image'], 250, str), false, 'style="width:180px;"'));
	FormRow('Минимальное количество пунктов для вступления', $site->Edit('minpoints', SafeDB($thrank['min'], 11, int), false, 'style="width:60px;"'));
	AddCenterBox('Редактирование ранга');
	AddForm('<form name="addrang" method="post" action="'.ADMIN_FILE.'?exe=user&a=saverank&id='.$id.'">', $site->Button('Отмена', 'onclick="history.go(-1)"').$site->Submit('Сохранить изменения'));
}

function AdminUserRankSave( $action ){
	$rankname = SafeEnv($_POST['rankname'], 250, str);
	$rankimage = SafeEnv($_POST['rankimage'], 250, str);
	$minpoints = SafeEnv($_POST['minpoints'], 11, int);
	if($action == 'addrank'){
		System::database()->Insert('userranks', Values('', $rankname, $minpoints, $rankimage));
	}elseif($action == 'saverank'){
		System::database()->Update('userranks', "title='$rankname',min='$minpoints',image='$rankimage'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	}
	// Очищаем кэш
	$cache = LmFileCache::Instance();
	$cache->Delete(system_cache, 'userranks');
	GO(ADMIN_FILE.'?exe=user&a=ranks');
}

function AdminUserDeleteRank(){
	System::database()->Delete('userranks', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	// Очищаем кэш
	$cache = LmFileCache::Instance();
	$cache->Delete(system_cache, 'userranks');
	GO(ADMIN_FILE.'?exe=user&a=ranks');
}

function AdminUserAvatarsGallery(){
	global $config, $site, $galeryedit, $db;
	TAddSubTitle('Галерея аватар');
	if(isset($_GET['user']) && $_GET['user'] == '1'){
		$personal = true;
		$dir = $config['general']['personal_avatars_dir'];
		$dirlink = System::admin()->Link('Показать аватары из галереи', ADMIN_FILE.'?exe=user&a=avatars');
		$users = $db->Select('users', "`type`='2'");
		$c = sizeof($users);
		for($i = 0; $i < $c; $i++){
			$users[$users[$i]['avatar']] = $i;
		}
	}else{
		$personal = false;
		$dir = $config['general']['avatars_dir'];
		$dirlink = System::admin()->Link('Показать аватары пользователей', ADMIN_FILE.'?exe=user&a=avatars&user=1');
	}
	$avatars2 = GetFiles($dir, false, true, '.gif.jpg.jpeg.png');
	$avatars = array();
	foreach($avatars2 as $av){
		$name = GetFileName($av);
		$sub = substr($name, -3);
		if($sub != 'x24' && $sub != 'x64'){
			$avatars[] = $av;
		}
	}
	$c = count($avatars);
	$allsize = 0;
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	if($c > 0){
		$col = 0;
		for($i = 0; $i < $c; $i++){
			if($col == 0){
				$text .= '<tr>';
			}
			$col++;
			$imagfn = $dir.$avatars[$i];
			$size = getimagesize($imagfn);
			$fsize = filesize($imagfn);
			$allsize = $allsize + $fsize;
			if($galeryedit){
				$funcs = System::admin()->SpeedConfirm('Удалить', ADMIN_FILE.'?exe=user&a=delavatar&filename='.$avatars[$i].($personal ? '&personal' : ''), 'images/admin/delete.png', 'Удалить аватар?');
			}else{
				$funcs = '&nbsp;';
			}
			$text .= '<td align="center">
				<table cellspacing="0" cellpadding="0" align="center" style="border:none; background: none;">
				<tr style="border:none; background: none;">
					<td style="border:none; background: none;"><a href="'.$imagfn.'" target="_blank"><img src="'.$imagfn.'" border="0" width="64" title="('.$size[0].' x '.$size[1].', '.FormatFileSize($fsize).') '.$avatars[$i].'" /></a></td>
					<td valign="top" style="border:none; background: none;">'.$funcs.'</td>
				</tr>
				';
			if($personal && isset($users[$avatars[$i]])){
				$text .= '<tr><td colspan="2" align="left" style="border:none; background: none;"><a href="'.ADMIN_FILE.'?exe=user&a=edituser&id='.SafeDB($users[$users[$avatars[$i]]]['id'], 11, int).'">'.SafeDB($users[$users[$avatars[$i]]]['name'], 255, str).'</a></td></tr>';
			}
			$text .= '</table></td>';
			if($col == 5){
				$text .= '</tr>';
				$col = 0;
			}
		}
		if($col < 5){
			$text .= '<td colspan="'.(5-$col).'"></td>';
			$text .= '</tr>';
		}
	}else{
		$text .= '<tr><td>В галерее нет ни одного аватара.</td></tr>';
	}
	$text .= '</table>';
	$info = '<table cellspacing="0" cellpadding="0" border="0" class="cfgtable">
		<tr>
		<td width="34%">Аватар в галерее: '.$c.'</td>
		<td width="33%">Общий размер: '.FormatFileSize($allsize).'</td>
		<td>'.$dirlink.'</td>
		</tr>
	</table>';
	$text = $info.$text;
	AddCenterBox('Галерея аватар', $text);
	AddText($text);

	if(!$personal && $galeryedit){
		System::admin()->FormTitleRow('Загрузить аватар');
		FormRow('Выберите файл', $site->FFile('avatar'));
		AddForm($site->FormOpen(ADMIN_FILE.'?exe=user&a=saveavatar', 'post', true), $site->Submit('Загрузить'));
	}
	AddText('<br />');
}

function AdminUserSaveAvatar(){
	global $config;
	$alloy_mime = array('image/gif' => '.gif', 'image/jpeg' => '.jpg', 'image/pjpeg' => '.jpg', 'image/png' => '.png', 'image/x-png' => '.png');
	include_once($config['inc_dir'].'picture.class.php');
	$asize = getimagesize($_FILES['avatar']['tmp_name']);
	$alloy_mime = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png');
	$alloy_exts = array('.gif', '.jpg', '.jpeg', '.png');
	if(in_array($_FILES['avatar']['type'], $alloy_mime) && in_array(strtolower(GetFileExt($_FILES['avatar']['name'])), $alloy_exts)){
		$NewName = $_FILES['avatar']['name'];
		if($asize[0] > $config['user']['max_avatar_width'] || $asize[1] > $config['user']['max_avatar_height']){
			$thumb = new TPicture($_FILES['avatar']['tmp_name']);
			$thumb->SetImageSize($config['user']['max_avatar_width'], $config['user']['max_avatar_height']);
			$thumb->SaveToFile($config['general']['avatars_dir'].$NewName);
		}else{
			copy($_FILES['avatar']['tmp_name'], $config['general']['avatars_dir'].$NewName);
		}
	}else{
		System::admin()->AddCenterBox('Загрузка аватара');
		System::admin()->HighlightError('Неправильный формат аватара. Ваш аватар должен быть формата GIF, JPEG или PNG.<br /><a href="javascript:history.go(-1)">Назад в галерею</a>');
	}
	GO(ADMIN_FILE.'?exe=user&a=avatars');
}

function AdminUserDeleteAvatar(){
	global $config, $db;
	if(isset($_GET['personal'])){
		$dir = $config['general']['personal_avatars_dir'];
		$personal = true;
	}else{
		$dir = $config['general']['avatars_dir'];
		$personal = false;
	}
	$avatar = SafeEnv($_GET['filename'], 250, str);
	$filename = RealPath2($dir.$avatar);
	if(file_exists($filename) && is_file($filename)){
		unlink($filename);
	}
	if($personal){
		$db->Update('users', "a_personal='0',avatar=''", "`a_personal`='1' and `avatar`='$avatar'");
	}
	GO(ADMIN_FILE.'?exe=user&a=avatars');
}
