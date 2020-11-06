<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'autentica_usuario.php';

$msg_erro = "";

$layout_menu = "os";
$title = "Relatório de Devolução de Peças";

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
	<td colspan='5' class='table_top'><center><B>Relatório de conferência das peças devolvidas</B></center></td>
</tr>
<tr>
	<td align='right' class='table_line'> Mês: </td>
	<td class='table_line'>
		<select name='mes' size='1'>
			<option value=''></option>
			<option value='01'>Janeiro</option>
			<option value='02'>Fevereiro</option>
			<option value='03'>Março</option>
			<option value='04'>Abril</option>
			<option value='05'>Maio</option>
			<option value='06'>Junho</option>
			<option value='07'>Julho</option>
			<option value='08'>Agosto</option>
			<option value='09'>Setembro</option>
			<option value='10'>Outubro</option>
			<option value='11'>Novembro</option>
			<option value='12'>Dezembro</option>
		</select>
	</td>
	<td width='50' class='table_line'>&nbsp;</td>
	<td class='table_line'> Ano: </td>
	<td class='table_line'>
		<select name='ano' size='1'>
			<option value=''></option>
			<option value='2004'>2004</option>
			<option value='2005'>2005</option>
			<option value='2006'>2006</option>
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
	echo "<td align='center'><font size='2' color='#0000cc' ><br><u><b>*Após fazer a emissão da nota fiscal, enviá-la via fax (34 3318-3018), aos cuidados de Anderson.</b></u></font></td>";
	echo "</tr>";
	echo "</table><br>";

	echo "<table align='center' width='750' border='0' cellpadding='2' cellspacing='1' style=' border-collapse: collapse'>";
	echo "<tr>";
	echo "<td colspan='5' align='center' style='font-size:12px;background-color:#596D9B;color:#FFFFFF'>Relação de peças para emissão da nota fiscal de devolução</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td colspan='5' align='center' style=' font-size: 12 px; background-color: #596D9B; color:#FFFFFF'>$aux_mes de $ano </td>";
	echo "</tr>";
	echo "<tr bgcolor='#596D9B' align='center' style=' font-size: 12 px; font-weight: bold; color:#FFFFFF'>";
	echo "<td class='table_line'>O.S</td>";
	echo "<td class='table_line'>Extrato</td>";
	echo "<td class='table_line'>Referência</td>";
	echo "<td class='table_line'>Descrição</td>";
	echo "<td class='table_line'>Qtd</td>";
	echo "</tr>\n\n";
	
	$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
	$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));
	
	$sql = "SELECT  tbl_os.sua_os,
				tbl_posto_fabrica.codigo_posto                ,
				tbl_os_extra.extrato,
				tbl_os_item.peca,
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
			JOIN    tbl_posto_fabrica ON tbl_posto_fabrica.posto   = tbl_os.posto 
									 AND tbl_posto_fabrica.fabrica = $login_fabrica
			WHERE   tbl_posto_fabrica.posto = $login_posto
			AND     tbl_os.data_digitacao::date BETWEEN '$data_inicial' AND '$data_final'
			AND     tbl_os_extra.extrato notnull
			AND     tbl_extrato.aprovado notnull
			GROUP BY tbl_os_item.peca,
					tbl_peca.referencia,
					tbl_peca.descricao ,
					tbl_posto_fabrica.codigo_posto                ,
					tbl_os_extra.extrato,
					tbl_os.sua_os
			ORDER BY tbl_peca.referencia";
	$res = pg_exec($con,$sql);
	$total_rows = pg_numrows($res);
	
	for ($i=0; $i<$total_rows; $i++) {
		$cor = ($i % 2) ? '#fafafa' : '#ffffff';
		$referencia = pg_result($res,$i,referencia);
		$descricao  = pg_result($res,$i,descricao);
		$qtde = pg_result($res,$i,qtde);
		$sua_os             = trim(pg_result($res,$i,sua_os));
		$codigo_posto             = trim(pg_result($res,$i,codigo_posto));
		$extrato             = trim(pg_result($res,$i,extrato));
		
		echo "<tr align='center' bgcolor='$cor'>\n";
		echo "<td class='table_line'>$codigo_posto$sua_os</td>\n";
		echo "<td class='table_line'>$extrato</td>\n"; 
		echo "<td class='table_line'>$referencia</td>\n";
		echo "<td class='table_line' align='left'>$descricao</td>\n";
		echo "<td class='table_line'>$qtde</td>\n";
		echo "</tr>\n\n";
	}
	echo "</table>";
}

echo "<BR><BR>";

include "rodape.php"; 

?>