<?php
	include('../configure.php');
	$w=$_GET['w'];
	$h=isset($_GET['h'])?$_GET['h']:$w;    // h est facultatif, =w par défaut
	$x=isset($_GET['x'])?$_GET['x']:0;    // x est facultatif, 0 par défaut
	$y=isset($_GET['y'])?$_GET['y']:0;    // y est facultatif, 0 par défaut
	$filename =  $_GET['src'];
	
	
	$image = imagecreatefromjpeg(TMP_IMAGE_PATH .$filename);
	$crop = imagecreatetruecolor($w,$h);
	imagecopy($crop, $image, 0, 0, $x, $y, $w, $h );
	
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
	ImageJPEG($crop,TMP_IMAGE_PATH .$filename);
	
	// Print the name
	echo utf8_encode($filename);
?>