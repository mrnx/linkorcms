<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

System::admin()->AddSubTitle('Смайлики');

if(!$user->CheckAccess2('smilies', 'smilies')){
	AddTextBox('Ошибка', $config['general']['admin_accd']);
	return;
}

$smilies_dir = 'uploads/smilies/';
$mod = ADMIN_FILE.'?exe=smilies';

if(isset($_GET['a'])){
	$action = $_GET['a'];
}else{
	$action = 'main';
}

TAddToolLink('Смайлики', 'main', 'smilies');
TAddToolLink('Авто-добавление', 'auto', 'smilies&a=auto');
TAddToolBox($action);
switch($action){
	case 'main': AdminSmilesMain();
		break;
	case 'addsmile': AdminSmilesAddSave();
		break;
	case 'delsmile': AdminSmilesDeleteSmile();
		break;
	case 'editsmile': AdminSmilesEditSmile();
		break;
	case 'seditsave': AdminSmilesEditSave();
		break;
	case 'changestatus': AdminBlocksChangeStatus();
		break;
	case 'auto': AdminSmiliesAutoAdd();
		break;
	case 'autosave': AdminSmiliesAutoSave();
		break;
	case 'delfile': AdminSmiliesDeleteFile();
		break;
	default:
		AdminSmilesMain();
}

function AdminSmilesGetAllSmiles( &$sid, $dir_name, $selected = '', $smilies = array() ){
	global $site, $smilies_dir;
	static $i = -1;
	static $sfiles = array();
	static $xor_smilies = array();
	static $xsm = false;
	if(!$xsm){
		foreach($smilies as $sm){
			$xor_smilies[$sm['file']] = true;
		}
	}
	$dir = @opendir($dir_name);
	while($file = @readdir($dir)){
		if(is_dir($dir_name.$file) && ($file != '.') && ($file != '..')){
			AdminSmilesGetAllSmiles($sid, $dir_name.$file.'/', $selected, $smilies);
		}else{
			$ext = GetFileExt($file);
			if($ext == '.gif' || $ext == '.png'){
				$rf = str_replace($smilies_dir, '', $dir_name).$file;
				if(!isset($xor_smilies[$rf]) || $selected == $rf){
					$site->DataAdd($sfiles, $rf, $rf, $selected == $rf);
					$i++;
				}
				if($selected != $rf){
					$sel = false;
				}else{
					$sel = true;
					$sid = $i;
				}
			}
		}
	}
	return $sfiles;
}

function AdminSmilesMain(){
	global $db, $config, $site, $smilies_dir, $mod;
	System::admin()->AddCenterBox('Смайлики');
	$smilies = $db->Select('smilies', '');
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>Изображение</th><th>Код</th><th>Описание</th><th>Имя файла</th><th>Показать</th><th>Функции</th></tr>';
	if(System::database()->NumRows() > 0){
		while($row = $db->FetchRow()){
			$sid = SafeDB($row['id'], 11, int);
			if(!is_file($smilies_dir.$row['file'])){
				$db->Delete('smilies', "`file`='".SafeEnv($row['file'], 255, str)."'");
				continue;
			}
			$en = System::admin()->SpeedStatus('Да', 'Нет', $mod.'&a=changestatus&id='.$sid, $row['enabled'] == '1');

			$func = '';
			$func .= System::admin()->SpeedButton('Редактировать', $mod.'&a=editsmile&id='.$sid, 'images/admin/edit.png');
			$func .= System::admin()->SpeedConfirm('Удалить', $mod.'&a=delsmile&sid='.$sid, 'images/admin/delete.png', 'Удалить смайлик?');

			$text .= "<tr>
			<td>".System::admin()->Link('<img src="'.$smilies_dir.$row['file'].'">', $mod.'&a=editsmile&id='.$sid)."</td>
			<td>{$row['code']}</td>
			<td>{$row['desc']}</td>
			<td>{$row['file']}</td>
			<td>$en</td>
			<td>$func</td>
			</tr>";
		}
	}else{
		$text .= '<tr><td colspan="6" style="text-align: left;">Нет смайликов.</td></tr>';
	}
	$text .= '</table>';
	$sfiles = AdminSmilesGetAllSmiles($id, $smilies_dir, '', $smilies);
	if(isset($sfiles[0])){
		$fname = $sfiles[0]['name'];
	}else{
		$fname = '';
	}
	AddText($text);
	if(count($sfiles) > 0){
		System::admin()->FormTitleRow('Добавить смайлик');
		System::admin()->FormRow('Изображение', $site->Select('file', $sfiles, false, "onchange=\"document.newsmile.image.src='$smilies_dir'+document.newsmile.file.value\""));
		System::admin()->FormRow('Предпросмотр', "<img id=\"image\" src=\"$smilies_dir$fname\" />");
		System::admin()->FormRow('Код', $site->Edit('code', '', false, 'style="width: 200px;"'));
		System::admin()->FormRow('Описание', $site->Edit('desc', '', false, 'style="width: 200px;"'));
		System::admin()->FormRow('Показывать', $site->Radio('indexview', 'on', true).'Да&nbsp;'.$site->Radio('indexview', 'off').'Нет');
		System::admin()->AddForm("<form name=\"newsmile\" action=\"$mod&a=addsmile\" method=\"post\">", $site->Submit('Добавить'));
	}else{
		System::admin()->Highlight('Загрузите изображения смайликов в папку <b>'.$smilies_dir.'</b> для добавления.');
	}
}

function AdminSmilesEditSmile(){
	global $db, $config, $site, $smilies_dir, $mod;
	$id = SafeEnv($_GET['id'], 11, int);
	$smilies = $db->Select('smilies', '');
	$db->Select('smilies', "`id`='$id'");
	$smd = $db->FetchRow();
	$sfiles = AdminSmilesGetAllSmiles($sid, $smilies_dir, $smd['file'], $smilies);
	$en = array(false, false);
	$en[$smd['enabled']] = true;
	FormRow('Изображение', $site->Select('file', $sfiles, false, "style=\"width:130px;\" onchange=\"document.newsmile.image.src='$smilies_dir'+document.newsmile.file.value\""));
	FormRow('Предпросмотр', '<img id="image" src="'.$smilies_dir.$sfiles[$sid]['name'].'" />');
	FormRow('Код', $site->Edit('code', $smd['code'], false, 'style="width: 200px;"'));
	FormRow('Описание', $site->Edit('desc', $smd['desc'], false, 'style="width: 200px;"'));
	FormRow('Показывать', $site->Radio('indexview', 'on', $en[1]).'Да&nbsp;'.$site->Radio('indexview', 'off', $en[0]).'Нет');
	AddCenterBox('Редактирование смайлика');
	AddForm('<form name="newsmile" action="'.$mod.'&a=seditsave&id='.$id.'" method="post">', $site->Button('Отмена', 'onclick="history.go(-1)"').$site->Submit('Сохранить'));
}

function AdminSmilesEditSave(){
	global $mod;
	$id = SafeEnv($_GET['id'], 11, int);
	$disp = EnToInt(SafeEnv($_POST['indexview'], 3, str));
	$vals = Values('', SafeEnv($_POST['code'], 30, str), SafeEnv($_POST['desc'], 255, str), SafeEnv($_POST['file'], 255, str), $disp);
	System::database()->Update('smilies', $vals, "`id`='$id'", true);
	GO($mod);
}

function AdminSmilesAddSave(){
	global $mod;
	$disp = EnToInt(SafeEnv($_POST['indexview'], 3, str));
	$vals = Values('', SafeEnv($_POST['code'], 30, str), SafeEnv($_POST['desc'], 255, str), SafeEnv($_POST['file'], 255, str), $disp);
	System::database()->Insert('smilies', $vals);
	GO($mod);
}

function AdminSmilesDeleteSmile(){
	global $smilies_dir, $mod;
	System::database()->Delete('smilies', "`id`='".SafeEnv($_GET['sid'], 11, int)."'");
	GO($mod);
}

function AdminBlocksChangeStatus(){
	global $mod;
	System::database()->Select('smilies', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	if(System::database()->NumRows() > 0){
		$r = System::database()->FetchRow();
		if(SafeDB($r['enabled'], 1, int) == 1){
			$en = '0';
		}else{
			$en = '1';
		}
		System::database()->Update('smilies', "enabled='$en'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	}
	GO($mod);
}

function AdminSmiliesAutoAdd(){
	global $mod, $smilies_dir, $site;
	AddCenterBox('Авто-добавление смайликов');

	$smilies = System::database()->Select('smilies', '');
	$sfiles = AdminSmilesGetAllSmiles($id, $smilies_dir, '', $smilies);
	if(count($sfiles) == 0){
		System::admin()->Highlight('Новых файлов не найдено. Загрузите изображения смайликов в папку: <b>'.$smilies_dir.'</b>.');
		return;
	}

	$text = '';
	$text .= $site->FormOpen(ADMIN_FILE.'?exe=smilies&a=autosave');
	$text .= '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr>
		<th>Добавить</th>
		<th>Изображение</th>
		<th>Код</th>
		<th>Описание</th>
		<th>Имя файла</th>
		<th>Виден на главной</th>
		<th>Удалить</th>
	</tr>';

	foreach($sfiles as $sm){
		$func = System::admin()->SpeedConfirm('Удалить файл', $mod.'&a=delfile&name='.$sm['name'], 'images/admin/delete.png', 'Удалить файл с сервера?');
		$text .= '<tr>'
		.'<td>'.$site->Check('smilies[]', $sm['name'], true).'</td>'
		.'<td><img src="'.$smilies_dir.$sm['name'].'" /></td>'
		.'<td>'.$site->Edit('code['.$sm['name'].']', '*'.GetFileName($sm['name']).'*', false, 'style="width:160px;"').'</td>'
		.'<td>'.$site->Edit('desc['.$sm['name'].']', '', false, 'style="width:160px;"').'</td>'
		.'<td>'.$sm['name'].'</td>'
		.'<td>'.$site->Check('en['.$sm['name'].']', '1', true).'</td>'
		.'<td>'.$func.'</td>'
		.'</tr>';
	}
	$text .= '</table><br />';
	$text .= $site->Submit('Добавить').'<br /><br />';
	$text .= $site->FormClose();
	AddText($text);
}

function AdminSmiliesAutoSave(){
	global $db, $mod;
	foreach($_POST['smilies'] as $file){
		$file = RealPath2(SafeEnv($file, 255, str));
		$code = SafeEnv($_POST['code'][$file], 255, str);
		$desc = SafeEnv($_POST['desc'][$file], 255, str);
		$disp = (isset($_POST['en'][$file]) ? '1' : '0');
		$vals = Values('', $code, $desc, $file, $disp);
		$db->Insert('smilies', $vals);
	}
	GO($mod);
}

function AdminSmiliesDeleteFile(){
	global $smilies_dir, $mod;
	unlink(RealPath2($smilies_dir.$_GET['name']));
	GO($mod.'&a=auto');
}
