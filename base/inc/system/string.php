<?php

/**
 * ����� �����, ������� ������� ��������� ���������, �� �����
 * @param string $text
 * @param string $maxWordLength
 * @return string
 */
function DivideWord( $text, $maxWordLength='30' ){
	return wordwrap($text, $maxWordLength, chr(13), 1);
}

/**
 * ���������� ��������� ������ ������������ �����
 * @param int $length ������ ������
 * @param string $chars
 * @return String
 */
function GenRandomString($length, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'){
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
 * @param int $length ����� ������
 * @return String
 */
function GenBPass($length){
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
