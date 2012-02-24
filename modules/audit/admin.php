<?php

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit();
}

System::admin()->AddSubTitle('Аудит');

if(!$user->isSuperUser()){
	AddTextBox('Ошибка', $config['general']['admin_accd']);
	return;
}

if(isset($_GET['a'])){
	$action = $_GET['a'];
}else{
	$action = 'main';
}

if($user->CheckAccess2('audit', 'audit_conf')){
	TAddToolLink('Аудит', 'audit', 'audit&a=audit');
	TAddToolLink('Переходы с сайтов', 'referers', 'audit&a=referers');
	TAddToolLink('Ключевые слова', 'keywords', 'audit&a=keywords');
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

// Главная страница Аудита. Показ лога действий администраторов.
function AdminAuditMain(){
	AddCenterBox('Аудит действий администраторов');
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
		System::admin()->HighlightError('Аудит поддерживается только в FilesDB.');
	}elseif($count == 0){
		System::admin()->Highlight('Администраторы не произвели никаких действий.');
	}elseif($count >= 1){
		$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
		$text .= '<tr><th>Действие</th><th>Дата</th><th>Пользователь</th><th>IP</th></tr>';
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
		$text .= System::admin()->SpeedConfirm('Очистить  лог', ADMIN_FILE.'?exe=audit&a=clear', '', 'Очистить лог действий администраторов?', true, true);
		AddText($text);
	}
}

// Очистка лога действий адмнистраторов
function AdminAuditClear(){
	System::database()->Delete('audit', '');
	GO(ADMIN_FILE.'?exe=audit');
}

// Список рефералов
function AdminAuditReferers(){
	System::admin()->AddCenterBox('Переходы с сайтов (Рефералы)');
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
		System::admin()->Highlight('Рефералов не было обнаружено.');
	}elseif($count >= 1){
		$text = '<table cellspacing="0" cellpadding="0" class="cfgtable">';
		$text .= '<tr><th>Реферал</th><th>Переходов</th></tr>';
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
		$text .= 'Всего переходов на этой странице: <b>'.$allcount.'</b>.&nbsp;'
			.System::admin()->SpeedConfirm('Очистить  лог', ADMIN_FILE.'?exe=audit&a=clear_referers', '', 'Очистить лог рефералов?', true, true);
		AddText($text);
	}
}

function AdminAuditClearReferers(){
	System::database()->Delete('referers', '');
	GO(ADMIN_FILE.'?exe=audit&a=referers');
}

function AdminAuditKeywords(){
	global $db, $config;
	AddCenterBox('Ключевые слова (Рефералы)');
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
		$text = '<center><br />Ключевых слов не было обнаружено.<br /><br /></center>';
		AddText($text);
	}elseif($count >= 1){
		$text = '<table cellspacing="0" cellpadding="0" border="0" class="cfgtable" >';
		$text .= '<tr><th>Ключевые слова</th><th>Кол-во</th></tr>';
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
	$from = array("А", "Б", "В", "Г", "Д", "Е", "Ё", "Ж", "З", "И", "Й", "К", "Л", "М", "Н", "О", "П", "Р", "С", "Т", "У", "Ф", "Х", "Ц", "Ч", "Ш", "Щ", "Ъ", "Ы", "Ь", "Э", "Ю", "Я", "A", "B", "C", "D", "E", "F", "G", "H", "I", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "J");
	$to = array("а", "б", "в", "г", "д", "е", "ё", "ж", "з", "и", "й", "к", "л", "м", "н", "о", "п", "р", "с", "т", "у", "ф", "х", "ц", "ч", "ш", "щ", "ъ", "ы", "ь", "э", "ю", "я", "a", "b", "c", "d", "e", "f", "g", "h", "i", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "j");
	return str_replace($from, $to, $s);
}

/**
 * Класс для получение словоформы. Русский язык.
 *
 * $stem = new Lingua_Stem_Ru();
 * print $stem->stem_word("блоги");
 *
 */
class AdminAudit_Lingua_Stem_Ru {
	public $VERSION = "0.02";
	public $Stem_Caching = 0;
	public $Stem_Cache = array();
	public $VOWEL = '/аеиоуыэюя/';
	public $PERFECTIVEGROUND = '/((ив|ивши|ившись|ыв|ывши|ывшись)|((?<=[ая])(в|вши|вшись)))$/';
	public $REFLEXIVE = '/(с[яь])$/';
	public $ADJECTIVE = '/(ее|ие|ые|ое|ими|ыми|ей|ий|ый|ой|ем|им|ым|ом|его|ого|ему|ому|их|ых|ую|юю|ая|яя|ою|ею)$/';
	public $PARTICIPLE = '/((ивш|ывш|ующ)|((?<=[ая])(ем|нн|вш|ющ|щ)))$/';
	public $VERB = '/((ила|ыла|ена|ейте|уйте|ите|или|ыли|ей|уй|ил|ыл|им|ым|ен|ило|ыло|ено|ят|ует|уют|ит|ыт|ены|ить|ыть|ишь|ую|ю)|((?<=[ая])(ла|на|ете|йте|ли|й|л|ем|н|ло|но|ет|ют|ны|ть|ешь|нно)))$/';
	public $NOUN = '/(а|ев|ов|ие|ье|е|иями|ями|ами|еи|ии|и|ией|ей|ой|ий|й|иям|ям|ием|ем|ам|ом|о|у|ах|иях|ях|ы|ь|ию|ью|ю|ия|ья|я)$/';
	public $RVRE = '/^(.*?[аеиоуыэюя])(.*)$/';
	public $DERIVATIONAL = '/[^аеиоуыэюя][аеиоуыэюя]+[^аеиоуыэюя]+[аеиоуыэюя].*(?<=о)сть?$/';

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
		$word = strtr($word, 'ё', 'е');
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
			$this->s($RV, '/и$/', '');
			# Step 3
			if($this->m($RV, $this->DERIVATIONAL))
				$this->s($RV, '/ость?$/', '');
				# Step 4
			if(!$this->s($RV, '/ь$/', '')){
				$this->s($RV, '/ейше?/', '');
				$this->s($RV, '/нн$/', 'н');
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
