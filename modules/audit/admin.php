<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit();
}

System::admin()->AddSubTitle('�����');

if(!$user->isSuperUser()){
	AddTextBox('������', $config['general']['admin_accd']);
	return;
}

if(isset($_GET['a'])){
	$action = $_GET['a'];
}else{
	$action = 'main';
}

if($user->CheckAccess2('audit', 'audit_conf')){
	TAddToolLink('�����', 'audit', 'audit&a=audit');
	TAddToolLink('�������� � ������', 'referers', 'audit&a=referers');
	TAddToolLink('�������� �����', 'keywords', 'audit&a=keywords');
}
TAddToolBox($action);


switch($action){
	case 'main':
		AdminAuditMain();
		break;
	case 'clear':
		AdminAuditClear();
		break;
	case 'referers':
		AdminAuditReferers();
		break;
	case 'clear_referers':
		AdminAuditClearReferers();
		break;
	case 'keywords':
		AdminAuditKeywords();
		break;
	default:
		AdminAuditMain();
}

// ������� �������� ������. ����� ���� �������� ���������������.
function AdminAuditMain(){
	AddCenterBox('����� �������� ���������������');
	$query = System::database()->Select('audit', '');
	$count = count($query);
	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 10, int);
	}else{
		$page = 1;
	}
	SortArray($query, 'date', true);
	$num = 50;
	if($count > $num){
		$navigator = new Navigation($page);
		$navigator->GenNavigationMenu($query, $num, ADMIN_FILE.'?exe=audit');
		AddNavigation();
		$nav = true;
	}else{
		$nav = false;
	}
	if(System::database()->Name != 'FilesDB'){
		System::admin()->HighlightError('����� �������������� ������ � FilesDB.');
	}elseif($count == 0){
		System::admin()->Highlight('�������������� �� ��������� ������� ��������.');
	}elseif($count >= 1){
		$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
		$text .= '<tr><th>��������</th><th>����</th><th>������������</th><th>IP</th></tr>';
		foreach($query as $q){
			$user = GetUserInfo(SafeDB($q['user'], 11, int));
			$date = TimeRender(SafeDB($q['date'], 11, int));
			$action = SafeDB($q['action'], 255, str);
			$ip = SafeDB($q['ip'], 255, str);
			$text .= '<tr>
			<td>'.$action.'</td>
			<td>'.$date.'</td>
			<td><a href="'.ADMIN_FILE.'?exe=admins&a=editadmin&id='.SafeDB($user['id'], 11, int).'">'.SafeDB($user['name'], 50, str).'</td>
			<td>'.$ip.'</td>
			</tr>';
		}
		$text .= '</table>';
		$text .= System::admin()->SpeedConfirm('��������  ���', ADMIN_FILE.'?exe=audit&a=clear', '', '�������� ��� �������� ���������������?', true, true);
		AddText($text);
	}
}

// ������� ���� �������� ��������������
function AdminAuditClear(){
	System::database()->Delete('audit', '');
	GO(ADMIN_FILE.'?exe=audit');
}

// ������ ���������
function AdminAuditReferers(){
	System::admin()->AddCenterBox('�������� � ������ (��������)');
	$query = System::database()->Select('referers', '');
	$allcount = 0;
	$count = count($query);
	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 10, int);
	}else{
		$page = 1;
	}
	SortArray($query, 'count', true);
	$num = 50;
	if($count > $num){
		$navigator = new Navigation($page);
		$navigator->GenNavigationMenu($query, $num, ADMIN_FILE.'?exe=audit&a=referers');
		AddNavigation();
		$nav = true;
	}else{
		$nav = false;
	}
	if($count == 0){
		System::admin()->Highlight('��������� �� ���� ����������.');
	}elseif($count >= 1){
		$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
		$text .= '<tr><th>�������</th><th>���������</th></tr>';
		foreach($query as $q){
			$referer = $q['referer'];
			$str = KeyWordsToStr($q['referer']);
			$count = SafeDB($q['count'], 11, int);
			$allcount += $count;
			$text .= '<tr>
			<td><a href="'.$referer.'" target="_blank">'.$str.'</a></td>
			<td>'.$count.'</td>
			</tr>';
		}
		$text .= '</table>';
		$text .= '����� ��������� �� ���� ��������: <b>'.$allcount.'</b>.&nbsp;'
			.System::admin()->SpeedConfirm('��������  ���', ADMIN_FILE.'?exe=audit&a=clear_referers', '', '�������� ��� ���������?', true, true);
		AddText($text);
	}
}

function AdminAuditClearReferers(){
	System::database()->Delete('referers', '');
	GO(ADMIN_FILE.'?exe=audit&a=referers');
}

function AdminAuditKeywords(){
	global $db, $config;
	AddCenterBox('�������� ����� (��������)');
	$query = $db->Select('referers', '');
	$allcount = 0;
	$stemmer = new AdminAudit_Lingua_Stem_Ru();
	if(isset($_GET['page'])){
		$page = SafeEnv($_GET['page'], 10, int);
	}else{
		$page = 1;
	}
	$key = array();
	foreach($query as $q){
		$referer = $q['referer'];
		$str = AdminAuditRustrToLower(KeyWordsToStr($q['referer']));
		if($str != $referer){
			$str = str_replace("&quot;", " ", $str);
			$str = str_replace("?", "", $str);
			$str = str_replace("!", "", $str);
			$str = str_replace("-", " ", $str);
			$str = str_replace(".", " ", $str);
			$a_str = explode(' ', ($str));

			foreach($a_str as $k_str){
				$k_str = strip_tags($k_str);
				$l = strlen($k_str);
				if($l > 4 and $l < 25){
					$str2 = $stemmer->stem_word($k_str);
					if(isset($key[$str2]['count'])){
						$key[$str2]['count']++;
						if(strlen($k_str) < strlen($key[$str2]['word']))
							$key[$str2]['word'] = $k_str;
					}else{
						$key[$str2]['count'] = $q['count'];
						$key[$str2]['word'] = $k_str;
						$key[$str2]['key'] = $str2;
					}
				}
			}
		}
	}

	SortArray($key, 'count', true);
	$count = count($key);
	$num = 100;
	if($count > $num){
		$navigator = new Navigation($page);
		$navigator->GenNavigationMenu($key, $num, ADMIN_FILE.'?exe=audit&a=keywords');
		AddNavigation();
		$nav = true;
	}else{
		$nav = false;
	}
	if($count == 0){
		$text = '<center><br />�������� ���� �� ���� ����������.<br /><br /></center>';
		AddText($text);
	}elseif($count >= 1){
		$text = '<table cellspacing="0" cellpadding="0" border="0" class="cfgtable" >';
		$text .= '<tr><th>�������� �����</th><th>���-��</th></tr>';
		AddText($text);
		$text = '';
		foreach($key as $q){
			$referer = SafeDB($q['word'], 255, str);
			$str = $q['word'];
			$count = SafeDB($q['count'], 11, int);
			$allcount += $count;
			$text .= '<tr>
			<td>'.$str.'</td>
			<td>'.$count.'</td>
			</tr>';
		}
		$text .= '</table>';
		AddText($text);
	}
}

function KeyWordsToStr($Referer = ''){
	$KeyWords = $Referer;
	if(!empty($Referer)){
		$qwery = parse_url($Referer);
		if(isset($qwery['query']) and $qwery['scheme'] == 'http'){
			$RefHost = $qwery['host'];
			$IsGoogle = strpos($RefHost, '.google.');
			if(is_int(strpos($RefHost, 'search.msn.com')) || is_int($IsGoogle)){
				parse_str($qwery['query']);
				if(Isset($q)){
					$KeyWords = $q;
				}elseif(Isset($as_q)){
					$KeyWords = $as_q;
				}
			}elseif(is_int(strpos($RefHost, 'go.mail.ru'))){
				parse_str($qwery['query']);
				if(Isset($q)){
					$KeyWords = $q;
				}else{
					$KeyWords = $query;
				}
			}elseif(is_int(strpos($RefHost, 'rambler.'))){
				parse_str($qwery['query']);
				if(Isset($words)){
					$KeyWords = $words;
				}else{
					$KeyWords = $query;
				}
			}elseif(is_int(strpos($RefHost, 'search.qip.ru'))){
				parse_str($qwery['query']);
				if(Isset($words)){
					$KeyWords = $words;
				}else{
					$KeyWords = $query;
				}
			}elseif(is_int(strpos($RefHost, 'blogs.yandex.'))){
				parse_str($qwery['query']);
				if(Isset($text)){
					$KeyWords = $text;
				}else{
					$KeyWords = $query;
				}
			}elseif(is_int(strpos($RefHost, 'search.ukr.net'))){
				parse_str($qwery['query']);
				if(Isset($search_query)){
					$KeyWords = $search_query;
				}elseif(Isset($query)){
					$KeyWords = $query;
				}
			}elseif(is_int(strpos($RefHost, 'yandex.'))){
				parse_str($qwery['query']);
				if(Isset($text)){
					$KeyWords = $text;
				}elseif(Isset($query)){
					$KeyWords = $query;
				}
			}elseif(is_int(strpos($RefHost, 'nigma.'))){
				parse_str($qwery['query']);
				if(Isset($s))
					$KeyWords = $s;
			}else{
				$KeyWords = $Referer;
			}
		}
		if(empty($KeyWords))
			return $Referer;
		$KeyWords = Main_Audit_Encodestr($KeyWords);
		$KeyWords = trim($KeyWords);
		$KeyWords = htmlspecialchars($KeyWords, ENT_QUOTES);
	}
	return $KeyWords;
}

function Main_Audit_Encodestr($str, $type = 'w'){
	static $conv = '';
	if(!is_array($conv)){
		$conv = array();
		for($x = 128; $x <= 143; $x++){
			$conv['utf'][] = chr(209).chr($x);
			$conv['win'][] = chr($x + 112);
		}
		for($x = 144; $x <= 191; $x++){
			$conv['utf'][] = chr(208).chr($x);
			$conv['win'][] = chr($x + 48);
		}
		$conv['utf'][] = chr(208).chr(129);
		$conv['win'][] = chr(168);
		$conv['utf'][] = chr(209).chr(145);
		$conv['win'][] = chr(184);
	}
	if($type == 'w'){
		return str_replace($conv['utf'], $conv['win'], $str);
	}elseif($type == 'u'){
		return iconv("WINDOWS-1251", "UTF-8", $str);
	}else{
		return $str;
	}
}

function AdminAuditRustrToLower($s){
	$from = array("�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "A", "B", "C", "D", "E", "F", "G", "H", "I", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "J");
	$to = array("�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "�", "a", "b", "c", "d", "e", "f", "g", "h", "i", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "j");
	return str_replace($from, $to, $s);
}

/**
 * ����� ��� ��������� ����������. ������� ����.
 *
 * $stem = new Lingua_Stem_Ru();
 * print $stem->stem_word("�����");
 *
 */
class AdminAudit_Lingua_Stem_Ru {
	public $VERSION = "0.02";
	public $Stem_Caching = 0;
	public $Stem_Cache = array();
	public $VOWEL = '/���������/';
	public $PERFECTIVEGROUND = '/((��|����|������|��|����|������)|((?<=[��])(�|���|�����)))$/';
	public $REFLEXIVE = '/(�[��])$/';
	public $ADJECTIVE = '/(��|��|��|��|���|���|��|��|��|��|��|��|��|��|���|���|���|���|��|��|��|��|��|��|��|��)$/';
	public $PARTICIPLE = '/((���|���|���)|((?<=[��])(��|��|��|��|�)))$/';
	public $VERB = '/((���|���|���|����|����|���|���|���|��|��|��|��|��|��|��|���|���|���|��|���|���|��|��|���|���|���|���|��|�)|((?<=[��])(��|��|���|���|��|�|�|��|�|��|��|��|��|��|��|���|���)))$/';
	public $NOUN = '/(�|��|��|��|��|�|����|���|���|��|��|�|���|��|��|��|�|���|��|���|��|��|��|�|�|��|���|��|�|�|��|��|�|��|��|�)$/';
	public $RVRE = '/^(.*?[���������])(.*)$/';
	public $DERIVATIONAL = '/[^���������][���������]+[^���������]+[���������].*(?<=�)���?$/';

	public function s(&$s, $re, $to){
		$orig = $s;
		$s = preg_replace($re, $to, $s);
		return $orig !== $s;
	}

	public function m($s, $re){
		return preg_match($re, $s);
	}

	public function stem_word($word){
		$word = strtolower($word);
		$word = strtr($word, '�', '�');
		# Check against cache of stemmed words
		if($this->Stem_Caching && isset($this->Stem_Cache[$word])){
			return $this->Stem_Cache[$word];
		}
		$stem = $word;
		do{
			if(!preg_match($this->RVRE, $word, $p))
				break;
			$start = $p[1];
			$RV = $p[2];
			if(!$RV)
				break;
				# Step 1
			if(!$this->s($RV, $this->PERFECTIVEGROUND, '')){
				$this->s($RV, $this->REFLEXIVE, '');

				if($this->s($RV, $this->ADJECTIVE, '')){
					$this->s($RV, $this->PARTICIPLE, '');
				}else{
					if(!$this->s($RV, $this->VERB, ''))
						$this->s($RV, $this->NOUN, '');
				}
			}
			# Step 2
			$this->s($RV, '/�$/', '');
			# Step 3
			if($this->m($RV, $this->DERIVATIONAL))
				$this->s($RV, '/����?$/', '');
				# Step 4
			if(!$this->s($RV, '/�$/', '')){
				$this->s($RV, '/����?/', '');
				$this->s($RV, '/��$/', '�');
			}
			$stem = $start.$RV;
		}while(false);
		if($this->Stem_Caching)
			$this->Stem_Cache[$word] = $stem;
		return $stem;
	}

	public function stem_caching($parm_ref){
		$caching_level = @$parm_ref['-level'];
		if($caching_level){
			if(!$this->m($caching_level, '/^[012]$/')){
				die(__CLASS__."::stem_caching() - Legal values are '0','1' or '2'. '$caching_level' is not a legal value");
			}
			$this->Stem_Caching = $caching_level;
		}
		return $this->Stem_Caching;
	}

	public function clear_stem_cache(){
		$this->Stem_Cache = array();
	}
}
