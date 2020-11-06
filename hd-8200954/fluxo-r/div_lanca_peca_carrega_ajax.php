<?
#--------------------------------------------------------------------------------------
# Este programa lê do banco de dados e carraga os campos do DIV_LANCA_PECA
#--------------------------------------------------------------------------------------
include 'cabecalho-ajax.php';

$produto_rg_item   = $_GET['produto_rg_item'];
$produto_rg_item   = trim (str_replace ("'","",$produto_rg_item));
if (strlen ($produto_rg_item) > 0) {
	$sql = "SELECT posto FROM tbl_produto_rg_item WHERE tbl_produto_rg_item.produto_rg_item = $produto_rg_item";
	$res = pg_exec ($con,$sql);
	$posto = pg_result ($res,0,0);
	if ($posto <> $cook_posto) {
		echo "<erro>Produto não pertence a este posto</erro>";
		exit;
	}

	$sql = "SELECT * FROM tbl_produto_rg_item WHERE tbl_produto_rg_item.produto_rg_item = $produto_rg_item";
	$res = pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (strlen ($msg_erro) > 0) {
		echo "<erro>" . $msg_erro . "</erro>";
		exit;
	}

	echo "<ok>";
	echo pg_result ($res,0,serie);
	echo "|";
	echo pg_result ($res,0,defeito_reclamado);

	$sql = "SELECT referencia, descricao FROM tbl_peca JOIN tbl_produto_rg_peca USING (peca) WHERE tbl_produto_rg_peca.produto_rg_item = $produto_rg_item ORDER BY tbl_produto_rg_peca.produto_rg_peca";
	$res = pg_exec ($con,$sql);

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		echo "|";
		echo pg_result ($res,$i,referencia);
		echo " - ";
		echo pg_result ($res,$i,descricao);
	}

	echo "||||||||||||||";
	echo "</ok>";
}


$produto_rg   = $_GET['produto_rg'];
$produto_rg   = trim (str_replace ("'","",$produto_rg));

$produto      = $_GET['produto'];
$produto      = trim (str_replace ("'","",$produto));

if (strlen ($produto_rg) > 0 AND strlen ($produto) > 0 ) {
	$sql = "SELECT posto FROM tbl_produto_rg WHERE tbl_produto_rg.produto_rg = $produto_rg";
	$res = pg_exec ($con,$sql);
	$posto = pg_result ($res,0,0);
	if ($posto <> $cook_posto) {
		echo "<erro>Produto não pertence a este posto</erro>";
		exit;
	}

	$sql = "SELECT * FROM tbl_produto_rg_pedido WHERE tbl_produto_rg_pedido.produto_rg = $produto_rg AND tbl_produto_rg_pedido.produto = $produto";
	$res = pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (strlen ($msg_erro) > 0) {
		echo "<erro>" . $msg_erro . "</erro>";
		exit;
	}

	echo "<ok>";
	echo "0|0";

	$sql = "SELECT tbl_peca.referencia, tbl_peca.descricao, tbl_produto_rg_pedido.qtde FROM tbl_peca JOIN tbl_produto_rg_pedido USING (peca) WHERE tbl_produto_rg_pedido.produto_rg = $produto_rg AND tbl_produto_rg_pedido.produto = $produto ORDER BY tbl_produto_rg_pedido.produto_rg_pedido";
	$res = pg_exec ($con,$sql);

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		echo "|";
		echo pg_result ($res,$i,referencia);
		echo " - ";
		echo pg_result ($res,$i,descricao);
		echo ";";
		echo pg_result ($res,$i,qtde);
	}

	echo "|;|;|;|;|;|;|;|;|;|;|;|;|;|";
	echo "</ok>";
}

?>