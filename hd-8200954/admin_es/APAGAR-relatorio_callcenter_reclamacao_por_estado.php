<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia,call_center";
include 'autentica_admin.php';

$msg_erro = "";

$layout_menu = "gerencia";
$title = "Call-Center - Reportes de Reclamos del Servicio por Estado";

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
<form name='frm_relatorio' action='<? echo $PHP_SELF ?>'>
<tr class='menu_top'>
	<td align='center'><font size='2'>Elija el AÑO</font></td>
	<td>&nbsp;</td>
</tr>
<tr class='table_line'>
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
<? selectAnoSimples(0,0,'',$ano) ?>
		</select>
	</td>
	<td><img src='imagens_admin/btn_confirmar.gif' border=0 onclick='javascript: submit();' style='cursor:pointer'></td>
</tr>
</form>
</table>

<br>

<?
if (strlen($ano) > 0){

	echo "<table align='center' border='0' cellspacing='1' cellpadding='5'>";

	$nomemes = array(1=> "ENE", "FEB", "MAR", "ABR", "MAY", "JUN", "JUL", "AGO", "SEP", "OCT", "NOV", "DIC");

	$mes = 12; // até dezembro

	echo "<tr class='menu_top'>\n";
	echo "<td>UF</td>";
	for ($i=1; $i <= $mes; $i++){
		echo "<td>$nomemes[$i]</td>";
	}
	echo "</tr>";

	$sql = "SELECT	tbl_cidade.estado
			FROM	tbl_callcenter
			JOIN	tbl_cliente USING(cliente)
			JOIN	tbl_cidade  ON tbl_cidade.cidade = tbl_cliente.cidade
			WHERE	tbl_callcenter.fabrica = $login_fabrica
			GROUP BY tbl_cidade.estado
			ORDER BY tbl_cidade.estado";
	$res = pg_exec($con,$sql);

	for ($i=0; $i < pg_numrows($res); $i++){

		$estado = pg_result($res,$i,0);

		$cor = ($i % 2 == 0) ? '#F1F4FA' : "#F9F9F9";

		echo "<tr class='table_line' bgcolor='$cor'>\n";
		echo "<td>".strtoupper($estado)."</td>";

		for ($x=1; $x <= 12; $x++){

			if ($x < 10)
				$iMes = "0" .intval($x);
			else
				$iMes = intval($x);

			$sql = "SELECT fn_dias_mes('$ano-$iMes-01',0)";
			$res3 = pg_exec($con,$sql);
			$data_inicial = pg_result($res3,0,0);

			$sql = "SELECT fn_dias_mes('$ano-$iMes-01',1)";
			$res3 = pg_exec($con,$sql);
			$data_final = pg_result($res3,0,0);

			$sql = "SELECT	count(tbl_callcenter.*) AS total
					FROM	tbl_callcenter
					JOIN	tbl_cliente USING(cliente)
					JOIN	tbl_cidade  ON tbl_cidade.cidade = tbl_cliente.cidade
					WHERE	tbl_callcenter.fabrica = $login_fabrica
					AND		tbl_callcenter.data BETWEEN '$data_inicial' AND '$data_final'
					AND		tbl_cidade.estado = '$estado'
					GROUP BY tbl_cidade.estado
					ORDER BY tbl_cidade.estado";
			$res2 = @pg_exec($con,$sql);

			$total_ocorrencias = @pg_result($res2,0,total);

			echo "<td align='right'>";
			if (strlen($total_ocorrencias ) > 0) echo round($total_ocorrencias );
			echo "&nbsp;</td>\n";
		}
	}
	echo "</tr>\n";

	echo "</table>";
}
echo "<br><br>";

include "rodape.php"; 

?>