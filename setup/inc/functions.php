<?php

function WriteConfigFile( $filename, $db_type, $host, $user, $pass, $name, $pref, $version )
{
	$cfgf = fopen($filename, "w");
	$content = "<?php\n"
	."		#--Файл сгенерирован инсталлятором.--\n"
	."		#Настройки базы данных\n"
	."\n"
	."\$config['db_errors'] = false;\n"
	."\$config['db_type'] = '$db_type';\n"
	."\$config['db_host'] = '$host';\n"
	."\$config['db_user'] = '$user';\n"
	."\$config['db_pass'] = '$pass';\n"
	."\$config['db_name'] = '$name';\n"
	."\$config['db_pref'] = '$pref';\n"
	."\$config['db_version'] = '$version';\n"
	."\n"
	."?>";
	fwrite($cfgf, $content);
	fclose($cfgf);
}

function WriteSaltFile( $filename )
{
	$cfgf = fopen($filename, "w");
	$salt = GenRandomString(64);
	$content = "<?php\n"
	."		#--Файл сгенерирован инсталлятором.--\n"
	."\n"
	."\$config['salt'] = '$salt';\n"
	."\n"
	."?>";
	fwrite($cfgf, $content);
	fclose($cfgf);
}

?>