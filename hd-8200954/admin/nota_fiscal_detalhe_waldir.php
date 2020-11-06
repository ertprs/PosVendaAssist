<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';

if($login_fabrica <> 50 and $login_fabrica <> 80) { // Hd 42839
	if (strlen($_GET['faturamento']) > 0) {
		$faturamento = $_GET['faturamento'];
	}else{
		header ("Location: login.php");
		exit;
	}
}
	$pedido  = trim($_GET['pedido']);
	$peca    = trim($_GET['peca']);
#------------ Le OS da Base de dados ------------#
if ((strlen ($faturamento) > 0) or (strlen($pedido) > 0 and strlen($peca) > 0)) {
	$sql = "SELECT	tbl_faturamento.faturamento,
					trim(tbl_faturamento.nota_fiscal)                      AS nota_fiscal     ,
					to_char(tbl_faturamento.emissao,'DD/MM/YYYY')          AS emissao         ,
					to_char(tbl_faturamento.saida,'DD/MM/YYYY')            AS saida           ,
					to_char(tbl_faturamento.previsao_chegada,'DD/MM/YYYY') AS previsao_chegada,
					CASE WHEN tbl_faturamento.pedido IS NOT NULL THEN tbl_faturamento.pedido ELSE tbl_faturamento_item.pedido END AS pedido                                                    ,
					tbl_faturamento.total_nota                                                ,
					tbl_faturamento.cfop                                                      ,
					tbl_transportadora.nome,
					tbl_faturamento.transp
			FROM	tbl_faturamento
			LEFT JOIN	tbl_transportadora USING(transportadora)
			JOIN    tbl_faturamento_item ON tbl_faturamento_item.faturamento= tbl_faturamento.faturamento
			WHERE	tbl_faturamento.fabrica     = '$login_fabrica'";
			if(strlen($faturamento) > 0) {
				$sql.=" AND tbl_faturamento.faturamento = $faturamento ";
			}
			if(strlen($pedido) > 0 and strlen($peca) > 0) {
				$sql.=" AND tbl_faturamento_item.pedido = $pedido
						AND tbl_faturamento_item.peca   = $peca ";
			}
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$faturamento		= pg_result ($res,0,faturamento);
		$nota_fiscal		= pg_result ($res,0,nota_fiscal);
		$emissao			= pg_result ($res,0,emissao);
		$saida				= pg_result ($res,0,saida);
		$transportadora		= pg_result ($res,0,nome);
		$pedido				= pg_result ($res,0,pedido);
		$previsao_chegada	= pg_result ($res,0,previsao_chegada);
		$total_nota			= pg_result ($res,0,total_nota);
		$cfop				= pg_result ($res,0,cfop);
		$transportadora		= pg_result ($res,0,nome);
		$transp		= pg_result ($res,0,transp);
		if(strlen($transportadora) == 0) $transportadora = $transp;
	}

}

$title = "Detalhes da Nota Fiscal";

$layout_menu = 'pedido';
include "cabecalho.php";

?>

<style type="text/css">

body {
	margin: 0px,0px,0px,0px;
}

.titulo {
	font-family: normal Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 7px;
	text-align: left;
	color: #000000;
	background: #ffffff;
	border-bottom: dotted 1px #000000;
	/*border-right: dotted 1px #a0a0a0;*/
 	border-left: dotted 1px #000000;
	padding: 1px,1px,1px,1px;
}

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 9px;
	font-weight: bold;
	border: 1px solid #a0a0a0;
	color:#000000;
	background:#d0d0d0
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 1px solid #a0a0a0;
}

</style>

<br>
<TABLE class="borda" width="650px" border="0" cellspacing="0" cellpadding="3">
<TR>
	<TD class="menu_top">Nota Fiscal</TD>
	<TD class="table_line"><? echo $nota_fiscal ?>&nbsp</TD>
	<TD class="menu_top">Pedido</TD>
	<TD class="table_line"><? echo $pedido ?>&nbsp</TD>
	<TD class="menu_top">Data de Emissão</TD>
	<TD class="table_line"><? echo $emissao ?>&nbsp</TD>
	<TD class="menu_top">Data de Saída</TD>
	<TD class="table_line"><? echo $saida ?>&nbsp</TD>
<? if($login_fabrica == 50) { // HD 42839?>
	<TD class="menu_top">Transportadora</TD>
	<TD class="table_line" colspan='3'><? echo $transportadora ?>&nbsp</TD>
<? } ?>
</TR>
<? if($login_fabrica <> 50) { ?>
<TR>
	<TD class="menu_top">CFOP</TD>
	<TD class="table_line"><? echo $cfop; ?>&nbsp</TD>
	<TD class="menu_top">Transportadora</TD>
	<TD class="table_line" colspan='3'><? echo $transportadora ?>&nbsp</TD>
	<TD class="menu_top">Previsão de chegada</TD>
	<TD class="table_line"><? echo $previsao_chegada ?>&nbsp</TD>
</TR>
<? } ?>
</TABLE>
<br>
<br>

<?
if (strlen ($faturamento) > 0) {
	$sql = "SELECT	tbl_faturamento_item.*,
					tbl_peca.referencia   ,
					tbl_peca.descricao
			FROM	tbl_faturamento_item
			JOIN	tbl_peca USING(peca)
			WHERE	tbl_faturamento_item.faturamento = $faturamento ";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		echo "<TABLE width='650' border='0' cellspacing='0' cellpadding='3'>";
		echo "<TR class='menu_top' bgcolor='#d0d0d0'>";
			echo "<TD class='menu_top' bgcolor='#d0d0d0'>Referência</TD>";
			echo "<TD class='menu_top' bgcolor='#d0d0d0'>Descrição</TD>";
			echo "<TD class='menu_top' bgcolor='#d0d0d0'>Quant.</TD>";
			echo "<TD class='menu_top' bgcolor='#d0d0d0'>Unit.</TD>";
			echo "<TD class='menu_top' bgcolor='#d0d0d0'>Valor</TD>";
		echo "</TR>";

		$peca_selecionada = $_GET['peca'];
		for($i=0; $i < pg_numrows($res); $i++){
			$peca				= pg_result ($res,$i,peca);
			$referencia			= pg_result ($res,$i,referencia);
			$descricao			= pg_result ($res,$i,descricao);
			$qtde				= pg_result ($res,$i,qtde);
			$preco				= pg_result ($res,$i,preco);
			$total_preco = $qtde * $preco;

			$total_nota_soma = $total_nota_soma + $total_preco;

			if ($peca_selecionada == $peca)
				$bgColor = "#FFEAD5";
			else
				$bgColor = "#FFFFFF";

			echo "		<TR bgcolor=$bgColor>\n";
			echo "			<TD class='table_line' style='text-align: center;'>$referencia</TD>\n";
			echo "			<TD class='table_line' style='text-align: left;'>$descricao</TD>\n";
			echo "			<TD class='table_line' style='text-align: center;'>$qtde</TD>\n";
			echo "			<TD class='table_line' style='text-align: right;'>".number_format($preco,2,',','.')."</TD>\n";
			echo "			<TD class='table_line' style='text-align: right;'>".number_format($total_preco,2,',','.')."</TD>\n";
			echo "		</TR>\n";
		}
		echo "		<TR>\n";
		echo "			<TD class='table_line' style='text-align: center;' colspan=4><b>Valor total da Nota</b></TD>\n";
		echo "			<TD class='table_line' style='text-align: right;'><b>".number_format($total_nota_soma,2,',','.')."</b></TD>\n";
		//echo "			<TD class='table_line' style='text-align: right;'><b>".number_format($total_nota,2,',','.')."</b></TD>\n";
		echo "		</TR>\n";
	}

}
?>
</TABLE>

<BR><BR>

<? include "rodape.php"; ?>
