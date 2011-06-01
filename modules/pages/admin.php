<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('Страницы');

if(!$user->CheckAccess2('pages', 'pages')){
	AddTextBox('Ошибка', $config['general']['admin_accd']);
	return;
}

$text = '';
include_once ($config['inc_dir'].'configuration/functions.php');

if(isset($_GET['a'])){
	AdminPages($_GET['a']);
}else{
	AdminPages('main');
}

function AdminPages( $action ){
	TAddToolLink('Страницы', 'main', 'pages');
	TAddToolLink('Добавить страницу', 'editor', 'pages&a=editor');
	TAddToolLink('Добавить ссылку', 'link', 'pages&a=link');
	TAddToolLink('Добавить категорию', 'cat', 'pages&a=cat');
	TAddToolLink('Настройки', 'config', 'pages&a=config');
	TAddToolBox($action);
	switch($action){
		case 'main':
		case 'ajaxtree':
		case 'ajaxnode':
			AdminPagesAjaxTree();
			break;
		case 'ajaxmove':
			AdminPagesAjaxMove();
			break;
		case 'delete':
			AdminPagesDelete();
			break;
		case 'editor':
			if(isset($_POST['action']) && $_POST['action'] != 'preview'){
				AdminPagesSave();
			}else{
				AdminPagesEditor();
			}
			break;
		case 'link':
			AdminPagesLinkEditor();
			break;
		case 'savelink':
			AdminPagesLinkSave();
			break;
		case 'cat':
			AdminPagesCatEditor();
			break;
		case 'savecat':
			AdminPagesCatSave();
			break;
		case 'changestatus':
			AdminPagesChangeStatus();
			break;
		case 'changemenu':
			AdminPagesChangeMenu();
			break;
		case 'resetcounter':
			AdminPagesResetCounter();
			break;
		case 'move':
			AdminPagesMove();
			break;
		case 'config':
			AdminConfigurationEdit('pages', 'pages', false, false, 'Конфигурация модуля "Страницы"');
			return true;
			break;
		case 'configsave':
			AdminConfigurationSave('pages&a=config', 'pages', false);
			return true;
			break;
		default:
			AdminPagesAjaxTree();
	}
}

/**
 * Очистка кэша страниц и меню
 * @return void
 */
function AdminPagesClearCache(){
	$bcache = LmFileCache::Instance();
	$bcache->Delete('block', 'menu1');
	$bcache->Delete('block', 'menu2');
	$bcache->Delete('block', 'menu3');
	$bcache->Delete('block', 'menu4');
	$bcache->Delete('tree', 'pages'); // Нестандартное дерево, используем апи при построении но не используем его при редактировании
}

/**
 * Определяет количество страниц в категории
 * @param  $parent_id
 * @return int
 */
function AdminPagesNewOrder( $parent_id ){
	System::database()->Select('pages', "`parent`='$parent_id'");
	return System::database()->NumRows();
}

/**
 * Генерирует статическую таблицу страниц в виде дерева
 * @param  $row
 * @param  $level
 * @return void
 */
function AdminPagesRender( $row, $level ){
	global $config, $text, $pages_tree;
	$vi = ViewLevelToStr(SafeDB($row['view'], 1, int));
	$pid = SafeDB($row['id'], 11, int);
	switch($row['enabled']){
		case '1':
			$st = '<a href="'.$config['admin_file'].'?exe=pages&a=changestatus&id='.$pid.'" title="Выключить"><font color="#008000">Вкл.</font></a>';
			break;
		case '0':
			$st = '<a href="'.$config['admin_file'].'?exe=pages&a=changestatus&id='.$pid.'" title="Включить"><font color="#FF0000">Выкл.</font></a>';
			break;
	}
	switch($row['showinmenu']){
		case '1':
			$menu = '<a href="'.$config['admin_file'].'?exe=pages&a=changemenu&id='.$pid.'" title="Выключить"><font color="#008000">Да</font></a>';
			break;
		case '0':
			$menu = '<a href="'.$config['admin_file'].'?exe=pages&a=changemenu&id='.$pid.'" title="Включить"><font color="#FF0000">Нет</font></a>';
			break;
		default:
			$menu = '<a href="'.$config['admin_file'].'?exe=pages&a=changemenu&id='.$pid.'" title="Включить"><font color="#FF0000">Нет</font></a>';
	}
	if($row['type'] == 'page'){
		$link = Ufu('index.php?name=pages&file='.SafeDB($row['link'], 255, str), 'pages/{file}.html');
		$counter = SafeDB($row['hits'], 11, int).' / <a href="'.$config['admin_file'].'?exe=pages&a=resetcounter&id='.SafeDB($row['id'], 11, int).'" title="Сбросить счётчик">Сбросить</a>';
		$editlink = $config['admin_file'].'?exe=pages&a=editor&id='.SafeDB($row['id'], 11, int);
		$type = 'Страница';
	}elseif($row['type'] == 'link'){
		$link = SafeDB($row['text'], 255, str);
		if(substr($link, 0, 6) == 'mod://'){
			$link = Ufu('index.php?name='.substr($link, 6), '{name}/');
		}
		$counter = '&nbsp;-&nbsp;';
		$editlink = $config['admin_file'].'?exe=pages&a=link&id='.SafeDB($row['id'], 11, int);
		$type = 'Ссылка';
	}
	$levs = '<table cellspacing="0" cellpadding="0" border="0" align="left"><tr>';
	$levs .= str_repeat('<td style="border:none;">&nbsp;-&nbsp;</td>', $level);
	$levs .= '<td align="left" style="text-align:left;padding-left:10px;border:none;"><b><a href="'.$editlink.'">'.SafeDB($row['title'], 255, str).'</a></b></td>';
	$levs .= '</tr></table>';

	$func = '';
	$func .= SpeedButton('Редактировать', $editlink, 'images/admin/edit.png');
	$func .= SpeedButton('Удалить', $config['admin_file'].'?exe=pages&a=del&id='.SafeDB($row['id'], 11, int).'&ok=0', 'images/admin/delete.png');

	$max_place = count($pages_tree->Cats[$row['parent']]) - 1;
	$move_menu = '';
	if($max_place == 0){
		$move_menu .= ' - ';
	}else{
		$order = SafeDB($row['order'], 11, int);
		if($order >= 0 && $order < $max_place){ // Первый элемент
			$move_menu .= SpeedButton('Вниз', $config['admin_file'].'?exe=pages&a=move&to=down&id='.$pid.'&pid='.SafeDB($row['parent'], 11, int), 'images/admin/down.png');
		}
		if($order <= $max_place && $order > 0){
			$move_menu .= SpeedButton('Вверх', $config['admin_file'].'?exe=pages&a=move&to=up&id='.$pid.'&pid='.SafeDB($row['parent'], 11, int), 'images/admin/up.png');
		}
	}
	$text .= '<tr><td>'.$move_menu.'</td><td>'.$levs.'</td><td>'.$type.'</td><td><a href="'.$link.'" target="_blank">'.SafeDB($row['link'], 255, str).'</a></td><td>'.$counter.'</td><td>'.$vi.'</td><td>'.$menu.'</td><td>'.$st.'</td><td>'.$func.'</td></tr>';
}

// Главная страница - статическое дерево
function AdminPagesMain(){
	global $config, $db, $text;
	global $pages_tree;
	$pages = $db->Select('pages');
	SortArray($pages, 'order');
	$pages_tree = new Tree($pages);
	$db->Select('pages', '');
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>Сортировка</th><th>Заголовок</th><th>Тип</th><th>Ссылка</th><th>Просмотров</th><th>Кто видит</th><th>В меню</th><th>Статус</th><th>Функции</th></tr>';
	$pages_tree->ListingTree(0, 'AdminPagesRender');
	$text .= '</table>';
	AddTextBox('Страницы', $text);
}

/**
 * Генерирует Ajax дерево страниц
 * @return void
 */
function AdminPagesAjaxTree(){
	global $pages_tree;

	UseScript('jquery_ui_treeview');

	if(CheckGet('parent')){
		$parent = SafeEnv($_GET['parent'], 11, int);
	}else{
		$parent = 0;
	}

	$pages = System::database()->Select('pages');
	SortArray($pages, 'order');
	$pages_tree = new Tree($pages);

	$elements = array();
	if($parent == 0){
		$func = '';
		$func .= SpeedButton('Добавить дочернюю страницу', ADMIN_FILE.'?exe=pages&a=editor', 'images/admin/page_add.png');
		$func .= SpeedButton('Добавить дочернюю ссылку', ADMIN_FILE.'?exe=pages&a=link', 'images/admin/link_add.png');
		$func .= SpeedButton('Добавить дочернюю категорию', ADMIN_FILE.'?exe=pages&a=cat', 'images/admin/folder_add.png');
		$site_node = array(
			'id'=>'0',
			'title'=> System::config('general/site_name'),
			'icon'=> 'images/globe.png',
			'func'=>$func,
			'isnode'=>true,
			'opened'=>true,
			'childs'=>array()
		);
	}

	foreach($pages_tree->Cats[$parent] as $page){
		$id = SafeDB($page['id'], 11, int);
		if($page['type'] == 'page'){
			$link = Ufu('index.php?name=pages&file='.SafeDB($page['link'], 255, str), 'pages/{file}.html');
			$icon = 'images/page.png';
			$type = 'Страница';
			$counter = SafeDB($page['hits'], 11, int);
			$editlink = ADMIN_FILE.'?exe=pages&a=editor&id='.$id;
		}elseif($page['type'] == 'link'){
			$link = SafeDB($page['text'], 255, str);
			if(substr($link, 0, 6) == 'mod://'){
				$link = Ufu('index.php?name='.substr($link, 6), '{name}/');
			}
			$icon = 'images/link.png';
			$type = 'Ссылка';
			$counter = '-&nbsp;';
			$editlink = ADMIN_FILE.'?exe=pages&a=link&id='.$id;
		}else{
			$link = Ufu('index.php?name=pages&file='.SafeDB($page['link'], 255, str), 'pages/{file}.html');
			$icon = 'images/folder.png';
			$type = 'Категория';
			$counter = '-&nbsp;';
			$editlink = ADMIN_FILE.'?exe=pages&a=cat&id='.$id;
		}
		$func = '';
		$func .= System::admin()->SpeedButton('Добавить дочернюю страницу', ADMIN_FILE.'?exe=pages&a=editor&parent='.$id, 'images/admin/page_add.png');
		$func .= System::admin()->SpeedButton('Добавить дочернюю ссылку', ADMIN_FILE.'?exe=pages&a=link&parent='.$id, 'images/admin/link_add.png');
		$func .= System::admin()->SpeedButton('Добавить дочернюю категорию', ADMIN_FILE.'?exe=pages&a=cat&parent='.$id, 'images/admin/folder_add.png');
		$func .= '&nbsp;';
		$func .= System::admin()->SpeedStatus('Скрыть из меню', 'Показать в меню', ADMIN_FILE.'?exe=pages&a=changemenu&id='.$id.'&ajax', $page['showinmenu'] == '1', 'images/menu_enabled.png', 'images/menu_disabled.png');
		$func .= System::admin()->SpeedStatus('Выключить', 'Включить', ADMIN_FILE.'?exe=pages&a=changestatus&id='.$id.'&ajax', $page['enabled'] == '1', 'images/bullet_green.png', 'images/bullet_red.png');
		$func .= '&nbsp;';
		$func .= System::admin()->SpeedButton('Редактировать', $editlink, 'images/admin/edit.png');
		$func .= System::admin()->SpeedAjax(
			'Удалить',
			'images/admin/delete.png',
			ADMIN_FILE.'?exe=pages&a=ajaxdelete&id='.$id,
			'Уверены что хотите удалить? Все дочерние страницы и ссылки так-же будут удалены.',
			'',
			'$(\'#tree_container\').lTreeView(\'deleteNode\', '.$id.');'
		);

		$view = ViewLevelToStr(SafeDB($page['view'], 1, int));
		$info = "<b>Тип</b>: $type<br />
		<b>Адрес</b>: <a href=\"$link\" target=\"_blank\">/".$link."</a><br />
		".($page['type'] == 'page' ? "<b>Просмотров</b>: $counter<br />" : '' )."
		<b>Видят</b>: $view";

		$elements[] = array(
			'id'=>$id,
			'icon'=>$icon,
			'title'=>'<b><a href="'.$editlink.'" onclick="return Admin.CheckButton(2, event);" onmousedown="return Admin.LoadPage(\''.$editlink.'\', event);">'.SafeDB($page['title'], 255, str).'</a></b>',
			'info'=>$info,
			'func'=>$func,
			'isnode'=>isset($pages_tree->Cats[$id]),
			'child_url'=>'admin.php?exe=pages&a=ajaxtree&parent='.$id,
		);
	}

	if($parent == 0){
		$site_node['childs'] = &$elements;
		$tree = array(&$site_node);
	}else{
		$tree = &$elements;
	}

	if($parent == 0){
		AddTextBox('Страницы', '<div id="tree_container"></div><script>$("#tree_container").treeview({move: \''.ADMIN_FILE.'?exe=pages&a=ajaxmove\', del: \''.ADMIN_FILE.'?exe=pages&a=delete\', tree: '.JsonEncode($tree).'});</script>');
	}else{
		echo JsonEncode($tree);
		exit;
	}
}

/**
 * Проверяет POST данные страницы
 * @param  $link
 * @param  $parent_id
 * @param  $title
 * @param  $text
 * @param  $copy
 * @param  $auto_br
 * @param  $info
 * @param  $view
 * @param  $enabled
 * @param  $seo_title
 * @param  $seo_keywords
 * @param  $seo_description
 * @param  $showinmenu
 * @return void
 */
function AdminPagesAcceptPost( &$link, &$parent_id, &$title, &$text, &$copy, &$auto_br, &$info, &$view, &$enabled, &$seo_title, &$seo_keywords, &$seo_description, &$showinmenu ){
	$link = htmlspecialchars($_POST['link']);
	$parent_id = htmlspecialchars($_POST['parent_id']);
	$title = htmlspecialchars($_POST['title']);
	if($link == ''){
		$link = Translit4Url($title);
	}
	$text = htmlspecialchars($_POST['text']);
	$copy = htmlspecialchars($_POST['copy']);
	$auto_br[EnToInt($_POST['auto_br'])] = true;
	$inf = '';
	if(isset($_POST['ins_title'])){
		$inf .= '1';
	}else{
		$inf .= '0';
	}
	if(isset($_POST['ins_copy'])){
		$inf .= '1';
	}else{
		$inf .= '0';
	}
	if(isset($_POST['ins_date'])){
		$inf .= '1';
	}else{
		$inf .= '0';
	}
	if(isset($_POST['ins_modified'])){
		$inf .= '1';
	}else{
		$inf .= '0';
	}
	if(isset($_POST['ins_counter'])){
		$inf .= '1';
	}else{
		$inf .= '0';
	}
	$info = array($inf[0], $inf[1], $inf[2], $inf[3], $inf[4]);
	$view[ViewLevelToInt($_POST['view'])] = true;
	$enabled[EnToInt($_POST['enabled'])] = true;
	$showinmenu[EnToInt($_POST['showinmenu'])] = true;
	//Модуль SEO
	$seo_title = htmlspecialchars($_POST['seo_title']);
	$seo_keywords = htmlspecialchars($_POST['seo_keywords']);
	$seo_description = htmlspecialchars($_POST['seo_description']);
	//
}

/**
 * Выводит предпросмотр страницы
 * @param  $title
 * @param  $pagetext
 * @param  $copy
 * @param  $auto_br
 * @param  $info
 * @return void
 */
function AdminPagesRenderPage( $title, $pagetext, $copy, $auto_br, $info ){
	$text = '<table cellspacing="0" cellpadding="0" boreder="0" width="100%" style="font-size:10pt;">';
	$text .= '<tr><td align="center"><h1>'.$title.'</h1></td></tr>';
	if($auto_br){
		$pagetext = nl2br($pagetext);
	}
	$text .= '<tr><td align="left">'.$pagetext.'</tr></td>';
	$text .= '<tr><td align="right"> © '.$copy.'</tr></td>';
	$text .= '</table>';
	AddTextBox('Предпросмотр страницы', $text);
}

/**
 * Редактор страницы
 * @return void
 */
function AdminPagesEditor(){
	global $config, $db, $site;

	$link = '';
	$parent_id = -1;
	if(isset($_GET['parent'])){
		$parent_id = SafeEnv($_GET['parent'], 11, int);
	}
	$id = -1;
	$title = '';
	$text = '<p></p>';
	$copy = '';
	$auto_br = array(false, false);
	$info = array(false, false, false, false, false);
	$view = array(1=>false, 2=>false, 3=>false, 4=>false);
	$enabled = array(false, false);
	$showinmenu = array(false, false);

	//Модуль SEO
	$seo_title = '';
	$seo_keywords = '';
	$seo_description = '';
	//

	if(!isset($_GET['id']) && !isset($_POST['method'])){
		$auto_br[0] = true;
		$view[4] = true;
		$enabled[1] = true;
		$showinmenu[1] = true;
		$alname = 'Добавить';
		$met = 'add';
		$url = '';
		$headt = 'Добавление страницы';
	}elseif(isset($_GET['id']) && !isset($_POST['method'])){
		$id = SafeEnv($_GET['id'], 11, int);
		$db->Select('pages', "`id`='".$id."'");
		$pg = $db->FetchRow();
		$link = SafeDB($pg['link'], 255, str);
		$parent_id = SafeDB($pg['parent'], 11, int);
		$title = SafeDB($pg['title'], 255, str);
		$text = SafeDB($pg['text'], 0, str, false);
		$copy = SafeDB($pg['copyright'], 255, str);
		$auto_br[SafeDB($pg['auto_br'], 1, int)] = true;
		$inf = SafeDB($pg['info_showmode'], 5, str);
		$info = array($inf[0], $inf[1], $inf[2], $inf[3], $inf[4]);
		$view[SafeDB($pg['view'], 1, int)] = true;
		$enabled[SafeDB($pg['enabled'], 1, int)] = true;
		$showinmenu[SafeDB($pg['showinmenu'], 1, int)] = true;
		//Модуль SEO
		$seo_title = SafeDB($pg['seo_title'], 255, str);
		$seo_keywords = SafeDB($pg['seo_keywords'], 255, str);
		$seo_description = SafeDB($pg['seo_description'], 255, str);
		//
		$alname = 'Сохранить';
		$met = 'edit';
		$url = '&id='.$id;
		$headt = 'Редактирование страницы';
		unset($pg);
	}elseif(!isset($_GET['id']) && isset($_POST['method'])){
		AdminPagesAcceptPost($link, $parent_id, $title, $text, $copy, $auto_br, $info, $view, $enabled, $seo_title, $seo_keywords, $seo_description, $showinmenu);
		AdminPagesRenderPage($title, $text, $copy, $auto_br[1], $info);
		$alname = 'Добавить';
		$met = 'add';
		$url = '';
		$headt = 'Добавление страницы';
	}elseif(isset($_GET['id']) && isset($_POST['method'])){
		$id = SafeEnv($_GET['id'], 11, int);
		AdminPagesAcceptPost($link, $parent_id, $title, $text, $copy, $auto_br, $info, $view, $enabled, $seo_title, $seo_keywords, $seo_description, $showinmenu);
		AdminPagesRenderPage($title, $text, $copy, $auto_br[1], $info);
		$alname = 'Сохранить';
		$met = 'edit';
		$url = '&id='.$id;
		$headt = 'Редактирование страницы';
	}
	# Возможные родительские страницы
	$tree = new Tree('pages');
	$cats_data = array();
	$cats_data = $tree->GetCatsData($parent_id, false, true, $id, true);
	#Кто видит
	$visdata = array();
	$site->DataAdd($visdata, 'all', 'Все', $view['4']);
	$site->DataAdd($visdata, 'members', 'Только пользователи', $view['2']);
	$site->DataAdd($visdata, 'guests', 'Только гости', $view['3']);
	$site->DataAdd($visdata, 'admins', 'Только администраторы', $view['1']);
	#Действие предпросмотр / разместить/сохранить
	$acts = array();
	$site->DataAdd($acts, 'save', $alname);
	$site->DataAdd($acts, 'preview', 'Предпросмотр');
	///
	FormRow('Ссылка', $site->Edit('link', htmlspecialchars($link), false, 'style="width:400px;" maxlength="255"'));
	FormRow('Родительская страница', $site->Select('parent_id', $cats_data));
	FormRow('Заголовок', $site->Edit('title', htmlspecialchars($title), false, 'style="width:400px;" maxlength="255"'));
	//Модуль SEO
	FormRow('[seo] Заголовок страницы', $site->Edit('seo_title', $seo_title, false, 'style="width:400px;"'));
	FormRow('[seo] Ключевые слова', $site->Edit('seo_keywords', $seo_keywords, false, 'style="width:400px;"'));
	FormRow('[seo] Описание', $site->Edit('seo_description', $seo_description, false, 'style="width:400px;"'));
	//
	FormTextRow('Текст (HTML)', $site->HtmlEditor('text', $text, 600, 400));
	FormRow('Вставлять тег &lt;br&gt;<br />автоматически',
		'<label>'.$site->Radio('auto_br', 'on', $auto_br[1]).'Да</label>&nbsp;'
		.'<label>'.$site->Radio('auto_br', 'off', $auto_br[0]).'Нет</label>');
	FormRow('Авторское право', $site->Edit('copy', htmlspecialchars($copy), false, 'style="width:400px;" maxlength="255"'));
	FormRow('Вставить информацию<br />на страницу',
		'<label>'.$site->Check('ins_title', '1', $info[0]).'Заголовок</label><br />'
		.'<label>'.$site->Check('ins_copy', '1', $info[1]).'Авторские права</label><br />'
		.'<label>'.$site->Check('ins_date', '1', $info[2]).'Дата добавления</label><br />'
		.'<label>'.$site->Check('ins_modified', '1', $info[3]).'Дата изменения (если изменялась)</label><br />'
		.'<label>'.$site->Check('ins_counter', '1', $info[4]).'Количество просмотров</label>'
	);
	FormRow('Кто видит', $site->Select('view', $visdata));
	FormRow('Показать в меню',
		'<label>'.$site->Radio('showinmenu', 'off', $showinmenu[0]).'Нет</label>&nbsp;&nbsp;'
		.'<label>'.$site->Radio('showinmenu', 'on', $showinmenu[1]).'Да</label>');
	FormRow('Включить',
		'<label>'.$site->Radio('enabled', 'off', $enabled[0]).'Нет</label>&nbsp;&nbsp;'
		.'<label>'.$site->Radio('enabled', 'on', $enabled[1]).'Да</label>');
	AddCenterBox($headt);
	AddForm('<form action="'.$config['admin_file'].'?exe=pages&a=editor'.$url.'" method="post">', $site->Hidden('method', $met).$site->Button('Отмена', 'onclick="history.go(-1)"').$site->Select('action', $acts).$site->Submit('Выполнить'));
}

/**
 * Сохранение изменений страницы
 * @return void
 */
function AdminPagesSave(){
	global $db, $config;
	$parent_id = SafeEnv($_POST['parent_id'], 11, int);
	$link = SafeEnv($_POST['link'], 255, str);
	$title = SafeEnv($_POST['title'], 255, str);
	if($link == ''){
		$link = SafeEnv(Translit4Url($title), 255, str);
	}
	$text = SafeEnv($_POST['text'], 0, str);
	$copy = SafeEnv($_POST['copy'], 255, str);
	$auto_br = EnToInt($_POST['auto_br']);
	//Модуль SEO
	$seo_title = SafeEnv($_POST['seo_title'], 255, str);
	$seo_keywords = SafeEnv($_POST['seo_keywords'], 255, str);
	$seo_description = SafeEnv($_POST['seo_description'], 255, str);
	//
	$inf = '';
	if(isset($_POST['ins_title'])){
		$inf .= '1';
	}else{
		$inf .= '0';
	}
	if(isset($_POST['ins_copy'])){
		$inf .= '1';
	}else{
		$inf .= '0';
	}
	if(isset($_POST['ins_date'])){
		$inf .= '1';
	}else{
		$inf .= '0';
	}
	if(isset($_POST['ins_modified'])){
		$inf .= '1';
	}else{
		$inf .= '0';
	}
	if(isset($_POST['ins_counter'])){
		$inf .= '1';
	}else{
		$inf .= '0';
	}
	$view = ViewLevelToInt($_POST['view']);
	$enabled = EnToInt($_POST['enabled']);
	$showinmenu = EnToInt($_POST['showinmenu']);
	if(!isset($_GET['id'])){
		$add_date = time();
		$modified = $add_date;
		$counter = 0;
		$order = AdminPagesNewOrder($parent_id);
		$values = Values('', $parent_id, $title, $text, $copy, $add_date, $modified, $counter, $auto_br, $inf, $link, $view, $enabled, $seo_title, $seo_keywords, $seo_description, 'page', $order, $showinmenu);
		$db->Insert('pages', $values);
	}else{
		$id = SafeEnv($_GET['id'], 11, int);
		$db->Select('pages', "`id`='".$id."'");
		$page = $db->FetchRow();
		$add_date = SafeDB($page['date'], 11, int);
		$modified = time(); // Изменился сейчас
		$counter = SafeDB($page['hits'], 11, int);
		$order = SafeDB($page['order'], 11, int);
		$values = Values('', $parent_id, $title, $text, $copy, $add_date, $modified, $counter, $auto_br, $inf, $link, $view, $enabled, $seo_title, $seo_keywords, $seo_description, 'page', $order, $showinmenu);
		$db->Update('pages', $values, "`id`='".$id."'", true);
	}
	AdminPagesClearCache();
	GO($config['admin_file'].'?exe=pages');
}

/**
 * Редактор ссылок
 * @return void
 */
function AdminPagesLinkEditor(){
	global $config, $db, $site;
	$link = '';
	$id = -1;
	$title = '';
	$parent_id = -1;
	if(isset($_GET['parent'])){
		$parent_id = SafeEnv($_GET['parent'], 11, int);
	}
	$view = array(1 => false, 2 => false, 3 => false, 4 => false);
	$enabled = array(false, false);
	$showinmenu = array(false, false);
	if(!isset($_GET['id'])){
		$view[4] = true;
		$enabled[1] = true;
		$showinmenu[1] = true;
		$form_title = 'Добавление ссылки';
		$submit = 'Добавить';
	}else{
		$id = SafeEnv($_GET['id'], 11, int);
		$db->Select('pages', "`id`='$id'");
		$pg = $db->FetchRow();
		$parent_id = SafeEnv($pg['parent'], 11, int);
		$title = SafeEnv($pg['title'], 255, str);
		$link = SafeEnv($pg['text'], 255, str);
		$view[SafeDB($pg['view'], 1, int)] = true;
		$enabled[SafeDB($pg['enabled'], 1, int)] = true;
		$showinmenu[SafeDB($pg['showinmenu'], 1, int)] = true;
		$form_title = 'Редактирование ссылки';
		$submit = 'Сохранить';
	}
	# Возможные родительские страницы
	$tree = new Tree('pages');
	$cats_data = array();
	$cats_data = $tree->GetCatsData($parent_id, false, true, $id, true);
	#Кто видит
	$visdata = array();
	$site->DataAdd($visdata, 'all', 'Все', $view['4']);
	$site->DataAdd($visdata, 'members', 'Только пользователи', $view['2']);
	$site->DataAdd($visdata, 'guests', 'Только гости', $view['3']);
	$site->DataAdd($visdata, 'admins', 'Только администраторы', $view['1']);
	#Модули
	$modules_data = array();
	$mods = $db->Select('modules', "`enabled`='1' and `isindex`='1'");
	$site->DataAdd($modules_data, '', '--- URL ---', false);
	foreach($mods as $mod){
		$site->DataAdd($modules_data, SafeDB($mod['folder'], 255, str), SafeDB($mod['name'], 255, str), false);
	}
	FormRow('Заголовок', $site->Edit('title', $title, false, 'style="width:400px;" maxlength="255"'));
	FormRow('Родительская страница', $site->Select('parent_id', $cats_data));
	FormRow('Ссылка на модуль', $site->Select('module', $modules_data));
	FormRow('Ссылка (URL)', $site->Edit('link', $link, false, 'style="width:400px;"'));
	FormRow('Кто видит', $site->Select('view', $visdata));
	FormRow('Показать в меню', $site->Radio('showinmenu', 'off', $showinmenu[0]).'Нет&nbsp;&nbsp;'.$site->Radio('showinmenu', 'on', $showinmenu[1]).'Да');
	FormRow('Включить', $site->Radio('enabled', 'off', $enabled[0]).'Нет&nbsp;&nbsp;'.$site->Radio('enabled', 'on', $enabled[1]).'Да');
	AddCenterBox($form_title);
	AddForm('<form action="'.$config['admin_file'].'?exe=pages&a=savelink'.($id != -1 ? '&id='.$id : '').'" method="post">', $site->Button('Отмена', 'onclick="history.go(-1)"').$site->Submit($submit));
}

/**
 * Сохранение изменений ссылки
 * @return void
 */
function AdminPagesLinkSave(){
	global $db, $config;
	$parent_id = SafeEnv($_POST['parent_id'], 11, int);
	$title = SafeEnv($_POST['title'], 255, str);
	if($_POST['module'] != ''){
		$url = 'mod://'.SafeEnv($_POST['module'], 255, str);
	}else{
		$url = SafeEnv($_POST['link'], 255, str);
	}
	$view = ViewLevelToInt($_POST['view']);
	$enabled = EnToInt($_POST['enabled']);
	$showinmenu = EnToInt($_POST['showinmenu']);
	if(isset($_GET['id'])){
		$id = SafeEnv($_GET['id'], 11, int);
		$db->Select('pages', "`id`='".$id."'");
		$page = $db->FetchRow();
		$order = SafeDB($page['order'], 11, int);
	}else{
		$order = AdminPagesNewOrder($parent_id);
	}
	$values = Values('', $parent_id, $title, $url, '', time(), time(), '0', '0', '', SafeEnv(Translit4Url($title), 255, str), $view, $enabled, '', '', '', 'link', $order, $showinmenu);
	if(isset($_GET['id'])){ // Редатирование
		$id = SafeEnv($_GET['id'], 11, int);
		$db->Update('pages', $values, "`id`='".$id."'", true);
	}else{
		$db->Insert('pages', $values);
	}
	AdminPagesClearCache();
	GO($config['admin_file'].'?exe=pages');
}

/**
 * Редактирование категории
 * @return void
 */
function AdminPagesCatEditor(){
	$link = '';
	$id = -1;
	$title = '';
	$parent_id = -1;
	if(isset($_GET['parent'])){
		$parent_id = SafeEnv($_GET['parent'], 11, int);
	}
	$view = array(1 => false, 2 => false, 3 => false, 4 => false);
	$enabled = array(false, false);
	$showinmenu = array(false, false);
	if(!isset($_GET['id'])){
		$view[4] = true;
		$enabled[1] = true;
		$showinmenu[1] = true;
		$form_title = 'Добавление категории';
		$submit = 'Добавить';
	}else{
		$id = SafeEnv($_GET['id'], 11, int);
		System::database()->Select('pages', "`id`='$id'");
		$pg = System::database()->FetchRow();
		$parent_id = SafeEnv($pg['parent'], 11, int);
		$title = SafeEnv($pg['title'], 255, str);
		$view[SafeDB($pg['view'], 1, int)] = true;
		$enabled[SafeDB($pg['enabled'], 1, int)] = true;
		$showinmenu[SafeDB($pg['showinmenu'], 1, int)] = true;
		$form_title = 'Редактирование категории';
		$submit = 'Сохранить';
	}
	# Возможные родительские страницы
	$tree = new Tree('pages');
	$cats_data = array();
	$cats_data = $tree->GetCatsData($parent_id, false, true, $id, true);
	#Кто видит
	$visdata = array();
	System::site()->DataAdd($visdata, 'all', 'Все', $view['4']);
	System::site()->DataAdd($visdata, 'members', 'Только пользователи', $view['2']);
	System::site()->DataAdd($visdata, 'guests', 'Только гости', $view['3']);
	System::site()->DataAdd($visdata, 'admins', 'Только администраторы', $view['1']);

	FormRow('Заголовок', System::site()->Edit('title', $title, false, 'style="width:400px;" maxlength="255"'));
	FormRow('Родительская страница', System::site()->Select('parent_id', $cats_data));
	FormRow('Кто видит', System::site()->Select('view', $visdata));
	FormRow('Показать в меню', System::site()->Radio('showinmenu', 'off', $showinmenu[0]).'Нет&nbsp;&nbsp;'.System::site()->Radio('showinmenu', 'on', $showinmenu[1]).'Да');
	FormRow('Включить', System::site()->Radio('enabled', 'off', $enabled[0]).'Нет&nbsp;&nbsp;'.System::site()->Radio('enabled', 'on', $enabled[1]).'Да');
	AddCenterBox($form_title);
	AddForm('<form action="'.ADMIN_FILE.'?exe=pages&a=savecat'.($id != -1 ? '&id='.$id : '').'" method="post">', System::site()->Button('Отмена', 'onclick="history.go(-1)"').System::site()->Submit($submit));
}

/**
 * Сохранение изменений категории
 * @return void
 */
function AdminPagesCatSave(){
	$parent_id = SafeEnv($_POST['parent_id'], 11, int);
	$title = SafeEnv($_POST['title'], 255, str);
	$view = ViewLevelToInt($_POST['view']);
	$enabled = EnToInt($_POST['enabled']);
	$showinmenu = EnToInt($_POST['showinmenu']);
	if(isset($_GET['id'])){
		$id = SafeEnv($_GET['id'], 11, int);
		System::database()->Select('pages', "`id`='".$id."'");
		$page = System::database()->FetchRow();
		$order = SafeDB($page['order'], 11, int);
	}else{
		$order = AdminPagesNewOrder($parent_id);
	}
	$values = Values('', $parent_id, $title, '', '', time(), time(), '0', '0', '', SafeEnv(Translit4Url($title), 255, str), $view, $enabled, '', '', '', 'cat', $order, $showinmenu);
	if(isset($_GET['id'])){ // Редатирование
		System::database()->Update('pages', $values, "`id`='".$id."'", true);
	}else{
		System::database()->Insert('pages', $values);
	}
	AdminPagesClearCache();
	GO(ADMIN_FILE.'?exe=pages');
}

/**
 * Изменение статуса страницы или ссылки
 * @return void
 */
function AdminPagesChangeStatus(){
	System::database()->Select('pages', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	$r = System::database()->FetchRow();
	if($r['enabled'] == 1){
		$en = '0';
	}else{
		$en = '1';
	}
	System::database()->Update('pages', "enabled='$en'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	AdminPagesClearCache();
	if(!isset($_GET['ajax'])){
		GO(ADMIN_FILE.'?exe=pages');
	}else{
		echo 'OK';
		exit;
	}
}

/**
 * Изменения статуса отображения страницы или ссылки в меню
 * @return void
 */
function AdminPagesChangeMenu(){
	global $config, $db;
	$db->Select('pages', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	$r = $db->FetchRow();
	if($r['showinmenu'] == 1){
		$en = '0';
	}else{
		$en = '1';
	}
	$db->Update('pages', "showinmenu='$en'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	AdminPagesClearCache();
	if(!isset($_GET['ajax'])){
		GO($config['admin_file'].'?exe=pages');
	}else{
		echo 'OK';
		exit;
	}
}

function _DeletePage($id){
	$sub_items = System::database()->Select('pages', "`parent`='$id'");
	foreach($sub_items as $item){
		_DeletePage(SafeEnv($item['id'], 11, int));
	}
	System::database()->Delete('pages', "`id`='$id'");
}

/**
 * Удаление страницы или ссылки
 * @return void
 */
function AdminPagesDelete(){
	if(!isset($_POST['id'])){
		exit("ERROR");
	}
	_DeletePage(SafeEnv($_POST['id'], 11, int));
	AdminPagesClearCache();
	exit("OK");
}

/**
 * Сброс счестчика просмотров станицы
 * @return void
 */
function AdminPagesResetCounter(){
	global $config, $db;
	$db->Update('pages', "hits='0'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	GO($config['admin_file'].'?exe=pages');
}

function AdminPagesBSort( $a, $b ){
	if($a['order'] == $b['order'])
		return 0;
	return ($a['order'] < $b['order']) ? -1 : 1;
}

/**
 * Перемещение страницы вверх или вниз
 * @return void
 */
function AdminPagesMove(){
	global $config, $db;
	$move = SafeEnv($_GET['to'], 4, str); // up, down
	$id = SafeEnv($_GET['id'], 11, int);
	$db->Select('pages', "`id`='$id'");
	if($db->NumRows() > 0){
		$page = $db->FetchRow();
		$pid = SafeDB($page['parent'], 11, int);
		$pages = $db->Select('pages', "`parent`='$pid'");
		usort($pages, 'AdminPagesBSort');
		$c = count($pages);
		//Исходный индекс
		$cur_pos = 0;
		for($i = 0; $i < $c; $i++){
			$pages[$i]['order'] = $i;
			if($pages[$i]['id'] == $id){
				$cur_pos = $i;
			}
		}
		//Индекс перемещения
		$rep_pos = $cur_pos;
		if($move == 'up'){
			$rep_pos = $cur_pos - 1;
		}elseif($move == 'down'){
			$rep_pos = $cur_pos + 1;
		}else{
			$rep_pos = $cur_pos;
		}
		if($rep_pos < 0 || $rep_pos >= $c){
			$rep_pos = $cur_pos;
		}
		$temp = intval($pages[$cur_pos]['order']);
		$pages[$cur_pos]['order'] = intval($pages[$rep_pos]['order']);
		$pages[$rep_pos]['order'] = intval($temp);
		for($i = 0; $i < $c; $i++){
			$order = $pages[$i]['order'];
			$id = $pages[$i]['id'];
			$db->Update('pages', "`order`='$order'", "`id`='$id'");
		}
	}
	AdminPagesClearCache();
	GO($config['admin_file'].'?exe=pages');
}

/**
 * Обработчик перемещения эементов в древовидном списке страниц
 * @return void
 */
function AdminPagesAjaxMove(){
	$itemId = SafeEnv($_POST['item_id'], 11, int);
	$parentId = SafeEnv($_POST['target_id'], 11, int);
	$position = SafeEnv($_POST['item_new_position'], 11, int);

	// Перемещаемый элемент
	System::database()->Select('pages',"`id`='$itemId'");
	if(System::database()->NumRows() == 0){
		// Error
		exit;
	}
	$item = System::database()->FetchRow();
	// Изменяем его родителя, если нужно
	if($item['parent'] != $parentId){
		System::database()->Update('pages', "`parent`='$parentId'", "`id`='$itemId'");
	}
	// Обноеление индексов элементов
	$indexes = array(); // соотвествие индексов и id элементов
	$items = System::database()->Select('pages',"`parent`='$parentId'");
	if($position == -1){
		$position = count($items);
	}
	SortArray($items, 'order');
	$i = 0;
	foreach($items as $p){
		if($p['id'] == $itemId){
			$indexes[$p['id']] = $position;
		}else{
			if($i == $position) $i++;
			$indexes[$p['id']] = $i;
			$i++;
		}
	}
	// Обновляем индексы
	foreach($indexes as $id=>$order){
		System::database()->Update('pages', "`order`='$order'", "`id`='$id'");
	}
	exit;
}

?>