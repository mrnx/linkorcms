<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('Администраторы');

if(!$user->isSuperUser()){
	AddTextBox('Ошибка', $config['general']['admin_accd']);
	return;
}

function AdminsGenAccessStr( &$useraccess, &$accesses, $system = false )
{
	$msg = '';
	if($system == '1'){
		$msg = 'Системный  ';
	}elseif(trim($useraccess) == 'ALL'){
		$msg = 'Полный  ';
	}else{
		$useraccess = unserialize($useraccess);
		$keys = array_keys($useraccess);
		for($i = 0, $c = count($keys); $i < $c; $i++){
			for($j = 0, $k = count($useraccess[$keys[$i]]); $j < $k; $j++){
				$msg .= $accesses[$keys[$i]][$useraccess[$keys[$i]][$j]].', ';
			}
		}
	}
	$len = strlen($msg);
	$msg = substr($msg, 0, $len - 2);
	$msg .= '.';
	return $msg;
}

function AdminsGetAccessArray( $useraccess )
{
	global $config, $db;
	$all = (trim($useraccess) == 'ALL');
	$result['ALL'][] = array('ALL', 'ALL', '<b><font color="#FF0000">Полный доступ</font></b>', $all);
	if(!$all){
		$useraccess = unserialize($useraccess);
	}else{
		$useraccess = array();
	}
	$accesses = $db->Select('access', '');
	foreach($accesses as $ac){
		$access[] = array(
			SafeDB($ac['group'], 255, str),
			SafeDB($ac['name'], 255, str),
			($ac['group'] == $ac['name'] ? '<b>'.SafeDB($ac['description'], 255, str).'</b>' : SafeDB($ac['description'], 255, str))
		);
	}
	unset($accesses);
	$keys = array_keys($access);
	for($i = 0, $c = count($keys); $i < $c; $i++){
		$s = $all || (isset($useraccess[$access[$i][0]]) && in_array($access[$i][1], $useraccess[$access[$i][0]]));
		$result[$access[$i][0]][] = array($access[$i][0], $access[$i][1], $access[$i][2], $s);
	}
	return $result;
}

function AdminsMain()
{
	global $config, $db;
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable"><tr><th>Имя</th><th>E-mail</th><th>Группа</th><th>Посл. посещение</th><th>Посещений</th><th>Функции</th></tr>';
	$atypes = $db->Select('usertypes', '');
	foreach($atypes as $type){
		$types[SafeDB($type['id'], 11, int)] = array('<font color="'.SafeDB($type['color'], 9, str).'">'.SafeDB($type['name'], 255, str).'</font>', SafeDB($type['system'], 1, bool));
	}
	unset($atypes);
	$admins = $db->Select('users', "`type`='1'");
	//Подсчитываем количество главных администраторов
	$system = 0;
	for($i = 0, $c = count($admins); $i < $c; $i++){
		if($types[SafeDB($admins[$i]['access'], 11, int)][1] == '1'){
			$system++;
		}
	}

	foreach($admins as $adm){
		$funcs = '';
		$funcs .= SpeedButton('Редактировать', $config['admin_file'].'?exe=admins&a=editadmin&id='.SafeDB($adm['id'], 11, int), 'images/admin/edit.png');
		if($system > 1 || SafeDB($adm['access'], 11, int) == 0){
			$funcs .= SpeedButton('Удалить или перевести в пользователи', $config['admin_file'].'?exe=admins&a=deladmin&id='.SafeDB($adm['id'], 11, int), 'images/admin/delete.png');
		}
		$text .= '<tr>
			<td><a href="'.$config['admin_file'].'?exe=admins&a=editadmin&id='.SafeDB($adm['id'], 11, int).'"><b>'.SafeDB($adm['name'], 50, str).'</b></a>'.'</td>
			<td>'.PrintEmail($adm['email'], $adm['name']).'</td>
			<td>'.$types[SafeDB($adm['access'], 11, int)][0].'</td>
			<td>'.TimeRender(SafeDB($adm['lastvisit'], 11, int)).'</td>
			<td>'.SafeDB($adm['visits'], 11, int).'</td>
			<td>'.$funcs.'</td>
			</tr>';
	}
	unset($admins);
	$text .= '</table>';
	AddTextBox('Администраторы сайта', $text);
}

function AdminsGroups()
{
	global $config, $db;
	$accesses = $db->Select('access', '');
	foreach($accesses as $ac){
		$access[SafeDB($ac['group'], 255, str)][SafeDB($ac['name'], 255, str)] = SafeDB($ac['description'], 255, str);
	}
	unset($accesses);
	$atypes = $db->Select('usertypes', '');
	foreach($atypes as $type){
		$types[SafeDB($type['id'], 11, int)] = array(
			'<font color="'.$type['color'].'">'.SafeDB($type['name'], 255, str).'</font>',
			SafeDB($type['access'], 0, str, false, false),
			SafeDB($type['id'], 11, int),
			SafeDB($type['system'], 1, bool)
		);
	}
	unset($atypes);
	unset($type);
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable"><th>Группа</th><th>Доступ</th><th>Функции</th></tr>';
	foreach($types as $type){
		$funcs = '';
		$funcs .= SpeedButton('Редактировать', $config['admin_file'].'?exe=admins&a=editgroup&id='.$type[2], 'images/admin/edit.png');
		if($type[3] == '0'){
			$funcs .= SpeedButton('Удалить', $config['admin_file'].'?exe=admins&a=delgroup&id='.$type[2], 'images/admin/delete.png');
		}
		$text .= '<tr>
		<td>'.$type[0].'</td>
		<td>'.SafeDB(AdminsGenAccessStr($type[1], $access, $type[3]), 0, str).'</td>
		<td>'.$funcs.'</td>
		</tr>';
	}
	$text .= '</table>';
	AddTextBox('Группы администраторов', $text);
}

function AdminsEditGroup()
{
	global $config, $db, $action, $site;
	if($action == 'editgroup'){
		$db->Select('usertypes', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
		$group = $db->FetchRow();
		$name = SafeDB($group['name'], 255, str);
		$color = SafeDB($group['color'], 9, str);
		$access = SafeDB($group['access'], 0, str, false, false);
		$image = SafeDB($group['image'], 250, str);
		$method = 'editsave&id='.SafeEnv($_GET['id'], 11, int);
		$title = 'Редактирование группы';
		if($group['system']){
			$other = 'disabled';
		}else{
			$other = '';
		}
	}elseif($action == 'addgroup'){
		$name = '';
		$color = '#000000';
		$image = '';
		$access = serialize(array());
		$method = 'addsave';
		$title = 'Добавление группы';
		$other = '';
	}
	FormRow('Название', $site->Edit('name', $name, false, 'style="width:400px;"'));
	FormRow('Цвет', $site->Edit('color', $color, false, 'style="width:400px;"'));
	FormRow('Картинка', $site->Edit('image', $image, false, 'style="width:400px;"'));
	$access = AdminsGetAccessArray($access);
	$ac = '';
	foreach($access as $a){
		$ac .= '<table width="100%" cellspacing="0" cellpadding="3" style="border:1px #ABC5D8 solid;margin-bottom:2px;">';
		for($i = 0, $c = count($a); $i < $c; $i++){
			$ac .= '<tr>
			<td style="border:none;"><label>'.$site->Check('access[]', $a[$i][0].','.$a[$i][1], $a[$i][3], $other).$a[$i][2].'</label></td>
			</tr>';
		}
		$ac .= '</table>';
	}
	FormRow('Доступ', $ac);
	AddCenterBox($title);
	AddForm('<form action="'.$config['admin_file'].'?exe=admins&a='.$method.'" method="post">', $site->Button('Отмена', 'onclick="history.go(-1);"').$site->Submit('Сохранить'));
}

function AdminsEditGroupSave()
{
	global $db, $config, $action;
	$access = array();
	$access2 = array();
	if(isset($_POST['access'])){
		$access = SafeEnv($_POST['access'], 0, str);
		$keys = array();
		for($i = 0, $c = count($access); $i < $c; $i++){
			$acca = explode(',', $access[$i]);
			$access2[$acca[0]][] = $acca[1];
			if(!in_array($acca[0], $keys)){
				$keys[] = $acca[0];
			}
		}
		//Очищаем массив от "ненужных" доступов
		for($i = 0, $c = count($keys); $i < $c; $i++){
			if(!in_array($keys[$i], $access2[$keys[$i]])){
				unset($access2[$keys[$i]]);
			}
		}
	}
	if(isset($access2['ALL'])){
		$access = 'ALL';
	}else{
		$access = serialize($access2);
	}
	if($action == 'editsave'){
		$adm = $db->Select('usertypes', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
		if($adm[0]['system'] == '1'){
			$access = 'ALL';
		}
		$vals = Values('', SafeEnv($_POST['name'], 255, str), SafeEnv($_POST['color'], 9, str), $access, SafeEnv($adm[0]['system'], 1, int), SafeEnv($_POST['image'], 250, str));
		$db->Update('usertypes', $vals, "`id`='".SafeEnv($_GET['id'], 11, int)."'", true);
	}elseif($action == 'addsave'){
		$vals = Values('', SafeEnv($_POST['name'], 255, str), SafeEnv($_POST['color'], 9, str), $access, 0, SafeEnv($_POST['image'], 250, str));
		$db->Insert('usertypes', $vals);
	}

	// Очищаем кэш
	$cache = LmFileCache::Instance();
	$cache->Delete(system_cache, 'usertypes');

	GO($config['admin_file'].'?exe=admins&a=groups');
}

function AdminsDeleteGroup()
{
	global $db, $config, $site;
	if(!isset($_GET['id'])){
		GO($config['admin_file'].'?exe=admins&a=groups');
		exit();
	}
	$id = SafeEnv($_GET['id'], 11, int);
	if(isset($_GET['ok']) && SafeEnv($_GET['ok'], 1, int) == '1'){

		// Очищаем кэш
		$cache = LmFileCache::Instance();
		if($cache->HasCache(system_cache, 'usertypes')){
			$cache->Delete(system_cache, 'usertypes');
		}

		$db->Select('users', "`access`='".$id."'");
		$num_users = $db->NumRows();
		if($num_users == 0){
			$db->Delete('usertypes', "`id`='".$id."'");
			GO($config['admin_file'].'?exe=admins&a=groups');
			exit();
		}else{
			if(!isset($_GET['users'])){
				$text = 'К этой группе принадлежат '.$num_users.' пользователей. Вы можете:<br />'
				.'<a href="'.$config['admin_file'].'?exe=admins&a=delgroup&id='.$id.'&ok=1&users=del">Удалить их...</a> <br />'
				.'<a href="'.$config['admin_file'].'?exe=admins&a=delgroup&id='.SafeEnv($_GET['id'], 11, int).'&ok=1&users=move">Переместить их в другую группу.</a>';
				AddTextBox('Внимание!', $text);
			}else{
				if($_GET['users'] == 'del'){
					$db->Delete('users', "`access`='".$id."'");
					GO($config['admin_file'].'?exe=user&a=delgroup&id='.$id.'&ok=1');
					exit();
				}elseif($_GET['users'] == 'move' && !isset($_POST['to'])){
					$text = 'Выберите группу, в которую Вы желаете переместить пользователей:<br />'.'<form action="'.$config['admin_file'].'?exe=admins&a=delgroup&id='.$id.'&ok=1&users=move" method="post">';
					$db->Select('usertypes', "`id`<>'".$id."'");
					$site->DataAdd($group_data, '-1', 'Пользователи');
					while($tp = $db->FetchRow()){
						$site->DataAdd($group_data, $tp['id'], $tp['name']);
					}
					$text .= $site->Select('to', $group_data).'<br />';
					$text .= $site->Submit('Продолжить').'<br />';
					$text .= '</form>';
					AddTextBox('Внимание!', $text);
				}elseif($_GET['users'] == 'move' && isset($_POST['to'])){
					$to = SafeEnv($_POST['to'], 11, int);
					if($to == '-1'){
						$set = "type='2',access='".$to."'";
					}else{
						$set = "access='".$to."'";
					}
					$db->Update('users', $set, "`access`='".$id."'");
					GO($config['admin_file'].'?exe=admins&a=delgroup&id='.$id.'&ok=1', 302);
					exit();
				}
			}
		}
	}else{
		$db->Select('usertypes', "`id`='".$id."'");
		$group = $db->FetchRow();
		$text = 'Вы действительно хотите удалить группу "'.$group['name'].'"?<br />'.'<a href="'.$config['admin_file'].'?exe=admins&a=delgroup&id='.$id.'&ok=1">Да</a> &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">Нет</a>';
		AddTextBox("Предупреждение", $text);
	}
}

function AdminsDelete()
{
	global $config, $db, $site;
	$r = $db->Select('users', "`id`='".SafeEnv($_GET['id'], 11, int)."' and `type`='1'");
	if($db->NumRows() == 0){
		$text = 'Администратор, которого вы хотите удалить, не найден.';
		AddTextBox("Ошибка", $text);
		return;
	}elseif(groupIsSystem(SafeEnv($r[0]['access'], 11, int)) && GetSystemAdminsCount() <= 1){
		$text = 'Нельзя удалить последнего системного администратора.';
		AddTextBox("Ошибка", $text);
		return;
	}
	if(isset($_GET['ok']) && $_GET['ok'] == '1'){
		$id = SafeEnv($_GET['id'], 11, int);
		if(!isset($_POST['del_comments'])){
			$db->Select('users', "`id`='$id'");
			if($db->NumRows() > 0){
				$suser = $db->FetchRow();
				UpdateUserComments($id, '0', SafeEnv($suser['name'], 50, str), SafeEnv($suser['email'], 50, str), SafeEnv($suser['hideemail'], 1, bool), SafeEnv($suser['url'], 250, str));
			}
		}else{
			DeleteAllUserComments($id);
		}
		$db->Delete('users', "`id`='$id' and `type`='1'");

		// Очищаем кэш пользователей
		$cache = LmFileCache::Instance();
		$cache->Delete(system_cache, 'users');

		GO($config['admin_file'].'?exe=admins');
		exit();
	}elseif(isset($_GET['ok']) && $_GET['ok'] == '2'){
		$db->Update('users', "type='2',access='-1'", "`id`='".SafeEnv($_GET['id'], 11, int)."' and `type`='1'");

		// Очищаем кэш пользователей
		$cache = LmFileCache::Instance();
		$cache->Delete(system_cache, 'users');

		GO($config['admin_file'].'?exe=admins');
		exit();
	}else{
		$text = '<form action="'.$config['admin_file'].'?exe=admins&a=deladmin&id='.SafeEnv($_GET['id'], 11, int).'&ok=1" method="post">Что вы хотите сделать с администратором "'.$r[0]['name'].'"?<br />'
			.$site->Check('del_comments', '1').'Удалить все комментарии этого пользователя.<br />'.$site->Submit('Удалить')
			.' &nbsp;&nbsp;&nbsp; <a href="'.$config['admin_file'].'?exe=admins&a=deladmin&id='.SafeEnv($_GET['id'], 11, int).'&ok=2">Сделать пользователем</a> &nbsp;&nbsp;&nbsp; '
			.$site->Button('Отмена', 'onclick="history.go(-1)"').'</form>';
		AddTextBox("Предупреждение", $text);
	}
}
if(isset($_GET['a'])){
	$action = $_GET['a'];
}else{
	$action = 'main';
}

function Admins()
{
	global $action, $config;
	TAddToolLink('Администраторы', 'main', 'admins');
	TAddToolLink('Группы администраторов', 'groups', 'admins&a=groups');
	TAddToolLink('Добавить группу', 'addgroup', 'admins&a=addgroup');
	TAddToolBox($action);
	switch($action){
		case 'main':
			AdminsMain();
			break;
		case 'groups':
			AdminsGroups();
			break;
		case 'editgroup':
			AdminsEditGroup();
			break;
		case 'addgroup':
			AdminsEditGroup();
			break;
		case 'addsave':
		case 'editsave':
			AdminsEditGroupSave();
			break;
		case 'delgroup':
			AdminsDeleteGroup();
			break;
		case 'editadmin':
			include_once ($config['apanel_dir'].'members.php');
			AdminUserEditor('admins&a=adminsave', 'edit', SafeEnv($_GET['id'], 11, int), true);
			break;
		case 'adminsave':
			include_once ($config['apanel_dir'].'members.php');
			AdminUserEditSave('admins', 'update', SafeEnv($_GET['id'], 11, int), true);
			break;
		case 'deladmin':
			AdminsDelete();
			break;
		default:
			AdminsMain();
	}
}

Admins();

?>