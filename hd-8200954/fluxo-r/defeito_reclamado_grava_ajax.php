<?
#--------------------------------------------------------------------------------------
# Este programa cadastra o Defeito Reclamado num dos produtos da planilha
# Deve receber o "produto_rg_item" e o "defeito reclamado" em formato texto livre
# faz a validação para ver se o produto_rg_item pertence ao posto que está logado
#--------------------------------------------------------------------------------------
include 'cabecalho-ajax.php';

$produto_rg_item   = $_GET['produto_rg_item'];
$defeito_reclamado = $_GET['defeito_reclamado'];

$produto_rg_item   = trim (str_replace ("'","",$produto_rg_item));
$defeito_reclamado = strtoupper (trim (str_replace ("'","",$defeito_reclamado)));

if (strlen ($produto_rg_item) > 0) {
	$sql = "SELECT posto FROM tbl_produto_rg_item WHERE tbl_produto_rg_item.produto_rg_item = $produto_rg_item";
	$res = pg_exec ($con,$sql);
	$posto = pg_result ($res,0,0);
	if ($posto <> $cook_posto) {
		echo "<erro>Produto não pertence a este posto</erro>";
		exit;
	}

	$sql = "UPDATE tbl_produto_rg_item SET defeito_reclamado = '$defeito_reclamado' WHERE produto_rg_item = $produto_rg_item";
	$res = pg_exec ($con,$sql);
	$msg_erro = pg_errormessage($con);
	if (strlen ($msg_erro) > 0) {
		echo "<erro>" . $msg_erro . "</erro>";
		exit;
	}

	echo "<ok>Registro Atualizado $cook_posto</ok>";
}
?>