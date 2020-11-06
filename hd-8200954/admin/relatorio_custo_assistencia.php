<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$msg_erro = "";

$layout_menu = "gerencia";
$title = "Relatório de Percentual dos Defeitos por Produtos";

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
	<td align='center'><font size='2'>Selecione o MÊS</font></td>
	<td align='center'><font size='2'>Selecione o ANO</font></td>
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
	<td><img src='imagens_admin/btn_confirmar.gif' border=0 onclick='javascript: submit();' style='cursor:pointer'></td>
</tr>
</form>
</table>

<br>

<?
if (strlen($mes) > 0 AND strlen($ano) > 0){

	$data_ano = "$ano-01-01";
	$data     = "$ano-$mes-01";
	
	$sql = "SELECT to_char(('$data'::date - ((2 || ' year')::interval))::date,'YYYY');";
	$resX = pg_exec($con,$sql);
	$ano_2 = pg_result($resX,0,0);
	
	if (strlen($ano_2) > 0) {
		$ano_2_inicio = "$ano_2-01-01 00:00:00";
		$ano_2_final  = "$ano_2-12-31 23:59:59";
	}
	
	$sql = "SELECT to_char(('$data'::date - ((1 || ' year')::interval))::date,'YYYY');";
	$resX = pg_exec($con,$sql);
	$ano_1 = pg_result($resX,0,0);
	
	if (strlen($ano_1) > 0) {
		$ano_1_inicio = "$ano_1-01-01 00:00:00";
		$ano_1_final  = "$ano_1-12-31 23:59:59";
	}
	
	$ano_0_inicio = "$ano-01-01 00:00:00";

	$sql = "SELECT fn_dias_mes((('$data'::date - ((1 || ' months')::interval))::date),1)";
	$resX = pg_exec($con,$sql);
	$ano_0_final = pg_result($resX,0,0);

	$ano_inicio = "$data 00:00:00";
	
	$sql = "SELECT fn_dias_mes('$data',1)";
	$resX = pg_exec($con,$sql);
	$ano_final = pg_result($resX,0,0);
	
	$sql = "SELECT
			(
				SELECT count(*)
				FROM   tbl_os
				JOIN   tbl_os_produto ON tbl_os_produto.os   = tbl_os.os
				JOIN   tbl_produto    ON tbl_produto.produto = tbl_os_produto.produto
				JOIN   tbl_linha      ON tbl_linha.linha     = tbl_produto.linha
				WHERE  tbl_linha.fabrica = $login_fabrica
				AND    tbl_os.data_abertura BETWEEN '$ano_2_inicio' AND '$ano_2_final'
			) AS qtde_3,
			(
				SELECT count(*)
				FROM   tbl_os
				JOIN   tbl_os_produto ON tbl_os_produto.os   = tbl_os.os
				JOIN   tbl_produto    ON tbl_produto.produto = tbl_os_produto.produto
				JOIN   tbl_linha      ON tbl_linha.linha     = tbl_produto.linha
				WHERE  tbl_linha.fabrica = $login_fabrica
				AND    tbl_os.data_abertura BETWEEN '$ano_1_inicio' AND '$ano_1_final'
			) AS qtde_2,
			(
				SELECT count(*)
				FROM   tbl_os
				JOIN   tbl_os_produto ON tbl_os_produto.os   = tbl_os.os
				JOIN   tbl_produto    ON tbl_produto.produto = tbl_os_produto.produto
				JOIN   tbl_linha      ON tbl_linha.linha     = tbl_produto.linha
				WHERE  tbl_linha.fabrica = $login_fabrica
				AND    tbl_os.data_abertura BETWEEN '$ano_0_inicio' AND '$ano_0_final'
			) AS qtde_1,
			(
				SELECT count(*)
				FROM   tbl_os
				JOIN   tbl_os_produto ON tbl_os_produto.os   = tbl_os.os
				JOIN   tbl_produto    ON tbl_produto.produto = tbl_os_produto.produto
				JOIN   tbl_linha      ON tbl_linha.linha     = tbl_produto.linha
				WHERE  tbl_linha.fabrica = $login_fabrica
				AND    tbl_os.data_abertura BETWEEN '$ano_inicio' AND '$ano_final'
			) AS qtde_0,
			(
				SELECT to_char(sum(tbl_os.mao_de_obra),999999990.99)
				FROM   tbl_os
				JOIN   tbl_os_produto ON tbl_os_produto.os   = tbl_os.os
				JOIN   tbl_produto    ON tbl_produto.produto = tbl_os_produto.produto
				JOIN   tbl_linha      ON tbl_linha.linha     = tbl_produto.linha
				WHERE  tbl_linha.fabrica = $login_fabrica
				AND    tbl_os.data_abertura BETWEEN '$ano_2_inicio' AND '$ano_2_final'
			) AS custo_at_3,
			(
				SELECT to_char(sum(tbl_os.pecas),999999990.99)
				FROM   tbl_os
				JOIN   tbl_os_produto ON tbl_os_produto.os   = tbl_os.os
				JOIN   tbl_produto    ON tbl_produto.produto = tbl_os_produto.produto
				JOIN   tbl_linha      ON tbl_linha.linha     = tbl_produto.linha
				WHERE  tbl_linha.fabrica = $login_fabrica
				AND    tbl_os.data_abertura BETWEEN '$ano_2_inicio' AND '$ano_2_final'
			) AS custo_peca_3,
			(
				SELECT to_char(sum(tbl_os.mao_de_obra),999999990.99)
				FROM   tbl_os
				JOIN   tbl_os_produto ON tbl_os_produto.os   = tbl_os.os
				JOIN   tbl_produto    ON tbl_produto.produto = tbl_os_produto.produto
				JOIN   tbl_linha      ON tbl_linha.linha     = tbl_produto.linha
				WHERE  tbl_linha.fabrica = $login_fabrica
				AND    tbl_os.data_abertura BETWEEN '$ano_1_inicio' AND '$ano_1_final'
			) AS custo_at_2,
			(
				SELECT to_char(sum(tbl_os.pecas),999999990.99)
				FROM   tbl_os
				JOIN   tbl_os_produto ON tbl_os_produto.os   = tbl_os.os
				JOIN   tbl_produto    ON tbl_produto.produto = tbl_os_produto.produto
				JOIN   tbl_linha      ON tbl_linha.linha     = tbl_produto.linha
				WHERE  tbl_linha.fabrica = $login_fabrica
				AND    tbl_os.data_abertura BETWEEN '$ano_1_inicio' AND '$ano_1_final'
			) AS custo_peca_2,
			(
				SELECT to_char(sum(tbl_os.mao_de_obra),999999990.99)
				FROM   tbl_os
				JOIN   tbl_os_produto ON tbl_os_produto.os   = tbl_os.os
				JOIN   tbl_produto    ON tbl_produto.produto = tbl_os_produto.produto
				JOIN   tbl_linha      ON tbl_linha.linha     = tbl_produto.linha
				WHERE  tbl_linha.fabrica = $login_fabrica
				AND    tbl_os.data_abertura BETWEEN '$ano_0_inicio' AND '$ano_0_final'
			) AS custo_at_1,
			(
				SELECT to_char(sum(tbl_os.pecas),999999990.99)
				FROM   tbl_os
				JOIN   tbl_os_produto ON tbl_os_produto.os   = tbl_os.os
				JOIN   tbl_produto    ON tbl_produto.produto = tbl_os_produto.produto
				JOIN   tbl_linha      ON tbl_linha.linha     = tbl_produto.linha
				WHERE  tbl_linha.fabrica = $login_fabrica
				AND    tbl_os.data_abertura BETWEEN '$ano_0_inicio' AND '$ano_0_final'
			) AS custo_peca_1,
			(
				SELECT to_char(sum(tbl_os.mao_de_obra),999999990.99)
				FROM   tbl_os
				JOIN   tbl_os_produto ON tbl_os_produto.os   = tbl_os.os
				JOIN   tbl_produto    ON tbl_produto.produto = tbl_os_produto.produto
				JOIN   tbl_linha      ON tbl_linha.linha     = tbl_produto.linha
				WHERE  tbl_linha.fabrica = $login_fabrica
				AND    tbl_os.data_abertura BETWEEN '$ano_inicio' AND '$ano_final'
			) AS custo_at_0,
			(
				SELECT to_char(sum(tbl_os.pecas),999999990.99)
				FROM   tbl_os
				JOIN   tbl_os_produto ON tbl_os_produto.os   = tbl_os.os
				JOIN   tbl_produto    ON tbl_produto.produto = tbl_os_produto.produto
				JOIN   tbl_linha      ON tbl_linha.linha     = tbl_produto.linha
				WHERE  tbl_linha.fabrica = $login_fabrica
				AND    tbl_os.data_abertura BETWEEN '$ano_inicio' AND '$ano_final'
			) AS custo_peca_0;";
	$res = pg_exec($con,$sql);
	
	if (pg_numrows($res) == 0) {
		echo "<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>";
		echo "<tr class='table_line'>";
		echo "<td align='center'><font size='2'>Não existem custos neste período</font></td>";
		echo "</tr>";
		echo "</table>";
	}else{
		$qtde_3 = trim(pg_result($res,0,qtde_3));
		$qtde_2 = trim(pg_result($res,0,qtde_2));
		$qtde_1 = trim(pg_result($res,0,qtde_1));
		$qtde_0 = trim(pg_result($res,0,qtde_0));
		
		$custo_at_3 = trim(pg_result($res,0,custo_at_3));
		$custo_at_2 = trim(pg_result($res,0,custo_at_2));
		$custo_at_1 = trim(pg_result($res,0,custo_at_1));
		$custo_at_0 = trim(pg_result($res,0,custo_at_0));
		
		$custo_peca_3 = trim(pg_result($res,0,custo_peca_3));
		$custo_peca_2 = trim(pg_result($res,0,custo_peca_2));
		$custo_peca_1 = trim(pg_result($res,0,custo_peca_1));
		$custo_peca_0 = trim(pg_result($res,0,custo_peca_0));
		
		$soma_custo_3  = $custo_at_3 + $custo_peca_3;
		$soma_custo_2  = $custo_at_2 + $custo_peca_2;
		$soma_custo_1  = $custo_at_1 + $custo_peca_1;
		$soma_custo_0  = $custo_at_0 + $custo_peca_0;
		
		if ($qtde_3 > 0) $custo_qtde_3  = $soma_custo_3 / $qtde_3;
		if ($qtde_2 > 0) $custo_qtde_2  = $soma_custo_2 / $qtde_2;
		if ($qtde_1 > 0) $custo_qtde_1  = $soma_custo_1 / $qtde_1;
		if ($qtde_0 > 0) $custo_qtde_0  = $soma_custo_0 / $qtde_0;
		
		echo "<table width='450' align='center' border='0' cellspacing='1' cellpadding='2'>";
		
		### monta linha de nome dos produtos
		echo "<tr class='menu_top'>\n";
		
		echo "<td>&nbsp;</td>";
		echo "<td>MÉDIA $ano_2</td>";
		echo "<td>MÉDIA $ano_1</td>";
		echo "<td>MÉDIA $ano</td>";
		echo "<td>$mes/$ano</td>";
		
		echo "</tr>";
		echo "<tr>\n";
		
		echo "<td align='left'>Atendimentos</td>";
		echo "<td align='right'>$qtde_3</td>";
		echo "<td align='right'>$qtde_2</td>";
		echo "<td align='right'>$qtde_1</td>";
		echo "<td align='right'>$qtde_0</td>";
		
		echo "</tr>";
		echo "<tr>\n";
		
		echo "<td align='left'>Custo Astec</td>";
		echo "<td align='right'>". number_format($custo_at_3,2,",",".") ."</td>";
		echo "<td align='right'>". number_format($custo_at_2,2,",",".") ."</td>";
		echo "<td align='right'>". number_format($custo_at_1,2,",",".") ."</td>";
		echo "<td align='right'>". number_format($custo_at_0,2,",",".") ."</td>";
		
		echo "</tr>";
		echo "<tr>\n";
		
		echo "<td align='left'>Custo Peças</td>";
		echo "<td align='right'>". number_format($custo_peca_3,2,",",".") ."</td>";
		echo "<td align='right'>". number_format($custo_peca_2,2,",",".") ."</td>";
		echo "<td align='right'>". number_format($custo_peca_1,2,",",".") ."</td>";
		echo "<td align='right'>". number_format($custo_peca_0,2,",",".") ."</td>";
		
		echo "</tr>";
		echo "<tr>\n";
		
		echo "<td align='left'>Custo/Atendimento</td>";
		echo "<td align='right'>". number_format($custo_qtde_3,2,",",".") ."</td>";
		echo "<td align='right'>". number_format($custo_qtde_2,2,",",".") ."</td>";
		echo "<td align='right'>". number_format($custo_qtde_1,2,",",".") ."</td>";
		echo "<td align='right'>". number_format($custo_qtde_0,2,",",".") ."</td>";
		
		echo "</tr>";
		echo "</table>\n";
	}
}

include "rodape.php"; 

?>