<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

if (strlen($_GET['faturamento_fatura']) > 0) $faturamento_fatura = $_GET['faturamento_fatura'];
if (strlen($_GET['posto']) > 0)              $posto              = $_GET['posto'];

?>

<html>
<head>
<title>Conferência de Duplicatas dos Postos</title>
<link type="text/css" rel="stylesheet" href="css/css.css">
</head>

<body>

<center><h1>Conferência de Duplicatas</h1></center>

<p>
<?
if (strlen ($faturamento_fatura) > 0 and strlen($posto) > 0) {
	$sql = "SELECT  tbl_faturamento.nota_fiscal                              ,
					to_char (tbl_faturamento.emissao,'DD/MM/YYYY') AS emissao,
					tbl_faturamento.total_nota                               ,
					tbl_faturamento.transp
			FROM    tbl_faturamento
			WHERE   tbl_faturamento.faturamento_fatura = $faturamento_fatura
			AND     tbl_faturamento.posto              = $posto;";
	//echo $sql;
	//exit ;
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		echo "<table border='1' cellspacing='0' align='center'>";
		
		echo "<tr bgcolor='#99cccc' align='center'  style='font-weight:bold;font-family:verdana;font-size:12px'>";
		echo "<td nowrap>Nota Fiscal</td>";
		echo "<td nowrap>Emissão</td>";
		echo "<td nowrap>Total Nota</td>";
		echo "<td nowrap>Transportadora</td>";
		echo "</tr>";
		
		for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
			$nota_fiscal = pg_result ($res,$i,nota_fiscal);
			$emissao     = pg_result ($res,$i,emissao);
			$total_nf    = pg_result ($res,$i,total_nota);
			$transp      = pg_result ($res,$i,transp);
			
			echo "<tr style='font-family:verdana;font-size:12px' bgcolor='$cor'> ";
			
			echo "<td align='center'>$nota_fiscal</td>";
			echo "<td align='center'>$emissao</td>";
			echo "<td align='right'>". number_format ($total_nf,2,",",".") ."</td>";
			echo "<td align='center'>&nbsp;$transp</td>";
			
			echo "</tr>";
		}
		
		echo "</table>";
	}
}
?>


<? #include "rodape.php"; ?>

</body>
</html>
