<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="auditoria";
include 'autentica_admin.php';

$msg_erro = "";

$layout_menu = "auditoria";
$title = "REPORTES GASTOS POR SERVICIO";

include "cabecalho.php";
?>
<script>

function AbrePosto(ano,mes,estado,linha){
	janela = window.open("gasto_por_posto_estado.php?ano=" + ano + "&mes=" + mes + "&estado=" + estado+ "&linha=" + linha,"Gasto",'width=700,height=300,top=0,left=0, scrollbars=yes' );
	janela.focus();
}
</script>

<style type="text/css">

.menu_top {
	text-align: center;
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
	text-align: center;
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
	<td class='pesquisa'>Elija el MES</td>
	<td class='pesquisa'>Elija el AÑO</td>
	<td class='pesquisa'>Elija la LÍNEA</td>
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
			<option value=''>Todas</option>
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

//if (strlen ($linha) == 0) $linha = 'tbl_linha.linha';

if (strlen($mes) > 0 AND strlen($ano) > 0){

	$data_inicial = $ano . "-" . $mes . "-01 00:00:00";
	$res = pg_exec ($con,"SELECT ('$data_inicial'::date + interval '1 month' - interval '1 day')::date");
	$data_final = pg_result ($res,0,0);
	$data_final = $data_final . " 23:59:59";

	$sql = "SELECT  SUM ( CASE WHEN tbl_os.mao_de_obra IS NULL THEN 0 ELSE tbl_os.mao_de_obra END )     AS mao_de_obra       ,
					SUM ( CASE WHEN tbl_os.custo_peca  IS NULL THEN 0 ELSE tbl_os.custo_peca  END )     AS pecas             ,
					COUNT (tbl_os.os)                                                                   AS qtde              ,
					STDDEV (CASE WHEN tbl_os.mao_de_obra IS NULL OR tbl_os.custo_peca IS NULL THEN 0 ELSE tbl_os.mao_de_obra + tbl_os.custo_peca END ) AS desvio  ,
					COUNT  (CASE WHEN tbl_os.consumidor_revenda = 'C' OR tbl_os.consumidor_revenda IS NULL THEN 1 ELSE NULL END)   AS qtde_os_consumidor,
					COUNT  (CASE WHEN tbl_os.consumidor_revenda = 'R' THEN 1 ELSE NULL END)   AS qtde_os_revenda
			FROM    tbl_os
			JOIN    tbl_produto    ON tbl_produto.produto = tbl_os.produto ";
	if (strlen($linha) > 0) $sql .= "JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha ";
	$sql .="JOIN    tbl_os_extra   ON tbl_os_extra.os     = tbl_os.os
			JOIN    tbl_extrato    ON tbl_extrato.extrato = tbl_os_extra.extrato
			JOIN    tbl_posto      ON tbl_posto.posto     = tbl_os.posto
			WHERE   tbl_extrato.aprovado BETWEEN '$data_inicial' AND '$data_final'
			AND     tbl_os.fabrica = $login_fabrica
			AND     tbl_posto.pais = '$login_pais'";
	if (strlen($linha) > 0) $sql .= "AND tbl_linha.linha = $linha";

	$res = pg_exec ($con,$sql);
	
	$mao_de_obra = pg_result ($res,0,mao_de_obra);
	$pecas       = pg_result ($res,0,pecas);
	$total_geral = $mao_de_obra + $pecas ;
	
	$qtde_geral         = pg_result ($res,0,qtde) ;
	$desvio_geral       = pg_result ($res,0,desvio) ;
	if (strlen($desvio_geral) == 0) $desvio_geral = 0;

	$qtde_os_consumidor = pg_result ($res,0,qtde_os_consumidor);
	$qtde_os_revenda    = pg_result ($res,0,qtde_os_revenda);
	
	echo "<table width='700'>";
	echo "<tr class='pesquisa'><td colspan='3'>Valores totales Pagos</td></tr>";
	echo "<tr class='menu_top'>";
	echo "<td width='33%'>";
	echo "MANO DE OBRA - R$ " . number_format ($mao_de_obra,2,",",".");
	echo "</td>";
	echo "<td width='33%'>";
	echo "PIEZAS - R$ " . number_format ($pecas,2,",",".");
	echo "</td>";
	echo "<td width='34%'>";
	echo "TOTAL - R$ " . number_format ($total_geral,2,",",".");
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	
	echo "<table width='700' >";
	echo "<tr class='menu_top'>";
	echo "<td width='33%'>";
	echo "CTD DE OS - " .number_format ($qtde_geral,0,",",".") ;
	echo "</td>";
	echo "<td width='33%'>";
	echo "COSTO MÉDIO $";
	if ($total_geral > 0){
		$gasto_medio = $total_geral / $qtde_geral;
	}else {
		$gasto_medio = 0;
	}
	
	echo number_format ($gasto_medio,2,",",".");
	echo "</td>";
	echo "<td width='34%'>";
	echo "LÍNEA DE DESVÍO ESTÁNDAR $ " ;
	echo number_format ($desvio_geral,2,",",".") ;
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	
	echo "<table width='700'>";
	echo "<tr class='menu_top'>";
	echo "<td width='50%'>";
	echo "CTD OS USUÁRIO  - " . number_format ($qtde_os_consumidor,0,",",".");
	echo "</td>";
	echo "<td width='50%'>";
	echo "CTD OS DISTRIBUIDOR - " . number_format ($qtde_os_revenda,0,",",".") ;
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	
	echo "<p>";

	/////////////////////////////////////////////////////////////////
	// exibe os graficos
	/////////////////////////////////////////////////////////////////
	echo "<table width='700'>";
	echo "<tr>";
	echo "<td width='50%'>";
	include ("gasto_por_posto_grafico_1.php"); // custo por OS
	echo "</td>";
	echo "<td width='50%'>";
	include ("gasto_por_posto_grafico_2.php"); // % de OS com defeitos
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td width='50%'>";
	include ("gasto_por_posto_grafico_4.php"); // clientes e revendas
	echo "</td>";
	echo "<td width='50%'>";
	include ("gasto_por_posto_grafico_3.php"); // clientes e revendas PIZZA	
	echo "</td>";
	echo "</tr>";
	echo "</table>";
	/////////////////////////////////////////////////////////////////
	
	echo "<p>";
	
	#---------------- 10 Maiores postos em Valores Nominais ------------
	
	echo "<table width='700' >";
	echo "<tr class='pesquisa'><td colspan='7'>10 mayores servicios em valores nominales</td></tr>";
	echo "<tr class='menu_top'>";
	echo "<td>SEVICIO</td>";
	echo "<td>NOMBRE</td>";
	echo "<td>PROVINCIA</td>";
	echo "<td>CANTIDAD</td>";
	echo "<td>MO</td>";
	echo "<td>PIEZAS</td>";
	echo "<td>TOTAL</td>";
	echo "</tr>";
	
	$sql = "SELECT  maiores.*                     ,
					tbl_posto.nome                ,
					tbl_posto.estado              ,
					tbl_posto_fabrica.codigo_posto
			FROM (
					SELECT * FROM (
						SELECT  tbl_os.posto                                                                                          ,
								CASE WHEN SUM   (tbl_os.mao_de_obra) IS NULL THEN 0 ELSE SUM   (tbl_os.mao_de_obra) END AS mao_de_obra,
								CASE WHEN SUM   (tbl_os.custo_peca)  IS NULL THEN 0 ELSE SUM   (tbl_os.custo_peca)  END AS pecas      ,
								CASE WHEN COUNT (tbl_os.os)          IS NULL THEN 0 ELSE COUNT (tbl_os.os)          END AS qtde
						FROM    tbl_os
						JOIN    tbl_produto    ON tbl_produto.produto = tbl_os.produto ";
	if (strlen($linha) > 0) $sql .= " JOIN tbl_linha ON tbl_linha.linha      = tbl_produto.linha ";
	$sql .= "			JOIN    tbl_os_extra      ON tbl_os_extra.os         = tbl_os.os
						JOIN    tbl_extrato       ON tbl_extrato.extrato     = tbl_os_extra.extrato
						JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto
						JOIN    tbl_posto         ON tbl_posto.posto         = tbl_os.posto
						WHERE   tbl_extrato.aprovado BETWEEN '$data_inicial' AND '$data_final'
						AND     tbl_os.fabrica            = $login_fabrica
						AND     tbl_posto_fabrica.fabrica = $login_fabrica 
						AND     tbl_posto.pais            = '$login_pais' ";
	if (strlen($linha) > 0) $sql .= "AND tbl_linha.linha = $linha ";
	$sql .= "		GROUP BY tbl_os.posto
					) AS x ORDER BY (x.mao_de_obra + x.pecas) DESC LIMIT 10
				) maiores
			JOIN tbl_posto         ON maiores.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica ON maiores.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica  = $login_fabrica;";
	$res = pg_exec ($con,$sql);
	
	$total_mao_de_obra = 0 ;
	$total_pecas = 0 ;
	$total_qtde = 0 ;
	
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$cor = "#F7F5F0"; 
		if ($i % 2 == 0) 
		{
			$cor = '#F1F4FA';
		}
		
		echo "<tr class='table_line'  style='background-color: $cor;'>";
		
		echo "<td align='left'>";
		echo pg_result ($res,$i,codigo_posto);
		echo "</td>";
		
		echo "<td align='left'>";
		echo pg_result ($res,$i,nome);
		echo "</td>";
		
		echo "<td align='center'>";
		echo pg_result ($res,$i,estado);
		echo "</td>";
		
		echo "<td align='right'> ";
		$qtde = pg_result ($res,$i,qtde);
		echo $qtde;
		echo "</td>";
		
		echo "<td align='right'>";
		$mao_de_obra = pg_result ($res,$i,mao_de_obra);
		echo number_format ($mao_de_obra,2,",",".");
		echo "</td>";
		
		echo "<td align='right'>";
		$pecas = pg_result ($res,$i,pecas);
		echo number_format ($pecas,2,",",".");
		echo "</td>";
		
		echo "<td align='right'>";
		$total = $mao_de_obra + $pecas ;
		echo number_format ($total,2,",",".");
		echo "</td>";
		
		echo "</tr>";
		
		$total_mao_de_obra += pg_result ($res,$i,mao_de_obra) ;
		$total_pecas       += pg_result ($res,$i,pecas) ;
		$total_qtde        += pg_result ($res,$i,qtde) ;
	}
	
	$total = $total_mao_de_obra + $total_pecas ;
	
	echo "<tr class='menu_top'>";
	echo "<td align='rigth' colspan='3'>";
	echo "&nbsp;&nbsp;PORCENTAJE: ";
	if ($total_geral > 0 ) $perc = $total / $total_geral * 100 ;
	echo number_format ($perc,0) . "% do total";
	echo "</td>";
	
	echo "<td align='right'>";
	echo $total_qtde;
	echo "</td>";
	
	echo "<td align='right'>";
	echo number_format ($total_mao_de_obra,2,",",".");
	echo "</td>";
	
	echo "<td align='right'>";
	echo number_format ($total_pecas,2,",",".");
	echo "</td>";
	
	echo "<td align='right'>";
	echo number_format ($total,2,",",".");
	echo "</td>";
	echo "</tr>";
	
	echo "</table>";
	echo "<p>";	
	
	flush();
	
	#-------------------- Acima da Media + Desvio ------------------------
	
	echo "<table width='700' >";
	echo "<tr class='pesquisa'><td colspan='9'>Servicios com costos sobre promedio (". number_format($gasto_medio,2,",",".") .") + línea de desvio estándar (". number_format($desvio_geral,2,",",".") .")</td></tr>";
	echo "<tr class='menu_top'>";
	echo "<td>SERVICIO</td>";
	echo "<td>NOMBRE</td>";
	echo "<td>PROVINCIA</td>";
	echo "<td>CANTIDAD</td>";
	echo "<td>MO</td>";
	echo "<td>PIEZAS</td>";
	echo "<td>TOTAL</td>";
	echo "<td>MÉDIA</td>";
	echo "<td>ARRIBA</td>";
	echo "</tr>";
	
	$xgasto_medio  = str_replace(",",".",$gasto_medio);
	$xdesvio_geral = str_replace(",",".",$desvio_geral);
		
	$sql = "SELECT  maiores.*       ,
					tbl_posto.nome  ,
					tbl_posto.estado,
					tbl_posto_fabrica.codigo_posto
			FROM (
					SELECT * FROM (
						SELECT  tbl_os.posto                                                                                          ,
								CASE WHEN SUM   (tbl_os.mao_de_obra) IS NULL THEN 0 ELSE SUM   (tbl_os.mao_de_obra) END AS mao_de_obra,
								CASE WHEN SUM   (tbl_os.custo_peca)  IS NULL THEN 0 ELSE SUM   (tbl_os.custo_peca)  END AS pecas      ,
								CASE WHEN COUNT (tbl_os.os)          IS NULL THEN 0 ELSE COUNT (tbl_os.os)          END AS qtde       ,
								AVG (CASE WHEN tbl_os.mao_de_obra IS NULL OR tbl_os.custo_peca IS NULL THEN 0 ELSE tbl_os.mao_de_obra + tbl_os.custo_peca END) AS media_mobra_peca
						FROM    tbl_os
						JOIN    tbl_produto    ON tbl_produto.produto = tbl_os.produto ";
	if (strlen($linha) > 0) $sql .= "JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha ";
	$sql .= "			JOIN    tbl_os_extra   ON tbl_os_extra.os     = tbl_os.os
						JOIN    tbl_extrato    ON tbl_extrato.extrato = tbl_os_extra.extrato
						WHERE   tbl_extrato.aprovado BETWEEN '$data_inicial' AND '$data_final'
						AND     tbl_os.fabrica  = $login_fabrica ";
	if (strlen($linha) > 0) $sql .= "AND tbl_linha.linha = $linha ";
	$sql .= "			GROUP BY tbl_os.posto
						HAVING   AVG (CASE WHEN tbl_os.mao_de_obra IS NULL OR tbl_os.custo_peca IS NULL THEN 0 ELSE tbl_os.mao_de_obra + tbl_os.custo_peca END) > ($xgasto_medio + $xdesvio_geral)
						ORDER BY AVG (CASE WHEN tbl_os.mao_de_obra IS NULL OR tbl_os.custo_peca IS NULL THEN 0 ELSE tbl_os.mao_de_obra + tbl_os.custo_peca END) DESC
					) AS x
					ORDER BY (x.media_mobra_peca) DESC
			) maiores
			JOIN tbl_posto         ON maiores.posto = tbl_posto.posto
			JOIN tbl_posto_fabrica ON maiores.posto = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica  = $login_fabrica
			AND  tbl_posto.pais = '$login_pais';";
	$res = pg_exec ($con,$sql);

	$total_mao_de_obra = 0 ;
	$total_pecas = 0 ;
	$total_qtde = 0 ;
	$total_perc_acima   = 0;
	
	$res_gastomedio_desviogeral = ($gasto_medio + $desvio_geral);
	
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$codigo_posto      = pg_result ($res,$i,codigo_posto);
		$nome              = pg_result ($res,$i,nome);
		$estado            = pg_result ($res,$i,estado);
		$qtde              = pg_result ($res,$i,qtde);
		$mao_de_obra       = pg_result ($res,$i,mao_de_obra);
		$pecas             = pg_result ($res,$i,pecas);
		$media_mobra_peca  = pg_result ($res,$i,media_mobra_peca);
		$total             = $mao_de_obra + $pecas ;
		
		$res_mo_qtde    = ($total / $qtde);
		
		//$perc_acima     = ($res_mo_qtde / $res_gastomedio_desviogeral * 100) - 100;
		$perc_acima     = 100 - ($res_gastomedio_desviogeral / $media_mobra_peca * 100);
		$perc_acima     = number_format ($perc_acima,1,",",".");
		
		$cor = "#F7F5F0"; 
		if ($i % 2 == 0) 
		{
			$cor = '#F1F4FA';
		}
		
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
		echo $qtde;
		echo "</td>";
		
		echo "<td align='right'>";
		echo number_format ($mao_de_obra,2,",",".");
		echo "</td>";
		
		echo "<td align='right'>";
		echo number_format ($pecas,2,",",".");
		echo "</td>";
		
		echo "<td align='right'>";
		echo number_format ($total,2,",",".");
		echo "</td>";
		
		echo "<td align='right'>";
		echo number_format($media_mobra_peca,2,",",".");
		echo "</td>";
		
		echo "<td align='right'>";
		echo $perc_acima ."%";
		echo "</td>";
		
		echo "</tr>";
		
		$total_mao_de_obra += pg_result ($res,$i,mao_de_obra) ;
		$total_pecas       += pg_result ($res,$i,pecas) ;
		$total_qtde        += pg_result ($res,$i,qtde) ;
	}
	
	$total = $total_mao_de_obra + $total_pecas ;
	
	echo "<tr class='menu_top'>";
	echo "<td align='rigth' colspan='3'>";
	echo "&nbsp;&nbsp;PORCENTAJE: ";
	if ($total_geral > 0 ) $perc = $total / $total_geral * 100 ;
	echo number_format ($perc,0) . "% do total";
	echo "</td>";
	
	echo "<td align='right'>";
	echo $total_qtde;
	echo "</td>";
	
	echo "<td align='right'>";
	echo number_format ($total_mao_de_obra,2,",",".");
	echo "</td>";
	
	echo "<td align='right'>";
	echo number_format ($total_pecas,2,",",".");
	echo "</td>";
	
	echo "<td align='right'>";
	echo number_format ($total,2,",",".");
	echo "</td>";
	
	echo "<td align='right'>&nbsp;</td>";
	
	echo "<td align='right'>&nbsp;</td>";
	
	echo "</tr>";
	
	echo "</table>";
	echo "<p>";
	
	flush();
	
	#---------------- 10 Maiores produtos em Valores Nominais ------------
	
	echo "<table width='700' >";
	echo "<tr class='pesquisa'><td colspan='5'>10 MAYORES PRODUCTOS EN VALORES NOMINALES</td></tr>";
	echo "<tr class='menu_top'>";
	echo "<td>PRODUCTO</td>";
	echo "<td>CTD</td>";
	echo "<td>MO</td>";
	echo "<td>PIEZAS</td>";
	echo "<td>TOTAL</td>";
	echo "</tr>";
	
	$sql = "SELECT  maiores.*             ,
					tbl_produto.referencia,
					tbl_produto.descricao
			FROM (
					SELECT * FROM (
						SELECT  tbl_os.produto                                                                                        ,
								CASE WHEN SUM   (tbl_os.mao_de_obra) IS NULL THEN 0 ELSE SUM   (tbl_os.mao_de_obra) END AS mao_de_obra,
								CASE WHEN SUM   (tbl_os.custo_peca)  IS NULL THEN 0 ELSE SUM   (tbl_os.custo_peca)  END AS pecas      ,
								CASE WHEN COUNT (tbl_os.os)          IS NULL THEN 0 ELSE COUNT (tbl_os.os)          END AS qtde
						FROM    tbl_os
						JOIN    tbl_produto    ON tbl_produto.produto = tbl_os.produto ";
	if (strlen($linha) > 0) $sql .= "JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha ";
	$sql .= "			JOIN    tbl_os_extra   ON tbl_os_extra.os     = tbl_os.os
						JOIN    tbl_extrato    ON tbl_extrato.extrato = tbl_os_extra.extrato
						JOIN    tbl_posto      ON tbl_posto.posto     = tbl_os.posto
						WHERE   tbl_extrato.aprovado BETWEEN '$data_inicial' AND '$data_final'
						AND     tbl_os.fabrica  = $login_fabrica 
						AND     tbl_posto.pais  = '$login_pais'";
	if (strlen($linha) > 0) $sql .= "AND tbl_linha.linha = $linha ";
	$sql .= "		GROUP BY tbl_os.produto
					) AS x ORDER BY (x.mao_de_obra + x.pecas) DESC LIMIT 10
				) maiores
			JOIN    tbl_produto ON maiores.produto = tbl_produto.produto
			JOIN    tbl_linha   ON tbl_linha.linha = tbl_produto.linha
			WHERE   tbl_linha.fabrica = $login_fabrica;";
	#if ($ip == "201.0.9.216") echo $sql;
	$res = pg_exec ($con,$sql);
	
	$total_mao_de_obra = 0 ;
	$total_pecas = 0 ;
	$total_qtde = 0 ;
	
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$cor = "#F7F5F0"; 
		if ($i % 2 == 0) 
		{
			$cor = '#F1F4FA';
		}
		
		$produto_referencia = pg_result ($res,$i,referencia);
		$produto_descricao   =  pg_result ($res,$i,descricao);
			$sql_idioma = "SELECT * FROM tbl_produto_idioma JOIN tbl_produto USING(produto) WHERE referencia = '$produto_referencia' AND upper(idioma) = 'ES'";
		
			$res_idioma = @pg_exec($con,$sql_idioma);
			if (@pg_numrows($res_idioma) >0) {
				$produto_descricao  = trim(@pg_result($res_idioma,0,descricao));
			}

		echo "<tr class='table_line'  style='background-color: $cor;'>";
		
		echo "<td align='left'>";
		echo $produto_referencia;
		echo " - ";
		echo $produto_descricao;
		echo "</td>";
		
		echo "<td align='right'>";
		$qtde = pg_result ($res,$i,qtde);
		echo $qtde;
		echo "</td>";
		
		echo "<td align='right'>";
		$mao_de_obra = pg_result ($res,$i,mao_de_obra);
		echo number_format ($mao_de_obra,2,",",".");
		echo "</td>";
		
		echo "<td align='right'>";
		$pecas = pg_result ($res,$i,pecas);
		echo number_format ($pecas,2,",",".");
		echo "</td>";
		
		echo "<td align='right'>";
		$total = $mao_de_obra + $pecas ;
		echo number_format ($total,2,",",".");
		echo "</td>";
		
		echo "</tr>";
		
		$total_mao_de_obra += pg_result ($res,$i,mao_de_obra) ;
		$total_pecas       += pg_result ($res,$i,pecas) ;
		$total_qtde        += pg_result ($res,$i,qtde) ;
	}
	
	$total = $total_mao_de_obra + $total_pecas ;
	
	echo "<tr class='menu_top'>";
	echo "<td align='rigth' colspan='1'>";
	echo "&nbsp;&nbsp;PORCENTAJE: ";
	if ($total_geral > 0 ) $perc = $total / $total_geral * 100 ;
	echo number_format ($perc,0) . "% do total";
	echo "</td>";
	
	echo "<td align='right'>";
	echo $total_qtde;
	echo "</td>";
	
	echo "<td align='right'>";
	echo number_format ($total_mao_de_obra,2,",",".");
	echo "</td>";
	
	echo "<td align='right'>";
	echo number_format ($total_pecas,2,",",".");
	echo "</td>";
	
	echo "<td align='right'>";
	echo number_format ($total,2,",",".");
	echo "</td>";
	echo "</tr>";
	
	echo "</table>";
	echo "<p>";	
	
	flush();
	
	#---------------- 20 Maiores peças em Valores Nominais ------------
	
	echo "<table width='700' >";
	echo "<tr class='pesquisa'><td colspan='5'>20 Mayores piezas en valores nominales</td></tr>";
	echo "<tr class='menu_top'>";
	echo "<td>PIEZA</td>";
	echo "<td>CTD</td>";
	echo "<td>MO</td>";
	echo "<td>PIEZAS</td>";
	echo "<td>TOTAL</td>";
	echo "</tr>";
	
	$sql = "SELECT  maiores.*          ,
					tbl_peca.referencia,
					tbl_peca.descricao
			FROM (
					SELECT * FROM (
						SELECT  tbl_os_item.peca                        ,
								CASE WHEN  SUM   (tbl_os.mao_de_obra) IS NULL THEN 0 ELSE SUM   (tbl_os.mao_de_obra) END AS mao_de_obra,
								CASE WHEN  SUM   (tbl_os.custo_peca)  IS NULL THEN 0 ELSE SUM   (tbl_os.custo_peca)  END AS pecas      ,
								CASE WHEN  COUNT (tbl_os.os)          IS NULL THEN 0 ELSE COUNT (tbl_os.os)          END AS qtde
						FROM    tbl_os
						JOIN    tbl_os_extra   ON tbl_os_extra.os           = tbl_os.os
						JOIN    tbl_extrato    ON tbl_extrato.extrato       = tbl_os_extra.extrato
						JOIN    tbl_os_produto ON tbl_os.os                 = tbl_os_produto.os
						JOIN    tbl_os_item    ON tbl_os_produto.os_produto = tbl_os_item.os_produto
						JOIN    tbl_produto    ON tbl_produto.produto       = tbl_os_produto.produto 
						JOIN    tbl_posto      ON tbl_posto.posto           = tbl_os.posto ";
	if (strlen($linha) > 0) $sql .= "JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha ";
	$sql .= "		WHERE   tbl_extrato.aprovado BETWEEN '$data_inicial' AND '$data_final'
					AND     tbl_os.fabrica = $login_fabrica 
					AND     tbl_posto.pais = '$login_pais'";
	if (strlen($linha) > 0) $sql .= "AND tbl_linha.linha = $linha ";
	$sql .= "		GROUP BY tbl_os_item.peca
					) AS x ORDER BY (x.mao_de_obra + x.pecas) DESC LIMIT 20
			) maiores
			JOIN tbl_peca ON maiores.peca = tbl_peca.peca AND tbl_peca.fabrica = $login_fabrica;";
	$res = pg_exec ($con,$sql);
	
	$total_mao_de_obra = 0 ;
	$total_pecas = 0 ;
	$total_qtde = 0 ;
	
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$cor = "#F7F5F0"; 
		if ($i % 2 == 0) 
		{
			$cor = '#F1F4FA';
		}
		
		$peca_referencia = pg_result ($res,$i,referencia);
		$peca_descricao  =  pg_result ($res,$i,descricao);
		$sql_idioma = "SELECT tbl_peca_idioma.* FROM tbl_peca_idioma JOIN tbl_peca USING(peca) WHERE referencia = '$peca_referencia' AND upper(idioma) = 'ES'";

		$res_idioma = @pg_exec($con,$sql_idioma);
		if (@pg_numrows($res_idioma) >0) {
			$peca_descricao  = trim(@pg_result($res_idioma,0,descricao));
		}

		echo "<tr class='table_line'  style='background-color: $cor;'>";
		
		echo "<td align='left'>";
		echo pg_result ($res,$i,referencia);
		echo " - ";
		echo pg_result ($res,$i,descricao);	
		echo "</td>";
		
		echo "<td align='right'>";
		$qtde = pg_result ($res,$i,qtde);
		echo $qtde;
		echo "</td>";
		
		echo "<td align='right'>";
		$mao_de_obra = pg_result ($res,$i,mao_de_obra);
		echo number_format ($mao_de_obra,2,",",".");
		echo "</td>";
		
		echo "<td align='right'>";
		$pecas = pg_result ($res,$i,pecas);
		echo number_format ($pecas,2,",",".");
		echo "</td>";
		
		echo "<td align='right'>";
		$total = $mao_de_obra + $pecas ;
		echo number_format ($total,2,",",".");
		echo "</td>";
		
		echo "</tr>";
		
		$total_mao_de_obra += pg_result ($res,$i,mao_de_obra) ;
		$total_pecas       += pg_result ($res,$i,pecas) ;
		$total_qtde        += pg_result ($res,$i,qtde) ;
	}
	
	$total = $total_mao_de_obra + $total_pecas ;
	
	echo "<tr class='menu_top'>";
	echo "<td align='rigth' colspan='1'>";
	echo "&nbsp;&nbsp;PORCENTAJE: ";
	if ($total_geral > 0 ) $perc = $total / $total_geral * 100 ;
	echo number_format ($perc,0) . "% do total";
	echo "</td>";
	
	echo "<td align='right'>";
	echo $total_qtde;
	echo "</td>";
	
	echo "<td align='right'>";
	echo number_format ($total_mao_de_obra,2,",",".");
	echo "</td>";
	
	echo "<td align='right'>";
	echo number_format ($total_pecas,2,",",".");
	echo "</td>";
	
	echo "<td align='right'>";
	echo number_format ($total,2,",",".");
	echo "</td>";
	echo "</tr>";
	
	echo "</table>";
	echo "<p>";
	
	flush();

#----------------------- OS de Consumidor x OS Loja --------------------------



#----------------------- OS sem Telefone --------------------------

	echo "<table width='700' >";
	echo "<tr class='pesquisa'><td colspan='5'>20 Servicios que no ponen teléfono del usuario en la OS</td></tr>";
	echo "<tr class='menu_top'>";
	echo "<td width='10%'>SERVICIO</td>";
	echo "<td width='50%'>NOMBRE</td>";
	echo "<td width='10%'>PROVINCIA</td>";
	echo "<td width='15%'>CTD OS</td>";
	echo "<td width='15%'>CTD SI TELÉFONO</td>";
	echo "</tr>";
	
	$sql = "SELECT  tbl_posto.nome                                                                                 ,
					tbl_posto.estado                                                                               ,
					tbl_posto_fabrica.codigo_posto                                                                 ,
					COUNT(CASE WHEN length (trim (consumidor_fone)) > 0 THEN 1 ELSE NULL      END) AS qtde_com_fone,
					COUNT(CASE WHEN tbl_os.os IS NULL                   THEN 0 ELSE tbl_os.os END) AS qtde_os
			FROM    tbl_posto
			JOIN    tbl_os            ON tbl_os.posto        = tbl_posto.posto
			JOIN    tbl_os_extra      ON tbl_os_extra.os     = tbl_os.os
			JOIN    tbl_extrato       ON tbl_extrato.extrato = tbl_os_extra.extrato
			JOIN    tbl_posto_fabrica ON tbl_posto.posto     = tbl_posto_fabrica.posto AND tbl_posto_fabrica.fabrica  = $login_fabrica
			WHERE   tbl_extrato.aprovado BETWEEN '$data_inicial' AND '$data_final'
			AND     tbl_os.fabrica            = $login_fabrica
			AND     tbl_posto_fabrica.fabrica = $login_fabrica
			AND     tbl_posto.pais            = '$login_pais'
			GROUP BY tbl_posto.nome, tbl_posto.estado, tbl_posto_fabrica.codigo_posto
			ORDER BY    COUNT(CASE WHEN tbl_os.os IS NULL THEN 0 ELSE tbl_os.os END) - COUNT(CASE WHEN length (trim (consumidor_fone)) > 0 THEN 1 ELSE NULL END ) DESC,
						COUNT(CASE WHEN tbl_os.os IS NULL THEN 0 ELSE tbl_os.os END) DESC,
						tbl_posto.nome LIMIT 20;";
	$res = pg_exec ($con,$sql);
	
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$cor = "#F7F5F0"; 
		if ($i % 2 == 0) 
		{
			$cor = '#F1F4FA';
		}
		
		echo "<tr class='table_line'  style='background-color: $cor;'>";
		

		echo "<td align='left'>";
		echo pg_result($res,$i,codigo_posto);
		echo "</td>";
		
		echo "<td align='left'>";
		echo pg_result ($res,$i,nome);
		echo "</td>";
		
		echo "<td>";
		echo pg_result ($res,$i,estado);
		echo "</td>";
		
		echo "<td align='right'>";
		echo pg_result ($res,$i,qtde_os);
		echo "</td>";
		
		echo "<td align='right'>";
		echo pg_result ($res,$i,qtde_os) - pg_result ($res,$i,qtde_com_fone);
		echo "</td>";
		
		echo "</tr>";
	}
	echo "</table>";
	flush();
	
	#echo "<table width='700' >";
	#echo "<tr><td>";
	//////////////////////////////////////////////////
	// grafico de postos que não colocam Telefone 
	//////////////////////////////////////////////////
	#include ("gasto_por_posto_grafico_5.php"); // postos que não colocam Telefone 
	//////////////////////////////////////////////////
	#echo "</td></tr>";
	#echo "</table>";
	
	echo "<p>";
	
	#---------------- Gasto por Estado ------------
	
	echo "<table width='700' >";
	echo "<tr class='pesquisa'><td colspan='5'>COSTO POR PROVINCIA</td></tr>";
	echo "<tr class='menu_top'>";
	echo "<td>PROVINCIA</td>";
	echo "<td>CTD</td>";
	echo "<td>MO</td>";
	echo "<td>PIEZAS</td>";
	echo "<td>TOTAL</td>";
	echo "</tr>";
	
	$sql = "SELECT * FROM (
				SELECT  tbl_posto.estado                                                                                     ,
						CASE WHEN SUM   (tbl_os.mao_de_obra) IS NULL THEN 0 ELSE SUM (tbl_os.mao_de_obra)  END AS mao_de_obra,
						CASE WHEN SUM   (tbl_os.custo_peca)  IS NULL THEN 0 ELSE SUM   (tbl_os.custo_peca) END AS pecas      ,
						CASE WHEN COUNT (tbl_os.os)          IS NULL THEN 0 ELSE COUNT (tbl_os.os)         END AS qtde
				FROM    tbl_os
				JOIN    tbl_produto          ON tbl_produto.produto       = tbl_os.produto ";
	if (strlen($linha) > 0) $sql .= "JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha ";
	$sql .=	"	JOIN    tbl_os_extra         ON tbl_os_extra.os           = tbl_os.os
				JOIN    tbl_extrato          ON tbl_extrato.extrato       = tbl_os_extra.extrato
				JOIN    tbl_posto            ON tbl_os.posto              = tbl_posto.posto
				JOIN    tbl_posto_fabrica    ON tbl_posto_fabrica.posto   = tbl_posto.posto
											AND tbl_posto_fabrica.fabrica = $login_fabrica
				WHERE   tbl_extrato.aprovado BETWEEN '$data_inicial' AND '$data_final'
				AND     tbl_os.fabrica            = $login_fabrica
				AND     tbl_posto_fabrica.fabrica = $login_fabrica 
				AND     tbl_posto.pais            = '$login_pais'";
	if (strlen($linha) > 0) $sql .= "AND tbl_linha.linha = $linha ";
	$sql .= "GROUP BY tbl_posto.estado
			) AS x
			ORDER BY (x.mao_de_obra + x.pecas) DESC;";
	$res = pg_exec ($con,$sql);
	
	$total_mao_de_obra = 0 ;
	$total_pecas = 0 ;
	$total_qtde = 0 ;
	
	for ($i = 0 ; $i < pg_numrows ($res) ; $i++) {
		$cor = "#F7F5F0"; 
		if ($i % 2 == 0) 
		{
			$cor = '#F1F4FA';
		}
		
		echo "<tr class='table_line'  style='background-color: $cor;'>";
		

		echo "<td align='center'>" ;
		echo pg_result ($res,$i,estado) ;
		echo "</td>";
		
		echo "<td align='right'>";
		$qtde = pg_result ($res,$i,qtde);
		echo $qtde;
		echo "</td>";
		
		echo "<td align='right'>";
		$mao_de_obra = pg_result ($res,$i,mao_de_obra);
		echo number_format ($mao_de_obra,2,",",".");
		echo "</td>";
		
		echo "<td align='right'>";
		$pecas = pg_result ($res,$i,pecas);
		echo number_format ($pecas,2,",",".");
		echo "</td>";
		
		echo "<td align='right'>";
		$total = $mao_de_obra + $pecas ;
		echo number_format ($total,2,",",".");
		echo "</td>";
		
		echo "</tr>";
		
		$total_mao_de_obra += pg_result ($res,$i,mao_de_obra) ;
		$total_pecas       += pg_result ($res,$i,pecas) ;
		$total_qtde        += pg_result ($res,$i,qtde) ;
	}
	
	$total = $total_mao_de_obra + $total_pecas ;
	
	echo "</table>";
	echo "<p>";
	
	flush();

	#echo "<table width='700' cellpadding=2 cellspacing=0 border=0>";
	#echo "<tr class='pesquisa'><td colspan='5'>Serviços Realizados</td></tr>";
	#echo "</table>";
	//////////////////////////////////////////////////
	// grafico de serviços realizados
	//////////////////////////////////////////////////
	#include ("servico_realizado_grafico.php"); // postos que não colocam Telefone 
	//////////////////////////////////////////////////
}

echo "<br><br>";

include "rodape.php"; 

?>
