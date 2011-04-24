<?php

# LinkorCMS
# � 2006-2008 �������� ��������� ���������� (linkorcms@yandex.ru)
# ����: setup.class.php
# ����������: ����� ��� ���������� �������������

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

class Setup
{
	public $block = 'template';
	public $NextPage = '';
	public $PrevPage = '';
	public $form_open = false;
	public $form_name = 'setup_form';

	public function Setup()
	{
		global $site, $config;
		$config['general']['site_name'] = '��������� LinkorCMS';
		$config['general']['main_title'] = '';
		$site->SetVar($this->block, 'content', '&nbsp;');
		$site->SetVar($this->block, 'help', false);
		$site->SetVar($this->block, 'form', false);
	}

	public function SetTitle( $title )
	{
		global $site;
		$site->Title = $title;
		$site->SetVar($this->block, 'title', $title);
	}

	public function SetNextPage( $PageName )
	{
		$this->NextPage = $PageName;
	}

	public function SetPrevPage( $PageName )
	{
		$$this->PrevPage = $PageName;
	}

	// ������������� ����� ��������
	public function SetContent( $text )
	{
		global $site;
		$site->SetVar($this->block, 'content', $text);
	}

	public function SetHelp( $text = false )
	{
		global $site;
		$site->SetVar($this->block, 'help', $text);
	}

	//��������� �����
	public function OpenForm( $goModName, $multipart = false )
	{
		global $site;
		$form = '<form name="'.$this->form_name.'" action="setup.php?mod='.$goModName.'" method="post"'
			.($multipart ? ' enctype="multipart/form-data"' : '').'>';
		$site->SetVar($this->block, 'form', $form);
		$this->form_open = true;
	}

	// ��������� ������ ����������� ������� �����
	public function AddSubmitButton( $caption )
	{
		global $site;
		$text = $site->Button($caption, 'class="button" onClick="javascript:'.$this->form_name.'.submit()"');
		$site->AddSubBlock('buttons', true, array('html'=>$text));
	}

	// ��������� ������
	public function AddButton( $caption, $goModName = '' )
	{
		global $site;
		$text = $site->Button($caption, 'class="button" onClick="javascript:SetLocation(\'setup.php?mod='.$goModName.'\')"');
		$site->AddSubBlock('buttons', true, array('html'=>$text));
	}

	public function SEcho()
	{
		global $site;
		$site->TEcho();
	}

	// ��������� ������ ��������
	public function Page( $page = '' )
	{
		global $config;
		if($page != '' && file_exists($config['s_mod_dir'].RealPath2($page).'/index.php')){
			include($config['s_mod_dir'].RealPath2($page).'/index.php');
		}else{
			include($config['s_mod_dir'].'main/index.php');
		}
		$this->SEcho();
	}
}

$setup = new Setup();

?>