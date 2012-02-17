<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('Web-�����');

include_once ($config['inc_dir'].'forms.inc.php');

if(isset($_GET['a'])){
	$action = $_GET['a'];
}else{
	$action = 'main';
}

TAddToolLink('Web-�����', 'main', 'forms');
TAddToolLink('�������� �����', 'add', 'forms&a=add');
TAddToolBox($action);
switch($action){
	case 'main': AdminFormsMain();
		break;
	case 'add':
	case 'edit': AdminFormsEditor();
		break;
	case 'del': AdminFormsDelete();
		break;
	case 'editsave': AdminFormsSave();
		break;
	case 'addsave': AdminFormsSave();
		break;
	case 'fields': AdminFormsFields();
		break;
	case 'addfield': AdminFormsFieldSave();
		break;
	case 'editfield': AdminFormsEditFields();
		break;
	case 'delfield': AdminFormsDelField();
		break;
	case 'changestatus': AdminFormsChangeStatus();
		break;
	case 'posts': AdminFormsViewPosts(false);
		break;
	case 'newposts': AdminFormsViewPosts(true);
		break;
	case 'delpost': AdminFormsDeletePost();
		break;
	case 'checkall': AdminFormsCheckAll();
		break;
	default: AdminFormsMain();
}


// ������ ����
function AdminFormsMain(){
	global $db, $config;
	$forms = $db->Select('forms', '');
	SortArray($forms, 'new_answ', true);
	$text = '';
	$text .= '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr>
		<th>��������</th>
		<th>����� �������</th>
		<th>����� �������</th>
		<th>�����</th>
		<th>��� �����</th>
		<th>������</th>
		<th>�������</th>
	</tr>';
	foreach($forms as $form){
		$id = SafeDB($form['id'], 11, int);
		$vi = ViewLevelToStr(SafeDB($form['view'], 1, int));
		switch($form['active']){
			case "1":
				$st = '<a href="'.ADMIN_FILE.'?exe=forms&a=changestatus&id='.$id.'" title="�������� ������"><font color="#008000">���.</font></a>';
				break;
			case "0":
				$st = '<a href="'.ADMIN_FILE.'?exe=forms&a=changestatus&id='.$id.'" title="�������� ������"><font color="#FF0000">����.</font></a>';
				break;
		}
		$answ = (SafeDB($form['answ'], 11, int) > 0 ? '<b>'.SafeDB($form['answ'], 11, int).'</b> / <a href="'.ADMIN_FILE.'?exe=forms&a=posts&id='.$id.'">��������</a>' : SafeDB($form['answ'], 11, int));
		$new_answ = (SafeDB($form['new_answ'], 11, int) > 0 ? '<b>'.SafeDB($form['new_answ'], 11, int).'</b> / <a href="'.ADMIN_FILE.'?exe=forms&a=newposts&id='.$id.'">��������</a>' : SafeDB($form['new_answ'], 11, int));

		$func = '';
		$func .= SpeedButton('������������� �����', ADMIN_FILE.'?exe=forms&a=edit&id='.$id, 'images/admin/edit.png');
		$func .= SpeedButton('������������� ����', ADMIN_FILE.'?exe=forms&a=fields&id='.$id, 'images/admin/config.png');
		$func .= SpeedButton('������� �����', ADMIN_FILE.'?exe=forms&a=del&id='.$id.'&ok=0', 'images/admin/delete.png');

		$text .= '<tr>
		<td><b><a href="'.ADMIN_FILE.'?exe=forms&a=edit&id='.$id.'" title="�������������">'.SafeDB($form['hname'], 255, str).'</a></b></td>
		<td>'.$new_answ.'</td>
		<td>'.$answ.'</td>
		<td>'.SafeDB($form['numfields'], 11, int).'</td>
		<td>'.$vi.'</td>
		<td>'.$st.'</td>
		<td>'.$func.'</td>
		</tr>
		';
	}
	$text .= '</table><br />';
	AddTextBox('Web-�����', $text);
}

// �������� ���� - ���������� ����� ����� - �������������� �����
function AdminFormsEditor(){
	global $config, $db, $site, $user, $tree;
	if(!$user->CheckAccess2('downloads', 'edit_files')){
		AddTextBox('������', $config['general']['admin_accd']);
		return;
	}
	$name = '';
	$hname = '';
	$desc = '';
	$form_action = '';
	$email = '';
	$send_ok_msg = '';
	$view = array(1=>false, 2=>false, 3=>false, 4=>false);
	$active = array(false, false);
	if(!isset($_GET['id'])){
		$allow_comments[1] = true;
		$allow_votes[1] = true;
		$view[4] = true;
		$active[1] = true;
		$action = 'addsave';
		$top = '�������� �����';
		$cap = '��������';
	}else{
		$id = SafeEnv($_GET['id'], 11, int);
		$db->Select('forms', "`id`='$id'");
		$form = $db->FetchRow();
		$hname = SafeDB($form['hname'], 255, str);
		$name = SafeDB($form['name'], 255, str);
		$desc = SafeDB($form['desc'], 5000, str, false, false);
		$view[SafeDB($form['view'], 1, int)] = true;
		$active[SafeDB($form['active'], 1, int)] = true;
		$form_action = SafeDB($form['action'], 250, str);
		$email = SafeDB($form['email'], 250, str);
		$send_ok_msg = SafeDB($form['send_ok_msg'], 255, str);
		$action = 'editsave&id='.$id;
		$top = '�������������� �����';
		$cap = '��������� ���������';
	}
	unset($form);
	$visdata = GetUserTypesFormData($view);
	FormRow('��������', $site->Edit('hname', $hname, false, 'maxlength="250" style="width:400px;"'));
	FormRow('��� html', $site->Edit('name', $name, false, 'maxlength="250" style="width:400px;"'));
	FormTextRow('��������', $site->HtmlEditor('desc', $desc, 600, 200));
	FormRow('���������<br /><small>��� �������� �������� �����</small>', $site->Edit('send_ok_msg', $send_ok_msg, false, 'maxlength="250" style="width:400px;"'));
	FormRow('�������� ����������<br /><small>���� �� ������� �� ������������<br /> ����������� ����������</small>', $site->Edit('action', $form_action, false, 'maxlength="250" style="width:400px;"'));
	FormRow('Email<br /><small>��������� ����� �� ��������� e-mail</small>', $site->Edit('email', $email, false, 'maxlength="50" style="width:400px;"'));
	FormRow('��� �����', $site->Select('view', $visdata));
	FormRow('�������', $site->Radio('active', 'on', $active[1]).' ��&nbsp;<br />'.$site->Radio('active', 'off', $active[0]).' ���');
	AddCenterBox($top);
	AddForm('<form action="'.ADMIN_FILE.'?exe=forms&a='.$action.'" method="post">', $site->Button('������', 'onclick="history.go(-1)"').$site->Submit($cap));
}

// ���������� �����
function AdminFormsSave(){
	global $config, $db, $user;
	$hname = SafeEnv($_POST['hname'], 255, str);
	$name = SafeEnv($_POST['name'], 255, str);
	$desc = SafeEnv($_POST['desc'], 0, str);
	$form_action = SafeEnv($_POST['action'], 250, str);
	$email = SafeEnv($_POST['email'], 50, str);
	$msg_ok = SafeEnv($_POST['send_ok_msg'], 255, str);
	$view = ViewLevelToInt($_POST['view']);
	$active = EnToInt($_POST['active']);
	if(isset($_GET['id'])){
		$set = "`hname`='$hname',`name`='$name',`desc`='$desc',`view`='$view',`active`='$active',`action`='$form_action',`email`='$email',`send_ok_msg`='$msg_ok'";
		$id = SafeEnv($_GET['id'], 11, int);
		$db->Update('forms', $set, "`id`='$id'");
		GO(ADMIN_FILE.'?exe=forms');
	}else{
		$form_data = serialize(array());
		$values = Values('', $hname, $name, $desc, 0, 0, 0, $form_data, $active, $view, $form_action, $email, $msg_ok);
		$db->Insert('forms', $values);
		GO(ADMIN_FILE.'?exe=forms');
	}
}

// �������� ����� �����
function AdminFormsFieldEditor( $action ){
	global $site, $cl_plugins, $cs_plugins, $config, $db;
	if(!isset($_GET['id'])){
		GO(ADMIN_FILE.'?exe=forms');
	}
	$id = SafeEnv($_GET['id'], 11, int);
	$collsd = array();
	for($i = 1; $i < 11; $i++){
		$site->DataAdd($collsd, $i, $i);
	}
	$getfuncdata = array();
	$site->DataAdd($getfuncdata, '', '');
	foreach($cl_plugins as $pl){
		$site->DataAdd($getfuncdata, $pl[0], $pl[0]);
	}
	$savefuncdata = array();
	$site->DataAdd($savefuncdata, '', '');
	foreach($cs_plugins as $pl){
		$site->DataAdd($savefuncdata, $pl[0], $pl[0]);
	}
	$controls = array();
	$site->DataAdd($controls, 'edit', '��������� ����');
	$site->DataAdd($controls, 'text', '������� ��������������');
	$site->DataAdd($controls, 'combo', '�������������� ������');
	$site->DataAdd($controls, 'list', '������ (���������������)');
	$site->DataAdd($controls, 'check', '������');
	$site->DataAdd($controls, 'radio', '�����������');
	//$site->DataAdd($controls,'file','����');
	$datatypes = array();
	$site->DataAdd($datatypes, 'int', '�������������');
	$site->DataAdd($datatypes, 'float', '������������');
	$site->DataAdd($datatypes, 'string', '���������');
	$site->DataAdd($datatypes, 'bool', '����������');
	//$site->DataAdd($datatypes,'file','����');
	if($action == 'add'){
		$hname = '';
		$name = '';
		$width = '';
		$height = '';
		$length = '0';
		$values = '';
		$cp = '��������';
		$edit = false;
		System::admin()->FormTitleRow('�������� ����');
	}else{
		$index = SafeEnv($_GET['index'], 11, int);
		$db->Select('forms', "`id`='$id'");
		$form = $db->FetchRow();
		$fields = unserialize($form['form_data']);
		$field = $fields[$index];
		$hname = $field['hname'];
		$name = $field['name'];
		$stype = FormsParseParams(explode(':', $field['kind']));
		$width = $stype['width'];
		$height = $stype['height'];
		$cols = $stype['cols'];
		$controls['selected'] = $stype['control'];
		$collsd['selected'] = $cols;
		$vv = explode(':', $field['values']);
		if(count($vv) == 2 && FormsConfigCheck2Func($vv[0], $vv[1])){
			$getfuncdata['selected'] = $vv[1];
			$values = '';
		}else{
			$values = $field['values'];
		}
		if(function_exists($field['savefunc'])){
			$savefuncdata['selected'] = $field['savefunc'];
		}
		$type = explode(',', $field['type']);
		$datatypes['selected'] = $type[1];
		$length = $type[0];
		$cp = '��������� ���������';
		$edit = true;
	}
	FormRow('��������', $site->Edit('hname', $hname, false, 'maxlength="250" style="width:400px;"'));
	FormRow('��� HTML<br /><small>���������� ��� ���� �����</small>', $site->Edit('name', $name, false, 'maxlength="250" style="width:400px;"'));
	FormRow('������� ����������<br /><small>������� ������� ���������<br />����� ������ � ������</small>',
		$site->Select('control', $controls)
		.'<table cellspacing="0" cellpadding="0" border="0">'
		.'<tr><td style="border:none">������:</td>'
		.'<td style="border:none">'.$site->Edit('cwidth', $width, false, 'style="width:100px;"').'</td></tr>'
		.'<tr><td style="border:none">������:</td>'
		.'<td style="border:none">'.$site->Edit('cheight', $height, false, 'style="width:100px;"').'</td></tr>'
		.'<tr><td style="border:none">�������:</td>'
		.'<td style="border:none">'.$site->Select('ccols', $collsd).'</td></tr>'
		.'</table>'
	);
	FormTextRow('��������� �������� <small>��������: name:���,name:���, .... ������ ��� ��������� ������.</small>', $site->TextArea('values', $values, 'style="width:600px;height:100px;"'));
	FormRow('������� ���������� ��������', $site->Select('valuesfunc', $getfuncdata));
	FormRow('������� ����������', $site->Select('savefunc', $savefuncdata));
	FormRow('��� ������', $site->Select('datatype', $datatypes));
	FormRow('����� ����(������ ����� ��.)<br /><small>0 - �������������</small>', $site->Edit('maxlength', $length, false, 'style="width:60px;" maxlength="11"'));
	AddForm($site->FormOpen(ADMIN_FILE.'?exe=forms&a=addfield&id='.$id.($edit ? '&index='.$index : '')), ($edit ? $site->Button('������', 'onclick="history.go(-1);"') : '').$site->Submit($cp));
}

function AdminFormsFields(){
	global $db, $config, $site;
	if(!isset($_GET['id'])){
		GO(ADMIN_FILE.'?exe=forms');
	}
	$id = SafeEnv($_GET['id'], 11, int);
	$db->Select('forms', "`id`='$id'");
	$form = $db->FetchRow();
	$fields = unserialize($form['form_data']);
	$cnt = count($fields);
	AddCenterBox('���� ����� "'.$form['hname'].'"');
	$text = '';
	if($cnt > 0){
		$text .= '<table cellspacing="0" cellpadding="0" class="cfgtable">';
		$text .= '<tr><th>��������</th><th>��� HTML</th><th>������������</th><th>���</th><th>������������ �����</th><th>������� ����������</th><th>�������</th></tr>';
		for($i = 0; $i < $cnt; $i++){
			$func = '';
			$func .= SpeedButton('�������������', ADMIN_FILE.'?exe=forms&a=editfield&id='.SafeDB($form['id'], 11, int).'&index='.$i, 'images/admin/edit.png');
			$func .= SpeedButton('�������', ADMIN_FILE.'?exe=forms&a=delfield&id='.SafeDB($form['id'], 11, int).'&index='.$i.'&ok=0', 'images/admin/delete.png');

			$type = explode(',', $fields[$i]['type']);
			$text .= '<tr><td>'.SafeDB($fields[$i]['hname'], 255, str).'</td><td>'.SafeDB($fields[$i]['name'], 255, str).'</td><td>'.FormsGetControl($fields[$i]['name'], '', $fields[$i]['kind'], $fields[$i]['type'], $fields[$i]['values']).'</td><td>'.SafeDB($type[1], 50, str).'</td><td>'.SafeDB($type[0], 11, int).'</td><td>'.$fields[$i]['kind'].'</td><td>'.$func.'</td></tr>';
		}
		$text .= '</table><br />';
	}else{
		$text = '<br /><p>� ���� ����� ��� �����.</p>';
	}
	AddText($text);
	AdminFormsFieldEditor('add');
}

function AdminFormsFieldSave(){
	global $db, $config;
	if(!isset($_GET['id'])){
		GO(ADMIN_FILE.'?exe=forms');
	}
	$id = SafeEnv($_GET['id'], 11, int);
	$db->Select('forms', "`id`='$id'");
	if($db->NumRows() == 0){
		GO(ADMIN_FILE.'?exe=forms');
	}
	$form = $db->FetchRow();
	$fields = unserialize($form['form_data']);
	$cnt = count($fields);
	$hname = SafeEnv($_POST['hname'], 250, str);
	$name = SafeEnv($_POST['name'], 250, str);
	$kind = '';
	$values = '';
	$savefunc = '';
	$type = '';
	//���������� kind
	$control = SafeEnv($_POST['control'], 25, str);
	$kind .= $control;
	$width = SafeEnv($_POST['cwidth'], 11, str);
	$height = SafeEnv($_POST['cheight'], 11, str);
	$cols = SafeEnv($_POST['ccols'], 11, int);
	if($width != '' && $width != 0){
		$kind .= ':w'.$width;
	}
	if($height != '' && $height != 0){
		$kind .= ':h'.$height;
	}
	if($cols > 1){
		$kind .= ':c'.$cols;
	}
	// ���������� values
	$getfunc = SafeEnv($_POST['valuesfunc'], 255, str);
	if($getfunc == '' || !function_exists(CONF_GET_PREFIX.$getfunc)){
		$values = SafeEnv($_POST['values'], 0, str);
	}else{
		$values = 'function:'.$getfunc;
	}
	// ���������� Savefunc
	if(function_exists(CONF_GET_PREFIX.$_POST['savefunc'])){
		$savefunc = SafeEnv($_POST['savefunc'], 255, str);
	}else{
		$savefunc = '';
	}
	// ���������� type
	$maxlenght = SafeEnv($_POST['maxlength'], 11, int);
	if($control == 'file'){
		$type = 'file';
	}else{
		$type = SafeEnv($_POST['datatype'], 255, str);
	}
	$striptags = 'true';
	$type = $maxlenght.','.$type.','.$striptags;
	if(isset($_GET['index'])){
		$fields[SafeEnv($_GET['index'], 11, int)] = array('hname'=>$hname, 'name'=>$name, 'kind'=>$kind, 'values'=>$values, 'savefunc'=>$savefunc, 'type'=>$type);
	}else{
		$fields[] = array('hname'=>$hname, 'name'=>$name, 'kind'=>$kind, 'values'=>$values, 'savefunc'=>$savefunc, 'type'=>$type);
	}
	$cnt = count($fields);
	$fields = serialize($fields);
	$db->Update('forms', "numfields='$cnt',form_data='$fields'", "`id`='$id'");
	GO(ADMIN_FILE.'?exe=forms&a=fields&id='.$id);
}

function AdminFormsEditFields(){
	AddCenterBox('�������������� ����');
	AdminFormsFieldEditor('edit');
}

function AdminFormsDelField(){
	global $db, $config;
	if(!isset($_GET['id']) || !isset($_GET['index'])){
		return;
	}
	$id = SafeEnv($_GET['id'], 11, int);
	$index = SafeEnv($_GET['index'], 11, int);
	$db->Select('forms', "`id`='$id'");
	if($db->NumQueries == 0){
		return;
	}
	$form = $db->FetchRow();
	$fields = unserialize($form['form_data']);
	if(isset($_GET['ok']) && $_GET['ok'] == '1'){
		unset($fields[$index]);
		$fields2 = array();
		foreach($fields as $field){
			$fields2[] = $field;
		}
		$cnt = count($fields2);
		$fields = serialize($fields2);
		$db->Update('forms', "numfields='$cnt',form_data='$fields'", "`id`='$id'");
		GO(ADMIN_FILE.'?exe=forms&a=fields&id='.$id);
	}else{
		$text = '�� ������������� ������ ������� ���� "'.$fields[$index]['hname'].'"<br />'.'<a href="'.ADMIN_FILE.'?exe=forms&a=delfield&id='.$id.'&index='.$index.'&ok=1">��</a> &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">���</a>';
		AddTextBox("��������", $text);
	}
}

function AdminFormsDelete(){
	global $config, $db;
	if(!isset($_GET['id'])){
		GO(ADMIN_FILE.'?exe=forms');
	}
	if(isset($_GET['ok']) && $_GET['ok'] == '1'){
		$db->Delete('forms', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
		$db->Delete('forms_data', "`form_id`='".SafeEnv($_GET['id'], 11, int)."'");
		GO(ADMIN_FILE.'?exe=forms');
	}else{
		$r = $db->Select('forms', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
		$text = '�� ������������� ������ ������� Web-����� "'.$r[0]['hname'].'"<br />'
		.'<a href="'.ADMIN_FILE.'?exe=forms&a=del&id='.SafeEnv($_GET['id'], 11, int).'&ok=1">��</a> &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">���</a>';
		AddTextBox("��������", $text);
	}
}

function AdminFormsChangeStatus(){
	global $config, $db;
	$db->Select('forms', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	if($db->NumRows() > 0){
		$r = $db->FetchRow();
		if(SafeDB($r['active'], 1, int) == 1){
			$en = '0';
		}else{
			$en = '1';
		}
		$db->Update('forms', "active='$en'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	}
	GO(ADMIN_FILE.'?exe=forms');
}

function AdminFormsViewPosts( $new ){
	global $db, $config;
	if(!isset($_GET['id'])){
		return;
	}
	$id = SafeEnv($_GET['id'], 11, int);
	$db->Select('forms', "`id`='$id'");
	$form = $db->FetchRow();
	$box_title = $form['hname'];
	if($new){
		$moderated = " and `moderated`='0'";
	}else{
		$moderated = '';
	}
	$posts = $db->Select('forms_data', "`form_id`='$id'".$moderated);
	if(count($posts) == 0){
		AddTextBox('����� ��c�� ����� "'.$box_title.'"', '��� ����� ���������');
		return;
	}
	$text = '';
	foreach($posts as $post){
		$time = TimeRender(SafeDB($post['time'], 11, int));
		if($post['user_id'] > 0){
			$user_info = GetUserInfo(SafeDB($post['user_id'], 11, int));
			$user_name = '<a href="'.'index.php?name=user&op=userinfo&user='.$user_info['id'].'">'.$user_info['name'].'</a>';
		}else{
			$user_name = '-';
		}
		$ip = SafeDB($post['user_ip'], 20, str);
		$data_rows = unserialize($post['data']);
		$post_text = '';
		foreach($data_rows as $row){
			$post_text .= '<b>'.SafeDB($row[0], 255, str).':</b><br />'.SafeDB($row[1], 0, str).'<br />';
		}
		$delfunc = '<a href="'.ADMIN_FILE.'?exe=forms&a=delpost&id='.$id.'&pid='.SafeDB($post['id'], 11, int).'&ok=0&new=1"><img src="images/admin/delete.png" title="������� ��� ������" /></a>';
		$text .= '<table cellspacing="0" cellpadding="0" border="0" class="cfgtable">';
		$text .= '<tr><th>����: '.$time.'</td><th>������������: '.$user_name.'</td><th>IP: '.$ip.'</td><th width="30">'.$delfunc.'</td></tr>';
		$text .= '<tr><td colspan="4" style="text-align:left;padding-left:10px;">'.$post_text.'</td></tr>';
		$text .= '</table>';
	}
	if($new){
		$text .= '<br /><a href="'.ADMIN_FILE.'?exe=forms&a=checkall&id='.$id.'">�������� ��� ��� �������������</a><br /><br />';
	}
	AddTextBox('����� ��c�� ����� "'.$box_title.'"', $text);
}

function AdminFormsDeletePost(){
	global $config, $db;
	if(!isset($_GET['pid']) || !isset($_GET['id'])){
		GO(ADMIN_FILE.'?exe=forms');
	}
	$post_id = SafeEnv($_GET['pid'], 11, int);
	$form_id = SafeEnv($_GET['id'], 11, int);
	$new = isset($_GET['new']);
	if(isset($_GET['ok']) && $_GET['ok'] == '1'){

		$db->Select('forms_data', "`id`='$post_id'");
		$forms_data = $db->FetchRow();
		$db->Delete('forms_data', "`id`='$post_id'");

		$db->Select('forms', "`id`='$form_id'");
		$form = $db->FetchRow();

		if($forms_data['moderated'] == '1'){
			$value1 = (int)$form['new_answ'];
		}else{
			$value1 = (int)$form['new_answ'] - 1;
		}
		$value2 = (int)$form['answ'] - 1;
		$set = "new_answ='$value1',answ='$value2'";
		$db->Update('forms', $set, "`id`='$form_id'");
		GO(ADMIN_FILE.'?exe=forms'.(isset($_GET['new']) ? '&a=newposts&id='.SafeEnv($_GET['id'], 11, int) : ''));
	}else{
		$text = '�� ������������� ������ ������� ������ �����.<br />'.'<a href="'.ADMIN_FILE.'?exe=forms&a=delpost&id='.$form_id.'&pid='.$post_id.'&ok=1'.(isset($_GET['new']) ? '&new=1' : '').'">��</a> &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">���</a>';
		AddTextBox("��������", $text);
	}
}

function AdminFormsCheckAll(){
	global $db, $config;
	if(!isset($_GET['id'])){
		GO(ADMIN_FILE.'?exe=forms');
	}
	$form_id = SafeEnv($_GET['id'], 11, int);
	$db->Update('forms_data', "moderated='1'", "`form_id`='$form_id'");
	$db->Update('forms', "new_answ='0'", "`id`='$form_id'");
	GO(ADMIN_FILE.'?exe=forms');
}
