<?php

/**
 * ������� �������� � http ������.
 * @param $url
 * @return mixed
 */
function Url( $url ){
	return preg_replace(array('/^https:\/\//', '/^http:\/\//', '/^www\./'), '', $url);
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
