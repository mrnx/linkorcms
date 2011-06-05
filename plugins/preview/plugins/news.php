<!doctype html>
<html>
<head>
	<link rel="StyleSheet" href="<?=System::config('tpl_dir').System::config('general/site_template');?>/style/textstyles.css" type="text/css" />
	<meta http-equiv="content-type" content="text/html; charset=windows-1251">
	<title>Предпросмотр новости</title>
	<script language="JavaScript">
		function nltobr( str ){
			return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1'+'<br>'+'$2');
		}
	</script>
</head>
<body>
	<script language="JavaScript">
		var f = opener.document.news_editor;
		var sh_text = f.shorttext.value;
		var fl_text = f.continuation.value;
		if(f.auto_br.value == 'on'){
			sh_text = nltobr(sh_text);
			fl_text = nltobr(fl_text);
		}
		document.write('<h1 style="border: 2px #ccc solid;">Короткая статья</h1>');
		document.write(sh_text);
		document.write('<h1 style="border: 2px #ccc solid;">Полная статья</h1>');
		document.write(fl_text);
	</script>
</body>
</html>