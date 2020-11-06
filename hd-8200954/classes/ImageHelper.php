<?php

class ImageHelper {

	public static function openImage($file,$ext = null){
		if(empty($ext))
			$ext = end(explode('.',$file));
		$ext = strtolower($ext);
		switch($ext){
			case 'png':
				return imagecreatefrompng($file);
			case 'jpg':
			case 'jpeg':
				return imagecreatefromjpeg($file);
			case 'bmp':
				return imagecreatefromwbmp($file);
			default:
				throw new Exception('Image Extension('.$ext.') not Supported');
		}
	}


	public static function saveImage($img,$file,$ext = null){
		if(empty($ext))
			$ext = end(explode('.',$file));
		$ext = strtolower($ext);
		switch($ext){
			case 'png':
				return imagepng($img,$file);
			case 'jpg':
			case 'jpeg':
				return imagejpeg($img,$file);
			case 'bmp':
				return imagewbmp($img,$file);
			default:
				throw new Exception('Image Extension('.$ext.') not Supported');
		}
	}

	public static function convertImage($file,$to,$from=null){
		if(empty($from))
			$from = end(explode('.',$file));
		$img = ImageHelper::openImage($file,$from);
		ImageHelper::saveImage($img,$file,$to);
	}


}