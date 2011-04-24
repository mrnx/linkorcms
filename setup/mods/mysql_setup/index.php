<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

global $user;

if(!$user->isDef('setup_type')){
	$user->Def('setup_type', 'install');
}

if(isset($_GET['p'])){
	$p = SafeEnv($_GET['p'], 1, int);
}else{
	$p = 1;
}

function ShowTables()
{
	global $default_prefix, $info_ext, $bases_path;
	$dir = opendir($bases_path);
	$i = -1;
	$tables = array();
	while($file = readdir($dir)){
		$i++;
		$epos = strpos($file, $info_ext);
		if(!($epos === false)){
			$tname = substr($file, 0, $epos);
			$tables[] = substr($tname, strlen($default_prefix) + 1);
		}
	}
	return $tables;
}

function GetTableInfo( $tablename )
{
	global $default_prefix, $info_ext, $bases_path;
	$iname = $bases_path.$default_prefix.'_'.$tablename.$info_ext;
	$fp = fopen($iname, "r");
	flock($fp, LOCK_EX);
	$info = fread($fp, 1048576); #1Мб
	$info = unserialize($info);
	$info['name'] = $tablename;
	flock($fp, LOCK_UN);
	fclose($fp);
	return $info;
}

function ReadData( $tablename )
{
	global $default_prefix, $data_ext, $bases_path;
	$dname = $bases_path.$default_prefix.'_'.$tablename.$data_ext;
	$data = file_get_contents($dname);
	$data = unserialize($data);
	return $data;
}

function sqlValues( $data )
{
	$result = '';
	foreach($data as $var){
		$var = str_replace('&#13', "\r", $var);
		$var = str_replace('&#10', "\n", $var);
		$result .= ",'".mysql_real_escape_string($var)."'";
	}
	$result = substr($result, 1);
	return '('.$result.')';
}

switch($p){
	case 1:
		global $default_prefix;
		$this->SetTitle(_STEP4_1);
		$this->OpenForm('mysql_setup&p=2');
		$text = "<table width=\"80%\">"."<tr><td id=\"l\">"._SQLCONF_1."</td><td><input type=\"text\" name=\"db_host\" value=\"localhost\"></td>\n"."</tr><tr><td id=\"l\">"._SQLCONF_2."</td><td><input type=\"text\" name=\"db_user\" value=\"root\"></td>\n"."</tr><tr><td id=\"l\">"._SQLCONF_3."</td><td><input type=\"text\" name=\"db_pass\" value=\"\"></td>\n"."</tr><tr><td id=\"l\">"._SQLCONF_4."</td><td><input type=\"text\" name=\"db_name\" value=\"\"></td>\n"."</tr><tr><td id=\"l\">"._SQLCONF_5."</td><td><input type=\"text\" name=\"db_pref\" value=\"$default_prefix\"></td>\n"."</tr><tr><td id=\"l\">"._DROP_TABLE_EX."</td><td id=\"l\"><input type=\"checkbox\" name=\"exdel\" value=\"1\" checked></td>\n"."</tr><tr><td id=\"l\">Попытаться создать одноименную БД</td><td id=\"l\"><input type=\"checkbox\" name=\"create_db\" value=\"1\" checked></td>\n"."</tr></table>\n";
		$this->SetContent($text);
		$this->AddButton('Назад', 'install&p=2');
		$this->AddSubmitButton('Проверка');
		break;
	case 2:
		$ok = '<img src="images/admin/accept.png" alt="Да">';
		$fail = '<img src="images/admin/delete.png" alt="Нет">';

		$this->SetTitle("Проверка данных");
		$this->OpenForm('mysql_setup&p=3');
		$db_host = SafeEnv($_POST['db_host'], 250, str);
		$db_user = SafeEnv($_POST['db_user'], 250, str);
		$db_pass = SafeEnv($_POST['db_pass'], 250, str);
		$db_name = SafeEnv($_POST['db_name'], 250, str);
		$db_pref = SafeEnv($_POST['db_pref'], 250, str);
		global $config;
		$error1 = false;
		$error2 = false;
		$error3 = false;
		if(mysql_connect($db_host, $db_user, $db_pass)){
			$p2 = $ok;
		}else{
			$p2 = $fail;
			$error2 = true;
		}
		$text = "<table width=\"80%\">"."<tr><td id=\"l\">Подключение к серверу БД:</td><td>".$p2."</td>\n";
		if($error2){
			$text .= "</tr><tr><td id=\"l\">Возможно вы ввели неверные данные.</td><td></td>\n";
		}
		if(is_file($config['config_dir']."db_config.php")){
			if(is_writable($config['config_dir']."db_config.php")){
				$p1 = $ok;
			}else{
				$p1 = $fail;
				$error1 = true;
			}
			$text .= "</tr><tr><td id=\"l\">\"".$config['config_dir']."db_config.php\" Доступен для записи:</td><td>".$p1."</td>\n";
			if($error1){
				$text .= "</tr><tr><td id=\"l\">Выставите соответствующие хостингу атрибуты на запись.</td><td></td>\n";
			}
		}else{
			if(is_writable($config['config_dir'])){
				$p3 = $ok;
			}else{
				$p3 = $fail;
				$error3 = true;
			}
			$text .= "</tr><tr><td id=\"l\">Папка \"".$config['config_dir']."\" доступна для записи:</td><td>".$p3."</td>\n";
			if($error3){
				$text .= "</tr><tr><td id=\"l\">Выставите права 777 на эту папку.</td><td></td>\n";
			}
		}
		$text .= "</tr></table>\n"."<input type=\"hidden\" name=\"db_host\" value=\"$db_host\">\n"."<input type=\"hidden\" name=\"db_user\" value=\"$db_user\">\n"."<input type=\"hidden\" name=\"db_pass\" value=\"$db_pass\">\n"."<input type=\"hidden\" name=\"db_name\" value=\"$db_name\">\n"."<input type=\"hidden\" name=\"db_pref\" value=\"$db_pref\">\n";
		if(isset($_POST['exdel'])){
			$text .= "<input type=\"hidden\" name=\"exdel\" value=\"1\">\n";
		}
		if(isset($_POST['create_db'])){
			$text .= "<input type=\"hidden\" name=\"create_db\" value=\"1\">\n";
		}
		$this->SetContent($text);
		$this->AddButton('Назад', 'mysql_setup&p=1');
		if(!$error1 && !$error2 && !$error3){
			$this->AddSubmitButton('Установить БД');
		}
		break;
	case 3:
		global $config;
		$this->SetTitle(_SQL_CREATE);
		$db_host = SafeEnv($_POST['db_host'], 250, str);
		$db_user = SafeEnv($_POST['db_user'], 250, str);
		$db_pass = SafeEnv($_POST['db_pass'], 250, str);
		$db_name = SafeEnv($_POST['db_name'], 250, str);
		$db_pref = SafeEnv($_POST['db_pref'], 250, str);
		$filename = $config['config_dir']."db_config.php";
		WriteConfigFile($filename, 'MySQL', $db_host, $db_user, $db_pass, $db_name, $db_pref, CMS_VERSION);
		$saltfilename = $config['config_dir']."salt.php";
		WriteSaltFile($saltfilename);
		global $db;
		include_once ($config['s_inc_dir'].'database.php');
		$delete_ex = isset($_POST['exdel']);
		$create_db = isset($_POST['create_db']);
		if(!$db->Connected){
			$this->SetContent("<html>\n<head>\n\t<title>!!!Ошибка!!!</title>\n</head>\n<body>\n<center>Проблемы с базой данных, проверьте настройки базы данных.</center>\n</body>\n</html>");
		}else{
			if($create_db){
				$db->CreateDB($db_name, false);
			}
			$db->SelectDB($db_name);
			$tables = ShowTables();
			foreach($tables as $table){
				$info = GetTableInfo($table);
				$db->CreateTable($info['name'], $info, $delete_ex);
				$data = ReadData($table);
				foreach($data as $row){
					$values = sqlValues($row);
					$sql = "INSERT INTO `{$db_pref}_{$table}` VALUES $values";
					if(!$db->MySQLQuery($sql)){
						echo $db->ErrorMsg;
					}
				}
			}
			$this->SetContent("База данных создана успешно!<br />Нажмите \"Далее\" для создания учетной записи главного администратора.");
			$this->AddButton('Далее', 'mysql_setup&p=4');
		}
		break;
	case 4: // На страницу создания главного администратора
		GO('setup.php?mod=admin');
		break;
}

?>