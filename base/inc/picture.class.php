<?php

// LinkorCMS 1.3
// © 2006 - 2010 Галицкий Александр Николаевич
// www.linkorcms.ru
// mail: linkorcms@yandex.ru

define('IMAGE_JPEG', 'jpeg');
define('IMAGE_PNG', 'png');
define('IMAGE_GIF', 'gif');
define('IMAGE_WBMP', 'wbmp');

class TPicture
{
	// Параметры рисования
	public $Brush = 0xFFFFFF;
	public $JpegQuality = 90;
	// Параметры картинки
	public $gd = null; // Указатель на картинку в GD
	public $Format = IMAGE_JPEG;
	public $SaveFormat = IMAGE_JPEG;
	public $Width = 100;
	public $Height = 100;
	public $NewWidth = 100;
	public $NewHeight = 100;

	public function TPicture( $ImgFileName = null )
	{
		if($ImgFileName !== null){
			$this->CreateFromFile($ImgFileName);
		}
	}

	public function NewPicture( $Width = 100, $Height = 100, $Format = IMAGE_JPEG )
	{
		if(function_exists('ImageCreateTrueColor')){
			$this->gd = ImageCreateTrueColor($Width, $Height);
		}else{
			$this->gd = ImageCreate($Width, $Height);
		}
		$this->Width = $this->NewWidth = $Width;
		$this->Height = $this->NewHeight = $Height;
		$this->Format = $Format;
		$this->SaveFormat = $Format;
		imagealphablending($this->gd, false);
		imagefill($this->gd, 0, 0, $this->Brush);
	}

	public function CreateFromFile( $ImgFileName, $Width = 0, $Height = 0 )
	{
		if(!file_exists($ImgFileName)){
			ErrorHandler(NOTICE, 'Файл не найден', 'TPicture::CreateFromFile');
			return;
		}
		if($this->gd != null){
			$this->Destruct();
		}
		$size = getimagesize($ImgFileName);
		$format = strtolower(substr($size['mime'], strpos($size['mime'], '/') + 1));
		$vformat = false;
		if($format == 'jpg' || $format == 'jpeg'){
			$vformat = true;
			$this->Format = IMAGE_JPEG;
			$this->gd = ImageCreateFromJPEG($ImgFileName);
		}elseif($format == 'png'){
			$vformat = true;
			$this->Format = IMAGE_PNG;
			$this->gd = ImageCreateFromPNG($ImgFileName);
		}elseif($format == 'gif'){
			$vformat = true;
			$this->Format = IMAGE_GIF;
			$this->gd = ImageCreateFromGIF($ImgFileName);
		}elseif($format == 'wbmp'){
			$vformat = true;
			$this->Format = IMAGE_WBMP;
			$this->gd = ImageCreateFromWBMP($ImgFileName);
		}
		if($vformat){
			$this->SaveFormat = $this->Format;
			$this->Width = $size[0];
			$this->Height = $size[1];
			$this->NewWidth = 0;
			$this->NewHeight = 0;
			$this->JpegQuality = 95;
		}
	}

	public function Destruct()
	{
		imagedestroy($this->pic['src']);
		$this->gd = null;
	}

	public function SetSaveFormat( $Format = 'jpeg' )
	{
		$this->SaveFormat = $Format;
	}

	public function SetImageSize( $NewWidth, $NewHeight )
	{
		$this->NewWidth = $NewWidth;
		$this->NewHeight = $NewHeight;
	}

	public function SetJpegQuality( $quality )
	{
		$this->JpegQuality = $quality;
	}

	public function SetSize()
	{
		if(($this->NewWidth == '0' && $this->NewHeight == '0')
			|| ($this->NewWidth == $this->Width && $this->NewHeight == $this->Height))
		{ // Изменение размера не требуется
			return;
		}

		$min_width = false;
		$min_height = false;
		if($this->NewWidth == '0'){
			$min_height = true;
		}elseif($this->NewHeight == '0'){
			$min_width = true;
		}else{
			$min_width = true;
		}

		if($min_width){
			$nwidth = $this->NewWidth;
			$nheight = round($this->Height / ($this->Width / $this->NewWidth));
		}else{
			$nheight = $this->NewHeight;
			$nwidth = round($this->Width / ($this->Height / $this->NewHeight));
		}
		if(function_exists('ImageCreateTrueColor')){
			$temp = ImageCreateTrueColor($nwidth, $nheight);
		}else{
			$temp = ImageCreate($nwidth, $nheight);
		}
		imagefill($temp, 0, 0, $this->Brush);
		if(function_exists('imagecopyresampled')){
			imagecopyresampled($temp, $this->gd, 0, 0, 0, 0, $nwidth, $nheight, $this->Width, $this->Height);
		}else{
			imagecopyresized($temp, $this->gd, 0, 0, 0, 0, $nwidth, $nheight, $this->Width, $this->Height);
		}
		imagedestroy($this->gd);
		$this->gd = $temp;
		$this->Width = imagesx($temp);
		$this->Height = imagesy($temp);
	}

	public function StreachDraw( $Src, $X, $Y, $Width, $Height )
	{
		if(function_exists('imagecopyresampled')){
			imagecopyresampled($this->gd, $Src, $X, $Y, 0, 0, $Width, $Height, imagesx($Src), imagesy($Src));
		}else{
			imagecopyresized($this->gd, $Src, $X, $Y, 0, 0, $Width, $Height, imagesx($Src), imagesy($Src));
		}
	}

	public function Draw( $Src, $X, $Y )
	{
		$this->StreachDraw($Src, $X, $Y, imagesx($Src), imagesy($Src));
	}

	public function Copy( $Src, $SrcX, $SrcY, $SrcWidth, $SrcHeight, $DstX, $DstY )
	{
		if(function_exists('imagecopyresampled')){
			imagecopyresampled($this->gd, $Src, $DstX, $DstY, $SrcX, $SrcY, $SrcWidth, $SrcHeight, $SrcWidth, $SrcHeight);
		}else{
			imagecopyresized($this->gd, $Src, $DstX, $DstY, $SrcX, $SrcY, $SrcWidth, $SrcHeight, $SrcWidth, $SrcHeight);
		}
	}

	public function SendToHTTPClient()
	{
		@Header('Content-Type: image/'.$this->SaveFormat);
		$this->SetSize();
		if($this->SaveFormat == 'jpeg'){
			imageJPEG($this->gd, '', $this->JpegQuality);
		}elseif($this->SaveFormat == 'png'){
			imagePNG($this->gd);
		}elseif($this->SaveFormat == 'gif'){
			imageGIF($this->gd);
		}elseif($this->SaveFormat == 'wbmp'){
			imageWBMP($this->gd);
		}
	}

	public function SaveToFile( $FileName, $SaveFormat = null )
	{
		if($SaveFormat !== null){
			$this->SetSaveFormat($SaveFormat);
		}
		$this->SetSize();
		if($this->SaveFormat == 'jpeg'){
			imageJPEG($this->gd, $FileName, $this->JpegQuality);
		}elseif($this->SaveFormat == 'png'){
			imagePNG($this->gd, $FileName);
		}elseif($this->SaveFormat == 'gif'){
			imageGIF($this->gd, $FileName);
		}elseif($this->SaveFormat == 'wbmp'){
			imageWBMP($this->gd, $FileName);
		}
	}
}

?>