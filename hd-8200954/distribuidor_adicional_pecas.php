<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$msg_erro = "";

$layout_menu = "os";
$title = "Distribuidor Peças";

include "cabecalho.php";

/* -------------========CONSULTA SQL=========------------*/
$sql = "";
$res = pg_exec ($con,$sql);

if (pg_numrows($res) > 0) {
	echo "<br><table align='center' border='0' cellspacing='1' cellpaddin='1'>";

	echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
	echo "<td colspan='6' align='center'>Pedidos realizados para a Fábrica</td>";
	echo "</tr>";

	echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
	echo "<td>Nota Fiscal</td>";
	echo "<td>Emissão</td>";
	echo "<td>Posto</td>";
	echo "<td>CFOP</td>";
	echo "<td>OS</td>";
	echo "<td>PEÇA</td>";
	echo "<td>OP</td>";
	echo "</tr>";
	
	$ultima_nf=0;
	
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

		$cor = "#cccccc";
		$nf=pg_result ($res,$i,nf);
		if ($ultima_nf==$nf) $cor = '#eeeeee';

		echo "<tr bgcolor='$cor'>";

		echo "<td title='Nota Fiscal'>";
		echo $nf;
		echo "</td>";

		echo "<td title='Emissão'>";
		echo pg_result ($res,$i,emissao);
		echo "</td>";

		echo "<td title='Posto'>";
		echo pg_result ($res,$i,posto);
		echo "</td>";

		echo "<td align='right' title='CFOP'>";
		echo pg_result ($res,$i,cfop);
		echo "</td>";

		echo "<td align='right' title='OS'>";
		echo pg_result ($res,$i,os);
		echo "</td>";

		echo "<td align='right' title='Peça'>";
		echo pg_result ($res,$i,qtde_faturada);
		echo "</td>";

		echo "<td align='right' title='Valor'>";
		echo pg_result ($res,$i,valor);
		echo "</td>";

		$ultima_nf=$nf;

		echo "</tr>";
	}
	echo "</table>";
}else{
}
?>