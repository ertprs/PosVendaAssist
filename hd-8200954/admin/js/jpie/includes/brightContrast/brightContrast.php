<?php
//error_reporting(0);
include('../functions/functions.php');
include('../configure.php');

$filename = $_GET['src'];

$new_name = get_next_name($filename);

// File and rotation
$brightness = $_GET['x'];
// Load
$source = imagecreatefromjpeg(TMP_IMAGE_PATH .$filename);

if ($source && imagefilter($source, IMG_FILTER_BRIGHTNESS, $brightness)) {
	ImageJPEG($rotate,TMP_IMAGE_PATH .$new_name);  
} else {
    echo 'Image brightness change failed.';
}




echo $new_name;

?>