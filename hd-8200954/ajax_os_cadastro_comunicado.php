<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

header("Expires: 0");
header("Cache-Control: no-cache, public, must-revalidate, post-check=0, pre-check=0");
header("Pragma: no-cache, public");

$fabrica = trim($_GET ['fabrica']);
$produto = trim($_GET ['produto']);

if (strlen ($fabrica) >0 AND strlen ($produto) >0) {
	$sql ="SELECT tbl_comunicado.comunicado
		FROM  tbl_comunicado 
		JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado.produto AND tbl_produto.fabrica_i = $fabrica
		WHERE tbl_produto.referencia = '$produto'
		AND tbl_comunicado.fabrica = $fabrica
		AND tbl_comunicado.ativo IS TRUE ";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "ok";
	}
	else{
		$sqlx = "SELECT tbl_comunicado.comunicado 
				 FROM tbl_comunicado
				 JOIN tbl_comunicado_produto USING(comunicado)
				 JOIN tbl_produto ON tbl_produto.produto = tbl_comunicado_produto.produto AND tbl_produto.fabrica_i = $fabrica
				 WHERE tbl_produto.referencia = '$produto'
				 AND tbl_comunicado.fabrica = $fabrica
				 AND tbl_comunicado.ativo IS TRUE";
		$resx = pg_exec($con,$sqlx);

		if (pg_numrows($resx) > 0) {
			echo "ok";
		}else{
			echo "sem";
		}
	}
}
?>
