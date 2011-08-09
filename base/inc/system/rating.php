<?php

/**
 * Возвращает имя файла изображения ранга в зависимости от количества оценок и их суммы.
 * @param $votes_amount
 * @param $votes
 * @return string
 */
function GetRatingImage( $votes_amount, $votes ){
	$default = 'images/rating_system/rating.gif';
	if($votes_amount==0){
		return $default;
	}
	$rating = round($votes/$votes_amount);
	if($rating>=1 && $rating<=5){
		return 'images/rating_system/rating'.$rating.'.gif';
	}else{
		return $default;
	}
}
