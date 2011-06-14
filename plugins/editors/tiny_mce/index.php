<?php

	if(!defined('VALID_RUN')){
		header("HTTP/1.1 404 Not Found");
		exit;
	}

	UseScript('tinymce');

	$textarea_name = System::site()->textarea_name;
	$options = array(
		'script_url' => $GLOBALS['TinyMceScriptUrl'],
		'language' => 'ru',
		'theme' => System::plug_config('editors.tiny_mce/theme'),
		'skin' => 'o2k7',
		'skin_variant' => 'black',
		'plugins' => 'autolink,lists,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,advlist,images',
		'theme_advanced_toolbar_location' => 'top',
		'theme_advanced_toolbar_align' => 'left',
		'theme_advanced_statusbar_location' => 'bottom',
		'theme_advanced_resizing' => true,
		'content_css' => System::config('tpl_dir').System::config('general/site_template').'/style/textstyles.css',
		'force_p_newlines' => true,
		'forced_root_block' => '',
		'relative_urls' => false,
		'remove_script_host' => true,
	);

	$buttons = array();
	$buttons[] = System::plug_config('editors.tiny_mce/theme_advanced_buttons1');
	$buttons[] = System::plug_config('editors.tiny_mce/theme_advanced_buttons2');
	$buttons[] = System::plug_config('editors.tiny_mce/theme_advanced_buttons3');
	$buttons[] = System::plug_config('editors.tiny_mce/theme_advanced_buttons4');

	$toolbar = 0;
	foreach($buttons as $panels){
		if($panels != ''){
			$toolbar++;
			$options['theme_advanced_buttons'.$toolbar] = $panels;
		}
	}

	System::site()->AddOnLoadJS('$("#'.$textarea_name.'").tinymce('.JsonEncode($options).');');

	System::site()->textarea_html = System::site()->TextArea(
		$textarea_name,
		System::site()->textarea_value,
		'id="'.$textarea_name.'" rows="15" cols="80" style="width:'.System::site()->textarea_width.'px;height:'.System::site()->textarea_height.'px;"'
	);

?>