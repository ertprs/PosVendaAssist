<?php
//error_reporting(0);
include('../functions/functions.php');
include('../configure.php');

$oldName = $_GET['oldName'];
$newName = $_GET['newName'];

copy(TMP_IMAGE_PATH . $oldName,IMAGE_PATH .  $newName);
?>