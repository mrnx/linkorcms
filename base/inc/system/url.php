<?php

// ������� �������� � http ������.
function Url( $url ){
	$url = preg_replace('/^https:\/\//', '', $url);
	$url = preg_replace('/^http:\/\//', '', $url);
	$url = preg_replace('/^www\./', '', $url);
	return $url;
}

//��� ��������� ������ - ���� ��� ������ �� �������� ������ �� �����
// - �� �������� �� ������������.
//���� ��� ������ �� ������� ������� (������ �����) - �� �������� ����������
//(��� ���������� ����� "������������� �������� ��� ������� ������").
function UrlRender( $url ){
	global $config;
	if($config['general']['specialoutlinks']) {
		if(!IsMainHost($url)){
			return 'index.php?name=plugins&p=out&url='.urlencode(Url($url));
		}else{
			return 'http://'.Url($url);
		}
	}else{
		return 'http://'.Url($url);
	}
}
