<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';
include '../funcoes.php';

$title = "RELATÓRIO REEMBOLSO DE FRETE";

include "cabecalho.php";

include 'menu.php';

if(strlen($_POST['btn_acao']) > 0) $btn_acao = $_POST['btn_acao'];
else                               $btn_acao = $_GET['btn_acao'];

if(strlen($_POST['fabrica']) > 0) $fabrica = $_POST['fabrica'];
else                              $fabrica = $_GET['fabrica'];

if(strlen($_POST['data_inicial_01']) > 0) $data_inicial_01 = $_POST['data_inicial_01'];
else                                      $data_inicial_01 = $_GET['data_inicial_01'];

if(strlen($_POST['data_final_01']) > 0) $data_final_01 = $_POST['data_final_01'];
else                                    $data_final_01 = $_GET['data_final_01'];

if (strlen($btn_acao) > 0) {
	$x_data_inicial = $data_inicial_01;
	$x_data_final   = $data_final_01;

	if ($x_data_inicial != "dd/mm/aaaa" && $x_data_final != "dd/mm/aaaa") {
		if (strlen($x_data_inicial) > 0) {
			$x_data_inicial = fnc_formata_data_pg($x_data_inicial);
			$x_data_inicial = str_replace("'","",$x_data_inicial);
		}else{
			$msg_erro .= " Preencha o campo Data Inicial para realizar a pesquisa. ";
		}

		if (strlen($x_data_final) > 0) {
			$x_data_final = fnc_formata_data_pg($x_data_final);
			$x_data_final = str_replace("'","",$x_data_final);
		}else{
			$msg_erro .= " Preencha o campo Data Inicial para realizar a pesquisa. ";
		}
	}else{
		$msg_erro .= " Informe as datas corretas para realizar a pesquisa. ";
	}
}


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

<? include "javascript_calendario.php";  // adicionado por Fabio 27-09-2007 ?>

<script type="text/javascript" charset="utf-8">
	$(function()
	{
		$('#data_inicial_01').datePicker({startDate:'01/01/2000'});
		$('#data_final_01').datePicker({startDate:'01/01/2000'});
		$("#data_inicial_01").maskedinput("99/99/9999");
		$("#data_final_01").maskedinput("99/99/9999");
	});

/* POP-UP IMPRIMIR */
	function abrir(URL) {
		var width = 850;
		var height = 550;
		var left = 90;
		var top = 90;

		window.open(URL,'janela', 'width='+width+', height='+height+', top='+top+', left='+left+', scrollbars=yes, status=no, toolbar=no, location=no, directories=no, menubar=no, resizable=no, fullscreen=no');
	}
</script>
</head>

<body>

<center><h1>Reembolso Frete</h1></center>

<? if(strlen($msg_erro)>0){?>
	<table width='100%' border='0' cellspacing='0' cellpadding='0' align='center'>
	<TR>
		<TD class='Erro'><? echo $msg_erro; ?></TD>
	</TR>
	</TABLE>
<?}?>

<BR>
<FORM METHOD="POST" NAME="frm_frete" ACTION="<? echo $PHP_SELF; ?>">
	<table width='800' border='0' cellspacing='0' cellpadding='0' align='center'>
		<TR>
			<TD>Data Inicial</TD>
			<TD>
				<INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" id="data_inicial_01" value="<? if (strlen($data_inicial_01) > 0) echo $data_inicial_01; else echo "dd/mm/aaaa"; ?>" onClick="this.value=''">
			</TD>
			<TD>Data Final</TD>
			<TD>
				<INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" id="data_final_01" value="<? if (strlen($data_final_01) > 0) echo $data_final_01; else echo "dd/mm/aaaa"; ?>" onClick="this.value=''">
			</TD>
			<TD>
				<select name="fabrica" size="1" class="frm">
					<?
					$sql = "SELECT  tbl_fabrica.fabrica,
									tbl_fabrica.nome
							FROM    tbl_fabrica
							WHERE fabrica IN (".implode(",", $fabricas).")
							ORDER BY tbl_fabrica.nome";
					$res = @pg_exec ($con,$sql);
					echo $sql;
					for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
						$fab = pg_result($res,$i,fabrica);
						$nom = pg_result($res,$i,nome);

						echo "<option value='$fab'";
						if ($fab == $fabrica OR $fab == 3) echo " selected";
						echo ">" . $nom . "</option>";
					}
					?>
				</select>
			</TD>
			<TD><INPUT TYPE="submit" NAME="btn_acao" VALUE="PESQUISAR"></TD>
		</TR>
	</TABLE>
</FORM>


<?

if (strlen($btn_acao) > 0 AND strlen($msg_erro) == 0) {
	$sql = "select tbl_embarque.embarque                              ,
			TO_CHAR(tbl_embarque.faturar, 'dd/mm/yyy') AS faturar     ,
			tbl_posto.nome                                            ,
			tbl_posto.cidade                                          ,
			tbl_posto.estado                                          ,
			tbl_faturamento.total_nota                                ,
			tbl_embarque.total_frete
		INTO TEMP temp_embarque_$fabrica
		from tbl_embarque
		join tbl_faturamento using(embarque)
		join tbl_posto on tbl_faturamento.posto = tbl_posto.posto
		where emissao between '$x_data_inicial' and '$x_data_final'
		AND tbl_faturamento.fabrica = $fabrica
		GROUP BY tbl_embarque.embarque  ,
			tbl_embarque.faturar        ,
			tbl_posto.nome              ,
			tbl_posto.cidade            ,
			tbl_posto.estado            ,
			tbl_faturamento.total_nota  ,
			tbl_embarque.total_frete
		ORDER BY tbl_posto.nome;

		CREATE INDEX temp_embarque_EMBARQUE_$fabrica ON temp_embarque_$fabrica(embarque);

		SELECT DISTINCT temp_embarque_$fabrica.embarque  ,
		temp_embarque_$fabrica.faturar          ,
		temp_embarque_$fabrica.nome             ,
		temp_embarque_$fabrica.cidade           ,
		temp_embarque_$fabrica.estado           ,
		/*temp_embarque_$fabrica.total_nota       ,*/
		temp_embarque_$fabrica.total_frete      
		/*(SELECT SUM(tbl_faturamento.total_nota)
		FROM tbl_faturamento
		WHERE tbl_faturamento.embarque = temp_embarque_$fabrica.embarque
		AND   tbl_faturamento.fabrica  = $fabrica
		) AS soma_notas*/
		FROM temp_embarque_$fabrica
		ORDER BY temp_embarque_$fabrica.nome, temp_embarque_$fabrica.embarque;
		";
	#echo nl2br($sql);

	$res = pg_exec($con, $sql);

	if(pg_numrows($res)>0){
		echo "<br>";
		/*echo "<div><A HREF=\"javascript:abrir('impressao_reembolso.php?x_data_inicial=$x_data_inicial&x_data_final=$x_data_final&fabrica=$fabrica')\">";
		echo "Clique aqui para imprimir";
		echo "</A></div>";*/

		echo "<table width='800' border='0' cellspacing='1' cellpadding='2' align='center'>";
			echo "<TR bgcolor='#0099CC'style='color:#ffffff; text-align:center; font-weight:bold;'>";
				echo "<TD>Embarque</TD>";
				echo "<TD>Faturar</TD>";
				echo "<TD>Posto</TD>";
				echo "<TD>Cidade</TD>";
				echo "<TD>UF</TD>";
				#echo "<TD>Total Nota</TD>";
				#echo "<TD>%</TD>";
				echo "<TD>Frete Pago</TD>";
			echo "</TR>";

		for($i=0; $i<pg_numrows($res); $i++){
			$embarque    = pg_result($res, $i, embarque);
			$faturamento = pg_result($res, $i, faturar);
			$nome        = pg_result($res, $i, nome);
			$cidade      = pg_result($res, $i, cidade);
			$estado      = pg_result($res, $i, estado);
			#$soma_notas = pg_result($res, $i, soma_notas);
			#$total_nota  = pg_result($res, $i, total_nota);
			$total_frete = pg_result($res, $i, total_frete);

			#$porcentagem = ((100*$total_nota)/$soma_notas);

			//rateio do frete
			#$frete_rateio = (($total_frete*$porcentagem)/100);
			$frete_rateio = $total_frete;
			#$soma_total_nota  = $soma_total_nota  + $total_nota;
			$soma_total_frete = $soma_total_frete + $total_frete;

			$total_nota  = number_format($total_nota, 2, ",", ".");
			$total_frete = number_format($total_frete, 2, ",", ".");

			/*if($porcentagem<100){
				$porcentagem  = number_format($porcentagem, 2, ",", ".");
			}*/

			$frete_rateio = number_format($frete_rateio, 2, ",", ".");

			if($cor=="#F1F4FA")$cor = '#F7F5F0';
			else               $cor = '#F1F4FA';

			echo "<TR bgcolor='$cor'>";
				echo "<TD class='Conteudo'>$embarque</TD>";
				echo "<TD class='Conteudo'>$faturamento</TD>";
				echo "<TD class='Conteudo'>$nome</TD>";
				echo "<TD class='Conteudo'>$cidade</TD>";
				echo "<TD class='Conteudo2'>$estado</TD>";
				#echo "<TD class='Conteudo2'>$total_nota</TD>";
				#echo "<TD class='Conteudo2'>$porcentagem</TD>";
				echo "<TD class='Conteudo2'>$frete_rateio</TD>";
			echo "</TR>";
		}
		$soma_total_nota  = number_format($soma_total_nota, 2, ",", ".");
		$soma_total_frete = number_format($soma_total_frete, 2, ",", ".");

		echo "<TR class='Conteudo' bgcolor='$cor'>";
			echo "<TD colspan='4'>&nbsp;</TD>";
			echo "<TD bgcolor='#0099CC' style='color:#ffffff ; text-align:center; font-weight:bold;'>Total</TD>";
			/*echo "<TD bgcolor='#0099CC' style='color:#ffffff; text-align:center; font-weight:bold;'>$soma_total_nota</TD>";*/
			echo "<TD bgcolor='#0099CC'style='color:#ffffff; text-align:center; font-weight:bold;'> $soma_total_frete</TD>";
		echo "</TR>";
		echo "</TABLE>";
	}

include 'rodape.php';
}

?>
</body>
</html>