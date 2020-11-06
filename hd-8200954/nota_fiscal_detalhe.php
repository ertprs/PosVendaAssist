<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if (strlen($_GET['nota_fiscal']) > 0) {
	$nota_fiscal = $_GET['nota_fiscal'];
}else{
	header ("Location: login.php");
	exit;
}
$login_fabrica_aux = $login_fabrica;

if ($telecontrol_distrib){
	$login_fabrica_aux = "10,$login_fabrica";
}


#------------ Le OS da Base de dados ------------#
if (strlen ($nota_fiscal) > 0) {
	$sql = "SELECT	tbl_faturamento.faturamento,
					to_char(tbl_faturamento.emissao,'DD/MM/YYYY')          AS emissao         ,
					to_char(tbl_faturamento.saida,'DD/MM/YYYY')            AS saida           ,
					to_char(tbl_faturamento.previsao_chegada,'DD/MM/YYYY') AS previsao_chegada,
					tbl_faturamento.pedido                                                    ,
					tbl_pedido.pedido_blackedecker                                            ,
					tbl_faturamento.total_nota                                                ,
					tbl_faturamento.cfop                                                      ,
					tbl_faturamento.transp                                                    ,
					tbl_transportadora.nome
			FROM	tbl_faturamento
			LEFT JOIN	tbl_transportadora USING(transportadora)
			LEFT JOIN   tbl_pedido ON tbl_faturamento.pedido = tbl_pedido.pedido
			WHERE	tbl_faturamento.nota_fiscal = '$nota_fiscal'
			AND		(tbl_faturamento.posto       = $login_posto OR tbl_faturamento.distribuidor = $login_posto)
			AND		tbl_faturamento.fabrica     in ( $login_fabrica_aux )";
	$res = pg_exec ($con,$sql);

	if (pg_numrows ($res) > 0) {
		$faturamento		= pg_result ($res,0,faturamento);
		$emissao			= pg_result ($res,0,emissao);
		$saida				= pg_result ($res,0,saida);
		$transportadora		= pg_result ($res,0,nome);
		$transp				= pg_result ($res,0,transp);
		$pedido				= pg_result ($res,0,pedido);
		$previsao_chegada	= pg_result ($res,0,previsao_chegada);
		$total_nota			= pg_result ($res,0,total_nota);
		$cfop				= pg_result ($res,0,cfop);

		if ($login_fabrica == 1) $pedido = pg_result ($res,0,pedido_blackedecker);
	}
}

if (empty($pedido)) {
	$pedido = $_GET['pedido'];
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

<TABLE class="borda" width="650px" border="0" cellspacing="0" cellpadding="3" align='center'>
<TR>
	<TD class="menu_top">Nota Fiscal</TD>
	<TD class="table_line"><? echo $nota_fiscal ?>&nbsp</TD>
	<TD class="menu_top">Pedido</TD>
	<TD class="table_line"><? echo $pedido ?>&nbsp</TD>
	<TD class="menu_top">Data de Emissão</TD>
	<TD class="table_line"><? echo $emissao ?>&nbsp</TD>
	<TD class="menu_top">Data de Saída</TD>
	<TD class="table_line"><? echo $saida ?>&nbsp</TD>
</TR>
<TR>
	<TD class="menu_top">CFOP</TD>
	<TD class="table_line"><? echo $cfop; ?>&nbsp</TD>
	<TD class="menu_top">Transportadora</TD>

	<TD class="table_line" colspan='3'><? if(strlen($transportadora)>0){ echo $transportadora; }else{echo $transp;} ?>&nbsp</TD>
	<TD class="menu_top">Previsão de chegada</TD>
	<TD class="table_line"><? echo $previsao_chegada ?>&nbsp</TD>
</TR>
</TABLE>

<br>
<br>

<TABLE width="650" border="0" cellspacing="0" cellpadding="3" align='center'>
<TR class="menu_top" bgcolor="#d0d0d0">
	<TD class="menu_top" bgcolor="#d0d0d0">Referência</TD>
	<TD class="menu_top" bgcolor="#d0d0d0">Descrição</TD>
	<TD class="menu_top" bgcolor="#d0d0d0">Qtde</TD>
	<TD class="menu_top" bgcolor="#d0d0d0">Pendente</TD>
	<TD class="menu_top" bgcolor="#d0d0d0">Unitário</TD>
	<TD class="menu_top" bgcolor="#d0d0d0">Valor</TD>
</TR>

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
		$peca_selecionada = $_GET['peca'];
		for($i=0; $i < pg_numrows($res); $i++){
			$peca			= pg_result ($res,$i,peca);
			$referencia		= pg_result ($res,$i,referencia);
			$descricao		= pg_result ($res,$i,descricao);
			$qtde			= pg_result ($res,$i,qtde);
			$pendente		= pg_result ($res,$i,pendente);
			$preco			= pg_result ($res,$i,preco);
			$total_preco	= $qtde * $preco;

			$total_nota_soma = $total_nota_soma + $total_preco;

			if ($peca_selecionada == $peca)
				$bgColor = "#FFEAD5";
			else
				$bgColor = "#FFFFFF";

			echo "		<TR bgcolor=$bgColor>\n";
			echo "			<TD class='table_line' style='text-align: center;'>$referencia</TD>\n";
			echo "			<TD class='table_line' style='text-align: left;'>$descricao</TD>\n";
			echo "			<TD class='table_line' style='text-align: center;'>$qtde</TD>\n";
			echo "			<TD class='table_line' style='text-align: center;'>$pendente</TD>\n";
			echo "			<TD class='table_line' style='text-align: right;'>".number_format($preco,2,',','.')."</TD>\n";
			echo "			<TD class='table_line' style='text-align: right;'>".number_format($total_preco,2,',','.')."</TD>\n";
			echo "		</TR>\n";
		}
		echo "		<TR>\n";
		echo "			<TD class='table_line' style='text-align: center;' colspan=5><b>Valor total da Nota</b></TD>\n";
		echo "			<TD class='table_line' style='text-align: right;'><b>".number_format($total_nota_soma,2,',','.')."</b></TD>\n";
		//echo "			<TD class='table_line' style='text-align: right;'><b>".number_format($total_nota,2,',','.')."</b></TD>\n";
		echo "		</TR>\n";
	}

}
?>

<tr>
	<td height="27" valign="middle" align="center" colspan="6">
		<br>
		<a href="javascript:history.back()"><img src='imagens/btn_voltar.gif'></a>
	</td>
</tr>

</TABLE>

<BR><BR>

<? include "rodape.php"; ?>
