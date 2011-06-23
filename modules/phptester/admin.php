<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

TAddSubTitle('������������ PHP ����');

if(isset($_GET['a'])){
	$action = $_GET['a'];
}else{
	$action = 'main';
}

switch($action){
	case 'main':
		AdminPhpTester();
		break;
	case 'perform':
		AdminPhpTesterPerform();
		break;

}

function AdminPhpTester(){

	System::admin()->AddJS('
function PerformPhpCode(){
	$("#perform").button("option", "label", "��������� ��� <img src=\"images/ajax-loader.gif\">");
	$("#result_container").hide();
	$("#perform_result").html("");
	$("#perform_result").hide();
	$.ajax({
		type: "POST",
		url: "'.ADMIN_FILE.'?exe=phptester&a=perform",
		data: {code: $("#phpcode").val()},
		success: function(data){
			$("#perform").button("option", "label", "��������� ���");
			$("#perform_result").html("<pre>"+data+"</pre>");
			$("#result_container").show();
			$("#perform_result").slideDown();
		}
	});
}');

	$html = <<<HTML
<div>
	<div style="width: 800px; ">
		<textarea id="phpcode" style="width: 791px; height: 200px;">echo Translit4Url('����');</textarea>
	</div>
	<div style="width: 800px; text-align: right;">
		<a href="#" id="perform" class="button" onclick="PerformPhpCode(); return false;">��������� ���</a>
	</div>
	<div id="result_container" style="margin: 10px 0; width: 794px; background-color: #EEE; display: none; text-align: left; border: 3px #DDD solid;
		border-radius: 3px;-moz-border-radius: 3px;">
		<div id="perform_result" style="display: none; padding: 5px; overflow-x: auto;"></div>
	</div>
</div>
HTML;

 System::admin()->AddTextBox('������������ PHP ����', $html);
}

function AdminPhpTesterPerform(){
	ob_start();
	eval(Utf8ToCp1251($_POST['code']));
	$source = ob_get_clean();
	echo htmlspecialchars($source);
	exit();
}

?>