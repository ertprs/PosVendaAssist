<?php
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include 'autentica_usuario.php';

$produto   = trim($_GET ['produto']);

if (strlen ($produto) > 0) {
	$sql ="SELECT  imagem FROM     tbl_produto
		JOIN     tbl_linha ON tbl_produto.linha = tbl_linha.linha
		LEFT JOIN tbl_produto_pais  using(produto)
		WHERE    tbl_produto.referencia = '$produto'
		AND      tbl_linha.fabrica = $login_fabrica
		AND      tbl_produto.ativo
		AND      tbl_produto.produto_principal";
	$res = pg_exec($con,$sql);
	if (pg_numrows($res) == 1) {
		$link = pg_result($res,0,imagem);
		header ("Location: $link");
		exit;
	}else{
		echo "<center><strong>Não foi cadastrado nenhuma foto para este produto</strong></center>";
	}
}
?>
