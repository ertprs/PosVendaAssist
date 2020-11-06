<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include '../funcoes.php';

if(strlen($_POST['fabrica']) > 0) $fabrica = $_POST['fabrica'];
else                              $fabrica = $_GET['fabrica'];

if(strlen($_POST['x_data_inicial']) > 0) $x_data_inicial = $_POST['x_data_inicial'];
else                                      $x_data_inicial = $_GET['x_data_inicial'];

if(strlen($_POST['x_data_final']) > 0) $x_data_final = $_POST['x_data_final'];
else                                   $x_data_final = $_GET['x_data_final'];

?>

<html>
<head>
<title>Reembolso Frete</title>

<style type="text/css">
	.Titulo {
		text-align: center;
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 12px;
		font-weight: bold;
		color: #FFFFFF;
		background-color: #596D9B;
	}

	.Conteudo {
		text-align: left;
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: normal;
		color: #000000;
	}

	.Conteudo2 {
		text-align: center;
		font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: normal;
		color: #000000;
	}

	.table_line {
		text-align: left;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 10px;
		font-weight: normal;
		border: 0px solid;
		background-color: #D9E2EF
	}

	.Erro {
		text-align: center;
		font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
		font-size: 12px;
		font-weight: bold;
		background-color: #FF3300;
		color: #FFFFFF;
	}

</style>
</head>

<body>
<table width='800' border='0' cellspacing='1' cellpadding='2' align='center'>
<TR>
	<TD style='width: 100%; font-size: 35px; height:15px; background-color:#0099CC; color:#ffffff; text-align:center; font-weight:bold;'>Reembolso Frete</TD>
</TR>
</TABLE>
<BR>
<?
if (strlen($x_data_inicial) > 0 AND  strlen($x_data_final) > 0 AND strlen($fabrica) > 0) {
	$sql = "select tbl_embarque.embarque                              ,
			tbl_faturamento.faturamento                               ,
			tbl_posto.nome                                            ,
			tbl_posto.cidade                                          ,
			tbl_posto.estado                                          ,
			tbl_faturamento.total_nota                                ,
			(SELECT SUM(tbl_faturamento.total_nota)
				FROM tbl_faturamento 
			 WHERE tbl_faturamento.embarque = tbl_embarque.embarque
			) AS soma_notas                                           ,
			tbl_embarque.total_frete
		INTO TEMP temp_embarque
		from tbl_embarque
		join tbl_faturamento using(embarque)
		join tbl_posto on tbl_faturamento.posto = tbl_posto.posto
		where emissao between '$x_data_inicial' and '$x_data_final'
		AND tbl_faturamento.fabrica = $fabrica 
		GROUP BY tbl_embarque.embarque  ,
			tbl_faturamento.faturamento ,
			tbl_posto.nome              ,
			tbl_posto.cidade            ,
			tbl_posto.estado            ,
			tbl_faturamento.total_nota  ,
			tbl_embarque.total_frete
		ORDER BY tbl_posto.nome;

		SELECT * FROM temp_embarque;
		";
	#echo nl2br($sql);
	
	$res = pg_exec($con, $sql);

	if(pg_numrows($res)>0){
		echo "<br>";
		echo "<table width='800' border='0' cellspacing='1' cellpadding='2' align='center'>";
			echo "<TR bgcolor='#0099CC'style='color:#ffffff; text-align:center; font-weight:bold;'>";
				echo "<TD>Embarque</TD>";
				echo "<TD>Faturamento</TD>";
				echo "<TD>Posto</TD>";
				echo "<TD>Cidade</TD>";
				echo "<TD>UF</TD>";
				echo "<TD>Total Nota</TD>";
				echo "<TD>%</TD>";
				echo "<TD>Frete Pago</TD>";
			echo "</TR>";

		for($i=0; $i<pg_numrows($res); $i++){
			$embarque    = pg_result($res, $i, embarque);
			$faturamento = pg_result($res, $i, faturamento);
			$nome        = pg_result($res, $i, nome);
			$cidade      = pg_result($res, $i, cidade);
			$estado      = pg_result($res, $i, estado);
			$soma_notas = pg_result($res, $i, soma_notas);
			$total_nota  = pg_result($res, $i, total_nota);
			$total_frete = pg_result($res, $i, total_frete);

			$porcentagem = ((100*$total_nota)/$soma_notas);

			//rateio do frete
			$frete_rateio = (($total_frete*$porcentagem)/100);

			$soma_total_nota  = $soma_total_nota  + $total_nota;
			$soma_total_frete = $soma_total_frete + $total_frete;

			$total_nota  = number_format($total_nota, 2, ",", ".");
			$total_frete = number_format($total_frete, 2, ",", ".");

			if($porcentagem<100){
				$porcentagem  = number_format($porcentagem, 2, ",", ".");
			}

			$frete_rateio = number_format($frete_rateio, 2, ",", ".");

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';

			echo "<TR bgcolor='$cor'>";
				echo "<TD class='Conteudo'>$embarque</TD>";
				echo "<TD class='Conteudo'>$faturamento</TD>";
				echo "<TD class='Conteudo'>$nome</TD>";
				echo "<TD class='Conteudo'>$cidade</TD>";
				echo "<TD class='Conteudo2'>$estado</TD>";
				echo "<TD class='Conteudo2'>$total_nota</TD>";
				echo "<TD class='Conteudo2'>$porcentagem</TD>";
				echo "<TD class='Conteudo2'>$frete_rateio</TD>";
			echo "</TR>";
		}
		$soma_total_nota  = number_format($soma_total_nota, 2, ",", ".");
		$soma_total_frete = number_format($soma_total_frete, 2, ",", ".");

		echo "<TR class='Conteudo' bgcolor='$cor'>";
			echo "<TD colspan='5'>&nbsp;</TD>";
			echo "<TD bgcolor='#0099CC' style='color:#ffffff ; text-align:center; font-weight:bold;'>Total</TD>";
			echo "<TD bgcolor='#0099CC' style='color:#ffffff; text-align:center; font-weight:bold;'>$soma_total_nota</TD>";
			echo "<TD bgcolor='#0099CC'style='color:#ffffff; text-align:center; font-weight:bold;'> $soma_total_frete</TD>";
		echo "</TR>";
		echo "</TABLE>";
	}
}

?>

<script>
	window.print();
</script>
</body>
</html>