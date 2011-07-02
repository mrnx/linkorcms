<?php

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
