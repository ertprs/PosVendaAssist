<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';



$btn_acao = strtoupper (trim ($_POST['btn_acao']));
if ($btn_acao == "GRAVAR") {

	$res = pg_exec ($con,"BEGIN TRANSACTION");

	#-------------- Confirma conferência atual ----------#
	$qtde_item   = $_POST['qtde_item'];
	$faturamento = $_POST['faturamento'];

	for ($i = 0 ; $i < $qtde_item ; $i++) {
		$faturamento_item = $_POST['faturamento_item_' . $i];
		$excluir          = $_POST['excluir_'          . $i];
		$referencia       = $_POST['referencia_'       . $i];
		$qtde             = $_POST['qtde_'             . $i];
		$nf_origem        = $_POST['nf_origem_'        . $i];

		if (strlen ($excluir) > 0) {
			$sql = "DELETE FROM tbl_faturamento_item WHERE faturamento = $faturamento AND faturamento_item = $faturamento_item";
			$res = pg_exec ($con,$sql);
		}else{
			if (strlen ($referencia) > 0) {
				$sql = "SELECT peca FROM tbl_peca WHERE referencia = '$referencia' AND fabrica IN (".implode(",", $fabricas).")";
				$res = pg_exec ($con,$sql);
				$peca = pg_result ($res,0,0);
#				echo $sql;

				$nf_origem = "000000" . trim ($nf_origem) ;
				$nf_origem = substr ($nf_origem , strlen ($nf_origem) - 6 , 6);

				$sql = "SELECT faturamento FROM tbl_faturamento WHERE nota_fiscal = '$nf_origem' AND fabrica IN (".implode(",", $fabricas).") AND distribuidor IS NULL";
				$res = pg_exec ($con,$sql);
				$devolucao_origem = pg_result ($res,0,0);

				$sql = "SELECT preco, aliq_icms FROM tbl_faturamento_item WHERE faturamento = $devolucao_origem AND peca = $peca";
				$res = pg_exec ($con,$sql);
				$preco = pg_result ($res,0,preco);
				$aliq_icms = pg_result ($res,0,aliq_icms);

				$sql = "INSERT INTO tbl_faturamento_item (faturamento, peca, qtde, preco, aliq_icms, devolucao_origem) VALUES ($faturamento, $peca, $qtde, $preco, $aliq_icms, $devolucao_origem)";
				$res = pg_exec ($con,$sql);
			}	
		}

	}
	
	if (strlen (pg_errormessage($con)) == 0) {
		$res = pg_exec ($con,"COMMIT TRANSACTION");
		header ("Location: nf_devolucao_item.php?faturamento=$faturamento");
		exit;
	}else{
		$res = pg_exec ($con,"ROLLBACK TRANSACTION");
	}
}


?>

<html>
<head>
<title>Itens da NF de Devolução</title>
</head>

<body>

<? include 'menu.php' ?>


<?
$sql = "SELECT	tbl_faturamento.nota_fiscal, 
				tbl_faturamento.faturamento, 
				tbl_faturamento_item.faturamento_item, 
				tbl_faturamento_item.qtde, 
				origem.nota_fiscal AS nf_origem, 
				tbl_peca.referencia, 
				tbl_peca.descricao 
		FROM tbl_faturamento
		LEFT JOIN tbl_faturamento_item USING (faturamento) 
		LEFT JOIN tbl_faturamento origem ON tbl_faturamento_item.devolucao_origem = origem.faturamento 
		LEFT JOIN tbl_peca ON tbl_faturamento_item.peca = tbl_peca.peca
		WHERE tbl_faturamento.distribuidor = $login_posto
		AND   tbl_faturamento.tipo_pedido = 99
		AND   tbl_faturamento.devolucao_concluida IS NOT TRUE
		ORDER BY origem.nota_fiscal, tbl_peca.referencia";
$res = pg_exec ($con,$sql);
if (pg_numrows ($res) > 0) {
	$nota_fiscal = pg_result ($res,0,nota_fiscal);
	$faturamento = pg_result ($res,0,faturamento);
}else{
	$sql = "INSERT INTO tbl_faturamento (fabrica, distribuidor, posto, tipo_pedido, cfop, natureza, emissao, saida, total_nota)
			VALUES ($login_fabrica, $login_posto, 13996, 99 , '6202', 'DEVOLUCAO MERCADORIA',CURRENT_DATE, CURRENT_DATE, 0)";
	$resX = pg_exec ($con,$sql);

	$sql = "SELECT CURRVAL ('seq_faturamento')";
	$resX = pg_exec ($con,$sql);
	$faturamento = pg_result ($resX,0,0);
	$nota_fiscal = "000000";
}

?>

<center><h1>Itens da NF de Devolução - <? echo $nota_fiscal ?></h1></center>

<p>

<table width='600' align='center'>
<tr bgcolor='#FF9933' style='color:#ffffff ; font-weight:bold'>
	<td align='center'>Peça</td>
	<td align='center'>Descrição</td>
	<td align='center'>Qtde</td>
	<td align='center'>NF Origem</td>
	<td align='center'>Excluir</td>
</tr>


<?
echo "<form method='post' action='$PHP_SELF' name='frm_nf_devolucao_item'>";
echo "<input type='hidden' name='faturamento' value='$faturamento'>";

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	$faturamento_item = trim(pg_result($res,$i,faturamento_item)) ;
	$referencia       = trim(pg_result($res,$i,referencia)) ;
	$descricao        = trim(pg_result($res,$i,descricao));
	$qtde             = trim(pg_result($res,$i,qtde));
	$nf_origem        = trim(pg_result($res,$i,nf_origem));

	$cor = "#ffffff";
	if ($i % 2 == 0) $cor = "#FFEECC";

	echo "<input type='hidden' name='faturamento_item_$i' value='$faturamento_item'>";

	echo "<tr style='font-size: 12px' bgcolor='$cor'>\n";
	echo "<td align='left'   nowrap>$referencia</td>\n";
	echo "<td align='left'   nowrap>$descricao</td>\n";
	echo "<td align='right'  nowrap>$qtde</td>\n";
	echo "<td align='left'   nowrap>$nf_origem</td>\n";
	echo "<td align='center' nowrap><input type='checkbox' name='excluir' value='$faturamento_item'></td>\n";
	echo "</tr>\n";
}

$fim = $i + 20;
for ($i = $i ; $i < $fim ; $i++) {
	$referencia = $_POST['referencia_' . $i];
	$qtde       = $_POST['qtde_'       . $i];
	$nf_origem  = $_POST['nf_origem_'  . $i];

	$cor = "#ffffff";
	if ($i % 2 == 0) $cor = "#FFEECC";

	echo "<tr style='font-size: 12px' bgcolor='$cor'>\n";
	echo "<td align='left'><input type='text' name='referencia_$i' size='10' value='$referencia'></td>\n";
	echo "<td align='left'><input type='text' name='descricao_$i'  size='25' value='$descricao' ></td>\n";
	echo "<td align='left'><input type='text' name='qtde_$i'       size='5'  value='$qtde'      ></td>\n";
	echo "<td align='left'><input type='text' name='nf_origem_$i'  size='6'  value='$nf_origem' ></td>\n";

	echo "</tr>";
}



echo "<tr>";
echo "<td colspan='5' align='center'>";
echo "<input type='hidden' name='qtde_item' value='$i'>";
echo "<input type='submit' name='btn_acao' value='Gravar'>";
echo "</form>";
echo "</td>";
echo "</tr>";
echo "</table>\n";

?>

<p>

<? #include "rodape.php"; ?>

</body>
