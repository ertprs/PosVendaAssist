<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$msg_erro = "";

$layout_menu = "os";
$title = "Emissão de NF para devolução de peças";

include "cabecalho.php";

//=================TABELA DOS 3 MESES ANTERIORES=====================================
?>

<style type="text/css">
.table_top {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}
.table_line {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: normal;
	border: 0px solid;
}
</style>

<BR>
<table width = '350' align = 'center' cellpadding='5' cellspacing='0' border='1' >
<form name='frm_relatorio' action='<? echo $PHP_SELF; ?>' method='post' >
<tr>
	<td colspan='5' class='table_top'><center><B><?php echo $title; ?></B></center></td>
</tr>
<tr>
	<td align='right' class='table_line'> Mês: </td>
	<td class='table_line'>
		<select name='mes' size='1'>
			<option value='01' <?php echo ($mes=='01') ? 'selected="selected"' : null;?>>Janeiro</option>
			<option value='02' <?php echo ($mes=='02') ? 'selected="selected"' : null;?>>Fevereiro</option>
			<option value='03' <?php echo ($mes=='03') ? 'selected="selected"' : null;?>>Março</option>
			<option value='04' <?php echo ($mes=='04') ? 'selected="selected"' : null;?>>Abril</option>
			<option value='05' <?php echo ($mes=='05') ? 'selected="selected"' : null;?>>Maio</option>
			<option value='06' <?php echo ($mes=='06') ? 'selected="selected"' : null;?>>Junho</option>
			<option value='07' <?php echo ($mes=='07') ? 'selected="selected"' : null;?>>Julho</option>
			<option value='08' <?php echo ($mes=='08') ? 'selected="selected"' : null;?>>Agosto</option>
			<option value='09' <?php echo ($mes=='09') ? 'selected="selected"' : null;?>>Setembro</option>
			<option value='10' <?php echo ($mes=='10') ? 'selected="selected"' : null;?>>Outubro</option>
			<option value='11' <?php echo ($mes=='11') ? 'selected="selected"' : null;?>>Novembro</option>
			<option value='12' <?php echo ($mes=='12') ? 'selected="selected"' : null;?>>Dezembro</option>
		</select>
	</td>
	<td width='50' class='table_line'>&nbsp;</td>
	<td class='table_line'> Ano: </td>
	<td class='table_line'>
		<select name='ano' size='1'>
			<option value=''></option>
			<?php 
			$year = Date('Y') - 10;
			while($year <= date('Y')):?>
				<option value='<?php echo $year;?>' <?php echo ($year==$ano) ? 'selected="selected"' : null;?>><?php echo $year;?></option>
				<?php 
				$year++;
			endwhile;?>
		</select>
	</td>
</tr>
<tr>
	<td colspan='5' class='table_line'><center><input type='submit' name='btn_acao' value='Gerar Relatório'></center></td>
</tr>
</form>
</table>

<?
//=================TABELA DOS 3 MESES ANTERIORES=====================================

$mes = trim($_GET['mes']);
$ano = trim($_GET['ano']);

if($_POST['mes']) $mes = trim($_POST['mes']);
if($_POST['ano']) $ano = trim($_POST['ano']);

$btn_acao = trim($_GET['btn_acao']);
if($_POST['btn_acao']) $btn_acao = trim($_POST['btn_acao']);

if (strlen($btn_acao) > 0){
	if (strlen ($ano) == 0 && strlen ($mes) == 0){
		echo "<script language='JavaScript'>alert('Ano e Mês em branco!');</script>";
	}elseif (strlen ($ano) == 0){
		echo "<script language='JavaScript'>alert('Ano em branco!');</script>";
	}elseif (strlen ($mes) == 0){ 
		echo "<script language='JavaScript'>alert('Mês em branco!');</script>";
	}
}

if ($mes == '01') $aux_mes = "Janeiro";
if ($mes == '02') $aux_mes = "Fevereiro";
if ($mes == '03') $aux_mes = "Março";
if ($mes == '04') $aux_mes = "Abril";
if ($mes == '05') $aux_mes = "Maio";
if ($mes == '06') $aux_mes = "Junho";
if ($mes == '07') $aux_mes = "Julho";
if ($mes == '08') $aux_mes = "Agosto";
if ($mes == '09') $aux_mes = "Setembro";
if ($mes == '10') $aux_mes = "Outubro";
if ($mes == '11') $aux_mes = "Novembro";
if ($mes == '12') $aux_mes = "Dezembro";

if(strlen($mes) > 0 AND strlen($ano) > 0){
	echo "<table width='750' align='center'>";
	echo "<tr>";
	echo "<td align='center'><font size='2' color='#0000cc' ><br><u><b>*Após fazer a emissão da nota fiscal, enviá-la via fax (34 3318-3018), aos cuidados do responsável pelo seu suporte.</b></u></font></td>";
	echo "</tr>";
	echo "</table><br>";

	echo "<table align='center' width='750' border='0' cellpadding='2' cellspacing='1' style=' border-collapse: collapse'>";
	echo "<tr>";
	echo "<td colspan='3' align='center' style='font-size:12px;background-color:#596D9B;color:#FFFFFF'>Relatório mensal para emissão da Nota fiscal de devolução de peças para a fábrica</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td colspan='3' align='center' style=' font-size: 12 px; background-color: #596D9B; color:#FFFFFF'>$aux_mes de $ano </td>";
	echo "</tr>";
	echo "<tr bgcolor='#596D9B' align='center' style=' font-size: 12 px; font-weight: bold; color:#FFFFFF'>";
	echo "<td class='table_line'>Referência</td>";
	echo "<td class='table_line'>Descrição</td>";
	echo "<td class='table_line'>Qtd</td>";
	echo "</tr>\n\n";
	
	$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
	$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
	
	$sql = "SELECT  tbl_os_item.peca,
					tbl_peca.referencia,
					tbl_peca.descricao ,
					sum(tbl_os_item.qtde) AS qtde
			FROM    tbl_os
			JOIN    tbl_os_produto ON tbl_os_produto.os            = tbl_os.os
			JOIN    tbl_os_item    ON tbl_os_item.os_produto       = tbl_os_produto.os_produto
			JOIN    tbl_peca       ON tbl_peca.peca                = tbl_os_item.peca
			JOIN    tbl_defeito    ON tbl_defeito.defeito          = tbl_os_item.defeito
			JOIN    tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
			JOIN    tbl_produto    ON tbl_produto.produto          = tbl_os.produto
			JOIN    tbl_os_extra   ON tbl_os_extra.os              = tbl_os.os
			JOIN    tbl_extrato    ON tbl_extrato.extrato          = tbl_os_extra.extrato
			WHERE   tbl_os.posto = $login_posto
			AND     tbl_os.fabrica = $login_fabrica
			AND     tbl_extrato.aprovado::date BETWEEN '$data_inicial' AND '$data_final'
			AND     tbl_os_extra.extrato notnull
			AND     tbl_extrato.aprovado notnull
			GROUP BY tbl_os_item.peca,
					tbl_peca.referencia,
					tbl_peca.descricao 
			ORDER BY tbl_peca.referencia";
	$res = pg_exec($con,$sql);
	$total_rows = pg_numrows($res);
	
	for ($i=0; $i<$total_rows; $i++) {
		$cor = ($i % 2) ? '#eaeaea' : '#ffffff';
		$corborda = '#bbbbbb';
		$referencia = pg_result($res,$i,referencia);
		$descricao  = pg_result($res,$i,descricao);
		$qtde = pg_result($res,$i,qtde);
		
		echo "<tr align='center' bgcolor='$cor'>\n";
		echo "<td class='table_line' style='border: 1px solid $corborda'>$referencia</td>\n";
		echo "<td class='table_line' style='border: 1px solid $corborda' align='left'>$descricao</td>\n";
		echo "<td class='table_line' style='border: 1px solid $corborda'>$qtde</td>\n";
		echo "</tr>\n\n";
	}
	echo "</table>";
}

echo "<BR><BR>";

/* HD 104916 */
//echo "<a href=relatorio_separacao_pecas_devolucao.php>Separação das peças para devolução</a>";

include "rodape.php"; 

?>