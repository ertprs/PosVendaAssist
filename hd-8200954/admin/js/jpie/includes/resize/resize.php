<?php
	include('../configure.php');

	$new_width = $_GET['w'];
	$new_height = $_GET['h'];
	$filename =  $_GET['src'];

	$src_img = imagecreatefromjpeg(TMP_IMAGE_PATH . $filename);
	$thumb = ImageCreateTrueColor($new_width,$new_height);
	$size=GetImageSize(TMP_IMAGE_PATH . $filename);
	
	ImageCopyResampled($thumb, $src_img, 0,0,0,0,($new_width),($new_height),$size[0],$size[1]);
	
	//Get the next name	
	$ext = strtolower(substr(($t=strrchr($filename,'.'))!==false?$t:'',1));
	$i=1;
	while (file_exists(TMP_IMAGE_PATH . $filename)) {
	 	if (strrpos($filename,"(")) {
			$filename = substr($filename, 0,strrpos($filename,"("))."(".$i.").".$ext;
		} else {
			$filename = substr($filename, 0,strrpos($filename,"."))."(".$i.").".$ext;			
		}
			
		$i++;
	}
	
	// Save the image
	ImageJPEG($thumb,TMP_IMAGE_PATH . $filename);
	
	// Print the name
	echo $filename;
?>