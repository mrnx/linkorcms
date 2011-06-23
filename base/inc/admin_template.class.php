<?php

# LinkorCMS
# � 2011 ��������� �������� (linkorcms@yandex.ru)
# ����������: ������������ ��� �����-������

class AdminPage extends PageTemplate{

	public $SideBarMenuLinks = array();
	public $FormRows = array();

	/**
	 * ������� ������� ��������
	 * @var StarkytSubBlock
	 */
	public $CurrentContentSubBlock;

	/**
	 * @var StarkytBlock
	 */
	public $BlockTemplate;

	/**
	 * @var StarkytBlock
	 */
	public $BlockContentBox;

	/**
	 * @var StarkytBlock
	 */
	public $BlockContents;

	/**
	 * @var StarkytBlock
	 */
	public $BlockAdminBlocks;

	/**
	 * ������������� ������
	 * @param  $PageTemplate ������ ������������ � �������� ��������(body)
	 * @return void
	 */
	public function Init( $PageTemplate ){
		$ajax = IsAjax();
		$this->InitPageTemplate($ajax);
		$this->SetGZipCompressionEnabled(System::config('general/gzip_status') == '1');

		// ����� � ��������
		$Template = 'default_admin'; // fixme: ������� � ������������ ����
		$TemplateDir = System::config('tpl_dir').$Template.'/';
		$DefaultTemplateDir = System::config('tpl_dir').'default_admin'.'/'; // fixme: ����������

		if($ajax){ // �������� �������� ����������� AJAX �������
			$this->InitStarkyt($TemplateDir, $PageTemplate);
		} else{
			$this->SetRoot($TemplateDir);
			$this->DefaultRoot = $DefaultTemplateDir;
			$this->SetTempVar('head', 'body', $PageTemplate);
			$this->Title = '�����-������';
		}
	}

	public function InitPage(){
		if(IsAjax()){
			$PageTemplate = 'theme_ajax.html';
		}else{
			$PageTemplate = 'theme_admin.html';
		}
		$this->Init($PageTemplate);

		// ��������� ����� � ����������
		$this->BlockTemplate = $this->NewBlock('template', true, false, 'page');
		$this->BlockContentBox = $this->NewBlock('content_box', true, true, '', 'content_box.html');
		$this->BlockAdminBlocks = $this->NewBlock('admin_blocks', true, true, 'block');
		$vars = array();
		$vars['dir']                    = $this->Root;
		$vars['admin_file']             = ADMIN_FILE;
		$vars['admin_name']             = System::user()->Get('u_name');
		$vars['admin_avatar']           = System::user()->Get('u_avatar');
		$vars['admin_avatar_small']     = System::user()->Get('u_avatar_small');
		$vars['admin_avatar_smallest']  = System::user()->Get('u_avatar_smallest');
		$vars['cms_name']               = CMS_NAME;
		$vars['cms_version']            = CMS_VERSION;
		$vars['cms_version_id']         = CMS_VERSION_ID;
		$vars['cms_build']              = CMS_BUILD;
		$vars['cms_version_str']        = CMS_VERSION_STR;
		$vars['site']                   = System::config('general/site_name');
		$vars['errors_text']            = '';
		$vars['tool_menu_block']        = false;
		$vars['content_block']          = false;
		$this->BlockTemplate->vars = $vars;
	}

	public function Login($AuthMessage = '', $AuthTitle = '����������� ��������������'){
		$this->Init('login.html');

		$this->SetTempVar('head', 'body', 'login.html');
		$this->AddBlock('template', true, false, 'login');
		$this->Blocks['template']['vars'] = array(
			'action' => '',
			'dir' => $this->Root,
			'auth_message' => $AuthMessage,
			'auth_title' => $AuthTitle
		);
		$this->AddCSSFile('login.css', false, true);
		$this->EchoAll();
		exit();
	}

	/**
	 * ��������� ������������ � ��������� �������� ������ ����������.
	 * @param $subtitle
	 */
	public function AddSubTitle( $subtitle ){
		$this->Title .= ' > '.$subtitle;
	}

	/**
	 * ��������� ����� ���� ��������.
	 *
	 * @param $title
	 */
	public function AddCenterBox( $title ){
		$this->BlockContents = $this->BlockContentBox->NewSubBlock(true, array('title'=>$title), array(), '', '')->NewBlock('contents', true, true, 'content');
		$this->BlockTemplate->vars['content_block'] = true;
	}

	/**
	 * ��������� � ���� ������� �����
	 * @param string $text
	 * @return void
	 */
	public function AddText( $text ){
		$this->BlockContents->NewSubBlock(true, array(), array(), '', $text);
	}

	/**
	 * ��������� ��������� ����
	 *
	 * @param $title
	 * @param $text
	 */
	public function AddTextBox( $title, $text ){
		$this->AddCenterBox($title);
		$this->AddText($text);
	}

	/**
	 * ��������� ���� ������������ ���������
	 */
	public function AddNavigation(){
		$this->BlockContents->NewSubBlock(true, array(), array(), 'navigation_subblock.html');
	}

	/**
	 * ��������� ����� ��������� ���� ������� ������������ ��� ������ ��������� � ����������
	 *
	 * @param string $name
	 */
	public function NotDeveloping( $name ){
		$text = '������ <u>'.$name.'</u> �� ���������� � ���� ������ ���������.';
		$this->AddTextBox('!!! � ���������� !!!', $text);
	}

	/**
	 * ���������� ��� �������� ������ � ���� ������
	 *
	 * @param string $Title
	 * @param string $Url
	 * @param string $ImgSrc
	 * @return string <type>
	 */
	public function SpeedButton( $Title, $Url, $ImgSrc = '' ){
		$Title = htmlspecialchars($Title, ENT_QUOTES);
		return '<a title="'.$Title.'" href="'.$Url.'" class="button">'
			.($ImgSrc != '' ? '<img src="'.$ImgSrc.'" alt="'.$Title.'" />' : $Title)
			.'</a>';
	}

	/**
	 * ������ - ������ � ���������������
	 *
	 * @param string $Title
	 * @param string $Url
	 * @param string $ImgSrc
	 * @param string $ConfirmMsg
	 * @return string
	 */
	public function SpeedConfirm( $Title, $Url, $ImgSrc = '', $ConfirmMsg = '�������?' ){
		$Title = htmlspecialchars($Title, ENT_QUOTES);
		$ConfirmMsg = htmlspecialchars($ConfirmMsg, ENT_QUOTES);
		$OnClick = "return Admin.Buttons.Confirm('$ConfirmMsg', this)";
		return '<a title="'.$Title.'" href="'.$Url.'" class="button" onclick="'.$OnClick.'">'
			.($ImgSrc != '' ? '<img src="'.$ImgSrc.'" alt="'.$Title.'" />' : $Title)
			.'</a>';
	}

	/**
	 * ������ � ����������� JS ���� � ���������������
	 *
	 * @param string $Title ��������� ������
	 * @param string $OnClick JavaScript ��� ��� �������
	 * @param string $ImgSrc ���� � ������
	 * @param string $ConfirmMsg ��������� ��������������, ���� ������, �� �� ���������
	 * @return void
	 */
	public function SpeedConfirmJs( $Title, $OnClick, $ImgSrc = '', $ConfirmMsg = '' ){
		$Title = htmlspecialchars($Title, ENT_QUOTES);
		$ConfirmMsg = htmlspecialchars($ConfirmMsg, ENT_QUOTES);
		$OnClick = "if(confirm('$ConfirmMsg')) $OnClick; return false";
		return '<a title="'.$Title.'" href="#" class="button" onclick="'.$OnClick.'">'
			.($ImgSrc != '' ? '<img src="'.$ImgSrc.'" alt="'.$Title.'" />' : $Title)
			.'</a>';
	}

	/**
	 * ���������� ��������� ������ ���������� ����� AJAX ������. ��� ��������� ������� ����� �������� �������� �� ������.
	 *
	 * @param  $EnabledTitle
	 * @param  $DisabledTitle
	 * @param  $AjaxUrl
	 * @param  $Status
	 * @param  $EnabledImage
	 * @param  $DisabledImage
	 * @return string
	 */
	public function SpeedStatus( $EnabledTitle, $DisabledTitle, $AjaxUrl, $Status, $EnabledImage, $DisabledImage ){
		$EnabledTitle = htmlspecialchars($EnabledTitle, ENT_QUOTES);
		$DisabledTitle = htmlspecialchars($DisabledTitle, ENT_QUOTES);

		$ImgSrc = ($Status ? $EnabledImage : $DisabledImage);
		$Title = ($Status ? $EnabledTitle : $DisabledTitle);
		$OnClick = "Admin.Buttons.Status('$EnabledTitle', '$DisabledTitle', '$EnabledImage', '$DisabledImage', '$AjaxUrl', this); return false;";
		$s = '<a title="'.$Title.'" href="#" class="button" onclick="'.$OnClick.'">'
			.($ImgSrc != '' ? '<img src="'.$ImgSrc.'" alt="'.$Title.'" />' : $Title)
			.'</a>';
		return $s;
	}

	public function SpeedAjax($Title, $AjaxUrl, $ImgSrc = '', $ConfirmMsg = '', $OnStart = '', $OnSuccess = '', $Method = 'post', $Params = ''){
		$Title = htmlspecialchars($Title, ENT_QUOTES);
		$ConfirmMsg = htmlspecialchars($ConfirmMsg, ENT_QUOTES);

		$OnClick = "Admin.Buttons.Ajax('$AjaxUrl', function(link){ $OnStart }, function(data, textStatus, jqXHR){ $OnSuccess }, '$Method', '$Params', '$ConfirmMsg',  this); return false;";
		return '<a title="'.$Title.'" href="#" class="button" onclick="'.$OnClick.'">'
			.($ImgSrc != '' ? '<img src="'.$ImgSrc.'" alt="'.$Title.'" />' : $Title)
			.'</a>';
	}

	/**
	 * ��������� ������� � �����
	 *
	 * @param $Caption
	 * @param $Control
	 * @param string $OtherParams
	 */
	public function FormRow( $Caption, $Control, $OtherParams = '' ){
		$this->FormRows[] = array('caption'=>$Caption, 'control'=>$Control, 'other_params'=>$OtherParams, 'type'=>'row');
	}

	/**
	 * ��������� ������������ �����.
	 *
	 * @param string $TitleCaption
	 * @param string $OtherParams
	 * @return void
	 */
	public function FormTitleRow( $TitleCaption, $OtherParams = '' ){
		$this->FormRows[] = array('caption'=>$TitleCaption, 'other_params'=>$OtherParams, 'type'=>'title');
	}

	/**
	 * ��������� ������� � ������� ������������� ��� ���������� ����
	 *
	 * @param string $Caption
	 * @param string $Control
	 * @param string $OtherParams
	 *
	 */
	public function FormTextRow( $Caption, $Control, $OtherParams = '' ){
		$this->FormRows[] = array('caption'=>$Caption, 'control'=>$Control, 'other_params'=>$OtherParams, 'type'=>'wide');
	}

	/**
	 * ������� ������ �����
	 */
	public function FormClear(){
		$this->FormRows = array();
	}

	/**
	 * ��������� ����� � ��������
	 *
	 * @param $open
	 * @param $submit_btn
	 */
	public function AddForm( $open, $submit_btn ){
		$sub = $this->BlockContents->NewSubBlock(true, array(), array(), 'form.html', '');
		$rows = $sub->NewBlock('rows', true, true, 'row');

		foreach($this->FormRows as $row){
			$rows->NewSubBlock(true, $row);
		}
		$sub->vars = array('form_open'=>$open, 'form_submit'=>$submit_btn);
		$this->FormClear();
	}

	/**
	 * ���������� � ������� ������� ���� ��������������
	 *
	 * @param $menu
	 * @param int $parentId
	 * @return array
	 */
	protected function GenAdminMenu(&$menu, $parentId = 0){
		$menuData = array();
		if(!isset($menu[$parentId])){
			return $menuData;
		}
		foreach($menu[$parentId] as &$item){
			$menuData[] = array(
				'id' => SafeDB($item['id'], 11, int),
				'title' => SafeDB($item['title'], 255, str),
				'icon' => SafeDB($item['icon'], 255, str),
				'admin_link' => ADMIN_FILE.'?'.SafeDB($item['admin_link'], 255, str),
				'external_link' => SafeDB($item['external_link'], 255, str),
				'js' => SafeDB($item['js'], 0, str, false, false),
				'blank' => $item['blank'],
				'type' => $item['type'],
				'submenu'   => $this->GenAdminMenu($menu, $item['id'])
			);
		}
		return $menuData;
	}

	/**
	 * ������� ������ �������� ���� � ������� JSON ����� � ������.
	 *
	 * @return
	 */
	function AddAdminMenu(){
		if(IsAjax()) return;
		$menu = System::database()->Select('adminmenu', "`enabled`='1'");
		SortArray($menu, 'order');
		$items = array(); // �������� ���� �� ������������ ��������
		foreach($menu as &$item){
			$items[$item['parent']][] = $item;
		}
		$this->BlockTemplate->vars['menu_data'] = JsonEncode($this->GenAdminMenu($items));
	}

	/**
	 * ��������� ���������� ������ �����-������ � ����� ����.
	 * ����� ����������� � ������� AJAX.
	 *
	 * @param  $Title ������� ��� ������
	 * @param  $AdminLocation ��������� �����-������. �������� "exe=news&a=add"
	 * @param bool $Active
	 * @return void
	 */
	public function SideBarAddMenuItem( $Title, $AdminLocation, $Active = false ){
		$url = ADMIN_FILE.'?'.$AdminLocation;
		$js = "return Admin.LoadPage('$url', event);";
		$this->SideBarMenuLinks[] = array('title'=>$Title, 'js'=>$js, 'url'=>$url, 'active'=>$Active);
	}

	/**
	 * ��������� ������� ������ � ����� ����.
	 *
	 * @param  $Title ������� ��� ������
	 * @param  $Url �����
	 * @param bool $External ������� � ����� ����/�������
	 * @param bool $Active
	 * @return void
	 */
	public function SideBarAddMenuItemLink( $Title, $Url, $External = false, $Active = false ){
		if($External){
			$js = "window.open('$Url'); return false;"; // TODO: ��������� �� Admin.Leave
		}else{
			$js = "location = '$Url'; return false;";
		}
		$this->SideBarMenuLinks[] = array('title'=>$Title, 'js'=>$js, 'url'=>$Url, 'active'=>$Active);
	}

	/**
	 * ��������� ������ � ����� ����. ��� ������� �� ������ ����� �������� JavaScript ���.
	 *
	 * @param  $Title ������� ��� ������
	 * @param  $JavaScript ��� ������� ����� �������� ��� ������� �� ������
	 * @param bool $Active
	 * @return void
	 */
	public function SideBarAddMenuItemJs( $Title, $JavaScript, $Active = false ){
		$this->SideBarMenuLinks[] = array('title'=>$Title, 'js'=>$JavaScript, 'url'=>'#', 'active'=>$Active);
	}

	/**
	 * ��������� ����������� � ����� ����.
	 *
	 * @return void
	 */
	public function SideBarAddMenuItemDelimiter(){
		$this->SideBarMenuLinks[] = array('title'=>'', 'js'=>'');
	}

	/**
	 * ��������� ���� � ����� �������.
	 *
	 * @param string $Title
	 * @param bool $ActiveItemActive
	 * @return StarkytBlock
	 */
	public function SideBarAddMenuBlock( $Title = '', $ActiveItemActive = true ){
		$menu = $this->BlockAdminBlocks->NewSubBlock(true, array('title'=>$Title), array(), 'block/menu.html')->NewBlock('menu_items', true, true, 'item');
		foreach($this->SideBarMenuLinks as $link){
			$link['active'] = ($link['active'] == $ActiveItemActive);
			$menu->NewSubBlock(true, $link);
		}
		$this->SideBarMenuLinks = array();
		$this->BlockTemplate->vars['tool_menu_block'] = true;
		return $menu;
	}

	/**
	 * ��������� ��������� ���� � ����� �������.
	 *
	 * @param $Title
	 * @param $Text
	 * @return StarkytSubBlock
	 */
	public function SideBarAddTextBlock( $Title, $Text ){
		$this->BlockTemplate->vars['tool_menu_block'] = true;
		return $this->BlockAdminBlocks->NewSubBlock(true, array('title'=>$Title, 'content'=>$Text), array(), 'block/text.html');
	}

	/**
	 * ��������� ���� � �������� � ����� �������.
	 *
	 * @param  $Title
	 * @param  $TemplateFile
	 * @return StarkytSubBlock
	 */
	public function SideBarAddTemplatedBlock( $Title, $TemplateFile ){
		$this->BlockTemplate->vars['tool_menu_block'] = true;
		return $this->BlockAdminBlocks->NewSubBlock(true, array('title'=>$Title), array('content'=>$TemplateFile), 'block/text.html');
	}

	/**
	 * ������� ������ ������������.
	 */
	public function TEcho(){
		global $script_start_time;
		System::user()->OnlineProcess($this->Title);
		$this->BlockTemplate->vars['showinfo'] = System::config('general/show_script_time');
		if(IsAjax()){
			$this->BlockTemplate->vars['head_items'] = $this->GenerateHead();
		}
		$this->BlockTemplate->vars['errors_text'] = implode(System::$Errors);
		$this->EchoAll();
	}

}

// ��������� ������� API
function AddCenterBox( $title ){System::admin()->AddCenterBox($title);}
function AddText( $text ){System::admin()->AddText($text);}
function AddTextBox( $title, $text ){System::admin()->AddTextBox($title, $text);}
function NotDeveloping( $name ){System::admin()->NotDeveloping($name);}
function SpeedButton( $Title, $Url, $ImgSrc ){return System::admin()->SpeedButton($Title, $Url, $ImgSrc);}
function TAddSubTitle( $subtitle ){System::admin()->AddSubTitle($subtitle);}
function AddNavigation(){System::admin()->AddNavigation();}
function TAddToolLink( $name, $param_val, $url ){
	if(isset($_GET['a'])){
		$sel = $_GET['a'] == $param_val;
	}else{
		$sel = $param_val == 'main';
	}
	System::admin()->SideBarAddMenuItem($name, 'exe='.$url, $sel);
}
function TAddToolBox( $cur_param_val ){System::admin()->SideBarAddMenuBlock();}
function FormRow( $capt, $ctrl ){System::admin()->FormRow($capt, $ctrl);}
function FormTextRow( $capt, $ctrl ){System::admin()->FormTextRow($capt, $ctrl);}
function AddForm( $open, $submit_btn ){System::admin()->AddForm($open, $submit_btn);}
function FormClear(){System::admin()->FormClear();}
function GenAdminMenu(){System::admin()->AddAdminMenu();}
function TEcho(){System::admin()->TEcho();}

?>