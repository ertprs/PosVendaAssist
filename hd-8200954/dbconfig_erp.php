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
#echo "<CENTER><h1>ATEN��O</h1>";
#echo "<h3>O sistema passar� por manuten��o t�cnica</h3";
#echo "<h3>Dentro de 60 minutos ser� restabelecido</h3";
#echo "<h3> </h3";
#echo "<p><h3>Agradecemos a compreens�o!</h3>";
#exit;


?>
