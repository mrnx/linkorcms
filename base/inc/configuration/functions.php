<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$conf_config_table = 'config';
$conf_config_groups_table = 'config_groups';
include_once ($config['inc_dir'].'forms.inc.php');

// Создает страницу конфигурации в админ панели
// в group должно быть либо 0, либо имя группы
function AdminConfigurationEdit( $paddr, $group = '', $showhidden = false, $showtitles = true, $modname = '', $method_name = 'a=configsave' ){
	global $config, $db, $site, $conf_config_table, $conf_config_groups_table;

	// Вытаскиваем настройки и отсортировываем по группам
	$temp = $db->Select($conf_config_table, '');
	$configs = array();
	for($i = 0, $cnt = count($temp); $i < $cnt; $i++){
		$configs[$temp[$i]['group_id']][] = $temp[$i];
	}
	unset($temp);

	// Вытаскиваем группы настроек
	if($group == ''){
		$q = '';
	}else{
		$q = "`name`='$group'";
	}
	$cfg_grps = $db->Select($conf_config_groups_table, $q);

	// Добавляем форму и начинаем генерировать текст
	$text = '<form action="'.$config['admin_file'].'?exe='.$paddr.'&'.$method_name.'" method="post">';
	for($i = 0, $cnt = count($cfg_grps); $i < $cnt; $i++){
		// Если эта группа невидима то пропускаем её
		if($group === 0){
			if($cfg_grps[$i]['visible'] == 0)
				continue;
		}
		// Или если в ней нет настроек
		if(!isset($configs[$cfg_grps[$i]['id']])){
			$jcnt = 0;
		}else{
			$jcnt = count($configs[$cfg_grps[$i]['id']]);
		}
		// Добавляем таблицу и заголовок группы настроек
		$text .= '<table cellspacing="1" cellpadding="0" class="configtable">';
		if($showtitles){
			$text .= '<tr><th colspan="3" height="30">'.SafeDB($cfg_grps[$i]['hname'], 255, str).'</th></tr>';
		}
		// $text .= '<tr><th>Настройка</th><th>Значение</th></tr>';

		// Добавляем настройки группы
		if($jcnt > 0){
			for($j = 0; $j < $jcnt; $j++){
				#Если настройка невидима то пропускаем её
				if($configs[$cfg_grps[$i]['id']][$j]['visible'] == '0' && !$showhidden)
					continue;
				$name = SafeDB($configs[$cfg_grps[$i]['id']][$j]['name'], 255, str, false, false);
				$desc = SafeDB($configs[$cfg_grps[$i]['id']][$j]['description'], 255, str, false, false);
				$type = $configs[$cfg_grps[$i]['id']][$j]['type'];
				$value = $configs[$cfg_grps[$i]['id']][$j]['value'];
				$kind = $configs[$cfg_grps[$i]['id']][$j]['kind'];
				$hname = SafeDB($configs[$cfg_grps[$i]['id']][$j]['hname'], 255, str, false, false);
				$values = $configs[$cfg_grps[$i]['id']][$j]['values'];
				$text .= '<tr>'
				.'<td class="leftc">'.$hname.($desc != '' ? '<br /><span class="configdesc">'.$desc.'</span>' : '').'</td>'
				.'<td class="rightc">'.FormsGetControl($name, $value, $kind, $type, $values).'</td>'
				.'</tr>';
			}
		}else{
			$text .= '<tr><td class="leftc" align="center"> В этой группе пока нет настроек. </td></tr>';
		}

		//Закрываем таблицу группы
		$text .= '</table><br />';
	}
	$text .= '<table class="configsubmit"><tr><td>'.$site->Submit('Сохранить').'</td></tr></table>';
	$text .= '</form>';
	if($modname != ''){
		$modname = $modname;
	}else{
		$modname = 'Конфигурация';
	}
	AddTextBox($modname, $text);
}

// Сохраняет конфигурацию в базе данных
function AdminConfigurationSave( $paddr, $group = '', $showhidden = false )
{
	global $config, $db, $conf_config_table, $conf_config_groups_table;
	// Вытаскиваем настройки и отсортировываем по группам
	$temp = $db->Select($conf_config_table, '');
	for($i = 0, $cnt = count($temp); $i < $cnt; $i++){
		$configs[$temp[$i]['group_id']][] = $temp[$i];
	}
	unset($temp);
	// Вытаскиваем группы настроек
	if($group == ''){
		$q = '';
	}else{
		$q = "`name`='".$group."'";
	}
	$cfg_grps = $db->Select($conf_config_groups_table, $q);
	for($i = 0, $cnt = count($cfg_grps); $i < $cnt; $i++){
		// Если эта группа невидима то пропускаем её
		if($group == ''){
			if($cfg_grps[$i]['visible'] == 0)
				continue;
		}
		// Или если в ней нет настроек
		if(!isset($configs[$cfg_grps[$i]['id']])){
			continue;
		}
		for($j = 0, $jcnt = count($configs[$cfg_grps[$i]['id']]); $j < $jcnt; $j++){
			// Если настройка невидима то пропускаем её
			if($configs[$cfg_grps[$i]['id']][$j]['visible'] == 0 && !$showhidden)
				continue;
			$name = $configs[$cfg_grps[$i]['id']][$j]['name'];
			$kind = explode(':', $configs[$cfg_grps[$i]['id']][$j]['kind']);
			$kind = trim(strtolower($kind[0]));
			$savefunc = trim($configs[$cfg_grps[$i]['id']][$j]['savefunc']);
			$type = trim($configs[$cfg_grps[$i]['id']][$j]['type']);
			if($type != ''){
				$type = explode(',', $type);
			}else{
				$type = array(255, str, false);
			}
			$where = "`name`='$name' and `group_id`='".$cfg_grps[$i]['id']."'";
			if(isset($_POST[$name])){
				switch($kind){
					case 'edit':
					case 'radio':
					case 'combo':
						if(FormsConfigCheck2Func('function', $savefunc, 'save')){
							$savefunc = CONF_SAVE_PREFIX.$savefunc;
							$value = $savefunc(FormsCheckType($_POST[$name], $type));
						}else{
							$value = FormsCheckType($_POST[$name], $type);
						}
						break;
					case 'text':
						if(FormsConfigCheck2Func('function', $savefunc, 'save')){
							$savefunc = CONF_SAVE_PREFIX.$savefunc;
							$value = $savefunc(FormsCheckType($_POST[$name], $type));
						}else{
							$value = FormsCheckType($_POST[$name], $type);
						}
						break;
					case 'check':
					case 'list':
						if(FormsConfigCheck2Func('function', $savefunc, 'save')){
							$savefunc = CONF_SAVE_PREFIX.$savefunc;
							$value = $savefunc(FormsCheckType($_POST[$name], $type));
						}else{
							if(isset($_POST[$name])){
								$c = count($_POST[$name]);
							}else{
								$c = 0;
							}
							$value = '';
							for($k = 0; $k < $c; $k++){
								$value .= ',';
								$value .= FormsCheckType($_POST[$name][$k], $type);
							}
							$value = substr($value, 1);
						}
						break;
					default:
						if(FormsConfigCheck2Func('function', $savefunc, 'save')){
							$savefunc = CONF_SAVE_PREFIX.$savefunc;
							$value = $savefunc(FormsCheckType($_POST[$name], $type));
						}else{
							$value = FormsCheckType($_POST[$name], $type);
						}
				}
				$db->Update($conf_config_table, 'value=\''.$value.'\'', $where);
			}
		}
	}
	// Очищаем кэш настроек
	$cache = LmFileCache::Instance();
	$cache->Clear('config');

	GO(ADMIN_FILE.'?exe='.$paddr);
}

?>