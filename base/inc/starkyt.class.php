<?php

# LinkorCMS
# © 2006-2011 Александр Галицкий (linkorcms@yandex.ru)
# Файл: starkyt.class.php
# Назначение: Класс для интерпретации шаблонов LinkorCMS

define("STARKYT_START", 1);
define("STARKYT_COND", 2);
define("STARKYT_POLY", 3);
define("STARKYT_TABLE", 4);
define("STARKYT_VAR", 5);
define("STARKYT_TEMPVAR", 6);
define("STARKYT_END", 7);
define('STARKYT_TEXT', 8);
define('STARKYT_SPEED_COND', 9);
define("STARKYT_OPENKEY", '{');
define("STARKYT_CLOSEKEY", '}');

class Starkyt extends HTML{

	public $Root = ''; // Имя папки с используемыми шаблонами, с последним слешем
	public $DefaultRoot = ''; // Имя папки с шаблонами по умолчанию
	public $TemplateFile = ''; // Имя файла с расширением в папке шаблона, с которого начинается компиляция

	public $TableOpen = '';
	public $TableClose = '';
	public $TableCellOpen = '';
	public $TableCellClose = '';

	public $Blocks = array();
	public $OpenedBlocks = array();
	public $CurrentBlock = null;
	public $Cache = array(); // Кэш загружаемых файлов

	// Конструктор
	public function InitStarkyt( $RootDir, $TemplateFile ){
		$this->Root = $RootDir;
		$this->TemplateFile = $TemplateFile;
		$this->SBlocks = $this->GetSBlocks($TemplateFile);
		$this->AddBlock('comment', false);// Добавляем блок комментариев {comment}комментарий{/comment}
	}

	/**
	 * Изменяет корневую папку шаблона
	 * @param  $Root
	 * @return void
	 */
	public function SetRoot($Root){
		$this->Root = $Root;
	}

	/**
	 * Проверяет, существует ли шаблон
	 */
	public function TemplateExists( $TemplateFile ){
		if($TemplateFile == ''){
			return false;
		}
		if(is_file($this->Root.$TemplateFile)){
			return $this->Root.$TemplateFile;
		}elseif(is_file($this->DefaultRoot.$TemplateFile)){
			return $this->DefaultRoot.$TemplateFile;
		}else{
			return false;
		}
	}

	/**
	 * Устанавливает шаблоны таблиц
	 * @deprecated
	 */
	public function SetTableTemplate( $table_open, $table_close, $table_cell_open, $table_cell_close ){
		$this->TableOpen = file_get_contents($this->TemplateExists($table_open));
		$this->TableClose = file_get_contents($this->TemplateExists($table_close));
		$this->TableCellOpen = file_get_contents($this->TemplateExists($table_cell_open));
		$this->TableCellClose = file_get_contents($this->TemplateExists($table_cell_close));
	}

	/**
	 * Загружает шаблон и разбивает на блоки
	 * @param  $templateFile
	 * @return array
	 */
	public function &GetSBlocks( $templateFile ){
		if(isset($this->Cache[$templateFile])) {
			return $this->Cache[$templateFile];
		}
		$blocks2 = array();
		if($templateFile == ''){
			return $blocks2;
		}
		$filename = $this->TemplateExists($templateFile);
		if($filename === false){
			echo 'не удалось найти шаблон: '.$templateFile;
			return $blocks2;
		}
		global $config, $site, $user, $db;
		ob_start();
		include($filename);
		$source = ob_get_clean();

		// Экранирование спецсимволов
		$source = preg_replace('#\{noblocks\}(.*)\{/noblocks\}#se', 'str_replace(array("{", "}", "\\\'"), array("&#123;", "&#125;", "\'"), "\\1")', $source);
		$source = str_replace(array('\{', '\}'), array('&#123;', '&#125;'), $source);

		$blocks = explode(STARKYT_OPENKEY, $source);
		$blocks2 = array($blocks[0]);
		for($i = 1, $cnt = count($blocks); $i < $cnt; $i++) {
			$tag = explode(STARKYT_CLOSEKEY, $blocks[$i]);
			$blocks2[] = $tag[0];
			if(isset($tag[1])){
				$blocks2[] = $tag[1];
			} else {
				$blocks2[] = '';
			}
		}
		$this->Cache[$templateFile] = $blocks2;
		return $blocks2;
	}

	/**
	 * Создает заготовку для нового блока
	 */
	static public function &CreateBlock( $enabled = true, $poly = false, $alias = '', $templatefile = '', $plaintext = '', $child = array(), $parent = null ){
		if(!$poly){
			$new = array('type' => STARKYT_POLY, 'alias' => $alias, 'sub' => array(Starkyt::CreateSubBlock($enabled, array(), array(), $templatefile, $plaintext, $child)), 'parent' => $parent);
		}else{
			$new = array('type' => STARKYT_POLY, 'alias' => $alias, 'sub' =>  array(), 'parent' => $parent);
			if($templatefile != ''){
				$new['template'] = $templatefile;
			}
			if($plaintext != ''){
				$new['plaintext'] = $plaintext;
			}
			if(count($child) > 0){
				$new['child'] = $child;
			}
		}
		return $new;
	}

	/**
	 * Создает заготовку для нового субблока
	 */
	static public function &CreateSubBlock( $enabled = true, $vars = array(), $tempvars = array(), $templatefile = '', $plaintext = '', $child = array(), $parent = null ){
		$block = array(
			'enabled' => $enabled,
			'plaintext' => $plaintext,
			'template' => $templatefile,
			'vars' => $vars,
			'tempvars' => $tempvars,
			'child' => $child,
			'parent' => $parent
		);
		return $block;
	}

	/**
	 * Создает заготовку таблицы
	 * @deprecated
	 */
	static public function &CreateTable( $enabled = true, $alias = '', $cols = 5, $templatefile = '', $plaintext = '' ){
		$new = array('type' => STARKYT_TABLE, 'alias' => $alias, 'sub' =>  array(), 'cols' => $cols);
		if($templatefile != ''){
			$new['template'] = $templatefile;
		}
		if($plaintext != ''){
			$new['plaintext'] = $plaintext;
		}
		return $new;
	}

	/**
	 * Создает заготовку ячейки таблицы
	 * @deprecated
	 */
	static public function &CreateTableCell( $enabled = true, $vars = array(), $tempvars = array(), $templatefile = '', $plaintext = '', $colspan = 1, $rowspan = 1, $child = array() ){
		$table = array(
			'enabled' => $enabled,
			'plaintext' => $plaintext,
			'template' => $templatefile,
			'vars' => $vars,
			'tempvars' => $tempvars,
			'child' => $child,
			'colspan' => $colspan,
			'rowspan' => $rowspan
		);
		return $table;
	}

	/**
	 * Создает и добавляет новый блок
	 */
	public function AddBlock( $name, $enabled = true, $poly = false, $alias = '', $templatefile = '', $plaintext = '', $child = array() ){
		$this->Blocks[$name] = Starkyt::CreateBlock($enabled, $poly, $alias, $templatefile, $plaintext, $child);
		$this->GetSBlocks($templatefile);
	}

	/**
	 * Создает и добавляет новый субблок в блок с определенным именем
	 */
	public function AddSubBlock( $name, $enabled = true, $vars = array(), $tempvars = array(), $templatefile = '', $plaintext = '', $child = array() ){
		$this->Blocks[$name]['sub'][] = Starkyt::CreateSubBlock($enabled, $vars, $tempvars, $templatefile, $plaintext, $child);
		$this->GetSBlocks($templatefile);
		foreach($tempvars as $temp){
			$this->GetSBlocks($temp);
		}
		return (count($this->Blocks[$name]['sub']) - 1);
	}

	/**
	 * Создает новый блок-таблицу
	 * @deprecated
	 */
	public function AddTable( $name, $enabled = true, $alias = '', $cols = 5, $templatefile = '', $plaintext = '' ){
		$this->Blocks[$name] = Starkyt::CreateTable($enabled, $alias, $cols, $templatefile, $plaintext);
		$this->GetSBlocks($templatefile);
	}

	/**
	 * Создает новый субблок-ячейку таблицы
	 * @deprecated
	 */
	public function AddTableCell( $name, $enabled = true, $vars = array(), $tempvars = array(), $templatefile = '', $plaintext = '', $colspan = 1, $rowspan = 1, $child = array() ){
		$this->Blocks[$name]['sub'][] = Starkyt::CreateTableCell($enabled, $vars, $tempvars, $templatefile, $plaintext, $colspan, $rowspan, $child);
		$this->GetSBlocks($templatefile);
		foreach($tempvars as $temp){
			$this->GetSBlocks($temp);
		}
		return (count($this->Blocks[$name]['sub']) - 1);
	}

	/**
	 * Добавляет переменную в блок или субблок блока с определенным именем
	 */
	public function SetVar( $block, $varname, $value, $sub_id = 0 ){
		if(isset($this->Blocks[$block])) {
			if($this->Blocks[$block]['type'] == 'poly' || $this->Blocks[$block]['type'] == 'table') {
				$this->Blocks[$block]['sub'][$sub_id]['vars'][$varname] = $value;
			} else {
				$this->Blocks[$block]['vars'][$varname] = $value;
			}
		}
	}

	/**
	 * Добавляет массив переменных в блок или субблок блока с определенным именем
	 * @since 1.3.5
	 */
	public function SetVars( $Block, $Vars, $SubId = 0 ){
		if(isset($this->Blocks[$Block])) {
			if($this->Blocks[$Block]['type'] == 'poly' || $this->Blocks[$Block]['type'] == 'table'){
				$this->Blocks[$Block]['sub'][$SubId]['vars'] = $Vars;
			} else {
				$this->Blocks[$Block]['vars'] = $Vars;
			}
		}
	}

	/**
	 * Добавляет переменную-шаблон в блок или субблок блока с определенным именем
	 */
	public function SetTempVar( $block, $varname, $template_file, $sub_id = 0 ){
		if(isset($this->Blocks[$block])){
			if($this->Blocks[$block]['type'] == 'poly' || $this->Blocks[$block]['type'] == 'table') {
				$this->Blocks[$block]['sub'][$sub_id]['tempvars'][$varname] = $template_file;
			} else {
				$this->Blocks[$block]['tempvars'][$varname] = $template_file;
			}
			$this->GetSBlocks($template_file);
		}
	}

	/**
	 * Добавляет массив переменных-шаблонов в блок или субблок блока с определенным именем
	 * @since 1.3.5
	 */
	public function SetTempVars( $Block, $Vars, $SubId = 0 ){
		if(isset($this->Blocks[$Block])){
			if($this->Blocks[$Block]['type'] == 'poly' || $this->Blocks[$Block]['type'] == 'table') {
				$this->Blocks[$Block]['sub'][$SubId]['tempvars'] = $Vars;
			} else {
				$this->Blocks[$Block]['tempvars'] = $Vars;
			}
			$this->GetSBlocks($template_file);
		}
	}

	/**
	 * Создает новый блок первого уровня и возвращает указатель на его объект
	 * @return StarkytBlock
	 */
	public function NewBlock( $name, $enabled = true, $poly = false, $alias = '', $templatefile = '', $plaintext = '' ){
		$this->Blocks[$name] = Starkyt::CreateBlock($enabled, $poly, $alias, $templatefile, $plaintext);
		$this->GetSBlocks($templatefile);
		return new StarkytBlock($this->Blocks[$name], $this);
	}

	public function Compile(){
		return StarkytCompile($this->TemplateFile, array($this->Blocks), array(array()), 0, $this);
	}

}

class StarkytBlock{

	protected $block;
	protected $starkyt;

	public function __construct( &$block, &$starkyt ){
		$this->block = &$block;
		$this->starkyt = &$starkyt;
	}

	/**
	 * Создает новый дочерний блок и возвращает указатель не его объект
	 * @param  $name
	 * @param bool $enabled
	 * @param bool $poly
	 * @param string $alias
	 * @param string $templatefile
	 * @param string $plaintext
	 * @return StarkytBlock
	 */
	public function NewBlock( $name, $enabled = true, $poly = false, $alias = '', $templatefile = '', $plaintext = '' ){
		$this->block['child'][$name] = Starkyt::CreateBlock($enabled, $poly, $alias, $templatefile, $plaintext, array(), $this);
		$this->starkyt->GetSBlocks($templatefile);
		return new StarkytBlock($this->block['child'][$name], $this->starkyt);
	}

	/**
	 * Добавляет новый субблок и возвращает указатель на его объект
	 * @param bool $enabled
	 * @param array $vars
	 * @param array $tempvars
	 * @param string $templatefile
	 * @param string $plaintext
	 * @return StarkytSubBlock
	 */
	public function NewSubBlock( $enabled = true, $vars = array(), $tempvars = array(), $templatefile = '', $plaintext = '' ){
		$this->block['sub'][] = Starkyt::CreateSubBlock($enabled, $vars, $tempvars, $templatefile, $plaintext, array(), $this);
		$this->starkyt->GetSBlocks($templatefile);
		foreach($tempvars as $temp){
			$this->GetSBlocks($temp);
		}
		return new StarkytSubBlock($this->block['sub'][count($this->block['sub']) - 1], $this->starkyt);
	}

	/**
	 * Добавляет переменную
	 * @param  $Name
	 * @param  $Value
	 * @return StarkytBlock
	 */
	public function SetVar( $Name, $Value ){
		$this->block['vars'][$Name] = $Value;
		return $this;
	}

	/**
	 * Устанавливает переменные блока
	 * @param  $Vars
	 * @return StarkytBlock
	 */
	public function SetVars( $Vars ){
		$this->block['vars'] = $Vars;
		return $this;
	}

	/**
	 * Устанавливает переменную на место коротой будет загружен шаблон
	 * @param  $Name
	 * @param  $Value
	 * @return StarkytBlock
	 */
	public function SetTempVar( $Name, $Value ){
		$this->block['tempvars'][$Name] = $Value;
		return $this;
	}

	/**
	 * Устанавливает переменные-шаблоны блока
	 * @param  $TempVars
	 * @return StarkytBlock
	 */
	public function SetTempVars( $TempVars ){
		$this->block['tempvars'] = $Vars;
		return $this;
	}

	/**
	 * Возвращает указатель на класс родительского объекта
	 * @return StarkytBlock
	 */
	public function Parent(){
		return $this->block['parent'];
	}

	public function &__get( $Name ){
		return $this->block[$Name];
	}

	public function __set( $Name, $Value ){
		$this->block[$Name] = $Value;
	}
}

class StarkytSubBlock{

	protected $block;
	protected $starkyt;

	public function __construct( &$block, &$starkyt ){
		$this->block = &$block;
		$this->starkyt = &$starkyt;
	}

	/**
	 * Добавляет новый дочерний блок в субблок
	 * @param  $name
	 * @param bool $enabled
	 * @param bool $poly
	 * @param string $alias
	 * @param string $templatefile
	 * @param string $plaintext
	 * @return StarkytBlock
	 */
	public function NewBlock( $name, $enabled = true, $poly = false, $alias = '', $templatefile = '', $plaintext = '' ){
		$this->block['child'][$name] = Starkyt::CreateBlock($enabled, $poly, $alias, $templatefile, $plaintext, array(), $this);
		$this->starkyt->GetSBlocks($templatefile);
		return new StarkytBlock($this->block['child'][$name], $this->starkyt);
	}

	/**
	 * Возвращает указатель на класс родительского объекта
	 * @return StarkytBlock
	 */
	public function Parent(){
		return $this->block['parent'];
	}

	public function &__get( $Name ){
		return $this->block[$Name];
	}

	public function __set( $Name, $Value ){
		$this->block[$Name] = $Value;
	}
}

function StarkytCompile( $FileName, $Blocks, $OpenedBlocks, $level, $starkyt ){

	// Защита от рекурсии
	if($level > 64) return '';

	$SBlocks = $starkyt->GetSBlocks($FileName);
	$result = $SBlocks[0];
	$cnt = count($SBlocks);
	$findClose = false;
	$closename = '';
	$name = '';
	$cols = 1;

	for($start = 1; $start < $cnt; $start = $start+2){
		$line = &$SBlocks[$start]; // Анализируемая строка
		if($findClose){ // Поиск закрывающего блока
			if($line === $closename){
				$line = array(STARKYT_END, $Blocks[$level][$name], 0, $name, $findClose, $cols, count($Blocks[$level][$name]['sub']), 0);
				$start -= 2; // Сразу попадаем на "is_array($line)"
				$SBlocks[$findClose][4] = $start; // Чтобы потом не искать конец
				$level++;
				$findClose = false;
			}
			continue;
		}
		if(is_array($line)){ // Обрабатываем итерацию блока или уже преобразованную команду
			switch($line[0]){
				case(STARKYT_END):
					$b = $line[1];
					$i = $line[2];
					if(isset($b['sub'][$i])){
						$sub = $b['sub'][$i];

						if(!$sub['enabled']){
							$line[2]++;
							$start -= 2;
							continue;
						}

						$Blocks[$level] = $Blocks[$level-1];
						$OpenedBlocks[$level] = $OpenedBlocks[$level-1];
						if(isset($b['vars'])){
							$sub['vars'] = $b['vars'];
						}
						if(isset($b['tempvars'])){
							$sub['tempvars'] = $b['tempvars'];
						}
						if(isset($b['plaintext'])){
							$sub['plaintext'] = $b['plaintext'];
						}
						if(isset($b['template'])){
							$sub['template'] = $b['template'];
						}

						$sub['vars']['even'] = ($i % 2 == 0);
						$sub['vars']['odd'] = !$sub['vars']['even'];
						$sub['vars']['rs'] = ($line[7] == 1);
						$sub['vars']['re'] = ($i == $line[6] || $line[7] == $line[5]);

						if($sub['plaintext']){
							$result .= $sub['plaintext'];

							// Устаревшая поддержка таблиц
							if($b['type'] == STARKYT_TABLE && $i != 0){
								$result .= '<!-- КОНЕЦ ЯЧЕЙКИ -->'.$starkyt->TableCellClose."\n";
								if($sub['vars']['re']) $result .= '<!-- КОНЕЦ СТРОКИ -->'."</tr>\n";
								if($i != $line[6]){
									if($sub['vars']['re']) $result .= '<!-- НАЧАЛО СТРОКИ -->'."<tr>\n";
									$tcopen = str_replace('{colspan}', '', $starkyt->TableCellOpen);
									$tcopen = str_replace('{rowspan}', '', $tcopen);
									$result .= '<!-- НАЧАЛО ЯЧЕЙКИ -->'.$tcopen."\n";
								}
							}

							$line[2]++;
							$line[7]++;
							if($line[7] > $line[5]){
								$line[7] = 1;
							}
							$start -= 2;
							continue;
						}

						if(isset($b['child'])){
							$Blocks[$level] = array_merge($Blocks[$level], $b['child']);
						}
						$Blocks[$level] = array_merge($Blocks[$level], $sub['child']);
						$OpenedBlocks[$level][$line[3]] = $sub;
						$OpenedBlocks[$level][$b['alias']] = $sub;

						if($sub['template']){
							$result .= StarkytCompile($sub['template'], $Blocks, $OpenedBlocks, $level, $starkyt);

							// Устаревшая поддержка таблиц
							if($b['type'] == STARKYT_TABLE && $i != 0){
								$result .= '<!-- КОНЕЦ ЯЧЕЙКИ -->'.$starkyt->TableCellClose."\n";
								if($sub['vars']['re']) $result .= '<!-- КОНЕЦ СТРОКИ -->'."</tr>\n";
								if($i != $line[6]){
									if($sub['vars']['re']) $result .= '<!-- НАЧАЛО СТРОКИ -->'."<tr>\n";
									$tcopen = str_replace('{colspan}', '', $starkyt->TableCellOpen);
									$tcopen = str_replace('{rowspan}', '', $tcopen);
									$result .= '<!-- НАЧАЛО ЯЧЕЙКИ -->'.$tcopen."\n";
								}
							}

							$line[2]++;
							$line[7]++;
							if($line[7] > $line[5]){
								$line[7] = 1;
							}
							$start -= 2;
							continue;
						}

						// Устаревшая поддержка таблиц
						if($b['type'] == STARKYT_TABLE && $i != 0){
							$result .= '<!-- КОНЕЦ ЯЧЕЙКИ -->'.$starkyt->TableCellClose."\n";
							if($sub['vars']['re']) $result .= '<!-- КОНЕЦ СТРОКИ '.$line[7].' -->'."</tr>\n";
							if($i != $line[6]){
								if($sub['vars']['re']) $result .= '<!-- НАЧАЛО СТРОКИ -->'."<tr>\n";
								$tcopen = str_replace('{colspan}', '', $starkyt->TableCellOpen);
								$tcopen = str_replace('{rowspan}', '', $tcopen);
								$result .= '<!-- НАЧАЛО ЯЧЕЙКИ -->'.$tcopen."\n";
							}
						}

						$line[2]++;
						$line[7]++; // Колонка таблицы
						if($line[7] > $line[5]){ // Колонок
							$line[7] = 1;
						}
						$start = $line[4];
						$result .= $SBlocks[$start+1];
					}else{ // Конец блока
						$line[2] = 0;
						$line[7] = 1;
						$level--;
						$result .= $SBlocks[$start+1];
						if($b['type'] == STARKYT_TABLE){
							$result .= '<!-- КОНЕЦ ТАБЛИЦЫ -->'.$starkyt->TableCellClose.'</tr>'.$starkyt->TableClose;
						}
					}
					break;
				case(STARKYT_START):

					$SBlocks[$line[4]+2][1] = $Blocks[$level][$line[1]];

					// Устаревшая поддержка таблиц
					if($Blocks[$level][$line[1]]['type'] == STARKYT_TABLE){
						$tcopen = str_replace('{colspan}', '', $starkyt->TableCellOpen);
						$tcopen = str_replace('{rowspan}', '', $tcopen);
						$result .= '<!-- НАЧАЛО ТАБЛИЦЫ -->'.$starkyt->TableOpen.'<tr>'.$tcopen."\n";
					}

					$level++;
					$start = $line[4];
					break;
				case(STARKYT_COND):
					$value = $OpenedBlocks[$level][$line[1]]['vars'][$line[2]];
					if(is_string($value)){
						$value = strlen($value) != 0;
					}else{
						$value = (bool)$value;
					}
					if($line[3]) $value = !$value;
					$level++;
					$start = $line[4];
					$SBlocks[$start+2][1]['sub'][0]['enabled'] = $value;
					break;
				case(STARKYT_SPEED_COND):
					$value = $OpenedBlocks[$level][$line[1]]['vars'][$line[2]];
					if(is_string($value)){
						$value = strlen($value) != 0;
					}else{
						$value = (bool)$value;
					}
					if($line[3]) $value = !$value;
					if($value){
						$result .= $line[4];
					}else{
						$result .= $line[5];
					}
					$result .= $SBlocks[$start+1];
					break;
				case(STARKYT_VAR):
					$result .= $OpenedBlocks[$level][$line[1]]['vars'][$line[2]]
							   .$SBlocks[$start+1];
					break;
				case(STARKYT_TEMPVAR):
					$result .= StarkytCompile($OpenedBlocks[$level][$line[1]]['tempvars'][$line[2]], $Blocks, $OpenedBlocks, $level, $starkyt)
							   .$SBlocks[$start+1];
					break;
				case(STARKYT_TEXT):
					$result .= $line[1].$SBlocks[$start+1];
					break;
			}
			continue;
		}
		if(strpos($line, ':') !== false){ // Условный блок
			$m = explode(':', $line);
			$block_name = $m[0];
			$inv = false;
			if($m[0]{0} === '!'){
				$block_name = substr($m[0], 1);
				$inv = true;
			}
			if(strpos($m[1], '(') !== false){//STARKYT_SPEED_COND
				$m[1] = explode('(', $m[1], 2);
				$var_name = $m[1][0];
				$vals = explode('|', $m[1][1]);
				if(isset($vals[1])){
					$vals[1] = substr($vals[1], 0, -1);
				}else{
					$vals[1] = '';
				}
				if(isset($OpenedBlocks[$level][$block_name]['vars'][$var_name])){
					$value = $OpenedBlocks[$level][$block_name]['vars'][$var_name];
					if(is_string($value)){
						$value = strlen($value) != 0;
					}else{
						$value = (bool)$value;
					}
					if($inv) $value = !$value;
					if($value){
						$result .= $vals[0];
					}else{
						$result .= $vals[1];
					}
					$result .= $SBlocks[$start+1];
					$line = array(STARKYT_SPEED_COND, $block_name, $var_name, $inv, $vals[0], $vals[1]);
					continue;
				}
			}
			if(isset($OpenedBlocks[$level][$block_name]['vars'][$m[1]])){
				$value = $OpenedBlocks[$level][$block_name]['vars'][$m[1]];
				if(is_string($value)){
					$value = strlen($value) != 0;
				}else{
					$value = (bool)$value;
				}
				if($inv) $value = !$value;
				$name = $line;
				$closename = '/'.$block_name.':'.$m[1];
				$findClose = $start;
				$cols = 1;
				$Blocks[$level][$name] = Starkyt::CreateBlock($value);
				$line = array(STARKYT_COND, $block_name, $m[1], $inv, 0, $name);
				continue;
			}
		}
		if(isset($Blocks[$level][$line])){ // Нашли новый блок
			$name = $line;
			$closename = '/'.$line;
			$findClose = $start;
			$cols = 1;

			// Устаревшая поддержка таблиц
			if($Blocks[$level][$line]['type'] == STARKYT_TABLE){
				$cols = $Blocks[$level][$line]['cols'];
				$tcopen = str_replace('{colspan}', '', $starkyt->TableCellOpen);
				$tcopen = str_replace('{rowspan}', '', $tcopen);
				$result .= '<!-- НАЧАЛО ТАБЛИЦЫ -->'.$starkyt->TableOpen.'<tr>'.$tcopen."\n";
			}

			$line = array(STARKYT_START, $name, $closename, $cols);
			continue;
		}
		if(strpos($line, '[') !== false){ // Блок таблица
			$m = explode('[', $line);
			$m[1] = substr($m[1], 0, -1);
			if(isset($Blocks[$level][$m[0]])){
				$name = $m[0];
				$closename = '/'.$m[0];
				$findClose = $start;
				$cols = intval($m[1]);
				$line = array(STARKYT_START, $name, $closename, $cols);
				continue;
			}
		}
		if(strpos($line, '.') !== false){ // Переменная
			$m = explode('.', $line);
			if(isset($OpenedBlocks[$level][$m[0]]['tempvars'][$m[1]])){
				$result .= StarkytCompile($OpenedBlocks[$level][$m[0]]['tempvars'][$m[1]], $Blocks, $OpenedBlocks, $level, $starkyt)
				           .$SBlocks[$start+1];
				$line = array(STARKYT_TEMPVAR, $m[0], $m[1]);
				continue;
			}
			if(isset($OpenedBlocks[$level][$m[0]]['vars'][$m[1]])){
				$result .= $OpenedBlocks[$level][$m[0]]['vars'][$m[1]]
				           .$SBlocks[$start+1];
				$line = array(STARKYT_VAR, $m[0], $m[1]);
				continue;
			}
		}
		$result .= $line.$SBlocks[$start+1];
		$line = array(STARKYT_TEXT, $line);

	}
	return str_replace(array('&#123;', '&#125;'), array('{', '}'), $result);
}

?>