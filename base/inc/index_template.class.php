<?php

# LinkorCMS
# © 2006-2010 Александр Галицкий (linkorcms@yandex.ru)
# Файл:       index_template.class.php
# Назначение: Шаблонизатор

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

class Page extends PageTemplate{

	public function InitPage(){
		$this->InitPageTemplate();
		$this->SetGZipCompressionEnabled(System::config('general/gzip_status'));

		$TemplateDir = System::config('tpl_dir').System::config('general/site_template').'/';
		$DefaultTemplateDir = System::config('tpl_dir').System::config('general/default_template').'/';

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
		$this->SetVar('template', 'site_name', System::config('general/site_name'));
		$this->SetVar('template', 'site_slogan', System::config('general/site_slogan'));
		$this->SetVar('template', 'site_email', System::config('general/site_email'));
		$this->SetVar('template', 'copyright', System::config('general/_copyright'));

		$ac = System::user()->AccessLevel();
		$this->SetVar('template', 'is_system_admin', System::user()->isSuperUser()); // Системный администратор
		$this->SetVar('template', 'is_admin', $ac == 1); // Любой Администратор
		$this->SetVar('template', 'is_member', $ac == 2); // Пользователь, но не администратор
		$this->SetVar('template', 'is_member_or_admin', $ac == 1 || $ac == 2); // Пользователь или Администратор
		$this->SetVar('template', 'is_member_or_guest', $ac == 2 || $ac == 3 || $ac == 4); // Пользователь или Гость
		$this->SetVar('template', 'is_guest', $ac == 3 || $ac == 4); // Гость
		$this->SetVar('template', 'is_guest_or_admin', $ac == 1 || $ac == 3 || $ac == 4); // Гость или Администратор

		//Информация о пользователе
		$this->SetVar('template', 'u_id', System::user()->Get('u_id'));
		$this->SetVar('template', 'u_name', System::user()->Get('u_name'));
		$this->SetVar('template', 'u_avatar', System::user()->Get('u_avatar'));
		$this->SetVar('template', 'u_avatar_small', System::user()->Get('u_avatar_small'));
		$this->SetVar('template', 'u_avatar_smallest', System::user()->Get('u_avatar_smallest'));

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
		global $config, $site, $db, $user; // для совместимости, НЕ УДАЛЯТЬ
		$blocks = System::database()->Select('blocks', GetWhereByAccess('view', "`enabled`='1'"));
		SortArray($blocks, 'place');
		foreach($blocks as $block){
			$block_config = $block['config'];
			$mblok1=$block['id'];
			$mblok2=$block['showin'];
			$mblok3=$block['showin_uri'] ;
			if($this->BloksCheckView($mblok1,$mblok2,$mblok3)){
				$area = SafeDB($block['position'], 1, str);
				$title = SafeDB($block['title'], 255, str);
				$enabled = SafeDB($block['enabled'], 1, int);
				$modified = SafeDB($block['modified'], 11, int);
				$cache = SafeDB($block['cache'], 0, str, false, false);
				$vars = array();
				$tempvars = array();
				$childs = array();
				if($enabled){
					include(RealPath2(System::config('blocks_dir').$block['type']).'/index.php'); // => $vars
				}
				if($enabled){
					$this->AddUserBlock($area, $vars, $tempvars, $childs, SafeDB(RealPath2($block['template']), 255, str));
				}
			}
		}
	}

	public function Login( $message = '' ){
		$this->AddTemplatedBox('Авторизация', 'login.html');
		$this->AddBlock('login', true, false, 'lf');
		$vars = array();
		$vars['message'] = $message;
		$vars['form_action'] = 'index.php?name=plugins&p=login&a=login&back=main';
		$vars['llogin'] = 'Логин';
		$vars['lpass'] = 'Пароль';
		$vars['lremember'] = 'Запомнить меня';
		$vars['registration'] = System::config('user/registration') == 'on';
		$vars['lregistration'] = 'Регистрация';
		$vars['registration_url'] = Ufu('index.php?name=user&op=registration', 'user/{op}/');
		$vars['lsubmit'] = 'Вход';
		$this->Blocks['login']['vars'] = $vars;
	}

	public function TEcho(){
		if(defined('INDEX_PHP') && INDEX_PHP == true){
			$title = 'Главная';
		}else{
			$title = $this->Title;
		}
		System::user()->OnlineProcess($title);
		if(System::user()->Auth){
			System::user()->ChargePoints(System::config('points/browsing'));
		}
		$this->ViewBlocks();
		//Добавляем информацию к странице
		$this->SetVar('template', 'showinfo', System::config('general/show_script_time'));
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
