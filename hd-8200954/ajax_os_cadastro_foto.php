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
	$sql ="SELECT  imagem FROM     tbl_produto
		JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
		LEFT JOIN tbl_produto_pais  using(produto)
		WHERE    tbl_produto.referencia = '$produto'
		AND      tbl_linha.fabrica = $login_fabrica
		AND      tbl_produto.ativo
		AND      tbl_produto.produto_principal";
	$res = pg_exec($con,$sql);
	if ((pg_numrows($res) > 0) and (strlen(pg_result($res,0,imagem))>0)) {
		echo "ok";
	}else{
		echo "sem";
	}
}
?>
