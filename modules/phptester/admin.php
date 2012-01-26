<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

System::admin()->AddSubTitle('Тестирование PHP кода');

if(isset($_GET['a'])){
	$action = $_GET['a'];
}else{
	if(!isset($_GET['lang']) || $_GET['lang'] == 'php'){
		$action = 'mainphp';
	}else{
		$action = 'mainjs';
	}
}

System::admin()->SideBarAddMenuItem('PHP', 'exe=phptester&lang=php', 'mainphp');
System::admin()->SideBarAddMenuItem('Сниппеты PHP', 'exe=phptester&a=phpsnippets', 'phpsnippets');
System::admin()->SideBarAddMenuItem('JavaScript', 'exe=phptester&lang=js', 'mainjs');
System::admin()->SideBarAddMenuItem('Сниппеты JS', 'exe=phptester&a=jssnippets', 'jssnippets');
System::admin()->SideBarAddMenuBlock('', $action);

switch($action){
	case 'mainphp':
		AdminPhpTester();
		break;
	case 'mainjs':
		AdminJsTester();
		break;
	case 'perform':
		AdminPhpTesterPerform();
		break;
	case 'phpsnippets':
		AdminPhpTesterSnippets("php");
		break;
	case 'jssnippets':
		AdminPhpTesterSnippets("js");
		break;
	case 'add':
	case 'save':
		AdminPhpTesterSave($action);
		break;
	case 'delete':
		AdminPhpTesterDelete();
		break;
}

function AdminPhpTester(){
	UseScript('jquery', 'codemirror');

	$code = '';
	$title = '';
	if(isset($_GET['id'])){
		$snippet_id = SafeDB($_GET['id'], 11, int);
		System::database()->Select('snippets', "`id`='$snippet_id'");
		$s = System::database()->FetchRow();
		$code = SafeDB($s['code'], 0, str);
		$title = SafeDB($s['title'], 255, str);
	}else{
		$snippet_id = '0';
	}

	System::admin()->AddOnLoadJS('myCodeMirror = CodeMirror.fromTextArea(document.getElementById("test_code"),{
		lineNumbers: true,
		matchBrackets: true,
		mode: "text/x-php",
		indentUnit: 4,
		indentWithTabs: true,
		onChange: function(){
			document.getElementById("test_code").value = myCodeMirror.getValue();
		}
	});');
	System::admin()->AddJS('
window.snippet_id = "'.$snippet_id.'";
PerformPhpCode = function(){
	$("#perform").button("option", "label", "Выполнить код <img src=\"images/ajax-loader.gif\">");
	$("#result_container").hide();
	$("#perform_result").html("");
	$("#perform_result").hide();
	$.ajax({
		type: "POST",
		url: "'.ADMIN_FILE.'?exe=phptester&a=perform",
		data: {code: $("#test_code").val(), id: window.snippet_id},
		success: function(data){
			$("#perform").button("option", "label", "Выполнить код");
			$("#perform_result").html("<pre>"+data+"</pre>");
			$("#result_container").show();
			$("#perform_result").slideDown();
		}
	});
};
SavePhpCode = function(met){
	if($("#test_title").val() == ""){
	  alert("Введите короткое описание сниппета.");
	  return;
	}
	var label = $("#"+met).button("option", "label");
	$("#"+met).button("option", "label", label+" <img src=\"images/ajax-loader.gif\">");
	$.ajax({
		type: "POST",
		url: "'.ADMIN_FILE.'?exe=phptester&a="+met,
		dataType: "json",
		data: {code: $("#test_code").val(), title: $("#test_title").val(), id: window.snippet_id, type: "php"},
		success: function(data){
			window.snippet_id = data.id;
			$("#"+met).button("option", "label", label);
		}
	});
};
');

	$html = <<<HTML
<div>
	<div style="width: 800px;">
		<textarea id="test_code" style="width: 791px; height: 200px;">$code</textarea>
		<div style="width: 72px; float: left; line-height: 25px; padding-left: 2px;"><strong>Описание</strong></div>
		<input type="text" id="test_title" style="width: 717px;" value="$title">
	</div>
	<div style="width: 800px; text-align: right;">
		<a href="#" id="add" class="button" onclick="SavePhpCode('add'); return false;" title="Добавить сниппет как новый">Добавить</a>
		<a href="#" id="save" class="button" onclick="SavePhpCode('save'); return false;" title="Добавить новый снипет или сохранить редактируемый">Сохранить</a>
		<a href="#" id="perform" class="button" onclick="PerformPhpCode(); return false;" title="Отправить код на выполнение и вывести результат">Выполнить код</a>
	</div>
	<div id="result_container" style="margin: 10px 0; width: 794px; background-color: #EEE; display: none; text-align: left; border: 3px #DDD solid; border-radius: 3px;-moz-border-radius: 3px;">
		<div id="perform_result" style="display: none; padding: 5px; overflow-x: auto;"></div>
	</div>
</div>
HTML;

	System::admin()->AddTextBox('Тестирование PHP', $html);
}

function AdminJsTester(){
	UseScript('jquery', 'codemirror');

	$code = '';
	$title = '';
	if(isset($_GET['id'])){
		$snippet_id = SafeDB($_GET['id'], 11, int);
		System::database()->Select('snippets', "`id`='$snippet_id'");
		$s = System::database()->FetchRow();
		$code = SafeDB($s['code'], 0, str);
		$title = SafeDB($s['title'], 255, str);
	}else{
		$snippet_id = '0';
	}

	System::admin()->AddOnLoadJS('myCodeMirror = CodeMirror.fromTextArea(document.getElementById("test_code"),{
		lineNumbers: true,
		matchBrackets: true,
		mode: "javascript",
		indentUnit: 4,
		indentWithTabs: true,
		onChange: function(){
			document.getElementById("test_code").value = myCodeMirror.getValue();
		}
	});');
	System::admin()->AddJS('
window.snippet_id = "'.$snippet_id.'";
PerformJsCode = function(){
	$("#perform").button("option", "label", "Выполнить код <img src=\"images/ajax-loader.gif\">");
	eval($("#test_code").val());
	$("#perform").button("option", "label", "Выполнить код");
};
SaveJSCode = function(met){
	if($("#test_title").val() == ""){
	  alert("Введите короткое описание сниппета.");
	  return;
	}
	var label = $("#"+met).button("option", "label");
	$("#"+met).button("option", "label", label+" <img src=\"images/ajax-loader.gif\">");
	$.ajax({
		type: "POST",
		url: "'.ADMIN_FILE.'?exe=phptester&a="+met,
		dataType: "json",
		data: {code: $("#test_code").val(), title: $("#test_title").val(), id: window.snippet_id, type: "js"},
		success: function(data){
			window.snippet_id = data.id;
			$("#"+met).button("option", "label", label);
		}
	});
};
');

	$html = <<<HTML
<div>
	<div style="width: 800px;">
		<textarea id="test_code" style="width: 791px; height: 200px;">$code</textarea>
		<div style="width: 72px; float: left; line-height: 25px; padding-left: 2px;"><strong>Описание</strong></div>
		<input type="text" id="test_title" style="width: 717px;" value="$title">
	</div>
	<div style="width: 800px; text-align: right;">
		<a href="#" id="add" class="button" onclick="SaveJSCode('add'); return false;" title="Добавить сниппет как новый">Добавить</a>
		<a href="#" id="save" class="button" onclick="SaveJSCode('save'); return false;" title="Добавить новый снипет или сохранить редактируемый">Сохранить</a>
		<a href="#" id="perform" class="button" onclick="PerformJsCode(); return false;" title="Выполнить код">Выполнить код</a>
	</div>
</div>
HTML;

	System::admin()->AddTextBox('Тестирование JS', $html);
}

function AdminPhpTesterPerform(){
	ob_start();
	$test = eval(Utf8ToCp1251($_POST['code']));
	$source = ob_get_clean();
	if($source == ''){
		ob_start();
		print_r($test);
		$source = ob_get_clean();
	}
	echo htmlspecialchars($source);
	exit();
}

function AdminPhpTesterSnippets( $type ){
	System::admin()->AddSubTitle('Сниппеты');
	UseScript('jquery_ui_table');

	if(isset($_REQUEST['onpage'])){
		$num = intval($_REQUEST['onpage']);
	}else{
		$num = 20;
	}
	if(isset($_REQUEST['page'])){
		$page = intval($_REQUEST['page']);
	}else{
		$page = 1;
	}

	$snippets_db = System::database()->Select('snippets', "`type`='$type'");
	$columns = array('title');
	$sortby = '';
	$sortbyid = -1;
	$desc = true;
	if(isset($_REQUEST['sortby'])){
		$sortby = $columns[$_REQUEST['sortby']];
		$sortbyid = intval($_REQUEST['sortby']);
		$desc = $_REQUEST['desc'] == '1';
	}
	if($sortby != ''){
		SortArray($snippets_db, $sortby, $desc);
	}

	$table = new jQueryUiTable();
	$table->listing = ADMIN_FILE.'?exe=phptester&a='.$type.'snippets&ajax';
	$table->del = ADMIN_FILE.'?exe=phptester&a=delete';
	$table->total = count($snippets_db);
	$table->onpage = $num;
	$table->page = $page;
	$table->sortby = $sortbyid;
	$table->sortdesc = $desc;

	$table->AddColumn('Заголовок');
	$table->AddColumn('Функции', 'center', false, true);

	$snippets_db = ArrayPage($snippets_db, $num, $page); // Берем только новости с текущей страницы
	foreach($snippets_db as $snip){
		$id = SafeDB($snip['id'], 11, int);
		$editlink = ADMIN_FILE.'?exe=phptester&id='.$id.'&lang='.$type;

		$func = '';
		$func .= System::admin()->SpeedButton('Редактировать', $editlink, 'images/admin/edit.png');
		$func .= System::admin()->SpeedConfirmJs(
			'Удалить',
			'$(\'#jqueryuitable\').table(\'deleteRow\', '.$id.');',
			'images/admin/delete.png',
			'Уверены, что хотите удалить этот сниппет?'
		);

		$table->AddRow(
			$id,
			'<b><a href="'.$editlink.'">'.SafeDB($snip['title'], 255, str).'</a></b>',
			$func
		);
	}
	if(isset($_GET['ajax'])){
		echo $table->GetOptions();
		exit;
	}else{
		System::admin()->AddTextBox('Сниппеты', $table->GetHtml());
	}
}

function AdminPhpTesterSave($action){
	$snippet = SafeR('title,type', 255, str) + SafeR('code', 0, str);
	ObjectUtf8ToCp1251($snippet);
	if($action == 'save'){ // Редактирование
		$id = SafeEnv($_POST['id'], 11, int);
		System::database()->Update('snippets', MakeSet($snippet), "`id`='$id'");
	}else{
		System::database()->Insert('snippets', MakeValues("'','title','code','type'", $snippet));
	}
	echo JsonEncode(array('id'=>System::database()->GetLastId()));
	exit();
}

function AdminPhpTesterDelete(){
	System::database()->Delete('snippets', "`id`='".SafeEnv($_REQUEST['id'], 11, int)."'");
	exit('OK');
}
