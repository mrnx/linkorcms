<?php

/**
 * Функции использующиеся в панели управления
 */

/**
 * Добавляет строку таблицы для выбора и загрузки изображения
 * @param $Title
 * @param $LoadTitle
 * @param $FileName
 * @param $Dir
 * @param string $Name
 * @param string $LoadName
 * @param string $FormName
 * @return void
 */
function AdminImageControl( $Title, $LoadTitle, $FileName, $Dir, $Name = 'image', $LoadName = 'up_image', $FormName = 'edit_form' ){
	global $site;

	$max_file_size = ini_get('upload_max_filesize');

	$images_data = array();
	$Dir = RealPath2($Dir).'/';

	$images = array();
	$images = GetFiles($Dir,false,true,'.gif.png.jpeg.jpg');
	$images[-1] = 'no_image/no_image.png';
	$site->DataAdd($images_data,$images[-1],'Нет картинки',($FileName == ''));

	$selindex = -1;
	for($i=0,$c=count($images)-1;$i<$c;$i++){
		if($FileName == $images[$i]){
			$sel = true;
			$selindex = $i;
		}else{
			$sel = false;
		}
		$site->DataAdd($images_data,$images[$i],$images[$i],$sel);
	}

	$select = $site->Select($Name,$images_data,false,'onchange="document.'.$FormName.'.iconview.src=\''.$Dir.'\'+document.'.$FormName.'.'.$Name.'.value;"');

	$ctrl = <<<HTML
<table cellspacing="0" cellpadding="0" border="0" width="100%">
	<tr>
		<td valign="top" style="border-bottom:none;">$select</td>
	</tr>
	<tr>
		<td style="border-bottom:none; padding-top: 5px;" width="100%" align="left"><img height="80" id="iconview" src="$Dir{$images[$selindex]}"></td>
	</tr>
</table>
HTML;
	FormRow($Title, $ctrl);
	FormRow($LoadTitle, $site->FFile($LoadName).'<br /><small>Формат изображений только *.jpg,*.jpeg,*.gif,*.png</small><br /><small>Максимальный размер файла: '.$max_file_size.'</small>');
}

/**
 * Вывод адреса электронной почты в админке
 * @param  $email
 * @param string $nik
 * @return string
 * @deprecated
 */
function PrintEmail($email, $nik = ''){
	$email = SafeDB($email, 50, str);
	$nik = SafeDB($nik, 50, str);
	if($email == ''){
		return '&nbsp;';
	} else{
		return '<a href="mailto:'.$email.'">'.$email.'</a>';
	}
}

/**
 * Возвращает имена шаблонов блоков, которые имеет текущий шаблон сайта
 * @return Array
 */
function GetBlockTemplates(){
	global $config, $db;
	$TemplateDir = $config['tpl_dir'].$config['general']['site_template'].'/block/';
	return GetFiles($TemplateDir, false, true, '.html.htm.tpl', true);
}

