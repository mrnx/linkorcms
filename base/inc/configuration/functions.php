<?php

// ��������! ��� ���������� �������,
// ����������� System::admin()->AddConfigsForm() � System::admin()->SaveConfigs()
// ��� ������ � ���������� ��������.

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

$conf_config_table = 'config';
$conf_config_groups_table = 'config_groups';
include_once $config['inc_dir'].'forms.inc.php';

/**
 * ������� �������� ������������ � ����� ������
 * @param $Exe �������� ��������� URL exe
 * @param string $Group ���� 0, ���� ��� ������
 * @param bool $ShowHiddenGroups ���������� ������� ������
 * @param bool $ShowTitles ���������� ��������� � �����
 * @param string $ModuleName ��������� ���������� �����
 * @param string $SavePageParam �������� ������ �� ������ ���������� ��������
 * @return void
 */
function AdminConfigurationEdit( $Exe, $Group = '', $ShowHiddenGroups = false, $ShowTitles = true, $ModuleName = '', $SavePageParam = 'a=configsave' ){
	global $config, $db, $site, $conf_config_table, $conf_config_groups_table;
	// ����������� ��������� � ��������������� �� �������
	$temp = $db->Select($conf_config_table, '');
	$configs = array();
	for($i = 0, $cnt = count($temp); $i < $cnt; $i++){
		$configs[$temp[$i]['group_id']][] = $temp[$i];
	}
	unset($temp);
	// ����������� ������ ��������
	if($Group == ''){
		$q = '';
	}else{
		$q = "`name`='$Group'";
	}
	$cfg_grps = $db->Select($conf_config_groups_table, $q);
	// ��������� ����� � �������� ������������ �����
	$text = '<form action="'.$config['admin_file'].'?exe='.$Exe.'&'.$SavePageParam.'" method="post">';
	for($i = 0, $cnt = count($cfg_grps); $i < $cnt; $i++){
		// ���� ��� ������ �������� �� ���������� �
		if($Group === 0){
			if($cfg_grps[$i]['visible'] == 0)
				continue;
		}
		// ��� ���� � ��� ��� ��������
		if(!isset($configs[$cfg_grps[$i]['id']])){
			$jcnt = 0;
		}else{
			$jcnt = count($configs[$cfg_grps[$i]['id']]);
		}
		// ��������� ������� � ��������� ������ ��������
		$text .= '<table cellspacing="1" cellpadding="0" class="configtable">';
		if($ShowTitles){
			$text .= '<tr><th colspan="2" class="configtableth">'.SafeDB($cfg_grps[$i]['hname'], 255, str).'</th></tr>';
		}
		// ��������� ��������� ������
		if($jcnt > 0){
			for($j = 0; $j < $jcnt; $j++){
				// ���� ��������� �������� �� ���������� �
				if($configs[$cfg_grps[$i]['id']][$j]['visible'] == '0' && !$ShowHiddenGroups)
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
			$text .= '<tr><td class="leftc" align="center"> � ���� ������ ���� ��� ��������. </td></tr>';
		}
		//��������� ������� ������
		$text .= '</table>';
	}
	$text .= '<table class="configsubmit"><tr><td>'.$site->Submit('���������').'</td></tr></table>';
	$text .= '</form>';
	if($ModuleName != ''){
		$ModuleName = $ModuleName;
	}else{
		$ModuleName = '������������';
	}
	AddTextBox($ModuleName, $text);
}

/**
 * ��������� ������������ � ���� ������
 * @param $Exe
 * @param string $Group
 * @param bool $ShowHidden
 * @return void
 */
function AdminConfigurationSave( $Exe, $Group = '', $ShowHidden = false ){
	global $config, $db, $conf_config_table, $conf_config_groups_table;
	// ����������� ��������� � ��������������� �� �������
	$temp = $db->Select($conf_config_table, '');
	for($i = 0, $cnt = count($temp); $i < $cnt; $i++){
		$configs[$temp[$i]['group_id']][] = $temp[$i];
	}
	unset($temp);
	// ����������� ������ ��������
	if($Group == ''){
		$q = '';
	}else{
		$q = "`name`='".$Group."'";
	}
	$cfg_grps = $db->Select($conf_config_groups_table, $q);
	for($i = 0, $cnt = count($cfg_grps); $i < $cnt; $i++){
		// ���� ��� ������ �������� �� ���������� �
		if($Group == ''){
			if($cfg_grps[$i]['visible'] == 0)
				continue;
		}
		// ��� ���� � ��� ��� ��������
		if(!isset($configs[$cfg_grps[$i]['id']])){
			continue;
		}
		for($j = 0, $jcnt = count($configs[$cfg_grps[$i]['id']]); $j < $jcnt; $j++){
			// ���� ��������� �������� �� ���������� �
			if($configs[$cfg_grps[$i]['id']][$j]['visible'] == 0 && !$ShowHidden)
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
				$db->Update($conf_config_table, 'value=\''.$value.'\'', $where); // FIXME: ������������ ����������
			}
		}
	}
	// ������� ��� ��������
	$cache = LmFileCache::Instance();
	$cache->Clear('config');
	GO(ADMIN_FILE.'?exe='.$Exe);
}
