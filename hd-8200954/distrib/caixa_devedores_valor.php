<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

?>

<html>
<head>
<title>Devedores</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>

<body>

<center><h1>Devedores há mais de 3 dias</h1></center>

<p>

<script type="text/javascript">
function informacoes(posto) {
    var url = "";
        url = "posicao_financeira_telecontrol.php?posto=" + posto;
        janela = window.open(url,"janela","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=650,height=600,top=18,left=0");
        janela.focus();
}
</script>



<?

$vencto = substr ($vencto,6,4) . "-" . substr ($vencto,3,2) . "-" . substr ($vencto,0,2);

flush();

$sql = "SELECT  tbl_posto.nome  , 
				tbl_posto.posto , 
				tbl_posto.fone  , 
				tbl_posto.email ,
				rec.qtde        ,
				rec.valor
		FROM    tbl_posto
		JOIN   (SELECT posto, COUNT(*) AS qtde, SUM (valor) AS valor
				FROM tbl_contas_receber
				WHERE distribuidor = $login_posto
				AND   recebimento IS NULL
				AND   vencimento < CURRENT_DATE - INTERVAL '3 days'
				GROUP BY posto
		) rec ON tbl_posto.posto = rec.posto
		ORDER BY valor DESC";

$res = pg_exec ($con,$sql);


echo "<br><table align='center' border='1' cellspacing='0' bordercolor='#000000' cellpaddin='1'>";
echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
echo "<td>Posto</td>";
echo "<td>Fone</td>";
echo "<td>EMail</td>";
echo "<td>Qtde</td>";
echo "<td>Valor</td>";
echo "</tr>";

$total = 0 ;

for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {

	$cor = "#cccccc";
	if ($i % 2 == 0) $cor = '#eeeeee';
	
	echo "<tr bgcolor='$cor'>";
	$login_pa = pg_result ($res,$i,posto);
	echo "<td><a href= \"javascript: informacoes($login_pa)\">";
	echo pg_result ($res,$i,nome);
	echo "</a></td>";

	echo "<td>";
	echo pg_result ($res,$i,fone);
	echo "</td>";

	echo "<td>";
	echo pg_result ($res,$i,email);
	echo "</td>";

	echo "<td>";
	echo pg_result ($res,$i,qtde);
	echo "</td>";

	echo "<td align='right' width='100'>";
	$valor = pg_result ($res,$i,valor);
	echo number_format ($valor,2,",",".");
	echo "</td>";

	$total_docto = $valor ;

	echo "</tr></a>";

	$total += $total_docto;

}

$total = number_format ($total,2,",",".");

echo "<tr bgcolor='#0099CC' style='color:#ffffff ; font-weight:bold ; font-size:16px' align='center'>";
echo "<td colspan='4'>TOTAL EM ABERTO SEM JUROS</td>";
echo "<td align='right'>$total</td>";
echo "</tr>";

echo "</table>";

echo "<P>";
?>


<? #include "rodape.php"; ?>

</body>
</html>
