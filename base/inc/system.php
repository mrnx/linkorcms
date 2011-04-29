<?php

# LinkorCMS 1.3
# � 2006-2010 ��������� �������� (linkorcms@yandex.ru)
# ����: system.php
# ����������: ���� �������

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

// ������
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

// �������
define('PLUGINS', true);
define('PLUG_AUTORUN', 1); //����������
define('PLUG_ADMIN_AUTORUN', 2); //���������� ������ � �������
define('PLUG_MAIN_AUTORUN', 3); //���������� ������ �� �������
define('PLUG_CALLEE', 4); //���������� �������� ����� index.php&name=plugins&p=plugin_name
define('PLUG_MANUAL', 5); //����� ��� ������ ������������ ������ � ������������ �������. ���������� ������.
define('PLUG_MANUAL_ONE', 7); //������������ ���� �����-�� ������ �� ������. ���������� ������.
define('PLUG_SYSTEM', 8); //��������� ������, �� ������� �����������, ���������� ������ ������� � ����� �������������� ����������� �� ���� ����������� �������

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
	 * ������ ��� ������ � ����� ������
	 * @return LcDatabaseFilesDB
	 */
	static public function db(){
		return $GLOBALS['db'];
	}

	/**
	 * ������ ��� ������ � ������� ������������ � �������
	 * @return User
	 */
	static public function user(){
		return $GLOBALS['user'];
	}

	/**
	 * ������ ���������� �����
	 * @return LmFileCache
	 */
	static public function cache(){
		return LmFileCache::Instance();
	}

	/**
	 * ������ ���������� ��������� �� �����
	 * @return Page
	 */
	static public function site(){
		return $GLOBALS['site'];
	}

	/**
	 * ������ ���������� ��������� � �����-������
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
		$smilies = $db->Select('smilies'); // ����� ����������� �������� ���� ��������
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
 * ���������� ��� ������� ������, ���� ��������� ����� ������� � ���
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
	// ���������� ������� ���������� � ������� ����� ���������� ��������� ������ � ������ �����
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

	// ���������� ���������� ��������� � ������ ������
	$replace = array();
	$ReplacePattern = '';
	foreach($params as $key=>$val){
		if(is_numeric($val)){
			$replace['\{'.$key.'\}'] = '([0-9]+)';// �������� ������ ������������ ����� �������
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

	// ��������� ������ � ���� ������
	$db->Insert('rewrite_rules', Values('', SafeEnv($UfuTemplate, 255, str), SafeEnv($Pattern, 255, str), SafeEnv($ReplacePattern, 255, str)));
	UfuGetRules( $db->GetLastId(), $UfuTemplate, $Pattern, $ReplacePattern); // ��������� ������� � ���
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
 * ���������� ���� � ������������ ��������� ����������,
 * ���� ���������� � ���������� ������ ����������
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
 * ��������� �������� �� ���� ������
 * @global  $db
 * @param var $config_var ���������� � ������� ����� �������� ���������
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

	# ����������� ������ ��������
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
 * ������������ �������� ����� ���������.
 * @param <type> $group
 * @param <type> $cname
 * @param <type> $newValue
 */
function ConfigSetValue( $group, $cname, $newValue ){
	global $config, $db;
	$group = $db->Select('config_groups', "`name`='$group'");
	$gid = SafeEnv($group[0]['id'], 11, int);
	$db->Update('config', "`value`='$newValue'",  "`group_id`='$gid' and `name`='$cname'");
	// ������� ��� ��������
	$cache = LmFileCache::Instance();
	$cache->Clear('config');
}

/**
 * ������������� ��������� ���� ��������� � ���������� �����
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
 * ��������� ������������� � ����������� �� ��������� php � �������� � ���� ��� ������� ���������� � ������ � ���� ������.
 * ��������, ��������� ��������� SafeXSS ����� �������� ������ ���, ��� ��� ����� �� ����� ��� ����������.
 *
 * @param Variable $Var // �����-�� ����������
 * @param Integer $maxlength // ����� ���������� �������� ����������
 * @param Const $type // ��������� ���� ����������. ��������� ������� � system.php
 * @param Bool $addsl // �������� �������� ����� ����� ����� �������������
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
 * ��������� ���������� ��� ����������� ������ �� ����������� � ������� ������������.
 * ������� �� ��������� �������������, ��������� ��������������� ������ �������� ������ ���������� � ������ � ���� ������.
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
 * ��������� ������ GET �� ������� ������ ������
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
 * ��������� ������ $_POST �� ������� ������
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

#������ �������������:
#������: 	$set="name='name',login='root',pass=''";
#���������: array('name','login','pass');-������������� ������
#� row ������ ���� ������������� ������ �� �������� ���� �������
function Parser_ParseSetStr( &$set, &$row, &$info ){
	$s = str_replace("\\'",'<&#39;>',$set);
	$maxlength=count($info['cols']);
	for($i=0;$i<$maxlength;$i++){
		$args[$i] = $row[$i];
		$names[$info['cols'][$i]['name']] = $i;
	}
	for($i=0;$i<$maxlength;$i++){
		$pos = strpos($s, '='); //���� ������ ��������� =
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
			echo "������ � ���������� �������. ���� ".$col." � ������� �� �������!";
			return $row;
		}
	}
	return $args;
}

#������ �������������:
#������: 	$values="'name','root',''";
#���������: array('name','root','');
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

#������� ������ ��������������� ���������� SQL ��������
#� row ������ ���� ������������� ������ �� �������� ���� �������
#���������: true ���� ������� ��������� � false ���� ���
function Parser_ParseWhereStr( $where, $row, $info, $index = 0 ){
	if($where == ''){ return true; };
	global $Parser_UseCache, $Parser_WhereCache;
	$vars = array();
	// �������� ���������� � ����� �������
	// fixme: ����� ���������� ��� �������� ������� ����
	for($j=0,$ccnt=count($info['cols']);$j<$ccnt;$j++){
		$n = $info['cols'][$j]['name'];
		$r = str_replace('&#13', "\r", $row[$j]); // ����� ������ �������������� ������ �� $db->Select
		$r = str_replace('&#10', "\n", $r);
		$vars[$n] = $r;
		$names[] = $n;
	}
	$vars['index'] = $index;
	$names[] = 'index';
	$ccnt++;
	// ��������� ���� �� ����� ��������� �������������� � ����������� ����
	if(isset($Parser_WhereCache[$where])){
		return Parser_ParseWhereStr2($Parser_WhereCache[$where], $vars);
	}
	// ����� ������� ������ �� �������� � ������������� ��� � PHP ������ �������� �������������� �������
	$where2 = str_replace("\\'", '<&#39;>', $where);
	$chs = explode("'", $where2);
	for($i=1,$cnt=count($chs)+1; $i<$cnt; $i++){
		if($i%2>0){
			$chs[$i-1] = str_replace("=", "==", $chs[$i-1]);
			for($j=0;$j<$ccnt;$j++){
				$chs[$i-1] = str_replace('`'.$names[$j].'`', '$'.$names[$j], $chs[$i-1]);
			}
		}else{
			// ���������� �������������
			$chs[$i-1] = str_replace("\\", "\\\\", $chs[$i-1]);
		}
	}
	$where2 = implode("'", $chs);
	$where2 = str_replace('<&#39;>', "\\'", $where2);
	// ���������� ��������� �������������� � ����������� ���
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

// ���������� ������ � ����������� � ������, ������� �������� ������ ��� ������ � ��.
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
 * ���������� ������ ������ � ������������� � ������� �� id
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
			// �� ������ ������ ��� ����������� ���� ��� � �����
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


#���������� ������ ���������� � ������������
#������� ����, �������� �����, ������ ������, ��� ����� ������� ��� ������.
#��� ���������� ����������.
function GetUserInfo($user_id){
	global $db, $user, $config;
	$system_users_cache = GetUsers();
	if(isset($system_users_cache[$user_id])){
		$usr = $system_users_cache[$user_id];
		//������
		$usr['avatar_file'] = GetUserAvatar($user_id);
		$usr['avatar_file_small'] = GetSmallUserAvatar($user_id, $usr['avatar_file']);
		$usr['avatar_file_smallest'] = GetSmallestUserAvatar($user_id,  $usr['avatar_file']);
		//����
		$rank = GetUserRank($usr['points'],$usr['type'],$usr['access']);
		$usr['rank_name'] = $rank[0];
		$usr['rank_image'] = $rank[1];
		//������ ������
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


// ���������� ��������, �������� � ������������� �����
function GetUserRank($points, $type, $access){
	global $config, $db;
	static $admintypes = null;
	if($type == '2'){ // ������������
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
	}else{ // �������������
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

	SendMail($username, $user_mail, '����������� �� '.$config['general']['site_name'], $text);
}

function UserSendEndRegMail($user_mail, $name, $login, $pass, $regtime){
	global $config;
	$text = '������������, ['.$name.']!

�� ���� ������� ���������������� �� �����
'.$config['general']['site_url'].'

���� �����������: '.date("d.m.Y", $regtime).'
���: '.$name.'

��� ����� �� ���� �����������:
�����: '.$login.'
������: '.$pass.'

��������, ��� ���� ����� ��� �������.
� ���������, ������������� ����� '.$config['general']['site_url'].'.';
	SendMail($name, $user_mail, '['.$config['general']['site_url'].'] �����������', $text);
}

function UserSendForgotPassword($user_mail, $name, $login, $pass){
	global $config;
	$ip = getip();
	$text = '������������, ['.$name.']!

�� ����� '.$config['general']['site_url'].'
���� ��������� ����������� ������.

���: '.$name.'

��� ����� � ����� ������:
�����: '.$login.'
������: '.$pass.'

�������� ������ �������� ������ �� ������:
'.GetSiteUrl().Ufu('index.php?name=user&op=editprofile', 'user/{op}/').'

IP-�����, � �������� ��� �������� ������: '.$ip.'

� ���������, ������������� ����� '.$config['general']['site_url'].'.';
	SendMail($name, $user_mail, '['.$config['general']['site_url'].'] ����������� ������', $text);
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
		$site->DataAdd($avd, '', '������������', true);
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
		$site->DataAdd($avd, '', '������������', true);
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
 * ������� ��������� ��������� ������ ($_FILES['upavatar])
 */
function UserLoadAvatar(&$errors, &$avatar, &$a_personal, $oldAvatarName, $oldAvatarPersonal, $editmode){
	global $config;

	$alloy_mime = array(
		'image/gif' => '.gif', 'image/jpeg' => '.jpg', 'image/pjpeg' => '.jpg', 'image/png' => '.png', 'image/x-png' => '.png'
	);
	include_once($config['inc_dir'].'picture.class.php');

	$asize = getimagesize($_FILES['upavatar']['tmp_name']);

	//�������� ������� �����
	$alloy_mime = array(
		'image/gif', 'image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png'
	);
	$alloy_exts = array(
		'.gif', '.jpg', '.jpeg', '.png'
	);
	if(in_array($_FILES['upavatar']['type'], $alloy_mime) && in_array(strtolower(GetFileExt($_FILES['upavatar']['name'])), $alloy_exts)){
		// ������� ������ ������
		if($editmode && $oldAvatarPersonal == '1'){
			UnlinkUserAvatarFiles($oldAvatarName);
		}

		//��������� ������, ���� �����, � ��������� ������ � ����� ������������ ������
		$NewName = GenRandomString(8, 'qwertyuiopasdfghjklzxcvbnm');
		$ext = strtolower(GetFileExt($_FILES['upavatar']['name']));

		if($asize[0] > $config['user']['max_avatar_width'] || $asize[1] > $config['user']['max_avatar_height']){
			$thumb = new TPicture($_FILES['upavatar']['tmp_name']);
			$thumb->SetImageSize($config['user']['max_avatar_width'], $config['user']['max_avatar_height']);
			$thumb->SaveToFile($config['general']['personal_avatars_dir'].$NewName.$ext);
		} else{
			copy($_FILES['upavatar']['tmp_name'], $config['general']['personal_avatars_dir'].$NewName.$ext);
		}

		// ������� ����������� ����������� ����� 24�24 � 64�64
		$thumb = new TPicture($_FILES['upavatar']['tmp_name']);
		$thumb->SetImageSize(64, 64);
		$thumb->SaveToFile($config['general']['personal_avatars_dir'].$NewName.'_64x64'.$ext);
		$thumb = new TPicture($_FILES['upavatar']['tmp_name']);
		$thumb->SetImageSize(24, 24);
		$thumb->SaveToFile($config['general']['personal_avatars_dir'].$NewName.'_24x24'.$ext);

		$avatar = $NewName.$ext;
		$a_personal = '1';
	} else{
		$errors[] = '������������ ������ �������. ��� ������ ������ ���� ������� GIF, JPEG ��� PNG.';
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

// �������� ���� �������� � �������
function CalcCounter($objTable, $whereObj, $objCounterColl, $calcVal){
	global $db;
	$objCounterColl = SafeEnv($objCounterColl, 255, str);
	$db->Select($objTable, $whereObj);
	if($db->NumRows() > 0){
		$counterVal = $db->QueryResult[0][$objCounterColl] + $calcVal;
		$db->Update($objTable, "$objCounterColl='$counterVal'", $whereObj);
	}
}

#������������ ������� ������������
function RegisterCommentTable($name, $objTable, $ObjIdColl, $objCounterColl, $objCounterCollIndex){
	global $db;
	$name = SafeEnv($name, 64, str);
	$db->Insert('comments', Values('', $name, $objTable, $ObjIdColl, $objCounterColl, $objCounterCollIndex));
}

#����������� ������� ������������
function UnRegisterCommentTable($name, $delete=false){
	global $db;
	$name = SafeEnv($name, 64, str);
	$db->Delete('comments', "`table`='$name'");
	if($delete){
		$db->DropTable($name);
	}
}

/**
 * ������� ��������� ������ ������������ �� ���� ������������.
 * ��� �������� ���������� ����������� �� �������� SafeEnv.
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
 * ������� ��� ���������� ������������
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
		//��������������� id ����������� �� ��������
		foreach($comms as $com){
			$comments[$com['object_id']] = SafeEnv($com['id'], 11, int);
			$objects[] = SafeEnv($com['object_id'], 11, int);
		}
		//������ ����� ������ ��� ������� �������� �������
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

#��������� �����, ������� ������� ��������� ���������, �� �����
function DivideWord( $text, $maxWordLength='30' ){
	return wordwrap($text, $maxWordLength, chr(13), 1);
}

/**
 * ��������� ������ �� �����. ��� ������ ������� ���������������� ���� ��������
 * ������������� ������������ ���� ��� ������ ������� foreach.
 *
 * @param Array $array // ������ ���� $array = array(array(col1=1,col2=2,...),arr...)
 * ������� ������ ���� �������� ��� �������� � ��
 * @param Integer $coll // ����� ������� � ������� �� ������� ��� �����������
 * @param Boolean $OnDecrease // ���� true �� ���������� ����� �������������� � �������� �������
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
 * ������ ������ �������� ��� ���� ������.
 * @return String : ������ ���� Values
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

#��������� ������� � ������
function ViewLevelToStr($level,$s_admins='',$s_members='',$s_guests='',$s_all='')
{
	switch($level){
		case 1:	$s_admins=='' ? $vi='<font color="#FF0000">������</font>' : $vi=$s_admins;
		break;
		case 2:	$s_members=='' ? $vi='<font color="#0080FF">������������</font>' : $vi=$s_members;
		break;
		case 3: $s_guests=='' ? $vi='<font color="#A0A000">�����</font>' : $vi=$s_guests;
		break;
		case 4:	$s_all=='' ? $vi='<font color="#008000">���</font>' : $vi=$s_all;
		break;
		default: $s_all=='' ? $vi='<font color="#008000">���</font>' : $vi=$s_all;
	}
	return $vi;
}

//������� ������ ���� ������ ����� �������� ������ �� ������� (������),
// ������� ������������ � ������ �������� ����� ������
function GetWhereByAccess($param_name, $user_access=null)
{
	if($user_access == null){
		global $user;
		$user_access = $user->AccessLevel();
	}
	$where = "`$param_name`='4'";
	if($user_access == '1'){//�������������
		$where = '';
	}elseif($user_access == '2'){//������������
		$where .= " or `$param_name`='2'";
	}else{//�����
		$where .= " or `$param_name`='3'";
	}
	return $where;
}

function GetMicroTime()
{
	return microtime(true);
}

/**
 * ������� ���� � ��������� �������
 *
 * @param Timestamp $time // ����� timestamp
 * @return unknown
 */
function TimeRender($time, $full=true, $logic=true)
{
	global $config;
	if($time==false || !is_numeric($time)){
		return '��� ������';
	}
	$format = '';
	$now = time();
	$ld = round(($now / 86400) - ($time / 86400));
	if($ld>1 || $now<$time || !$logic){
		$fdate = 'd.m.Y';
	}elseif($ld==0){
		$fdate = '�������';
	}elseif($ld==1){
		$fdate = '�����';
	}else{
		return '��� ������';
	}
	if($full){
		$date = date($fdate.' '.$config['general']['datetime_delemiter'].' H:i', $time);
	}else{
		$date = date($fdate,$time);
	}
	return $date;
}

/**
 * ���������� ���������� ������� ����� ����� ������ � �������
 * ��������� � ���� �������, ��� ���� ���������� �����, �����, ����
 * ��������� ����� ������ � �� ��������� �����������.
 *
 * @param Time $runtime // ����� ������ � ��������
 * @param Time $endtime // ����� ��������� � ��������
 * @return array('days'=>���������� ����,'hours'=>���������� �����,'sdays'=>����������� ����,'shours'=>����������� �����)
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

	//���������� ���������� ����
	$days2 = $days;
	if($days>19){$days = $days % 10;}
	if($days == 1){$str .= '����';}
	elseif($days > 1 && $days <= 4){$str .= '���';}
	elseif(($days > 4 && $days <= 19) || $days == 0){$str .= '����';}

	//���������� ���������� �����
	$hours2 = $hours;
	if($hours>19){$hours = $hours % 10;}
	if($hours == 1){$str2 = '���';}
	elseif($hours > 1 && $hours <= 4){$str2 = '����';}
	elseif(($hours > 4 && $hours <= 19) || $hours == 0){$str2 = '�����';}

	$str = $days2.' '.$str;
	$str2 = $hours2.' '.$str2;
	return array('days'=>$days2,'hours'=>$hours2,'sdays'=>$str,'shours'=>$str2);
}

/**
 * ��������� �� ������� ����� ����� ��� ���������� � ������
 *
 * @param String $file // ������ ��� �����
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
 * ��������� �� ������� ����� ����� ��� ��� ��� ����������.
 *
 * @param Itring $Name // ������ ��� �����
 * @return String
 */
function GetFileName($Name){
	$ext = strrpos($Name,".");
	return basename($Name,substr($Name,$ext));
}

/**
 * ���������� ������� ������� � ���������� ������ � �������������� ������� ������ ���������������� ��������
 *
 * @param String $folder // ��� ����� � ��������� ������
 * @param Boolean $use_subfolders // ������ � ���������
 * @param Boolean $use_mask // ������������ ����� ������
 * @param String $mask // ����� ������. �� ������ ������� ���������� (� ������) ����� �������.
 * @param Boolean $newSearch // ������ �� ����� ����� (����������� ���������� ����� ������������)
 * @param String $parentf // �� ��������� ��������. ����� ��� ������ �������.
 * @return Array // ������ ��������� ������
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
 * ���������� ������ ������������� �� �������� ����������.
 *
 * @param String $folder ���� �� ����� � ��������� ������.
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

# ���������� �� ����� ������������
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
 * ��������� ����� ����������� ����� �� ������������
 *
 * @param String $email // e-mail �����
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
		$error_out[] = '�� �� ����� ��� E-mail �����.';
		return false;
	}
	if(!CheckEmail($Email)){
		$error_out[] = '�� ���������� ������ E-mail. �� ������ ���� ����: <b>domain@host.ru</b> .';
		return false;
	}
	if($CheckExist){
		$db->Select('users', "`email`='$Email'".($xor_id<>0?' and `id`<>'.$xor_id:''));
		if($db->NumRows() > 0){
			$error_out[] = '������������ � ����� E-mail ��� ��������������� !';
			$result = false;
		}
	}
	return true;
}

/**
 * ��������� ����� �� ������������
 *
 * @param String $login // �����
 * @param $error_out // ���������� � ������� ���������� ����� ������
 * @param $CheckExist // ���������� �������� �� ��������� ������
 * @return Boolean // ������ ���� ����� ������
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
		$error_out[] = '����� ������ ���� �� ����� '.$minlength.' � �� ����� 15 ��������.';
		$result = false;
	}
	if(preg_match('/[^a-zA-Z�-��-�0-9_]/', $login)){
		$error_out[] = '��� ����� ������ �������� ������ �� ������� ��� ��������� ����, ���� � �������� �������������.';
		$result = false;
	}
	if($CheckExist){
		$db->Select('users',"`login`='$login'".($xor_id<>0?' and `id`<>'.$xor_id:''));
		if($db->NumRows()>0){
			$error_out[] = '������������ � ����� ������� ��� ��������������� !';
			$result = false;
		}
	}
	return $result;
}

/**
 * ��������� ������� �� ������������
 *
 * @param String $nikname // �������
 * @param $error_out // ���������� � ������� ���������� ����� ������
 * @param $CheckExist // ���������� �������� �� ��������� ������
 * @return Boolean // ������ ���� ������ ������
 */
function CheckNikname( $nikname, &$error_out, $CheckExist=false, $xor_id=0 )
{
	global $db, $config;
	$result = true;
	if($nikname == ''){
		$error_out[] = '�� �� ����� ���!';
		$result = false;
	}
	if(preg_match("/[^a-zA-Z�-��-�0-9_ ]/",$nikname)){
		$error_out[] = '���� ��� ������ �������� ������ �� ������� ��� ��������� ���� � ����, �������� ������������� � �������.';
		$result = false;
	}
	if($CheckExist){
		$db->Select('users',"`name`='$nikname'".($xor_id<>0?' and `id`<>'.$xor_id:''));
		if($db->NumRows()>0){
			$error_out[] = '������������ � ����� ������ ��� ��������������� !';
			$result = false;
		}
	}
	return $result;
}

/**
 * ��������� ������ �� ������������
 *
 * @param String $pass // ������
 * @param $error_out // ���������� � ������� ���������� ����� ������ (������)
 * @return Boolean // ������ ���� ������ ������
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
		$error_out[] = '������ ������ ���� �� ������ '.$minlength.' ��������.';
		$result = false;
	}
	return $result;
}

/**
 * ���������� ��������� ������ ������������ �����
 *
 * @param Integer $length // ������ ������
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
 * ���������� ����� �������������� ������
 *
 * @param Integer $length // ����� ������
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
 * ���������� E-mail
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
 * �������� ������� �������� ������
 * ������� �� ���������� ������.
 * ������������� ������������ ������ Header('Location: ...');
 * @param String $address // ����� ��������.
 */
function GO( $address, $exit = true, $response_code = 303 ){
	if($address == '') return;
	if(!defined('ERROR_HANDLER') || count(System::$Errors) == 0){ // todo ��������� �������� ��������� ������ ������ � �������
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

// �������������� ������������ �� �������� �� ������� �� ��� �������� ����� ��������� �����
// ���� � �������� �������� ��������� $BackSteps �������� �������, �� ������ ������� ����� ���������� ������� GoBack()
// ������������ �������� $BackSteps ����� ������.
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
 * ��������� ����� � ������ � ���������� �������������
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
 * ��������� ��������������� �� ������������ � ������ ������
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
 * ���������� �������������� ������ ������� ����������� ��������� ���.
 *
 * @param String $str // ��� ������
 * @param String $needle // ��� ������
 * @param Integer $searchWhere // ����� ����� ��������� �����
 * @param Integer $offset // � ������ ������� �������� �����
 * @return Integer // ����� �������
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
 * ��������� / �������� � ����������� ������� ������. ��������� ���������� �� ������.
 *
 * @param String $str // ������ ��� ������
 * @param Integer $selstart // ������ �������
 * @param Integer $sellength // ����� �������
 * @param String $needle // ��� �������� / ��������
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
 * ������� ������� ������
 *
 * @param String $str // ������
 * @param Integer $selstart // ������ �������
 * @param Integer $sellength // ����� �������
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
		if($size>$mb){$size = sprintf("%01.2f",$size/$mb).' ��';
		}elseif($size>=1024){$size = sprintf("%01.2f",$size/1024).' ��';
		}else{$size = $size.' ����';}
	}else{
		if($sizeType == 'k'){
			$size = $size.' ��';
		}elseif($sizeType == 'm'){
			$size = $size.' ��';
		}else{
			$size = $size.' ��';
		}
	}
	return $size;
}

//���������� ��� ������� ��������������
//�������� ��� ������ � ������������� ������������ � ����������
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
			die('��� ��� �������� ������ � �����, �������� ������� ���������� ��������������
			�������� � ����� �������. ���� �� ��������, ��� ��� ��������� �� ������, - ����������
			� ������ ��������� �� e-mail '.$config['general']['site_email'].'.');
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
	$site->DataAdd($images_data,$images[-1],'��� ��������',($FileName == ''));

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
	FormRow($LoadTitle, $site->FFile($LoadName).'<br /><small>������ ����������� ������ *.jpg,*.jpeg,*.gif,*.png</small><br /><small>������������ ������ �����: '.$max_file_size.'</small>');
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

// ������� ����������� �������� � ���� boolean.
// Ÿ ������� � ���������� �����. ����� �������� ������ ���� true.
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
 * �������������� ���� 7.79-2000
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
		'�' => 'a', '�' => 'A',
		'�' => 'b', '�' => 'B',
		'�' => 'v', '�' => 'V',
		'�' => 'g', '�' => 'G',
		'�' => 'd', '�' => 'D',
		'�' => 'e', '�' => 'E',
		'�' => 'yo', '�' => 'YO',
		'�' => 'zh', '�' => 'ZH',
		'�' => 'z', '�' => 'Z',
		'�' => 'i', '�' => 'I',
		'�' => 'j', '�' => 'J',
		'�' => 'k', '�' => 'K',
		'�' => 'l', '�' => 'L',
		'�' => 'm', '�' => 'M',
		'�' => 'n', '�' => 'N',
		'�' => 'o', '�' => 'O',
		'�' => 'p', '�' => 'P',
		'�' => 'r', '�' => 'R',
		'�' => 's', '�' => 'S',
		'�' => 't', '�' => 'T',
		'�' => 'u', '�' => 'U',
		'�' => 'f', '�' => 'F',
		'�' => 'x', '�' => 'X',
		'�' => 'c', '�' => 'C',
		'�' => 'ch', '�' => 'CH',
		'�' => 'sh', '�' => 'SH',
		'�' => 'shh', '�' => 'SHH',
		'�' => '``', '�' => '``',
		'�' => 'y\'', '�' => 'Y\'',
		'�' => '`', '�' => '`',
		'�' => 'e`', '�' => 'E`',
		'�' => 'yu', '�' => 'YU',
		'�' => 'ya', '�' => 'YA',
	    )
	);
	return $text;
}

/**
 * ���������������� ���� 7.79-2000
 * @param <type> $text
 * @return <type>
 */
function Retranslit($text, $strip_tospaces = true)
{
	if($strip_tospaces){
		$text = str_replace('_', ' ', $text);
	}
	$text = strtr($text, array(
		'a' => '�', 'A' => '�',
		'b' => '�', 'B' => '�',
		'v' => '�', 'V' => '�',
		'g' => '�', 'G' => '�',
		'd' => '�', 'D' => '�',
		'e' => '�', 'E' => '�',
		'yo' => '�', 'YO' => '�',
		'zh' => '�', 'ZH' => '�',
		'z' => '�', 'Z' => '�',
		'i' => '�', 'I' => '�',
		'j' => '�', 'J' => '�',
		'k' => '�', 'K' => '�',
		'l' => '�', 'L' => '�',
		'm' => '�', 'M' => '�',
		'n' => '�', 'N' => '�',
		'o' => '�', 'O' => '�',
		'p' => '�', 'P' => '�',
		'r' => '�', 'R' => '�',
		's' => '�', 'S' => '�',
		't' => '�', 'T' => '�',
		'u' => '�', 'U' => '�',
		'f' => '�', 'F' => '�',
		'x' => '�', 'X' => '�',
		'c' => '�', 'C' => '�',
		'ch' => '�', 'CH' => '�',
		'sh' => '�', 'SH' => '�',
		'shh' => '�', 'SHH' => '�',
		'``' => '�',
		'y\'' => '�', 'Y\'' => '�',
		'`' => '�',
		'e`' => '�', 'E`' => '�',
		'yu' => '�', 'YU' => '�',
		'ya' => '�', 'YA' => '�',
	    )
	);
	return $text;
}

#������� ������ � ����������� �� ������������� �������
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
		if(is_integer($pos)){ // ���� ����� �����
			$slen = strlen($search);
			if(!$set_text){ // �������� ����� �� ����� ����� ���� ��� ������ ���������
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
			// ������������
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

// ������� �������� � http ������.
function Url( $url ){
	$url = preg_replace('/^https:\/\//', '', $url);
	$url = preg_replace('/^http:\/\//', '', $url);
	$url = preg_replace('/^www\./', '', $url);
	return $url;
}

// ��������� �������� �� ������ ����������.
function IsMainHost( $url ){
	$host = $_SERVER['HTTP_HOST'];
	if(stristr(Url($url), Url($host))) {
		return true;
	}else{
		return false;
	}
}

//��� ��������� ������ - ���� ��� ������ �� �������� ������ �� �����
// - �� �������� �� ������������.
//���� ��� ������ �� ������� ������� (������ �����) - �� �������� ����������
//(��� ���������� ����� "������������� �������� ��� ������� ������").
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
 * ���������� ������������� ����������, � ������� ���������� ����.
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
 * ���������� ����� �����
 * @return string
 */
function GetSiteDomain(){
	return getenv("HTTP_HOST");
}

/**
 * ���������� ����� �����
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
 * ���������� URL ����� � ������������� ����������� � ������� ���������� ����.
 * @return String
 * @since 1.3.3
 */
function GetSiteUrl( $EndSlash = true ){
	return GetSiteHost().GetSiteDir($EndSlash);
}

/**
 * �������� ������ � ������ JSON
 * @param  $value
 * @return string
 * @since 1.3.5
 */
function JsonEncode( $value ){
	return json_encode(ObjectCp1251ToUtf8($value));
}

/**
 * ���������� ������ �� ������ � ������� JSON
 * @param  $json
 * @return mixed
 * @since 1.3.5
 */
function JsonDecode( $json ){
	return json_decode($json);
}

/**
 * ����������� ������ ������� ��� ������� � ��������� UTF-8
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
 * ������ �� �����
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
			'~\[hide\](.*?)\[\/hide\]~sei' => "'<div class=\"bbcode_hide\"><a href=\"javascript:onclick=ShowHide(\''.strlen(md5('$1')).substr(md5('$1'),0,3).'\')\">������� �����</a><div id=\"'.strlen(md5('$1')).substr(md5('$1'),0,3).'\" style=\"visibility: hidden; display: none;\">$1</div></div>'",
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
 * �������� ����� ������
 * @return void
 */
function ErrorsOn(){
	global $SITE_ERRORS;
	$SITE_ERRORS = true;
}

/**
 * �������� ��������� ����� ������
 * @return void
 */
function ErrorsOff(){
	global $SITE_ERRORS;
	$SITE_ERRORS = false;
}

/**
 * ���������� ������
 * @param  $No
 * @param  $Error
 * @param  $File
 * @param  $Line
 * @return void
 */
function ErrorHandler($No, $Error, $File, $Line = -1){
	global $ErrorsLog, $SITE_ERRORS;
	$errortype = array(
		1 => '������', 2 => '��������������!', 4 => '������ ����������', 8 => '���������', 16 => '������ ����', 32 => '�������������� ����!', 64 => '������ ����������',
		128 => '�������������� ����������!', 256 => '���������������� ������', 512 => '����������������� ��������������!', 1024 => '����������������� ���������', 2048 => '��������� ���������',
		8192 => '���������� ���'
	);
	$Error = '<br /><b>'.$errortype[$No].'</b>: '.$Error.' � <b>'.$File.($Line > -1 ? '</b> �� ����� <b>'.$Line.'</b>' : '').'.<br />';
	if(!defined('SETUP_SCRIPT') && System::$config['debug']['log_errors'] == '1'){
		$ErrorsLog->Write($Error);
	}
	if($SITE_ERRORS && isset(System::$config['debug']['php_errors']) && System::$config['debug']['php_errors'] == '1'){
		System::$Errors[] = $Error."\n";
	}
}

/**
 * ������� ��� ��������
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
 * ��������� ����� �������� � ����� plug_dir
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

	// ������� (��������� ����������)
	foreach($plugins as $name => $plugin){
		$plugins[$name]['group'] = '';
		$plugins[$name]['name'] = $name;
	}
	$result['plugins'] = $plugins;

	// ������ (����� �������� ������) $groups ����������� �� ������ info.php
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
 * ���������� ������ ��������� ��������
 * @param  $group ������
 * @param string $function ���������(���� ����)
 * @param bool $return ���������� ����� ������ �������� ������ �� ��������������� �����������
 * @param bool $return_full ���������� ������ ���� ������ ������ � ������ ����������� �� ��������
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
				global $include_plugin_path; // ��� ���������� ����� �������� �� �������
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
 * ���������� ��� ������ �������� ��������
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
 * ���������� ���������� �� ��������� � ������� ��������
 * @param bool $ClearCache ������������ ����� �����
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
	); // ������������� �������
	$install_groups = array(
	); // ������������� ������

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
 * ������� ������ �� ���� ������
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
 * ������� ������ �������� �� ���� ������
 * @param  $group
 * @return void
 */
function UninstallGroup($group){
	global $db;
	$db->Delete('plugins', "`group`='$group'");
	PluginsClearCache();
}

/**
 * ������������� ������ ��� ������ �������� � ���� ������
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
 * ���������� ������ ��������
 * @param $group ������
 * @param string $function ���������
 * @param bool $return ���������� ����� ������ �������� ������ ��������������� ����������
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
				global $include_plugin_path; // ��� ���������� ����� �������� �� �������
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

// ����������� ��������� ��������
$plugins = IncludeSystemPluginsGroup('system', '', true);
foreach($plugins as $plugin){
	include($plugin.'index.php');
}

?>