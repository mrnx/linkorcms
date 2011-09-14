<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

System::admin()->AddSubTitle('������������ PHP ����');

if(isset($_GET['a'])){
	$action = $_GET['a'];
}else{
	$action = 'mainphp';
}

System::admin()->SideBarAddMenuItem('PHP', 'exe=phptester&lang=php', 'mainphp');
System::admin()->SideBarAddMenuItem('JavaScript', 'exe=phptester&lang=js', 'mainjs');
System::admin()->SideBarAddMenuItem('��������', 'exe=phptester&a=snippets', 'snippets');
System::admin()->SideBarAddMenuBlock('', $action);

switch($action){
	case 'mainphp':
		AdminPhpTester();
		break;
	case 'perform':
		AdminPhpTesterPerform();
		break;
	case 'snippets':
		AdminPhpTesterSnippets();
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
	UseScript('jquery');

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

	System::admin()->AddJS('
window.snippet_id = "'.$snippet_id.'";
function PerformPhpCode(){
	$("#perform").button("option", "label", "��������� ��� <img src=\"images/ajax-loader.gif\">");
	$("#result_container").hide();
	$("#perform_result").html("");
	$("#perform_result").hide();
	$.ajax({
		type: "POST",
		url: "'.ADMIN_FILE.'?exe=phptester&a=perform",
		data: {code: $("#test_code").val(), id: window.snippet_id},
		success: function(data){
			$("#perform").button("option", "label", "��������� ���");
			$("#perform_result").html("<pre>"+data+"</pre>");
			$("#result_container").show();
			$("#perform_result").slideDown();
		}
	});
}
function SavePhpCode(met){
	if($("#test_title").val() == ""){
	  alert("������� �������� �������� ��������.");
	  return;
	}
	var label = $("#"+met).button("option", "label");
	$("#"+met).button("option", "label", label+" <img src=\"images/ajax-loader.gif\">");
	$.ajax({
		type: "POST",
		url: "'.ADMIN_FILE.'?exe=phptester&a="+met,
		dataType: "json",
		data: {code: $("#test_code").val(), title: $("#test_title").val(), id: window.snippet_id},
		success: function(data){
			window.snippet_id = data.id;
			$("#"+met).button("option", "label", label);
		}
	});
}
');

	$html = <<<HTML
<div>
	<div style="width: 800px;">
		<textarea id="test_code" style="width: 791px; height: 200px;">$code</textarea>
		<div style="width: 72px; float: left; line-height: 25px; padding-left: 2px;"><strong>��������</strong></div>
		<input type="text" id="test_title" style="width: 717px;" value="$title">
	</div>
	<div style="width: 800px; text-align: right;">
		<a href="#" id="add" class="button" onclick="SavePhpCode('add'); return false;" title="�������� ������� ��� �����">��������</a>
		<a href="#" id="save" class="button" onclick="SavePhpCode('save'); return false;" title="�������� ����� ������ ��� ��������� �������������">���������</a>
		<a href="#" id="perform" class="button" onclick="PerformPhpCode(); return false;" title="��������� ��� �� ���������� � ������� ���������">��������� ���</a>
	</div>
	<div id="result_container" style="margin: 10px 0; width: 794px; background-color: #EEE; display: none; text-align: left; border: 3px #DDD solid; border-radius: 3px;-moz-border-radius: 3px;">
		<div id="perform_result" style="display: none; padding: 5px; overflow-x: auto;"></div>
	</div>
</div>
HTML;

	System::admin()->AddTextBox('������������ PHP ����', $html);
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

function AdminPhpTesterSnippets(){
	System::admin()->AddSubTitle('��������');
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

	$snippets_db = System::database()->Select('snippets');
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
	$table->listing = ADMIN_FILE.'?exe=phptester&a=snippets&ajax';
	$table->del = ADMIN_FILE.'?exe=phptester&a=delete';
	$table->total = count($snippets_db);
	$table->onpage = $num;
	$table->page = $page;
	$table->sortby = $sortbyid;
	$table->sortdesc = $desc;

	$table->AddColumn('���������');
	$table->AddColumn('�������', 'center', false, true);

	$snippets_db = ArrayPage($snippets_db, $num, $page); // ����� ������ ������� � ������� ��������
	foreach($snippets_db as $snip){
		$id = SafeDB($snip['id'], 11, int);
		$editlink = ADMIN_FILE.'?exe=phptester&id='.$id;

		$func = '';
		$func .= System::admin()->SpeedButton('�������������', $editlink, 'images/admin/edit.png');
		$func .= System::admin()->SpeedConfirmJs(
			'�������',
			'$(\'#jqueryuitable\').table(\'deleteRow\', '.$id.');',
			'images/admin/delete.png',
			'�������, ��� ������ ������� ���� �������?'
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
		System::admin()->AddTextBox('��������', $table->GetHtml());
	}
}

function AdminPhpTesterSave($action){
	$snippet = SafeR('title', 255, str) + SafeR('code', 0, str);
	ObjectUtf8ToCp1251($snippet);
	if($action == 'save'){ // ��������������
		$id = SafeEnv($_POST['id'], 11, int);
		System::database()->Update('snippets', MakeSet($snippet), "`id`='$id'");
	}else{
		System::database()->Insert('snippets', MakeValues("'','title','code'", $snippet));
	}
	echo JsonEncode(array('id'=>System::database()->GetLastId()));
	exit();
}

function AdminPhpTesterDelete(){
	System::database()->Delete('snippets', "`id`='".SafeEnv($_REQUEST['id'], 11, int)."'");
	exit('OK');
}
