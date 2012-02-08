<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('Управление настройками');

if(!$user->CheckAccess2('config', 'config')){
	AddTextBox('Ошибка', 'Доступ запрещен!');
	return;
}

include_once ($config['inc_dir'].'configuration/functions.php');

if(isset($_GET['a'])){
	$action = $_GET['a'];
}else{
	$action = 'view_all';
}

TAddToolLink('Все настройки', 'view_all', 'config_admin&a=view_all');
TAddToolLink('Добавить настройку', 'add', 'config_admin&a=add');
TAddToolLink('Группы настроек', 'view_groups', 'config_admin&a=view_groups');
TAddToolBox($action);
TAddToolLink('Все настройки плагинов','view_all_plugins','config_admin&a=view_all_plugins&plugins=1');
TAddToolLink('Добавить настройку плагина', 'add_plugins', 'config_admin&a=add_plugins&plugins=1');
TAddToolLink('Группы настроек плагинов', 'view_groups_plugins', 'config_admin&a=view_groups_plugins&plugins=1');
TAddToolBox($action);

switch($action){
	case 'view_all':
	case 'view_all_plugins':
		AdminViewRetrofittingList();
	break;
	case 'add':
	case 'add_plugins':
	case 'edit':
		AdminConfigAdd();
	break;
	case 'save':
		AcAddRetrofitting();
	break;
	case 'delete':
		AdminConfigDeleteRetrofitting();
	break;
	case 'view_groups':
	case 'view_groups_plugins':
		AdminConfigViewGroups();
	break;
	case 'editgroup':
		AdminConfigGroupEdit();
	break;
	case 'savegroup':
		AdminConfigGroupSave();
	break;
	case 'deletegroup':
		AdminConfigGroupDelete();
	break;
	default: AdminViewRetrofittingList();
}

// Проверяет работаем ли мы с настройками плагинов
function AdminConfigPlugins(){
	return (isset($_GET['plugins']) && $_GET['plugins'] == '1');
}

// Возвращает таблицу для групп
function AdminConfigGroupTable(){
	if(AdminConfigPlugins()){
		return 'plugins_config_groups';
	}else{
		return 'config_groups';
	}
}

// Возвращает таблицу для настроек
function AdminConfigConfigTable(){
	if(AdminConfigPlugins()){
		return 'plugins_config';
	}else{
		return 'config';
	}
}

// Возвращает данные формы для групп
function AdminConfigGetGroupsFormData( $group = 0 ){
	global $config, $db, $site;
	$db->Select(AdminConfigGroupTable(), '');
	$result = array();
	while($g = $db->FetchRow()){
		$site->DataAdd($result, SafeDB($g['id'], 11, int), SafeDB($g['hname'], 255, str).' ('.SafeDB($g['name'], 255, str).')', $group == $g['id']);
	}
	return $result;
}

// Список настроек
function AdminViewRetrofittingList(){
	global $db, $config;

	if(!AdminConfigPlugins()){
		$access_config = 'System::config(';
	}else{
		$access_config = 'System::plug_config(';
	}

	$groups = array();
	$db->Select(AdminConfigGroupTable());
	while($group = $db->FetchRow()){
		$groups[$group['id']] = $group;
	}

	$db->Select(AdminConfigConfigTable(), '');
	SortArray($db->QueryResult, 'group_id');

	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr>
	<th>№</th>
	<th>Группа</th>
	<th>Настройка</th>
	<th>PHP код</th>'
//	.'<th>Установка/удаление<th>'
	.'<th>Видимая</th>
	<th>Функции</th>'
	.'</tr>';
	$id = 0;
	while($conf = $db->FetchRow()){
		$id++;
		$confid = SafeDB($conf['id'], 11, int);
		$func = '';
		$func .= System::admin()->SpeedButton('Редактировать', ADMIN_FILE.'?exe=config_admin&a=edit&id='.$confid.(AdminConfigPlugins() ? '&plugins=1' : ''), 'images/admin/edit.png');
		$func .= System::admin()->SpeedConfirm('Удалить', ADMIN_FILE.'?exe=config_admin&a=delete&id='.$confid.'&ok=0'.(AdminConfigPlugins() ? '&plugins=1' : ''), 'images/admin/delete.png', 'Это может повлиять на работу системы. Нажмите отмена, если не уверены. Удалить настройку?');

		$access = $access_config."<span style=\"color: #008200\">'".$groups[$conf['group_id']]['name'].'/'."".$conf['name']."'</span>)";

		$install_vals = Values('', $conf['group_id'], $conf['name'], $conf['value'],
			$conf['visible'], $conf['hname'], $conf['description'], $conf['kind'],
			$conf['values'], $conf['savefunc'], $conf['type'], $conf['autoload']);
		$install = '$db->Insert("'.AdminConfigConfigTable().'","'.$install_vals.'");';

		if($conf['visible'] == '1'){
			$visible = '<font color="#008000">Да</font>';
		}else{
			$visible = '<font color="#FF0000">Нет</font>';
		}

		$text .= '<tr><td>'.$id.'</td>
		<td>'.$groups[$conf['group_id']]['hname'].'</td>
		<td style="text-align:left;padding-left:10px;">'.$conf['hname'].'</td>
		<td style="text-align:left;padding-left:10px;">'.$access.'</td>'
	//	.'<td>'.$install.'</td>'
		.'<td>'.$visible.'</td>
		<td>'.$func.'</td>
		</tr>';
	}
	$text .= '</table>';

	System::admin()->AddCenterBox('Все настройки');
	if(isset($_GET['saveok'])){
		System::admin()->Highlight('Изменения сохранены.');
	}elseif(isset($_GET['addok'])){
		System::admin()->Highlight('Настройка добавлена.');
	}elseif(isset($_GET['delok'])){
		System::admin()->Highlight('Настройка удалена.');
	}
	System::admin()->AddText($text);
}

// Редактор настроек
function AdminConfigAdd(){
	global $site, $config, $cl_plugins, $cs_plugins, $db;

	if(isset($_GET['id'])){ // Редактирование
		$id = SafeEnv($_GET['id'], 11, int);
		$db->Select(AdminConfigConfigTable(), "`id`='$id'");
		$ret = $db->FetchRow();

		$group = SafeDB($ret['group_id'], 11, int);
		$name = SafeDB($ret['name'], 255, str);
		$hname = SafeDB($ret['hname'], 255, str);
		$description = SafeDB($ret['description'], 255, str);
		$value = SafeDB($ret['value'], 0, str, false);

		$control = explode(':', $ret['kind']);
		$control[0] = trim(strtolower($control[0]));
		$control = FormsParseParams($control);

		$values = SafeDB($ret['values'], 0, str);
		$vals = explode(':', $values);
		if(count($vals) == 2 && FormsConfigCheck2Func($vals[0], $vals[1])){
			$valuesfunc = trim($vals[1]);
			$values = '';
		}else{
			$valuesfunc = '';
		}
		$savefunc = SafeDB($ret['savefunc'], 250, str);

		$type = SafeDB($ret['type'], 60, str);
		if($type != ''){
			$type = explode(',', $type);
			settype($type[0], int); //maxlength
			settype($type[1], str); //type
			if($type[2] == 'false'){
				$type[2] = false;
			}else{
				$type[2] = true;
			}
		}else{
			$type = array(255, str, false);
		}

		$visible = SafeDB($ret['visible'], 1, int);
		$autoload = SafeDB($ret['autoload'], 1, int);

	}else{ // Добавление
		$group = 0;
		$name = '';
		$hname = '';
		$description = '';
		$value = '';

		$control = array('cols'=>1, 'style'=>'', 'control'=>'', 'width'=>'', 'height'=>'');

		$values = '';
		$valuesfunc = '';
		$savefunc = '';
		$type = array(255, str, false);
		$visible = 0;
		$autoload = 0;
	}

	// Элемент управления
	$controls_array = array('edit', 'password', 'text', 'combo', 'list', 'check', 'radio');
	$controls_array2 = array('Текстовое поле', 'Пароль', 'Область редактирования',
	 'Раскрывающийся список', 'Список (мультивыделение)', 'Флажки', 'Радиокнопки');
	$controls = array();
	foreach($controls_array as $c=>$contol_name){
		$site->DataAdd($controls, $contol_name, $controls_array2[$c], $contol_name == $control['control']);
	}

	// Количество колонок
	$collsd = array();
	for($i = 1; $i < 11; $i++){
		$site->DataAdd($collsd, $i, $i, $i==$control['cols']);
	}

	// Функция заполнения значений
	$getfuncdata = array();
	$site->DataAdd($getfuncdata, '', '');
	foreach($cl_plugins as $pl){
		$site->DataAdd($getfuncdata, $pl[0], $pl[0], $pl[0] == $valuesfunc);
	}

	// Функция обработки
	$savefuncdata = array();
	$site->DataAdd($savefuncdata, '', '');
	foreach($cs_plugins as $pl){
		$site->DataAdd($savefuncdata, $pl[0], $pl[0], $pl[0] == $savefunc);
	}

	// Тип данных
	$types_array = array('int', 'float', 'string', 'bool');
	$types_array2 = array('Целочисленный', 'Вещественный', 'Текстовый', 'Логический');
	$datatypes = array();
	foreach($types_array as $c=>$type_name){
		$site->DataAdd($datatypes, $type_name, $types_array2[$c], $type_name == $type[1]);
	}

	AddCenterBox('Добавить настройку');
	FormRow('Группа', $site->Select('group', AdminConfigGetGroupsFormData($group)));
	FormRow('Имя', $site->Edit('name', $name, false, 'style="width:400px;" maxlength="255"'));
	FormRow('Заголовок', $site->Edit('hname', $hname, false, 'style="width:400px;" maxlength="255"'));
	FormRow('Описание', $site->Edit('description', $description, false, 'style="width:400px;" maxlength="255"'));
	FormRow('Значение', $site->TextArea('value', $value, 'style="width:400px;height:200px;"'));
	FormRow(
		'Элемент управления<br /><small>Укажите единицу измерения<br />после ширины и высоты</small>',
		$site->Select('control', $controls).'<table cellspacing="3" cellpadding="0" border="0">'
		.'<tr><td style="border:none">Ширина:</td><td style="border:none">'.$site->Edit('cwidth', $control['width'], false, 'style="width:200px;"').'</td></tr>'
		.'<tr><td style="border:none">Высота:</td><td style="border:none">'.$site->Edit('cheight', $control['height'], false, 'style="width:200px;"').'</td></tr>'
		.'<tr><td style="border:none">Колонок:</td><td style="border:none">'.$site->Select('ccols', $collsd).'</td></tr>'.'</table>'
	);
	FormRow(
		'Возможные значения<br /><small>Например:<br />name:имя, name:имя, ...<br />Только для элементов выбора.</small>',
		$site->TextArea('values', $values, 'style="width:400px;height:100px;"')
	);
	FormRow('Функция заполнения значений', $site->Select('valuesfunc', $getfuncdata));
	FormRow('Функция обработчик', $site->Select('savefunc', $savefuncdata));
	FormRow('Тип данных', $site->Select('datatype', $datatypes));
	FormRow('Длина поля<br /><small>0 - не ограничено</small>', $site->Edit('maxlength', $type[0], false, 'style="width:200px;" maxlength="11"'));
	FormRow('Вырезать html-теги и<br />заменять спецсимволы<br />html-эквивалентами', $site->Check('striptags', '1', $type[2]));
	FormRow('Видимая', $site->Check('visible', '1', $visible));
	FormRow('Автозагрузка', $site->Check('autoload', '1', $autoload));
	AddForm(
		$site->FormOpen(ADMIN_FILE.'?exe=config_admin&a=save'.(AdminConfigPlugins() ? '&plugins=1' : '').(isset($_GET['id']) ? '&id='.$id : '')),
		$site->Submit((isset($_GET['id']) ? 'Сохранить' : 'Добавить'))
	);
}

// Сохранение настройки
function AcAddRetrofitting(){
	global $db, $config;

	if(RequestMethod() != 'POST') return;
	$back_url = '';
	if(!AdminConfigPlugins()){
		$access_config = 'System::config(';
		$back_url = ADMIN_FILE.'?exe=config_admin&a=view_all&saveok';
	}else{
		$access_config = 'System::plug_config(';
		$back_url = ADMIN_FILE.'?exe=config_admin&a=view_all_plugins&plugins=1&saveok';
	}

	$db->Select(AdminConfigGroupTable(), '');
	$groups = array();
	while($g = $db->FetchRow()){
		$groups[$g['id']] = $g['name'];
	}
	$group = SafeEnv($_POST['group'], 11, int);
	$hname = SafeEnv($_POST['hname'], 255, str, true);
	$name = SafeEnv($_POST['name'], 255, str);
	$value = SafeEnv($_POST['value'], 0, str);
	$description = SafeEnv($_POST['description'], 255, str, true);
	if(isset($_POST['visible'])){
		$visible = '1';
	}else{
		$visible = '0';
	}
	if(isset($_POST['autoload'])){
		$autoload = '1';
	}else{
		$autoload = '0';
	}
	//генерируем kind
	$kind = '';
	$values = '';
	$savefunc = '';
	$type = '';
	if($visible == '1'){
		$kind .= SafeEnv($_POST['control'], 25, str);
		$width = SafeEnv($_POST['cwidth'], 14, str);
		$height = SafeEnv($_POST['cheight'], 14, str);
		$cols = SafeEnv($_POST['ccols'], 11, int);
		if($width != ''){
			$kind .= ':w'.$width;
		}
		if($height != ''){
			$kind .= ':h'.$height;
		}
		if($cols > 1){
			$kind .= ':c'.$cols;
		}
		$getfunc = SafeEnv($_POST['valuesfunc'], 255, str);
		if($getfunc == '' || !function_exists(CONF_GET_PREFIX.$getfunc)){
			$values = SafeEnv($_POST['values'], 0, str);
		}else{
			$values = 'function:'.$getfunc;
		}
	}

	if(function_exists(CONF_SAVE_PREFIX.$_POST['savefunc'])){
		$savefunc = SafeEnv($_POST['savefunc'], 255, str);
	}else{
		$savefunc = '';
	}
	$maxlenght = SafeEnv($_POST['maxlength'], 11, int);
	$type = SafeEnv($_POST['datatype'], 255, str);
	if(isset($_POST['striptags'])){
		$striptags = 'true';
	}else{
		$striptags = 'false';
	}
	$type = $maxlenght.','.$type.','.$striptags;


	//Сохраняем
	$to_db = Values('', $group, $name, $value, $visible, $hname, $description, $kind, $values, $savefunc, $type, $autoload);
	if(isset($_GET['id'])){
		$id = SafeEnv($_GET['id'], 11, str);
		$db->Update(AdminConfigConfigTable(), $to_db, "`id`='$id'", true);
		GO($back_url);
	}else{
		$db->Insert(AdminConfigConfigTable(), $to_db);
		AddTextBox('Подтверждение', 'Новая настройка успешно добавлена.<br />Для доступа к значению настройки используйте код:<br /><br />'
			.$access_config."<span style=\"color: #008200\">'".$groups[$group].'/'."".$name."'</span>)"
			.'<br />'
		);
	}
}

// Удаление настройки
function AdminConfigDeleteRetrofitting(){
	global $config, $db;
	$back_url = '';
	if(!AdminConfigPlugins()){
		$back_url = ADMIN_FILE.'?exe=config_admin&a=view_all&delok';
	}else{
		$back_url = ADMIN_FILE.'?exe=config_admin&a=view_all_plugins&plugins=1&delok';
	}
	if(!isset($_GET['id'])){
		GO($back_url);
	}else{
		$id = SafeEnv($_GET['id'], 11, int);
	}
	if(isset($_GET['ok']) && $_GET['ok'] == '1' || IsAjax()){
		$db->Delete(AdminConfigConfigTable(), "`id`='$id'");
		GO($back_url);
	}else{
		$r = $db->Select(AdminConfigConfigTable(), "`id`='$id'");
		AddCenterBox('Удаление настройки');
		System::admin()->HighlightConfirm('Это может повлиять на работу системы. Нажмите отмена, если не уверены. Удалить группу настроек "'.SafeDB($r[0]['hname'], 255, str).'"?',
			ADMIN_FILE.'?exe=config_admin&a=delete&id='.$id.'&ok=1'.(AdminConfigPlugins() ? '&plugins=1' : ''));
	}
}

// Список Групп
function AdminConfigViewGroups(){
	global $db, $config, $site;

	$db->Select(AdminConfigGroupTable());
	AddCenterBox('Группы настроек');
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr>
	<th>Имя группы</th>
	<th>Заголовок</th>
	<th>Описание</th>'
	//<th>Установка</th>
	.'<th>Видимая</th>
	<th>Функции</th>'
	.'</tr>';

	while($group = $db->FetchRow()){
		$groupid = SafeDB($group['id'], 11, int);
		$func = '';
		$func .= System::admin()->SpeedButton('Редактировать', ADMIN_FILE.'?exe=config_admin&a=editgroup&id='.$groupid.(AdminConfigPlugins() ? '&plugins=1' : ''), 'images/admin/edit.png');
		$func .= System::admin()->SpeedConfirm('Удалить', ADMIN_FILE.'?exe=config_admin&a=deletegroup&id='.$groupid.'&ok=0'.(AdminConfigPlugins() ? '&plugins=1' : ''),
			'images/admin/delete.png', 'Это может повлиять на работу системы. Нажмите отмена, если не уверены. Удалить группу настроек?');

		if($group['visible'] == '1'){
			$visible = '<font color="#008000">Да</font>';
		}else{
			$visible = '<font color="#FF0000">Нет</font>';
		}

		//$install_vals = Values('', $group['name'], $group['hname'], $group['description'], $group['visible']);
		//$install = '$db->Insert("'.AdminConfigGroupTable().'","'.$install_vals.'");';

		$text .= '<tr>
		<td>'.SafeDB($group['name'], 255, str).'</td>
		<td>'.SafeDB($group['hname'], 255, str).'</td>
		<td>'.SafeDB($group['description'], 255, str).'</td>'
		//<td>'.$install.'</td>
		.'<td>'.$visible.'</td>
		<td>'.$func.'</td>
		</tr>';
	}
	$text .= '</table><br />';

	if(isset($_GET['saveok'])){
		System::admin()->Highlight('Изменения сохранены.');
	}elseif(isset($_GET['addok'])){
		System::admin()->Highlight('Группа добавлена.');
	}elseif(isset($_GET['delok'])){
		System::admin()->Highlight('Группа удалена.');
	}
	AddText($text);

	//AddText('.:Добавить группу:.');
	System::admin()->FormTitleRow('Добавить группу');
	FormRow('Имя', $site->Edit('name', '', false, 'style="width:400px;"'));
	FormRow('Заголовок', $site->Edit('hname', '', false, 'style="width:400px;"'));
	FormRow('Описание', $site->TextArea('description', '', 'style="width:400px;height:100px;"'));
	FormRow('Видимая', $site->Check('visible', '1', false));
	AddForm('<form action="'.ADMIN_FILE.'?exe=config_admin&a=savegroup'.(AdminConfigPlugins() ? '&plugins=1' : '').'" method="post">',$site->Submit('Добавить'));
}

// Редактирование группы
function AdminConfigGroupEdit(){
	global $db, $site, $config;
	$id = SafeEnv($_GET['id'], 11, int);
	$db->Select(AdminConfigGroupTable(), "`id`='$id'");
	$group = $db->FetchRow();
	FormRow('Имя', $site->Edit('name', SafeDB($group['name'],255,str), false, 'style="width:400px;"'));
	FormRow('Заголовок', $site->Edit('hname', SafeDB($group['hname'],255,str), false, 'style="width:400px;"'));
	FormRow('Описание', $site->TextArea('description', SafeDB($group['description'],255,str), 'style="width:400px;height:100px;"'));
	FormRow('Видимая', $site->Check('visible', '1', $group['visible']=='1'));
	AddCenterBox('Редактирование группы');
	AddForm('<form action="'.ADMIN_FILE.'?exe=config_admin&a=savegroup&id='.$id.(AdminConfigPlugins() ? '&plugins=1' : '').'" method="post">',
		$site->Button('Отмена', 'onclick="history.go(-1)"').$site->Submit('Сохранить')
	);
}

// Сохранение группы
function AdminConfigGroupSave(){
	global $db, $config;
	$name = SafeEnv($_POST['name'], 255, str);
	$hname = SafeEnv($_POST['hname'], 255, str);
	$description = SafeEnv($_POST['description'], 255, str);
	if(isset($_POST['visible'])){
		$visible = '1';
	}else{
		$visible = '0';
	}
	$vals = Values('', $name, $hname, $description, $visible);
	if(isset($_GET['id'])){
		$id = SafeEnv($_GET['id'], 11, int);
		$db->Update(AdminConfigGroupTable(), $vals, "`id`='$id'", true);
		$msg = '&saveok';
	}else{
		$db->Insert(AdminConfigGroupTable(), $vals);
		$msg = '&addok';
	}
	GO(ADMIN_FILE.'?exe=config_admin'.(AdminConfigPlugins() ? '&a=view_groups_plugins&plugins=1' : '&a=view_groups').$msg);
}

// Удаление группы
function AdminConfigGroupDelete(){
	global $config, $db;
	$back_url = '';
	if(!AdminConfigPlugins()){
		$back_url = ADMIN_FILE.'?exe=config_admin&a=view_groups&delok';
	}else{
		$back_url = ADMIN_FILE.'?exe=config_admin&a=view_groups_plugins&plugins=1&delok';
	}
	if(!isset($_GET['id'])){
		GO($back_url);
	}else{
		$id = SafeEnv($_GET['id'], 11, int);
	}
	if(isset($_GET['ok']) && $_GET['ok'] == '1' || IsAjax()){
		$db->Delete(AdminConfigGroupTable(), "`id`='$id'");
		$db->Delete(AdminConfigConfigTable(), "`group_id`='$id'");
		GO($back_url);
	}else{
		$r = $db->Select(AdminConfigGroupTable(), "`id`='$id'");
		AddCenterBox('Удаление группы навтроек');
		System::admin()->HighlightConfirm('Это может повлиять на работу системы. Нажмите отмена, если не уверены. Удалить группу настроек "'.SafeDB($r[0]['hname'], 255, str).'"?',
			ADMIN_FILE.'?exe=config_admin&a=delete&id='.$id.'&ok=1'.(AdminConfigPlugins() ? '&plugins=1' : ''));
	}
}
