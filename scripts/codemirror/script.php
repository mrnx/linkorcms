<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

/*	Пример:
	-------
	var myCodeMirror = CodeMirror.fromTextArea(document.getElementById("textarea_code"),{
		lineNumbers: true,
		matchBrackets: true,
		mode: "text/x-php",
		indentUnit: 4,
		indentWithTabs: true,
		onChange: function(){
			document.getElementById("textarea_code").value = myCodeMirror.getValue();
		}
	});
 */

System::site()->AddCSSFile('scripts/codemirror/lib/codemirror.css', true);
System::site()->AddJSFile('scripts/codemirror/lib/codemirror.js', true);
// Modes
System::site()->AddJSFile('scripts/codemirror/mode/htmlmixed/htmlmixed.js', true); // text/html
System::site()->AddJSFile('scripts/codemirror/mode/javascript/javascript.js', true); // text/javascript
System::site()->AddJSFile('scripts/codemirror/mode/css/css.js', true); // text/css
System::site()->AddJSFile('scripts/codemirror/mode/clike/clike.js', true); // text/x-csrc, text/x-c++src, text/x-java, text/x-groovy (требуется для text/x-php)
System::site()->AddJSFile('scripts/codemirror/mode/php/php.js', true); // text/x-php
System::site()->AddJSFile('scripts/codemirror/mode/plsql/plsql.js', true); // text/x-plsql