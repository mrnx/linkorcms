<?php

# LinkorCMS
# © 2011 Александр Галицкий (linkorcms@yandex.ru)
# Назначение: Шаблонизатор для админ-панели

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

include_once System::$config['inc_dir'].'page_template.class.php';

class AdminPage extends PageTemplate{

	public $SideBarMenuLinks = array();
	public $FormRows = array();

	/**
	 * Текущий субблок контента
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
	 * Инициализация класса
	 * @param  $PageTemplate Шаблон используемый в качестве главного(body)
	 * @return void
	 */
	public function Init( $PageTemplate ){
		$ajax = IsAjax();
		$this->InitPageTemplate($ajax);
		$this->SetGZipCompressionEnabled(System::$config['general']['gzip_status'] == '1');

		// Папка с шаблоном
		$Template = 'default_admin'; // fixme: Вынести в конфигурацию сата
		$TemplateDir = System::$config['tpl_dir'].$Template.'/';
		$DefaultTemplateDir = System::$config['tpl_dir'].'default_admin'.'/'; // fixme: доработать

		if($ajax){ // Загрузка страницы посредством AJAX запроса
			$this->InitStarkyt($TemplateDir, $PageTemplate);
		} else{
			$this->SetRoot($TemplateDir);
			$this->DefaultRoot = $DefaultTemplateDir;
			$this->SetTempVar('head', 'body', $PageTemplate);
			$this->Title = 'Админ-панель';
		}
	}

	public function InitPage(){
		if(IsAjax()){
			$PageTemplate = 'theme_ajax.html';
		}else{
			$PageTemplate = 'theme_admin.html';
		}
		$this->Init($PageTemplate);

		// Добавляем блоки и переменные
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
		$vars['site']                   = System::$config['general']['site_name'];
		$vars['errors_text']            = '';
		$vars['tool_menu_block']        = false;
		$vars['content_block']          = false;
		$this->BlockTemplate->vars = $vars;
	}

	public function Login($AuthMessage = '', $AuthTitle = 'Авторизация администратора'){
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
	 * Добавляет подзаголовок в заголовок страницы панели управления
	 * @param <type> $subtitle
	 */
	public function AddSubTitle( $subtitle ){
		$this->Title .= ' > '.$subtitle;
	}

	/**
	 * Открывает новый блок контента
	 * @param <type> $title
	 */
	public function AddCenterBox( $title ){
		$this->BlockContents = $this->BlockContentBox->NewSubBlock(true, array('title'=>$title), array(), '', '')->NewBlock('contents', true, true, 'content');
		$this->BlockTemplate->vars['content_block'] = true;
	}

	/**
	 * Добавляет в блок обычный текст
	 * @param <type> $text
	 */
	public function AddText( $text ){
		$this->BlockContents->NewSubBlock(true, array(), array(), '', $text);
	}

	/**
	 * Добавляет текстовый блок
	 * @param <type> $title
	 * @param <type> $text
	 */
	public function AddTextBox( $title, $text ){
		$this->AddCenterBox($title);
		$this->AddText($text);
	}

	/**
	 * Добавляет блок постраничной навигации
	 */
	public function AddNavigation(){
		$this->BlockContents->NewSubBlock(true, array(), array(), 'navigation_subblock.html');
	}

	/**
	 * Добавляет новый текстовый блок сообщая пользователю что раздел находится в разработке
	 * @param <type> $name
	 */
	public function NotDeveloping( $name ){
		$text = 'Раздел <u>'.$name.'</u> не реализован в этой версии программы.';
		$this->AddTextBox('!!! В разработке !!!', $text);
	}

	/**
	 * Генерирует код красивой ссылки в виде кнопки
	 * @param <type> $Title
	 * @param <type> $Url
	 * @param <type> $ImgSrc
	 * @return <type>
	 */
	public function SpeedButton( $Title, $Url, $ImgSrc ){
		return '<a title="'.$Title.'" href="'.$Url.'" class="button"><img src="'.$ImgSrc.'" alt="'.$Title.'" /></a>';
	}

	/**
	 * Генерирует код кнопки с запросом на выполнение
	 * @param  $Title
	 * @param  $Url
	 * @param  $ImgSrc
	 * @param string $Confirm
	 * @return string
	 */
	public function SpeedConfirm( $Title, $Url, $ImgSrc, $Confirm = 'Уверены?' ){
		$OnClick = "SpeedConfirmButtonClick('$Confirm', this)";
		return '<a title="'.$Title.'" href="'.$Url.'" class="button" onclick="'.$OnClick.'"><img src="'.$ImgSrc.'" alt="'.$Title.'" /></a>';
	}

	/**
	 * Генерирует статусную кнопку работающую через AJAX запрос. При изменении статуса может изменить картинку на кнопке.
	 * @param  $EnabledTitle
	 * @param  $DisabledTitle
	 * @param  $AjaxUrl
	 * @param  $Status
	 * @param  $EnabledImage
	 * @param  $DisabledImage
	 * @return string
	 */
	public function SpeedStatus( $EnabledTitle, $DisabledTitle, $AjaxUrl, $Status, $EnabledImage, $DisabledImage ){
		$ImgSrc = ($Status ? $EnabledImage : $DisabledImage);
		$Title = ($Status ? $EnabledTitle : $DisabledTitle);
		$OnClick = "SpeedStatusButtonClick('$EnabledTitle', '$DisabledTitle', '$EnabledImage', '$DisabledImage', '$AjaxUrl', this); return false;";
		return '<a title="'.$Title.'" href="#" class="button" onclick="'.$OnClick.'"><img src="'.$ImgSrc.'" alt="'.$Title.'" /></a>';
	}

	/**
	 * Кнопка выполняющая JavaScript
	 * @param  $Title
	 * @param  $ImgSrc
	 * @param  $OnClickJavaScript
	 * @return string
	 */
	public function SpeedFunction( $Title, $ImgSrc, $OnClickJavaScript ){
		return '<a title="'.$Title.'" href="#" class="button" onclick="'.$OnClickJavaScript.'"><img src="'.$ImgSrc.'" alt="'.$Title.'" /></a>';
	}

	/**
	 * Добавляет Элемент к форме
	 * @param <type> $capt
	 * @param <type> $ctrl
	 */
	public function FormRow( $Caption, $Control, $Width = false, $OtherParams = '' ){
		$this->FormRows[] = array('row', array('caption'=>$Caption, 'control'=>$Control, 'width'=>$Width, 'other_params'=>$OtherParams, 'title'=>false, 'row'=>true));
	}

	public function FormTitleRow( $TitleCaption, $OtherParams = '' ){
		$this->FormRows[] = array('row', array('caption'=>$TitleCaption, 'other_params'=>$OtherParams, 'title'=>true, 'row'=>false));
	}

	/**
	 * Добавляет Элемент с удобным расположением для текстового поля
	 * @param <type> $capt
	 * @param <type> $ctrl
	 */
	public function FormTextRow( $capt, $ctrl ){
		$this->FormRows[] = array('coll', array('caption'=>$capt, 'control'=>$ctrl));
	}

	/**
	 * Очищает данные формы
	 */
	public function FormClear(){
		$this->FormRows = array();
	}

	/**
	 * Добавляет форму к странице
	 * @param <type> $open
	 * @param <type> $submit_btn
	 */
	public function AddForm( $open, $submit_btn ){
		$sub = $this->BlockContents->NewSubBlock(true, array(), array(), 'form.html', '');
		$rows = $sub->NewBlock('rows', true, true, 'row');

		foreach($this->FormRows as $row){
			if($row[0] == 'row'){
				$rows->NewSubBlock(true, $row[1]);
			}else{
				$rows->NewSubBlock(true, $row[1], array(), 'form_textarea.html');
			}
		}
		$sub->vars = array('form_open'=>$open, 'form_submit'=>$submit_btn);
		$this->FormClear();
	}

	/**
	 * Генерирует и выводит верхнее меню администратора
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
	 * Выводит данные верхнего меню в формате JSON сразу в шаблон
	 * @return
	 */
	function AddAdminMenu(){
		if(IsAjax()) return;
		$menu = System::db()->Select('admin_menu', "`enabled`='1'");
		SortArray($menu, 'order');
		$items = array(); // Элементы меню по родительским индексам
		foreach($menu as &$item){
			$items[$item['parent']][] = $item;
		}
		$this->BlockTemplate->vars['menu_data'] = JsonEncode($this->GenAdminMenu($items));
	}

	/**
	 * Добавляет внутреннюю ссылку админ-панели в левое меню. Может загружаться с помощью AJAX
	 * @param  $Title Видимое имя ссылки
	 * @param  $AdminLocation Параметры админ-панели. Например "exe=news&a=add"
	 * @return void
	 */
	public function SideBarAddMenuItemAdmin( $Title, $AdminLocation, $Active = false ){
		$url = ADMIN_FILE.'?'.$AdminLocation;
		$js = "return Admin.LoadPage('$url', event);";
		$this->SideBarMenuLinks[] = array('title'=>$Title, 'js'=>$js, 'url'=>$url, 'active'=>$Active);
	}

	/**
	 * Добавляет внешнюю ссылку в левое меню
	 * @param  $Title Видимое имя ссылки
	 * @param  $Url Адрес
	 * @param bool $External Открыть в новом окне/вкладке
	 * @return void
	 */
	public function SideBarAddMenuItemLink( $Title, $Url, $External = false, $Active = false ){
		if($External){
			$js = "window.open('$Url'); return false;"; // TODO: Дорабоать на Admin.Leave
		}else{
			$js = "location = '$Url'; return false;";
		}
		$this->SideBarMenuLinks[] = array('title'=>$Title, 'js'=>$js, 'url'=>$Url, 'active'=>$Active);
	}

	/**
	 * Добавляет ссылку в левое меню. При нажатии на ссылку будет выполнен JavaScript код
	 * @param  $Title Видимое имя ссылки
	 * @param  $JavaScript Код который будет выполнен при нажатии на ссылку
	 * @return void
	 */
	public function SideBarAddMenuItemJs( $Title, $JavaScript, $Active = false ){
		$this->SideBarMenuLinks[] = array('title'=>$Title, 'js'=>$JavaScript, 'url'=>'#', 'active'=>$Active);
	}

	/**
	 * Добавляет разделитель в левое меню
	 * @return void
	 */
	public function SideBarAddMenuItemDelimiter(){
		$this->SideBarMenuLinks[] = array('title'=>'', 'js'=>'');
	}

	/**
	 * Добавляет меню в левой колонке
	 * @param <type> $title
	 * @param <type> $text
	 * @return StarkytBlock
	 */
	public function SideBarAddMenuBlock( $Title = '' ){
		$menu = $this->BlockAdminBlocks->NewSubBlock(true, array('title'=>$Title), array(), 'block/menu.html')->NewBlock('menu_items', true, true, 'item');
		foreach($this->SideBarMenuLinks as $link){
			$menu->NewSubBlock(true, $link);
		}
		$this->SideBarMenuLinks = array();
		$this->BlockTemplate->vars['tool_menu_block'] = true;
		return $menu;
	}

	/**
	 * Добавляет текстовый блок в левой колонке
	 * @param <type> $Title
	 * @param <type> $Text
	 * @return StarkytSubBlock
	 */
	public function SideBarAddTextBlock( $Title, $Text ){
		$this->BlockTemplate->vars['tool_menu_block'] = true;
		return $this->BlockAdminBlocks->NewSubBlock(true, array('title'=>$Title, 'content'=>$Text), array(), 'block/text.html');
	}

	/**
	 * Добавляет блок с шаблоном в левую колонку
	 * @param  $Title
	 * @param  $TemplateFile
	 * @return StarkytSubBlock
	 */
	public function SideBarAddTemplatedBlock( $Title, $TemplateFile ){
		$this->BlockTemplate->vars['tool_menu_block'] = true;
		return $this->BlockAdminBlocks->NewSubBlock(true, array('title'=>$Title), array('content'=>$TemplateFile), 'block/text.html');
	}

	/**
	 * Выводит данные пользователю
	 */
	public function TEcho(){
		global $script_start_time;
		System::user()->OnlineProcess($this->Title);
		$this->BlockTemplate->vars['showinfo'] = System::$config['general']['show_script_time'];
		if(IsAjax()){
			$this->BlockTemplate->vars['head_items'] = $this->GenerateHead();
		}
		$this->BlockTemplate->vars['errors_text'] = implode(System::$Errors);
		$this->EchoAll();
	}

}

// Поддержка старого API
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
	System::admin()->SideBarAddMenuItemAdmin($name, 'exe='.$url, $sel);
}
function TAddToolBox( $cur_param_val ){System::admin()->SideBarAddMenuBlock();}
function FormRow( $capt, $ctrl ){System::admin()->FormRow($capt, $ctrl);}
function FormTextRow( $capt, $ctrl ){System::admin()->FormTextRow($capt, $ctrl);}
function AddForm( $open, $submit_btn ){System::admin()->AddForm($open, $submit_btn);}
function FormClear(){System::admin()->FormClear();}
function GenAdminMenu(){System::admin()->AddAdminMenu();}
function TEcho(){System::admin()->TEcho();}

?>