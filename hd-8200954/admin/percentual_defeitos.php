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

	$sql = "SELECT fn_dias_mes('$data',0)";
	$resX = pg_exec($con,$sql);
	$data_inicial = pg_result($resX,0,0);

	$sql = "SELECT fn_dias_mes('$data',1)";
	$resX = pg_exec($con,$sql);
	$data_final = pg_result($resX,0,0);

	$sql = "SELECT fn_dias_mes('$data_ano',0)";
	$resX = pg_exec($con,$sql);
	$data_inicial_ano = pg_result($resX,0,0);

	$sql = "SELECT	tbl_produto.produto   ,
					tbl_produto.descricao ,
					tbl_produto.referencia,
					COUNT(*) AS conta     
			FROM	tbl_os
			JOIN	tbl_os_produto ON tbl_os_produto.os    = tbl_os.os
			JOIN	tbl_produto    ON tbl_produto.produto  = tbl_os_produto.produto
			JOIN	tbl_linha      USING(linha)
			WHERE	tbl_linha.fabrica = $login_fabrica
			AND		tbl_os.data_abertura BETWEEN '$data_inicial' AND '$data_final'
			GROUP BY tbl_produto.descricao,
					tbl_produto.referencia,
					tbl_produto.produto";
	$res = pg_exec($con,$sql);

	if (pg_numrows($res) == 0) {
		echo "<table width='700' align='center' border='0' cellspacing='2' cellpadding='2'>";
		echo "<tr class='table_line'>";
		echo "<td align='center'><font size='2'>Não existem defeitos por produtos neste período</font></td>";
		echo "</tr>";
		echo "</table>";
	}else{
		echo "<table align='center' border='0' cellspacing='1' cellpadding='2'>";

		### monta linha de nome dos produtos
		echo "<tr class='menu_top'>\n";
		echo "<td>#</td>";
		for ($i=0; $i<pg_numrows($res); $i++){
			echo "<td><b><acronym title='".pg_result($res,$i,descricao)."'>".pg_result($res,$i,referencia)."</acronym></b></td>\n";

			$sql = "SELECT	COUNT(*) AS contaano
					FROM	tbl_os
					JOIN	tbl_os_produto ON tbl_os_produto.os    = tbl_os.os
					JOIN	tbl_produto    ON tbl_produto.produto  = tbl_os_produto.produto
					JOIN	tbl_linha      USING(linha)
					WHERE	tbl_linha.fabrica = $login_fabrica
					AND		tbl_produto.produto = ".pg_result($res,$i,0)."
					AND		tbl_os.data_abertura BETWEEN '$data_inicial_ano' AND '$data_final'
					GROUP BY tbl_produto.descricao,
							tbl_produto.referencia";
			$res2 = pg_exec($con,$sql);
			$contaano[$i] = pg_result($res2,0,0);
		}
		echo "</tr>\n";

		### MONTA LINHA EM BRANCO, PQ GARANTIA
		echo "<tr class='table_line' BGCOLOR='#F7F7F7'>\n";
		echo "<td class='menu_top'>PQ GARANTIA</td>";
		for ($i=0; $i<pg_numrows($res); $i++){
			echo "<td>&nbsp;</td>\n";
		}
		echo "</tr>\n";

		### MONTA LINHA COM TOTAL DE OS DO MES
		echo "<tr class='table_line' bgcolor='#F1F4FA'>\n";
		echo "<td class='menu_top'>ATEND. MÊS</td>";
		for ($i=0; $i<pg_numrows($res); $i++){
			echo "<td align='right' style='padding-right:5px;'>".pg_result($res,$i,conta)."</td>\n";
		}
		echo "</tr>\n";

		### MONTA LINHA COM TOTAL DE OS DO ANO
		echo "<tr class='table_line' BGCOLOR='#F7F7F7'>\n";
		echo "<td class='menu_top'>ATEND. ANO</td>";
		for ($i=0; $i<count($contaano); $i++){
			echo "<td align='right' style='padding-right:5px;'>".$contaano[$i]."</td>\n";
		}
		echo "</tr>\n";

		### % NO MÊS
		echo "<tr class='table_line' bgcolor='#F1F4FA'>\n";
		echo "<td class='menu_top'>% NO MÊS</td>";
		for ($i=0; $i<pg_numrows($res); $i++){
			echo "<td>&nbsp;</td>\n";
		}
		echo "</tr>\n";

		### % NO ANO
		echo "<tr class='table_line'BGCOLOR='#F7F7F7'>\n";
		echo "<td class='menu_top' bgcolor='#F1F4FA'>% NO ANO</td>";
		for ($i=0; $i<pg_numrows($res); $i++){
			echo "<td>&nbsp;</td>\n";
		}
		echo "</tr>\n";

		### % MÉDIA
		echo "<tr class='table_line' bgcolor='#F1F4FA'>\n";
		echo "<td class='menu_top'>% MÉDIA</td>";
		for ($i=0; $i<pg_numrows($res); $i++){
			echo "<td>&nbsp;</td>\n";
		}
		echo "</tr>\n";

		echo "</table>";
	}

	echo "<br><br>";
}

include "rodape.php"; 

?>