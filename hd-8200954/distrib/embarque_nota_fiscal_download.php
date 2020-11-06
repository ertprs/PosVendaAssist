<?php
$arquivo = $_GET['arquivo']; 
header("Content-type: application/save"); 
header("Content-Length:".filesize($arquivo)); 
header('Content-Disposition: attachment; filename="' . $arquivo . '"'); 
header('Expires: 0'); 
header('Pragma: no-cache'); 
readfile("$arquivo"); 
?>