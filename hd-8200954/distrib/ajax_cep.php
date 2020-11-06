<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

$cep = $_GET ['cep'];
$cep = str_replace (".","",$cep);
$cep = str_replace ("-","",$cep);
$cep = str_replace (" ","",$cep);

if (strlen ($cep) == 8) {
	$sql = "SELECT * FROM tbl_cep WHERE cep = '$cep'";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$logradouro = trim (pg_result ($res,0,logradouro));
		$bairro     = trim (pg_result ($res,0,bairro));
		$cidade     = trim (pg_result ($res,0,cidade));
		$estado     = trim (pg_result ($res,0,estado));
		$tipo       = trim (pg_result ($res,0,tipo));

		switch($tipo){
			case "A" : $logradouro = "Av. ".$logradouro;break;
			case "R" : $logradouro = "R. " .$logradouro;break;
		}
		echo "ok;". $logradouro . ";" . $bairro . ";" . $cidade . ";" . $estado ;
	}
}

?>
