<?php

// LinkorCMS
// � 2011 ��������� �������� (linkorcms@yandex.ru)
// ����������: ������������ ��� �����-������

class AdminPage extends PageTemplate{

	public $SideBarMenuLinks = array();
	public $FormRows = array();
	public $ConfigGroups = array();

	/**
	 * ����� Ajax
	 * @var Bool
	 */
	public $AjaxMode;
	/**
	 * @var Starkyt
	 */
	public $AjaxSidebarTemplate;
	/**
	 * @var Starkyt
	 */
	public $AjaxContentTemplate;

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

	private $content_block = false;
	private $tool_menu_block = false;

	/**
	 * ������������� ������
	 * @param  $PageTemplate ������ ������������ � �������� ��������(body)
	 * @return void
	 */
	public function Init( $PageTemplate ){
		$this->AjaxMode = IsAjax();
		$this->InitPageTemplate($this->AjaxMode);
		$this->SetGZipCompressionEnabled(System::config('general/gzip_status') == '1');
		// ����� � ��������
		$Template = 'default_admin'; // fixme: ������� � ������������ ����
		$TemplateDir = System::config('tpl_dir').$Template.'/';
		$DefaultTemplateDir = System::config('tpl_dir').'default_admin'.'/'; // fixme: ����������
		$this->SetRoot($TemplateDir);
		$this->DefaultRoot = $DefaultTemplateDir;
		$this->Title = '�����-������';
		if($this->AjaxMode){ // �������� �������� ����������� AJAX �������
			$this->AjaxSidebarTemplate = new Starkyt();
			$this->AjaxSidebarTemplate->InitStarkyt($TemplateDir, 'sidebar_ajax.html');
			$this->AjaxContentTemplate = new Starkyt();
			$this->AjaxContentTemplate->InitStarkyt($TemplateDir, 'content_box_ajax.html');
		}else{
			$this->SetTempVar('head', 'body', $PageTemplate);
		}
	}

	public function InitPage(){
		$this->Init('theme_admin.html');
		// ��������� ����� � ����������
		if($this->AjaxMode){
			$this->BlockContentBox = $this->AjaxContentTemplate->NewBlock('content_box', true, true);
			$this->BlockAdminBlocks = $this->AjaxSidebarTemplate->NewBlock('admin_blocks', true, true, 'block');
		}else{
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
			$vars['cms_build']              = CMS_BUILD;
			$vars['cms_version_str']        = CMS_VERSION_STR;
			$vars['site']                   = System::config('general/site_name');
			$vars['errors_text']            = '';
			$vars['tool_menu_block']        = false;
			$vars['content_block']          = false;
			$this->BlockTemplate->vars = $vars;
		}
	}

	public function Login( $AuthMessage = '', $AuthTitle = '����������� ��������������' ){
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
		$this->Title .= ' &gt; '.$subtitle;
	}

	/**
	 * ��������� ����� ���� ��������.
	 *
	 * @param $title
	 */
	public function AddCenterBox( $title ){
		$this->BlockContents = $this->BlockContentBox->NewSubBlock(true, array('title'=>$title), array(), '', '')->NewBlock('contents', true, true, 'content');
		$this->content_block = true;
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
	 * ������� �������������� ���������
	 * @param $Text
	 * @return void
	 */
	public function Highlight( $Text ){
		$this->BlockContents->NewSubBlock(true, array('text'=>$Text), array(), 'highlight.html');
	}

	/**
	 * ������� ��������� �� ������
	 * @param $Text
	 * @return void
	 */
	public function HighlightError( $Text ){
		$this->BlockContents->NewSubBlock(true, array('text'=>$Text), array(), 'highlight_error.html');
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
		return '<a title="'.$Title.'" href="'.$Url.'" class="button" onmousedown="event.cancelBubble = true; event.stopPropagation();">'
			.($ImgSrc != '' ? '<img src="'.$ImgSrc.'" alt="'.$Title.'" />' : $Title).'</a>';
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
		$OnClick = "event.cancelBubble = true; event.stopPropagation(); return Admin.Buttons.Confirm('$ConfirmMsg', this);";
		return '<a title="'.$Title.'" href="'.$Url.'" class="button" onclick="'.$OnClick.'" onmousedown="event.cancelBubble = true; event.stopPropagation();">'
			.($ImgSrc != '' ? '<img src="'.$ImgSrc.'" alt="'.$Title.'" />' : $Title).'</a>';
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
		$OnClick = "if(confirm('$ConfirmMsg')) $OnClick; event.cancelBubble = true; event.stopPropagation(); return false";
		return '<a title="'.$Title.'" href="#" class="button" onclick="'.$OnClick.'" onmousedown="event.cancelBubble = true; event.stopPropagation();">'
			.($ImgSrc != '' ? '<img src="'.$ImgSrc.'" alt="'.$Title.'" />' : $Title).'</a>';
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
		$OnClick = "Admin.Buttons.Status('$EnabledTitle', '$DisabledTitle', '$EnabledImage', '$DisabledImage', '$AjaxUrl', this); event.cancelBubble = true; event.stopPropagation(); return false;";
		$s = '<a title="'.$Title.'" href="#" class="button" onclick="'.$OnClick.'" onmousedown="event.cancelBubble = true; event.stopPropagation();">'
			.($ImgSrc != '' ? '<img src="'.$ImgSrc.'" alt="'.$Title.'" />' : $Title).'</a>';
		return $s;
	}

	public function SpeedAjax($Title, $AjaxUrl, $ImgSrc = '', $ConfirmMsg = '', $OnStart = '', $OnSuccess = '', $Method = 'post', $Params = ''){
		$Title = htmlspecialchars($Title, ENT_QUOTES);
		$ConfirmMsg = htmlspecialchars($ConfirmMsg, ENT_QUOTES);
		$OnClick = "Admin.Buttons.Ajax('$AjaxUrl', function(link){ $OnStart }, function(data, textStatus, jqXHR){ $OnSuccess }, '$Method', '$Params', '$ConfirmMsg',  this); event.cancelBubble = true; event.stopPropagation(); return false;";
		return '<a title="'.$Title.'" href="#" class="button" onclick="'.$OnClick.'" onmousedown="event.cancelBubble = true; event.stopPropagation();">'
			.($ImgSrc != '' ? '<img src="'.$ImgSrc.'" alt="'.$Title.'" />' : $Title).'</a>';
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
	 * ��������� ������ �������� � ����� ������������
	 * @param $Groups
	 * @param bool $ShowTitles
	 * @return void
	 */
	public function ConfigGroups( $Groups, $ShowTitles = false ){
		if(!is_array($Groups)){
			$Groups = explode(',', $Groups);
		}
		foreach($Groups as $group){
			$this->ConfigGroups[] = array($group, $ShowTitles);
		}
	}

	/**
	 * ��������� ����� ������������ �� ��������
	 * @return void
	 */
	public function AddConfigsForm( $Action, $ConfigTable = 'config', $GroupsTable = 'config_groups' ){
		include_once System::config('inc_dir').'forms.inc.php';

		// ����������� ��������� � ��������������� �� �������
		$configsdb = System::database()->Select($ConfigTable, "`visible`='1'");
		$configs = array();
		foreach($configsdb as $conf){
			$configs[$conf['group_id']][] = $conf;
		}

		// ����������� ������ ��������
		$groupsdb = System::database()->Select($GroupsTable);
		$groups = array();
		foreach($groupsdb as $gr){
			$groups[$gr['name']] = $gr;
		}

		// ��������� ����� � �������� ������������ �����
		$form = $this->BlockContents->NewSubBlock(true, array('action'=>$Action, 'submit'=>$this->Submit('���������')), array(), 'config.html');
		$form_groups = $form->NewBlock('config_groups', true, true, 'group');

		foreach($this->ConfigGroups as $cgroup){
			$group = $groups[$cgroup[0]];
			if($cgroup[1]){
				$title = SafeDB($group['hname'], 255, str);
			}else{
				$title = false;
			}
			$aconfigs = isset($configs[$group['id']]);
			$form_group = $form_groups->NewSubBlock(true, array('title'=>$title, 'noconfigs'=>!$aconfigs));
			$form_configs = $form_group->NewBlock('config_group_configs', $aconfigs, true, 'config');
			if($aconfigs){
				foreach($configs[$group['id']] as $conf){
					$hname = SafeDB($conf['hname'], 255, str);
					$desc = SafeDB($conf['description'], 255, str);
					$name = SafeDB($group['name'], 255, str).'/'.SafeDB($conf['name'], 255, str);
					$value = $conf['value'];
					$kind = $conf['kind'];
					$type = $conf['type'];
					$values = $conf['values'];
					$vars = array(
						'title' => $hname,
						'description' => $desc,
						'controlvalue' => FormsGetControl($name, $value, $kind, $type, $values),
					);
					$form_configs->NewSubBlock(true, $vars);
				}
			}
		}
	}

	public function SaveConfigs( $SaveGroups, $ConfigTable = 'config', $GroupsTable = 'config_groups' ){
		if(!is_array($SaveGroups)){
			$SaveGroups = explode(',', $SaveGroups);
		}

		// ����������� ��������� � ��������������� �� �������
		$configsdb = System::database()->Select($ConfigTable, "`visible`='1'");
		$configs = array();
		foreach($configsdb as $conf){
			$configs[$conf['group_id']][] = $conf;
		}

		// ����������� ������ ��������
		$groupsdb = System::database()->Select($GroupsTable);
		$groups = array();
		foreach($groupsdb as $gr){
			$groups[$gr['name']] = $gr['id'];
		}

		foreach($SaveGroups as $gname){
			$gid = $groups[$gname];
			foreach($configs[$gid] as $conf){
				$cname = $conf['name'];
				$postname = $gname.'/'.$cname;
				if(isset($_POST[$postname])){
					$name = $cname;
					$kind = explode(':', $conf['kind']);
					$kind = trim(strtolower($kind[0]));
					$savefunc = trim($conf['savefunc']);
					$type = trim($conf['type']);
					if($type != ''){
						$type = explode(',', $type);
					}else{
						$type = array(255, str, false);
					}
					switch($kind){
						case 'edit':
						case 'radio':
						case 'combo':
						case 'text':
							if(FormsConfigCheck2Func('function', $savefunc, 'save')){
								$savefunc = CONF_SAVE_PREFIX.$savefunc;
								$value = $savefunc(FormsCheckType($_POST[$postname], $type));
							}else{
								$value = FormsCheckType($_POST[$postname], $type);
							}
							break;
						case 'check':
						case 'list':
							if(FormsConfigCheck2Func('function', $savefunc, 'save')){
								$savefunc = CONF_SAVE_PREFIX.$savefunc;
								$value = $savefunc(FormsCheckType($_POST[$postname], $type));
							}else{
								if(isset($_POST[$postname])){
									$c = count($_POST[$postname]);
								}else{
									$c = 0;
								}
								$value = '';
								for($k = 0; $k < $c; $k++){
									$value .= ',';
									$value .= FormsCheckType($_POST[$postname][$k], $type);
								}
								$value = substr($value, 1);
							}
							break;
						default:
							if(FormsConfigCheck2Func('function', $savefunc, 'save')){
								$savefunc = CONF_SAVE_PREFIX.$savefunc;
								$value = $savefunc(FormsCheckType($_POST[$postname], $type));
							}else{
								$value = FormsCheckType($_POST[$postname], $type);
							}
					}
					$where = "`name`='".SafeEnv($cname, 255, str)."' and `group_id`='".SafeEnv($gid, 11, int)."'";
					System::database()->Update($ConfigTable, "`value`='$value'", $where); // FIXME: ������������ ����������
				}
			}
		}
		// ������� ��� ��������
		$cache = LmFileCache::Instance();
		$cache->Clear('config');
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
				'blank' => $item['blank'] == '1' ? 'true' : 'false',
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
		if($this->AjaxMode) return; // ���� �� ������������ ��� AJAX ��������
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
		$this->tool_menu_block = true;
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
		$this->tool_menu_block = true;
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
		$this->tool_menu_block = true;
		return $this->BlockAdminBlocks->NewSubBlock(true, array('title'=>$Title), array('content'=>$TemplateFile), 'block/text.html');
	}

	/**
	 * ������� ������ ������������.
	 */
	public function TEcho(){
		if($this->AjaxMode){
			$start = microtime(true);
			$response = array(
				'content'=>'',
				'sidebar'=>'',
				'show_sidebar'=>false,
				'css'=>array(),
				'js'=>array(),
				'js_inline'=>'',
				'errors'=>'',
				'info'=>'',
				'title'=>''
			);
			$response['content'] = $this->AjaxContentTemplate->Compile();
			if($this->tool_menu_block){
				$response['sidebar'] = $this->AjaxSidebarTemplate->Compile();
			}else{
				$response['sidebar'] = '';
			}
			$response['show_sidebar'] = $this->tool_menu_block;

			foreach($this->css as $file){
				$response['css'][] = $file[0];
			}
			foreach($this->css_inc as $file){
				$response['css'][] = $file;
			}
			if($this->JQueryFile != ''){
				$response['js'][] = $this->JQueryFile;
				foreach($this->JQueryPlugins as $filename){
					$response['js'][] = $filename[0];
				}
			}
			foreach($this->js as $filename){
				$response['js'][] = $filename[0];
			}
			foreach($this->js_inc as $filename){
				$response['js'][] = $filename;
			}
			$response['js_inline'] = $this->TextJavaScript."\n".$this->OnLoadJavaScript;
			$response['errors'] = implode(System::$Errors);
			$response['info'] = $this->GetPageInfo($start);
			$response['title'] = $this->GenerateTitle();
			echo JsonEncode($response);
		}else{
			System::user()->OnlineProcess($this->Title);
			$this->BlockTemplate->vars['content_block'] = $this->content_block;
			$this->BlockTemplate->vars['tool_menu_block'] = $this->tool_menu_block;
			$this->BlockTemplate->vars['showinfo'] = System::config('general/show_script_time');
			$this->BlockTemplate->vars['errors_text'] = implode(System::$Errors);
			$this->EchoAll();
		}
	}

}

// ������ ������� ��� ���������� ������ ���� ����� ������
function TAddToolLink( $Title, $Action, $Exe ){
	System::admin()->SideBarAddMenuItem($Title, 'exe='.$Exe, $Action);
}
function TAddToolBox( $CurrentAction, $Title = '' ){
	System::admin()->SideBarAddMenuBlock($Title, $CurrentAction);
}

// ��������� ������� API
function AddCenterBox( $title ){System::admin()->AddCenterBox($title);}
function AddText( $text ){System::admin()->AddText($text);}
function AddTextBox( $title, $text ){System::admin()->AddTextBox($title, $text);}
function NotDeveloping( $name ){System::admin()->NotDeveloping($name);}
function SpeedButton( $Title, $Url, $ImgSrc ){return System::admin()->SpeedButton($Title, $Url, $ImgSrc);}
function TAddSubTitle( $subtitle ){System::admin()->AddSubTitle($subtitle);}
function AddNavigation(){System::admin()->AddNavigation();}
function FormRow( $capt, $ctrl ){System::admin()->FormRow($capt, $ctrl);}
function FormTextRow( $capt, $ctrl ){System::admin()->FormTextRow($capt, $ctrl);}
function AddForm( $open, $submit_btn ){System::admin()->AddForm($open, $submit_btn);}
function FormClear(){System::admin()->FormClear();}
function GenAdminMenu(){System::admin()->AddAdminMenu();}
function TEcho(){System::admin()->TEcho();}
