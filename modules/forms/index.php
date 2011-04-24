<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

include_once ($config['inc_dir'].'forms.inc.php');
$site->SetTitle('Web-формы');

function IndexFormsAddRow( $control )
{
	global $site;
	$vars = array();
	$vars['hname'] = SafeEnv($control['hname'], 255, str);
	$vars['control'] = FormsGetControl(SafeDB($control['name'], 255, str), '', $control['kind'], $control['type'], $control['values']);
	$site->AddSubBlock('form_fields', true, $vars);
}

function IndexFormsViewForm( $id )
{
	global $db, $site;
	$db->Select('forms', "`id`='$id' && `active`='1'");
	if($db->NumRows() == 0){
		GO(Ufu('index.php'));
	}
	$form = $db->FetchRow();
	$vars['title'] = SafeDB($form['hname'], 255, str);
	$site->SetTitle($vars['title']);

	$vars['desc'] = SafeDB($form['desc'], 5000, str, false, false);
	$controls = unserialize($form['form_data']);
	if(trim($form['action']) != ''){
		$action = SafeDB($form['action'], 250, str);
	}else{
		$action = Ufu("index.php?name=forms&form=$id&op=save", 'forms/{op}/');
	}
	$site->AddBlock('forms', true, false, 'form');
	$site->AddBlock('form_fields', true, true, 'field');
	$site->AddTemplatedBox('', 'module/forms.html');
	$enctype = '';
	foreach($controls as $control){
		$kind = explode(':', $control['kind']);
		if($kind[0] == 'file'){
			$enctype = 'multipart/form-data';
		}
		IndexFormsAddRow($control);
	}
	$vars['open'] = $site->FormOpen($action, 'post', $enctype == 'multipart/form-data');
	$vars['close'] = $site->FormClose();
	$vars['submit'] = $site->Submit('Отправить');
	$site->Blocks['forms']['vars'] = $vars;
}

function IndexFormGetValues( $name, $values )
{
	$vals = explode(':', $values);
	if($vals[0] == 'function'){
		$func = CONF_GET_PREFIX.trim($vals[1]);
		$values = $func($name);
		$vals = array();
		for($i = 0, $cnt = count($values); $i < $cnt; $i++){
			$vals[$values[$i][0]] = $values[$i][1];
		}
	}else{
		$values = explode(',', $values);
		$vals = array();
		for($i = 0, $cnt = count($values); $i < $cnt; $i++){
			$vv = explode(':', $values[$i]);
			$vals[$vv[0]] = $vv[1];
		}
	}
	return $vals;
}

function IndexFormSendMail( $email, $form_name, $time, $user, $ip, $postrows )
{
	global $config;
	$user = GetUserInfo($user);
	$data_rows = unserialize($postrows);
	$post_text = '';
	foreach($data_rows as $row){
		$post_text .= '<b>'.SafeDB($row[0], 255, str).':</b><br />'.SafeDB($row[1], 0, str).'<br />';
	}
	$text = '<html><head><title>Форма</title></head><body>';
	$text .= '<table cellspacing="2" cellpadding="10" border="1">';
	$text .= '<tr><th>Дата: '.TimeRender($time, true, false).'</th><th>Пользователь: '.$user['name'].'( id:'.$user['id'].' )'.'</th><th>IP: '.$ip.'</th></tr>';
	$text .= '<tr><td colspan="3" style="text-align:left;">'.$post_text.'</td></tr>';
	$text .= '</table></body></html>';
	SendMail('Администратор', $email, 'Веб форма "'.$form_name.'"', $text, true);
}

function IndexFormSave( $id )
{
	global $user, $db, $site;
	$db->Select('forms', "`id`='$id' && `active`='1'");
	if($db->NumRows() == 0){
		GO(Ufu('index.php'));
	}
	$form = $db->FetchRow();
	$controls = unserialize($form['form_data']);
	$post_data = array();
	foreach($controls as $control){
		$name = $control['name'];
		$hname = SafeEnv($control['hname'], 255, str);
		$kind = explode(':', $control['kind']);
		$kind = trim(strtolower($kind[0]));
		$savefunc = trim($control['savefunc']);
		$type = trim($control['type']);
		if($type != ''){
			$type = explode(',', $type);
		}else{
			$type = array(255, str, false);
		}
		switch($kind){
			case 'edit':
				if(FormsConfigCheck2Func('function', $savefunc, 'save')){
					$value = CONF_SAVE_PREFIX.$savefunc(FormsCheckType($_POST[$name], $type));
				}else{
					$value = FormsCheckType($_POST[$name], $type);
				}
				break;
			//case 'radio' :
			case 'combo':
				$vals = IndexFormGetValues($name, $control['values']);
				if(FormsConfigCheck2Func('function', $savefunc, 'save')){
					$value = CONF_SAVE_PREFIX.$savefunc(FormsCheckType($_POST[$name], $type));
				}else{
					$value = $vals[$_POST[$name]];
				}
				break;
			case 'text':
				if(FormsConfigCheck2Func('function', $savefunc, 'save')){
					$value = CONF_SAVE_PREFIX.$savefunc(FormsCheckType($_POST[$name], $type));
				}else{
					$value = FormsCheckType($_POST[$name], $type);
				}
				break;
			case 'check':
			case 'list':
				$vals = IndexFormGetValues($name, $control['values']);
				if(FormsConfigCheck2Func('function', $savefunc, 'save')){
					$value = CONF_SAVE_PREFIX.$savefunc(FormsCheckType($_POST[$name], $type));
				}else{
					if(isset($_POST[$name])){
						$c = count($_POST[$name]);
					}else{
						$c = 0;
					}
					$value = '';
					for($k = 0; $k < $c; $k++){
						$value .= ',';
						$value .= $vals[$_POST[$name][$k]];
					}
					$value = substr($value, 1);
				}
				break;
			/*
			case 'file':
				if(FormsConfigCheck2Func('function',$savefunc,'save')){
					$value = CONF_SAVE_PREFIX.$savefunc(FormsCheckType($_POST[$name],$type));
				}else{
					$value = FormsCheckType($_POST[$name],$type);
				}
			break;
			*/
			default:
				if(FormsConfigCheck2Func('function', $savefunc, 'save')){
					$value = CONF_SAVE_PREFIX.$savefunc(FormsCheckType($_POST[$name], $type));
				}else{
					$value = FormsCheckType($_POST[$name], $type);
				}
		}
		$post_data[] = array($control['hname'], $value, $type);
	}
	$form_id = $id;
	if($user->Auth){
		$user_id = $user->Get('u_id');
	}else{
		$user_id = 0;
	}
	$time = time();
	$data = serialize($post_data);
	$ip = getip();
	if($form['email'] != ''){
		IndexFormSendMail($form['email'], $form['hname'], $time, $user_id, $ip, $data);
	}
	$db->Insert('forms_data', "'','$form_id','$user_id','$time','$data','0','$ip'");
	if($form['send_ok_msg'] != ''){
		$msg = SafeDB($form['send_ok_msg'], 255, str);
	}else{
		$msg = 'Ваша форма отправлена успешно.';
	}
	$new = $form['new_answ'] + 1;
	$cnt = $form['answ'] + 1;
	$db->Update('forms', "answ='$cnt',new_answ='$new'", "`id`='$id'");
	$site->AddTextBox('', '<center>'.$msg.'</center>');
}

if(!isset($_GET['op'])){
	$op = 'view';
}else{
	$op = 'save';
}

if(isset($_GET['form'])){
	$id = SafeEnv($_GET['form'], 11, int);
}else{
	GO(Ufu('index.php'));
}

switch($op){
	case 'view':
		IndexFormsViewForm($id);
		break;
	case 'save':
		IndexFormSave($id);
		break;
	default:
		GO(Ufu('index.php'));
}

?>