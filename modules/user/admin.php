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

function AdminUserGetUsers( $where = '`type`=\'2\'' )
{
	global $config, $db;
	return $db->Select('users', $where);
}

function AdminUserQueryStristrFilter( $str, $inez )
{
	global $db;
	if($str == ''){
		return;
	}
	$newResult = array();
	foreach($db->QueryResult as $user){
		if(stristr($user[$inez], $str) !== false){
			$newResult[] = $user;
		}
	}
	$db->QueryResult = $newResult;
}

function AdminUserMain()
{
	global $db, $config, $site, $user, $editing;
	$db->FreeResult();
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
	$showd = array();
	$site->DataAdd($showd, 'all', 'Все пользователи', $show == '');
	$site->DataAdd($showd, 'online', 'Пользователи OnLine', $show == 'online');

	//Пользователи online
	$sonline = false;
	$onlwhere = '';
	$where = '`type`=\'2\'';
	if(isset($_GET['show'])){
		if($_GET['show'] == 'online'){
			$donline = $user->Online();
			$donline = $donline['members'];
			$onlwhere = '';
			foreach($donline as $memb){
				$onlwhere .= "or `id`='".SafeDB($memb['u_id'], 11, int)."'";
			}
			$onlwhere = substr($onlwhere, 3);
			$sonline = true;
			if(count($donline) > 0){
				$where = '`type`=\'2\' and ('.$onlwhere.')';
			}else{
				$where = '`type`=\'2\' and `id`=\'-1\'';
			}
			AdminUserGetUsers($where);
		}
	}

	//Поиск
	$searchm = false;
	$criterion = '';
	$sstr = '';
	if(!$sonline && isset($_GET['criterion']) && isset($_GET['stext'])){
		$searchm = true;
		$criterion = $_GET['criterion'];
		$sstr = $_GET['stext'];
		//генерируем where
		switch($criterion){
			case 'nikname':
				$sstr = SafeEnv($sstr, 50, str);
				AdminUserGetUsers();
				AdminUserQueryStristrFilter($sstr, 3);
				break;
			case 'email':
				$sstr = SafeEnv($sstr, 50, str);
				AdminUserGetUsers();
				AdminUserQueryStristrFilter($sstr, 6);
				break;
			case 'rname':
				$sstr = SafeEnv($sstr, 250, str);
				AdminUserGetUsers();
				AdminUserQueryStristrFilter($sstr, 4);
				break;
			case 'age':
				$sstr = SafeEnv($sstr, 1, int);
				AdminUserGetUsers('`type`=\'2\' and `age`=\''.$sstr.'\'');
				break;
			case 'city':
				$sstr = SafeEnv($sstr, 100, str);
				AdminUserGetUsers();
				AdminUserQueryStristrFilter($sstr, 8);
				break;
			case 'site':
				$sstr = SafeEnv($sstr, 250, str);
				AdminUserGetUsers();
				AdminUserQueryStristrFilter($sstr, 10);
				break;
			case 'icq':
				$sstr = SafeEnv($sstr, 15, str);
				AdminUserGetUsers();
				AdminUserQueryStristrFilter($sstr, 9);
				break;
			case 'gmt':
				$sstr = SafeEnv($sstr, 3, str);
				AdminUserGetUsers('`type`=\'2\' and `timezone`=\''.$sstr.'\'');
				break;
			case 'active':
				$sstr = SafeEnv($sstr, 1, int);
				AdminUserGetUsers('`type`=\'2\' and `active`=\''.$sstr.'\'');
				break;
			case 'points':
				$sstr = SafeEnv($sstr, 11, int);
				AdminUserGetUsers("`type`='2' and (`points`='$sstr' or `points`>'$sstr')");
				break;
			case 'ip':
				$sstr = SafeEnv($sstr, 15, str);
				AdminUserGetUsers();
				AdminUserQueryStristrFilter($sstr, 9);
				break;
		}
	}
	$sstr = strval($sstr);
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
	if(!$sonline && !$searchm){
		AdminUserGetUsers();
	}
	TAddSubTitle('Главная');
	AddCenterBox('Зарегистрированные пользователи');
	if($searchm){
		$c = 'Найдено: '.$db->NumRows();
	}else{
		$c = 'Зарегистрированных пользователей: '.$db->NumRows();
	}
	$serchtool = '<style>.ustd td{ border: none; padding: 0; }</style>';
	$serchtool .= '<table cellspacing="0" cellpadding="0" border="0" class="cfgtable"><tr><td>'."\n";
	$serchtool .= '<form method="get">'.$site->Hidden('exe', 'user').'<table cellspacing="0" cellpadding="0" border="0" width="100%" class="ustd"><tr><td>'.$c.'</td><td>Показать: '.$site->Select('show', $showd).'</td><td>'.$site->Submit('Обновить список').'</td></tr></table></form>'."\n";
	$serchtool .= '</td></tr><tr><td>'."\n";
	$serchtool .= '<form method="get">'.$site->Hidden('exe', 'user').'<table cellspacing="0" cellpadding="0" border="0" width="100%" class="ustd"><tr><td>Поиск: </td><td>'.$site->Select('criterion', $searchd).$site->Edit('stext', $sstr).'</td><td>'.$site->Submit('Поиск').'</td></tr></table></form>'."\n";
	$serchtool .= '</td></tr></table>'."\n";
	AddText($serchtool);
	SortArray($db->QueryResult, 'regdate', true); // Сортируем по дате регистрации
	if(count($db->QueryResult) > $config['user']['users_on_page']){
		$navigator = new Navigation($page);
		$navigator->GenNavigationMenu($db->QueryResult, $config['user']['users_on_page'], $config['admin_file'].'?exe=user'.($searchm ? '&criterion='.$criterion.'&stext='.$sstr : ''));
		AddNavigation();
		$nav = true;
	}else{
		$nav = false;
		AddText('<br />');
	}
	$text = '';
	$text .= '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>Ник</th><th>E-mail</th><th>Дата региcтрации</th><th>Посл. посещение</th><th>Посещений</th><th>Пунктов</th><th>Активация</th><th>IP</th><th>Функции</th></tr>';
	while($row = $db->FetchRow()){
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
			$funcs .= SpeedButton('Редактировать', $config['admin_file'].'?exe=user&a=edituser&id='.$uid, 'images/admin/edit.png');
		}
		$funcs .= SpeedButton('Удалить', $config['admin_file'].'?exe=user&a=deluser&id='.$uid, 'images/admin/delete.png');

		$text .= '
		<tr>
		<td>'.($editing ? '<a href="'.$config['admin_file'].'?exe=user&a=edituser&id='.$uid.'">' : '').'<b>'.SafeDB($row['name'], 50, str).'</b>'.($editing ? '</a>' : '').'</td>
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

function AdminUserDelUser()
{
	global $config, $db, $site;
	if(isset($_GET['ok']) && $_GET['ok'] == '1'){
		$id = SafeEnv($_GET['id'], 11, int);
		if(!isset($_POST['del_comments'])){
			$db->Select('users', "`id`='$id'");
			$guser = $db->FetchRow();
			UpdateUserComments($id, '0', SafeEnv($guser['name'], 50, str), SafeEnv($guser['email'], 50, str), SafeEnv($guser['hideemail'], 1, bool), SafeEnv($guser['url'], 250, str));
		}else{
			DeleteAllUserComments($id);
		}
		$db->Delete('users', "`id`='$id'");

		// Очищаем кэш пользователей
		$cache = LmFileCache::Instance();
		$cache->Delete(system_cache, 'users');

		GO($config['admin_file'].'?exe=user');
	}else{
		$r = $db->Select('users', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
		$text = '<form action="'.$config['admin_file'].'?exe=user&a=deluser&id='.SafeEnv($_GET['id'], 11, int).'&ok=1" method="post">
			<br />Вы действительно хотите удалить пользователя "'.$r[0]['name'].'"?<br />'
			.$site->Check('del_comments', '1', false, 'id="del_comments"').'<label for="del_comments">Удалить все комментарии этого пользователя.</label><br /><br />'
			.$site->Button('Отмена', 'onclick="history.go(-1)"').'&nbsp;'.$site->Submit('Удалить').'</form><br />';
		AddTextBox("Предупреждение", $text);
	}
}

function AdminUserRanks()
{
	global $config, $db, $site, $rankedit;
	TAddSubTitle('Ранги пользователей');

	$users = $db->Select('users', "`type`='2'");
	foreach($users as $u){
		$r = GetUserRank($u['points'], $u['type'], $u['access']);
		if(!isset($rcounts[$r[2]])){
			$rcounts[$r[2]] = 0;
		}
		$rcounts[$r[2]]++;
	}

	$ranks = $db->Select('userranks', '');
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
			$funcs .= SpeedButton('Редактировать', $config['admin_file'].'?exe=user&a=editrank&id='.SafeDB($rank['id'], 11, int), 'images/admin/edit.png');
			$funcs .= SpeedButton('Удалить', $config['admin_file'].'?exe=user&a=delrank&id='.SafeDB($rank['id'], 11, int), 'images/admin/delete.png');
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
		FormRow('Название ранга', $site->Edit('rankname', '', false, 'style="width:140px;"'));
		FormRow('Изображение', $site->Edit('rankimage', '', false, 'style="width:180px;"'));
		FormRow('Минимальное количество пунктов<br />для вступления', $site->Edit('minpoints', '0', false, 'style="width:60px;"'));
		AddText('<br /><center>.: Добавить ранг :.</center>');
		AddForm('<form name="addrang" method="post" action="'.$config['admin_file'].'?exe=user&a=addrank">', $site->Submit('Добавить')).'<br />';
	}
}

function AdminUserEditRank()
{
	global $db, $config, $site;
	$id = SafeEnv($_GET['id'], 11, int);
	$db->Select('userranks', "`id`='$id'");
	$thrank = $db->FetchRow();
	FormRow('Название ранга', $site->Edit('rankname', SafeDB($thrank['title'], 250, str), false, 'style="width:140px;"'));
	FormRow('Изображение', $site->Edit('rankimage', SafeDB($thrank['image'], 250, str), false, 'style="width:180px;"'));
	FormRow('Минимальное количество пунктов<br />для вступления', $site->Edit('minpoints', SafeDB($thrank['min'], 11, int), false, 'style="width:60px;"'));
	AddCenterBox('Редактирование ранга');
	AddForm('<form name="addrang" method="post" action="'.$config['admin_file'].'?exe=user&a=saverank&id='.$id.'">', $site->Button('Отмена', 'onclick="history.go(-1)"').$site->Submit('Сохранить изменения'));
}

function AdminUserRankSave( $action )
{
	global $config, $db;
	$rankname = SafeEnv($_POST['rankname'], 250, str);
	$rankimage = SafeEnv($_POST['rankimage'], 250, str);
	$minpoints = SafeEnv($_POST['minpoints'], 11, int);
	if($action == 'addrank'){
		$db->Insert('userranks', Values('', $rankname, $minpoints, $rankimage));
	}elseif($action == 'saverank'){
		$db->Update('userranks', "title='$rankname',min='$minpoints',image='$rankimage'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	}

	// Очищаем кэш
	$cache = LmFileCache::Instance();
	$cache->Delete(system_cache, 'userranks');

	GO($config['admin_file'].'?exe=user&a=ranks');
}

function AdminUserDeleteRank()
{
	global $config, $db;
	if(isset($_GET['ok']) && $_GET['ok'] == '1'){
		$db->Delete('userranks', "`id`='".SafeEnv($_GET['id'], 11, int)."'");

		// Очищаем кэш
		$cache = LmFileCache::Instance();
		$cache->Delete(system_cache, 'userranks');

		GO($config['admin_file'].'?exe=user&a=ranks');
	}else{
		TAddSubTitle('Удаление ранга');
		$r = $db->Select('userranks', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
		$text = 'Вы действительно хотите удалить ранг "'.SafeDB($r[0]['title'], 250, str).'"<br />'
			.'<a href="'.$config['admin_file'].'?exe=user&a=delrank&id='.SafeEnv($_GET['id'], 11, int).'&ok=1">Да</a>'
			.' &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">Нет</a>';
		AddTextBox("Внимание!", $text);
	}
}

function AdminUserAvatarsGallery()
{
	global $config, $site, $galeryedit, $db;
	TAddSubTitle('Галерея аватар');
	if(isset($_GET['user']) && $_GET['user'] == '1'){
		$personal = true;
		$dir = $config['general']['personal_avatars_dir'];
		$dirlink = '<a href="'.$config['admin_file'].'?exe=user&a=avatars">Показать аватары из галереи</a>';
		$users = $db->Select('users', "`type`='2'");
		$c = sizeof($users);
		for($i = 0; $i < $c; $i++){
			$users[$users[$i]['avatar']] = $i;
		}
	}else{
		$personal = false;
		$dir = $config['general']['avatars_dir'];
		$dirlink = '<a href="'.$config['admin_file'].'?exe=user&a=avatars&user=1">Показать аватары пользователей</a>';
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
				$funcs = SpeedButton('Удалить', $config['admin_file'].'?exe=user&a=delavatar&filename='.$avatars[$i].($personal ? '&personal' : ''), 'images/admin/delete.png');
			}else{
				$funcs = '&nbsp;';
			}
			
			$text .= '
			<td align="center">
				<table cellspacing="0" cellpadding="0" align="center">
				<tr>
				<td style="border:none"><a href="'.$imagfn.'" target="_blank"><img src="'.$imagfn.'" border="0" width="64" title="('.$size[0].' x '.$size[1].', '.FormatFileSize($fsize).') '.$avatars[$i].'" /></a></td>
				<td valign="top" style="border:none">'.$funcs.'</td>
				</tr>
				<tr>
				<td colspan="2" align="left" style="border:none">'.(($personal && isset($users[$avatars[$i]])) ? '<a href="'.$config['admin_file'].'?exe=user&a=edituser&id='.SafeDB($users[$users[$avatars[$i]]]['id'], 11, int).'">'.SafeDB($users[$users[$avatars[$i]]]['name'], 255, str).'</a>' : '').'</td>
				</tr>
				</table>
			</td>';
			if($col == 5){
				$text .= '</tr>';
				$col = 0;
			}
		}
		if($col < 5){
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
		$text .= '<br />.: Загрузить аватар :.';
		FormRow('Имя файла', $site->FFile('avatar'));
		AddForm($site->FormOpen($config['admin_file'].'?exe=user&a=saveavatar', 'post', 'multipart/form-data'), $site->Submit('Загрузить'));
	}
	AddText('<br />');
}

function AdminUserSaveAvatar()
{
	global $config;
	$alloy_mime = array('image/gif'=>'.gif', 'image/jpeg'=>'.jpg', 'image/pjpeg'=>'.jpg', 'image/png'=>'.png', 'image/x-png'=>'.png');
	if(isset($_FILES['avatar'])){
		if(isset($alloy_mime[$_FILES['avatar']['type']]) && $alloy_mime[$_FILES['avatar']['type']] == strtolower(GetFileExt($_FILES['avatar']['name']))){
			copy($_FILES['avatar']['tmp_name'], $config['general']['avatars_dir'].$_FILES['avatar']['name']);
		}elseif(!file_exists($_FILES['avatar']['tmp_name'])){
			AddTextBox('Ошибка', '<center>Вы не выбрали файл для загрузки.<br /><a href="javascript:history.go(-1)">Назад в галерею</a></center>');
			return;
		}else{
			AddTextBox('Ошибка', '<center>Неправильный формат файла. Можно загружать только изображения формата GIF, JPEG или PNG.<br /><a href="javascript:history.go(-1)">Назад в галерею</a></center>');
			return;
		}
	}
	GO($config['admin_file'].'?exe=user&a=avatars');
}

function AdminUserDeleteAvatar()
{
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
	if(isset($_GET['ok']) && $_GET['ok'] == '1'){
		if(file_exists($filename) && is_file($filename)){
			unlink($filename);
		}
		if($personal){
			$db->Update('users', "a_personal='0',avatar=''", "`a_personal`='1' and `avatar`='$avatar'");
		}
		GO($config['admin_file'].'?exe=user&a=avatars');
		exit();
	}else{
		TAddSubTitle('Удаление аватара');
		if(file_exists($filename) && is_file($filename)){
			$text = '<table cellspacing="0" cellpadding="5" border="0" align="center"><tr><td align="center">'.'<img src="'.$filename.'" border="0" /></tr></td><tr><td align="center">'.'Аватар будет удален физически с жесткого диска. Продолжить?<br />'.'<a href="'.$config['admin_file'].'?exe=user&a=delavatar&filename='.SafeEnv($_GET['filename'], 250, str).'&ok=1'.($personal ? '&personal' : '').'">Да</a> &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">Нет</a><br /><br />'.'</td></tr></table>';
		}else{
			$text = '<center>Аватар, который вы пытаетесь удалить, не найден в папке с аватарами.<br /><a href="javascript:history.go(-1)">Назад в галерею</a></center>';
		}
		AddTextBox("Внимание!", $text);
	}
}
include_once ($config['apanel_dir'].'configuration/functions.php');

function AdminUser( $action )
{
	global $config, $editing, $rankedit, $galeryedit, $confedit;
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
			return true;
			break;
		case 'add':
			if($editing){
				include_once ($config['apanel_dir'].'members.php');
				AdminUserEditor('user&a=addsave', 'add', 0, false);
				return true;
			}
			break;
		case 'addsave':
			if($editing){
				include_once ($config['apanel_dir'].'members.php');
				AdminUserEditSave('user', 'addsave', 0, false);
				return true;
			}
			break;
		case 'edituser':
			if($editing){
				include_once ($config['apanel_dir'].'members.php');
				AdminUserEditor('user&a=editsave', 'edit', SafeEnv($_GET['id'], 11, int), false);
				return true;
			}
			break;
		case 'editsave':
			if($editing){
				include_once ($config['apanel_dir'].'members.php');
				AdminUserEditSave('user', 'update', SafeEnv($_GET['id'], 11, int), false);
				return true;
			}
			break;
		case 'deluser':
			if($editing){
				AdminUserDelUser();
				return true;
			}
			break;
		case 'ranks':
			AdminUserRanks();
			return true;
			break;
		case 'editrank':
			if($rankedit){
				AdminUserEditRank();
				return true;
			}
			break;
		case 'saverank':
		case 'addrank':
			if($rankedit){
				AdminUserRankSave($action);
				return true;
			}
			break;
		case 'delrank':
			if($rankedit){
				AdminUserDeleteRank();
				return true;
			}
			break;
		case 'avatars':
			AdminUserAvatarsGallery();
			return true;
			break;
		case 'delavatar':
			if($galeryedit){
				AdminUserDeleteAvatar();
				return true;
			}
			break;
		case 'saveavatar':
			if($galeryedit){
				AdminUserSaveAvatar();
				return true;
			}
			break;
		case 'config':
			if($confedit){
				global $config, $site;
				include_once ($config['apanel_dir'].'configuration/functions.php');
				AdminConfigurationEdit('user', 'user', true, false, 'Конфигурация модуля "Пользователи"');
				return true;
			}
			break;
		case 'configsave':
			if($confedit){
				global $config;
				include_once ($config['apanel_dir'].'configuration/functions.php');
				AdminConfigurationSave('user&a=config', 'user', true);
				return true;
			}
			break;
		case 'points':
			if($rankedit){
				AdminConfigurationEdit('user', 'points', true, false, 'Система пунктов', 'a=pointsave');
				return true;
			}
			break;
		case 'pointsave':
			if($rankedit){
				AdminConfigurationSave('user&a=points', 'points', true);
				return true;
			}
			break;
		default:
			return false;
	}
	return false;
}

if(isset($_GET['a'])){
	$a = $_GET['a'];
}else{
	$a = 'main';
}

if(!AdminUser($a)){
	AddTextBox('Ошибка', $config['general']['admin_accd']);
	return;
}

?>