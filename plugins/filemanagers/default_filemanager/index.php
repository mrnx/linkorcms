<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

UseScript('elfinder');
$name = System::site()->editfilemanager_name;

System::site()->AddOnLoadJS('$("#openfilemanager_'.$name.'").click(function(){
	var opts = {
		url : \'index.php?name=plugins&p=connectors&mod=elfinder\',
		lang : \'ru\',
		closeOnEditorCallback : true,
		editorCallback : function(url){
			$("#filemanager_'.$name.'").val(url);
		},
		title          : \'My files\',
		width          : 850,
		autoOpen       : true,
		destroyOnClose : true
	};
	$("<div>").dialogelfinder(opts);
});');

System::site()->editfilemanager_html = System::site()->editfilemanager_html.'<img src="modules/filemanager/filemanager.png" id="openfilemanager_'.$name.'" style="cursor: pointer;">';
