<?php

function GDVersion(){
	global $config;
	if(!isset($config['info']['gd'])){
		if(!extension_loaded('gd')){
			return ($config['info']['gd'] = 0);
		}
		if(function_exists('gd_info')){
			$ver_info = gd_info();
			preg_match('/\d/', $ver_info['GD Version'],$match);
			$config['info']['gd'] = $match[0];
			return $match[0];
		}else{
			return ($config['info']['gd'] = 0);
		}
	}else{
		return $config['info']['gd'];
	}
}

function CreateThumb( $SrcFileName, $DstFileName, $MaxWidth, $MaxHeight ){
	if(is_file($DstFileName)){
		unlink($DstFileName);
	}
	$thumb = new TPicture($SrcFileName);
	$thumb->SetImageSize($MaxWidth, $MaxHeight);
	$thumb->SaveToFile($DstFileName);
}

function ImageSize( $FileName ){
	$size = getimagesize($FileName);
	$size['width'] = $size[0];
	$size['height'] = $size[1];
	return $size;
}
