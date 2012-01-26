<?php

# LinkorCMS 1.3
# © 2006-2010 Галицкий Александр Николаевич (linkorcms@yandex.ru)
# Файл: page_template.class.php
# Назначение: Общий шаблонизатор


class PageTemplate extends Starkyt{

	public $Disabled = false; // Отключить все модули - генерацию мета тегов и т.д.

// Модуль Head
	public $Doctype = '<!doctype html>';
	// Тег валидатора определяющего версию языка разметки HTML
	public $Title = ''; // Заголовок страницы
	public $Icon = ''; // Иконка для сайта
	// <link rel="shortcut icon" href="favicon.ico">

// Модуль MetaTags
	public $Charset = ''; // Определяет тип содержимого и кодировку
	// <meta http-equiv="content-type" content="text/html; charset=Windows-1251">

	public $Copyright = ''; // Определяет авторские права на страницу
	// <meta name="copyright" content="© 2006 www.yoursite.ru">

	public $ContentLang = ''; // Определяет язык сайта
	// <meta http-equiv="content-language" content="ru">

	public $Rating = 'general'; // Роботы
	// <meta name="rating" content="general">
	// Одно из 4-х значений: 'general', '14 years', 'restricted', 'mature'.

	public $Robots = ''; // <meta name="robots" content="index, follow">
	// <meta name="robots" content="noindex, nofollow">

	public $Generator = ''; // <meta name="generator" content="Cms">
	public $KeyWords = ''; // Ключевые слова (разделять запятой)
	// <meta name="keywords" content="слово,слово,слово">

	public $Description = ''; // Описание
	// <meta name="description" content="Здесь ваше описание">

	public $Author = ''; // Автор страницы/сайта/содержимого
	// <meta name="author" content="Ваше имя">

	public $RevisitAfter = 0; // Периодичность обхода роботами поисковых систем в днях
	// <meta name="revisit-after" content="X days">

	public $OtherMeta = ''; // Дополнительные мета-теги.

// Модуль подключения RSS
	public $RssTitle = ''; // Заголовок RSS-канала
	public $RssLink = ''; // Ссылка на RSS-канал
	// <LINK REL="alternate" TYPE="application/rss+xml" TITLE="title" HREF="link">

// Модуль SEO
	public $SeoTitle = '';
	public $SeoDescription = '';
	public $SeoKeyWords = '';

// Модуль CSS и JavaScript
	protected $css = array(); // Имена файлов css которые следует подключить
	protected $css_inc = array(); // Файлы css встраиваемые в страницу
	protected $js = array(); // Имена файлов JavaScript которые следует подключить
	protected $js_inc = array(); // Файлы js встраиваемые в страницу
	protected $TextJavaScript = ''; // Сюда записывается javaScript который потом вставится в шапку страницы
	protected $JQueryFile = ''; // Имя файла библиотеки JQuery
	protected $JQueryPlugins = array(); // Имена файлов плагинов JQuery и других библиотек
	protected $OnLoadJavaScript = ''; // Скрипты выполняющиеся при загрузке DOM

// Модуль WYSIWYG редактор
	protected $HtmlAreaInit = false;


// Модуль GZip
	protected $GZipCompressPage = false;
	protected $SupportGZip = false;

	/**
	 * Инициализирует шаблон страницы
	 * @param bool $Disabled Если отключен. то вам нужно самостоятельно инициализировать Starkyt : InitStarkyt
	 * @return void
	 */
	public function InitPageTemplate( $Disabled = false ){
		header("Content-Type: text/html");
		header('X-content-type-options: nosniff');
		header("Cache-Control: no-cache");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header('Pragma: no-cache');
		header('Last-Modified: '.gmdate("D, d M Y H:i:s", time()-360).' GMT');
		header('Expires: '.gmdate('D, d M Y H:i:s', time()-120).' GMT');
		if(!$Disabled){
			$this->InitStarkyt(System::config('inc_dir'), 'page.php');
			$this->AddBlock('head');
			$this->Charset = 'windows-1251'; // FIXME: Перенести куда нибудь!
			$this->Generator = CMS_VERSION_STR;
			if(System::config('meta_tags')){
				$this->Author = System::config('meta_tags/author');
				$this->Copyright = System::config('meta_tags/copyright');
				$this->Description = System::config('meta_tags/description');
				$this->KeyWords = System::config('meta_tags/key_words');
				$this->Robots = System::config('meta_tags/robots');
				$this->RevisitAfter = System::config('meta_tags/revisit_after');
				$this->Icon = System::config('meta_tags/favicon');
				$this->OtherMeta = System::config('meta_tags/other_meta');
			}
			$this->Disabled = $Disabled;
		}
	}

	/**
	 * Включает или отключает GZip компрессию данных страницы перед отправкой
	 * @param  $Value
	 * @return void
	 */
	public function SetGZipCompressionEnabled( $Value ){
		if(isset($_SERVER['HTTP_ACCEPT_ENCODING']) && extension_loaded('zlib')){
			$AllowBrowser = (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false);
		}else{
			$AllowBrowser = false;
		}
		$this->GZipCompressPage = $Value;
		$this->SupportGZip = $AllowBrowser && extension_loaded('zlib') && !ini_get('zlib.output_compression');
	}

	/**
	 * Вставляет редактор HTML-контента на страницу, поддерживает плагины
	 * @param string $Name
	 * @param string $Value
	 * @param int $Width
	 * @param int $Height
	 * @return string
	 */
	public function HtmlEditor( $Name, $Value, $Width = 600, $Height = 400 ){
		$this->textarea_name = $Name;
		$this->textarea_html = $this->TextArea($Name, $Value, 'id="'.$Name.'"  rows="15" cols="80" style="width:'.$Width.'px;height:'.$Height.'px;"');
		$this->textarea_width = $Width;
		$this->textarea_height = $Height;
		$this->textarea_value = & $Value;
		if(defined('PLUGINS')){
			IncludePluginsGroup('editors');
		}
		return $this->textarea_html;
	}

	/**
	 * Добавляет поле ввода с возможностью выбрать файл с помощью файлового менеджера
	 * @param string $Name
	 * @param string $Value
	 * @param int $Width
	 * @return string
	 */
	public function FileManager( $Name, $Value, $Width = 400 ){
		$this->editfilemanager_name = $Name;
		$this->editfilemanager_html = $this->Edit($Name, $Value, false, 'id="filemanager_'.$Name.'" style="width:'.$Width.'px;"');
		$this->editfilemanager_width = $Width;
		if(defined('PLUGINS')){
			IncludePluginsGroup('filemanagers');
		}
		return $this->editfilemanager_html;
	}

	/**
	 * Подключает CSS файл к странице
	 * @param  $filename
	 * @param bool $local
	 * @param bool $inc
	 * @return void
	 */
	public function AddCSSFile( $filename, $local = false, $inc = false, $params='' ){
		if(!$local){
			$filename = $this->Root.'style/'.$filename;
		}
		if($inc){
			if(!in_array($filename, $this->css_inc)){
				$this->css_inc[] = $filename;
			}
		}else{
			if(!in_array($filename, $this->css)){
				$this->css[] = array($filename, $params);
			}
		}
	}

	/**
	 * Подключает JavaScript программу к странице
	 * @param  $filename
	 * @param bool $local
	 * @param bool $inc
	 * @return void
	 */
	public function AddJSFile( $filename, $local = false, $inc = false, $params='charset="utf-8"' ){
		if(!$local){
			$filename = $this->Root.'java/'.$filename;
		}
		if($inc){
			if(!in_array($filename, $this->js_inc)){
				$this->js_inc[] = $filename;
			}
		}else{
			if(!in_array($filename, $this->js)){
				$this->js[] = array($filename, $params);
			}
		}
	}

	/**
	 * Добавляет в заголовок страницы произвольный JavaScript код
	 * @param  $JsText
	 * @return void
	 */
	public function AddJS( $JavaScript ){
		$this->TextJavaScript .= "\n".$JavaScript."\n";
	}

	// Добавляет JavaScript код выполняющийся после загрузки DOM документа
	public function AddOnLoadJS( $JavaScript ){
		$this->OnLoadJavaScript .= "\n".$JavaScript."\n";
	}

	/**
	 * Устанавливает имя файла библиотеки JQuery.
	 * Если передать пустое значение то Jquery и ее библиотеки не будет подключены.
	 * @param <type> $FileName
	 */
	public function JQuery( $FileName = '' ){
		$this->JQueryFile = $FileName;
	}

	/**
	 * Подключает плагин JQuery
	 * @param string $FileName
	 * @param <type> $local
	 */
	public function JQueryPlugin( $FileName, $local = true, $params='charset="utf-8"' ){
		if(!$local){
			$FileName = $this->Root.'java/'.$FileName;
		}
		if(!in_array($FileName, $this->JQueryPlugins)){
			$this->JQueryPlugins[] = array($FileName, $params);
		}
	}

	/**
	 * Устанавливает уникальный заголовок страницы и мета теги
	 * @param  $Title
	 * @param  $KeyWords
	 * @param  $Description
	 * @return void
	 */
	public function Seo( $Title, $KeyWords, $Description ){
		$this->SeoTitle = $Title;
		$this->SeoKeyWords = $KeyWords;
		$this->SeoDescription = $Description;
	}

	/**
	 * Устанавливает заголовок страницы
	 * @param  $Title
	 * @return void
	 */
	public function SetTitle( $Title ){
		$this->Title = $Title;
	}

	/**
	 * Генерирует метатеги для текущей страницы и возвращает результат
	 * @return string
	 */
	public function GenerateMetaTags(){
		$Meta = '';
		if($this->Charset != ''){
			$Meta .= '<meta http-equiv="content-type" content="text/html; charset='.$this->Charset.'" />'."\n";
		}
		if($this->Copyright != ''){
			$Meta .= '<meta name="copyright" content="'.$this->Copyright.'" />'."\n";
		}
		if($this->ContentLang != ''){
			$Meta .= '<meta http-equiv="content-language" content="'.$this->ContentLang.'" />'."\n";
		}
		if($this->Rating != ''){
			$Meta .= '<meta name="rating" content="'.$this->Rating.'" />'."\n";
		}
		if($this->Robots != ''){
			$Meta .= '<meta name="robots" content="'.$this->Robots.'" />'."\n";
		}
		if($this->Generator != ''){
			$Meta .= '<meta name="generator" content="'.$this->Generator.'" />'."\n";
		}
		if($this->SeoKeyWords != ''){
			$Meta .= '<meta name="keywords" content="'.$this->SeoKeyWords.'" />'."\n";
		}elseif($this->KeyWords != ''){
			$Meta .= '<meta name="keywords" content="'.$this->KeyWords.'" />'."\n";
		}
		if($this->SeoDescription != ''){
			$Meta .= '<meta name="description" content="'.$this->SeoDescription.'" />'."\n";
		}elseif($this->Description != ''){
			$Meta .= '<meta name="description" content="'.$this->Description.'" />'."\n";
		}
		if($this->Author != ''){
			$Meta .= '<meta name="author" content="'.$this->Author.'" />'."\n";
		}
		if($this->RevisitAfter != 0){
			$Meta .= '<meta name="revisit-after" content="'.$this->RevisitAfter.' days" />'."\n";
		}
		if($this->Icon != ''){
			$Meta .= '<link rel="shortcut icon" href="'.$this->Icon.'" />'."\n";
		}
		if($this->RssTitle != '' && $this->RssLink != ''){
			$Meta .= '<link rel="alternate" type="application/rss+xml" title="'.$this->RssTitle.'" href="'.$this->RssLink.'" />'."\n";
		}
		$Meta .= $this->OtherMeta."\n";
		return $Meta;
	}

	/**
	 * Генерирует заголовок страницы и возвращает результат
	 * @return string
	 */
	public function GenerateHead(){
		$Head = '';
		$Head .= '<base href="'.GetSiteUrl().'" />'."\n";
		//Подключаем таблицы стилей
		foreach($this->css as $css){
			$Head .= '<link rel="StyleSheet" href="'.$css[0].'" type="text/css" '.$css[1].' />'."\n";
		}
		foreach($this->css_inc as $filename){
			if(file_exists($filename)){
				$Head .= "<style>\n".file_get_contents($filename)."\n</style>\n";
			}
		}
		// Подключаем JQuery и плагины
		if($this->JQueryFile != ''){
			$Head .= '<script src="'.$this->JQueryFile.'" type="text/javascript"></script>'."\n";
			foreach($this->JQueryPlugins as $js){
				$Head .= '<script src="'.$js[0].'" type="text/javascript" '.$js[1].'></script>'."\n";
			}
		}
		//Подключаем JavaScript
		foreach($this->js as $js){
			$Head .= '<script src="'.$js[0].'" type="text/javascript" '.$js[1].'></script>'."\n";
		}
		foreach($this->js_inc as $filename){
			if(file_exists($filename)){
				$this->TextJavaScript .= "\n".file_get_contents($filename)."\n";
			}
		}
		// JavaScript
		$JSInline = '';
		if($this->JQueryFile != ''){
			$JSInline .= "jQuery(function(){".$this->OnLoadJavaScript."});\n";
		}else{
			$JSInline .= "window.onload = function(){".$this->OnLoadJavaScript."};\n";
		}
		$JSInline .= $this->TextJavaScript;
		if($JSInline != ''){
			$Head .= "<script type=\"text/javascript\">\n".$JSInline."\n</script>\n";
		}
		return $Head;
	}

	/**
	 * Генерирует заголовок страницы и возвращает результат
	 * @return string
	 */
	public function GenerateTitle(){
		if(defined('INDEX_PHP') && INDEX_PHP == true){
			$title = System::config('general/site_name').(System::config('general/main_title') != '' ? ' - '.System::config('general/main_title') : '');
		}elseif($this->SeoTitle != ''){
			$title = $this->SeoTitle.' - '.System::config('general/site_name');
		}else{
			$title = ($this->Title != '' ? $this->Title.' - ' : '').System::config('general/site_name');
		}
		return $title;
	}

	/**
	 * Устанавливает переменные страницы
	 * @return void
	 */
	protected function SetPage(){
		$this->SetVar('head', 'doctype', $this->Doctype);
		$this->SetVar('head', 'title', $this->GenerateTitle());
		$this->SetVar('head', 'meta', $this->GenerateMetaTags());
		$this->SetVar('head', 'text', $this->GenerateHead());
	}

	public function GetPageInfo( $CompileStartTime ){
		if(!defined('SETUP_SCRIPT') && System::config('general/show_script_time')){
			$end = GetMicroTime();
			$end_time = GetMicroTime();
			$end_time = $end_time - SCRIPT_START_TIME;
			$php_time = $end_time - System::database()->QueryTotalTime;
			$persent = 100 / $end_time;
			$memory = memory_get_peak_usage(true);
			$MB = $memory / 1024 / 1024;
			$info = 'Страница сгенерирована за '.sprintf("%01.4f", $end_time).' сек. Шаблонизатор: '.sprintf("%01.4f", $end - $CompileStartTime).' сек. Инициализация ядра: '.sprintf("%01.4f", INIT_CORE_END - INIT_CORE_START).' сек.<br>'
					.'Память: '.sprintf("%01.2f", $MB).'М./'.get_cfg_var('memory_limit').'. '
			        .'БД: '.System::database()->NumQueries.' запросов за '.sprintf("%01.4f", System::database()->QueryTotalTime).' сек. ( PHP: '.round($persent * $php_time).'% БД: '.round($persent * System::database()->QueryTotalTime).'% )';
		}else{
			$info = '';
		}
		return $info;
	}

	/**
	 * Компилирует шаблоны и выводит результат в браузер
	 * @return void
	 */
	public function EchoAll(){
		if(!$this->Disabled){
			$this->SetPage();
		}
		$start = microtime(true);
		$contents = $this->Compile(); // Компиляция всей страницы
		if(ob_get_level() > 0 && ob_get_length() > 0){
			$contents = ob_get_clean().$contents;
		}
		$contents = str_replace('%info%', $this->GetPageInfo($start), $contents);
		if($this->GZipCompressPage && $this->SupportGZip){
			@Header('Content-Encoding: gzip');
			ob_start('ob_gzhandler');
			echo $contents;
			ob_end_flush();
		}else{
			echo $contents;
		}
	}

}
