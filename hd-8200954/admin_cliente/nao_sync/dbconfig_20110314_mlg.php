<?php
$ip        = getenv ("REMOTE_ADDR");
$dbhost    = "192.168.0.3";
$dbbanco   = "postgres";
$dbport    = 5432;
$dbusuario = "telecontrol";
$dbsenha   = "tc2006";

$programa = $_SERVER["SCRIPT_FILENAME"] ;
if (strpos ($programa,"teste") > 0 or strpos ($programa,"TESTE") > 0) {
	$dbnome  = "teste";
}else{
	$dbnome  = "telecontrol";
}

/*
echo "<CENTER><h1>ATENÇÃO</h1>";
echo "<h3>O sistema passará por manutenção técnica</h3";
echo "<h3>Dentro de algumas horas será restabelecido</h3";
echo "<h3> </h3";
echo "<p><h3>Agradecemos a compreensão!</h3>";
exit;
*/

?>
