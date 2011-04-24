<?php

// LinkorCMS
// � 2006-2010 �������� ��������� ���������� (linkorcms@yandex.ru)
// LinkorCMS Development Group
// www.linkorcms.ru
// �������� LinkorCMS 1.3
// ��� ����� � ����������� ������ ������������ Posts.
// ������ 0.9

global $config, $site;

include_once($config['inc_dir'].'bbcode.inc.php');

/**
 * ������ ������������ Posts
 */
class Posts{

	/**
	 * ��� ������� � �������������.
	 * @var str
	 */
	public $PostsTable;

	/**
	 * ������ �����������.
	 * @var str
	 */
	public $PostTemplate = 'comment.html';

	/**
	 * ������ ����� ����������/�������������� �����������.
	 * @var str
	 */
	public $PostFormTemplate = 'comment_form.html';

	/**
	 * ����� �������� �������������� �����������.
	 * @var str
	 */
	public $EditPageUrl = '';

	/**
	 * ����� �������� �������� �����������.
	 * @var str
	 */
	public $DeletePageUrl = '';

	/**
	 * ����� ������� �������� ��� ������������ ���������.
	 * @var str
	 */
	public $NavigationUrl = '';

	/**
	 * ����� �������� ����������/���������� �����������.
	 * @var str
	 */
	public $PostFormAction = '';

	/**
	 * ����� � �������� �� ������� ��������� ����-������
	 * @var int
	 */
	public $FloodTime = 10;

	/**
	 * ������������ ���������� �������� � ���������
	 * @var int
	 */
	public $PostMaxLength = 512;

	/**
	 * ��������� �������������������� ������������� ��������� �����������.
	 * @var bool
	 */
	public $GuestPost = true;

	/**
	 * ��������� ������������� �������� �� �����������.
	 * @var bool
	 */
	public $Answers = true;

	/**
	 * ������������ ����������� ������������ ��� ������.
	 * ����� �������� �� ���� ����������.
	 * @var int
	 */
	public $MaxTreeLevel = 5;

	/**
	 * �������� ������������ ���������.
	 * @var bool
	 */
	public $EnNavigation = true;

	/**
	 * ���������� ��������� ������� ������ �� ��������.
	 * @var int
	 */
	public $MessagesOnPage = 10;

	/**
	 * �������� ����������, ����� ��������� �����.
	 * @var bool
	 */
	public $DecreaseSort = false;

	/**
	 * � ���� ������ ������������ ������ ��� ����������/���������� ������ �����������.
	 * @var array
	 */
	public $LastSaveErrors = array();

	/**
	 * ��������� ���������� ��� ����� �������
	 * @var bool
	 */
	public $AlloyComments = true;

	/**
	 * ��������� ��������� ��������� ��������� ������������
	 * @var bool
	 */
	public $DisableComments = false;

	/**
	 * ���������� ����� ������������������ �������������
	 * @var bool
	 */
	public $ShowKaptchaForMembers = false;

	private $PostsTree = array();

	static $LevelMargin = 40;


	function __construct( $PostsTable, $AlloyComments = true ){
		global $config, $site;
		$this->PostsTable = $PostsTable;
		$this->AlloyComments = $AlloyComments;

		if(isset($config['comments'])){
			$this->FloodTime = $config['comments']['floodtime'];
			$this->PostMaxLength = $config['comments']['maxlength'];
			$this->GuestPost = $config['comments']['guestpost'];
			$this->Answers = $config['comments']['answers'];
			$this->MaxTreeLevel = $config['comments']['maxtreelevel'];
			$this->EnNavigation = $config['comments']['ennav'];
			$this->MessagesOnPage = $config['comments']['onpage'];
			$this->DecreaseSort = $config['comments']['decreasesort'];
			$this->DisableComments = $config['comments']['disable_posts_engine'];
			$this->ShowKaptchaForMembers = $config['comments']['show_kaptcha_for_members'];
		}
		$site->SetVar('template', 'enabled_comments', !$this->DisableComments);
		$site->SetVar('template', 'disabled_comments', $this->DisableComments);
	}

	private function RenderPost($ObjectId, &$Posts, $BlockName, $Level)
	{
		global $user, $site;
		if(count($Posts) == 0){
			return false;
		}
		foreach($Posts as $post){
			$post_id = SafeDB($post['id'],11,int);
			$user_id = SafeDB($post['user_id'],11,int);

			$vars = array();

			$vars['level_padding'] = Posts::$LevelMargin * $Level;

			$vars['post_message'] = htmlspecialchars($post['post_message']);
			SmiliesReplace($vars['post_message']);
			$vars['post_message'] = nl2br($vars['post_message']);
			$vars['post_message'] = BbCodePrepare($vars['post_message']);

			if($user_id != 0){ // ������������������ ������������
				$userinfo = GetUserInfo($user_id);
				$vars['user_link'] = Ufu("index.php?name=user&op=userinfo&user=$user_id", 'user/{user}/info/');
				$vars['user_name'] = '<a href="'.$vars['user_link'].'">'.$userinfo['name'].'</a>';
				$vars['post_date'] = SafeDB($post['post_date'],11,int);
				if($userinfo['hideemail'] == '0'){
					$vars['user_email'] = AntispamEmail($userinfo['email']);
				}else{
					$vars['user_email'] = '';
				}
				$vars['user_homepage'] = SafeDB($userinfo['url'], 255, str);
				$vars['user_homepage_url'] = UrlRender(SafeDB($post['user_homepage'],255,str));
				if($userinfo['online']){
					$vars['user_status'] = '������ �� �����.';
				}else{
					$vars['user_status'] = '';
				}
				$vars['user_rank_image'] = $userinfo['rank_image'];
				$vars['user_rank_name'] = $userinfo['rank_name'];

				$vars['user_avatar'] = $userinfo['avatar_file'];
				$vars['user_avatar_small'] = $userinfo['avatar_file_small'];
				$vars['user_avatar_smallest'] = $userinfo['avatar_file_smallest'];
				$vars['user_regdate'] = TimeRender($userinfo['regdate'], false, false);
				$ruser = true;
			}else{
				$vars['user_name'] = SafeDB($post['user_name'],255,str);
				$vars['post_date'] = SafeDB($post['post_date'],11,int);
				if($post['user_email'] != '' && $post['user_hideemail'] != 0){
					$vars['user_email'] = AntispamEmail(SafeDB($post['user_email'],255,str));
				}else{
					$vars['user_email'] = '';
				}
				if($post['user_homepage'] != ''){
					$vars['user_homepage'] = SafeDB($post['user_homepage'],255,str);
					$vars['user_homepage_url'] = UrlRender(SafeDB($post['user_homepage'],255,str));
				}else{
					$vars['user_homepage'] = '';
					$vars['user_homepage_url'] = '';
				}
				$vars['user_status'] = '';
				$vars['user_rank_image'] = '';
				$vars['user_rank_name'] = '';
				$vars['user_avatar'] = GetPersonalAvatar(0);
				$vars['user_avatar_small'] = GetSmallUserAvatar(0, $vars['user_avatar']);
				$vars['user_avatar_smallest'] = GetSmallestUserAvatar(0, $vars['user_avatar']);
				$vars['user_regdate'] = '';
				$ruser = false;
			}
			$vars['user_id'] = SafeDB($post['user_id'], 11, int);
			$vars['post_id'] = $post_id;
			$vars['user_ip'] = SafeDB($post['user_ip'],19,str);
			$vars['object_id'] = $ObjectId;
			$vars['parent_id'] = SafeDB($post['post_parent_id'], 11, int);

			if($vars['user_homepage'] != ''){
				$vars['user_homepage'] = '<a href="'.$vars['user_homepage_url'].'" target="_blank">'.$vars['user_homepage'].'</a>';
			}else{
				$vars['user_homepage'] = '';
			}
			$vars['post_date'] = TimeRender($vars['post_date']);

			if($user->Auth){
				$vars['editing'] = ($user->Get('u_id') == $user_id || $user->isAdmin());
			}else{
				$vars['editing'] = ($user_id == '0' && $vars['user_ip'] == getip());
			}


			$vars['answers'] = $this->Answers != '0' || $user->isAdmin();
			if(!$user->Auth && !$this->GuestPost){
				$vars['answers'] = false;
			}
			if(!$this->AlloyComments){
				$vars['answers'] = false;
			}
			$vars['no_answers'] = !$vars['answers'];

			$vars['edit_url'] = $this->EditPageUrl.'&post_id='.$post_id;
			$vars['delete_url'] = $this->DeletePageUrl.'&post_id='.$post_id;

			$vars['parent_post_url'] = $_SERVER['REQUEST_URI'].'#post_'.SafeDB($post['post_parent_id'], 11, int);
			$vars['post_url'] = $_SERVER['REQUEST_URI'].'#post_'.$post_id;

			$site->AddSubBlock($BlockName, true, $vars, array(), $this->PostTemplate);
			if(isset($this->PostsTree[$post_id])){
				if($this->MaxTreeLevel > $Level){
					$newLevel = $Level + 1;
				}else{
					$newLevel = $Level;
				}
				$this->RenderPost($ObjectId, $this->PostsTree[$post_id], $BlockName, $newLevel);
			}
		}
		return true;
	}

	/**
	 * ����� ������������ � ������������ ����������.
	 *
	 * @param int $ObjectId "object_id" �����������
	 * @param str $PostsBlockName ��� ����� � �������, ���� �������� �����������
	 * @param bool $LastPage ���� ������, �� ������� ������� ��������� �������� ������������
	 * @param int $Page ����� �������� ������������
	 * @param str $ExWhere ���� Where ������. �����������, ���� ��� ��������� ������ ������ ������ ������������.
	 */
	public function RenderPosts( $ObjectId, $PostsBlockName = 'posts', $NavigationBlockName = 'navigation', $LastPage = false, &$Page = 0, $ExWhere = '' )
	{
		global $site, $db, $config, $user;

		if($this->DisableComments){
			$site->AddBlock($PostsBlockName, false, false, 'post');
			$site->AddBlock($NavigationBlockName, true, false);
			return;
		}else{
			$site->AddBlock($PostsBlockName, true, true, 'post');
		}

		if($this->EditPageUrl == ''){
			error_handler(USER_NOTICE, 'Posts::Posts(): �� ��������������� ����� �������� �������������� ������������ Posts::$EditPageUrl.', __FILE__);
		}
		if($this->DeletePageUrl == ''){
			error_handler(USER_NOTICE, 'Posts::Posts(): �� ��������������� ����� �������� �������� ������������ Posts::$DeletePageUrl.', __FILE__);
		}
		if($this->NavigationUrl == ''){
			error_handler(USER_NOTICE, 'Posts::Posts(): �� ��������������� ����� ������� �������� ��� ������������ ��������� Posts::$NavigationUrl.', __FILE__);
		}

		// �������� ��������� �� ���� ������
		if($ObjectId != 0){
			$where = "`object_id`='".$ObjectId."'";
		}elseif($ExWhere != ''){
			$where = $ExWhere;
		}else{
			$where = ''; // ��� �������
		}
		$posts = $db->Select($this->PostsTable, $where);

		// ����������
		SortArray($posts, 'post_date', $this->DecreaseSort);
		$this->PostsTree = array();
		foreach($posts as $post){
			$this->PostsTree[$post['post_parent_id']][] = $post;
		}

		if($Page == 0){ // �������� �� ���������
			if($this->DecreaseSort){
				$Page = 1;
			}else{
				$LastPage = true;
			}
		}

		// �������������� ���������
		$comm_nav_obj = new Navigation($Page, $NavigationBlockName);
		$comm_nav_obj->FrendlyUrl = $config['general']['ufu'];
		if(!isset($this->PostsTree[0])){
			$comm_nav_obj->DisableNavigation();
		}else{
			if(!$this->EnNavigation){
				$comm_nav_obj->DisableNavigation();
			}else{
				if($LastPage){
					$Page = ceil(count($this->PostsTree[0]) / $this->MessagesOnPage);
				}
				$comm_nav_obj->GenNavigationMenu($this->PostsTree[0], $this->MessagesOnPage, $this->NavigationUrl, $Page);
			}
			$this->RenderPost($ObjectId, $this->PostsTree[0], $PostsBlockName, 0);
		}
	}

	protected function Alert($Block, $Message)
	{
		global $site;
		$site->AddBlock($Block, true, false, 'alert', 'alert_message.html');
		$vars = array();
		$vars['message'] = $Message;
		$site->Blocks[$Block]['vars'] = $vars;
	}

	/**
	 * ������� ����� ���������� ��� �������������� �����������.
	 * @param bool $Edit ����� ��������������
	 * @param str $PostFormBlockName ��� ����� ��� ������ �����
	 */
	public function RenderForm( $Edit = false, $PostFormBlockName = 'postsform' )
	{
		global $site, $db, $config, $user;


		if($this->DisableComments){
			$site->AddBlock($PostFormBlockName, false, false, 'form', $this->PostFormTemplate);
			return;
		}else{
			$site->AddBlock($PostFormBlockName, true, false, 'form', $this->PostFormTemplate);
		}

		if($Edit && isset($_GET['post_id'])){
			$post_id = SafeEnv($_GET['post_id'], 11, int);
		}elseif($Edit && !isset($_GET['post_id'])){
			error_handler(USER_ERROR, 'Posts::PostForm(): post_id �� ����������������.', __FILE__);
			return;
		}
		if(!$Edit && !$this->AlloyComments){
			$this->Alert($PostFormBlockName, '���������� �������');
			return;
		}

		if(!$Edit && !$user->Auth && !$this->GuestPost){ // �����
			$this->Alert($PostFormBlockName, '����� �� ����� ��������� �����������, ������� ��� �����������������.');
			return;
		}

		$site->SetVar('template', 'lang_posts_username', '���');
		$site->SetVar('template', 'lang_posts_useremail', 'E-mail');
		$site->SetVar('template', 'lang_posts_hideemail', '������ E-mail');
		$site->SetVar('template', 'lang_posts_userhomepage', '����');

		$site->SetVar('template', 'lang_posts_posttitle', '���������');
		$site->SetVar('template', 'lang_posts_postmessage', '���������');

		$site->SetVar('template', 'lang_posts_cancel', '������');
		$site->SetVar('template', 'lang_posts_canceltitle', '��������� � ���� ��� ���������� ���������');

		$vars = array();
		if($Edit){
			$db->Select($this->PostsTable, "`id`='$post_id'");
			$post = $db->FetchRow();

			if($user->Auth){
				$access = ($user->Get('u_id') == $post['user_id'] || $user->isAdmin());
			}else{
				$access = ($post['user_id'] == '0' && $post['user_ip'] == getip());
			}
			if(!$access){
				$this->Alert($PostFormBlockName, '� ��� �� ���������� ����!');
				return;
			}

			$vars['form_title'] = '�������������� ���������';
			$vars['form_action'] = $this->PostFormAction."&amp;post_id={$post_id}";

			$vars['post_message'] = htmlspecialchars($post['post_message']);

			$vars['edit'] = true;

			$site->SetVar('template','lang_posts_submit', '���������');
			$site->SetVar('template','lang_posts_submittitle', '��������� ��������� � ���������');

			$vars['visibility'] = 'visible';

		}else{
			$vars['form_title'] = '�������� �����������';
			$vars['form_action'] = $this->PostFormAction;

			$vars['post_title'] = '';
			$vars['post_message'] = '';

			$vars['edit'] = false;

			$site->SetVar('template','lang_posts_submit', '��������');
			$site->SetVar('template','lang_posts_submittitle', '�������� ����� ���������');

			$vars['visibility'] = 'hidden';
		}

		$vars['add'] = !$vars['edit'];
		$vars['add_guest'] = ($user->AccessLevel() == 3 || $user->AccessLevel() == 4) && $vars['add'];

		$vars['show_kaptcha'] = $vars['add_guest'] || (!$user->isAdmin() && $this->ShowKaptchaForMembers);
		$vars['kaptcha_url'] = 'index.php?name=plugins&amp;p=antibot';
		$vars['kaptcha_width'] = '120';
		$vars['kaptcha_height'] = '40';

		$site->Blocks[$PostFormBlockName]['vars'] = $vars;

		// JavaScript
		include_once('scripts/bbcode_editor/index.php');

		// �������� ��� �����
		$smilies = $db->Select('smilies', "`enabled`='1'");
		if($db->NumRows() == 0){
			$site->AddBlock('smilies', true, false, 'smile', '','��������� ���� ���.');
		}else{
			$site->AddBlock('smilies', true, true, 'smile');
			foreach($smilies as $smile){
				$smile['file'] = $config['general']['smilies_dir'].$smile['file'];
				$smile['code'] = SafeDB($smile['code'], 255, str);
				$sub_codes = explode(',', $smile['code']);
				$smile['code'] = $sub_codes[0];
				$site->AddSubBlock('smilies', true, $smile);
			}
		}
	}

	public function CheckFlood()
	{
		global $db, $config;
		$db->Select($this->PostsTable, "`user_ip`='".getip()."' and `post_date`>'".(time() - $this->FloodTime)."'");
		if($db->NumRows() > 0){
			return true;
		}else{
			return false;
		}
	}

	// ������������ � ��������� ���������
	public function SavePost( $ObjectId, $Edit = false )
	{
		global $db, $config, $user;

		$errors = array();

		if($Edit){
			if(!isset($_GET['post_id'])){
				$errors[] = 'post_id �� ���������������� � GET.';
			}else{
				$post_id = SafeEnv($_GET['post_id'], 11,int);
				$db->Select($this->PostsTable, "`id`='$post_id'");
				$post = $db->FetchRow();
			}
		}else{
			if(!$this->AlloyComments){
				$errors[] = '���������� �������';
				return;
			}
			if($this->DisableComments){
				$errors[] = '������� ������������ ���������. �� �� ������� �������� �����������.';
			}
		}

		$post_message = '';
		$post_parent_id = 0;

		if($user->Auth){ // �������������� ������������, ��������� �����������

			if(!isset($_POST['post_message']) || !isset($_POST['parent_id'])){
				$errors[] = '������ �� ����������������.';
			}

			$user_id = $user->Get('u_id');
			$user_name = $user->Get('u_name');
			$user_email = $user->Get('u_email');
			$user_hideemail = $user->Get('u_hideemail');
			$user_homepage = $user->Get('u_homepage');

			if($Edit && !$user->isAdmin() && $post['user_id'] != $user->Get('u_id')){
				$errors[] = '� ��� ������������ ���� ��� �������������� ����� ���������.';
			}

		}else{ // �����, ��������� ��� ����������� �����������
			if($Edit && ($post['user_id'] != '0' || $post['user_ip'] != getip())){
				$errors[] = '� ��� ������������ ���� ��� �������������� ����� ���������.';
			}else{
				if($this->GuestPost || $Edit){ // ��������� �������������� ������?
					if(!$Edit){
						if((!isset($_POST['user_name'])
						|| !isset($_POST['user_email'])
						|| !isset($_POST['user_homepage'])
						|| !isset($_POST['post_message'])
						|| !isset($_POST['parent_id']))){
							$errors[] = '������ �� ����������������.';
						}else{
							$user_id = 0;

							$user_name = SafeEnv($_POST['user_name'], 255, str, true);
							CheckNikname($user_name, $er, true);
							$user->Def('u_name', $user_name);

							$user_email = SafeEnv($_POST['user_email'], 255, str, true);
							if($user_email != ''){
								if(!CheckEmail($user_email)){
									$errors[] = '������ E-mail �� ����������. �� ������ ���� ����: <b>domain@host.ru</b> .';
								}
							}
							$user->Def('u_email', $user_email);

							if(isset($_POST['hideemail'])){
								$user_hideemail = '1';
							}else{
								$user_hideemail = '0';
							}
							$user->Def('u_hideemail', $user_hideemail);

							$user_homepage = Url(SafeEnv($_POST['user_homepage'], 250, str, true));
							$user->Def('u_homepage', $user_homepage);
						}
					}else{
						if(!isset($_POST['post_message']) || !isset($_POST['parent_id'])){
							$errors[] = '������ �� ����������������.';
						}

						$user_id = SafeDB($post['user_id'], 11, int);
						$user_name = SafeDB($post['user_name'], 255, str);
						$user_email = SafeDB($post['user_email'], 255, str);
						$user_hideemail = SafeDB($post['user_hideemail'], 1, int);
						$user_homepage = SafeDB($post['user_homepage'], 255, str);
					}

				}else{
					$errors[] = '����� ��������� ���������, ��� ���������� ������������������.';
				}
			}
		}

		if($user_name == ''){
			$errors[] = '�� �� ����� ���.';
		}
		if($user_email == ''){
			$errors[] = '�� �� ������� ��� E-mail.';
		}

		$post_message = SafeEnv($_POST['post_message'], $this->PostMaxLength, str);
		if(strlen($post_message) == 0){
			$errors[] = '�� �� ����� ����� ���������.';
		}

		// ��������� �����
		if(!$user->Auth || (!$user->isAdmin() && $this->ShowKaptchaForMembers)){
			if(!$user->isDef('captcha_keystring') || $user->Get('captcha_keystring') != $_POST['keystr']){
				$errors[] = '�� �������� ��� ����� ���� � ��������.';
			}
		}

		if(!isset($_POST['parent_id'])){
			$errors[] = 'parent_id �� ���������������� � POST.';
		}else{
			if($this->Answers == '1' || $user->isAdmin()){
				$parent = $_POST['parent_id'];
				$parent = explode('_', $parent, 2);
				$post_parent_id = SafeEnv($parent[1],11,int);
			}else{
				$post_parent_id = '0';
			}
		}

		if($this->CheckFlood() && !$Edit){
			$errors[] = '����-������, ��������� �������.';
		}

		$this->LastSaveErrors = $errors;

		if(count($errors) == 0){
			if(!$Edit){
				$vals = Values('', $ObjectId, $user_id, $user_name, $user_homepage, $user_email, $user_hideemail, getip(), time(), $post_message, $post_parent_id);
				$cols = array('id', 'object_id', 'user_id', 'user_name', 'user_homepage', 'user_email', 'user_hideemail', 'user_ip', 'post_date', 'post_message', 'post_parent_id');
				$db->Insert($this->PostsTable, $vals, $cols);
			}else{
				$db->Update($this->PostsTable, "`post_message`='$post_message'", "`id`='$post_id'");
			}
			return true;
		}else{
			return false;
		}
	}

	public function DeletePost( $post_id = null, $first = true )
	{
		global $db, $site, $user;

		if($post_id == null){
			if(isset($_GET['post_id'])){
				$post_id = $_GET['post_id'];
			}
		}

		if($post_id != null){
			$db->Select($this->PostsTable, "`id`='$post_id'");
			$post = $db->FetchRow();
		}else{
			$text = 'post_id ����� �� ����������������.';
			$site->AddTextBox('������.', '<center>'.$text.'</center>');
			return 0;
		}

		if($first){
			if($user->Auth){
				$editing = ($user->Get('u_id') == $post['user_id'] || $user->isAdmin());
			}else{
				$editing = ($post['user_id'] == '0' && $post['user_ip'] == getip());
			}
			if(!$editing){
				$text = '� ��� ������������ ���� ��� �������� ����� ���������.';
				$site->AddTextBox('������.', '<center>'.$text.'</center>');
				return 0;
			}
		}

		if(!$first || isset($_GET['ok'])){
			$del_count = 1;
			$parent_posts = $db->Select($this->PostsTable, "`post_parent_id`='$post_id'");
			foreach($parent_posts as $post){
				$del_count += $this->DeletePost(SafeDB($post['id'], 11, int), false);
			}
			$db->Delete($this->PostsTable, "`id`='$post_id'");
			return $del_count;
		}else{
			$text = '<br />������� ���������?<br /><br />'
			. '<a href="'.$this->DeletePageUrl.'&amp;post_id='.$post_id.'&amp;ok=1">��</a> &nbsp;
			&nbsp;&nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">���</a><br /><br />';
			$site->AddTextBox('', '<center>'.$text.'</center>');
			return 0;
		}
	}

	public function PrintErrors()
	{
		$text = '���� ��������� �� ��������� �� ��������� ��������:<br /><ul>';
		foreach($this->LastSaveErrors as $error){
			$text .= '<li>'.$error;
		}
		$text .= '</ul><center><a href="javascript:history.back()">�����</a></center>';
		return $text;
	}
}

?>