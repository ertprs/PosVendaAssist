<?php
$arquivo = $_GET['arquivo']; 

if (strlen($arquivo)>0){
	$content = file_get_contents($arquivo); 
	$name = pathinfo($arquivo, PATHINFO_FILENAME);
	$ext = pathinfo($arquivo, PATHINFO_EXTENSION);
	if (strpos($ext, '?')) {
		$ext = substr(pathinfo($arquivo, PATHINFO_EXTENSION), 0, strpos(pathinfo($arquivo, PATHINFO_EXTENSION), '?'));
	}	
	header("Content-type: application/octet-stream"); 
	header("Content-Length:".strlen($content)); 
	header("Content-Disposition: attachment; filename=\"$name.$ext\""); 
	header('Expires: 0'); 
	header('Pragma: no-cache'); 
	die($content);
	
}