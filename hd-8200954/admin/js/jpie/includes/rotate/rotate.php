<?php
//error_reporting(0);
include('../functions/functions.php');
include('../configure.php');

$filename = $_GET['src'];
$angle2rotate = $_GET['x'];

$new_name = get_next_name($filename);
// File and rotation
$degrees = $_GET['x'];
// Load
$source = imagecreatefromjpeg(TMP_IMAGE_PATH .$filename);
// Rotate
$rotate = imagerotate($source, $degrees, 0);
// Output
ImageJPEG($rotate,TMP_IMAGE_PATH .$new_name);  

echo $new_name;

?>