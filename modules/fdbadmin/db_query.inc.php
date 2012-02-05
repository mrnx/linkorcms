<?php

System::admin()->AddCenterBox('Выполнить SQL');

if(System::database()->Name != 'MySQL'){
	System::admin()->HighlightError('Только базы данных с поддержкой SQL.');
	return;
}

UseScript('jquery', 'codemirror');

System::admin()->AddOnLoadJS('myCodeMirror = CodeMirror.fromTextArea(document.getElementById("sqlcode"),{
	lineNumbers: true,
	matchBrackets: true,
	mode: "text/x-plsql",
	indentUnit: 4,
	indentWithTabs: true,
	onChange: function(){
		document.getElementById("sqlcode").value = myCodeMirror.getValue();
	}
});');
System::admin()->AddJS('
PerformSqlCode = function(){
	var label = $("#perform").button("option", "label");
	$("#perform").button("option", "label", "Запуск <img src=\"images/ajax-loader.gif\">");
	$("#result_container").hide();
	$("#perform_result").html("");
	$("#perform_result").hide();
	$.ajax({
		type: "POST",
		url: "'.ADMIN_FILE.'?exe=fdbadmin&a=performsql",
		data: {code: $("#sqlcode").val()},
		success: function(data){
			$("#perform").button("option", "label", label);
			$("#perform_result").html("<pre>"+data+"</pre>");
			$("#result_container").show();
			$("#perform_result").slideDown();
		}
	});
};');

$html = <<<HTML
<div>
	<div style="width: 800px;">
		<textarea id="sqlcode" style="width: 791px; height: 200px;">SELECT * FROM `table_news` WHERE `enabled`=1 ORDER BY `date`;</textarea>
	</div>
	<div style="margin: 2px 0; width: 800px; text-align: right;">
		<a href="#" id="perform" class="button" onclick="PerformSqlCode(); return false;" title="Отправить код на выполнение и вывести результат">Запуск<img src="images/arrow_blue_right.png" /></a>
	</div>
	<div id="result_container" style="margin: 8px 0; width: 794px; background-color: #EEE; display: none; text-align: left; border: 3px #DDD solid; border-radius: 3px;-moz-border-radius: 3px;">
		<div id="perform_result" style="display: none; padding: 5px; overflow-x: auto;"></div>
	</div>
</div>
HTML;


System::admin()->AddText($html);