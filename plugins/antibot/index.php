<?php

// LinkorCMS Капча
// 2008 год Галицкий Александр
// В программе использованы куски кода проекта captcha.ru для искажения изображения.

if(!defined('VALID_RUN')){
	header("HTTP/1.1 404 Not Found");
	exit;
}

// -------------------------------------------------------------------------

$foreground_color = array(0, 0, 0);
$background_color = array(255, 255, 255);
$border = 5;
$border_color = 0xFFFFFF;
$num_chars = 4;
$char_height = 16;
$char_width = 10;
$code = GenRandomString($num_chars, '1234567890');
$captcha_width = $char_width * $num_chars - $num_chars;
$captcha_height = 40;

include_once($config['inc_dir']."picture.class.php");
$captcha = new TPicture();
$captcha->Brush = 0xEEEEEE;
$result_image = new TPicture();
$result_image->Brush = 0xEEEEEE;
$captcha->NewPicture($captcha_width, $captcha_height - 10, IMAGE_PNG);
$result_width = $captcha_width + 80;
$result_image->NewPicture($result_width, $captcha_height, IMAGE_PNG);
imagealphablending($captcha->gd, true);
imagecolortransparent($captcha->gd, 0xEEEEEE);
$font = imagecreatefrompng('images/font.png');
imagealphablending($font, true);
imagecolortransparent($font, 0xEEEEEE);
$foreground = imagecolorallocate($result_image->gd, $foreground_color[0], $foreground_color[1], $foreground_color[2]);
$background = imagecolorallocate($result_image->gd, $background_color[0], $background_color[1], $background_color[2]);
// Рисование --------------------------------------------------------------
// Рисуем цифры
$pole_wid = intval($captcha->Width / $num_chars);
for($i = 0; $i < $num_chars; ++$i){
	$c = $code[$i];
	$x = $char_width * $c + $c + 1;
	$posx = rand($pole_wid * $i + 1, $pole_wid * $i + $pole_wid - $char_width - 1) + rand(-1, 1);
	if($posx < 0){
		$posx = 0;
	}elseif($posx > $captcha->Width - $char_width){
		$posx = $captcha->Width - $char_width;
	}
	$posy = rand(3, $captcha->Height - $char_height - 3);
	$captcha->Copy($font, $x, 1, 10, $char_height, $posx, $posy);
}
// Рисуем капчу
$result_image->Draw($captcha->gd, ($result_width - $captcha_width) / 2, 5);
// Искажение
$result_MultiWave = new TPicture();
$result_MultiWave->NewPicture($result_width, $captcha_height, IMAGE_PNG);
imagealphablending($result_MultiWave->gd, true);
imagecolortransparent($result_MultiWave->gd, 0xEEEEEE);
imagefilledrectangle($result_MultiWave->gd, 0, 0, $result_width - 1, $captcha_height - 1, 0xEEEEEE);
// случайные параметры (можно поэкспериментировать с коэффициентами):
// частоты
$rand1 = mt_rand(700000, 1000000) / 15000000;
$rand2 = mt_rand(700000, 1000000) / 15000000;
$rand3 = mt_rand(700000, 1000000) / 15000000;
$rand4 = mt_rand(700000, 1000000) / 15000000;
// фазы
$rand5 = mt_rand(0, 3141592) / 1000000;
$rand6 = mt_rand(0, 3141592) / 1000000;
$rand7 = mt_rand(0, 3141592) / 1000000;
$rand8 = mt_rand(0, 3141592) / 1000000;
// амплитуды
$rand9 = mt_rand(400, 600) / 100;
$rand10 = mt_rand(400, 600) / 100;
for($x = 0; $x < $result_image->Width; $x++){
	for($y = 0; $y < $result_image->Height; $y++){
		// координаты пикселя-первообраза.
		$sx = $x + (sin($x * $rand1 + $rand5) + sin($y * $rand3 + $rand6)) * $rand9;
		$sy = $y + (sin($x * $rand2 + $rand7) + sin($y * $rand4 + $rand8)) * $rand10;
		// первообраз за пределами изображения
		if($sx < 0 || $sy < 0 || $sx >= $result_image->Width - 1 || $sy >= $result_image->Height - 1){
			//$color = 255;
		//$color_x = 255;
		//$color_y = 255;
		//$color_xy = 255;
		}else{ // цвета основного пикселя и его 3-х соседей для лучшего антиалиасинга
			$color = (imagecolorat($result_image->gd, $sx, $sy) >> 16) & 0xFF;
			$color_x = (imagecolorat($result_image->gd, $sx + 1, $sy) >> 16) & 0xFF;
			$color_y = (imagecolorat($result_image->gd, $sx, $sy + 1) >> 16) & 0xFF;
			$color_xy = (imagecolorat($result_image->gd, $sx + 1, $sy + 1) >> 16) & 0xFF;
		}
		// сглаживаем только точки, цвета соседей которых отличается
		if($color == $color_x && $color == $color_y && $color == $color_xy){
			$newcolor = $color;
		}else{
			$frsx = $sx - floor($sx); //отклонение координат первообраза от целого
			$frsy = $sy - floor($sy);
			$frsx1 = 1 - $frsx;
			$frsy1 = 1 - $frsy;
			// вычисление цвета нового пикселя как пропорции от цвета основного пикселя и его соседей
			$newcolor = floor($color * $frsx1 * $frsy1 + $color_x * $frsx * $frsy1 + $color_y * $frsx1 * $frsy + $color_xy * $frsx * $frsy);
		}
		imagesetpixel($result_MultiWave->gd, $x, $y, imagecolorallocate($result_MultiWave->gd, $newcolor, $newcolor, $newcolor));
	}
}

// Сглаживание
if(function_exists('imageconvolution')){
	$matrix = array(array(1, 2, 1), array(2, 4, 2), array(1, 2, 1));
	imageconvolution($result_MultiWave->gd, $matrix, 16, 0);
}

// Добавляем рамку
//if($border > 0){
//	imagerectangle($result_MultiWave->gd, 0, 0, $result_MultiWave->Width-1, $result_MultiWave->Height-1, $border_color);
//}
// -----------------------------------------------------------------------

header('Expires: Mon, 1 Jan 2006 00:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');

$result_MultiWave->SendToHTTPClient();
$captcha->Destruct();
$result_image->Destruct();
$result_MultiWave->Destruct();
$user->Def('captcha_keystring', $code);

// Восстанавливаем Referer
$user->Def('REFERER', $_SERVER['HTTP_REFERER']);
die();

?>