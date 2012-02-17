<?php

# LinkorCMS
# © 2006-2008 Галицкий Александр Николаевич (linkorcms@yandex.ru)
# LinkorCMS Development Group
# www.linkorcms.ru
# Лицензия LinkorCMS 1.2.
# Дополненая версия - Муратов Вячеслав (smilesoft@yandex.ru)

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(!$user->CheckAccess2('forum', 'forum')){
	AddTextBox('Ошибка', $config['general']['admin_accd']);
	return;
}
global $admin_forum_url, $config;
$admin_forum_url = ADMIN_FILE.'?exe=forum';


include_once('forum_init.php');
include_once($forum_lib_dir.'forum_init_admin.php');

if(isset($_GET['a'])) {
	$action = $_GET['a'];
}else {
	$action = 'main';
}

TAddSubTitle('Форум');

if(!$config['forum']['basket'] && $config['forum']['del_auto_time']){
	Forum_Basket_RestoreBasketAll();
}

TAddToolLink('Список форумов', 'main', 'forum');
TAddToolLink('Добавить категорию', 'forum_editor', 'forum&a=forum_editor');
TAddToolLink('Настройки', 'config', 'forum&a=config');
TAddToolBox($action);
if($config['forum']['basket']){
	System::admin()->SideBarAddMenuItem('Удаляемые темы', 'exe=forum&a=forum_basket_topics', 'forum_basket_topics');
	System::admin()->SideBarAddMenuItem('Удаляемые сообщения', 'exe=forum&a=forum_basket_posts', 'forum_basket_posts');
	System::admin()->SideBarAddMenuBlock('Корзина', $action);
}

switch($action) {
	case 'main':
	case 'ajaxtree':
		AdminForumMain();
		break;
	case 'changestatus':
		AdminForumChangeStatus();
		break;
	case 'forum_editor':
		AdminForumEditor();
		break;
	case 'forum_save':
		AdminForumSave();
		break;
	case 'delete':
		AdminForumDelete();
		break;
	case 'move':
		AdminForumMove();
		break;
	// Настройки //////////////////////////////
	case 'config':
		System::admin()->AddCenterBox('Конфигурация модуля "Форум"');
		if(CheckGet('saveok')){
			System::admin()->Highlight('Настройки сохранены.');
		}
		System::admin()->ConfigGroups('forum');
		System::admin()->AddConfigsForm(ADMIN_FILE.'?exe=forum&a=configsave');
		break;
	case 'configsave':
		System::admin()->SaveConfigs('forum');
		GO(ADMIN_FILE.'?exe=forum&a=config&saveok');
		break;
	////////////////////////////////////////////
	// Корзина ////////////////////////////////////
	case 'forum_basket_posts':
	case 'forum_basket_topics':
	case 'forum_basket':
		TAddSubTitle('Форум > Корзина');
		switch($action) {
			////////////////// Корзина
			case 'forum_basket_posts':
				AdminForumBasket();
				break;
			case 'forum_basket_topics':
				AdminForumBasket('forum_basket_topics');
				break;
			default:
				AdminForumBasket();
		}
		return true;
		break;
	case 'basket_restore':
		AdminForumBasketRestore();
		break;
	////////////////////////////////////////////
	case 'get_update':
		if (file_exists(MOD_DIR.'update.php')) {
			include_once(MOD_DIR.'update.php');
			Forum_Update(false);
			return true;
		}
	case 'update':
		if (file_exists(MOD_DIR.'update.php')) {
			include_once(MOD_DIR.'update.php');
			Forum_Update();
			return true;
		}else{
			AddTextBox($forum_lang['error'] , $forum_lang['error_file_exists'].' <b>'.MOD_DIR.'update.php'.'</b> ');
		}
		break;
	default:
		AdminForumMain();
}

function AdminForumGetOrder( $parent_id ){
	System::database()->Select('forums', "`parent_id`='$parent_id'");
	return System::database()->NumRows();
}

function AdminForumGetElement( &$forum, &$forums, $level = 1 ){
	global $forum_lang;
	$id = SafeDB($forum['id'], 11, int);
	$editlink = ADMIN_FILE.'?exe=forum&a=forum_editor&id='.$id;
	$func = '';
	$func .= System::admin()->SpeedButton('Добавить форум', ADMIN_FILE.'?exe=forum&a=forum_editor&parent='.$id, 'images/admin/folder_add.png');
	$func .= '&nbsp;';
	$func .= System::admin()->SpeedStatus('Выключить', 'Включить', ADMIN_FILE.'?exe=forum&a=changestatus&id='.$id, $forum['status'] == '1');
	$func .= '&nbsp;';
	$func .= System::admin()->SpeedButton('Редактировать', $editlink, 'images/admin/edit.png');
	$func .= System::admin()->SpeedConfirmJs(
		'Удалить',
		'$(\'#tree_container\').treeview(\'deleteNode\', '.$id.');',
		'images/admin/delete.png',
		'Все дочерние форумы, темы и сообщения так-же будут удалены. Уверены что хотите удалить?'
	);
	$view = ViewLevelToStr(SafeDB($forum['view'], 1, int));
	$description = SafeDB($forum['description'], 255, str);
	if(trim($description) == '') $description = 'Нет описания.';
	$topics = SafeDB($forum['topics'], 11, int);
	$posts = SafeDB($forum['posts'], 11, int);
	$discussion = $forum['close_topic'] == 1 ? $forum_lang['close_for_discussion_admin'] : $forum_lang['on_for_discussion'];
	$info = "$description<br>
		<b>Тем</b>: $topics<br>
		<b>Ответов</b>: $posts<br>
		<b>Видят</b>: $view<br>
		<b>Обсуждение</b>: $discussion
		";
	if($level == 1){
		$title = System::admin()->Link(SafeDB($forum['title'], 255, str), $editlink, '', true, 'style="color: #3300FF; font-weight: bold;"');
	}elseif($level == 2){
		$title = System::admin()->Link(SafeDB($forum['title'], 255, str), $editlink, '', true, 'style="color: #0080FF; font-weight: bold;"');
	}else{
		$title = '<b>'.System::admin()->Link(SafeDB($forum['title'], 255, str), $editlink).'</b>';
	}
	$element = array(
		'id'=>$id,
		'icon'=>'images/logo16.png',
		'title'=>$title,
		'info'=>$info,
		'func'=>$func,
		'isnode'=>isset($forums[$id]),
	);
	if($forum['parent_id']==0){
		$element['opened'] = true;
		$element['childs'] = array();
	}else{
		$element['child_url'] = 'admin.php?exe=forum&a=ajaxtree&parent='.$id;
	}
	return $element;
}

function AdminForumMain(){
	UseScript('jquery_ui_treeview');
	if(CheckGet('parent')){ // Запрос дочернего дерева
		$parent = SafeEnv($_GET['parent'], 11, int);
		$default_level = 0;
	}else{
		$parent = 0;
		$default_level = 1;
	}
	$forumsdb = System::database()->Select('forums');
	SortArray($forumsdb, 'order');
	$forums = array();
	foreach($forumsdb as $f){
		$forums[$f['parent_id']][] = $f;
	}
	$elements = array();
	if(isset($forums[$parent])){
		foreach($forums[$parent] as $forum){
			$element = AdminForumGetElement($forum, $forums, $default_level);
			if($parent == 0 && isset($forums[$forum['id']])){
				foreach($forums[$forum['id']] as $forum){
					$element['childs'][] = AdminForumGetElement($forum, $forums, 2);
				}
			}
			$elements[] = $element;
		}
	}
	if($parent == 0){
		$delete_url = ADMIN_FILE.'?exe=forum&a=delete';
		$move_url = ADMIN_FILE.'?exe=forum&a=move';
		AddTextBox('Управление форумами',
			'<div id="tree_container"></div><script>$("#tree_container").treeview({move: "'.$move_url.'", del: "'.$delete_url.'", tree: '.JsonEncode($elements).'});</script>');
	}else{
		echo JsonEncode($elements);
		exit;
	}
}

function AdminForumEditor() {
	global $site, $forum_lib_dir;

	$f_title = '';
	$f_desc = '';
	if(CheckGet('parent')){
		$f_parent = SafeDB($_GET['parent'], 11, int);
	}else{
		$f_parent = 0;
	}
	$f_view = array(false, false, false, false, false);
	$f_status = array(false, false);

	$f_admin_theme_add = array(false, false);
	$f_new_message_email = array(false, true);
	$f_no_link_guest = array(false, false);
	$rang_access = 0;
	$rang_add_theme = 0;
	$rang_message = 0;
	$close_topic = array(false, false);

	if(isset($_GET['id'])) {
		// Редактирование
		$id = SafeDB($_GET['id'], 11, int);
		System::database()->Select('forums', "`id`='$id'");
		$forum = System::database()->FetchRow();
		$f_title = SafeDB($forum['title'], 255, str);
		$f_desc = SafeDB($forum['description'], 0, str);
		$f_parent = SafeDB($forum['parent_id'], 11, int);

		$f_admin_theme_add[(int)$forum['admin_theme_add']] = true;
		$f_new_message_email[(int)$forum['new_message_email']] = true;
		$f_no_link_guest[(int)$forum['no_link_guest']] = true;
		$rang_access= SafeDB($forum['rang_access'], 11, int);
		$rang_add_theme= SafeDB($forum['rang_add_theme'], 11, int);
		$rang_message= SafeDB($forum['rang_message'], 11, int);
		$close_topic[(int)$forum['close_topic']] = true;

		$f_view[(int)$forum['view']] = true;
		$f_status[(int)$forum['status']] = true;
		$id_param = '&id='.$id;
		$b_cap = 'Сохранить';
		if($f_parent == 0) {
			$c_cap = 'Редактирование категории';
		}else {
			$c_cap = 'Редактирование Форума';
		}
	}else {
		// Добавление
		$f_title = '';
		$f_view[4] = true;
		$f_status[1] = true;

		$f_admin_theme_add[0] = true;
		$f_new_message_email[1] = true;
		$f_no_link_guest[0] = true;
		$close_topic[0] = true;

		$id_param = '';
		$b_cap = 'Добавить';
		if($f_parent == 0) {
			$c_cap = 'Добавить категорию';
		}else {
			$c_cap = 'Добавить форум';
		}
	}
	FormRow('Название', $site->Edit('title', htmlspecialchars($f_title), false, 'style="width:400px;" maxlength="255"'));
	if($f_parent != 0){
		$where = '';
		if(isset($_GET['id'])) {
			$where = "  `id`<>'".SafeDB($_GET['id'], 11, int)."'";
		}

		$forums = System::database()->Select('forums', $where );
		$cat = false;
		if(count($forums)==0) {
			$cat = false;
		}else{
			foreach($forums as $forum){
				if($forum['parent_id'] == 0)
				$cat = true;
			}
		}
		SortArray($forums, 'order');
		include_once($forum_lib_dir.'tree_f.class.php');
		$tree = new ForumTree($forums ,  'id',  'parent_id', 'title', 'topics', 'posts');
		$tree->moduleName = 'forum_topics';
		$tree->catTemplate = '';
		$tree->id_par_name = 'forum_id';
		$tree->NumItemsCaption = '';
		$tree->TopCatName = 'Нет родительского раздела';
		$data = array();
		if($cat){
			$data = $tree->GetCatsDataF($f_parent, true, true);
		} else {
			$site->DataAdd($data, 0, $tree->TopCatName, true);
		}
		FormRow('Родительский раздел', $site->Select('sub_id', $data));
		FormTextRow('Описание', $site->HtmlEditor('desc', $f_desc, 600, 200));
	}

	if($f_parent != 0) {
		System::admin()->FormTitleRow('Настройки форума');
		$endata = array();
		$site->DataAdd($endata, '1', 'Да', $f_admin_theme_add[1]);
		$site->DataAdd($endata, '0', 'Нет', $f_admin_theme_add[0]);
		FormRow('', 'Только администраторы могут создавать новые темы: '.$site->Select('admin_theme_add', $endata));
		$endata = array();
		$site->DataAdd($endata, '1', 'Да', $f_no_link_guest[1]);
		$site->DataAdd($endata, '0', 'Нет', $f_no_link_guest[0]);
		FormRow('', 'Скрывать ссылки от гостей: '.$site->Select('no_link_guest', $endata));
		$endata = array();
		$site->DataAdd($endata, '1', 'Да', $f_new_message_email[1]);
		$site->DataAdd($endata, '0', 'Нет', $f_new_message_email[0]);
		FormRow('', 'Разрешить подписку на уведомление о новых сообщениях в теме: '.$site->Select('new_message_email', $endata));
		$endata = array();
		$site->DataAdd($endata, '1', 'Да', $close_topic[1]);
		$site->DataAdd($endata, '0', 'Нет', $close_topic[0]);
		FormRow('', 'Закрыть для обсуждения (будет доступен только просмотр): '.$site->Select('close_topic', $endata));
		System::admin()->FormTitleRow('Настройка прав доступа по рангам пользователей');
		FormRow('Доступ по рангу пользователей (с меньшим рангом доступ будет закрыт)',ForumAdminGetUsersTypesComboBox('rang_access',$rang_access));
		FormRow('Создание тем (с меньшим рангом темы создавать будет запрещено)',ForumAdminGetUsersTypesComboBox('rang_add_theme',$rang_add_theme));
		FormRow('Создание сообщений в теме (с меньшим рангом сообщения создавать будет запрещено)',ForumAdminGetUsersTypesComboBox('rang_message',$rang_message));
	}

	System::admin()->FormTitleRow('Параметры видимости');
	$visdata = array();
	$site->DataAdd($visdata, 'all', 'Все', $f_view[4]);
	$site->DataAdd($visdata, 'members', 'Только пользователи', $f_view[2]);
	$site->DataAdd($visdata, 'guests', 'Только гости', $f_view[3]);
	$site->DataAdd($visdata, 'admins', 'Только администраторы', $f_view[1]);
	FormRow('Доступ', $site->Select('view', $visdata));

	$endata = array();
	$site->DataAdd($endata, '1', 'Да', $f_status[1]);
	$site->DataAdd($endata, '0', 'Нет', $f_status[0]);
	FormRow('Включить', $site->Select('status', $endata));
	AddCenterBox($c_cap);

	AddForm($site->FormOpen(ADMIN_FILE.'?exe=forum&a=forum_save'.$id_param), $site->Submit($b_cap));
}

function AdminForumSave(){
	$f_title = SafeDB($_POST['title'], 255, str);
	$f_view = ViewLevelToInt($_POST['view']);
	$f_status = SafeEnv($_POST['status'], 1, int);
	$f_admin_theme_add= 0;
	$f_new_message_email = 0;
	$f_no_link_guest= 0;
	$rang_access=0;
	$rang_message=0;
	$close_topic=0;
	$rang_add_theme=0;
	if(isset($_POST['admin_theme_add'])) $f_admin_theme_add= SafeEnv($_POST['admin_theme_add'], 1, int);
	if(isset($_POST['new_message_email'])) $f_new_message_email = SafeEnv($_POST['new_message_email'], 1, int);
	if(isset($_POST['no_link_guest'])) $f_no_link_guest= SafeEnv($_POST['no_link_guest'], 1, int);
	if(isset($_POST['rang_access'])) $rang_access=SafeEnv($_POST['rang_access'], 11, int);
	if(isset($_POST['rang_message'])) $rang_message=SafeEnv($_POST['rang_message'], 11, int);
	if(isset($_POST['rang_add_theme'])) $rang_add_theme=SafeEnv($_POST['rang_add_theme'], 11, int);
	if(isset($_POST['close_topic'])) $close_topic= SafeEnv($_POST['close_topic'], 1, int);
	if(isset($_POST['desc'])) {
		$f_desc = SafeEnv($_POST['desc'], 0, str);
	}else{
		$f_desc = '';
	}
	if(isset($_POST['parent_id'])) {
		$f_parent = SafeEnv($_POST['parent_id'], 11, int);
	}else {
		$f_parent = '0';
	}
	if(isset($_POST['sub_id'])){
		$f_parent2 = SafeEnv($_POST['sub_id'], 11, int);
		$f_parent =$f_parent2;
	}else{
		$f_parent2 = '0';
	}
	if(isset($_GET['id'])) {
		// Редактирование
		$id = SafeEnv($_GET['id'], 11, int);
		$set = "`parent_id`='$f_parent',`title`='$f_title',`description`='$f_desc',`view`='$f_view',`status`='$f_status',`admin_theme_add`='$f_admin_theme_add',`no_link_guest`='$f_no_link_guest',`new_message_email`='$f_new_message_email',`rang_access`='$rang_access',`rang_message`='$rang_message',`rang_add_theme`='$rang_add_theme',`close_topic`='$close_topic'";
		System::database()->Update('forums', $set, "`id`='$id'");
		if($f_parent == 0){
			System::database()->Update('forums',"`parent_id`='$id''", "`parent_id`='".$id."'");
		}
	}else {
		// Добавление
		$order = AdminForumGetOrder('0');
		$values = "'','$f_parent','$f_title','$f_desc','0','0','0','0','','','0','$order','$f_status','$f_view','$f_admin_theme_add','$f_no_link_guest','$f_new_message_email', '$rang_access', '$rang_message', '$rang_add_theme','$close_topic'";
		System::database()->Insert('forums', $values);
	}
	Forum_Cache_ClearAllCacheForum();
	GO(ADMIN_FILE.'?exe=forum');
}

function AdminForumChangeStatus(){
	System::database()->Select('forums', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	$r = System::database()->FetchRow();
	if($r['status'] == 1) {
		$en = '0';
	}else {
		$en = '1';
	}
	System::database()->Update('forums', "status='$en'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	Forum_Cache_ClearAllCacheForum();
	if(IsAjax()){
		exit("OK");
	}
	GO(ADMIN_FILE.'?exe=forum');
}

function AdminForumDelete(){
	if(!isset($_POST['id'])){
		exit("ERROR");
	}
	ForumAdminDeleteForum(SafeEnv($_POST['id'], 11, int));
	Forum_Cache_ClearAllCacheForum();
	exit("OK");
}

function AdminForumMove(){
	$itemId = SafeEnv($_POST['item_id'], 11, int);
	$parentId = SafeEnv($_POST['target_id'], 11, int);
	$position = SafeEnv($_POST['item_new_position'], 11, int);

	// Перемещаемый элемент
	System::database()->Select('forums',"`id`='$itemId'");
	if(System::database()->NumRows() == 0){
		// Error
		exit("ERROR");
	}
	$item = System::database()->FetchRow();
	// Изменяем его родителя, если нужно
	if($item['parent_id'] != $parentId){
		System::database()->Update('forums', "`parent_id`='$parentId'", "`id`='$itemId'");
	}
	// Обноеление индексов элементов
	$indexes = array(); // соотвествие индексов и id элементов
	$items = System::database()->Select('forums', "`parent_id`='$parentId'");
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
		System::database()->Update('forums', "`order`='$order'", "`id`='$id'");
	}
	Forum_Cache_ClearAllCacheForum();
	exit("OK");
}

/******************Корзина*****************/
function AdminForumBasket( $table = 'forum_basket_post' ){
	global $config, $site;

	if(isset($_GET['page'])) {
		$page = SafeEnv($_GET['page'],10,int);
	}else {
		$page = 1;
	}

	if($table == 'forum_basket_post'){
		$site->Title .= ' > Удаляемые сообщения';
		$caption =  'Удаляемые сообщения';
	}else{
		$site->Title .= ' > Удаляемые Темы';
		$caption =  'Удаляемые Темы';
	}

	$result = System::database()->Select($table);

	if(count($result)>20){
		$navigator = new Navigation($page);
		$navigator->GenNavigationMenu($result, 20, ADMIN_FILE.'?exe=forum&a='.$table);
		AddNavigation();
		$nav = true;
	}else{
		$nav = false;

	}

	$mop = 'showtopic&topic=';

	if($table == 'forum_basket_post'){
		$table_caption = ' (сообщение)';
		if(count($result) > 0){
			$mposts = array();
			$where = '';
			foreach($result as $mpost){
				$where .= "`id`='".$mpost['obj_id']."' or ";
			}
			$where = substr($where, 0, strlen($where) - 3);
			$result_posts = System::database()->Select('forum_posts', $where);
			if(count($result_posts)>0){
				foreach($result_posts as $mpost){
					$mposts[$mpost['id']] = $mpost['object'];
					$mpostsm[$mpost['id']] = $mpost['message'];
				}
				foreach($result as $mpost){
					$mpost['obj_id2'] = $mposts[$mpost['obj_id']];
					$mpost['obj_id'] = $mpost['obj_id'] ;
					$mpost['date'] = $mpost['date'] ;
					$mpost['user'] =  $mpost['user'] ;
					$mpost['reason'] = $mpost['reason'] ;
					$mpost['message'] = $mpostsm[$mpost['obj_id']];
					$result2[] = $mpost;
				}
				$result = $result2;
			}
		}
	}else{
		$table_caption = ' (название темы)';
		if(count($result) > 0){
			$where = '';
			foreach($result as $mpost){
				$where .= "`id`='".$mpost['obj_id']."' or ";
			}
			$where = substr($where, 0, strlen($where) - 3);
			$result_topics = System::database()->Select('forum_topics', $where);
			if(count($result_topics)>0) {
				foreach($result_topics as $mtopic){
					$mtopics[$mtopic['id']] = $mtopic['title'];
				}
				foreach($result as $mtopic){
					$mpost['obj_id'] = $mtopic['obj_id'];
					$mpost['date'] = $mtopic['date'];
					$mpost['user'] = $mtopic['user'];
					$mpost['reason'] = $mtopic['reason'];
					$mpost['message'] = $mtopics[$mtopic['obj_id']];
					$result2[] = $mpost;
				}
				$result =  $result2;
			}
		}
	}

	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th>Кто удалил</th><th>Дата удаления</th><th>Дата окончательного удаления</th><th>Комментарий</th><th>Содержимое удаляемого <BR>'. $table_caption.'</th><th>Функции</th></tr>';
	foreach($result as $basket){
		$mop = 'showtopic&topic='.($table == 'forum_basket_post' ? $basket['obj_id2'] : $basket['obj_id']);
		$restore_link = ADMIN_FILE.'?exe=forum&a=basket_restore&'.$table.'='.$basket['obj_id'];
		$ainfo = GetUserInfo($basket['user']);
		$text .= '<tr>
		<td>'.$ainfo['name'].'</td>
		<td>'.TimeRender($basket['date'], false, false).'</td>
		<td>'.TimeRender($basket['date']+(86400*$config['forum']['clear_basket_day']), false, false).'</td>
		<td>'.$basket['reason'].'</td>
		<td>'.(isset($basket['message']) ? $basket['message'] : '').'</td>
		<td><a href="'.$restore_link.'">Восстановить</a>
		&nbsp;<a href="index.php?name=forum&op='.$mop.'" target="_blank">Просмотр</a></td>
		</tr>';
	}
	$text .= '</table>';
	AddTextBox($caption , $text);
	if($nav){
		AddNavigation();
	}
}

function AdminForumBasketRestore(){
	ForumLoadFunction('restore_basket');
	if(isset($_GET['forum_basket_post'])){
		IndexForumRestoreBasketPost(SafeEnv($_GET['forum_basket_post'], 11, int));
	}elseif(isset($_GET['forum_basket_topics'])){
		IndexForumRestoreBasketTopic(SafeEnv($_GET['forum_basket_topics'], 11, int));
	}
}
