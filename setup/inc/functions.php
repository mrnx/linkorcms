<?php

function WriteConfigFile( $filename, $db_type, $host, $user, $pass, $name, $pref, $version ){
	file_put_contents($filename, "<?php\n"
	."// Файл сгенерирован инсталлятором\n"
	."// Настройки базы данных\n"
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
	."?>");
}

function WriteSaltFile( $filename ){
	$salt = GenRandomString(64);
	file_put_contents($filename, "<?php\n"
	."// Файл сгенерирован инсталлятором\n"
	."\n"
	."\$config['salt'] = '$salt';\n"
	."\n"
	."?>");
}

?>