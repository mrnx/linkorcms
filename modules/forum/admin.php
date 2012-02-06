<?php

# LinkorCMS
# � 2006-2008 �������� ��������� ���������� (linkorcms@yandex.ru)
# LinkorCMS Development Group
# www.linkorcms.ru
# �������� LinkorCMS 1.2.
# ���������� ������ - ������� �������� (smilesoft@yandex.ru)

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

if(!$user->CheckAccess2('forum', 'forum')){
	AddTextBox('������', $config['general']['admin_accd']);
	return;
}
global $admin_forum_url,$config;
$admin_forum_url = ADMIN_FILE.'?exe=forum';


include_once('forum_init.php');
include_once($forum_lib_dir.'forum_init_admin.php');
include_once($config['inc_dir'].'configuration/functions.php');

function AdminForum( $action ){
	global $config;
	TAddSubTitle('�����');

	if(!$config['forum']['basket'] and $config['forum']['del_auto_time']){
		Forum_Basket_RestoreBasketAll();
	}

	TAddToolLink('������ �������', 'main', 'forum');
	TAddToolLink('�������� �����', 'forum_editor', 'forum&a=forum_editor');
	TAddToolLink('���������', 'config', 'forum&a=config');
	TAddToolBox($action);

	if($config['forum']['basket']){
		System::admin()->SideBarAddMenuItem('��������� ����', 'forum&a=forum_basket_topics');
		System::admin()->SideBarAddMenuItem('��������� ���������', 'forum&a=forum_basket_posts');
		System::admin()->SideBarAddMenuBlock('�������');
	}

	switch($action) {
		case 'main':
			AdminForumMain();
			break;
		case 'changestatus':
			AdminForumChangeStatus();
			break;
		case 'cat_editor':
		case 'forum_editor':
			AdminForumCatEditor($action);
			break;
		case 'cat_save':
			AdminForumCatSave();
			break;
		case 'move':
			AdminForumMove();
			break;
		case 'delete':
			AdminForumDelete();
			break;

			// ��������� //////////////////////////////
		case 'config':
			AdminConfigurationEdit('forum', 'forum', false, false, '������������ ������');
			return true;
			break;
		case 'configsave':
			AdminConfigurationSave('forum&a=config', 'forum', false);
			return true;
			break;
			////////////////////////////////////////////

			// ������� ////////////////////////////////////
		case 'forum_basket_posts':
		case 'forum_basket_topics':
		case 'forum_basket':
			TAddSubTitle('����� > �������');
			switch($action) {
				////////////////// �������
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
			}
			else {
				global $site, $lang;
				AddTextBox($lang['error'] , $lang['error_file_exists'].' <B>'.MOD_DIR.'update.php'.'</B> ');

			}
			break;
		default:
			AdminForumMain();
	}
}

if(isset($_GET['a'])) {
	AdminForum($_GET['a']);
}else {
	AdminForum('main');
}



function AdminForumGetOrder( $parent_id ) {
	global $db;
	$db->Select('forums', "`parent_id`='$parent_id'");
	return $db->NumRows();
}

function AdminForumRender2( $forum, $all_forums, $forums_all_id = array() ) {
	global $config, $lang;
	$text = '';
	//���������� move menu
	if(isset($all_forums[$forum['parent_id']])) {
		$max_place = count($all_forums[$forum['parent_id']]) - 1;
	}else {
		$max_place = 0;
	}
	$move_menu = '';
	if($max_place == 0){
		$move_menu .= ' - ';
	}else{
		$order = SafeDB($forum['order'], 11, int);
		if($order >= 0 && $order < $max_place){ // ������ �������
			$move_menu .= SpeedButton('����', ADMIN_FILE.'?exe=forum&a=move&to=down&id='.SafeDB($forum['id'], 11, int).'&pid='.SafeDB($forum['parent_id'], 11, int), 'images/admin/down.png');
		}
		if($order <= $max_place && $order > 0){
			$move_menu .= SpeedButton('�����', ADMIN_FILE.'?exe=forum&a=move&to=up&id='.SafeDB($forum['id'], 11, int).'&pid='.SafeDB($forum['parent_id'], 11, int), 'images/admin/up.png');
		}
	}
	// ��� �����
	$vi = ViewLevelToStr(SafeDB($forum['view'], 1, int));
	// ������
	switch($forum['status']) {
		case '1':
			$st = '<a href="'.ADMIN_FILE.'?exe=forum&a=changestatus&id='.SafeDB($forum['id'], 11, int).'" title="���������"><font color="#008000">���.</font></a>';
			break;
		case '0':
			$st = '<a href="'.ADMIN_FILE.'?exe=forum&a=changestatus&id='.SafeDB($forum['id'], 11, int).'" title="��������"><font color="#FF0000">����.</font></a>';
			break;
	}
	$font_start = '';
	$font_end ='';

	// Func ����
	if($forum['parent_id'] == '0') {
		$editlink = ADMIN_FILE.'?exe=forum&a=cat_editor&id='.SafeDB($forum['id'], 11, int);
		$font_start = '<FONT SIZE="2" COLOR="#3300FF">';
		$font_end = '</FONT>';
	}else {
		$editlink = ADMIN_FILE.'?exe=forum&a=forum_editor&id='.SafeDB($forum['id'], 11, int);
	}
	$discussion = $forum['close_topic'] == 1?$lang['close_for_discussion_admin']:$lang['on_for_discussion'];
	if(isset($forums_all_id[$forum['parent_id']])) {
		if($forums_all_id[$forum['parent_id']]['close_topic'] == 1 and $forum['close_topic'] == 0)
		$discussion =  $lang['close_for_discussion_admin_parent'];
	}
	$func = '';
	$func .= SpeedButton('�������������', $editlink, 'images/admin/edit.png');
	$func .= SpeedButton('�������', ADMIN_FILE.'?exe=forum&a=delete&id='.SafeDB($forum['id'], 11, int).'&ok=0', 'images/admin/delete.png');

	$levs = '<table cellspacing="0" cellpadding="0" border="0" align="left"><tr>';
	$levs .= str_repeat('<td style="border:none;">&nbsp;-&nbsp;</td>', ($forum['parent_id'] == '0' ? 0 : 1));
	$levs .= '<td align="left" style="text-align:left;padding-left:10px;border:none;"><b><a href="'.$editlink.'">'.$font_start.SafeDB($forum['title'], 255, str).$font_end.'</a></b></td>';
	$levs .= '</tr></table>';
	$text .= '<tr><td>'.$move_menu.'</td><td>'.$levs.'</td><td>'.$vi.'</td><td>'.$st.'</td><td>'.$discussion.'</td><td>'.$func.'</td></tr>';
	return $text;
}


function AdminForumRender( $forum, $all_forums, $forums_all_id = array(), $is_sub_parent=false, $levs_sub=1){
	global $admin_forum_url,$config, $lang;
	$text = '';
	//���������� move menu
	if(isset($all_forums[$forum['parent_id']])){
		$max_place = count($all_forums[$forum['parent_id']]) - 1;
	}else{
		$max_place = 0;
	}
	$move_menu = '';
	if($max_place > 0){
		$order = SafeDB($forum['order'], 11, int);
		if($order > 0 && $order < $max_place){
			$move_menu .= SpeedButton('�����', ADMIN_FILE.'?exe=forum&a=move&to=up&id='.SafeDB($forum['id'], 11, int).'&pid='.SafeDB($forum['parent_id'], 11, int), 'images/admin/up.png');
			$move_menu .= SpeedButton('����', ADMIN_FILE.'?exe=forum&a=move&to=down&id='.SafeDB($forum['id'], 11, int).'&pid='.SafeDB($forum['parent_id'], 11, int), 'images/admin/down.png');
		}elseif($order == 0){
			$move_menu .= SpeedButton('����', ADMIN_FILE.'?exe=forum&a=move&to=down&id='.SafeDB($forum['id'], 11, int).'&pid='.SafeDB($forum['parent_id'], 11, int), 'images/admin/down.png');
		}elseif($order >= $max_place){
			$move_menu .= SpeedButton('�����', ADMIN_FILE.'?exe=forum&a=move&to=up&id='.SafeDB($forum['id'], 11, int).'&pid='.SafeDB($forum['parent_id'], 11, int), 'images/admin/up.png');
		}
	}else{
		$move_menu = '&nbsp;-&nbsp;';
	}
	// ��� �����
	$vi = ViewLevelToStr(SafeDB($forum['view'], 1, int));
	// ������
	switch($forum['status']){
		case '1':
			$st = '<a href="'.$admin_forum_url.'&a=changestatus&id='.SafeDB($forum['id'], 11, int).'" title="���������"><font color="#008000">���.</font></a>';
			break;
		case '0':
			$st = '<a href="'.$admin_forum_url.'&a=changestatus&id='.SafeDB($forum['id'], 11, int).'" title="��������"><font color="#FF0000">����.</font></a>';
			break;
	}
	$font_start = '';
	$font_end ='';

	// Func ����
	if($forum['parent_id'] == '0'){
		$editlink = $admin_forum_url.'&a=forum_editor&id='.SafeDB($forum['id'], 11, int);
		$font_start = '<FONT SIZE="2" COLOR="#3300FF">';
		$font_end = '</FONT>';
	} elseif ($is_sub_parent){
		$font_start = '<FONT SIZE="2" COLOR="#0080FF"><B>';
		$font_end = '</B></FONT>';
		$editlink = $admin_forum_url.'&a=forum_editor&id='.SafeDB($forum['id'], 11, int);
	} else {
		$editlink = $admin_forum_url.'&a=forum_editor&id='.SafeDB($forum['id'], 11, int);
	}

	$discussion = $forum['close_topic'] == 1?$lang['close_for_discussion_admin']:$lang['on_for_discussion'];

	if(isset($forums_all_id[$forum['parent_id']])){
		if($forums_all_id[$forum['parent_id']]['close_topic'] == 1 and $forum['close_topic'] == 0)
		$discussion =  $lang['close_for_discussion_admin_parent'];
	}

	$func = '';
	$func .= SpeedButton('�������������', $editlink, 'images/admin/edit.png');
	$func .= SpeedButton('�������', ADMIN_FILE.'?exe=forum&a=delete&id='.SafeDB($forum['id'], 11, int).'&ok=0', 'images/admin/delete.png');

	$levs = '<table cellspacing="0" cellpadding="0" border="0" align="left"><tr>';
	$levs .= str_repeat('<td style="border:none;">&nbsp;-&nbsp;</td>', ($forum['parent_id'] == '0' ? 0 : $levs_sub));
	$levs .= '<td align="left" style="text-align:left;padding-left:10px;border:none;"><b><a href="'.$editlink.'">'.$font_start.SafeDB($forum['title'], 255, str).$font_end.'</a></b></td>';
	$levs .= '</tr></table>';

	$text .= '<tr><td>'.$move_menu.'</td><td>'.$levs.'</td><td>'.$vi.'</td><td>'.$st.'</td><td>'.$discussion.'</td><td>'.$func.'</td></tr>';
	return $text;
}

function AdminSubForums($forums,$forums_all_id, $sub_forum, $id, $text='',$levels){
	foreach($sub_forum[$id] as $forum1){
		$text .= AdminForumRender($forum1, $forums, $forums_all_id, false, $levels);
		if(isset($sub_forum[$forum1['id']])) {
			foreach($sub_forum[$forum1['id']] as $forum2){
				$text .= AdminForumRender($forum2, $forums, $forums_all_id, false, $levels+3);
				if(isset($sub_forum[$forum2['id']])) {
					$text .= AdminSubForums($forums,$forums_all_id, $sub_forum, $forum2['id'],'', $levels+6);
				}
			}
		}
	}
	return $text;
}


function AdminForumMain(){
	global $admin_forum_url,$db, $config;
	/* @var $db Database_FilesDB */
	$result = $db->Select('forums');
	$forums = array();
	foreach($result as $forum){
		$forums_all_id[$forum['id']] = $forum;
		if($forum['parent_id']>0)
		$sub_forum[$forum['parent_id']][] = $forum;
	}
	foreach($result as $forum){
		$forums[$forum['parent_id']][] = $forum;
	}
	$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
	$text .= '<tr><th style="width:80px;">����������</th><th>�����</th><th>������</th><th>������</th><th>����������</th><th>�������</th></tr>';
	if(isset($forums['0'])){
		SortArray($forums['0'], 'order');
		foreach($forums['0'] as $category){
			// ������� ������ � ����������
			$text .= AdminForumRender($category, $forums);
			if(isset($forums[$category['id']])){
				SortArray($forums[$category['id']], 'order');
				foreach($forums[$category['id']] as $forum){
					// ������� �������� ������
					$text .= AdminForumRender($forum, $forums, $forums_all_id, isset($sub_forum[$forum['id']]));
					if(isset($forums[$forum['id']])){
						SortArray($forums[$forum['id']], 'order');
						$text .= AdminSubForums($forums,$forums_all_id, $forums, $forum['id'],'', 3);
					}
				}
			}
		}
	}
	$text .= '</table>';
	AddTextBox('���������� ��������', $text);
}



function AdminForumCatEditor( $action ) {
	global $admin_forum_url,$config, $db, $site,$forum_lib_dir;
	/* @var $db Database_FilesDB */
	$f_title = '';
	$f_desc = '';
	$f_parent = 0;
	$f_view = array(false, false, false, false, false);
	$f_status = array(false, false);

	$f_admin_theme_add = array(false, false);
	$f_new_message_email = array(false, true);
	$f_no_link_guest = array(false, false);
	$rang_access=0;
	$rang_add_theme=0;
	$rang_message=0;
	$close_topic= array(false, false);


	if(isset($_GET['id'])) {
		// ��������������
		$id = SafeDB($_GET['id'], 11, int);
		$db->Select('forums', "`id`='$id'");
		$forum = $db->FetchRow();
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
		$b_cap = '���������';
		if($action != 'forum_editor') {
			$c_cap = '�������������� ���������';
		}else {
			$c_cap = '�������������� ������';
		}
		unset($forum);
	}else {
		// ����������
		$f_title = '';
		$f_view[4] = true;
		$f_status[1] = true;

		$f_admin_theme_add[0] = true;
		$f_new_message_email[1] = true;
		$f_no_link_guest[0] = true;
		$close_topic[0] = true;

		$id_param = '';
		$b_cap = '��������';
		if($action != 'forum_editor') {
			$c_cap = '�������� ���������';
		}else {
			$c_cap = '�������� �����';
		}
	}
	FormRow('��������', $site->Edit('title', htmlspecialchars($f_title), false, 'style="width:250px;" maxlength="255"'));
	if($action == 'forum_editor'){
		$where = '';
		if(isset($_GET['id'])) {
			$where = "  `id`<>'".SafeDB($_GET['id'], 11, int)."'";
		}

		$forums = $db->Select('forums', $where );
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
		$tree->TopCatName = '��� ������������� �������';
		$data = array();
		if($cat){
			$data = $tree->GetCatsDataF($f_parent, true, true);
		} else {
			$site->DataAdd($data, 0, $tree->TopCatName, true);
		}
		FormRow('������������ ������', $site->Select('sub_id', $data));
		FormTextRow('��������', $site->HtmlEditor('desc', $f_desc, 500, 150));
	}

	if($action == 'forum_editor') {
		System::admin()->FormTitleRow('��������� ������');
		$endata = array();
		$site->DataAdd($endata, '1', '��', $f_admin_theme_add[1]);
		$site->DataAdd($endata, '0', '���', $f_admin_theme_add[0]);
		FormRow('������ ��������������<br />����� ��������� ����� ����', $site->Select('admin_theme_add', $endata));
		$endata = array();
		$site->DataAdd($endata, '1', '��', $f_no_link_guest[1]);
		$site->DataAdd($endata, '0', '���', $f_no_link_guest[0]);
		FormRow('�������� ������ �� ������', $site->Select('no_link_guest', $endata));
		$endata = array();
		$site->DataAdd($endata, '1', '��', $f_new_message_email[1]);
		$site->DataAdd($endata, '0', '���', $f_new_message_email[0]);
		FormRow('��������� ��������<br /><small>�������� �� ����������� � �����<BR> ���������� � ����</small>', $site->Select('new_message_email', $endata));
		$endata = array();
		$site->DataAdd($endata, '1', '��', $close_topic[1]);
		$site->DataAdd($endata, '0', '���', $close_topic[0]);
		FormRow('������� ��� ����������.<br /><small>����� �������� ������ ��������</small>', $site->Select('close_topic', $endata));

		//FormRow('<CENTER><B>����:</B></CENTER>',' &nbsp;');
		System::admin()->FormTitleRow('��������� ���� ������� �� ������ �������������');
		FormRow('������ �� ����� �������������:<BR>� ������� ������ ������<BR> ����� ������',ForumAdminGetUsersTypesComboBox('rang_access',$rang_access));
		FormRow('�������� ���:<BR>� ������� ������ ���� ���������<BR> ����� ���������',ForumAdminGetUsersTypesComboBox('rang_add_theme',$rang_add_theme));
		FormRow('�������� ��������� � ����:<BR>� ������� ������ ��������� <BR> ��������� ����� ���������',ForumAdminGetUsersTypesComboBox('rang_message',$rang_message));
	}



	System::admin()->FormTitleRow('��������� ���������');
	$visdata = array();
	$site->DataAdd($visdata, 'all', '���', $f_view[4]);
	$site->DataAdd($visdata, 'members', '������ ������������', $f_view[2]);
	$site->DataAdd($visdata, 'guests', '������ �����', $f_view[3]);
	$site->DataAdd($visdata, 'admins', '������ ��������������', $f_view[1]);
	FormRow('������', $site->Select('view', $visdata));

	$endata = array();
	$site->DataAdd($endata, '1', '��', $f_status[1]);
	$site->DataAdd($endata, '0', '���', $f_status[0]);
	FormRow('��������', $site->Select('status', $endata));
	AddCenterBox($c_cap);
	AddForm($site->FormOpen($admin_forum_url.'&a=cat_save'.$id_param), $site->Submit($b_cap));
}

function AdminForumCatSave() {
	global $admin_forum_url,$db, $config,$admin_forum_url;
	/* @var $db Database_FilesDB */
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

	if(isset($_POST['admin_theme_add']))
	$f_admin_theme_add= SafeEnv($_POST['admin_theme_add'], 1, int);
	if(isset($_POST['new_message_email']))
	$f_new_message_email = SafeEnv($_POST['new_message_email'], 1, int);
	if(isset($_POST['no_link_guest']))
	$f_no_link_guest= SafeEnv($_POST['no_link_guest'], 1, int);
	if(isset($_POST['rang_access']))
	$rang_access=SafeEnv($_POST['rang_access'], 11, int);
	if(isset($_POST['rang_message']))
	$rang_message=SafeEnv($_POST['rang_message'], 11, int);
	if(isset($_POST['rang_add_theme']))
	$rang_add_theme=SafeEnv($_POST['rang_add_theme'], 11, int);
	if(isset($_POST['close_topic']))
	$close_topic= SafeEnv($_POST['close_topic'], 1, int);
	if(isset($_POST['desc'])) {
		$f_desc = SafeEnv($_POST['desc'], 0, str);
	}else {
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
		// ��������������
		$id = SafeEnv($_GET['id'], 11, int);
		$set = "`parent_id`='$f_parent',`title`='$f_title',`description`='$f_desc',`view`='$f_view',`status`='$f_status',`admin_theme_add`='$f_admin_theme_add',`no_link_guest`='$f_no_link_guest',`new_message_email`='$f_new_message_email',`rang_access`='$rang_access',`rang_message`='$rang_message',`rang_add_theme`='$rang_add_theme',`close_topic`='$close_topic'";
		$db->Update('forums', $set, "`id`='$id'");
		if($f_parent == 0){
			$db->Update('forums',"`parent_id`='$id''", "`parent_id`='".$id."'");
		}
	}else {
		// ����������
		$order = AdminForumGetOrder('0');
		$values = "'','$f_parent','$f_title','$f_desc','0','0','0','0','','','0','$order','$f_status','$f_view','$f_admin_theme_add','$f_no_link_guest','$f_new_message_email', '$rang_access', '$rang_message', '$rang_add_theme','$close_topic'";
		$db->Insert('forums', $values);
	}
	Forum_Cache_ClearAllCacheForum();
	GO($admin_forum_url);
}


function AdminForumMove() {
	global $config, $db;
	$move = SafeEnv($_GET['to'], 4, str); // up, down
	$id = SafeEnv($_GET['id'], 11, int);
	$db->Select('forums', "`id`='$id'");
	if($db->NumRows() > 0) {
		$forum = $db->FetchRow();
		$pid = SafeDB($forum['parent_id'], 11, int);
		$forums = $db->Select('forums', "`parent_id`='$pid'");
		SortArray($forums, 'order');
		$c = count($forums);
		//�������� ������
		$cur_pos = 0;
		for($i = 0; $i < $c; $i++) {
			$forums[$i]['order'] = $i;
			if($forums[$i]['id'] == $id) {
				$cur_pos = $i;
			}
		}
		//������ �����������
		$rep_pos = $cur_pos;
		if($move == 'up') {
			$rep_pos = $cur_pos - 1;
		}elseif($move == 'down') {
			$rep_pos = $cur_pos + 1;
		}else {
			$rep_pos = $cur_pos;
		}
		if($rep_pos < 0 || $rep_pos >= $c) {
			$rep_pos = $cur_pos;
		}
		$temp = intval($forums[$cur_pos]['order']);
		$forums[$cur_pos]['order'] = intval($forums[$rep_pos]['order']);
		$forums[$rep_pos]['order'] = intval($temp);
		for($i = 0; $i < $c; $i++) {
			$order = $forums[$i]['order'];
			$id = $forums[$i]['id'];
			$db->Update('forums', "`order`='$order'", "`id`='$id'");
		}
	}
	Forum_Cache_ClearAllCacheForum();
	GO(ADMIN_FILE.'?exe=forum');
}

function AdminForumDelete() {
	global $config, $db;
	if(!isset($_GET['id'])) {
		GO(ADMIN_FILE.'?exe=forum');
	}
	if(isset($_GET['ok']) && $_GET['ok'] == '1') {
		ForumAdminDeleteForum(SafeEnv($_GET['id'], 11, int));
		Forum_Cache_ClearAllCacheForum();
		GO(ADMIN_FILE.'?exe=forum');
	}else {
		/* @var $db Database_FilesDB */
		$db->Select('forums', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
		$r = $db->FetchRow();
		if($r['parent_id'] == -1) {
			$f = '���������';
		}else {
			$f = '�����';
		}
		$text = '�� ������������� ������ ������� '.$f.' "'.SafeDB($r['title'], 255, str).'"? ��� �������� ������ � ���� ����� �������.<br />'.'<a href="'.ADMIN_FILE.'?exe=forum&a=delete&id='.SafeEnv($_GET['id'], 11, int).'&ok=1">��</a> &nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">���</a>';
		AddTextBox("��������!", $text);
	}
}

function AdminForumChangeStatus() {
	global $config, $db;
	$db->Select('forums', "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	$r = $db->FetchRow();
	if($r['status'] == 1) {
		$en = '0';
	}else {
		$en = '1';
	}
	$db->Update('forums', "status='$en'", "`id`='".SafeEnv($_GET['id'], 11, int)."'");
	Forum_Cache_ClearAllCacheForum();
	GO(ADMIN_FILE.'?exe=forum');
}

/******************�������*****************/
function AdminForumBasket( $table = 'forum_basket_post' ){
	global $db, $config, $site;

	if(isset($_GET['page'])) {
		$page = SafeEnv($_GET['page'],10,int);
	}else {
		$page = 1;
	}

	if($table == 'forum_basket_post'){
		$site->Title .= ' > ��������� ���������';
		$caption =  '��������� ���������';
	}else{
		$site->Title .= ' > ��������� ����';
		$caption =  '��������� ����';
	}

	$result = $db->Select($table);

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
		$table_caption = ' (���������)';
		if(count($result) > 0){
			$mposts = array();
			$where = '';
			foreach($result as $mpost){
				$where .= "`id`='".$mpost['obj_id']."' or ";
			}
			$where = substr($where, 0, strlen($where) - 3);
			$result_posts = $db->Select('forum_posts', $where);
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
		$table_caption = ' (�������� ����)';
		if(count($result) > 0){
			$where = '';
			foreach($result as $mpost){
				$where .= "`id`='".$mpost['obj_id']."' or ";
			}
			$where = substr($where, 0, strlen($where) - 3);
			$result_topics = $db->Select('forum_topics', $where);
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
	$text .= '<tr><th>��� ������</th><th>���� ��������</th><th>���� �������������� ��������</th><th>�����������</th><th>���������� ���������� <BR>'. $table_caption.'</th><th>�������</th></tr>';
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
		<td><a href="'.$restore_link.'">������������</a>
		&nbsp;<a href="index.php?name=forum&op='.$mop.'" target="_blank">��������</a></td>
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
