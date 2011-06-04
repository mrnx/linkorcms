<?php

	if(!defined('VALID_RUN')){
		header("HTTP/1.1 404 Not Found");
		exit;
	}

	UseScript('jquery_ui');

	System::site()->JQueryPlugin('scripts/jquery_ui_table/jquery.ui.table.js', true);
	System::site()->AddCSSFile('scripts/jquery_ui_table/theme/jquery.ui.table.css', true);

	class jQueryUiTable{

		private $id = 0;
		private $columns = array();
		private $rows = array();
		public $listing = '';
		public $total = 0;
		public $page = 0;
		public $onpage = 10;
		public $sortby = -1;
		public $sortdesc = false;

		public function AddColumn( $Title, $Align = 'left', $Sortable = true, $Sorted = false, $Desc = false ){
			$this->columns[] = array(
				'id' => $this->id,
				'title' => $Title,
				'sortable' => $Sortable,
				'align' => $Align
			);
			$this->id++;
		}

		public function AddRow( $RowId, $Col1, $Col2 = '', $Col3 = ''){
			$args = func_get_args();
			array_shift($args);
			if(is_array($args[0])){
				$args = $args[0];
			}
			$this->rows[] = array(
				'id' => $RowId,
				'data' => $args
			);
		}

		public function GetRowsJson(){
			return JsonEncode($this->rows);
		}

		public function GetOptions(){
			$options = array(
				'columns' => $this->columns,
				'rows' => $this->rows,
				'listing' => $this->listing,
				'total' => $this->total,
				'page' => $this->page,
				'onpage' => $this->onpage,
				'sortby' => $this->sortby,
				'sortdesc' => $this->sortdesc
			);
			return JsonEncode($options);
		}

		public function GetHtml(){
			return "<div id=\"news_table\"></div><script>$('#news_table').table(".$this->GetOptions().");</script>";
		}

	}

?>