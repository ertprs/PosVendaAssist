<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$msg_erro = "";

$layout_menu = "gerencia";
$title = "Reporte de tiempo de reparación";

include "cabecalho.php";

?>

<p>

<style type="text/css">

.menu_top {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

a:link.top{
	color:#ffffff;
}
a:visited.top{
	color:#ffffff;
}
a:hover.top{
	color:#ffffff;
}

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
}

</style>

<table align='center' border='0' cellspacing='2' cellpadding='2'>
<form name='frm_percentual' action='<? echo $PHP_SELF ?>'>
<tr class='menu_top'>
	<td align='center'><font size='2'>Eleja el MES</font></td>
	<td align='center'><font size='2'>Eleja el AÑO</font></td>
	<td>&nbsp;</td>
</tr>
<tr class='table_line'>
	<td align='center'>
<?
/*--------------------------------------------------------------------------------
selectMesSimples()
Cria ComboBox com meses de 1 a 12
--------------------------------------------------------------------------------*/
function selectMesSimples($selectedMes){
	for($dtMes=1; $dtMes <= 12; $dtMes++){
		$dtMesTrue = ($dtMes < 10) ? "0".$dtMes : $dtMes;
		
		echo "<option value=$dtMesTrue ";
		if ($selectedMes == $dtMesTrue) echo "selected";
		echo ">$dtMesTrue</option>\n";
	}
}
?>
		<select name='mes'>
			<option value=''></option>
<? selectMesSimples($mes); ?>
		</select>
	</td>
	<td align='center'>
<?
/*--------------------------------------------------------------------------------
selectAnoSimples($ant,$pos,$dif,$selectedAno)
// $ant = qtdade de anos retroceder
// $pos = qtdade de anos posteriores
// $dif = ve qdo ano termina
// $selectedAno = ano já setado
Cria ComboBox com Anos
--------------------------------------------------------------------------------*/
function selectAnoSimples($ant,$pos,$dif=0,$selectedAno)
{
	$startAno = date("Y"); // ano atual
	for($dtAno = $startAno - $ant; $dtAno <= $startAno + ($pos - $dif); $dtAno++){
		echo "<option value=$dtAno ";
		if ($selectedAno == $dtAno) echo "selected";
		echo ">$dtAno</option>\n";
	}
}
?>
		<select name='ano'>
			<option value=''></option>
<? selectAnoSimples(1,0,'',$ano) ?>
		</select>
	</td>

	

	<td><img src='imagens_admin/btn_confirmar.gif' border=0 onclick='javascript: document.frm_percentual.submit();' style='cursor:pointer'></td>
</tr>
</form>
</table>

<br>

<?
if (strlen($mes) > 0 AND strlen($ano) > 0){
	$pais = "$login_pais";
	echo "<table align='center' border='0' cellspacing='1' cellpadding='5'>";

	$nomemes = array (1 => "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");

	echo "<tr class='menu_top'>\n";
	echo "<td>#</td>";
	for ($i=1; $i <= $mes; $i++){
		echo "<td>$nomemes[$i]</td>";
	}
	echo "<td>PROMEDIO/$ano</td>";
	echo "</tr>";

	echo "<tr class='table_line'>\n";
	echo "<td><a href='relatorio_tempo_conserto_postos.php?mes=$mes&ano=$ano&estado=$estado&pais=$pais'>SERVICIO</a></td>";
	for ($i=1; $i <= $mes; $i++){
		if ($i < 10)
			$iMes = "0" .intval($i);
		else
			$iMes = intval($i);

		$sql = "SELECT fn_dias_mes('$ano-$iMes-01',0)";
		$res3 = pg_exec($con,$sql);
		$data_inicial = pg_result($res3,0,0);

		$sql = "SELECT fn_dias_mes('$ano-$iMes-01',1)";
		$res3 = pg_exec($con,$sql);
		$data_final = pg_result($res3,0,0);

		$sql = "SELECT	count(*) AS total                                       ,
						SUM((data_fechamento - data_abertura)) AS data_diferenca
				FROM	tbl_os
				WHERE	fabrica = $login_fabrica
				AND		data_abertura BETWEEN '$data_inicial' AND '$data_final'
				AND		data_fechamento notnull ";

		$sql = "SELECT	count(tbl_os.*) AS total, 
						SUM((tbl_os.data_fechamento - tbl_os.data_abertura)) AS data_diferenca 
				FROM	tbl_os 
				JOIN	tbl_posto USING(posto)
				WHERE	tbl_os.fabrica = $login_fabrica 
				AND		tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final' 
				AND		tbl_os.data_fechamento NOTNULL
				AND		tbl_os.finalizada      NOTNULL
				AND		tbl_os.excluida IS NOT TRUE 
				AND     tbl_posto.pais = '$login_pais'";
				
		$sql = "SELECT	os,data_abertura,data_fechamento
					INTO TEMP temp_rtc_$iMes
				FROM	tbl_os 
				JOIN	tbl_posto USING(posto)
				WHERE   tbl_os.fabrica = $login_fabrica 
				AND     tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final' 
				AND     tbl_os.data_fechamento NOTNULL
				AND     tbl_os.finalizada      NOTNULL
				AND     tbl_os.excluida IS NOT TRUE
				AND     tbl_posto.pais = '$login_pais';
				";
		$res2 = pg_exec($con,$sql);

		$sql = "SELECT	COUNT(os) AS total, 
						SUM((data_fechamento - data_abertura)) AS data_diferenca 
				FROM temp_rtc_$iMes;";
		$res2 = pg_exec($con,$sql);

//if ($ip == '201.0.9.216') { echo $sql; exit; };
		$res2 = pg_exec($con,$sql);

	/*
		echo $sql."<br>";
		echo "Total: ".pg_result($res2,0,0) ." || Diferenca: ". pg_result($res2,0,1)."<br>";
	*/
		$total_ocorrencias += @pg_result($res2,0,0);
		$total_diferenca   += @pg_result($res2,0,1);

		if (@pg_numrows($res2) > 0) {
			### monta linha de nome dos produtos
			echo "<td align='right'>";
			if (@pg_result($res2,0,0) > 0)
				$total_sem_formatacao = @pg_result($res2,0,1) / @pg_result($res2,0,0);
				$numero_formatado = number_format($total_sem_formatacao,2,'.',''); 
				echo $numero_formatado; 
				//echo round(@pg_result($res2,0,1) / @pg_result($res2,0,0));
			echo "</td>\n";
		}
	}

	if ($total_ocorrencias > 0) $total = $total_diferenca / $total_ocorrencias;

	echo "<td align='right'>";
	$total = number_format($total,2,'.',''); 
	echo $total;
	//echo round($total);
	echo "</td>";
	echo "</tr>\n";

	echo "</table>";
}
echo "<br><br>";

include "rodape.php"; 

?>
