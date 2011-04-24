<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('Управление БД');

if(!$user->isSuperUser()){
	AddTextBox('Ошибка', $config['general']['admin_accd']);
	return;
}

if(isset($_GET['a'])){
	$action = $_GET['a'];
}else{
	$action = 'main';
}

AdminFdbAdminGenMenu();

switch($action){
	case 'main': include(MOD_DIR.'table_list.inc.php');
		break;

	case 'createtable': include(MOD_DIR.'table_create.inc.php');
		break;

	case 'newtable':
	case 'edittable': include(MOD_DIR.'table_edit.inc.php');
		break;

	case 'savetable':
	case 'editsavetable': include(MOD_DIR.'table_save.inc.php');
		break;

	case 'droptable': include(MOD_DIR.'table_drop.inc.php');
		break;

	case 'renametable': include(MOD_DIR.'table_rename.inc.php');
		break;

	case 'structure': include(MOD_DIR.'table_structure.inc.php');
		break;

	case 'review': include(MOD_DIR.'table_review.inc.php');
		break;

	case 'insert':
	case 'editfield': include(MOD_DIR.'row_edit.inc.php');
		break;

	case 'insertsave':
	case 'editsave': include(MOD_DIR.'row_save.inc.php');
		break;

	case 'deleterow': include(MOD_DIR.'row_delete.inc.php');
		break;

	case 'newcoll': include(MOD_DIR.'coll_edit.inc.php');
		break;

	case 'addcoll': include(MOD_DIR.'coll_save.inc.php');
	break;

	case 'deletecoll': include(MOD_DIR.'coll_delete.inc.php');
		break;

	case 'viewcode': include(MOD_DIR.'code_row.inc.php');
		break;

	case 'viewcollinfo': include(MOD_DIR.'code_coll.inc.php');
		break;
}

function AdminFdbAdminGenMenu(){
	global $action;
	System::admin()->SideBarAddMenuItemAdmin('Список таблиц', 'exe=fdbadmin', $action == 'main');
	System::admin()->SideBarAddMenuItemAdmin('Создать таблицу', 'exe=fdbadmin&a=createtable', $action == 'createtable');
	System::admin()->SideBarAddMenuItemAdmin('Бекап', 'exe=fdbadmin&a=backup', $action == 'backup');
	System::admin()->SideBarAddMenuItemAdmin('Выполнить SQL', 'exe=fdbadmin&a=query', $action == 'query');
	System::admin()->SideBarAddMenuBlock('База данных');
}

function AdminFdbAdminGenTableMenu( $name ){
	global $action;
	System::admin()->SideBarAddMenuItemAdmin('Структура', 'exe=fdbadmin&a=structure&name='.$name, $action == 'structure');
	System::admin()->SideBarAddMenuItemAdmin('Обзор', 'exe=fdbadmin&a=review&name='.$name, $action == 'review');
	System::admin()->SideBarAddMenuItemAdmin('Добавить запись', 'exe=fdbadmin&a=insert&name='.$name, $action == 'insert');
	System::admin()->SideBarAddMenuItemAdmin('Редактировать', 'exe=fdbadmin&a=edittable&name='.$name, $action == 'edittable');
	System::admin()->SideBarAddMenuItemAdmin('Удалить таблицу', 'exe=fdbadmin&a=droptable&name='.$name, $action == 'droptable');
	System::admin()->SideBarAddMenuBlock('Таблица');
}



function AdminFdbAdminInitCollForm( &$text, $saveparam )
{
	global $config;
	$text .= '<form action="'.$config['admin_file'].'?exe=fdbadmin&a='.$saveparam.'" method="post"><table cellspacing="0" cellspacing="0" class="cfgtable">'
	.'<tr><th>Поле</th><th>Тип</th><th>Длина/значения</th><th>Атрибуты</th><th>Ноль</th><th>По умолчанию</th><th>Авто приращение</th><th>Первичный</th><th>Индекс</th><th>Уникальное</th><th> - </th><th>Полный текст</th></tr>';
}

function AdminFdbAdminCollForm( &$text, $cols, $name = '', $length = '', $SetType = '', $SetAttributes = '', $SetTableType = '' )
{
	global $site;

	$type = array('varchar', 'tinyint', 'text', 'date', 'smallint', 'mediumint', 'int', 'bigint', 'float', 'double', 'decimal', 'datetime', 'time',
	'year', 'char', 'tinyblob', 'tinytext', 'blob', 'mediumblob', 'mediumtext', 'longblob', 'longtext', 'enum', 'set');

	for($i = 0; $i < 24; $i++){
		if($SetType == $type[$i]){
			$checked = true;
		}else{
			$checked = false;
		}
		$site->DataAdd($types, $type[$i], $type[$i], $checked);
	}

	$Attributes = array(array('none', ''),
		array('binary', 'binary'),
		array('unsigned', 'unsigned'),
		array('unsigned_zerofill', 'unsigned zerofill')
	);

	for($i = 0; $i < 4; $i++){
		if($SetAttributes == $Attributes[$i][0]){
			$checked = true;
		}else{
			$checked = false;
		}
		$site->DataAdd($atr, $Attributes[$i][0], $Attributes[$i][1]);
	}

	for($i = 0; $i < $cols; $i++){
		$text .= '<tr><td>'
		.$site->Edit('name'.$i,'',false,'style="width:80px;"').'</td><td>'
		.$site->Select('type'.$i,$types).'</td><td>'
		.$site->Edit('length'.$i,'',false,'style="width:50px"').'</td><td>'
		.$site->Select('atributes'.$i,$atr).'</td><td>'
		.$site->Check('null'.$i,'null').'</td><td>'
		.$site->Edit('default'.$i,'',false,'style="width:80px;"').'</td><td>'
		.$site->Check('auto_increment'.$i,'val').'</td><td>'
		.$site->Radio('params'.$i,'primary').'</td><td>'
		.$site->Radio('params'.$i,'index').'</td><td>'
		.$site->Radio('params'.$i,'unique').'</td><td>'
		.$site->Radio('params'.$i,'noparams',true).'</td><td>'
		.$site->Check('fulltext'.$i,'1').'</td></tr>';
	}
	$text .= '</table>';
}

function AdminFdbAdminCloseCollForm( &$text, $cols, $btnCaption )
{
	global $site;
	$text .= $site->Hidden('cols',$cols)."<br><br>"
		.$site->Submit($btnCaption).'</form>';
}

function AdminFdbAdminAddTableForm( &$text, $tablename, $SetComment = '', $SetTableType = '' )
{
	global $site;
	$tabletypes = array(array('default','По умолчанию'),
				array('myisam','MyISAM'),
				array('heap','MyISAM'),
				array('merge','Merge'),
				array('berkeleydb','Berkeley DB'),
				array('isam','ISAM'),
		);

	for($i = 0; $i < 4; $i++){
		if($SetTableType == $tabletypes[$i][0]){
			$checked = true;
		}else{
			$checked = false;
		}
		$site->DataAdd($tabletype, $tabletypes[$i][0], $tabletypes[$i][1]);
	}
	$text .= '<font size="2">Комментарий к таблице '
		.$site->Edit('comment',$SetComment,false,'style="width: 300px;"').'&nbsp;&nbsp;&nbsp;&nbsp;Тип '
		.$site->Select('tabletype',$tabletype).'</font>'
		.$site->Hidden('tablename',$tablename);
}

?>