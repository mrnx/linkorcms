<?php

/**
 * Загружает натройки из базы данных.
 * @param $ConfigVar Переменная в которую будут записаны настройки
 * @param string $ConfigTable
 * @param string $GroupsTable
 * @return
 */
function LoadSiteConfig( &$ConfigVar, $ConfigTable = 'config', $GroupsTable = 'config_groups' ){
	global $db;

	$cache = LmFileCache::Instance();
	if($cache->HasCache('config', $ConfigTable)){
		$ConfigVar = $cache->Get('config', $ConfigTable);
		return;
	}

	$temp = $db->Select($ConfigTable, "`autoload`='1'");
	foreach($temp as $i){
		$configs[$i['group_id']][] = $i;
	}

	# Вытаскиваем группы настроек
	$config_groups = $db->Select($GroupsTable, '');
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

				$ConfigVar[$gname][$cname] = $cvalue;
			}
		}
	}

	$cache->Write('config', $ConfigTable, $ConfigVar);
}

/**
 * Устанавливет значение настройки в таблице config.
 * @param $Group
 * @param $ConfigName
 * @param $NewValue
 */
function ConfigSetValue( $Group, $ConfigName, $NewValue ){
	global $config, $db;
	$Group = $db->Select('config_groups', "`name`='$Group'");
	$gid = SafeEnv($Group[0]['id'], 11, int);
	$db->Update('config', "`value`='$NewValue'",  "`group_id`='$gid' and `name`='$ConfigName'");
	// Очищаем кэш настроек
	$cache = LmFileCache::Instance();
	$cache->Clear('config');
}
