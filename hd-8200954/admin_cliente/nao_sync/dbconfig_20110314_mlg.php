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
echo "<CENTER><h1>ATEN��O</h1>";
echo "<h3>O sistema passar� por manuten��o t�cnica</h3";
echo "<h3>Dentro de algumas horas ser� restabelecido</h3";
echo "<h3> </h3";
echo "<p><h3>Agradecemos a compreens�o!</h3>";
exit;
*/

?>
