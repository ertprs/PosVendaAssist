<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';



#$title = "DETALHAMENTO DE NOTA FISCAL";
#$layout_menu = 'pedido';

#include "cabecalho.php";
?>

<html>
<head>
<title>Conferência de NF de Entrada</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>

<body>

<? include 'menu.php' ?>


<center><h1>Conferência de NF de Entrada</h1></center>

<center><a href="nf_divergente.php">Clique aqui para ver os itens de NF com divergência</a></center>

<p>

<center><b>Suas notas ainda não conferidas</b></center>


<table width='500' align='center'>
<tr bgcolor='#FF9933' style='color:#ffffff ; font-weight:bold'>
	<td align='center'>&nbsp;</td>
	<td align='center'>OK</td>
	<td align='center'>Fábrica</td>
	<td align='center'>Fornecedor</td>
	<td align='center'>Nota Fiscal</td>
	<td align='center'>Emissão</td>
	<td align='center'>CFOP</td>
	<td align='center'>Transp.</td>
	<td align='center'>Total</td>
</tr>

<form name='nf_entrada' method='post' action='nf_entrada_item.php'>

<?
$btn_procura = $_GET["btn_procura"];
if($btn_procura == "Procurar"){
	$nota_procura = $_GET["nf_procura"];
	$sql = "SELECT tbl_faturamento.faturamento ,
				tbl_fabrica.nome AS fabrica_nome ,
				tbl_faturamento.nota_fiscal ,
				to_char (tbl_faturamento.emissao,'DD/MM/YYYY') as emissao ,
				to_char (tbl_faturamento.conferencia,'DD/MM/YYYY') as conferencia ,
				to_char (tbl_faturamento.cancelada,'DD/MM/YYYY') as cancelada ,
				tbl_faturamento.cfop ,
				tbl_faturamento.transp ,
				tbl_transportadora.nome AS transp_nome ,
				tbl_transportadora.fantasia AS transp_fantasia ,
				to_char (tbl_faturamento.total_nota,'999999.99') as total_nota,
				tbl_posto.nome as fornecedor_distrib
		FROM    tbl_faturamento
		JOIN    tbl_fabrica USING (fabrica)
		LEFT JOIN tbl_posto on tbl_posto.posto = tbl_faturamento.distribuidor 
		LEFT JOIN tbl_posto_extra on tbl_posto.posto = tbl_posto_extra.posto
		LEFT JOIN tbl_transportadora USING (transportadora)
		WHERE   tbl_faturamento.posto = $login_posto
		AND     (tbl_faturamento.distribuidor IS NULL or (tbl_faturamento.distribuidor IS NOT NULL and tbl_posto_extra.fornecedor_distrib IS TRUE))
		AND     tbl_faturamento.fabrica <> 0
		AND     tbl_faturamento.nota_fiscal = '$nota_procura'
		ORDER BY tbl_faturamento.emissao DESC, tbl_faturamento.nota_fiscal DESC ";
}else{
	$sql = "SELECT	tbl_faturamento.faturamento ,
				tbl_fabrica.nome AS fabrica_nome ,
				tbl_faturamento.nota_fiscal ,
				to_char (tbl_faturamento.emissao,'DD/MM/YYYY') as emissao ,
				to_char (tbl_faturamento.conferencia,'DD/MM/YYYY') as conferencia ,
				to_char (tbl_faturamento.cancelada,'DD/MM/YYYY') as cancelada ,
				tbl_faturamento.cfop ,
				tbl_faturamento.transp ,
				tbl_transportadora.nome AS transp_nome ,
				tbl_transportadora.fantasia AS transp_fantasia ,
				to_char (tbl_faturamento.total_nota,'999999.99') as total_nota,
				tbl_posto.nome as fornecedor_distrib
		FROM    tbl_faturamento
		JOIN    tbl_fabrica USING (fabrica)
		LEFT JOIN tbl_posto on tbl_posto.posto = tbl_faturamento.distribuidor 
		LEFT JOIN tbl_posto_extra on tbl_posto.posto = tbl_posto_extra.posto
		LEFT JOIN tbl_transportadora USING (transportadora)
		WHERE   tbl_faturamento.posto = $login_posto
		AND     (tbl_faturamento.distribuidor IS NULL or (tbl_faturamento.distribuidor IS NOT NULL and tbl_posto_extra.fornecedor_distrib IS TRUE))
	 	AND     tbl_faturamento.fabrica <> 0
		AND     tbl_faturamento.emissao > CURRENT_DATE - INTERVAL '60 days'
		ORDER BY tbl_faturamento.emissao DESC, tbl_faturamento.nota_fiscal DESC ";
}

//echo nl2br($sql);
$res = pg_exec ($con,$sql);

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
	$conferencia      = trim(pg_result($res,$i,conferencia)) ;
	$faturamento      = trim(pg_result($res,$i,faturamento)) ;
	$fabrica_nome     = trim(pg_result($res,$i,fabrica_nome)) ;
	$nota_fiscal      = trim(pg_result($res,$i,nota_fiscal));
	$emissao          = trim(pg_result($res,$i,emissao));
	$cancelada        = trim(pg_result($res,$i,cancelada));
	$cfop             = trim(pg_result($res,$i,cfop));
	$transp           = trim(pg_result($res,$i,transp));
	$transp_nome      = trim(pg_result($res,$i,transp_nome));
	$transp_fantasia  = trim(pg_result($res,$i,transp_fantasia));
	$total_nota       = trim(pg_result($res,$i,total_nota));
	$fornecedor_distrib = trim(pg_result($res,$i,fornecedor_distrib));

	if (strlen ($transp_nome) > 0) $transp = $transp_nome;
	if (strlen ($transp_fantasia) > 0) $transp = $transp_fantasia;
	$transp = strtoupper ($transp);

	
	$cor = "#ffffff";
	if ($i % 2 == 0) $cor = "#FFEECC";

	
	if (strlen ($cancelada) > 0) $cor = '#FF6633';

	echo "<tr style='font-size: 12px' bgcolor='$cor'>\n";

	if (strlen ($conferencia) > 0) {
		$conferencia = "OK";
	}else{
		$conferencia = "--";
	}
	echo "<td align='left' nowrap>";
	echo "<input type='checkbox' name='agrupada_$i' value='$faturamento'>" ;
	echo "</td>\n";
	echo "<td align='left' nowrap>$conferencia</td>\n";
	echo "<td align='left' nowrap>$fabrica_nome</td>\n";
	echo "<td align='left' nowrap>$fornecedor_distrib</td>\n";
	echo "<td align='left' nowrap><a href='nf_entrada_item.php?faturamento=$faturamento'>$nota_fiscal</a></td>\n";
	echo "<td align='left' nowrap>$emissao</td>\n";
	echo "<td align='left' nowrap>$cfop</td>\n";
	echo "<td align='left' nowrap>$transp</td>\n";
	$total_nota = number_format ($total_nota,2,',','.');
	echo "<td align='right' nowrap>$total_nota</td>\n";
	echo "</tr>\n";
}

echo "</table>\n";
echo "<input type='hidden' name='qtde_nf' value='$i'>";
echo "<center><input type='submit' name='btn_conf' value='Conferir Agrupado'></center>";

echo "</form>";
?>
<form name='nf_entrada' method='GET' action='<? echo $PHP_SELF?>'>
<?
echo "</table>\n";
echo "<input type='text' name='nf_procura'>Digite o número da NF que deseja procurar<input type='submit' name='btn_procura' value='Procurar'>";
echo "</form>";
?>

<p>

</body>
<?
include'rodape.php';
?>