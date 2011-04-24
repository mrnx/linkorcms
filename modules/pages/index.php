<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$site->SetTitle('Страницы');

if(isset($_GET['file']) && $_GET['file'] != ''){
	$link = SafeEnv($_GET['file'], 255, str);
}else{
	$link = $config['pages']['default_page'];
}

$db->Select('pages', "`link`='$link' and `enabled`='1' and `type`='page'");

if($db->NumRows() > 0){
	$page = $db->FetchRow();
	$hits = SafeDB($page['hits'], 11, int) + 1;
	$db->Update('pages', "hits='$hits'", "`link`='$link'");
	if($user->AccessIsResolved($page['view'])){

		$site->SetTitle(SafeDB($page['title'], 255, str));
		//Модуль SEO
		$site->SeoTitle = SafeDB($page['seo_title'], 255, str);
		$site->SeoKeyWords = SafeDB($page['seo_keywords'], 255, str);
		$site->SeoDescription = SafeDB($page['seo_description'], 255, str);
		//
		$site->AddTemplatedBox('', 'module/page.html');
		$site->AddBlock('page');
		$vars = array();
		ErrorsOff();
		$vars['show_title'] = $page['info_showmode'][0] == '1';
		$vars['show_copy'] = $page['info_showmode'][1] == '1';
		$vars['show_public'] = $page['info_showmode'][2] == '1';
		$vars['show_modified'] = $page['info_showmode'][3] == '1';
		$vars['show_hits'] = $page['info_showmode'][4] == '1';
		ErrorsOn();
		$vars['title'] = SafeDB($page['title'], 255, str);
		if($page['auto_br'] == '1'){
			$text = nl2br(SafeDB($page['text'], 0, str, false, false));
		}else{
			$text = SafeDB($page['text'], 0, str, false, false);
		}
		$vars['text'] = $text;
		$vars['copyright'] = 'Авторское право &copy; '.SafeDB($page['copyright'], 255, str).' Все права защищены.';
		$vars['public'] = 'Страница опубликована: '.TimeRender(SafeDB($page['date'], 11, int));
		$vars['hits'] = ' Просмотров: '.SafeDB($page['hits'], 11, int);
		$vars['modified'] = 'Изменена: '.TimeRender(SafeDB($page['modified'], 11, int));
		$site->Blocks['page']['vars'] = $vars;
	}else{
		$site->AddTextBox('', '<center><p>Доступ к этой странице запрещен.</p></center>');
	}
}else{
	$site->AddTextBox('', '<center><p>Страница не существует или временно недоступна.</p></center>');
}

?>