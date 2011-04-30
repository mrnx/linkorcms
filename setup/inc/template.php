<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

include ($config['inc_dir'].'page_template.class.php'); //class PageTemplate

class Page extends PageTemplate{

	public function InitPage(){
		global $config;
		$this->InitPageTemplate();
		$this->print_log = false;
		$TemplateDir = $config['s_tpl_dir'];
		$this->SetRoot($TemplateDir);
		$this->AddCSSFile('style.css');
		$this->SetTableTemplate('table/table_open.html', 'table/table_close.html', 'table/table_cell_open.html', 'table/table_cell_close.html');
		$this->SetTempVar('head', 'body', 'theme.html');
		$this->AddBlock('template', true, false, 'page');
		$this->AddBlock('buttons', true, true, 'btn');
		$this->SetVar('template', 'dir', $TemplateDir);
	}

	public function TEcho(){
		global $config, $user;
		$this->EchoAll();
	}
}

$site = new Page();
$site->InitPage();

?>