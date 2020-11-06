<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$msg_erro = "";

$layout_menu = "gerencia";
$title = "Costo por OS";

include "cabecalho.php";

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

<p>

<?
$mes = $_POST['mes'];
$ano = $_POST['ano'];

?>
<style>
.Titulo{
	font-size:12px;
}
.Conteudo{
	font-size:10px;
}
</style>
<table align='center' border='0' cellspacing='2' cellpadding='2'>
<form name='frm_custo' action='<? echo $PHP_SELF ?>' method='post'>
<tr>
	<td bgcolor='#556699' style='color: #ffffff'><b>Mes</b></td>
	<td>
		<select name='mes' size='1'>
		<option value=''></option>
		<option value='01' <? if ($mes == '01') echo ' selected ' ?> >Eneiro</option>
		<option value='02' <? if ($mes == '02') echo ' selected ' ?> >Febrero</option>
		<option value='03' <? if ($mes == '03') echo ' selected ' ?> >Marzo</option>
		<option value='04' <? if ($mes == '04') echo ' selected ' ?> >Abril</option>
		<option value='05' <? if ($mes == '05') echo ' selected ' ?> >Mayo</option>
		<option value='06' <? if ($mes == '06') echo ' selected ' ?> >Junio</option>
		<option value='07' <? if ($mes == '07') echo ' selected ' ?> >Julio</option>
		<option value='08' <? if ($mes == '08') echo ' selected ' ?> >Agosto</option>
		<option value='09' <? if ($mes == '09') echo ' selected ' ?> >Septiembre</option>
		<option value='10' <? if ($mes == '10') echo ' selected ' ?> >Octubre</option>
		<option value='11' <? if ($mes == '11') echo ' selected ' ?> >Noviembre</option>
		<option value='12' <? if ($mes == '12') echo ' selected ' ?> >Diciembre</option>
		</select>
	</td>
</tr>
<tr>
	<td bgcolor='#556699' style='color: #ffffff'><b>Año</b></td>
	<td>
		<select name='ano' size='1'>
		<option value=''></option>
		<? selectAnoSimples(1,0,'',$ano) ?>
		</select>
	</td>
</tr>

<tr>
	<td colspan='2' align='center'><input type='submit' name='btn_acao' value='Crear Reporte'></td>
</tr>

</form>
</table>

<br>

<?
if (strlen($mes) > 0 AND strlen($ano) > 0){
	$data_inicial = $ano . "-" . $mes . "-01 00:00:00";
	$data_final   = pg_result (pg_exec ($con,"SELECT ('$data_inicial'::date + INTERVAL '1 month' - INTERVAL '1 day')::date "),0,0) . " 23:59:59";

	$sql = "SELECT tbl_posto.nome, tbl_posto_fabrica.codigo_posto, tbl_posto.fone, tbl_posto.email, med.media , med.qtde
			FROM tbl_posto
			JOIN tbl_posto_fabrica ON tbl_posto.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN (SELECT tbl_os.posto, AVG (tbl_os.mao_de_obra + tbl_os.pecas) AS media , COUNT(*) AS qtde
					FROM tbl_os
					JOIN tbl_posto USING (posto)
					JOIN tbl_os_extra ON tbl_os.os = tbl_os_extra.os
					JOIN tbl_extrato  ON tbl_os_extra.extrato = tbl_extrato.extrato
					WHERE tbl_os.fabrica = $login_fabrica
					AND   tbl_posto.pais = '$login_pais'
					AND   tbl_extrato.aprovado BETWEEN '$data_inicial' AND '$data_final'
					GROUP BY tbl_os.posto
			) med ON tbl_posto.posto = med.posto
			ORDER BY media DESC";
	$res = pg_exec ($con,$sql);

	echo "<table  align='center' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#d2e4fc' width='700'>";
	echo "<tr bgcolor='#556699' align='center' style='color: #ffffff ; font-weight:bold' class='Titulo'>";
	echo "<td>Codigo</td>";
	echo "<td>Nombre</td>";
	echo "<td>Teléfono</td>";
	echo "<td>Correo</td>";
	echo "<td>Ctd OS</td>";
	echo "<td>Promedio $</td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		if($cor=="#F1F4FA")     $cor = '#F7F5F0';
		else                    $cor = '#F1F4FA';

		echo "<tr align='left' bgcolor='$cor' class='Conteudo'>";

		echo "<td>";
		echo pg_result ($res,$i,codigo_posto);
		echo "</td>";

		echo "<td>";
		echo pg_result ($res,$i,nome);
		echo "</td>";

		echo "<td>";
		echo pg_result ($res,$i,fone);
		echo "</td>";

		echo "<td>";
		echo pg_result ($res,$i,email);
		echo "</td>";

		echo "<td align='right'>";
		echo pg_result ($res,$i,qtde);
		echo "</td>";

		echo "<td align='right'>";
		echo number_format (pg_result ($res,$i,media),2,",",".");
		echo "</td>";

		echo "</tr>";
	}

}

echo "</table>";

echo "<br><br>";

include "rodape.php"; 

?>
