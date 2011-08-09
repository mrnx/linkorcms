<?php

/**
 * ¬ыполн€ет поиск в строке, находит слово и подсвечивает его. “ак-же укорачивает исходную строку дл€ вывода в результатах поиска и возвращает ее.
 * @param $text
 * @param $search
 * @return string
 */
function SCoincidence($text, $search){
	$swords = explode(' ',$search);
	$text = strip_tags($text);
	$result_text = '';
	$set_text = false;
	foreach($swords as $search){
		$pos = stripos($text, $search);
		if(is_integer($pos)){ // ≈сли нашли слово
			$slen = strlen($search);
			if(!$set_text){ // ќбрезаем текст по этому слову если оно первое найденное
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
			// ѕодсвечиваем
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

/**
 * ¬ыполн€ет поиск сразу нескольких слов в строке и возвращает истину только если все слова в ней присутствуют.
 * @param $text
 * @param $search
 * @return bool
 */
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
