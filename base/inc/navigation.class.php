<?php

class Navigation{

	static public $StarkytDefault = null; // ������������, ���� ������������ �������� �� ������������
	static public $AdminAjaxLinks = false; // ������������ Ajax ������ ��� �����-������

	public $page = 0;
	public $param_name;
	public $template_block;
	public $FrendlyUrl = false;
	public $Starkyt = null;
	/**
	 * ������ ��� ������. ���������� � #.
	 * @var string
	 */
	public $Anchor = '';

	/**
	 * �����������
	 * @global <type> $site
	 * @param <type> $Page
	 * @param <type> $BlockName
	 * @param <type> $ParamName
	 */
	public function  __construct( $Page, $BlockName = 'navigation', $ParamName = 'page' ){
		$this->page = $Page;
		$this->param_name = $ParamName;
		$this->template_block = $BlockName;
		if(self::$StarkytDefault == null){
			$this->Starkyt = System::site();
		}else{
			$this->Starkyt = self::$StarkytDefault;
		}
	}

	private function GetUrl( $Link, $Page ){
		if($this->FrendlyUrl){
			return str_replace('{'.$this->param_name.'}', $Page, $Link).$this->Anchor;
		}else{
			return $Link.'&'.$this->param_name.'='.$Page.$this->Anchor;
		}
	}

	private function SetItem( $Pos, $Enabled ){
		$this->Starkyt->Blocks[$this->template_block]['sub'][0]['child'][$Pos]['sub'][0]['enabled'] = $Enabled;
	}

	private function AddItem( $Pos, $Link, $Page, $Text, $isText = false){
		$url = $this->GetUrl($Link, $Page);
		if(!$isText){
			if(self::$AdminAjaxLinks){
				$link_a = System::admin()->Link($Text, $url);
			}else{
				$link_a = '<a href="'.$url.'">'.$Text.'</a>';
			}
		}else{
			$link_a = $Text;
		}
		$vars = array(
			'link' => $link_a,
			'link_url' => $url,
			'text' => $Text,
			'is_text' => $isText,
			'is_link' => !$isText,
			'pos' => $Pos
		);
		$this->Starkyt->Blocks[$this->template_block]['sub'][0]['child'][$Pos]['sub'][] = $this->Starkyt->CreateSubBlock(true, $vars);
	}

	/**
	 * ��������� ���� ������������ ���������
	 * @global <type> $site
	 */
	public function DisableNavigation(){
		$this->Starkyt->AddBlock($this->template_block, true, false);
	}

	/**
	 * ��������� ������������ ���������
	 * @global <type> $site
	 * @param <type> $ItemsCount
	 * @param <type> $ItemsOnPage
	 * @param <type> $Link
	 * @param <type> $Page
	 */
	public function GenNavigationMenu2( $ItemsCount, $ItemsOnPage, $Link, $Page = null ){
		if($Page == null){
			$Page = $this->page;
		}
		if($ItemsCount <= $ItemsOnPage){
			$this->DisableNavigation();
			return;
		}else{
			$items_block_vars = array();
			$items_block_vars['back'] = $this->Starkyt->CreateBlock(true, true, 'item', 'navigation_item.html');
			$items_block_vars['begin'] = $this->Starkyt->CreateBlock(true, true, 'item', 'navigation_item.html');
			$items_block_vars['left'] = $this->Starkyt->CreateBlock(true, true, 'item', 'navigation_item.html');
			$items_block_vars['right'] = $this->Starkyt->CreateBlock(true, true, 'item', 'navigation_item.html');
			$items_block_vars['end'] = $this->Starkyt->CreateBlock(true, true, 'item', 'navigation_item.html');
			$items_block_vars['next'] = $this->Starkyt->CreateBlock(true, true, 'item', 'navigation_item.html');
			$items_block_vars['is_next'] = $this->Starkyt->CreateBlock();
			$items_block_vars['is_back'] = $this->Starkyt->CreateBlock();
			$this->Starkyt->AddBlock($this->template_block, true, false, 'nav', 'navigation.html', '', $items_block_vars);
		}

		$PagesCount = ceil($ItemsCount / $ItemsOnPage);

		if($Page < 1){
			$Page = 1;
		}elseif($Page > $PagesCount){
			$Page = $PagesCount;
		}

		#���������� �������� ����� � ������ �� ��������
		$min = $Page - 5;
		$max = $Page + 5;
		if($min < 1){
			$min = 1;
		}
		if($max > $PagesCount){ #����� �� ��������� ��� ���
			$max = $PagesCount; # count ���� ���������� ��������� � ������� �� 1
		}

		#������� ������ ����������� ����� ���� �����
		if($Page > 1){ #����� �� ������������ ������
			$back = $this->AddItem('back', $Link, $Page - 1, '&lt;&lt;&lt;');
			$this->SetItem('is_back', true);
		}else{
			$back = $this->AddItem('back', $Link, $Page - 1, '&lt;&lt;&lt;', true);
			$this->SetItem('is_back', false);
		}
		#������ �� ������ �������� ���� �����
		if($min > 1){
			$begin = $this->AddItem('begin', $Link, 1, '1..');
		}
		$litems = '';
		$ritems = '';
		#������� ������
		for($i = $min; $i <= $max; $i++){
			if($i < $Page){
				$litems .= $this->AddItem('left', $Link, $i, $i);
			}elseif($i > $Page){
				$ritems .= $this->AddItem('right', $Link, $i, $i);
			}else{
				$Active = $i;
			}
		}
		#������ �� ��������� �������� ���� �����
		if($max < $PagesCount){
			$end = $this->AddItem('end', $Link, $PagesCount, '..'.$PagesCount);
		}
		#������� ������ ����������� ������ ���� �����
		if($Page < $PagesCount){
			$next = $this->AddItem('next', $Link, $Page + 1, '&gt;&gt;&gt;');
			$this->SetItem('is_next', true);
		}else{
			$next = $this->AddItem('next', $Link, $Page + 1, '&gt;&gt;&gt;', true);
			$this->SetItem('is_next', false);
		}
		$this->Starkyt->Blocks[$this->template_block]['sub'][0]['vars']['active'] = $Active;
	}

	/**
	 * ������� ���� ������������ ���������
	 * @param Integer $page ����� �������� ����������(�� 1)
	 * @param Array $items ��������� ������� ���� ������. � ��� ��������� ������ ������ ���� ��������.
	 * @param Integer $nOnPage ���������� �� ��������
	 * @param String $link ������ ������. � ����� ������ ����� ����������� �������� page.
	 */
	public function GenNavigationMenu( &$Items, $ItemsOnPage, $Link, $Page = null ){
		if($Page == null){
			$Page = $this->page;
		}
		$ItemsCount = count($Items);
		if(count($Items) > $ItemsOnPage){
			$pages = array_chunk($Items, $ItemsOnPage);
		}else{
			$pages[0] = $Items;
		}
		$pages_count = count($pages);
		if($Page > $pages_count) $Page = $pages_count;
		if($Page < 0) $Page = 0;
		$Items = $pages[$Page - 1];
		$this->GenNavigationMenu2($ItemsCount, $ItemsOnPage, $Link, $Page);
	}
}

