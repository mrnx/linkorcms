<?php

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
