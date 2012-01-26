<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

UseScript('jquery_ui', 'jquery_popup', 'jquery_ui_nestedSortable');

System::site()->JQueryPlugin('scripts/jquery_ui_treeview/jquery.ui.treeview.js', true);
System::site()->AddCSSFile('scripts/jquery_ui_treeview/theme/jquery.ui.treeview.css', true);
