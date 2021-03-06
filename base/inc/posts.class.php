<?php

// LinkorCMS
// � 2006-2010 �������� ��������� ���������� (linkorcms@yandex.ru)
// LinkorCMS Development Group
// www.linkorcms.ru
// �������� LinkorCMS 1.3
// ��� ����� � ����������� ������ ������������ Posts.
// ������ 0.9

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
	 * ����� ������� �������� ��� ������������ ���������.
	 * @var str
	 */
	public $NavigationUrl = '';

	/**
	 * ������ ��� ������ ��������� �� ���������. �������� #comments.
	 * @var string
	 */
	public $NavigationAnchor = '';

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
		$this->PostsTable = $PostsTable;
		$this->AlloyComments = $AlloyComments;

		if(System::config('comments')){
			$config = System::config('comments');
			$this->FloodTime = $config['floodtime'];
			$this->PostMaxLength = $config['maxlength'];
			$this->GuestPost = $config['guestpost'];
			$this->Answers = $config['answers'];
			$this->MaxTreeLevel = $config['maxtreelevel'];
			$this->EnNavigation = $config['ennav'];
			$this->MessagesOnPage = $config['onpage'];
			$this->DecreaseSort = $config['decreasesort'];
			$this->DisableComments = $config['disable_posts_engine'];
			$this->ShowKaptchaForMembers = $config['show_kaptcha_for_members'];
		}
		System::site()->SetVar('template', 'enabled_comments', !$this->DisableComments);
		System::site()->SetVar('template', 'disabled_comments', $this->DisableComments);
	}

	private function RenderPost($ObjectId, &$Posts, $BlockName, $Level){
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

			if(System::user()->Auth){
				$vars['editing'] = (System::user()->Get('u_id') == $user_id || System::user()->isAdmin());
			}else{
				$vars['editing'] = ($user_id == '0' && $vars['user_ip'] == getip());
			}


			$vars['answers'] = $this->Answers != '0' || System::user()->isAdmin();
			if(!System::user()->Auth && !$this->GuestPost){
				$vars['answers'] = false;
			}
			if(!$this->AlloyComments){
				$vars['answers'] = false;
			}
			$vars['no_answers'] = !$vars['answers'];

			$back = SaveRefererUrl();
			$vars['edit_url'] = $this->EditPageUrl.'&post_id='.$post_id.'&back='.$back;
			$vars['delete_url'] = $this->DeletePageUrl.'&post_id='.$post_id.'&back='.$back;

			$vars['parent_post_url'] = $_SERVER['REQUEST_URI'].'#post_'.SafeDB($post['post_parent_id'], 11, int);
			$vars['post_url'] = $_SERVER['REQUEST_URI'].'#post_'.$post_id;

			System::site()->AddSubBlock($BlockName, true, $vars, array(), $this->PostTemplate);
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
	public function RenderPosts( $ObjectId, $PostsBlockName = 'posts', $NavigationBlockName = 'navigation', $LastPage = false, &$Page = 0, $ExWhere = '' ){
		if($this->DisableComments){
			System::site()->AddBlock($PostsBlockName, false, false, 'post');
			System::site()->AddBlock($NavigationBlockName, true, false);
			return;
		}else{
			System::site()->AddBlock($PostsBlockName, true, true, 'post');
		}

		if($this->EditPageUrl == ''){
			ErrorHandler(USER_NOTICE, 'Posts::Posts(): �� ��������������� ����� �������� �������������� ������������ Posts::$EditPageUrl.', __FILE__);
		}
		if($this->DeletePageUrl == ''){
			ErrorHandler(USER_NOTICE, 'Posts::Posts(): �� ��������������� ����� �������� �������� ������������ Posts::$DeletePageUrl.', __FILE__);
		}
		if($this->NavigationUrl == ''){
			ErrorHandler(USER_NOTICE, 'Posts::Posts(): �� ��������������� ����� ������� �������� ��� ������������ ��������� Posts::$NavigationUrl.', __FILE__);
		}

		// �������� ��������� �� ���� ������
		if($ObjectId != 0){
			$where = "`object_id`='".$ObjectId."'";
		}elseif($ExWhere != ''){
			$where = $ExWhere;
		}else{
			$where = ''; // ��� �������
		}
		$posts = System::database()->Select($this->PostsTable, $where);

		// ����������
		SortArray($posts, 'post_date', !$this->DecreaseSort);
		$this->PostsTree = array();
		foreach($posts as $post){
			$this->PostsTree[$post['post_parent_id']][] = $post;
		}

		if($Page == 0){ // �������� �� ���������
			if(!$this->DecreaseSort){
				$Page = 1;
			}else{
				$LastPage = true;
			}
		}

		// �������������� ���������
		$nav = new Navigation($Page, $NavigationBlockName);
		$nav->FrendlyUrl = System::config('general/ufu');
		$nav->Anchor = $this->NavigationAnchor;
		if(!isset($this->PostsTree[0])){
			$nav->DisableNavigation();
		}else{
			if(!$this->EnNavigation){
				$nav->DisableNavigation();
			}else{
				if($LastPage){
					$Page = ceil(count($this->PostsTree[0]) / $this->MessagesOnPage);
				}
				$nav->GenNavigationMenu($this->PostsTree[0], $this->MessagesOnPage, $this->NavigationUrl, $Page);
			}
			$this->RenderPost($ObjectId, $this->PostsTree[0], $PostsBlockName, 0);
		}
	}

	protected function Alert($Block, $Message){
		System::site()->AddBlock($Block, true, false, 'alert', 'alert_message.html');
		$vars = array();
		$vars['message'] = $Message;
		System::site()->Blocks[$Block]['vars'] = $vars;
	}

	/**
	 * ������� ����� ���������� ��� �������������� �����������.
	 * @param bool $Edit ����� ��������������
	 * @param str $PostFormBlockName ��� ����� ��� ������ �����
	 */
	public function RenderForm( $Edit = false, $PostFormBlockName = 'postsform' ){
		if($this->DisableComments){
			System::site()->AddBlock($PostFormBlockName, false, false, 'form', $this->PostFormTemplate);
			return;
		}else{
			System::site()->AddBlock($PostFormBlockName, true, false, 'form', $this->PostFormTemplate);
		}
		if($Edit && isset($_GET['post_id'])){
			$post_id = SafeEnv($_GET['post_id'], 11, int);
		}elseif($Edit && !isset($_GET['post_id'])){
			ErrorHandler(USER_ERROR, 'Posts::PostForm(): post_id �� ����������������.', __FILE__);
			return;
		}
		if(!$Edit && !$this->AlloyComments){
			$this->Alert($PostFormBlockName, '���������� �������');
			return;
		}
		if(!$Edit && !System::user()->Auth && !$this->GuestPost){ // �����
			$this->Alert($PostFormBlockName, '����� �� ����� ��������� �����������, ������� ��� �����������������.');
			return;
		}

		System::site()->SetVar('template', 'lang_posts_username', '���');
		System::site()->SetVar('template', 'lang_posts_useremail', 'E-mail');
		System::site()->SetVar('template', 'lang_posts_hideemail', '������ E-mail');
		System::site()->SetVar('template', 'lang_posts_userhomepage', '����');
		System::site()->SetVar('template', 'lang_posts_posttitle', '���������');
		System::site()->SetVar('template', 'lang_posts_postmessage', '���������');
		System::site()->SetVar('template', 'lang_posts_cancel', '������');
		System::site()->SetVar('template', 'lang_posts_canceltitle', '��������� � ���� ��� ���������� ���������');

		$back = '';
		if(!$Edit){
			$back = '&back='.SaveRefererUrl();
		}
		$vars = array();
		if($Edit){
			System::database()->Select($this->PostsTable, "`id`='$post_id'");
			$post = System::database()->FetchRow();

			if(System::user()->Auth){
				$access = (System::user()->Get('u_id') == $post['user_id'] || System::user()->isAdmin());
			}else{
				$access = ($post['user_id'] == '0' && $post['user_ip'] == getip());
			}
			if(!$access){
				$this->Alert($PostFormBlockName, '� ��� �� ���������� ����!');
				return;
			}
			$vars['form_title'] = '�������������� ���������';
			$vars['form_action'] = $this->PostFormAction."&post_id={$post_id}".$back;
			$vars['post_message'] = htmlspecialchars($post['post_message']);
			$vars['edit'] = true;
			System::site()->SetVar('template','lang_posts_submit', '���������');
			System::site()->SetVar('template','lang_posts_submittitle', '��������� ��������� � ���������');
			$vars['visibility'] = 'visible';
		}else{
			$vars['form_title'] = '�������� �����������';
			$vars['form_action'] = $this->PostFormAction.$back;
			$vars['post_title'] = '';
			$vars['post_message'] = '';
			$vars['edit'] = false;
			System::site()->SetVar('template','lang_posts_submit', '��������');
			System::site()->SetVar('template','lang_posts_submittitle', '�������� ����� ���������');
			$vars['visibility'] = 'hidden';
		}

		$vars['add'] = !$vars['edit'];
		$vars['add_guest'] = (System::user()->AccessLevel() == 3 || System::user()->AccessLevel() == 4) && $vars['add'];

		$vars['show_kaptcha'] = $vars['add_guest'] || (!System::user()->isAdmin() && $this->ShowKaptchaForMembers);
		$vars['kaptcha_url'] = 'index.php?name=plugins&amp;p=antibot';
		$vars['kaptcha_width'] = '120';
		$vars['kaptcha_height'] = '40';

		System::site()->Blocks[$PostFormBlockName]['vars'] = $vars;

		// JavaScript
		UseScript('bbcode_editor');

		// �������� ��� �����
		$smilies = System::database()->Select('smilies', "`enabled`='1'");
		if(System::database()->NumRows() == 0){
			System::site()->AddBlock('smilies', true, false, 'smile', '','��������� ���� ���.');
		}else{
			System::site()->AddBlock('smilies', true, true, 'smile');
			foreach($smilies as $smile){
				$smile['file'] = System::config('general/smilies_dir').$smile['file'];
				$smile['code'] = SafeDB($smile['code'], 255, str);
				$sub_codes = explode(',', $smile['code']);
				$smile['code'] = $sub_codes[0];
				System::site()->AddSubBlock('smilies', true, $smile);
			}
		}
	}

	public function CheckFlood(){
		System::database()->Select($this->PostsTable, "`user_ip`='".getip()."' and `post_date`>'".(time() - $this->FloodTime)."'");
		if(System::database()->NumRows() > 0){
			return true;
		}else{
			return false;
		}
	}

	// ������������ � ��������� ���������
	public function SavePost( $ObjectId, $Edit = false ){
		$errors = array();
		if($Edit){
			if(!isset($_GET['post_id'])){
				$errors[] = 'post_id �� ���������������� � GET.';
			}else{
				$post_id = SafeEnv($_GET['post_id'], 11,int);
				System::database()->Select($this->PostsTable, "`id`='$post_id'");
				$post = System::database()->FetchRow();
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
		if(System::user()->Auth){ // �������������� ������������, ��������� �����������
			if(!isset($_POST['post_message']) || !isset($_POST['parent_id'])){
				$errors[] = '������ �� ����������������.';
			}
			$user_id = System::user()->Get('u_id');
			$user_name = System::user()->Get('u_name');
			$user_email = System::user()->Get('u_email');
			$user_hideemail = System::user()->Get('u_hideemail');
			$user_homepage = System::user()->Get('u_homepage');
			if($Edit && !System::user()->isAdmin() && $post['user_id'] != System::user()->Get('u_id')){
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
							System::user()->Def('u_name', $user_name);
							$user_email = SafeEnv($_POST['user_email'], 255, str, true);
							if($user_email != ''){
								if(!CheckEmail($user_email)){
									$errors[] = '������ E-mail �� ����������. �� ������ ���� ����: <b>domain@host.ru</b> .';
								}
							}
							System::user()->Def('u_email', $user_email);
							if(isset($_POST['hideemail'])){
								$user_hideemail = '1';
							}else{
								$user_hideemail = '0';
							}
							System::user()->Def('u_hideemail', $user_hideemail);

							$user_homepage = Url(SafeEnv($_POST['user_homepage'], 250, str, true));
							System::user()->Def('u_homepage', $user_homepage);
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
		if(!System::user()->Auth || (!System::user()->isAdmin() && $this->ShowKaptchaForMembers)){
			if(!System::user()->isDef('captcha_keystring') || System::user()->Get('captcha_keystring') != $_POST['keystr']){
				$errors[] = '�� �������� ��� ����� ���� � ��������.';
			}
		}

		if(!isset($_POST['parent_id'])){
			$errors[] = 'parent_id �� ���������������� � POST.';
		}else{
			if($this->Answers == '1' || System::user()->isAdmin()){
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
				System::database()->Insert($this->PostsTable, $vals, $cols);
			}else{
				System::database()->Update($this->PostsTable, "`post_message`='$post_message'", "`id`='$post_id'");
			}
			return true;
		}else{
			return false;
		}
	}

	public function DeletePost( $post_id = null, $first = true ){
		if($post_id == null){
			if(isset($_GET['post_id'])){
				$post_id = $_GET['post_id'];
			}
		}
		if($post_id != null){
			System::database()->Select($this->PostsTable, "`id`='$post_id'");
			$post = System::database()->FetchRow();
		}else{
			$text = 'post_id ����� �� ����������������.';
			System::site()->AddTextBox('������.', '<center>'.$text.'</center>');
			return 0;
		}
		if($first){
			if(System::user()->Auth){
				$editing = (System::user()->Get('u_id') == $post['user_id'] || System::user()->isAdmin());
			}else{
				$editing = ($post['user_id'] == '0' && $post['user_ip'] == getip());
			}
			if(!$editing){
				$text = '� ��� ������������ ���� ��� �������� ����� ���������.';
				System::site()->AddTextBox('������.', '<center>'.$text.'</center>');
				return 0;
			}
		}
		if(!$first || isset($_GET['ok'])){
			$del_count = 1;
			$parent_posts = System::database()->Select($this->PostsTable, "`post_parent_id`='$post_id'");
			foreach($parent_posts as $post){
				$del_count += $this->DeletePost(SafeDB($post['id'], 11, int), false);
			}
			System::database()->Delete($this->PostsTable, "`id`='$post_id'");
			return $del_count;
		}else{
			$text = '<br />������� ���������?<br /><br />'
			. '<a href="'.$this->DeletePageUrl.'&amp;post_id='.$post_id.'&amp;ok=1">��</a> &nbsp;
			&nbsp;&nbsp;&nbsp;&nbsp; <a href="javascript:history.go(-1)">���</a><br /><br />';
			System::site()->AddTextBox('', '<center>'.$text.'</center>');
			return 0;
		}
	}

	public function PrintErrors(){
		$text = '���� ��������� �� ��������� �� ��������� ��������:<br /><ul>';
		foreach($this->LastSaveErrors as $error){
			$text .= '<li>'.$error;
		}
		$text .= '</ul><center><a href="javascript:history.back()">�����</a></center>';
		return $text;
	}
}
