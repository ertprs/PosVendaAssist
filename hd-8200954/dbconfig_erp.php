<?php
$ip        = getenv ("REMOTE_ADDR");
#$dbhost    = "10.40.244.132";
$dbhost    = `cat /mnt/webdata/var/database.txt`;
$dbbanco   = "postgres";
$dbport    = 5432;
$dbusuario = "erp";
$dbsenha   = "erp";
$dbnome    = "telecontrol";

error_reporting(0);
#echo "<CENTER><h1>ATENÇÃO</h1>";
#echo "<h3>O sistema passará por manutenção técnica</h3";
#echo "<h3>Dentro de 60 minutos será restabelecido</h3";
#echo "<h3> </h3";
#echo "<p><h3>Agradecemos a compreensão!</h3>";
#exit;


?>
