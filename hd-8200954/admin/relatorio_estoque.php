<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_admin.php';
include 'funcoes.php';

if (strlen($HTTP_GET_VARS["peca"]) > 0) {
	$codigo_peca = trim($HTTP_GET_VARS["peca"]);
}

$sql = "SELECT  tbl_peca.referencia     ,
				tbl_peca.descricao      ,
				tbl_estoque.almoxarifado,
				tbl_estoque.qtde
		FROM    tbl_peca
		JOIN    tbl_estoque ON tbl_estoque.peca    = tbl_peca.peca
		JOIN    tbl_fabrica ON tbl_fabrica.fabrica = tbl_peca.fabrica
		WHERE   tbl_peca.fabrica = $login_fabrica
		AND     tbl_estoque.peca = $codigo_peca;";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	$referencia_peca = trim(pg_result($res,0,referencia));
	$descricao_peca  = trim(pg_result($res,0,descricao));
	$relatorio = "ok";
}


$title = "Telecontrol - Relatório de Estoque";
include "cabecalho.php";
?>

<p>


<? if ($relatorio == "ok") { ?>

<table width="500" border="0" cellpadding="0" cellspacing="0" align="center" bgcolor="#CCCCCC">
<tr>
	<td height="27" valign="middle" align="center" colspan="3" background="assets/netscapebrowser.gif" bgcolor="#FFFFFF">
		<b><font face="Arial, Helvetica, sans-serif" color="#FFFFCC">
		Relatório de Estoque
		</font></b>
	</td>
</tr>
</table>

<!-- DESCRIÇÃO DA PEÇA -->

<?
if (pg_numrows($res) > 0) {
	echo "<table width='500' border='0' cellspacing='0' cellpadding='0' align='center'>";
	echo "<tr>";
	
	echo "<td background='assets/netscapebrowser.gif' align='center'>";
	echo "<font size='2' face='Geneva, Arial, Helvetica, san-serif' color='#FFFFFF'><b>$referencia_peca - $descricao_peca</b></font>";
	echo "</td>";
	
	echo "</tr>";
	echo "</table>";
	
	echo "<table width='500' border='0' cellspacing='0' cellpadding='0' align='center' bgcolor='#CCCCCC'>";
	
	for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
		$estoque = trim(pg_result($res,$i,almoxarifado));
		$qtde    = trim(pg_result($res,$i,qtde));
		$soma_estoque = $soma_estoque + $qtde;
		
		echo "<tr><td colspan='4'><hr></td></tr>";
		
		echo "<tr>";
		
		echo "<td><img src='assets/spacer.gif' height='1' width='15'><br>";
		echo "<td align='center'><img src='assets/spacer.gif' height='1' width='180'><br><font face='Geneva, Arial, Helvetica, san-serif' size='2'>$estoque</font></td>";
		echo "<td align='center'><img src='assets/spacer.gif' height='1' width='180'><br><font face='Geneva, Arial, Helvetica, san-serif' size='2'>$qtde</font></td>";
		echo "<td><img src='assets/spacer.gif' height='1' width='15'><br>";
		
		echo "</tr>";
		
		echo "<tr><td colspan='4'><hr></td></tr>";
	}
	echo "</table>";
}
?>

<!--  TOTAIS DAS NOTAS FISCAIS. -->

<table width="500" border="0" cellspacing="0" cellpadding="0" align="center" bgcolor="#CCCCCC">
<tr>
	<td background="../assets/netscapebrowser.gif"><img src='assets/spacer.gif' height='1' width='15'><br>
	<td background="../assets/netscapebrowser.gif" align="center"><img src='assets/spacer.gif' height='1' width='180'><br><font size="2" face="Geneva, Arial, Helvetica, san-serif"><b><font color="#FFFFFF">TOTAL</font></b></font></td>
	<td background="../assets/netscapebrowser.gif" align="center"><img src='assets/spacer.gif' height='1' width='180'><br><font size="2" face="Geneva, Arial, Helvetica, san-serif"><b><font color="#FFFFFF"><? echo $soma_estoque ?></font></b></font></td>
	<td background="../assets/netscapebrowser.gif"><img src='assets/spacer.gif' height='1' width='15'><br>
</tr>
</table>


<? } ?>

<p>

<? include "rodape.php" ?>