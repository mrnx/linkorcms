<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if($userAuth === 1 && $userAccess === 1){
}else{
	exit("Доступ запрещен!");
}

include_once($config['inc_dir'].'page_template.class.php');

$site = new PageTemplate();
$site->InitPageTemplate();
$site->Doctype = '<!doctype html>';

$site->Title = 'Подготовка писем';

$site->SetRoot($config['inc_dir'].'template/');
$site->SetTempVar('head', 'body', 'form_page.html');

$vars = array();
$vars['dir'] = $site->Root;

$site->AddBlock('template', true, false, 'page');
$site->Blocks['template']['vars'] = $vars;
$site->AddCSSFile('style.css');
$site->AddBlock('content_box', true, true, '', 'content_box.html');

$form_rows = array();
$cur_bid = 0;

function AddCenterBox($title)
{
    global $site, $config, $cur_bid;
    $cur_bid = $site->AddSubBlock('content_box',true,array('title'=>$title),array(),'','',array('contents'=>$site->CreateBlock(true,true,'content')));
}

function AddText($text)
{
	global $site, $cur_bid;
	$site->Blocks['content_box']['sub'][$cur_bid]['child']['contents']['sub'][] = $site->CreateSubBlock(true,array(),array(),'',$text);
}

function AddTextBox($title,$text)
{
	AddCenterBox($title);
	AddText($text);
}

function FormClear()
{
	global $form_rows;
	$form_rows = array();
}

function AddForm($open,$submit_btn)
{
	global $site, $form_rows, $cur_bid;
	$rows = $site->CreateBlock(true,true,'row');
	for($i=0,$c=count($form_rows);$i<$c;$i++){
		if($form_rows[$i][0]=='row'){
			$rows['sub'][] = $site->CreateSubBlock(true,$form_rows[$i][1]);
		}else{
			$rows['sub'][] = $site->CreateSubBlock(true,$form_rows[$i][1],array(),'form_textarea.html');
		}
	}
	$site->Blocks['content_box']['sub'][$cur_bid]['child']['contents']['sub'][]
	= $site->CreateSubBlock(true,array('form_open'=>$open,'form_submit'=>$submit_btn),array(),'form.html','',array('rows'=>$rows));
	FormClear();
}

function FormRow($capt, $ctrl)
{
	global $site, $form_rows;
	$args = func_get_args();
	if(count($args)>2){
		$wid = 'width="'.$args[2].'"';
	}else{
		$wid = '';
	}
	$form_rows[] = array('row',array('caption'=>$capt,'control'=>$ctrl,'width'=>$wid));
}

function FormTextRow($capt, $ctrl)
{
	global $site, $form_rows;
	$form_rows[] = array('coll',array('caption'=>$capt,'control'=>$ctrl));
}

function TEcho()
{
	global $site;
	$site->EchoAll();
}

if(isset($_GET['op']) && $_GET['op']=='send'){
	AdminPluginMailSend();
}else{
	AdminPluginMail();
}

function AdminPluginMail()
{
	global $site, $config;
	AddCenterBox('Подготовка писем');
	$email = '';
	$nick = '';
	if(isset($_GET['email'])){
		$email = SafeEnv($_GET['email'],255,str);
	}
	if(isset($_GET['toname'])){
		$nick = SafeEnv($_GET['toname'],255,str);
	}
	FormRow('E-mail',$site->Edit('email',$email,false,'style="width:400px;"'));
	FormRow('Кому',$site->Edit('nick',$nick,false,'style="width:400px;"'));
	FormRow('Заголовок',$site->Edit('subject','',false,'style="width:400px;"'));
	FormTextRow('Текст письма', $site->TextArea('message', '', 'style="width:600px;height:300px;"'));
	FormRow('Отправить в HTML-форме',$site->Check('html','1',false));
	$from = $config['general']['site_name'];
	$from_email = $config['general']['site_email'];
	FormRow('От кого',$site->Edit('from',$from,false,'style="width:400px;"'));
	FormRow('E-mail отправителя',$site->Edit('from_email',$from_email,false,'style="width:400px;"'));
	AddForm('<form action="index.php?name=plugins&p=mail&op=send" method="POST">',$site->Submit('Отправить'));
	TEcho();
}

function AdminPluginMailSend()
{
	$email = SafeEnv($_POST['email'], 255, str);
	$to = SafeEnv($_POST['nick'], 255, str);
	$subject = SafeEnv($_POST['subject'], 255, str, false, false, false);
	$message = SafeEnv($_POST['message'], 0, str, false, false, false);
	$html = isset($_POST['html']);
	$from = SafeEnv($_POST['from'], 255, str);
	$from_email = SafeEnv($_POST['from_email'], 255, str);

	SendMail(
		$to,
		$email,
		$subject,
		$message,
		$html,
		$from,
		$from_email
	);

	AddTextBox('Подготовка писем','<p><br>Письмо отправлено.<br><br></p>');
	TEcho();
}

?>