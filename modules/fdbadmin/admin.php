<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(!$user->isSuperUser()){
	AddTextBox('������', $config['general']['admin_accd']);
	return;
}

System::admin()->AddSubTitle('���������� ��');

$action = isset($_GET['a']) ? $_GET['a'] : 'main';
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
	case 'backups': include(MOD_DIR.'backups.inc.php');
		break;
	case 'backup_create': include(MOD_DIR.'backup_create.inc.php');
		break;
	case 'backup_delete': include(MOD_DIR.'backup_delete.inc.php');
		break;
	case 'backup_restore': include(MOD_DIR.'backup_restore.inc.php');
		break;
	case 'query': include(MOD_DIR.'db_query.inc.php');
		break;
	case 'performsql': include(MOD_DIR.'db_performsql.inc.php');
		break;
	case 'optimize': include(MOD_DIR.'db_optimize.inc.php');
		break;
}

if($action == 'main') System::admin()->SideBarAddTextBlock('', $top_text);

function AdminFdbAdminGenMenu(){
	global $action;
	System::admin()->SideBarAddMenuItem('������ ������', 'exe=fdbadmin', 'main');
	System::admin()->SideBarAddMenuItem('������� �������', 'exe=fdbadmin&a=createtable', 'createtable');
	System::admin()->SideBarAddMenuItem('��������� �����', 'exe=fdbadmin&a=backups', 'backups');
	if(System::database()->Name == 'MySQL'){
		System::admin()->SideBarAddMenuItem('��������� SQL', 'exe=fdbadmin&a=query', 'query');
		System::admin()->SideBarAddMenuItem('��������������', 'exe=fdbadmin&a=optimize', 'optimize');
	}
	System::admin()->SideBarAddMenuBlock('���� ������ "'.System::database()->SelectDbName.'"', $action);
}

function AdminFdbAdminGenTableMenu( $name ){
	global $action;
	System::admin()->SideBarAddMenuItem('���������', 'exe=fdbadmin&a=structure&name='.$name, 'structure');
	System::admin()->SideBarAddMenuItem('�����', 'exe=fdbadmin&a=review&name='.$name, 'review');
	System::admin()->SideBarAddMenuItem('�������� ������', 'exe=fdbadmin&a=insert&name='.$name, 'insert');
	System::admin()->SideBarAddMenuItem('�������������', 'exe=fdbadmin&a=edittable&name='.$name, 'edittable');
	System::admin()->SideBarAddMenuItem('������� �������', 'exe=fdbadmin&a=droptable&name='.$name, 'droptable');
	System::admin()->SideBarAddMenuBlock('������� "'.$_GET['name'].'"', $action);
}

function AdminFdbAdminInitCollForm( &$text, $saveparam ){
	global $config;
	$text .= '<form action="'.ADMIN_FILE.'?exe=fdbadmin&a='.$saveparam.'" method="post"><table cellspacing="0" cellspacing="0" class="cfgtable">'
	.'<tr><th>����</th><th>���</th><th>�����/��������</th><th>��������</th><th>����</th><th>�� ���������</th><th>���� ����������</th><th>���������</th><th>������</th><th>����������</th><th> - </th><th>������ �����</th></tr>';
}

function AdminFdbAdminCollForm( &$text, $cols, $name = '', $length = '', $SetType = '', $SetAttributes = '', $SetTableType = '' ){
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

function AdminFdbAdminCloseCollForm( &$text, $cols, $btnCaption ){
	global $site;
	$text .= $site->Hidden('cols',$cols)."<br><br>"
		.$site->Submit($btnCaption).'</form>';
}

function AdminFdbAdminAddTableForm( &$text, $tablename, $SetComment = '', $SetTableType = '' ){
	global $site;
	$tabletypes = array(array('default','�� ���������'),
			array('myisam','MyISAM'),
			array('heap','MyISAM'),
			array('merge','Merge'),
			array('berkeleydb','Berkeley DB'),
			array('isam','ISAM'),
	);
	for($i=0; $i<4; $i++){
		if($SetTableType == $tabletypes[$i][0]){
			$checked = true;
		}else{
			$checked = false;
		}
		$site->DataAdd($tabletype, $tabletypes[$i][0], $tabletypes[$i][1]);
	}
	$text .= '<font size="2">����������� � ������� '
		.$site->Edit('comment', $SetComment, false, 'style="width: 300px;"').'&nbsp;&nbsp;&nbsp;&nbsp;��� '
		.$site->Select('tabletype', $tabletype).'</font>'
		.$site->Hidden('tablename', $tablename);
}

// ������� �������� ��� �� �� ����� �����
// ����� �� �������� �� ������ ��������� � MySQL
function BackupCheckDbType( $Name ){
	$pos = strrpos($Name, '.');
	if($pos === false){
		return false;
	}
	$ext = substr($Name, $pos+1); // zip
	$pos2 = strrpos($Name, '.', -strlen($ext)-2);
	if($pos2 === false){
		return false;
	}
	$ext2 = substr($Name, $pos2+1, $pos-$pos2-1); // MySQL
	if($ext != 'zip' || $ext2 != System::database()->Name){
		return false;
	}
	return true;
}
