<?php

# LinkorCMS
# © 2006-2010 Галицкий Александр Николаевич (linkorcms@yandex.ru)
# Файл:       index_template.inc.php
# Назначение: Шаблонизатор

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

include ($config['inc_dir'].'page_template.class.php'); //class PageTemplate

class Page extends PageTemplate{

	public function InitPage(){
		global $config, $user;

		$this->InitPageTemplate();
		$this->SetGZipCompressionEnabled($config['general']['gzip_status'] == '1');

		$TemplateDir = $config['tpl_dir'].$config['general']['site_template'].'/';
		$DefaultTemplateDir = $config['tpl_dir'].$config['general']['default_template'].'/';

		if(defined('MOD_THEME') && MOD_THEME != '' && (is_file($TemplateDir.'themes/'.MOD_THEME) || is_file($DefaultTemplateDir.'themes/'.MOD_THEME))){
			$ThemeFile = 'themes/'.MOD_THEME;
		}else{
			$ThemeFile = 'theme.html';
		}

		$this->SetRoot($TemplateDir);
		$this->DefaultRoot = $DefaultTemplateDir;

		$this->SetTableTemplate('table/table_open.html', 'table/table_close.html', 'table/table_cell_open.html', 'table/table_cell_close.html');
		$this->SetTempVar('head', 'body', $ThemeFile);

		// Создаем блоки и добавляем переменные
		$this->AddBlock('template', true, false, 'page');
		$this->SetVar('template', 'powered', '<a href="http://linkorcms.ru/" target="_blank">Сайт работает на LinkorCMS</a>');
		$this->SetVar('template', 'dir', $TemplateDir);
		$this->SetVar('template', 'default_dir', $DefaultTemplateDir);
		if(defined('MOD_DIR')){
			$this->SetVar('template', 'mdir', MOD_DIR);
		}
		$this->SetVar('template', 'site_name', $config['general']['site_name']);
		$this->SetVar('template', 'site_slogan', $config['general']['site_slogan']);
		$this->SetVar('template', 'site_email', $config['general']['site_email']);
		$this->SetVar('template', 'copyright', $config['general']['_copyright']);

		$ac = $user->AccessLevel();
		$this->SetVar('template', 'is_system_admin', $user->isSuperUser()); // Системный администратор
		$this->SetVar('template', 'is_admin', $ac == 1); // Любой Администратор
		$this->SetVar('template', 'is_member', $ac == 2); // Пользователь, но не администратор
		$this->SetVar('template', 'is_member_or_admin', $ac == 1 || $ac == 2); // Пользователь или Администратор
		$this->SetVar('template', 'is_member_or_guest', $ac == 2 || $ac == 3 || $ac == 4); // Пользователь или Гость
		$this->SetVar('template', 'is_guest', $ac == 3 || $ac == 4); // Гость
		$this->SetVar('template', 'is_guest_or_admin', $ac == 1 || $ac == 3 || $ac == 4); // Гость или Администратор

		//Информация о пользователе
		$this->SetVar('template', 'u_id', $user->Get('u_id'));
		$this->SetVar('template', 'u_name', $user->Get('u_name'));
		$this->SetVar('template', 'u_avatar', $user->Get('u_avatar'));
		$this->SetVar('template', 'u_avatar_small', $user->Get('u_avatar_small'));
		$this->SetVar('template', 'u_avatar_smallest', $user->Get('u_avatar_smallest'));

		$this->AddBlock('lblocks', true, true, 'block');
		$this->AddBlock('rblocks', true, true, 'block');
		$this->AddBlock('tblocks', true, true, 'block');
		$this->AddBlock('bblocks', true, true, 'block');
		$this->AddBlock('left_coll', false);
		$this->AddBlock('right_coll', false);
		$this->AddBlock('top_coll', false);
		$this->AddBlock('bottom_coll', false);
		$this->AddBlock('content_box', true, true, 'message');
	}

	public function AddUserBlock( $area, $vars, $tempvars, $childs, $template = 'standart.html' ){
		$template = 'block/'.$template;
		if(!file_exists($this->Root.$template)){
			$template = 'block/standart.html';
		}
		switch($area){
			case 'L':
				$this->AddSubBlock('lblocks', true, $vars, $tempvars, $template, '', $childs);
				$this->Blocks['left_coll']['if'] = true;
				break;
			case 'R':
				$this->AddSubBlock('rblocks', true, $vars, $tempvars, $template, '', $childs);
				$this->Blocks['right_coll']['if'] = true;
				break;
			case 'T':
				$this->AddSubBlock('tblocks', true, $vars, $tempvars, $template, '', $childs);
				$this->Blocks['top_coll']['if'] = true;
				break;
			case 'B':
				$this->AddSubBlock('bblocks', true, $vars, $tempvars, $template, '', $childs);
				$this->Blocks['bottom_coll']['if'] = true;
				break;
			default:
				$this->AddSubBlock('lblocks', true, $vars, $tempvars, $template, '', $childs);
				$this->Blocks['left_coll']['if'] = true;
		}
	}

	public function AddTextBox( $title, $content ){
		$this->AddSubBlock('content_box', true, array('container'=>$content, 'title'=>$title), array(), 'box.html');
	}

	public function AddTemplatedBox( $title, $template_file, $vars = array() ){
		$vars['title'] = $title;
		$this->AddSubBlock('content_box', true, $vars, array('container'=>$template_file), 'box.html');
	}

	public function AddMessage( $title, $text, $admin ){
		$this->AddSubBlock('content_box', true, array('title'=>$title, 'text'=>$text, 'admin'=>$admin), array(), 'message.html');
	}

	public function ViewBlocks(){
		global $db, $config, $userAccess, $userAuth, $user, $site;
		$where = "`enabled`='1'";
		$w2 = GetWhereByAccess('view');
		if($w2 != ''){
			$where .= ' and ('.$w2.')';
		}
		$blocks = $db->Select('blocks', $where);
		SortArray($blocks, 'place');
		foreach($blocks as $block){
			$block_config = $block['config'];
			$mblok1=$block['id'];
			$mblok2=$block['showin'];
			$mblok3=$block['showin_uri'] ;
			if( $this->BloksCheckView($mblok1,$mblok2,$mblok3)){
				$area = SafeDB($block['position'], 1, str);
				$title = SafeDB($block['title'], 255, str);
				$enabled = SafeDB($block['enabled'], 1, int);
				$modified = SafeDB($block['modified'], 11, int);
				$cache = SafeDB($block['cache'], 0, str, false, false);
				$vars = array();
				$tempvars = array();
				$childs = array();
				if($enabled){
					include(RealPath2($config['blocks_dir'].$block['type']).'/index.php'); // => $vars
				}
				if($enabled){
					$this->AddUserBlock($area, $vars, $tempvars, $childs, SafeDB(RealPath2($block['template']), 255, str));
				}
			}
		}
	}

	public function Login( $message = '' ){
		global $config;
		$this->AddTemplatedBox('Авторизация', 'login.html');
		$this->AddBlock('login', true, false, 'lf');
		$vars = array();
		$vars['message'] = $message;
		$vars['form_action'] = 'index.php?name=plugins&p=login&a=login&back=main';
		$vars['llogin'] = 'Логин';
		$vars['lpass'] = 'Пароль';
		$vars['lremember'] = 'Запомнить меня';
		$vars['registration'] = $config['user']['registration'] == 'on';
		$vars['lregistration'] = 'Регистрация';
		$vars['registration_url'] = Ufu('index.php?name=user&op=registration', 'user/{op}/');
		$vars['lsubmit'] = 'Вход';
		$this->Blocks['login']['vars'] = $vars;
	}

	public function TEcho(){
		global $db, $config, $user;
		if(defined('INDEX_PHP') && INDEX_PHP == true){
			$title = 'Главная';
		}else{
			$title = $this->Title;
		}
		$user->OnlineProcess($title);
		if($user->Auth){
			$user->ChargePoints($config['points']['browsing']);
		}
		$this->ViewBlocks();
		//Добавляем информацию к странице
		$this->SetVar('template', 'showinfo', System::$config['general']['show_script_time']);
		$this->SetVar('template', 'info', '');
		$this->SetVar('template', 'errors_text', implode(System::$Errors));
		$this->EchoAll();
	}

	function BloksCheckViewpos($uris){
		$r = false;
		foreach($uris as $url){
			if(is_integer(stripos($_SERVER['REQUEST_URI'], $url))){
				$r = true;
			}
		}
		return $r;
	}

	function BloksCheckViewPreg($uris){
		$r = array();
		foreach($uris as $url){
			$url = str_replace("/", "\\/", $url);
			preg_match('/'.$url.'/si', $_SERVER['REQUEST_URI'], $r);
		}
		$res = ((isset($r[0]) and count($r[0]) > 0) ? true : false);
		return $res;
	}

	function BloksCheckView($id, $mods, $uris){
		global $ModuleName, $s_m, $user;
		$r = false;
		if($mods == '' and  $uris == ''){
			return true;
		}
		$mods = unserialize($mods);
		$uris = unserialize($uris);
		if(in_array($ModuleName, $mods)){
			$r = true;
		}elseif($_SERVER['REQUEST_URI'] <> '' && in_array($_SERVER['REQUEST_URI'], $uris)){
			$r = true;
		}elseif($_SERVER['REQUEST_URI'] <> '' && $this->BloksCheckViewpos($uris)){
			$r = true;
		}elseif(INDEX_PHP == true && in_array('INDEX', $mods)){
			$r = true;
		}else{
			$r = false;
		}
		if(in_array('ALL_EXCEPT', $mods)){
			$r = !$r;
		}
		return $r;
	}
}

$site = new Page();
$site->InitPage();
$initfile = $site->Root.'init.php';

if(file_exists($initfile)){
	include ($initfile);
}

unset($initfile);

?>