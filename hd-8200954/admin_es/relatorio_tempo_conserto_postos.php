<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$msg_erro = "";

$layout_menu = "gerencia";

$title = "Reporte de tiempo de reparación / Servicios Autorizados";

include "cabecalho.php";

$mes    = trim($_GET['mes']);
$ano    = trim($_GET['ano']);
$estado = trim($_GET['estado']);
$pais = trim($_GET['pais']);

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
<form name='frm_percentual' action='<? echo $PHP_SELF ?>' method='get'>
<tr class='menu_top'>
	<td align='center'><font size='2'>Eleja el MES</font></td>
	<td align='center'><font size='2'>Eleja el AÑO</font></td>
	<td align='center'><font size='2'>Provincia</font></td>
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
	<td>
		<select name="estado" size="1">
			<option value=""   <? if (strlen($estado) == 0)    echo " selected "; ?>>TODOS LAS PROVINCIAS</option>

<?
	$sql = "SELECT	*
			FROM tbl_estado
			WHERE pais ='$login_pais'";

	$res_prov = @pg_exec($con,$sql);

if(pg_numrows($res_prov)>0){
	for ($i=0; $i < @pg_numrows($res_prov) ; $i++){
		$estado_e = @pg_result($res_prov,$i,estado);
		$nome_e	= @pg_result($res_prov,$i,nome);
		$select= ""; 
		if ($estado_e == $estado) $select= "selected "; 

		echo "<option value='$estado_e' $select>$nome_e</option>";
	}
}
?>
			
		</select>
	</td>
	<td><img src='imagens_admin/btn_confirmar.gif' border=0 onclick='javascript: submit();' style='cursor:pointer'></td>
</tr>
</form>
</table>

<br>

<?



if (strlen($mes) > 0 AND strlen($ano) > 0){

	echo "<table align='center' border='0' cellspacing='1' cellpadding='5'>";

	$nomemes = array (1 => "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre");

	echo "<tr class='menu_top'>\n";
	echo "<td>#</td>";
	for ($x=1; $x <= $mes; $x++){
		echo "<td>Resultados</td>";
		echo "<td>$nomemes[$x]</td>";
	}
	echo "<td>PROMEDIO</td>";
	echo "</tr>";

	// seleciona os postos
	$sql = "SELECT	tbl_posto.posto,
					tbl_posto.nome ,
					tbl_posto.estado
			FROM	tbl_posto
			JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto
			JOIN	tbl_fabrica       ON tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
			WHERE	tbl_posto_fabrica.fabrica = $login_fabrica ";

	if(strlen($estado) > 0) $sql .= "AND		tbl_posto.estado = '$estado'";
	if(strlen($pais) > 0) $sql .= "AND		tbl_posto.pais = '$pais'";
	$sql .= " ORDER BY tbl_posto.nome ASC ";
	$resX = @pg_exec($con,$sql);

	$media_geral = 0;

	for ($z=0; $z < @pg_numrows($resX) ; $z++){

		$posto = @pg_result($resX,$z,0);
		$nome  = @pg_result($resX,$z,1);
		$estado= @pg_result($resX,$z,2);

//		echo "<br>{ [ $z ] - $posto - $nome - $estado }<br>";

		$cor = ($z % 2 == 0) ? '#F1F4FA' : "#F9F9F9";

		echo "<tr class='table_line' bgcolor='$cor'>\n";
		echo "<td align='left'><a href='relatorio_tempo_conserto_os.php?posto=$posto&mes=$mes&ano=$ano&estado=$estado&pais=$pais'>$nome</td>\n";

		$total_diferenca   = 0;
		$total_ocorrencias = 0;
		$exibetotal        = 0; // valor total ($total_diferenca/$total_ocorrencias)
		$total             = 0; // valor acumulado de $total
		$divide            = 0; // valor de meses a ser dividido o valor de $exibetotal
		$media             = 0;

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

			$sql = "SELECT	count(tbl_os.*) AS total                                              ,
							SUM((tbl_os.data_fechamento - tbl_os.data_abertura)) AS data_diferenca
					FROM	tbl_os
					JOIN	tbl_posto   USING(posto)
					JOIN	tbl_fabrica USING(fabrica)
					JOIN	tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_posto.posto
												AND tbl_posto_fabrica.fabrica = tbl_fabrica.fabrica
					WHERE	tbl_os.fabrica = $login_fabrica
					AND		tbl_os.posto   = $posto
					AND		tbl_os.data_abertura   BETWEEN '$data_inicial' AND '$data_final'
					AND		tbl_os.data_fechamento NOTNULL
					AND		tbl_os.finalizada      NOTNULL
					AND		tbl_os.excluida IS NOT TRUE ";
			$res2 = pg_exec($con,$sql);

#	echo $sql."<br>";
#	echo "{ $i } Total: ".pg_result($res2,0,0) ." || Diferenca: ". pg_result($res2,0,1)."<br><br>";

			if (pg_numrows($res2) > 0) {

				$total_ocorrencias = pg_result($res2,0,total);
				$total_diferenca   = pg_result($res2,0,data_diferenca);
				
				$xtotal_ocorrencias += $total_ocorrencias;
				$xtotal_diferenca   += $total_diferenca;
				
				echo "<td align='center'>";
				if ($total_ocorrencias > 0) {
					echo $total_ocorrencias;
				}
				echo "</td>";
				
				echo "<td align='right'>";
				if ($total_ocorrencias > 0) {
					$exibetotal = ($total_diferenca / $total_ocorrencias);
					$exibetotalX = number_format($exibetotal,2,'.',''); 
					echo $exibetotalX;
					//echo round($exibetotal, 2);
					$total += $exibetotal;
					$divide++;
					if ($i == $mes) $divide_geral++;

				}
				echo "</td>\n";
			}

		}

		echo "<td align='right'>";
		if ($divide > 0){
			$media = ($total / $divide);
			$media_geral += $media;
		}

		$mediaX = number_format($media,2,'.',''); 
		echo $mediaX;
		//echo round($media, 2);
		echo "</td>";
		echo "</tr>\n";
	}
	
	echo "</table>";

	echo "<table align='center'>";
	echo "<tr>";

	if (strlen($divide_geral) > 0) $exibe_geral = $xtotal_diferenca / $xtotal_ocorrencias;
	else                           $exibe_geral = 0;

	echo "<td align='right' align='center'>PROMEDIO DEL SERVICIOS: <b>";
	$exibe_geral = number_format($exibe_geral,2,'.',''); 
	echo $exibe_geral;
	echo "</b></td>";
	echo "</tr>";
	echo "</table>";

}

echo "<br><br>";

include "rodape.php"; 

?>
