<?
require '../dbconfig.php';
require '../includes/dbconnect-inc.php';

require 'autentica_revenda.php';
include '../ajax_cabecalho.php';
$aba = 5;
$title = "Lista de Produtos";
include "cabecalho.php";
if(isset($_GET["excluir"])){
	$sql = "DELETE FROM tbl_revenda_produto where revenda_produto=".$_GET["excluir"];
	$res = pg_exec ($con,$sql);
}

$sql = "SELECT  revenda_produto,
		tbl_revenda_produto.referencia AS revenda_referencia,
		tbl_revenda_produto.descricao  AS revenda_descricao ,
		tbl_produto.referencia         AS produto_referencia,
		tbl_produto.descricao          AS produto_descricao ,
		tbl_produto.voltagem
	FROM tbl_produto
	JOIN tbl_revenda_produto USING(produto)
	JOIN tbl_linha           USING(linha)
	WHERE tbl_linha.fabrica           = $login_fabrica
	AND   tbl_revenda_produto.revenda = $login_revenda
	ORDER BY tbl_revenda_produto.descricao,tbl_produto.descricao";
//tbl_revenda_produto.referencia,
$res = pg_exec ($con,$sql);
if(pg_numrows($res)>0){
	echo "<br><table class='HD' align='center' width='700' border='0' cellspacing='0' cellpadding='2'>";

	echo "<tr class='Titulo'>";
	echo "<td align='left' width='100'> <b>Código Revenda</td>";
	echo "<td align='left'> <b>Descrição Revenda</td>";
	echo "<td align='left' width='100'> <b>Código Fabricante</td>";
	echo "<td align='left'> <b>Descrição Fabricante</td>";

	echo "<td align='left'> <b>Voltagem</td>";
	echo "</tr>";

	for($i = 0 ; $i < pg_numrows($res) ; $i++){
		$revenda_produto = pg_result($res,$i,revenda_produto);
		$revenda_referencia = pg_result($res,$i,revenda_referencia);
		$revenda_descricao  = pg_result($res,$i,revenda_descricao);
		$produto_referencia = pg_result($res,$i,produto_referencia);
		$produto_descricao  = pg_result($res,$i,produto_descricao);
		$voltagem   = pg_result($res,$i,voltagem);

		$cor = "#ffffff";
		if ($i % 2 == 0) $cor = "#FFEECC";

		echo "<tr bgcolor='$cor' class='Conteudo' height='15'>";
		echo "<td align='left' width='100'>$revenda_referencia</td>";
		echo "<td align='left' >$revenda_descricao</td>";
		echo "<td align='left' width='100'>$produto_referencia</td>";
		echo "<td align='left' >$produto_descricao</td>";
		echo "<td align='lef' >$voltagem</td>";
		//echo "<td align='lef' ><a href='?excluir=$revenda_produto'>Excluir</a></td>";

		echo "</tr>";
	}
	echo "</table>";
}

include "rodape.php";
?>