<?php

# LinkorCMS 1.3
# © 2006-2010 Александр Галицкий (linkorcms@yandex.ru)
# Файл: system.php
# Назначение: Ядро системы

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

define('Min2Sec', 60);
define('Hour2Sec', 3600);
define('Day2Sec', 86400);
define('int', 'integer');
define('real', 'float');
define('bool', 'boolean');
define('str', 'string');
define('mix', 'array');
define('intmix', 'int_mix');
define('realmix', 'real_mix');
define('boolmix', 'bool_mix');
define('strmix', 'str_mix');
define('obj', 'object');
define('nil', 'null');
define('system_cache', 'system');

// Ошибки
define('ERROR_HANDLER', true);
define('ERROR', 1);
define('WARNING', 2);
define('PARSE', 4);
define('NOTICE', 8);
define('CORE_ERROR', 16);
define('CORE_WARNING', 32);
define('COMPILE_ERROR', 64);
define('COMPILE_WARNING', 128);
define('USER_ERROR', 256);
define('USER_WARNING', 512);
define('USER_NOTICE', 1024);

// Плагины
define('PLUGINS', true);
define('PLUG_AUTORUN', 1); //Автозапуск
define('PLUG_ADMIN_AUTORUN', 2); //Автозапуск только в админке
define('PLUG_MAIN_AUTORUN', 3); //Автозапуск только на главной
define('PLUG_CALLEE', 4); //Вызываемый отдельно через index.php&name=plugins&p=plugin_name
define('PLUG_MANUAL', 5); //Нужен для работы определённого модуля и подключается вручную. Использует группы.
define('PLUG_MANUAL_ONE', 7); //Подключается один какой-то плагин из группы. Использует группы.
define('PLUG_SYSTEM', 8); //Системный плагин, не трабует инсталляции, вызывается только вручную и может использоваться практически из всех компонентов системы

include $config['inc_dir'].'logi.class.php';
include $config['inc_dir'].'navigation.class.php';
include $config['inc_dir'].'LmFileCache.php';
include $config['inc_dir'].'LmEmailExtended.php';
include $config['inc_dir'].'user.class.php';
include $config['inc_dir'].'html.class.php';
include $config['inc_dir'].'starkyt.class.php';

abstract class System{

	static public $config;
	static public $plug_config;
	static public $Errors = array();

	/**
	 * Объект для работы с базой данных
	 * @return LcDatabaseFilesDB
	 */
	static public function db(){
		return $GLOBALS['db'];
	}

	/**
	 * Объект для работы с данными пользователя и сессией
	 * @return User
	 */
	static public function user(){
		return $GLOBALS['user'];
	}

	/**
	 * Объект управления кэшем
	 * @return LmFileCache
	 */
	static public function cache(){
		return LmFileCache::Instance();
	}

	/**
	 * Объект управления страницей на сайте
	 * @return Page
	 */
	static public function site(){
		return $GLOBALS['site'];
	}

	/**
	 * Объект управления страницей в админ-панели
	 * @return AdminPage
	 */
	static public function admin(){
		return $GLOBALS['site'];
	}

}

System::$config = &$config;
System::$plug_config = &$plug_config;

function SmiliesReplace( &$text ){
	global $db, $config;
	static $codes = array();
	static $cached = false;
	if(!$cached){
		$smilies = $db->Select('smilies'); // Пусть отключенные смайлики тоже парсятся
		foreach($smilies as $smile){
			$sub_codes = explode(',', $smile['code']);
			foreach($sub_codes as $code){
				$codes[$code] = '<img src="'.$config['general']['smilies_dir'].$smile['file'].'" />';
			}
		}
		$cached = true;
	}
	$text = strtr($text, $codes);
}

function IsAjax(){
	return isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' : false;
}

/**
 * Возвращает все правила замены, либо добавляет новое правило в кэш
 * @global $db $db
 * @staticvar <type> $UfuRewriteRules
 * @param <type> $id
 * @param <type> $UfuTemplate
 * @param <type> $Pattern
 * @param <type> $Params
 * @return <type>
 */
function &UfuGetRules( $id = null, $UfuTemplate = '', $Pattern = '', $Params = ''){
	global $db;
	static $UfuRewriteRules;
	if($UfuRewriteRules == null){
		$_rules = $db->Select('rewrite_rules');
		foreach($_rules as $rule){
			$UfuRewriteRules[$rule['ufu']] = $rule;
		}
	}
	if($id != null){
		$UfuRewriteRules[$UfuTemplate] = array('id'=>$id, 'ufu'=>$UfuTemplate, 'pattern'=>$Pattern, 'params'=>$Params);
	}
	return $UfuRewriteRules;
}

function UfuAddRewriteRule( $UfuTemplate, $params ){
	global $db;
	// Определяем позиции параметров в шаблоне чтобы установить параметры замены в нужном месте
	$temp_pos = array();
	foreach($params as $key=>$val){
		$p = strpos($UfuTemplate, '{'.$key.'}');
		if($p !== false){
			$temp_pos[] = array($p, $key);
		}
	}
	SortArray($temp_pos, 0);
	$pos = array();
	foreach($temp_pos as $key=>$val){
		$pos[$val[1]] = $key+1;
	}

	// Генерируем регулярное выражение и шаблон замены
	$replace = array();
	$ReplacePattern = '';
	foreach($params as $key=>$val){
		if(is_numeric($val)){
			$replace['\{'.$key.'\}'] = '([0-9]+)';// фигурная скобка экранируется перед заменой
		}else{
			$replace['\{'.$key.'\}'] = '([0-9]*[^0-9\/-]+[0-9]*)';
		}
		if(strpos($UfuTemplate, '{'.$key.'}') !== false){
			$ReplacePattern .= "$key=\${$pos[$key]}&";
		}else{
			$ReplacePattern .= "$key=$val&";
		}
	}
	if(substr($ReplacePattern, -1) == '&'){
		$ReplacePattern = substr($ReplacePattern, 0, -1);
	}
	$Pattern = strtr(preg_quote($UfuTemplate, '/'), $replace);

	// Добавляем запись в базу данных
	$db->Insert('rewrite_rules', Values('', SafeEnv($UfuTemplate, 255, str), SafeEnv($Pattern, 255, str), SafeEnv($ReplacePattern, 255, str)));
	UfuGetRules( $db->GetLastId(), $UfuTemplate, $Pattern, $ReplacePattern); // Добавляем правило в кэш
}

function Ufu( $Url, $UfuTemplate = '', $NavLink = false, $NavParam = 'page' ){
	global $config;
	if($config['general']['ufu']){
		if($Url == 'index.php'){
			return GetSiteUrl().'index.html';
		}
		$p = strpos($Url, '?');
		if($p !== false){
			$Url = substr($Url, $p + 1);
		}
		$p = strrpos($Url, '#');
		if($p !== false){
			$anchor = substr($Url, $p);
			$Url = substr($Url, 0, $p);
		}else{
			$anchor = '';
		}
		parse_str($Url, $params);
		if($NavLink){
			$params[$NavParam] = 1;
		}
		$replace = array();
		foreach($params as $key=>$val){
			if(!$NavLink || $key != $NavParam){
				$replace['{'.$key.'}'] = $val;
			}
		}
		$Ufu = strtr($UfuTemplate, $replace);

		$Rules = UfuGetRules();
		if(!isset($Rules[$UfuTemplate])){
			UfuAddRewriteRule($UfuTemplate, $params);
		}
		return $Ufu.$anchor;
	}else{
		if($Url == 'index.php'){
			return GetSiteUrl().$Url;
		}
		return $Url;
	}
}

/**
 * Сравнивает путь с сохраненными правилами автозамены,
 * ищет совпадения и генерирует массив параметров
 * @param <type> $Path
 */
function UfuRewrite( $Path ){
	$Rules = UfuGetRules();
	foreach($Rules as $Rule){
		if(preg_match_all('/^'.$Rule['pattern'].'$/', $Path, $matches)){
			foreach($matches as $i=>$m){
				$search[] = '$'.$i;
				$replace[] = $m[0];
			}
			parse_str(str_replace($search, $replace, $Rule['params']), $Params);
			return $Params;
		}
	}
	return array();
}

/**
 * Загружает натройки из базы данных
 * @global  $db
 * @param var $config_var Переменная в которую будут записаны настройки
 * @param <type> $cfg_table
 * @param <type> $grp_table
 */
function LoadSiteConfig( &$config_var, $cfg_table = 'config', $grp_table = 'config_groups' ){
	global $db;

	$cache = LmFileCache::Instance();
	if($cache->HasCache('config', $cfg_table)){
		$config_var = $cache->Get('config', $cfg_table);
		return;
	}

	$temp = $db->Select($cfg_table, "`autoload`='1'");
	foreach($temp as $i){
		$configs[$i['group_id']][] = $i;
	}

	# Вытаскиваем группы настроек
	$config_groups = $db->Select($grp_table,'');
	foreach($config_groups as $group){
		if(isset($configs[$group['id']])){
			foreach($configs[$group['id']] as $config){
				$gi = $group['id'];
				$gname = $group['name'];
				$cname = $config['name'];
				$cvalue = $config['value'];
				$type = trim($config['type']);
				if($type<>''){
					$type = explode(',', $type);
				}else{
					$type = array(255, 'string', false);
				}
				if($type[0] > 0){
					$cvalue = substr($cvalue, 0, $type[0]);
				}
				if($type[2] != 'false'){
					$type[2] = strip_tags($type[2]);
				}
				settype($cvalue, $type[1]);
				if($cvalue=='' && ($type[1]=='bool' || $type[1]=='boolean')){
					$cvalue = '0';
				}

				$config_var[$gname][$cname] = $cvalue;
			}
		}
	}

	$cache->Write('config', $cfg_table, $config_var);
}

/**
 * Устанавливет значение одной настройки.
 * @param <type> $group
 * @param <type> $cname
 * @param <type> $newValue
 */
function ConfigSetValue( $group, $cname, $newValue ){
	global $config, $db;
	$group = $db->Select('config_groups', "`name`='$group'");
	$gid = SafeEnv($group[0]['id'], 11, int);
	$db->Update('config', "`value`='$newValue'",  "`group_id`='$gid' and `name`='$cname'");
	// Очищаем кэш настроек
	$cache = LmFileCache::Instance();
	$cache->Clear('config');
}

/**
 * Устанавливает временную зону указанную в настройках сайта
 */
function SetDefaultTimezone(){
	@date_default_timezone_set(System::$config['general']['default_timeone']);
}

function SafeXSS( &$var ){
	$var = strtr( $var,array(
		'&#34'=>'"',
		'&#x22;'=>'"',
		'&quot;'=>'"',
		'%22'=>'"',
		'&#39'=>"'",
		'&#x27;'=>"'",
		'%27'=>"'",
		'&#96'=>'`',
		'&#x60;'=>'`',
		'%60'=>'`',
		'&#32'=>' ',
		'&#x20;'=>' ',
		'&#9'=>"\t",
		'&#x09;'=>"\t",
		'%09'=>"\t",
		'&#61'=>'=',
		'&#x3D;'=>'=',
		'%3D'=>'=',
		'&#60'=>'<',
		'&#x3C;'=>'<',
		'&lt;'=>'<',
		'%3C'=>'<',
		'&#62'=>'>',
		'&#x3E;'=>'>',
		'&gt;'=>'>',
		'%3E'=>'>',
		'&#92'=>'\\',
		'&#x5C;'=>'\\',
		'%5C'=>'\\',
		'&#37'=>'%',
		'&#x25;'=>'%',
		'%25'=>'%',
		'&#43'=>'+',
		'&#x2B;'=>'+',
		'%2B'=>'+',
		'&#173'=>'-',
		'&#xAD;'=>'-',
		'&shy;'=>'-',
		'%AD'=>'-',
		'&#38'=>'&',
		'&#x26;'=>'&',
		'&amp;'=>'&',
		'%26'=>'&'
		)
	);
}

/**
 * Добавляет экранирование в зависимости от настройки php и приводит к типу для вставки переменной в запрос к базе данных.
 * Внимание, включение параметра SafeXSS может исказить данные так, что они будут не равны при стравнении.
 *
 * @param Variable $Var // Какая-то переменная
 * @param Integer $maxlength // Длина строкового значения переменной
 * @param Const $type // Константа типа переменной. Константы описаны в system.php
 * @param Bool $addsl // Добавить обратные слэши перед всеми спецсимволами
 * @return Variable
 */
function SafeEnv( $Var, $maxlength, $type, $strip_tags = false, $addsl = true, $safexss = true ){
	global $db;
	if(is_array($Var)){
		foreach($Var as $i=>$v){
			if($maxlength > 0){
				$v = substr($v, 0, $maxlength);
			}
			$v = trim($v);
			if($safexss){
				SafeXSS($v);
			}
			if($strip_tags){
				$v = strip_tags($v);
			}
			if($addsl){
				if(defined("DATABASE")){
					$v = $db->EscapeString($v);
				}else{
					$v = addslashes($v);
				}
			}
			settype($v, $type);
			$Var[$i] = $v;
		}
	}else{
		if($maxlength > 0){
			$Var = substr($Var, 0, $maxlength);
		}
		$Var = trim($Var);
		if($safexss){
			SafeXSS($Var);
		}
		if($strip_tags){
			$Var = strip_tags($Var);
		}
		if($addsl){
			if(defined("DATABASE")){
				$Var = $db->EscapeString($Var);
			}else{
				$Var = addslashes($Var);
			}
		}
		settype($Var,$type);
	}
	return $Var;
}

/**
 * Фильтрует переменную для безопасного вывода ее содержимого в браузер пользователя.
 * Функция не добавляет экранирование, переменую отфильтрованную данной функцией опасно передавать в запрос к базе данных.
 *
 * @param <type> $Var
 * @param <type> $maxlength
 * @param <type> $type
 * @param <type> $strip_tags
 * @param <type> $specialchars
 * @param <type> $safexss
 * @return <type>
 */
function SafeDB( $Var, $maxlength, $type, $strip_tags = true, $specialchars=true, $safexss = true ){
	if(is_array($Var)){
		for($i=0, $cnt=count($Var); $i<$cnt; $i++){
			if($maxlength > 0){
				$Var[$i] = substr($Var[$i],0,$maxlength);
			}
			$Var[$i] = trim($Var[$i]);
			if($safexss){
				SafeXSS($Var[$i]);
			}
			if($strip_tags){
				$Var[$i] = strip_tags($Var[$i]);
			}
			if($specialchars){
				$Var[$i] = htmlspecialchars($Var[$i]);
			}
			settype($Var[$i],$type);
		}
	}else{
		if($maxlength > 0){
			$Var = substr($Var,0,$maxlength);
		}
		$Var = trim($Var);
		if($safexss){
			SafeXSS($Var);
		}
		if($strip_tags){
			$Var = strip_tags($Var);
		}
		if($specialchars){
			$Var = htmlspecialchars($Var);
		}
		settype($Var,$type);
	}
	return $Var;
}

/**
 * Проверяет массив GET на наличие нужных ключей
 * @return bool
 */
function CheckGet(){
	$args = func_get_args();
	foreach($args as $name){
		if(!isset($_GET[$name])){
			return false;
		}
	}
	return true;
}

/**
 * Проверяет массив $_POST на наличие ключей
 * @return bool
 */
function CheckPost(){
	$args = func_get_args();
	foreach($args as $name){
		if(!isset($_POST[$name])){
			return false;
		}
	}
	return true;
}

$Parser_WhereCache = array();
$Parser_SetCache = array();
$Parser_UseCache = true;

#Пример использования:
#Строка: 	$set="name='name',login='root',pass=''";
#Результат: array('name','login','pass');-Упорядоченный массив
#в row должен быть упорядоченный массив из значений поля таблицы
function Parser_ParseSetStr( &$set, &$row, &$info ){
	$s = str_replace("\\'",'<&#39;>',$set);
	$maxlength=count($info['cols']);
	for($i=0;$i<$maxlength;$i++){
		$args[$i] = $row[$i];
		$names[$info['cols'][$i]['name']] = $i;
	}
	for($i=0;$i<$maxlength;$i++){
		$pos = strpos($s, '='); //Ищем первое вхождение =
		if($pos===false){
			break;
		}
		$col = trim(substr($s, 0, $pos));
		if(substr($col,0,1)=='`'){
			$col = substr($col,1,strlen($col)-2);
		}
		$s = substr($s,$pos+1);
		$pos = strpos($s,"'");
		$s = substr($s,$pos+1);
		$pos = strpos($s,"'");
		$val = substr($s,0,$pos);
		$s = substr($s,$pos+1);
		$pos = strpos($s,",");
		$s = substr($s,$pos+1);
		if(isset($names[$col])){
			$val = str_replace('<&#39;>',"'",$val);
			$args[$names[$col]] = $val;
		}else{
			echo "Ошибка в синтаксисе запроса. Поле ".$col." в таблице не найдено!";
			return $row;
		}
	}
	return $args;
}

#Пример использования:
#Строка: 	$values="'name','root',''";
#Результат: array('name','root','');
function Parser_ParseValuesStr(&$values, &$Info, $isUpdateMethod = false, $lastvals = false){
	$values2 = str_replace("\\'",'<&#39;>',$values);
	$values2 = trim($values2);
	$maxlength = Count($Info['cols']);
	for($i=0; $i<$maxlength; $i++){
		$pos = strpos($values2, "'");
		if($pos === false){
			break;
		}
		$values2 = substr($values2, $pos + 1);
		$pos = strpos($values2, "'");
		$val = substr($values2, 0, $pos);
		$values2 = substr($values2, $pos + 1);

		if((isset($Info['cols'][$i]['auto_increment']))&&($Info['cols'][$i]['auto_increment'])){
			if(!$isUpdateMethod){
				$args[$i] = $Info['counter']+1;
				continue;
			}else{
				if(isset($lastvals[$i])){
					$args[$i] = $lastvals[$i];
					continue;
				}
			}
		}
		$val = str_replace('<&#39;>',"'",$val);
		$args[$i] = $val;
	}
	return $args;
}

#Запросы должны соответствовать синтаксису SQL запросов
#в row должен быть упорядоченный массив из значений поля таблицы
#Результат: true если условие выполнено и false если нет
function Parser_ParseWhereStr( $where, $row, $info, $index = 0 ){
	if($where == ''){ return true; };
	global $Parser_UseCache, $Parser_WhereCache;
	$vars = array();
	// Значение переменных и имена колонок
	// fixme: Можно кешировать или ускорить выборку имен
	for($j=0,$ccnt=count($info['cols']);$j<$ccnt;$j++){
		$n = $info['cols'][$j]['name'];
		$r = str_replace('&#13', "\r", $row[$j]); // Чтобы данные соотвествовали данным из $db->Select
		$r = str_replace('&#10', "\n", $r);
		$vars[$n] = $r;
		$names[] = $n;
	}
	$vars['index'] = $index;
	$names[] = 'index';
	$ccnt++;
	// Проверяем есть ли такой результат преобразования в оперативном кеше
	if(isset($Parser_WhereCache[$where])){
		return Parser_ParseWhereStr2($Parser_WhereCache[$where], $vars);
	}
	// Чтобы разбить запрос по кавычкам и преобразовать его в PHP формат заменяем экранированные кавычки
	$where2 = str_replace("\\'", '<&#39;>', $where);
	$chs = explode("'", $where2);
	for($i=1,$cnt=count($chs)+1; $i<$cnt; $i++){
		if($i%2>0){
			$chs[$i-1] = str_replace("=", "==", $chs[$i-1]);
			for($j=0;$j<$ccnt;$j++){
				$chs[$i-1] = str_replace('`'.$names[$j].'`', '$'.$names[$j], $chs[$i-1]);
			}
		}else{
			// Экранируем экранирование
			$chs[$i-1] = str_replace("\\", "\\\\", $chs[$i-1]);
		}
	}
	$where2 = implode("'", $chs);
	$where2 = str_replace('<&#39;>', "\\'", $where2);
	// Записываем результат преобразования в оперативный кеш
	if($Parser_UseCache){
		$Parser_WhereCache[$where] = $where2;
	}
	if($info['name'] == 'rewrite_rules'){
		echo $where2;
		print_r($vars);
	}
	return Parser_ParseWhereStr2($where2, $vars);
}

function Parser_ParseWhereStr2(){
	extract(func_get_arg(1), EXTR_OVERWRITE);
	eval('if('.func_get_arg(0).'){$result = true;}else{$result = false;}');
	return $result;
}

// Возвращает массив с информацией о ячейке, который понимают классы для работы с БД.
function GetCollDescription( $cname, $type, $length, $auto_increment=false, $default='', $attributes='', $notnull=true, $primary=false, $index=false, $unique=false, $fulltext=false ){
	$newcoll = array(
		'name'=>$cname,
		'type'=>$type,
		'length'=>$length,
	);
	if($auto_increment == true){
		$newcoll['auto_increment'] = true;
	}
	if($default <> ''){
		$newcoll['default'] = $default;
	}
	if($attributes <> ''){
		$newcoll['attributes'] = $attributes;
	}
	if($notnull == true){
		$newcoll['notnull'] = $notnull;
	}
	if($primary <> ''){
		$newcoll['primary'] = $primary;
	}
	if($index <> ''){
		$newcoll['index'] = $index;
	}
	if($unique <> ''){
		$newcoll['unique'] = $unique;
	}
	if($fulltext <> ''){
		$newcoll['fulltext'] = $fulltext;
	}
	return $newcoll;
}

function AntispamEmail($email, $addjava=true){
	global $site;
	static $javaAdd = false;
	if(!$javaAdd && $addjava){
		$site->AddJS('
		function email(login,domain){
			mail = login+"@"+domain;
			mail = \'<a href="mailto:\'+mail+\'" target="_blank">\'+mail+\'</a>\';
			document.write(mail);
		}
		');
		$javaAdd = true;
	}
	$email = explode('@', $email);
	if(count($email) == 2){
		return '<script>email(\''.$email[0].'\',\''.$email[1].'\');</script>';
	}else{
		return '';
	}
}

$system_users_cache = null;
$system_userranks_cache = null;
$system_usertypes_cache = null;

/**
 * Возвращает массив данных о пользователях с ключами по id
 * @global $db $db
 * @global <type> $user
 * @global <type> $system_users_cache
 * @return <type>
 */
function GetUsers(){
	global $db, $user, $system_users_cache;
	if($system_users_cache == null){
		$cache = LmFileCache::Instance();
		if($cache->HasCache(system_cache, 'users')){
			$system_users_cache = $cache->Get(system_cache, 'users');
		}else{
			$db->Select('users', '');
			$system_users_cache = array();
			foreach($db->QueryResult as $usr){
				$system_users_cache[$usr['id']] = $usr;
			}
			// На всякий случай кеш обновляется один раз в сутки
			$cache->Write(system_cache, 'users', $system_users_cache, Day2Sec);
		}
	}
	return $system_users_cache;
}

function GetUserRanks(){
	global $db, $user, $system_userranks_cache;
	if($system_userranks_cache == null){
		$cache = LmFileCache::Instance();
		if($cache->HasCache(system_cache, 'userranks')){
			$system_userranks_cache = $cache->Get(system_cache, 'userranks');
		}else{
			$system_users_cache = array();
			$system_userranks_cache = $db->Select('userranks', '');
			SortArray($system_userranks_cache, 'min');
			$cache->Write(system_cache, 'userranks', $system_userranks_cache);
		}

	}
	return $system_userranks_cache;
}

function GetUserTypes(){
	global $db, $user, $system_usertypes_cache;
	if($system_usertypes_cache == null){
		$cache = LmFileCache::Instance();
		if($cache->HasCache(system_cache, 'usertypes')){
			$system_usertypes_cache = $cache->Get(system_cache, 'usertypes');
		}else{
			$types = $db->Select('usertypes', '');
			$system_usertypes_cache = array();
			foreach($types as $type){
				$system_usertypes_cache[$type['id']] = $type;
			}
			$cache->Write(system_cache, 'usertypes', $system_usertypes_cache);
		}
	}
	return $system_usertypes_cache;
}


#Возвращает полную информацию о пользователе
#Включая ранг, картинку ранга, статус онлайн, имя файла аватара для вывода.
#Вся информация кэшируется.
function GetUserInfo($user_id){
	global $db, $user, $config;
	$system_users_cache = GetUsers();
	if(isset($system_users_cache[$user_id])){
		$usr = $system_users_cache[$user_id];
		//Аватар
		$usr['avatar_file'] = GetUserAvatar($user_id);
		$usr['avatar_file_small'] = GetSmallUserAvatar($user_id, $usr['avatar_file']);
		$usr['avatar_file_smallest'] = GetSmallestUserAvatar($user_id,  $usr['avatar_file']);
		//Ранг
		$rank = GetUserRank($usr['points'],$usr['type'],$usr['access']);
		$usr['rank_name'] = $rank[0];
		$usr['rank_image'] = $rank[1];
		//Статус онлайн
		$online = $user->Online();
		$usr['online'] = isset($online[$user_id]);
		return $usr;
	}else{
		return false;
	}
}

function GetUserAvatar( $user_id ){
	return GetPersonalAvatar($user_id);
}

function GetSmallUserAvatar( $user_id, $avatar = '' ){
	global $config;
	if($avatar == ''){
		$avatar = GetPersonalAvatar($user_id);
	}
	if($config['user']['secure_avatar_upload'] == '1' && GDVersion() <> 0){
		return $avatar.'&size=small';
	}else{
		$_name = GetFileName($avatar);
		$_ext = GetFileExt($avatar);
		$filename = $config['general']['personal_avatars_dir'].$_name.'_64x64'.$_ext;
		if(is_file($filename)){
			return $filename;
		}else{
			return 'index.php?name=plugins&p=avatars_render&user='.$user_id.'&size=small';
		}
	}
}

function GetSmallestUserAvatar( $user_id, $avatar = '' ){
	global $config;
	if($avatar == ''){
		$avatar = GetPersonalAvatar($user_id);
	}
	if($config['user']['secure_avatar_upload'] == '1' && GDVersion() <> 0){
		return $avatar.'&size=smallest';
	}else{
		$_name = GetFileName($avatar);
		$_ext = GetFileExt($avatar);
		$filename = $config['general']['personal_avatars_dir'].$_name.'_24x24'.$_ext;
		if(is_file($filename)){
			return $filename;
		}else{
			return 'index.php?name=plugins&p=avatars_render&user='.$user_id.'&size=smallest';
		}
	}
}

function GetPersonalAvatar($user_id){
	global $db, $config;
	if($user_id == 0){
		return GetGalleryAvatar('guest.gif');
	}
	if($config['user']['secure_avatar_upload']=='1' && GDVersion()<>0){
		if($user_id==0){
			return GetGalleryAvatar('guest.gif');
		}else{
			return 'index.php?name=plugins&p=avatars_render&user='.$user_id;
		}
	}else{
		$system_users_cache = GetUsers();
		if(!isset($system_users_cache[$user_id])){
			return GetGalleryAvatar('guest.gif');
		}
		$usePersonal = $system_users_cache[$user_id]['a_personal'];
		$filename = $system_users_cache[$user_id]['avatar'];
		if($usePersonal=='1'){
			$afn = $config['general']['personal_avatars_dir'].$filename;
		}else{
			$afn = $config['general']['avatars_dir'].$filename;
		}
		if(file_exists($afn)){
			return $afn;
		}else{
			return GetGalleryAvatar('noavatar.gif');
		}
	}
}

function GetGalleryAvatar($filename){
	global $config;
	if(!defined('SETUP_SCRIPT')){
		if(trim($filename)==''){
			$filename = 'noavatar.gif';
		}
		if($config['user']['secure_avatar_upload']=='1' && GDVersion()!==false){
			return 'index.php?name=plugins&p=avatars_render&aname='.$filename;
		}else{
			return $config['general']['avatars_dir'].$filename;
		}
	}else{
		return $filename;
	}
}


// Возвращает название, картинку и идентификатор ранга
function GetUserRank($points, $type, $access){
	global $config, $db;
	static $admintypes = null;
	if($type == '2'){ // Пользователь
		$ranks = GetUserRanks();
		$last = $ranks[0];
		foreach($ranks as $rank){
			if($rank['min'] > $points){
				return array(
				    SafeDB($last['title'], 250, str),
				    $config['general']['ranks_dir'].RealPath2(SafeDB($last['image'], 250, str)),
				    SafeDB($last['id'], 11, int));
			}else{
				$last = $rank;
			}
		}
		return array(
		    SafeDB($last['title'], 250, str),
		    $config['general']['ranks_dir'].RealPath2(SafeDB($last['image'], 250, str)),
		    SafeDB($last['id'], 11, int));
	}else{ // Администратор
		$admintypes = GetUserTypes();
		if(isset($admintypes[$access])){
			return array(
				'<font color="'.SafeDB($admintypes[$access]['color'], 9, str).'">'.SafeDB($admintypes[$access]['name'], 255, str).'</font>',
				RealPath2($config['general']['ranks_dir'].SafeDB($admintypes[$access]['image'], 250, str)),
				SafeDB($admintypes[$access]['id'], 11, int));
		}
	}
}

function UserSendActivationMail($username, $user_mail, $login, $pass, $code, $regtime){
	global $config;
	$time = $regtime+604800;
	$time = date("d.m.Y", $time);

	$text = $config['user']['mail_template'];

	$sr = array(
		'{sitename}', '{siteurl}', '{username}', '{date}', '{login}', '{pass}', '{link}'
	);
	$rp = array(
		$config['general']['site_name'], $config['general']['site_url'], $username, $time, $login, $pass, $config['general']['site_url'].'index.php?name=plugins&p=activate&code='.$code
	);

	$text = str_replace($sr, $rp, $text);

	SendMail($username, $user_mail, 'Регистрация на '.$config['general']['site_name'], $text);
}

function UserSendEndRegMail($user_mail, $name, $login, $pass, $regtime){
	global $config;
	$text = 'Здравствуйте, ['.$name.']!

Вы были успешно зарегистрированы на сайте
'.$config['general']['site_url'].'

Дата регистрации: '.date("d.m.Y", $regtime).'
Имя: '.$name.'

Для входа на сайт используйте:
логин: '.$login.'
пароль: '.$pass.'

Надеемся, наш сайт будет Вам полезен.
С уважением, администрация сайта '.$config['general']['site_url'].'.';
	SendMail($name, $user_mail, '['.$config['general']['site_url'].'] Регистрация', $text);
}

function UserSendForgotPassword($user_mail, $name, $login, $pass){
	global $config;
	$ip = getip();
	$text = 'Здравствуйте, ['.$name.']!

На сайте '.$config['general']['site_url'].'
было запрошено напоминание пароля.

Имя: '.$name.'

Ваш логин и новый пароль:
логин: '.$login.'
пароль: '.$pass.'

Изменить данные аккаунта можете по адресу:
'.GetSiteUrl().Ufu('index.php?name=user&op=editprofile', 'user/{op}/').'

IP-адрес, с которого был запрошен пароль: '.$ip.'

С уважением, администрация сайта '.$config['general']['site_url'].'.';
	SendMail($name, $user_mail, '['.$config['general']['site_url'].'] Напоминание пароля', $text);
}

function GetGmtArray(){
	$tlist = timezone_identifiers_list();
	$gmt = array(
	);
	foreach($tlist as $timezone){
		$gmt[] = array(
			$timezone, $timezone
		);
	}
	return $gmt;
}

function GetGmtData($val){
	global $site;
	$tlist = timezone_identifiers_list();
	$gmt = array(
	);
	foreach($tlist as $timezone){
		$site->DataAdd($gmt, $timezone, $timezone, $val == $timezone);
	}
	return $gmt;
}

function GetGalleryAvatarsData($avatar, $personal){
	global $config, $site;
	$avatars = GetFiles($config['general']['avatars_dir'], false, true, '.gif.jpg.jpeg.png');
	$selindex = 0;
	$avd = array(
	);
	if($personal == '1'){
		$site->DataAdd($avd, '', 'Персональный', true);
	}
	for($i = 0, $c = count($avatars); $i < $c; $i++){
		if($avatar == $avatars[$i]){
			$sel = true;
			$selindex = $i;
		} else{
			$sel = false;
		}
		$site->DataAdd($avd, $avatars[$i], $avatars[$i], $sel);
	}
	return array(
		$avd, $avatars[$selindex]
	);
}

function GetGalleryAvatars($avatar, $personal){
	global $config, $site;
	$avatars = GetFiles($config['general']['avatars_dir'], false, true, '.gif.jpg.jpeg.png');
	$selindex = 0;
	$avd = array(
	);
	if($personal == '1'){
		$site->DataAdd($avd, '', 'Персональный', true);
	}
	for($i = 0, $c = count($avatars); $i < $c; $i++){
		if($avatar == $avatars[$i]){
			$sel = true;
			$selindex = $i;
		} else{
			$sel = false;
		}
		$vars['name'] = $avatars[$i];
		$vars['selected'] = $sel;
		$vars['caption'] = $avatars[$i];
	}
	return $vars;
}

/**
 * Функция управляет загрузкой аватар ($_FILES['upavatar])
 */
function UserLoadAvatar(&$errors, &$avatar, &$a_personal, $oldAvatarName, $oldAvatarPersonal, $editmode){
	global $config;

	$alloy_mime = array(
		'image/gif' => '.gif', 'image/jpeg' => '.jpg', 'image/pjpeg' => '.jpg', 'image/png' => '.png', 'image/x-png' => '.png'
	);
	include_once($config['inc_dir'].'picture.class.php');

	$asize = getimagesize($_FILES['upavatar']['tmp_name']);

	//Проверка формата файла
	$alloy_mime = array(
		'image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png'
	);
	$alloy_exts = array(
		'.gif', '.jpg', '.jpeg', '.png'
	);
	if(in_array($_FILES['upavatar']['type'], $alloy_mime) && in_array(strtolower(GetFileExt($_FILES['upavatar']['name'])), $alloy_exts)){
		// Удаляем старый аватар
		if($editmode && $oldAvatarPersonal == '1'){
			UnlinkUserAvatarFiles($oldAvatarName);
		}

		//Выполняем ресайз, если нужно, и сохраняем аватар в папку персональных аватар
		$NewName = GenRandomString(8, 'qwertyuiopasdfghjklzxcvbnm');
		$ext = strtolower(GetFileExt($_FILES['upavatar']['name']));

		if($asize[0] > $config['user']['max_avatar_width'] || $asize[1] > $config['user']['max_avatar_height']){
			$thumb = new TPicture($_FILES['upavatar']['tmp_name']);
			$thumb->SetImageSize($config['user']['max_avatar_width'], $config['user']['max_avatar_height']);
			$thumb->SaveToFile($config['general']['personal_avatars_dir'].$NewName.$ext);
		} else{
			copy($_FILES['upavatar']['tmp_name'], $config['general']['personal_avatars_dir'].$NewName.$ext);
		}

		// Создаем стандартные уменьшенные копии 24х24 и 64х64
		$thumb = new TPicture($_FILES['upavatar']['tmp_name']);
		$thumb->SetImageSize(64, 64);
		$thumb->SaveToFile($config['general']['personal_avatars_dir'].$NewName.'_64x64'.$ext);
		$thumb = new TPicture($_FILES['upavatar']['tmp_name']);
		$thumb->SetImageSize(24, 24);
		$thumb->SaveToFile($config['general']['personal_avatars_dir'].$NewName.'_24x24'.$ext);

		$avatar = $NewName.$ext;
		$a_personal = '1';
	} else{
		$errors[] = 'Неправильный формат аватара. Ваш аватар должен быть формата GIF, JPEG или PNG.';
		$a_personal = '0';
	}
}

function UnlinkUserAvatarFiles($AvatarFileName){
	global $config;
	$AvatarFileName = RealPath2($config['general']['personal_avatars_dir'].$AvatarFileName);
	if(is_file($AvatarFileName)){
		unlink($AvatarFileName);
		$_name = GetFileName($AvatarFileName);
		$_ext = GetFileExt($AvatarFileName);
		if(is_file($config['general']['personal_avatars_dir'].$_name.'_24x24'.$_ext)){
			unlink($config['general']['personal_avatars_dir'].$_name.'_24x24'.$_ext);
		}
		if(is_file($config['general']['personal_avatars_dir'].$_name.'_64x64'.$_ext)){
			unlink($config['general']['personal_avatars_dir'].$_name.'_64x64'.$_ext);
		}
	}
}

// Изменяет поле счетчика у объекта
function CalcCounter($objTable, $whereObj, $objCounterColl, $calcVal){
	global $db;
	$objCounterColl = SafeEnv($objCounterColl, 255, str);
	$db->Select($objTable, $whereObj);
	if($db->NumRows() > 0){
		$counterVal = $db->QueryResult[0][$objCounterColl] + $calcVal;
		$db->Update($objTable, "$objCounterColl='$counterVal'", $whereObj);
	}
}

#Регистрирует таблицу комментариев
function RegisterCommentTable($name, $objTable, $ObjIdColl, $objCounterColl, $objCounterCollIndex){
	global $db;
	$name = SafeEnv($name, 64, str);
	$db->Insert('comments', Values('', $name, $objTable, $ObjIdColl, $objCounterColl, $objCounterCollIndex));
}

#Освобождает таблицу комментариев
function UnRegisterCommentTable($name, $delete=false){
	global $db;
	$name = SafeEnv($name, 64, str);
	$db->Delete('comments', "`table`='$name'");
	if($delete){
		$db->DropTable($name);
	}
}

/**
 * Функция обновляет данные пользователя во всех комментариях.
 * При передаче параметров фильтровать их функцией SafeEnv.
 * @global <type> $db
 * @param <type> $uid
 * @param <type> $newUid
 * @param <type> $Name
 * @param <type> $email
 * @param <type> $hEmail
 * @param <type> $homePage
 * @param <type> $uIP
 */
function UpdateUserComments($uid, $newUid, $Name, $email, $hEmail, $homePage, $uIP=null)
{
	global $db;
	$set = "user_id='$newUid',user_name='$Name',user_homepage='$homePage',user_email='$email',"
	."user_hideemail='$hEmail'".($uIP<>null?",user_ip='$uIP'":'');
	$where = "`user_id`='$uid'";
	$ctables = $db->Select('comments', '');
	foreach($ctables as $table){
		$db->Update($table['table'], $set, $where);
	}
}

/**
 * Удаляет все коментарии пользователя
 * @param  $uid
 * @return void
 */
function DeleteAllUserComments( $uid ){
	global $db;
	$uid = SafeEnv($uid, 11, int);
	$where = "`user_id`='$uid'";
	$ctables = $db->Select('comments','');
	foreach($ctables as $table){
		$comms = $db->Select(SafeEnv($table['table'], 255, str), $where);
		$comments = array();
		$objects = array();
		//Отсортировываем id комментарий по объектам
		foreach($comms as $com){
			$comments[$com['object_id']] = SafeEnv($com['id'], 11, int);
			$objects[] = SafeEnv($com['object_id'], 11, int);
		}
		//теперь нужно обойти все объекты уменьшая счетчик
		foreach($objects as $obj){
			$id_coll = SafeEnv($table['id_coll'], 11, int);
			CalcCounter(
				$table['objects_table'],
				"`$id_coll`='{$obj}'",
				$table['counter_coll'],
				count($comments[$obj]) * -1
			);
		}
		$db->Delete(SafeEnv($table['table'], 255, str), $where);
	}
}

#Разрезает слова, которые длиннее заданного параметра, на части
function DivideWord( $text, $maxWordLength='30' ){
	return wordwrap($text, $maxWordLength, chr(13), 1);
}

/**
 * Сортирует массив по ключу. Для вывода массива отсортированного этой функцией
 * рекомендуется использовать цикл для обхода массива foreach.
 *
 * @param Array $array // массив типа $array = array(array(col1=1,col2=2,...),arr...)
 * массивы такого вида выдаются при запросах к БД
 * @param Integer $coll // номер колонки в массиве по которой его сортировать
 * @param Boolean $OnDecrease // если true то сортировка будет осуществляться в обратном порядке
 */
function SortArray( &$array, $coll, $OnDecrease=false )
{
	global $SATempVar;

	if(!function_exists('SorterUp')){
		function SorterUp($a,$b)
		{
			global $SATempVar;
			if ($a[$SATempVar] == $b[$SATempVar]) return 0;
			return ($a[$SATempVar] < $b[$SATempVar]) ? -1 : 1;
		}

		function SorterDown($a,$b)
		{
			global $SATempVar;
			if ($a[$SATempVar] == $b[$SATempVar]) return 0;
			return ($a[$SATempVar] > $b[$SATempVar]) ? -1 : 1;
		}
	}

	$SATempVar = $coll;

	if(!$OnDecrease){
		usort($array, 'SorterUp');
	}else{
		usort($array, 'SorterDown');
	}
	unset($SATempVar);
}

/**
 * Создаёт запрос значений для базы данных.
 * @return String : Запрос типа Values
 * @example Values('a','b','c') => "'a','b','c'";
 */
function Values(){
	$args = func_get_args();
	if(is_array($args[0])){
		$args = $args[0];
	}
	$result = '';
	foreach($args as $var){
		$result .= ",'".$var."'";
	}
	$result = substr($result,1);
	return $result;
}

#Переводит уровень в строку
function ViewLevelToStr($level,$s_admins='',$s_members='',$s_guests='',$s_all='')
{
	switch($level){
		case 1:	$s_admins=='' ? $vi='<font color="#FF0000">Админы</font>' : $vi=$s_admins;
		break;
		case 2:	$s_members=='' ? $vi='<font color="#0080FF">Пользователи</font>' : $vi=$s_members;
		break;
		case 3: $s_guests=='' ? $vi='<font color="#A0A000">Гости</font>' : $vi=$s_guests;
		break;
		case 4:	$s_all=='' ? $vi='<font color="#008000">Все</font>' : $vi=$s_all;
		break;
		default: $s_all=='' ? $vi='<font color="#008000">Все</font>' : $vi=$s_all;
	}
	return $vi;
}

//Создаст запрос базы данных чтобы получить только те объекты (данные),
// которые пользователь с данным доступом может видеть
function GetWhereByAccess($param_name, $user_access=null)
{
	if($user_access == null){
		global $user;
		$user_access = $user->AccessLevel();
	}
	$where = "`$param_name`='4'";
	if($user_access == '1'){//Администратор
		$where = '';
	}elseif($user_access == '2'){//Пользователь
		$where .= " or `$param_name`='2'";
	}else{//Гость
		$where .= " or `$param_name`='3'";
	}
	return $where;
}

function GetMicroTime()
{
	return microtime(true);
}

/**
 * Выводит дату в строковом формате
 *
 * @param Timestamp $time // Время timestamp
 * @return unknown
 */
function TimeRender($time, $full=true, $logic=true)
{
	global $config;
	if($time==false || !is_numeric($time)){
		return 'Нет данных';
	}
	$format = '';
	$now = time();
	$ld = round(($now / 86400) - ($time / 86400));
	if($ld>1 || $now<$time || !$logic){
		$fdate = 'd.m.Y';
	}elseif($ld==0){
		$fdate = 'Сегодня';
	}elseif($ld==1){
		$fdate = 'Вчера';
	}else{
		return 'Нет данных';
	}
	if($full){
		$date = date($fdate.' '.$config['general']['datetime_delemiter'].' H:i', $time);
	}else{
		$date = date($fdate,$time);
	}
	return $date;
}

/**
 * Определяет промежуток времени между двумя датами и выводит
 * результат в виде массива, где есть количество минут, часов, дней
 * прошедших между датами и их строковые обозначения.
 *
 * @param Time $runtime // Время старта в секундах
 * @param Time $endtime // Время остановки в секундах
 * @return array('days'=>Количество дней,'hours'=>Количество часов,'sdays'=>Обозначение дней,'shours'=>Обозначение часов)
 */
function TotalTime($runtime, $endtime)
{
	$right = $endtime - $runtime;
	if($right<0){return false;}

	$str = '';
	$days = floor($right / Day2Sec);

	$str2 = '';
	$hours = round(($right - $days * Day2Sec) / Hour2Sec);
	if($hours==24){
		$hours=0;
		$days++;
	}

	//Определяем количество дней
	$days2 = $days;
	if($days>19){$days = $days % 10;}
	if($days == 1){$str .= 'день';}
	elseif($days > 1 && $days <= 4){$str .= 'дня';}
	elseif(($days > 4 && $days <= 19) || $days == 0){$str .= 'дней';}

	//Определяем количество часов
	$hours2 = $hours;
	if($hours>19){$hours = $hours % 10;}
	if($hours == 1){$str2 = 'час';}
	elseif($hours > 1 && $hours <= 4){$str2 = 'часа';}
	elseif(($hours > 4 && $hours <= 19) || $hours == 0){$str2 = 'часов';}

	$str = $days2.' '.$str;
	$str2 = $hours2.' '.$str2;
	return array('days'=>$days2,'hours'=>$hours2,'sdays'=>$str,'shours'=>$str2);
}

/**
 * Извлекает из полного имени файла его расширение с точкой
 *
 * @param String $file // Полное имя файла
 * @return String
 */
function GetFileExt($file){
	$pos = strrpos($file,'.');
	if(!($pos===false)){
		return substr($file,$pos);
	}else{
		return '';
	}
}

/**
 * Извлекает из полного имени файла его имя без расширения.
 *
 * @param Itring $Name // Полное имя файла
 * @return String
 */
function GetFileName($Name){
	$ext = strrpos($Name,".");
	return basename($Name,substr($Name,$ext));
}

/**
 * Рекурсивно обходит каталог и возвращает массив с относительными именами файлов просматриваемого каталога
 *
 * @param String $folder // Имя папки с последним слэшем
 * @param Boolean $use_subfolders // Искать в подпапках
 * @param Boolean $use_mask // Использовать маску поиска
 * @param String $mask // Маска поиска. Вы можете указать расширения (с точкой) через запятую.
 * @param Boolean $newSearch // Начать ли новый поиск (статическая переменная будет перезаписана)
 * @param String $parentf // Не обращайте внимания. Нужна для работы функции.
 * @return Array // Список найденных файлов
 */
function GetFiles( $folder, $use_subfolders = false, $use_mask = false, $mask = '', $newSearch = true, $parentf = '' )
{
	static $sfiles = array();
	if(!is_dir($folder)){
		return $sfiles;
	}
	if($newSearch){
		$sfiles = array();
	}
	$mask = strtolower($mask);
	if($parentf==''){
		$parentf = $folder;
	}
	$files = scandir($folder);
	foreach($files as $file){
		if(is_dir($folder.$file) && ($file != '.') && ($file != '..')){
			if($use_subfolders){
				GetFiles($folder.$file.'/', $use_subfolders, $use_mask, $mask, false, $parentf);
			}
		}elseif(is_file($folder.'/'.$file) && ($file != '.') && ($file != '..')){
			$ext = GetFileExt($file);
			if(!$use_mask || stripos($mask, strtolower($ext)) !== false){
				$rf = str_replace($parentf, '', $folder.$file);
				$sfiles[] = $rf;
			}
		}
	}
	return $sfiles;
}

/**
 * Возвращает список поддиректорий из заданной директории.
 *
 * @param String $folder Путь до папки с последним слешем.
 */
function GetFolders( $folder )
{
	$result = array();
	if(!is_dir($folder)){
		return $result;
	}

	$files = scandir($folder);
	foreach($files as $p){
		if(($p != ".") && ($p != "..")){
			if(is_dir($folder.$p)){
				$result[] = $p;
			}
		}
	}
	return $result;
}

function GetFolderSize( $folder )
{
	$file_size = 0;
	$files = scandir($folder);
	foreach($files as $file){
		if (($file!='.') && ($file!='..')){
			$f = $folder.'/'.$file;
			if(is_dir($f)){
				$file_size += GetFolderSize($f);
			}else{
				$file_size += filesize($f);
			}
		}
	}
	return $file_size;
}

# Возвращает ИП адрес пользователя
function getip()
{
	global $_SERVER, $config;
	if(!isset($config['info']['ip'])){
		if(isset($_SERVER['REMOTE_ADDR'])){
			$ip = $_SERVER['REMOTE_ADDR'];
		}elseif(isset($HTTP_SERVER_VARS['REMOTE_ADDR'])){
			$ip = $HTTP_SERVER_VARS['REMOTE_ADDR'];
		}elseif(getenv('REMOTE_ADDR')){
			$ip = getenv('REMOTE_ADDR');
		}
		if($ip!=""){
			if(preg_match("/^([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/",$ip,$ipm)){
				$private = array("/^0\./","/^127\.0\.0\.1/","/^192\.168\..*/","/^172\.16\..*/"
				,"/^10..*/","/^224..*/","/^240..*/");
				$ip = preg_replace($private,$ip,$ipm[1]);
			}
		}
		if (strlen($ip)>16) $ip = substr($ip, 0, 16);
		return $config['info']['ip'] = $ip;
	}else{
		return $config['info']['ip'];
	}
}

/**
 * Проверяет адрес электронной почты на корректность
 *
 * @param String $email // e-mail адрес
 * @return Boolean
 */
function CheckEmail( $email )
{
	return (
		preg_match(
			'/^[-!#$%&\'*+\\.\/0-9=?A-Z^_`{|}~]+@([-0-9A-Z]+\.)+([0-9A-Z]){2,4}$/i'
			,trim($email)
		)
	);
}

function CheckUserEmail( $Email, &$error_out, $CheckExist=false, $xor_id=0 )
{
	global $db, $config;
	if($Email == ''){
		$error_out[] = 'Вы не ввели ваш E-mail адрес.';
		return false;
	}
	if(!CheckEmail($Email)){
		$error_out[] = 'Не правильный формат E-mail. Он должен быть вида: <b>domain@host.ru</b> .';
		return false;
	}
	if($CheckExist){
		$db->Select('users', "`email`='$Email'".($xor_id<>0?' and `id`<>'.$xor_id:''));
		if($db->NumRows() > 0){
			$error_out[] = 'Пользователь с таким E-mail уже зарегистрирован !';
			$result = false;
		}
	}
	return true;
}

/**
 * Проверяет логин на корректность
 *
 * @param String $login // Логин
 * @param $error_out // Переменная в которую произвести вывод ошибок
 * @param $CheckExist // Произвести проверку на занятость логина
 * @return Boolean // Истина если логин верный
 */
function CheckLogin( $login, &$error_out, $CheckExist=false, $xor_id=0 )
{
	global $db, $config;
	$result = true;
	if(isset($config['user']['login_min_length'])){
		$minlength = $config['user']['login_min_length'];
	}else{
		$minlength = 4;
	}
	if(strlen($login) < $minlength || strlen($login)>15){
		$error_out[] = 'Логин должен быть не менее '.$minlength.' и не более 15 символов.';
		$result = false;
	}
	if(preg_match('/[^a-zA-Zа-яА-Я0-9_]/', $login)){
		$error_out[] = 'Ваш логин должен состоять только из русских или латинских букв, цифр и символов подчеркивания.';
		$result = false;
	}
	if($CheckExist){
		$db->Select('users',"`login`='$login'".($xor_id<>0?' and `id`<>'.$xor_id:''));
		if($db->NumRows()>0){
			$error_out[] = 'Пользователь с таким логином уже зарегистрирован !';
			$result = false;
		}
	}
	return $result;
}

/**
 * Проверяет никнейм на корректность
 *
 * @param String $nikname // Никнейм
 * @param $error_out // Переменная в которую произвести вывод ошибок
 * @param $CheckExist // Произвести проверку на занятость логина
 * @return Boolean // Истина если пароль верный
 */
function CheckNikname( $nikname, &$error_out, $CheckExist=false, $xor_id=0 )
{
	global $db, $config;
	$result = true;
	if($nikname == ''){
		$error_out[] = 'Вы не ввели Имя!';
		$result = false;
	}
	if(preg_match("/[^a-zA-Zа-яА-Я0-9_ ]/",$nikname)){
		$error_out[] = 'Ваше имя должно состоять только из русских или латинских букв и цифр, символов подчеркивания и пробела.';
		$result = false;
	}
	if($CheckExist){
		$db->Select('users',"`name`='$nikname'".($xor_id<>0?' and `id`<>'.$xor_id:''));
		if($db->NumRows()>0){
			$error_out[] = 'Пользователь с таким именем уже зарегистрирован !';
			$result = false;
		}
	}
	return $result;
}

/**
 * Проверяет пароль на корректность
 *
 * @param String $pass // Пароль
 * @param $error_out // Переменная в которую произвести вывод ошибок (массив)
 * @return Boolean // Истина если пароль верный
 */
function CheckPass($pass,&$error_out)
{
	global $config;
	$result = true;
	if(isset($config['user']['pass_min_length'])){
		$minlength = $config['user']['pass_min_length'];
	}else{
		$minlength = 4;
	}
	if($pass<>'' && (strlen($pass) < $minlength || strlen($pass)>255)){
		$error_out[] = 'Пароль должен быть не короче '.$minlength.' символов.';
		$result = false;
	}
	return $result;
}

/**
 * Возвращает случайную строку произвольной длины
 *
 * @param Integer $length // Длинна строки
 * @return String
 */
function GenRandomString($length, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789')
{
	srand((double)microtime()*1000000);
	$char_length = (strlen($chars)-1);
	$rstring = '';
	for($i=0;$i<$length;$i++){
		$rstring.= $chars[rand(0,$char_length)];
	}
	return $rstring;
}

/**
 * Генерирует легко запоминающийся пароль
 *
 * @param Integer $length // Длина пароля
 * @return String
 */
function GenBPass($length)
{
	srand((double)microtime()*1000000);
	$password = '';
	$vowels = array('a','e','i','o','u');
	$cons = array('b','c','d','g','h','j','k','l','m','n','p','r','s','t','u','v','w','tr','cr','br','fr','th','dr','ch','ph','wr','st','sp','sw','pr','sl','cl');
	$num_vowels = count($vowels);
	$num_cons = count($cons);
	for($i=0;$i<$length;$i++){
		$password .= $cons[rand(0,$num_cons-1)].$vowels[rand(0,$num_vowels-1)];
	}
	return substr($password,0,$length);
}

/**
 * Отправляет E-mail
 */
function SendMail( $ToName, $ToEmail, $Subject, $Text, $Html=false, $From='', $FromEmail='' )
{
	global $config;
	$mail = LmEmailExtended::Instance();

	if($From == '' && $FromEmail == ''){
		$mail->SetFrom($config['general']['site_email'], Cp1251ToUtf8($config['general']['site_name']));
	}else{
		$mail->SetFrom($FromEmail, Cp1251ToUtf8($From));
	}
	$mail->SetSubject(Cp1251ToUtf8($Subject));

	if(!$Html){
		$mail->AddTextPart(Cp1251ToUtf8($Text));
	}else{
		$mail->AddHtmlPart(Cp1251ToUtf8($Text));
	}

	$mail->AddTo($ToEmail, Cp1251ToUtf8($ToName));
	if(!$mail->Send()){
		 error_handler(USER_ERROR, $mail->ErrorMessage, __FILE__);
	}
}

/**
 * Посылает команду удалённой машине
 * перейти по указанному адресу.
 * Рекомендуется использовать вместо Header('Location: ...');
 * @param String $address // Адрес перехода.
 */
function GO( $address, $exit = true, $response_code = 303 ){
	if($address == '') return;
	if(!defined('ERROR_HANDLER') || count(System::$Errors) == 0){ // todo Учитывать значение настройки вывода ошибок в браузер
		if($response_code == 302){
			Header('Location: '.$address);
		}else{
			Header('Location: '.$address, true, $response_code);
		}
		if($exit){
			exit;
		}
	}
}

function GoBack( $exit = true, $response_code = 303 )
{
	if(isset($_SERVER['HTTP_REFERER'])){
		GO($_SERVER['HTTP_REFERER'], $exit, $response_code);
	}else{
		GO(Ufu('index.php'), $exit, $response_code);
	}
}

// Перенаправляет пользователя на страницу на которой он был заданное число переходов ранее
// Если в качестве значения параметра $BackSteps передать единицу, то работа функции будет аналогична функции GoBack()
// Максимальное значение $BackSteps равно девяти.
function HistoryGoBack( $BackSteps, $exit = true, $response_code = 303 )
{
	global $user;
	$history = $user->Get('HISTORY');
	if(isset($history[10-$BackSteps])){
		GO($history[10-$BackSteps], $exit, $response_code);
	}
}

function HistoryGetUrl( $BackSteps )
{
	global $user;
	$history = $user->Get('HISTORY');
	if(isset($history[10-$BackSteps])){
		return $history[10-$BackSteps];
	}else{
		return '';
	}
}

/**
 * Сохраняет адрес в сессии и возвращает идентификатор
 * @param <type> $Url
 * @return <type>
 */
function SaveRefererUrl( $Url = '' )
{
	if($Url == ''){
		$Url = HistoryGetUrl(1);
	}
	$id = GenRandomString(10);
	$_SESSION['saved_urls'][$id] = $Url;
	return $id;
}

/**
 * Выполняет перенаправление по сохраненному в сессии адресу
 * @param <type> $id
 */
function GoRefererUrl( $id )
{
	if(isset($_SESSION['saved_urls'][$id])){
		$url = $_SESSION['saved_urls'][$id];
		unset($_SESSION['saved_urls'][$id]);
		GO($url);
	}else{
		GO(HistoryGetUrl(2));
	}
}

function GetRefererUrl( $id )
{
	if(isset($_SESSION['saved_urls'][$id])){
		$url = $_SESSION['saved_urls'][$id];
		unset($_SESSION['saved_urls'][$id]);
		return $url;
	}else{
		return HistoryGetUrl(2);
	}
}

/**
 * Определяет местоположение строки которая встречается несколько раз.
 *
 * @param String $str // Где искать
 * @param String $needle // Что искать
 * @param Integer $searchWhere // Какой номер вхождения нужен
 * @param Integer $offset // С какого символа начинать поиск
 * @return Integer // Номер символа
 */
function StrPosEx($str,$needle,$searchWhere=1,$offset=0)
{
	for($i=1;$i<=$searchWhere;$i++){
		$offset = strpos($str,$needle,$offset);
		if($offset===false){
			$offset = strlen($str);
			break;
		}else{
			$offset++;
		}
	}
	return $offset;
}

/**
 * Вставляет / Заменяет в определённой области строки. Результат передается по ссылке.
 *
 * @param String $str // Строка или массив
 * @param Integer $selstart // Начало области
 * @param Integer $sellength // Длина области
 * @param String $needle // Что вставить / заменить
 */
function InsertToStr( &$str, $selstart, $sellength, $needle )
{
	if(is_array($str)){
		for($i=0,$cnt=count($str);$i<$cnt;$i++){
			$left = substr($str[$i],0,$selstart);
			$right = substr($str[$i],$selstart+$sellength);
			$str[$i] = $left.$needle.$right;
		}
	}else{
		$left = substr($str,0,$selstart);
		$right = substr($str,$selstart+$sellength);
		$str = $left.$needle.$right;
	}
}

/**
 * Удаляет область строки
 *
 * @param String $str // Строка
 * @param Integer $selstart // Начало области
 * @param Integer $sellength // Длина области
 */
function DeleteFromStr( &$str, $selstart, $sellength )
{
	$left = substr($str,0,$selstart);
	$right = substr($str,$selstart+$sellength);
	$str = $left.$right;
}

function GetRatingImage( $votes_amount, $votes )
{
	$default = 'images/rating_system/rating.gif';
	if($votes_amount==0){
		return $default;
	}
	$rating = round($votes/$votes_amount);
	if($rating>=1 && $rating<=5){
		return 'images/rating_system/rating'.$rating.'.gif';
	}else{
		return $default;
	}
}

function FormatFileSize($size, $sizeType = 'b')
{
	if($sizeType == 'b'){
		$mb = 1024*1024;
		if($size>$mb){$size = sprintf("%01.2f",$size/$mb).' Мб';
		}elseif($size>=1024){$size = sprintf("%01.2f",$size/1024).' Кб';
		}else{$size = $size.' Байт';}
	}else{
		if($sizeType == 'k'){
			$size = $size.' Кб';
		}elseif($sizeType == 'm'){
			$size = $size.' Мб';
		}else{
			$size = $size.' Гб';
		}
	}
	return $size;
}

//Вызывается при запросе несуществующей
//страницы или ошибки и использования спецсимволов в параметрах
function HackOff($LowProtect=false, $redirect=true)
{
	global $user, $config;
	if($user->isAdmin() || $LowProtect){
		if(defined('MAIN_SCRIPT') || defined('PLUG_SCRIPT') || !defined('ADMIN_SCRIPT')){
			if($redirect){
				GO(Ufu('index.php'));
			}
		}elseif(defined('ADMIN_SCRIPT')){
			GO($config['admin_file']);
		}
	}else{
		if($config['security']['hack_event'] == 'alert'){
			die($config['security']['hack_alert']);
		}elseif($config['security']['hack_event'] == 'ban'){
			die('Вам был запрещен доступ к сайту, возможно система обнаружила подозрительные
			действия с Вашей стороны. Если Вы считаете, что это произошло по ошибке, - обратитесь
			в службу поддержки по e-mail '.$config['general']['site_email'].'.');
		}else{
			if($redirect){
				GO(Ufu('index.php'));
			}
		}
	}
}

function RealPath2($path)
{
	$path = str_replace('\\', '/',$path);
	$path = str_replace(array('../','./'),'',$path);
	$parr = explode('/',$path);
	$pcnt = count($parr);
	for($i=0;$i<$pcnt;$i++){
		if($i<>$pcnt-1){
			if($parr[$i]<>''){
				$parr[$i] = str_replace('.','',$parr[$i]);
			}else{
				unset($parr[$i]);
			}
		}
	}
	$path = implode('/',$parr);
	if($pcnt>1){
		if((substr($path, 0, 1) == '/')){
			$path = substr($path, 1);
		}
	}
	return $path;
}

function GDVersion()
{
	global $config;
	if(!isset($config['info']['gd'])){
		if(!extension_loaded('gd')){
			return ($config['info']['gd'] = 0);
		}
		if(function_exists('gd_info')){
			$ver_info = gd_info();
			preg_match('/\d/', $ver_info['GD Version'],$match);
			$config['info']['gd'] = $match[0];
			return $match[0];
		}else{
			return ($config['info']['gd'] = 0);
		}
	}else{
		return $config['info']['gd'];
	}
}

function AdminImageControl( $Title, $LoadTitle, $FileName, $Dir, $Name = 'image', $LoadName = 'up_image', $FormName = 'edit_form' )
{
	global $site;

	$max_file_size = ini_get('upload_max_filesize');

	$images_data = array();
	$Dir = RealPath2($Dir);

	$images = array();
	$images = GetFiles($Dir,false,true,'.gif.png.jpeg.jpg');
	$images[-1] = 'no_image/no_image.png';
	$site->DataAdd($images_data,$images[-1],'Нет картинки',($FileName == ''));

	$selindex = -1;
	for($i=0,$c=count($images)-1;$i<$c;$i++){
		if($FileName == $images[$i]){
			$sel = true;
			$selindex = $i;
		}else{
			$sel = false;
		}
		$site->DataAdd($images_data,$images[$i],$images[$i],$sel);
	}

	$select = $site->Select($Name,$images_data,false,'onchange="document.'.$FormName.'.iconview.src=\''.$Dir.'\'+document.'.$FormName.'.'.$Name.'.value;"');

	$ctrl = <<<HTML
<table cellspacing="0" cellpadding="0" border="0" width="100%">
	<tr>
		<td valign="top" style="border-bottom:none;">$select</td>
	</tr>
	<tr>
		<td style="border-bottom:none; padding-top: 5px;" width="100%" align="left"><img height="80" id="iconview" src="$Dir{$images[$selindex]}"></td>
	</tr>
</table>
HTML;


	FormRow($Title, $ctrl);
	FormRow($LoadTitle, $site->FFile($LoadName).'<br /><small>Формат изображений только *.jpg,*.jpeg,*.gif,*.png</small><br /><small>Максимальный размер файла: '.$max_file_size.'</small>');
}

function CreateThumb( $SrcFileName, $DstFileName, $MaxWidth, $MaxHeight )
{
	global $config;
	if(is_file($DstFileName)){
		unlink($DstFileName);
	}
	include_once($config['inc_dir'].'picture.class.php');
	$thumb = new TPicture($SrcFileName);
	$thumb->SetImageSize($MaxWidth, $MaxHeight);
	$thumb->SaveToFile($DstFileName);
}

function LoadImage($PostName, $Dir, $ThumbsDir, $MaxWidth, $MaxHeight, $Default, &$Error, $CreateThumbs = true, $OriginalOptimization = false, $OriginalMaxWidth = 800, $OriginalMaxHeight = 600)
{
	global $config;
	$Error = false;
	if($Default == 'no_image/no_image.png') {
		$Default = '';
	}

	$alloy_mime = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png');
	$alloy_exts = array('.gif', '.jpg', '.jpeg', '.png');
	if(isset($_FILES[$PostName]) && file_exists($_FILES[$PostName]['tmp_name'])){
		if(in_array($_FILES[$PostName]['type'], $alloy_mime) && in_array(strtolower(GetFileExt($_FILES[$PostName]['name'])), $alloy_exts)) {
			$file_name = Translit($_FILES[$PostName]['name'], true);
			if(!is_dir($Dir)) {
				mkdir($Dir, 0755);
			}
			$ext = GetFileExt($file_name);
			$name = GetFileName($file_name);
			$i = 1;
			while(is_file($Dir.$file_name)) {
				$i++;
				$file_name = $name.'_'.$i.$ext;
			}
			$FileName = $Dir.$file_name;
			$ThumbFileName = $ThumbsDir.$file_name;
			if(!$OriginalOptimization){
				copy($_FILES[$PostName]['tmp_name'], $FileName);
			}else{
				CreateThumb($_FILES[$PostName]['tmp_name'], $FileName, $OriginalMaxWidth, $OriginalMaxHeight);
			}
			if($CreateThumbs) {
				if(!is_dir($ThumbsDir)) {
					mkdir($ThumbsDir, 0755);
				}
				CreateThumb($FileName, $ThumbFileName, $MaxWidth, $MaxHeight);
			}
			$result = $file_name;
		} else {
			$Error = true;
			return RealPath2(SafeEnv($Default, 255, str));
		}
	} else {
		$result = RealPath2(SafeEnv($Default, 255, str));
	}
	return $result;
}

function ImageSize( $FileName )
{
	$size = getimagesize($FileName);
	$size['width'] = $size[0];
	$size['height'] = $size[1];
	return $size;
}

// Функция преобразует значение к типу boolean.
// Её отличие в приведении строк. Любая непустая строка даст true.
function GetBoolValue($var){
	if(is_string($var)){
		if(strlen($var)==0){
			$r = false;
		}else{
			$r = true;
		}
	}else{
		$r = (bool)$var;
	}
	return $r;
}

/**
 * Транслитерация ГОСТ 7.79-2000
 * @param <type> $text
 * @return <type>
 */
function Translit($text, $strip_spaces = true)
{
	if($strip_spaces) {
		$text = str_replace(' ', '_', $text);
	}
	$text = str_replace(' ', '_', $text);
	$text = strtr($text, array(
		'а' => 'a', 'А' => 'A',
		'б' => 'b', 'Б' => 'B',
		'в' => 'v', 'В' => 'V',
		'г' => 'g', 'Г' => 'G',
		'д' => 'd', 'Д' => 'D',
		'е' => 'e', 'Е' => 'E',
		'ё' => 'yo', 'Ё' => 'YO',
		'ж' => 'zh', 'Ж' => 'ZH',
		'з' => 'z', 'З' => 'Z',
		'и' => 'i', 'И' => 'I',
		'й' => 'j', 'Й' => 'J',
		'к' => 'k', 'К' => 'K',
		'л' => 'l', 'Л' => 'L',
		'м' => 'm', 'М' => 'M',
		'н' => 'n', 'Н' => 'N',
		'о' => 'o', 'О' => 'O',
		'п' => 'p', 'П' => 'P',
		'р' => 'r', 'Р' => 'R',
		'с' => 's', 'С' => 'S',
		'т' => 't', 'Т' => 'T',
		'у' => 'u', 'У' => 'U',
		'ф' => 'f', 'Ф' => 'F',
		'х' => 'x', 'Х' => 'X',
		'ц' => 'c', 'Ц' => 'C',
		'ч' => 'ch', 'Ч' => 'CH',
		'ш' => 'sh', 'Ш' => 'SH',
		'щ' => 'shh', 'Щ' => 'SHH',
		'ъ' => '``', 'Ъ' => '``',
		'ы' => 'y\'', 'Ы' => 'Y\'',
		'ь' => '`', 'Ь' => '`',
		'э' => 'e`', 'Э' => 'E`',
		'ю' => 'yu', 'Ю' => 'YU',
		'я' => 'ya', 'Я' => 'YA',
	    )
	);
	return $text;
}

/**
 * Ретранслитерация ГОСТ 7.79-2000
 * @param <type> $text
 * @return <type>
 */
function Retranslit($text, $strip_tospaces = true)
{
	if($strip_tospaces){
		$text = str_replace('_', ' ', $text);
	}
	$text = strtr($text, array(
		'a' => 'а', 'A' => 'А',
		'b' => 'б', 'B' => 'Б',
		'v' => 'в', 'V' => 'В',
		'g' => 'г', 'G' => 'Г',
		'd' => 'д', 'D' => 'Д',
		'e' => 'е', 'E' => 'Е',
		'yo' => 'ё', 'YO' => 'Ё',
		'zh' => 'ж', 'ZH' => 'Ж',
		'z' => 'з', 'Z' => 'З',
		'i' => 'и', 'I' => 'И',
		'j' => 'й', 'J' => 'Й',
		'k' => 'к', 'K' => 'К',
		'l' => 'л', 'L' => 'Л',
		'm' => 'м', 'M' => 'М',
		'n' => 'н', 'N' => 'Н',
		'o' => 'о', 'O' => 'О',
		'p' => 'п', 'P' => 'П',
		'r' => 'р', 'R' => 'Р',
		's' => 'с', 'S' => 'С',
		't' => 'т', 'T' => 'Т',
		'u' => 'у', 'U' => 'У',
		'f' => 'ф', 'F' => 'Ф',
		'x' => 'х', 'X' => 'Х',
		'c' => 'ц', 'C' => 'Ц',
		'ch' => 'ч', 'CH' => 'Ч',
		'sh' => 'ш', 'SH' => 'Ш',
		'shh' => 'щ', 'SHH' => 'Щ',
		'``' => 'ъ',
		'y\'' => 'ы', 'Y\'' => 'Ы',
		'`' => 'ь',
		'e`' => 'э', 'E`' => 'Э',
		'yu' => 'ю', 'YU' => 'Ю',
		'ya' => 'я', 'YA' => 'Я',
	    )
	);
	return $text;
}

#Выводит массив с информацией об установленных модулях
function GetModuleList()
{
	global $db;
	$db->Select('modules','');
	$r = array();
	while($mod = $db->FetchRow()){
		$r[SafeDB(RealPath2($mod['folder']), 255, str)] = $mod;
	}
	return $r;
}

function Cp1251ToUtf8( $String ){
	return iconv("windows-1251", "utf-8//IGNORE//TRANSLIT", $String);
}

function Utf8ToCp1251( $Unicode ){
	return iconv("utf-8", "windows-1251", $Unicode);
}

function SCoincidence($text, $search){
	$swords = explode(' ',$search);
	$text = strip_tags($text);
	$result_text = '';
	$set_text = false;
	foreach($swords as $search){
		$pos = stripos($text, $search);
		if(is_integer($pos)){ // Если нашли слово
			$slen = strlen($search);
			if(!$set_text){ // Обрезаем текст по этому слову если оно первое найденное
				$result_length = 124;
				$start_str = '';
				$end_str = '';
				if($pos - $result_length < 0){
					$start = 0;
				}else{
					$start = $pos - $result_length;
					$start_str = ' ... ';
				}
				if($start + $result_length*2 > strlen($text)){
					$length = strlen($text) - $start;
				}else{
					$length = $result_length*2;
					$end_str = ' ... ';
				}
				$result_text = $start_str.substr($text, $start, $length).$end_str;
				$set_text = true;
			}
			// Подсвечиваем
			$pos = stripos($result_text, $search);
			while(is_integer($pos)){
				$start_str = substr($result_text, 0, $pos);
				$end_str = substr($result_text, $pos+$slen, strlen($result_text)-$pos-$slen);
				$search = substr($result_text, $pos, $slen);
				$result_text = $start_str.'<b>'.$search.'</b>'.$end_str;
				$pos = stripos($result_text, $search, $pos+$slen+7);
			}
		}
	}
	return $result_text;
}

function SSearch($text, $search){
	if($search == ''){
		return false;
	}
	$swords = explode(' ',$search);
	$text = strip_tags($text);
	foreach($swords as $search){
		if(stristr($text, $search) === false){
			return false;
		}
	}
	return true;
}

// Удаляет протокол у http ссылок.
function Url( $url ){
	$url = preg_replace('/^https:\/\//', '', $url);
	$url = preg_replace('/^http:\/\//', '', $url);
	$url = preg_replace('/^www\./', '', $url);
	return $url;
}

// Проверяет является ли ссылка внутренней.
function IsMainHost( $url ){
	$host = $_SERVER['HTTP_HOST'];
	if(stristr(Url($url), Url($host))) {
		return true;
	}else{
		return false;
	}
}

//Код проверяет ссылку - если это ссылка на страницы своего же сайта
// - то редирект не используется.
//Если это ссылка на внешние ресурсы (другие сайты) - то редирект включается
//(при включённой опции "Промежуточная страница для внешних ссылок").
function UrlRender( $url ){
	global $config;
	if($config['general']['specialoutlinks']) {
		if(!IsMainHost($url)){
			return 'index.php?name=plugins&p=out&url='.urlencode(Url($url));
		}else{
			return 'http://'.Url($url);
		}
	}else{
		return 'http://'.Url($url);
	}
}

/**
 * Возвращает относительную директорию, в которую установлен сайт.
 * @return String
 * @since 1.3.3
 */
function GetSiteDir( $EndSlash = true ){
	$dir = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
	if(substr($dir, -1) != '/' && $EndSlash){
		$dir .= '/';
	}elseif(substr($dir, -1) == '/' && !$EndSlash){
		$dir = substr($dir, 0, -1);
	}
	return $dir;
}

/**
 * Возвращает домен сайта
 * @return string
 */
function GetSiteDomain(){
	return getenv("HTTP_HOST");
}

/**
 * Возвращает адрес сайта
 * @return void
 */
function GetSiteHost(){
	$host = 'http://'.GetSiteDomain();
	if(substr($host, -1) == '/'){
		$host = substr($host, 0, -1);
	}
	return $host;
}

/**
 * Возвращает URL сайта с относительной директорией в которую установлен сайт.
 * @return String
 * @since 1.3.3
 */
function GetSiteUrl( $EndSlash = true ){
	return GetSiteHost().GetSiteDir($EndSlash);
}

/**
 * Кодирует объект в формат JSON
 * @param  $value
 * @return string
 * @since 1.3.5
 */
function JsonEncode( $value ){
	return json_encode(ObjectCp1251ToUtf8($value));
}

/**
 * Декодирует объект из строки в формате JSON
 * @param  $json
 * @return mixed
 * @since 1.3.5
 */
function JsonDecode( $json ){
	return json_decode($json);
}

/**
 * Преобразует строки объекта или массива в кодировку UTF-8
 * @param  $var
 * @return array|string
 * @since 1.3.5
 */
function ObjectCp1251ToUtf8( &$var ){
	if(is_array($var)){
		foreach($var as &$v){
			$v = ObjectCp1251ToUtf8($v);
		}
	}elseif(is_object($var)){
		$vars = get_object_vars($var);
		foreach($vars as $f=>&$v) {
			$var->$f = ObjectCp1251ToUtf8($v);
		}
	}elseif(is_string($var)){
		$var = Cp1251ToUtf8($var);
	}
	return $var;
}

function BbCodeTag( $tag, $part ){
	static $first_run = true;
	if($first_run){
		$first_run = false;
		ini_set('highlight.string', '#008800');
		ini_set('highlight.comment', '#969696');
		ini_set('highlight.keyword', '#0000DD');
		ini_set('highlight.default', '#444444');
		ini_set('highlight.html', '#0000FF');
	}
	switch($tag){
		case 'php':
			$part = str_replace('<br />', '', $part);
			$part = htmlspecialchars_decode($part);
			if(substr($part, 0, 2) != '<?'){
				$part = "<?\n".$part."\n?>";
			}
			$part = '<div class="bbcode_php">'.highlight_string($part, true).'</div>';
			break;
	}
	return $part;
}

/**
 * Парсер ББ кодов
 * @param  $text
 * @return
 */
function BbCodePrepare( $text ){
	$preg =
		array
		(
			'~\[s\](.*?)\[\/s\]~si' => '<del>$1</del>',
			'~\[b\](.*?)\[\/b\]~si' => '<strong>$1</strong>',
			'~\[i\](.*?)\[\/i\]~si' => '<em>$1</em>',
			'~\[u\](.*?)\[\/u\]~si' => '<u>$1</u>',
			'~\[color=(.*?)\](.*?)\[\/color\]~si' => '<span style="color:$1;">$2</span>',
			'~\[size=(.*?)\](.*?)\[\/size\]~si' => '<span style="font-size:$1px;">$2</span>',
			'~\[div=(.*?)\](.*?)\[\/div\]~si' => '<div style="$1">$2</div>',
			'~\[p=(.*?)\](.*?)\[\/p\]~si' => '<p style="$1">$2</p>',
			'~\[span=(.*?)\](.*?)\[\/span\]~si' => '<span style="$1">$2</span>',
			'~\[left (.*?)\](.*?)\[\/left\]~si' => '<div style="text-align: left; $1">$2</div>',
			'~\[left\](.*?)\[\/left\]~si' => '<div style="text-align: left;">$1</div>',
			'~\[right (.*?)\](.*?)\[\/right\]~si' => '<div style="text-align: right; $1">$2</div>',
			'~\[right\](.*?)\[\/right\]~si' => '<div style="text-align: right;">$1</div>',
			'~\[center (.*?)\](.*?)\[\/center\]~si' => '<div style="text-align: center; $1">$2</div>',
			'~\[center\](.*?)\[\/center\]~si' => '<div style="text-align: center;">$1</div>',
			'~\[justify\](.*?)\[\/justify\]~si' => '<p style="text-align: justify;">$1</p>',
			'~\[pleft\](.*?)\[\/pleft\]~si' => '<p style="text-align: left;">$1</p>',
			'~\[pright\](.*?)\[\/pright\]~si' => '<p style="text-align: right;">$1</p>',
			'~\[pcenter\](.*?)\[\/pcenter\]~si' => '<p style="text-align: center;">$1</p>',
			'~\[br\]~si' => '<br clear="all">',
			'~\[hr\]~si' => '<hr color="#B5B5B5">',
			'~\[line\]~si' => '<hr>',
			'~\[table\]~si' => '<div><table border="1" cellspacing="1" cellpadding="1" width="50%" style="margin:10px;  float:left;" >',
			'~\[\/table\]~si' => '</table></div>',
			'~\[tr\]~si' => '<tr>',
			'~\[\/tr\]~si' => '</tr>',
			'~\[td\]~si' => '<td style="padding:10px;">',
			'~\[\/td\]~si' => '</td>',
			'~\[th\]~si' => '<th>',
			'~\[\/th\]~si' => '</th>',
			'~\[\*\](.*?)\[\/\*\]~si' => '<li>$1</li>',
			'~\[\*\]~si' => '<li>',
			'~\[ul\](.*?)\[\/ul\]~si' => "<ul>$1</li></ul>",
			'~\[list\](.*?)\[\/list\]~si' => "<ul>$1</li></ul>",
			'~\[ol\](.*?)\[\/ol\]~si' => '<ol>$1</li></ol>',
			'~\[php\](.*?)\[\/php\]~sei' => "'<span>'.BbCodeTag('php', '$1').'</span>'",
			'~\[hide\](.*?)\[\/hide\]~sei' => "'<div class=\"bbcode_hide\"><a href=\"javascript:onclick=ShowHide(\''.strlen(md5('$1')).substr(md5('$1'),0,3).'\')\">Скрытый текст</a><div id=\"'.strlen(md5('$1')).substr(md5('$1'),0,3).'\" style=\"visibility: hidden; display: none;\">$1</div></div>'",
			'~\[h1\](.*?)\[\/h1\]~si' => '<h1>$1</h1>',
			'~\[h2\](.*?)\[\/h2\]~si' => '<h2>$1</h2>',
			'~\[h3\](.*?)\[\/h3\]~si' => '<h3>$1</h3>',
			'~\[h4\](.*?)\[\/h4\]~si' => '<h4>$1</h4>',
			'~\[h5\](.*?)\[\/h5\]~si' => '<h5>$1</h5>',
			'~\[h6\](.*?)\[\/h6\]~si' => '<h6>$1</h6>',
			'~\[video\](.*?)\[\/video\]~sei' => "'<CENTER><div>'.strip_tags(htmlspecialchars_decode('$1'), '<object><param><embed>').'</div></CENTER>'",
			'~\[code\](.*?)\[\/code\]~si' => '<div class="bbcode_code"><code>$1</code></div>',
			'~\[email\](.*?)\[\/email\]~sei' => "AntispamEmail('$1')",
			'~\[email=(.*?)\](.*?)\[\/email\]~sei' => "'<a rel=\"noindex\" href=\"mailto:'.str_replace('@', '.at.','$1').'\">$2</a>'",
			'~\[url\](.*?)\[\/url\]~sei' => "'<a href=\"'.UrlRender('$1').'\" target=\"_blank\">$1</a>'",
			'~\[url=(.*?)?\](.*?)\[\/url\]~sei' => "'<a href=\"'.UrlRender('$1').'\"target=\"_blank\">$2</a>'",
			'~\[img=(.*?)x(.*?)\](.*?)\[\/img\]~si' => '<img src="$3" style="width: $1px; height: $2px" >',
			'~\[img (.*?)\](.*?)\[\/img\]~si' => '<img src="$2" title="$1" alt="$1">',
			'~\[img\](.*?)\[\/img\]~si' => '<a href="$1" target="_blank"><img src="$1"></a>',
			'~\[quote\](.*?)\[\/quote\]~si' => '<div class="bbcode_quote">$1</div>',
			'~\[quote=(?:&quot;|"|\')?(.*?)["\']?(?:&quot;|"|\')?\](.*?)\[\/quote\]~si' => '<div class="bbcode_quote"><strong>$1:</strong>$2</div>',
		);
	$text = preg_replace(array_keys($preg), array_values($preg), $text);
	return $text;
}

/**
 * Включить вывод ошибок
 * @return void
 */
function ErrorsOn(){
	global $SITE_ERRORS;
	$SITE_ERRORS = true;
}

/**
 * Временно отключить вывод ошибок
 * @return void
 */
function ErrorsOff(){
	global $SITE_ERRORS;
	$SITE_ERRORS = false;
}

/**
 * Обработчик ошибок
 * @param  $No
 * @param  $Error
 * @param  $File
 * @param  $Line
 * @return void
 */
function ErrorHandler($No, $Error, $File, $Line = -1){
	global $ErrorsLog, $SITE_ERRORS;
	$errortype = array(
		1 => 'Ошибка', 2 => 'Предупреждение!', 4 => 'Ошибка разборщика', 8 => 'Замечание', 16 => 'Ошибка ядра', 32 => 'Предупреждение ядра!', 64 => 'Ошибка компиляции',
		128 => 'Предупреждение компиляции!', 256 => 'Пользовательская Ошибка', 512 => 'Пользовательскаое Предупреждение!', 1024 => 'Пользовательскаое Замечание', 2048 => 'Небольшое замечание',
		8192 => 'Устаревший код'
	);
	$Error = '<br /><b>'.$errortype[$No].'</b>: '.$Error.' в <b>'.$File.($Line > -1 ? '</b> на линии <b>'.$Line.'</b>' : '').'.<br />';
	if(!defined('SETUP_SCRIPT') && System::$config['debug']['log_errors'] == '1'){
		$ErrorsLog->Write($Error);
	}
	if($SITE_ERRORS && isset(System::$config['debug']['php_errors']) && System::$config['debug']['php_errors'] == '1'){
		System::$Errors[] = $Error."\n";
	}
}

/**
 * Очищает кэш плагинов
 * @return void
 */
function PluginsClearCache(){
	$cache = LmFileCache::Instance();
	$cache->Delete('system', 'plugins');
	$cache->Delete('system', 'plugins_auto_main');
	$cache->Delete('system', 'plugins_auto_admin');
	$cache->Delete('system', 'plugins_load');
}

/**
 * Выполняет поиск плагинов в папке plug_dir
 * @param bool $ClearCache
 * @return null|string
 */
function LoadPlugins($ClearCache = false){
	global $config;
	static $resultcache = null;

	if($ClearCache){
		$resultcache = null;
		PluginsClearCache();
	}

	if($resultcache != null){
		return $resultcache;
	}

	$cache = LmFileCache::Instance();
	if($cache->HasCache('system', 'plugins_load')){
		$resultcache = $cache->Get('system', 'plugins_load');
		return $resultcache;
	}

	$plugins = array(
	);
	$folder = $config['plug_dir'];
	$dir = @opendir($folder);
	while($file = @readdir($dir)){
		if($file != '.' && $file != '..' && is_dir($folder.$file) && is_file($folder.$file.'/info.php')){
			include(RealPath2($folder.$file.'/info.php'));
		}
	}

	// Плагины (добавляем информацию)
	foreach($plugins as $name => $plugin){
		$plugins[$name]['group'] = '';
		$plugins[$name]['name'] = $name;
	}
	$result['plugins'] = $plugins;

	// Группы (поиск плагинов группы) $groups загружается из файлов info.php
	foreach($groups as $name => $group){
		$plugins = array(
		);
		$folder = $config['plug_dir'].$name.'/';
		$dir = opendir($folder);
		while($file = readdir($dir)){
			if($file != '.' && $file != '..' && is_dir($folder.$file) && is_file($folder.$file.'/info.php')){
				include(RealPath2($folder.$file.'/info.php'));
			}
		}
		foreach($plugins as $pname => $plugin){
			$plugins[$pname]['group'] = $name;
			$plugins[$pname]['name'] = $pname;
		}
		$groups[$name]['plugins'] = $plugins;
	}
	$result['groups'] = $groups;

	$resultcache = &$result;
	$cache->Write('system', 'plugins_load', $result, Day2Sec);
	return $result;
}

/**
 * Подключает группу системных плагинов
 * @param  $group группа
 * @param string $function подгруппа(если есть)
 * @param bool $return возвратить имена файлов плагинов вместо их автоматического подключения
 * @param bool $return_full возвращать вместо имен файлов массив с полной информацией по плагинам
 * @return array
 */
function IncludeSystemPluginsGroup($group, $function = '', $return = false, $return_full = false){
	global $config;
	$plugins = LoadPlugins();
	$result = array();
	if(isset($plugins['groups'][$group])){
		$plugins = $plugins['groups'][$group]['plugins'];
		foreach($plugins as $plugin){
			if(($function == '') || (isset($plugin['function']) && $function == $plugin['function'])){
				global $include_plugin_path; // эта переменная будет доступна из плагина
				$include_plugin_path = RealPath2($config['plug_dir'].$group.'/'.$plugin['name'].'/');
				if($return){
					if($return_full){
						$plugin['path'] = $include_plugin_path;
						$result[] = $plugin;
					} else{
						$result[] = $include_plugin_path;
					}
				} else{
					include ($include_plugin_path.'index.php');
				}
			}
		}
	}
	return $result;
}

/**
 * Возвращает все группы настроек плагинов
 * @return array
 */
function PluginsConfigsGroups(){
	global $db;
	$result = array(
	);
	$db->Select('plugins_config_groups', '');
	while($group = $db->FetchRow()){
		$result[$group['name']] = $group;
	}
	return $result;
}

/**
 * Возвращает информацию по найденным в системе плагинам
 * @param bool $ClearCache Инициировать новый поиск
 * @return null|string
 */
function GetPlugins($ClearCache = false){
	global $config, $db;
	static $resultcache = null;

	if($ClearCache){
		$resultcache = null;
		PluginsClearCache();
	}

	if($resultcache != null)
		return $resultcache;
	$cache = LmFileCache::Instance();
	if($cache->HasCache('system', 'plugins')){
		$resultcache = $cache->Get('system', 'plugins');
		return $resultcache;
	}

	$install_plugins = array(
	); // Установленные плагины
	$install_groups = array(
	); // Установленные группы

	$plugins = $db->Select('plugins', '');
	foreach($plugins as $temp){
		if($temp['type'] == PLUG_MANUAL || $temp['type'] == PLUG_MANUAL_ONE){
			$install_groups[$temp['group']][$temp['name']] = true;
		} else{
			$install_plugins[$temp['name']] = true;
		}
	}

	$result = LoadPlugins($ClearCache);
	$groups = &$result['groups'];
	$plugins = &$result['plugins'];
	foreach($plugins as $name => $plugin){
		if(isset($plugins['type']) && $plugins['type'] == PLUG_SYSTEM){
			unset($plugins[$name]);
		} else{
			$plugins[$name]['installed'] = isset($install_plugins[$name]);
		}
	}
	foreach($groups as $name => $group){
		if(isset($groups[$name]['type']) && $groups[$name]['type'] == PLUG_SYSTEM){
			unset($groups[$name]);
		} else{
			foreach($group['plugins'] as $pname => $plugin){
				$groups[$name]['plugins'][$pname]['installed'] = isset($install_groups[$name][$pname]);
			}
		}
	}
	$resultcache = &$result;
	$cache->Write('system', 'plugins', $result);
	return $result;
}

/**
 * Удаляет плагин из базы данных
 * @param  $plugin_name
 * @param string $group
 * @return void
 */
function UninstallPlugin($plugin_name, $group = ''){
	global $config, $db;
	$name = $plugin_name;
	$plugins = GetPlugins();
	if($group != ''){
		if(isset($plugins['groups'][$group]['plugins'][$name]) && $plugins['groups'][$group]['plugins'][$name]['installed'] == true){
			$p = &$plugins['groups'][$group]['plugins'][$name];
			$uninstall_file = RealPath2($config['plug_dir'].$group.'/'.$name.'/'.'uninstall.php');
			if(file_exists($uninstall_file)){
				include_once ($uninstall_file);
			}
			$db->Delete('plugins', "`name`='$name' and `group`='$group'");
			PluginsClearCache();
		}
	} else{
		if(isset($plugins['plugins'][$name]) && $plugins['plugins'][$name]['installed'] == true){
			$p = &$plugins['plugins'][$name];
			$uninstall_file = RealPath2($config['plug_dir'].$name.'/'.'uninstall.php');
			if(file_exists($uninstall_file)){
				include_once ($uninstall_file);
			}
			$db->Delete('plugins', "`name`='".$name."'");
			PluginsClearCache();
		}
	}
}

/**
 * Удаляет группу плагинов из базы данных
 * @param  $group
 * @return void
 */
function UninstallGroup($group){
	global $db;
	$db->Delete('plugins', "`group`='$group'");
	PluginsClearCache();
}

/**
 * Устанавливает плагин или группу плагинов в базу данных
 * @param  $plugin_name
 * @param string $group
 * @return void
 */
function InstallPlugin($plugin_name, $group = ''){
	global $config, $db;
	$name = $plugin_name;
	$plugins = GetPlugins();
	if($group != ''){
		if(isset($plugins['groups'][$group]['plugins'][$name]) && $plugins['groups'][$group]['plugins'][$name]['installed'] == false){
			$p = &$plugins['groups'][$group]['plugins'][$name];
			if(!isset($p['config'])){
				$p['config'] = '';
			}
			if(($plugins['groups'][$group]['type'] == PLUG_MANUAL_ONE || $p['type'] == PLUG_MANUAL_ONE)){
				UninstallGroup($group);
			}
			$install_file = RealPath2($config['plug_dir'].$group.'/'.$name.'/'.'install.php');
			if(file_exists($install_file)){
				include_once ($install_file);
			}
			$vals = Values('', $name, SafeEnv($p['config'], 0, str), SafeEnv($p['type'], 1, int), $group);
			$db->Insert('plugins', $vals);
			PluginsClearCache();
		}
	} else{
		if(isset($plugins['plugins'][$name]) && $plugins['plugins'][$name]['installed'] == false){
			$p = &$plugins['plugins'][$name];
			if(!isset($p['config'])){
				$p['config'] = '';
			}
			$install_file = RealPath2($config['plug_dir'].$name.'/'.'install.php');
			if(file_exists($install_file)){
				include_once ($install_file);
			}
			$vals = Values('', SafeEnv($name, 250, str), SafeEnv($p['config'], 0, str), SafeEnv($p['type'], 1, int), SafeEnv($group, 250, str));
			$db->Insert('plugins', $vals);
			PluginsClearCache();
		}
	}
}

/**
 * Подключает группу плагинов
 * @param $group группа
 * @param string $function подгруппа
 * @param bool $return возвратить имена файлов плагинов вместо автоматического подкочения
 * @return array
 */
function IncludePluginsGroup($group, $function = '', $return = false){
	global $config, $db;
	$plugins = GetPlugins();
	$result = array(
	);
	if(isset($plugins['groups'][$group])){
		$plugins = $plugins['groups'][$group]['plugins'];
		foreach($plugins as $plugin){
			if(($plugin['installed'] && $function == '') || ($plugin['installed'] && isset($plugin['function']) && $function == $plugin['function'])){
				global $include_plugin_path; // эта переменная будет доступна из плагина
				$include_plugin_path = RealPath2($config['plug_dir'].$group.'/'.$plugin['name'].'/');
				if($return){
					$result[] = $include_plugin_path;
				} else{
					include ($include_plugin_path.'index.php');
				}
			}
		}
	}
	return $result;
}

// Пожключение системных плагинов
$plugins = IncludeSystemPluginsGroup('system', '', true);
foreach($plugins as $plugin){
	include($plugin.'index.php');
}

?>