<!doctype html>
<html>
<head>
	<link rel="StyleSheet" href="<?=System::config('tpl_dir').System::config('general/site_template');?>/style/textstyles.css" type="text/css" />
	<meta http-equiv="content-type" content="text/html; charset=windows-1251">
	<title>Предосмотр статьи</title>
	<script language="JavaScript">
		function nltobr( str ){
			return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1'+'<br>'+'$2');
		}
	</script>
</head>
<body>
<script language="JavaScript">
	document.write(opener.document.edit_form.article.value);
</script>
</body>
</html>