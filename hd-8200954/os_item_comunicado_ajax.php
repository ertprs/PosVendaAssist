<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

/* Rotina para verificação de comunicados por Peça - HD 19052 */

$referencia = trim($_GET['referencia']);

if (strlen ($referencia) >0) {
	$sql =" SELECT count(*)
			FROM  tbl_comunicado 
			LEFT JOIN tbl_comunicado_peca USING(comunicado)
			LEFT JOIN tbl_peca PC_1  ON PC_1.peca = tbl_comunicado_peca.peca
			LEFT JOIN tbl_peca PC_2  ON PC_2.peca = tbl_comunicado.peca
			WHERE tbl_comunicado.fabrica = $login_fabrica
			AND   tbl_comunicado.ativo  IS TRUE
			AND ( tbl_comunicado.posto = $login_posto OR tbl_comunicado.posto IS NULL)
			AND (PC_1.referencia = '$referencia' OR PC_2.referencia = '$referencia')";
	$res = pg_exec($con,$sql);
	$qtde_comunicados = trim(pg_result($res,0,0));
	if ($qtde_comunicados>0){
		echo "ok";
	}else{
		echo "sem";
	}
}
?>
