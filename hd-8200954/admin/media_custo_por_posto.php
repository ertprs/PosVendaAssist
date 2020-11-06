<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria";
include 'autentica_admin.php';

$msg_erro = "";

$layout_menu = "auditoria";
$title = "Relatório de Custos com Consertos por Posto";

include "cabecalho.php";

?>

<style type="text/css">

.menu_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
	background-color: #d9e2ef
}

.pesquisa {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}

.border {
	border: 1px solid #ced7e7;
}

.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #ffffff
}

input {
	font-size: 10px;
}

.top_list {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color:#596d9b;
	background-color: #d9e2ef
}

.line_list {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#596d9b;
	background-color: #ffffff
}

</style>
<p>

<table align='center' border='0' cellspacing='0' cellpadding='2'>
<form name='frm_percentual' action='<? echo $PHP_SELF ?>'>
<tr class='menu_top'>
	<td class='pesquisa'>Selecione o MÊS</td>
	<td class='pesquisa'>Selecione o ANO</td>
	<td class='pesquisa'>Selecione a LINHA</td>
	<td class='pesquisa'>&nbsp;</td>
	
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
	<td align='center'>
		<select name='linha'>
			<option value=''></option>
<?
	// LINHA
	$sql = "SELECT	linha,
					nome
			FROM	tbl_linha
			WHERE	fabrica = $login_fabrica
			ORDER BY nome ASC";
	$res = pg_exec($con,$sql);
	for($i=0; $i<pg_numrows($res); $i++){
		$xlinha = pg_result($res,$i,linha);
		$nome   = pg_result($res,$i,nome);
		echo "<option value=$xlinha ";
		if ($xlinha == $linha) echo "selected";
		echo ">$nome</option>\n";
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

	// seleciona as 6 datas, validando com a funcao
	$sql = "SELECT  (
						(fn_dias_mes('$ano-$mes-01',0) - interval '0 months'):: date
					) AS data_inicio_0,
					(
						(fn_dias_mes('$ano-$mes-01',1) - interval '0 months'):: date
					) AS data_fim_0,
					(
						(fn_dias_mes('$ano-$mes-01',0) - interval '1 months'):: date
					) AS data_inicio_1,
					(
						(fn_dias_mes('$ano-$mes-01',1) - interval '1 months'):: date
					) AS data_fim_1,
					(
						(fn_dias_mes('$ano-$mes-01',0) - interval '2 months'):: date
					) AS data_inicio_2,
					(
						(fn_dias_mes('$ano-$mes-01',1) - interval '2 months'):: date
					) AS data_fim_2,
					(
						(fn_dias_mes('$ano-$mes-01',0) - interval '3 months'):: date
					) AS data_inicio_3,
					(
						(fn_dias_mes('$ano-$mes-01',1) - interval '3 months'):: date
					) AS data_fim_3";
	$resDia = pg_exec($con,$sql);
	if (pg_numrows($resDia) > 0){
		$data_inicio[0] = pg_result($resDia,0,data_inicio_0) . ' 00:00:00';
		$data_fim[0]    = pg_result($resDia,0,data_fim_0)    . ' 23:59:59';
		$data_inicio[1] = pg_result($resDia,0,data_inicio_1) . ' 00:00:00';
		$data_fim[1]    = pg_result($resDia,0,data_fim_1)    . ' 23:59:59';
		$data_inicio[2] = pg_result($resDia,0,data_inicio_2) . ' 00:00:00';
		$data_fim[2]    = pg_result($resDia,0,data_fim_2)    . ' 23:59:59';
		$data_inicio[3] = pg_result($resDia,0,data_inicio_3) . ' 00:00:00';
		$data_fim[3]    = pg_result($resDia,0,data_fim_3)    . ' 23:59:59';
	}

	###########################################################
	//	seleciona os valores dos postos
	###########################################################

	$sql = "SELECT	z.n1             ,
					z.e1             ,
					z.cp1            ,
					z.f1             ,
					sum(z.tt4) AS tt4,
					sum(z.tt3) AS tt3,
					sum(z.tt2) AS tt2,
					sum(z.tt1) AS tt1
			FROM(
					(
					SELECT	x.nome AS n1         ,
							x.estado AS e1       ,
							x.codigo_posto AS cp1,
							x.fabrica AS f1      ,
							x.total AS tt1       ,
							x.tt2, x.tt3         ,
							x.tt4
					FROM	(
							SELECT	tbl_posto.nome                 ,
									tbl_posto.estado               ,
									tbl_posto_fabrica.codigo_posto ,
									tbl_posto_fabrica.fabrica      ,
									tbl_extrato.total              ,
									0 AS tt2                       ,
									0 AS tt3                       ,
									0 AS tt4
							FROM	tbl_os
							JOIN	tbl_posto         ON tbl_posto.posto = tbl_os.posto 
							JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto 
							JOIN	tbl_os_produto    ON tbl_os_produto.os = tbl_os.os 
							JOIN	tbl_produto       ON tbl_produto.produto = tbl_os_produto.produto 
							JOIN	tbl_linha         ON tbl_linha.linha = tbl_produto.linha 
							JOIN	tbl_os_extra      ON tbl_os_extra.os = tbl_os.os 
							JOIN	tbl_extrato       ON tbl_extrato.extrato = tbl_os_extra.extrato 
							WHERE	tbl_extrato.aprovado BETWEEN '$data_inicio[0]' AND '$data_fim[0]' 
							AND		tbl_os.fabrica  = $login_fabrica
							AND		tbl_linha.linha = $linha
							) AS x
					WHERE	x.fabrica = $login_fabrica
					ORDER BY (x.total) DESC
					) UNION (
					SELECT	x.nome AS n1         ,
							x.estado AS e1       ,
							x.codigo_posto AS cp1,
							x.fabrica AS f1      ,
							x.tt1                ,
							x.total AS tt2       ,
							x.tt3                ,
							x.tt4
					FROM	(
							SELECT	tbl_posto.nome                ,
									tbl_posto.estado              ,
									tbl_posto_fabrica.codigo_posto,
									tbl_posto_fabrica.fabrica     ,
									0 AS tt1                      ,
									tbl_extrato.total             ,
									0 AS tt3                      ,
									0 AS tt4
							FROM	tbl_os
							JOIN	tbl_posto         ON tbl_posto.posto = tbl_os.posto 
							JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto 
							JOIN	tbl_os_produto    ON tbl_os_produto.os = tbl_os.os 
							JOIN	tbl_produto       ON tbl_produto.produto = tbl_os_produto.produto 
							JOIN	tbl_linha         ON tbl_linha.linha = tbl_produto.linha 
							JOIN	tbl_os_extra      ON tbl_os_extra.os = tbl_os.os 
							JOIN	tbl_extrato       ON tbl_extrato.extrato = tbl_os_extra.extrato 
					WHERE	tbl_extrato.aprovado BETWEEN '$data_inicio[1]' AND '$data_fim[1]' 
					AND		tbl_os.fabrica  = $login_fabrica
					AND		tbl_linha.linha = $linha
					) AS x
					WHERE	x.fabrica = $login_fabrica
					ORDER BY (x.total) DESC
					) UNION (
					SELECT	x.nome AS n1         ,
							x.estado AS e1       ,
							x.codigo_posto AS cp1,
							x.fabrica AS f1      ,
							x.tt1                ,
							x.tt2                ,
							x.total AS tt3       ,
							x.tt4
					FROM	(
							SELECT	tbl_posto.nome                ,
									tbl_posto.estado              ,
									tbl_posto_fabrica.codigo_posto,
									tbl_posto_fabrica.fabrica     ,
									0 AS tt1                      ,
									0 AS tt2                      ,
									tbl_extrato.total             ,
									0 AS tt4
							FROM	tbl_os
							JOIN	tbl_posto         ON tbl_posto.posto = tbl_os.posto 
							JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto 
							JOIN	tbl_os_produto    ON tbl_os_produto.os = tbl_os.os 
							JOIN	tbl_produto       ON tbl_produto.produto = tbl_os_produto.produto 
							JOIN	tbl_linha         ON tbl_linha.linha = tbl_produto.linha 
							JOIN	tbl_os_extra      ON tbl_os_extra.os = tbl_os.os 
							JOIN	tbl_extrato       ON tbl_extrato.extrato = tbl_os_extra.extrato 
							WHERE	tbl_extrato.aprovado BETWEEN '$data_inicio[2]' AND '$data_fim[2]' 
							AND		tbl_os.fabrica  = $login_fabrica
							AND		tbl_linha.linha = $linha 
							) AS x
					WHERE	x.fabrica = $login_fabrica
					ORDER BY (x.total) DESC
					) UNION (
					SELECT	x.nome AS n1         ,
							x.estado AS e1       ,
							x.codigo_posto AS cp1,
							x.fabrica AS f1      ,
							x.tt1                ,
							x.tt2                ,
							x.tt3                ,
							x.total AS tt4
					FROM	(
							SELECT	tbl_posto.nome                ,
									tbl_posto.estado              ,
									tbl_posto_fabrica.codigo_posto,
									tbl_posto_fabrica.fabrica     ,
									0 AS tt1                      ,
									0 AS tt2                      ,
									0 AS tt3                      ,
									tbl_extrato.total
							FROM	tbl_os
							JOIN	tbl_posto         ON tbl_posto.posto = tbl_os.posto
							JOIN	tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto.posto 
							JOIN	tbl_os_produto    ON tbl_os_produto.os = tbl_os.os 
							JOIN	tbl_produto       ON tbl_produto.produto = tbl_os_produto.produto 
							JOIN	tbl_linha         ON tbl_linha.linha = tbl_produto.linha 
							JOIN	tbl_os_extra      ON tbl_os_extra.os = tbl_os.os 
							JOIN	tbl_extrato       ON tbl_extrato.extrato = tbl_os_extra.extrato 
							WHERE	tbl_extrato.aprovado BETWEEN '$data_inicio[3]' AND '$data_fim[3]'
							AND		tbl_os.fabrica  = $login_fabrica
							AND		tbl_linha.linha = $linha
							) AS x
					WHERE	x.fabrica = $login_fabrica
					ORDER BY (x.total) DESC
				)
			) AS z 
			GROUP BY z.n1 ,
					z.e1  ,
					z.cp1 ,
					z.f1  ;";
	$res = pg_exec ($con,$sql);
	
	// para montar o título com os meses
	$month = array(1=>"Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez");

	for($i=3; $i>=0; $i--){
		$sqlY = "SELECT to_char ('$data_inicio[$i]':: date, 'MM'), to_char ('$data_inicio[$i]':: date, 'YY')";
		$resY = pg_exec ($con,$sqlY);
		$mesY = intval(pg_result($resY,0,0));
		$anoY = pg_result($resY,0,1);
		$valorLabel[$i] = $month[$mesY]."/".$anoY;
	}

	echo "<table width='700' >";
//	echo "<tr class='pesquisa'><td colspan='8'>Postos com gastos acima da Média (". number_format($media,2,",",".") .") + Desvio Padrão (". number_format($mes4_desvio_geral,2,",",".") .")</td></tr>";
	echo "<tr class='pesquisa'><td colspan='8'>&nbsp;</td></tr>";
	echo "<tr class='menu_top'>";
	echo "<td>POSTO</td>";
	echo "<td>NOME</td>";
	echo "<td>UF</td>";
	echo "<td>$valorLabel[3]</td>";
	echo "<td>$valorLabel[2]</td>";
	echo "<td>$valorLabel[1]</td>";
	echo "<td>$valorLabel[0]</td>";
	echo "<td>%</td>";
	echo "</tr>";

	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$perc_acima     = 0;
		$codigo_posto   = pg_result ($res,$i,cp1);
		$nome           = pg_result ($res,$i,n1);
		$estado         = pg_result ($res,$i,e1);
		$m1_media_total = pg_result ($res,$i,tt1);
		$m2_media_total = pg_result ($res,$i,tt2);
		$m3_media_total = pg_result ($res,$i,tt3);
		$m4_media_total = pg_result ($res,$i,tt4);
		
		// media dos 3 meses anteriores
		$media = ($m4_media_total + $m3_media_total + $m2_media_total) / 3;
		
		if ($media > 0 and $m1_media_total > 0){
			$perc_acima = 100 - ($media * 100 / $m1_media_total);
			$perc_acima = number_format ($perc_acima,2,",",".");
		}else{
			$perc_acima = 0;
			$perc_acima = number_format ($perc_acima,2,",",".");
		}
		
		$cor = "#F7F5F0"; 
		if ($i % 2 == 0) $cor = '#F1F4FA';
		
		echo "<tr class='table_line'  style='background-color: $cor;'>";
		
		echo "<td align='left'>";
		echo $codigo_posto; 
		echo "</td>";
		
		echo "<td align='left'>";
		echo $nome;
		echo "</td>";
		
		echo "<td align='center'>";
		echo $estado;
		echo "</td>";
		
		echo "<td align='right'>";
		echo number_format($m4_media_total,2,",",".");
		echo "</td>";
		
		echo "<td align='right'>";
		echo number_format($m3_media_total,2,",",".");
		echo "</td>";

		echo "<td align='right'>";
		echo number_format($m2_media_total,2,",",".");
		echo "</td>";

		echo "<td align='right'>";
		echo number_format($m1_media_total,2,",",".");
		echo "</td>";

		echo "<td align='right'>";
		echo $perc_acima ."%";
		echo "</td>";
		
		echo "</tr>";
		
	}

	echo "<tr class='menu_top'>";
	echo "<td align='rigth' colspan='3'>";
	echo "&nbsp;&nbsp;MÉDIA DOS MESES: ";
	echo "</td>";
	
	echo "<td align='right'>";
	echo $mes1_media;
	echo "</td>";
	
	echo "<td align='right'>";
	echo $mes2_media;
	echo "</td>";
	
	echo "<td align='right'>";
	echo $mes3_media;
	echo "</td>";
	
	echo "<td align='right'>";
	echo $mes4_media;
	echo "</td>";

	echo "<td align='right'>";
	echo $perc;
	echo "</td>";

	echo "</tr>";
	
	echo "</table>";
	echo "<p>";	
	
	flush();
	
}

echo "<br><br>";

include "rodape.php"; 

?>