<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include "login_unico_autentica_usuario.php";

if(isset($_GET["id"])){
	$fabrica = $_GET["id"];
	$sql = "SELECT  tbl_posto_fabrica.oid as posto_fabrica,
			tbl_posto_fabrica.posto,
			tbl_posto_fabrica.fabrica 
		FROM tbl_posto_fabrica 
		WHERE fabrica = $fabrica 
		AND posto     = $cook_posto";
	$res = pg_exec($con,$sql);
	if(pg_numrows($res)>0){
		setcookie ("cook_posto_fabrica");
		setcookie ("cook_posto");
		setcookie ("cook_fabrica");
		setcookie ("cook_login_posto");
		setcookie ("cook_login_nome");
		setcookie ("cook_login_cnpj");
		setcookie ("cook_login_fabrica");
		setcookie ("cook_login_fabrica_nome");
		setcookie ("cook_login_pede_peca_garantia");
		setcookie ("cook_login_tipo_posto");
		setcookie ("cook_login_e_distribuidor");
		setcookie ("cook_login_distribuidor");
		setcookie ("cook_pedido_via_distribuidor");

		setcookie ("cook_posto_fabrica",pg_result ($res,0,posto_fabrica));
		setcookie ("cook_posto",pg_result ($res,0,posto));
		setcookie ("cook_fabrica",pg_result ($res,0,fabrica));

		if(strlen($os)>0) header("Location: os_item.php?os=$os");
		else              header("Location: login.php");
		exit;
	}
}

$aba=3;
include "estoque_cabecalho.php";
?>


<div id='dest'>Conferência de NF de Entrada</div>


<table width='500' align='center'>
<tr bgcolor='#FF9933' style='color:#ffffff ; font-weight:bold'>
	<td align='center'>&nbsp;</td>
	<td align='center'>OK</td>
	<td align='center'>Fábrica</td>
	<td align='center'>Nota Fiscal</td>
	<td align='center'>Emissão</td>
	<td align='center'>CFOP</td>
	<td align='center'>Transp.</td>
	<td align='center'>Total</td>
</tr>

<form name='nf_entrada' method='post' action='nf_entrada_item.php'>

<?
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
				to_char (tbl_faturamento.total_nota,'999999.99') as total_nota
		FROM    tbl_faturamento
		JOIN    tbl_fabrica USING (fabrica)
		LEFT JOIN tbl_transportadora USING (transportadora)
		WHERE   tbl_faturamento.posto = $login_posto
		AND     tbl_faturamento.distribuidor IS NULL
		AND     tbl_faturamento.fabrica <> 0
		AND     tbl_faturamento.emissao > CURRENT_DATE - INTERVAL '120 days'
		AND     tbl_fabrica.fabrica=45
		ORDER BY tbl_faturamento.emissao DESC, tbl_faturamento.nota_fiscal DESC ";

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

<p>

</body>
<?
include'login_unico_rodape.php';
?>