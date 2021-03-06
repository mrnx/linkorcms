<?php

/**
 * �������������� ���� 7.79-2000
 * @param string $text
 * @param bool $strip_spaces
 * @return string
 */
function Translit( $text, $strip_spaces = true ){
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
	));
	return $text;
}

/**
 * ���������������� ���� 7.79-2000
 * @param string $text
 * @param bool $strip_tospaces
 * @return string
 */
function Retranslit( $text, $strip_tospaces = true ){
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
	));
	return $text;
}

/**
 * �������������� ������ ��� ������������� � URL
 * @param string $text
 * @return string
 */
function Translit4Url( $text ){
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
		'�' => '', '�' => '',
		'�' => 'y', '�' => 'Y',
		'�' => '', '�' => '',
		'�' => 'e', '�' => 'E',
		'�' => 'yu', '�' => 'YU',
		'�' => 'ya', '�' => 'YA',
	));
	$text = preg_replace('/[^a-zA-Z0-9\.\-_ ]*/', '', $text);
	$text = trim($text, ' _');
	$text = str_replace(' ', '_', $text);
	return $text;
}
