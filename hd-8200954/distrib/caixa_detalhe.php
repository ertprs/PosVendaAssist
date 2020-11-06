<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

?>

<html>
<head>
<title>Detalhe do Caixa</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>

<body>

<?
$vencto = $_GET['vencto'];
?>

<center><h1>Detalhe de <?= $vencto ?></h1></center>

<p>


<?

$vencto = substr ($vencto,6,4) . "-" . substr ($vencto,3,2) . "-" . substr ($vencto,0,2);

flush();

$sql = "SELECT  tbl_posto.nome, 
				tbl_posto.fone, 
				tbl_posto.email, 
				tbl_contas_receber.documento, 
				tbl_contas_receber.valor AS valor,
				(current_date - vencimento::date)::int4 AS dias_atraso,
				valor_dias_atraso
		FROM tbl_posto
		JOIN tbl_contas_receber USING (posto)
		WHERE tbl_contas_receber.vencimento = '$vencto' AND tbl_contas_receber.recebimento IS NULL AND tbl_contas_receber.distribuidor=$login_posto";

//Apenas Tulio e Valeria veem valores altos
if ($login_unico != 13 and $login_unico != 1) {
	$sql = "SELECT  tbl_posto.nome, 
				tbl_posto.fone, 
				tbl_posto.email, 
				tbl_contas_receber.documento, 
				tbl_contas_receber.valor AS valor,
				(current_date - vencimento::date)::int4 AS dias_atraso,
				valor_dias_atraso
		FROM tbl_posto
		JOIN tbl_contas_receber USING (posto)
		WHERE tbl_contas_receber.vencimento = '$vencto' AND tbl_contas_receber.recebimento IS NULL AND tbl_contas_receber.distribuidor=$login_posto 
		AND   tbl_contas_receber.valor < 1000";
}

$res = pg_exec ($con,$sql);


echo "<br><table align='center' border='0' cellspacing='1' cellpaddin='1'>";
echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
echo "<td>Posto</td>";
echo "<td>Fone</td>";
echo "<td>EMail</td>";
echo "<td>Documento</td>";
echo "<td>Valor</td>";
echo "<td>Juros</td>";
echo "<td>Total</td>";
echo "</tr>";

$total = 0 ;

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

	$dias_atraso         = pg_result($res,$i,dias_atraso);
	$valor_dias_atraso   = pg_result($res,$i,valor_dias_atraso);
	$juros_dias_atraso   = $dias_atraso * $valor_dias_atraso;
	$juros               = pg_result ($res,$i,valor) * 2 / 100;
	$tarifa_cancelamento = 6;
	$total_juros         = $juros_dias_atraso + $juros + $tarifa_cancelamento;
					

	$cor = "#cccccc";
	if ($i % 2 == 0) $cor = '#eeeeee';
	
	echo "<tr bgcolor='$cor'>";

	echo "<td>";
	echo pg_result ($res,$i,nome);
	echo "</td>";

	echo "<td>";
	echo pg_result ($res,$i,fone);
	echo "</td>";

	echo "<td>";
	echo pg_result ($res,$i,email);
	echo "</td>";

	echo "<td>";
	echo pg_result ($res,$i,documento);
	echo "</td>";

	echo "<td align='right' width='100'>";
	$valor = pg_result ($res,$i,valor);
	echo number_format ($valor,2,",",".");
	echo "</td>";

	echo "<td align='right' width='100'>";
	echo number_format ($total_juros,2,",",".");
	echo "</td>";

	echo "<td align='right' width='100'>";
	$total_docto = $total_juros + $valor;
	echo number_format ($total_docto,2,",",".");
	echo "</td>";

	echo "</tr></a>";

	$total += $total_docto;

}

$total = number_format ($total,2,",",".");

echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
echo "<td colspan='6'>TOTAL EM ABERTO</td>";
echo "<td align='right'>$total</td>";
echo "</tr>";

echo "</table>";

echo "<P>";
?>


<? #include "rodape.php"; ?>

</body>
</html>
