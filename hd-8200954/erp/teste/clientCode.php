<?php
	include("upload.class.php");
	//
	$maxSize=1024*1000;//the max file size for images in bytes.
	$u=new uploader($maxSize, "/www/assist/www/erp/imagens/fotos/");
	$imageName=$u->upload("file");
?>