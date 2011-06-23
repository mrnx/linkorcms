<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('Редактор меню администратора');

if(isset($_GET['a'])){
	$action = $_GET['a'];
}else{
	$action = 'main';
}

System::admin()->SideBarAddMenuItem('Меню администратора', 'exe=adminmenu', 'main');
System::admin()->SideBarAddMenuItem('Добавить элемент', 'exe=adminmenu&a=editor', 'editor');
System::admin()->SideBarAddMenuBlock('', $action);

switch($action){
	case 'main':
	case 'ajaxtree':
		AdminAdminMenuMain();
		break;
	case 'ajaxmove':
		AdminAdminMenuAjaxMove();
		break;
	case 'editor':
		AdminAdminMenuEditor();
		break;
	case 'save':
		AdminAdminMenuSave();
		break;
	case 'delete':
		AdminAdminMenuDelete();
		break;
	case 'changestatus':
		AdminAdminMenuChangeStatus();
		break;
}

function AdminAdminMenuMain(){
	UseScript('jquery_ui_treeview');

	if(isset($_GET['parent'])){
		$parent = SafeEnv($_GET['parent'], 11, int);
	}else{
		$parent = 0;
	}

	$itemsdb = System::database()->Select('adminmenu');
	SortArray($itemsdb, 'order');
	$items = array();
	foreach($itemsdb as $item){
		$items[$item['parent']][] = $item;
	}

	if(!isset($items[$parent])) return '';

	foreach($items[$parent] as $item){
		$id = SafeDB($item['id'], 11, int);
		$icon = SafeDB($item['icon'], 255, str);
		if($icon == ''){
			$icon = 'images/page.png';
		}
		$title = SafeDB($item['title'], 255, str);

		$editlink = ADMIN_FILE.'?exe=adminmenu&a=editor&id='.$id;

		$func = '';
		$func .= System::admin()->SpeedButton('Добавить дочернюю ссылку', ADMIN_FILE.'?exe=adminmenu&a=editor&parent='.$id, 'images/admin/link_add.png');
		$func .= '&nbsp;';
		$func .= System::admin()->SpeedStatus('Выключить', 'Включить', ADMIN_FILE.'?exe=adminmenu&a=changestatus&id='.$id, $item['enabled'] == '1', 'images/bullet_green.png', 'images/bullet_red.png');
		$func .= '&nbsp;';
		$func .= System::admin()->SpeedButton('Редактировать', $editlink, 'images/admin/edit.png');
		$func .= System::admin()->SpeedConfirmJs(
			'Удалить',
			'$(\'#tree_container\').treeview(\'deleteNode\', '.$id.');',
			'images/admin/delete.png',
			'Удалить элемент "'.$title.'"?'
		);

		$elements[] = array(
			'id'=>$id,
			'icon'=>$icon,
			'title'=>'<b><a href="'.$editlink.'" onclick="return Admin.CheckButton(2, event);" onmousedown="return Admin.LoadPage(\''.$editlink.'\', event);">'.$title.'</a></b>',
			'func'=>$func,
			'isnode'=>isset($items[$id]),
			'child_url'=>'admin.php?exe=adminmenu&a=ajaxtree&parent='.$id,
		);
	}

	if($parent == 0){
		AddTextBox('Меню администратора', '<div id="tree_container"></div><script>$("#tree_container").treeview({move: \''.ADMIN_FILE.'?exe=adminmenu&a=ajaxmove\', del: \''.ADMIN_FILE.'?exe=adminmenu&a=delete\', tree: '.JsonEncode($elements).'});</script>');
	}else{
		echo JsonEncode($elements);
		exit;
	}
}

function AdminAdminMenuAjaxMove(){
	$table = 'adminmenu';
	$itemId = SafeEnv($_POST['item_id'], 11, int);
	$parentId = SafeEnv($_POST['target_id'], 11, int);
	$position = SafeEnv($_POST['item_new_position'], 11, int);

	// Перемещаемый элемент
	System::database()->Select($table ,"`id`='$itemId'");
	if(System::database()->NumRows() == 0){
		// Error
		exit;
	}
	$item = System::database()->FetchRow();
	// Изменяем его родителя, если нужно
	if($item['parent'] != $parentId){
		System::database()->Update($table, "`parent`='$parentId'", "`id`='$itemId'");
	}
	// Обноеление индексов элементов
	$indexes = array(); // соотвествие индексов и id элементов
	$items = System::database()->Select($table, "`parent`='$parentId'");
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
		System::database()->Update($table, "`order`='$order'", "`id`='$id'");
	}
	exit;
}

function AdminAdminMenuEditor(){
	UseScript('jquery');
	System::admin()->AddJS(<<<JS
function SelectLinkType(type, first){
	jQuery('.aaml').hide();
	if(first){
	  jQuery('.aaml_'+type).show();
	}else{
		jQuery('.aaml_'+type).fadeIn();
	}
}
JS
);

	$id = -1;
	$parent = 0;
	if(isset($_GET['parent'])){
		$parent = SafeEnv($_GET['parent'], 11, int);
	}
	$module = '';
	$title = '';
	$icon = '';
	$admin_link = '';
	$external_link = '';
	$blank = false;
	$js = '';
	$type = 'admin'; //(admin|external|js|node|delimiter)
	$enabled = true;
	if(isset($_GET['id'])){
		$id = SafeEnv($_GET['id'], 11, int);
		System::database()->Select('adminmenu', "`id`='$id'");
		$item = System::database()->FetchRow();
		$parent = SafeDB($item['parent'], 11, int);
		$module = SafeDB($item['module'], 255, str);
		$title = SafeDB($item['title'], 255, str);
		$icon = SafeDB($item['icon'], 255, str);
		$admin_link = SafeDB($item['admin_link'], 255, str);
		$external_link = SafeDB($item['external_link'], 255, str);
		$blank = SafeDB($item['blank'], 1, bool);
		$js = SafeDB($item['js'], 0, str);
		$type = SafeDB($item['type'], 255, str);
		$enabled = SafeDB($item['enabled'], 1, bool);

		$form_title = 'Редактирование элемента';
		$button = 'Сохранить';
	}else{
		$form_title = 'Добавить элемент';
		$button = 'Добавить';
	}

	$items_db = System::database()->Select('adminmenu', "`type`<>'delimiter'");
	$items_tree = new Tree($items_db);
	$parent_data = $items_tree->GetCatsData($parent, false, true, $id, true);

	$modules_db = System::database()->Select('modules');
	$modules_data = array();
	System::admin()->DataAdd($modules_data, '', '', ''==$module);
	foreach($modules_db as $mod){
		System::admin()->DataAdd($modules_data, $mod['folder'], $mod['name'], $mod['folder']==$module);
	}

	System::admin()->AddOnLoadJS('SelectLinkType(\''.$type.'\', true);');

	$types_data = array();
	$types_av = array(
		'admin'=>'Внутренняя ссылка',
		'external'=>'Внешняя ссылка',
		'js'=>'JavaScript',
		'node'=>'Категория',
		'delimiter'=>'Разделитель'
	);
	foreach($types_av as $t=>$c){
		System::admin()->DataAdd($types_data, $t, $c, $t==$type);
	}

	System::admin()->FormRow('Родительский элемент', System::admin()->Select('parent', $parent_data));
	System::admin()->FormRow('Модуль', System::admin()->Select('module', $modules_data));
	System::admin()->FormRow('Заголовок', System::site()->Edit('title', $title, false, 'style="width:400px;" maxlength="255"'));
	System::admin()->FormRow('Иконка (16x16)', System::site()->Edit('icon', $icon, false, 'style="width:400px;" maxlength="255"'));
	System::admin()->FormRow('Тип', System::admin()->Select('type', $types_data, false, 'onchange="SelectLinkType(this.value);"'));
	System::admin()->FormRow('Внутренняя ссылка', System::site()->Edit('admin_link', $admin_link, false, 'style="width:400px;" maxlength="255"'), 'class="aaml aaml_admin"');
	System::admin()->FormRow('Внешняя ссылка', System::site()->Edit('external_link', $external_link, false, 'style="width:400px;" maxlength="255"'), 'class="aaml aaml_external"');
	System::admin()->FormRow('Открыть ссылку в новом окне/вкладке', System::admin()->Select('blank', GetEnData($blank, 'Да', 'Нет')), 'class="aaml aaml_external"');
	System::admin()->FormTextRow('JavaScript', System::site()->TextArea('js', $js, 'style="width:400px;height:100px;"'), 'class="aaml aaml_js"');
	System::admin()->FormRow('Включить', System::admin()->Select('enabled', GetEnData($enabled, 'Да', 'Нет')));
	System::admin()->AddCenterBox($form_title);
	System::admin()->AddForm(
		'<form action="'.ADMIN_FILE.'?exe=adminmenu&a=save'.($id != -1 ? '&id='.$id : '').'" method="post">',
		System::site()->Button('Отмена', 'onclick="history.go(-1)"').System::site()->Submit($button)
	);
}

function AdminAdminMenuSave(){
	$post = array('parent'=>SafeR('parent', 11,int))
	        +SafeR('module, title, icon, admin_link, external_link, js, type', 255, str)
	        +SafeR('blank, enabled', 3, onoff);

	if(isset($_GET['id'])){
		$id = SafeR('id', 11, int);
		System::database()->Update('adminmenu', MakeSet($post), "`id`='$id'");
	}else{
		System::database()->Select('adminmenu', "`parent`='{$post['parent']}'");
		$order = System::database()->NumRows();
		System::database()->Insert('adminmenu', MakeValues("'','parent','$order','module','title','icon','admin_link','external_link','blank','js','type','enabled'", $post));
	}
	GO(ADMIN_FILE.'?exe=adminmenu');
}

function _AdminAdminMenuDelete($id){
	$sub_items = System::database()->Select('adminmenu', "`parent`='$id'");
	foreach($sub_items as $item){
		_AdminAdminMenuDelete(SafeEnv($item['id'], 11, int));
	}
	System::database()->Delete('adminmenu', "`id`='$id'");
}

function AdminAdminMenuDelete(){
	if(!isset($_POST['id'])){
		exit('ERROR');
	}
	$id = SafeR('id', 11, int);
	_AdminAdminMenuDelete($id);
	exit('OK');
}

function AdminAdminMenuChangeStatus(){
	System::database()->Select('adminmenu', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	$r = System::database()->FetchRow();
	if($r['enabled'] == 1){
		$en = '0';
	}else{
		$en = '1';
	}
	System::database()->Update('adminmenu', "enabled='$en'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	echo 'OK';
	exit;
}

?>