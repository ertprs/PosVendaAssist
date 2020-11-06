<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$pid = pg_get_pid($con);
echo "Seu PID: $pid";
flush ();
?>

