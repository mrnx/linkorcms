<?php

/**
 * ������������ �������
 */

/**
 * ������������ �������
 * @param $ClassName
 */
function __autoload( $ClassName ){
	require_once $GLOBALS['system_autoload'][$ClassName];
}

/**
 * ����������� ������ ��� ������������
 * @param $ClassName �������� ������
 * @param $FileName ��� �����
 */
function RegisterClass( $ClassName, $FileName ){
	$GLOBALS['system_autoload'][$ClassName] = $FileName;
}

/**
 * ����������� ������� ������� ��� ������������
 * @param $ClassesArray ������������� ������ ���� ������� � ������
 * @param string $Path ���� � ������
 */
function RegisterClassesArray( $ClassesArray, $Path = '' ){
	foreach($ClassesArray as $class=>$file){
		$GLOBALS['system_autoload'][$class] = $Path.$file;
	}
}

/**
 * ������� ����� ��� ������ ������� �� ������������
 * @param string|array $ClassName
 */
function UnregisterClass( $ClassName ){
	if(is_array($ClassName)){
		foreach($ClassName as $class){
			unset($GLOBALS['system_autoload'][$class]);
		}
	}else{
		unset($GLOBALS['system_autoload'][$ClassName]);
	}
}

