<?php

class Navigation{

	public $page = 0;
	public $param_name;
	public $template_block;
	public $FrendlyUrl = false;

	/**
	 * Конструктор
	 * @global <type> $site
	 * @param <type> $Page
	 * @param <type> $BlockName
	 * @param <type> $ParamName
	 */
	public function  __construct( $Page, $BlockName = 'navigation', $ParamName = 'page' ){
		global $site;
		$this->page = $Page;
		$this->param_name = $ParamName;
		$this->template_block = $BlockName;
	}

	private function GetUrl( $Link, $Page ){
		if($this->FrendlyUrl){
			return str_replace('{'.$this->param_name.'}', $Page, $Link);
		}else{
			return $Link.'&'.$this->param_name.'='.$Page;
		}
	}

	private function SetItem( $Pos, $Enabled ){
		global $site;
		$site->Blocks[$this->template_block]['sub'][0]['child'][$Pos]['sub'][0]['enabled'] = $Enabled;
	}

	private function AddItem( $Pos, $Link, $Page, $Text, $isText = false){
		global $site;
		$url = $this->GetUrl($Link, $Page);
		$vars = array(
			'link' => ( !$isText ? '<a href="'.$url.'">'.$Text.'</a>' : $Text),
			'link_url' => $url,
			'text' => $Text,
			'is_text' => $isText,
			'is_link' => !$isText,
			'pos' => $Pos
		);
		$site->Blocks[$this->template_block]['sub'][0]['child'][$Pos]['sub'][] = $site->CreateSubBlock(true, $vars);
	}

	/**
	 * Отключает блок постраничной навигации
	 * @global <type> $site
	 */
	public function DisableNavigation(){
		global $site;
		$site->AddBlock($this->template_block, true, false);
	}

	/**
	 * Добавляет постраничную навигацию
	 * @global <type> $site
	 * @param <type> $ItemsCount
	 * @param <type> $ItemsOnPage
	 * @param <type> $Link
	 * @param <type> $Page
	 */
	public function GenNavigationMenu2( $ItemsCount, $ItemsOnPage, $Link, $Page = null ){
		global $site;
		if($Page == null){
			$Page = $this->page;
		}
		if($ItemsCount <= $ItemsOnPage){
			$this->DisableNavigation();
			return;
		}else{
			$items_block_vars = array();
			$items_block_vars['back'] = $site->CreateBlock(true, true, 'item', 'navigation_item.html');
			$items_block_vars['begin'] = $site->CreateBlock(true, true, 'item', 'navigation_item.html');
			$items_block_vars['left'] = $site->CreateBlock(true, true, 'item', 'navigation_item.html');
			$items_block_vars['right'] = $site->CreateBlock(true, true, 'item', 'navigation_item.html');
			$items_block_vars['end'] = $site->CreateBlock(true, true, 'item', 'navigation_item.html');
			$items_block_vars['next'] = $site->CreateBlock(true, true, 'item', 'navigation_item.html');
			$items_block_vars['is_next'] = $site->CreateBlock();
			$items_block_vars['is_back'] = $site->CreateBlock();
			$site->AddBlock($this->template_block, true, false, 'nav', 'navigation.html', '', $items_block_vars);
		}

		$PagesCount = ceil($ItemsCount / $ItemsOnPage);

		if($Page < 1){
			$Page = 1;
		}elseif($Page > $PagesCount){
			$Page = $PagesCount;
		}

		#Определяем элементы слева и справа от текущего
		$min = $Page - 5;
		$max = $Page + 5;
		if($min < 1){
			$min = 1;
		}
		if($max > $PagesCount){ #Здесь всё правильно так как
			$max = $PagesCount; # count дает количество элементов в массиве от 1
		}

		#Выводим ссылку перемещения влево если нужно
		if($Page > 1){ #Можно ли перемещаться налево
			$back = $this->AddItem('back', $Link, $Page - 1, '&lt;&lt;&lt;');
			$this->SetItem('is_back', true);
		}else{
			$back = $this->AddItem('back', $Link, $Page - 1, '&lt;&lt;&lt;', true);
			$this->SetItem('is_back', false);
		}
		#Ссылка на первую страницу если нужно
		if($min > 1){
			$begin = $this->AddItem('begin', $Link, 1, '1..');
		}
		$litems = '';
		$ritems = '';
		#Выводим ссылки
		for($i = $min; $i <= $max; $i++){
			if($i < $Page){
				$litems .= $this->AddItem('left', $Link, $i, $i);
			}elseif($i > $Page){
				$ritems .= $this->AddItem('right', $Link, $i, $i);
			}else{
				$Active = $i;
			}
		}
		#Ссылка на последнюю страницу если нужно
		if($max < $PagesCount){
			$end = $this->AddItem('end', $Link, $PagesCount, '..'.$PagesCount);
		}
		#Выводим ссылку перемещения вправо если нужно
		if($Page < $PagesCount){
			$next = $this->AddItem('next', $Link, $Page + 1, '&gt;&gt;&gt;');
			$this->SetItem('is_next', true);
		}else{
			$next = $this->AddItem('next', $Link, $Page + 1, '&gt;&gt;&gt;', true);
			$this->SetItem('is_next', false);
		}
		$site->Blocks[$this->template_block]['sub'][0]['vars']['active'] = $Active;
	}

	/**
	 * Создает меню постраничной навигации
	 * @param Integer $page Какую страницу отображать(от 1)
	 * @param Array $items Результат запроса базы данных. В нем останутся только записи этой страницы.
	 * @param Integer $nOnPage Количество на страницу
	 * @param String $link Формат ссылок. В конец ссылки будет добавляться параметр page.
	 */
	public function GenNavigationMenu( &$Items, $ItemsOnPage, $Link, $Page = null ){
		global $site;
		if($Page == null){
			$Page = $this->page;
		}
		$ItemsCount = count($Items);
		if(count($Items) > $ItemsOnPage){
			$pages = array_chunk($Items, $ItemsOnPage);
		}else{
			$pages[0] = $Items;
		}
		$Items = $pages[$Page - 1];
		$this->GenNavigationMenu2($ItemsCount, $ItemsOnPage, $Link, $Page);
	}
}

?>