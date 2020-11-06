<?php
include "../dbconfig.php";
include "../includes/dbconnect-inc.php";

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

		$sql = "SELECT cidade FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) == 0){

			$sql = "INSERT INTO tbl_cidade (nome, estado, cep, cod_ibge) VALUES ('$cidade', '$estado', '$cep', '66666666')";
			$res = pg_query($con, $sql);

		}

		echo "ok;". $logradouro . ";" . $bairro . ";" . $cidade . ";" . $estado ;
	}
}

?>
