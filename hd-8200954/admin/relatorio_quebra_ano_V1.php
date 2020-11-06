<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

// Criterio padrão
########################################
$_POST["criterio"] = "data_digitacao";
########################################

if ($btn_finalizar == 1) {
	if (strlen($erro) == 0) {
		if (strlen($_POST["data_inicial_01"]) == 0) {
			$erro .= "Favor informar a data inicial para pesquisa<br>";
		}
		
		if (strlen($erro) == 0) {
			$data_inicial   = trim($_POST["data_inicial_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_inicial')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}
			
			if (strlen($erro) == 0) $aux_data_inicial = "'". @pg_result ($fnc,0,0) ." 00:00:00'";
		}
	}
	
	if (strlen($erro) == 0) {
		if (strlen($_POST["data_final_01"]) == 0) {
			$erro .= "Favor informar a data final para pesquisa<br>";
		}
		
		if (strlen($erro) == 0) {
			$data_final   = trim($_POST["data_final_01"]);
			$fnc            = @pg_exec($con,"SELECT fnc_formata_data('$data_final')");
			
			if (strlen ( pg_errormessage ($con) ) > 0) {
				$erro = pg_errormessage ($con) ;
			}
			
			if (strlen($erro) == 0) $aux_data_final = "'". @pg_result ($fnc,0,0) ." 23:59:59'";
		}
	}
	
	if (strlen($erro) == 0) {
		if(strlen($_POST["criterio"]) == 0) {
			$erro .= "Favor informar o critério (Abertura ou Lançamento de OS) para pesquisa<br>";
		}
		
		if (strlen($erro) == 0) {
			$aux_criterio = trim($_POST["criterio"]);
		}
	}
	
	if (strlen($erro) == 0) $listar = "ok";
	
	if (strlen($erro) > 0) {
		$data_inicial = trim($_POST["data_inicial"]);
		$data_final   = trim($_POST["data_final"]);
		$criterio     = trim($_POST["criterio"]);
		
		$msg  = "<b>Foi detectado o seguinte erro: </b><br>";
		$msg .= $erro;
	}
}

$layout_menu = "gerencia";
$title = "RELATÓRIO DE QUEBRA POR ANO";

include "cabecalho.php";

?>

<script language="JavaScript">
function AbreProduto(mes,data_inicial,data_final){
	janela = window.open("relatorio_quebra_ano_produto.php?mes=" + mes + "&data_inicial=" + data_inicial + "&data_final=" + data_final,"produto","toolbar=no,location=no,status=no,scrollbars=yes,directories=no,width=600,height=350,top=18,left=0");
}
</script>

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

.table_line {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

/*****************************
ELEMENTOS DE POSICIONAMENTO
*****************************/

#container {
  border: 0px;
  padding:0px 0px 0px 0px;
  margin:0px 0px 0px 0px;
  background-color: white;
}

</style>

<? include "javascript_pesquisas.php" ?>


<!--=============== <FUNÇÕES> ================================!-->
<!--  XIN´S POP UP CALENDAR -->

<script language="javascript" src="js/cal2.js">
/*
Xin's Popup calendar script-  Xin Yang (http://www.yxscripts.com/)
Script featured on/available at http://www.dynamicdrive.com/
This notice must stay intact for use
*/
</script>

<script language="javascript" src="js/cal_conf2.js"></script>


<DIV ID="container" style="width: 100%; ">

<!-- =========== PESQUISA POR INTERVALO ENTRE DATAS ============ -->
<FORM name="frm_pesquisa" METHOD="POST" ACTION="<? echo $PHP_SELF ?>">

<?
if (strlen($msg) > 0){
?>
<table width="730" border="0" cellpadding="2" cellspacing="2" align='center'>
<tr>
	<td align="center" class='error'>
			<? echo $msg ?>
			
	</td>
</tr>
</table>

<br>
<?
}
?>

<br>

<TABLE width="400" align="center" border="0" cellspacing="0" cellpadding="2">
  <TR>
	<TD colspan="6" class="menu_top"><div align="center"><b>Pesquisa por Intervalo entre Datas</b></div></TD>
  </TR>
  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line">Data Inicial</TD>
    <TD class="table_line">Data Final</TD>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
  </TR>
  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line" style="width: 185px"><INPUT size="12" maxlength="10" TYPE="text" NAME="data_inicial_01" value="dd/mm/aaaa" onClick="this.value=''">&nbsp;<IMG src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('dataPesquisaInicial_01')" style="cursor:pointer" alt="Clique aqui para abrir o calendário"></TD>
	<TD class="table_line" style="width: 185px"><INPUT size="12" maxlength="10" TYPE="text" NAME="data_final_01" value="dd/mm/aaaa" onClick="this.value=''">&nbsp;<IMG src="imagens_admin/btn_lupa.gif" align='absmiddle' onclick="javascript:showCal('dataPesquisaFinal_01')" style="cursor:pointer" alt="Clique aqui para abrir o calendário"></TD>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
  </TR>
<!--
	<TR>
		<TD class="table_line" colspan="4"><hr></TD>
	</TR>
  <TR>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
	<TD class="table_line">
		<INPUT TYPE="radio" <? if ($criterio == "data_abertura") echo " checked "; ?> NAME="criterio" value="data_abertura">Abertura da OS
	</TD>
	<TD class="table_line">
		<INPUT TYPE="radio" <? if ($criterio == "data_digitacao") echo " checked "; ?> NAME="criterio" value="data_digitacao">Lançamento da OS
	</TD>
    <TD class="table_line" style="width: 10px">&nbsp;</TD>
  </TR>
-->
  <TR>
    <input type='hidden' name='btn_finalizar' value='0'>
    <TD colspan="4" class="table_line" style="text-align: center;"><IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_finalizar.value == '0' ) { document.frm_pesquisa.btn_finalizar.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'></TD>
  </TR>
</TABLE>

</FORM>

<!-- =========== AQUI TERMINA O FORMULÁRIO FRM_PESQUISA =========== -->
</DIV>

<?
if ($listar == "ok") {
	$sql = "SELECT
			(
				SELECT  sum(vw_visao_geral.qtde)
				FROM    vw_visao_geral
				WHERE   vw_visao_geral.$aux_criterio BETWEEN $aux_data_inicial AND $aux_data_final
				AND     vw_visao_geral.fabrica = $login_fabrica
				AND     vw_visao_geral.mes <= 12
			) AS qtde_1_ano,
			(
				SELECT  sum(vw_visao_geral.qtde)
				FROM    vw_visao_geral
				WHERE   vw_visao_geral.$aux_criterio BETWEEN $aux_data_inicial AND $aux_data_final
				AND     vw_visao_geral.fabrica = $login_fabrica
				AND     vw_visao_geral.mes > 12 AND vw_visao_geral.mes <= 24
			) AS qtde_2_ano,
			(
				SELECT  sum(vw_visao_geral.qtde)
				FROM    vw_visao_geral
				WHERE   vw_visao_geral.$aux_criterio BETWEEN $aux_data_inicial AND $aux_data_final
				AND     vw_visao_geral.fabrica = $login_fabrica
				AND     vw_visao_geral.mes > 24 AND vw_visao_geral.mes <= 36
			) AS qtde_3_ano,
			(
				SELECT  sum(vw_visao_geral.qtde)
				FROM    vw_visao_geral
				WHERE   vw_visao_geral.$aux_criterio BETWEEN $aux_data_inicial AND $aux_data_final
				AND     vw_visao_geral.fabrica = $login_fabrica
				AND     vw_visao_geral.mes > 36
			) AS qtde_acima
			;";
//echo $sql; exit;
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		$qtde_1_ano = trim(pg_result($res,0,qtde_1_ano));
		$qtde_2_ano = trim(pg_result($res,0,qtde_2_ano));
		$qtde_3_ano = trim(pg_result($res,0,qtde_3_ano));
		$qtde_acima = trim(pg_result($res,0,qtde_acima));
		
		if (strlen($qtde_1_ano) == 0) $qtde_1_ano = 0;
		if (strlen($qtde_2_ano) == 0) $qtde_2_ano = 0;
		if (strlen($qtde_3_ano) == 0) $qtde_3_ano = 0;
		if (strlen($qtde_acima) == 0) $qtde_acima = 0;
		
		$total_qtde = $qtde_1_ano + $qtde_2_ano + $qtde_3_ano + $qtde_acima;
		
		if ($qtde_1_ano > 0) $porc_1_ano = $qtde_1_ano * 100 / $total_qtde;
		if ($qtde_2_ano > 0) $porc_2_ano = $qtde_2_ano * 100 / $total_qtde;
		if ($qtde_3_ano > 0) $porc_3_ano = $qtde_3_ano * 100 / $total_qtde;
		if ($qtde_acima > 0) $porc_acima = $qtde_acima * 100 / $total_qtde;
		
		$total_porc = $porc_1_ano + $porc_2_ano + $porc_3_ano + $porc_acima;
	}
	
	echo "<br>";

	echo "<b>Resultado de pesquisa entre os dias $data_inicial e $data_final</b>";

	echo "<br><br>";
	
	echo "<table width='200' border='1' cellpadding='2' cellspacing='0' align='center'>";
	echo "<tr>";
	
	echo "<td align='center' colspan='3' bgcolor='#CCCCCC'>";
	echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><b>VISÃO GERAL POR ANO</b></font>";
	echo "</td>";
	
	echo "</tr>";
	echo "<tr>";
	
	echo "<td align='center'>";
	echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><b>ANO</b></font>";
	echo "</td>";
	
	echo "<td align='center'>";
	echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><b>QTDE</b></font>";
	echo "</td>";

	echo "<td align='center'>";
	echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'><b>%</b></font>";
	echo "</td>";
	
	echo "</tr>";
	echo "<tr>";
	### QUEBRA 1º ANO ###
	echo "<td align='center'>";
	echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>1º ANO</font>";
	echo "</td>";
	
	echo "<td align='center'>";
	echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>$qtde_1_ano</font>";
	echo "</td>";
	
	echo "<td align='right'>";
	echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>". number_format($porc_1_ano,2,",",".") ."%</font>";
	echo "</td>";
	
	echo "</tr>";
	echo "<tr>";
	### QUEBRA 2º ANO ###
	echo "<td align='center'>";
	echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>2º ANO</font>";
	echo "</td>";
	
	echo "<td align='center'>";
	echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>$qtde_2_ano</font>";
	echo "</td>";
	
	echo "<td align='right'>";
	echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>". number_format($porc_2_ano,2,",",".") ."%</font>";
	echo "</td>";
	
	echo "</tr>";
	echo "<tr>";
	### QUEBRA 3º ANO ###
	echo "<td align='center'>";
	echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>3º ANO</font>";
	echo "</td>";
	
	echo "<td align='center'>";
	echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>$qtde_3_ano</font>";
	echo "</td>";
	
	echo "<td align='right'>";
	echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>". number_format($porc_3_ano,2,",",".") ."%</font>";
	echo "</td>";
	
	echo "</tr>";
	echo "<tr>";
	### QUEBRA ACIMA 3 ANOS ###
	echo "<td align='center'>";
	echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>ACIMA</font>";
	echo "</td>";
	
	echo "<td align='center'>";
	echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>$qtde_acima</font>";
	echo "</td>";
	
	echo "<td align='right'>";
	echo "<font face='Arial, Verdana, Times, Sans' size='2' color='#000000'>". number_format($porc_acima,2,",",".") ."%</font>";
	echo "</td>";
	
	echo "</tr>";
	echo "</table>";
	
	echo "<br>";
	
	echo "<table border='0' cellpadding='2' cellspacing='2' width='600' align='center'>";
	echo "<tr>";
	
	# -- Início Tabela Primeiro Ano -- '
	echo "<td width='30%' valign='top'>";
	
	echo "<table border='1' cellpadding='0' cellspacing='0' width='100%' align='center'>";
	echo "<tr>";
	
	echo "<td width='100%' align='center' colspan='3' bgcolor='#CCCCCC'><small><strong><font face='Verdana, Arial, Helvetica, sans'>1º ANO</font></strong></small></td>";
	
	echo "</tr>";
	echo "<tr>";
	
	echo "<td width='33%' align='center'><b><font face='Verdana, Arial, Helvetica, sans' size='2'>MESES</font></b></td>";
	echo "<td width='33%' align='center'><b><font face='Verdana, Arial, Helvetica, sans' size='2'>QTDE</font></b></td>";
	echo "<td width='33%' align='center'><b><font face='Verdana, Arial, Helvetica, sans' size='2'>%</font></b></td>";
	
	echo "</tr>";
	
	$sql = "SELECT  vw_visao_geral.mes                ,
					count(vw_visao_geral.qtde) AS qtde
			FROM    vw_visao_geral
			WHERE   vw_visao_geral.$aux_criterio BETWEEN $aux_data_inicial AND $aux_data_final
			AND     vw_visao_geral.fabrica = $login_fabrica
			AND     vw_visao_geral.mes <= 12
			GROUP BY vw_visao_geral.mes
			ORDER BY vw_visao_geral.mes;";
	$res = pg_exec ($con,$sql);
	$porc_soma = 0;
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$mes  = trim(pg_result($res,$i,mes));
		$qtde = trim(pg_result($res,$i,qtde));
		$porc = $qtde * 100 / $qtde_1_ano;
		$porc_soma = $porc_soma + $porc;
		
		echo "<tr>";
		
		echo "<td width='33%' align='center'><font face='Verdana, Arial, Helvetica, sans' size='2'>$mes</font></td>";
		echo "<td width='33%' align='center'><a href='javascript:AbreProduto(\"$mes\",\"$data_inicial\", \"$data_final\");'><font face='Verdana, Arial, Helvetica, sans' size='2'>$qtde</font></a></td>";
		echo "<td width='33%' align='right'><font face='Verdana, Arial, Helvetica, sans' size='2'>". number_format($porc,2,",",".") ."%</font></td>";
		
		echo "</tr>";
	}
	
	echo "<tr>";
	
	echo "<td width='33%' align='center'><font face='Verdana, Arial, Helvetica, sans' size='2'><b>TOTAL</b></font></strong></small></td>";
	echo "<td width='33%' align='center'><font face='Verdana, Arial, Helvetica, sans' size='2'>$qtde_1_ano</font></td>";
	echo "<td width='33%' align='right'><font face='Verdana, Arial, Helvetica, sans' size='2'>". number_format($porc_soma,2,",",".") ."%</font></td>";
	
	echo "</tr>";
	echo "</table>";
	
	echo "</td>";
	# -- Final Tabela Primeiro Ano -- '
	
	echo "<td width='5%' valign='top'>&nbsp;</td>";
	
	# -- Início Tabela Segundo Ano -- '
	echo "<td width='30%' valign='top'>";
	
	echo "<table border='1' cellpadding='0' cellspacing='0' width='100%' align='center'>";
	echo "<tr>";
	
	echo "<td width='100%' align='center' colspan='3' bgcolor='#CCCCCC'><small><strong><font face='Verdana, Arial, Helvetica, sans'>2º ANO</font></strong></small></td>";
	
	echo "</tr>";
	echo "<tr>";
	
	echo "<td width='33%' align='center'><b><font face='Verdana, Arial, Helvetica, sans' size='2'>MESES</font></b></td>";
	echo "<td width='33%' align='center'><b><font face='Verdana, Arial, Helvetica, sans' size='2'>QTDE</font></b></td>";
	echo "<td width='33%' align='center'><b><font face='Verdana, Arial, Helvetica, sans' size='2'>%</font></b></td>";
	
	echo "</tr>";
	
	$sql = "SELECT  vw_visao_geral.mes                ,
					count(vw_visao_geral.qtde) AS qtde
			FROM    vw_visao_geral
			WHERE   vw_visao_geral.$aux_criterio BETWEEN $aux_data_inicial AND $aux_data_final
			AND     vw_visao_geral.fabrica = $login_fabrica
			AND     vw_visao_geral.mes > 12 AND vw_visao_geral.mes <= 24
			GROUP BY vw_visao_geral.mes
			ORDER BY vw_visao_geral.mes;";
	$res = pg_exec ($con,$sql);
	$porc_soma = 0;
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$mes  = trim(pg_result($res,$i,mes));
		$qtde = trim(pg_result($res,$i,qtde));
		$porc = $qtde * 100 / $qtde_2_ano;
		$porc_soma = $porc_soma + $porc;
		
		echo "<tr>";
		
		echo "<td width='33%' align='center'><font face='Verdana, Arial, Helvetica, sans' size='2'>$mes</font></td>";
		//echo "<td width='33%' align='center'><font face='Verdana, Arial, Helvetica, sans' size='2'>$qtde</font></td>";
		echo "<td width='33%' align='center'><a href='javascript:AbreProduto(\"$mes\",\"$data_inicial\", \"$data_final\");'><font face='Verdana, Arial, Helvetica, sans' size='2'>$qtde</font></a></td>";
		echo "<td width='33%' align='right'><font face='Verdana, Arial, Helvetica, sans' size='2'>". number_format($porc,2,",",".") ."%</font></td>";
		
		echo "</tr>";
	}
	
	echo "<tr>";
	
	echo "<td width='33%' align='center'><font face='Verdana, Arial, Helvetica, sans' size='2'><b>TOTAL</b></font></strong></small></td>";
	echo "<td width='33%' align='center'><font face='Verdana, Arial, Helvetica, sans' size='2'>$qtde_2_ano</font></td>";
	echo "<td width='33%' align='right'><font face='Verdana, Arial, Helvetica, sans' size='2'>". number_format($porc_soma,2,",",".") ."%</font></td>";
	
	echo "</tr>";
	echo "</table>";
	
	echo "</td>";
	# -- Final Tabela Segundo Ano -- '
	
	echo "<td width='5%' valign='top'>&nbsp;</td>";
	
	# -- Início Tabela Terceiro Ano -- '
	echo "<td width='30%' valign='top'>";
	
	echo "<table border='1' cellpadding='0' cellspacing='0' width='100%' align='center'>";
	echo "<tr>";
	
	echo "<td width='100%' align='center' colspan='3' bgcolor='#CCCCCC'><small><strong><font face='Verdana, Arial, Helvetica, sans'>3º ANO</font></strong></small></td>";
	
	echo "</tr>";
	echo "<tr>";
	
	echo "<td width='33%' align='center'><b><font face='Verdana, Arial, Helvetica, sans' size='2'>MESES</font></b></td>";
	echo "<td width='33%' align='center'><b><font face='Verdana, Arial, Helvetica, sans' size='2'>QTDE</font></b></td>";
	echo "<td width='33%' align='center'><b><font face='Verdana, Arial, Helvetica, sans' size='2'>%</font></b></td>";
	
	echo "</tr>";
	
	$sql = "SELECT  vw_visao_geral.mes                ,
					count(vw_visao_geral.qtde) AS qtde
			FROM    vw_visao_geral
			WHERE   vw_visao_geral.$aux_criterio BETWEEN $aux_data_inicial AND $aux_data_final
			AND     vw_visao_geral.fabrica = $login_fabrica
			AND     vw_visao_geral.mes > 24 AND vw_visao_geral.mes <= 36
			GROUP BY vw_visao_geral.mes
			ORDER BY vw_visao_geral.mes;";
	$res = pg_exec ($con,$sql);
	$porc_soma = 0;
	for ( $i = 0 ; $i < pg_numrows ($res) ; $i++ ) {
		$mes  = trim(pg_result($res,$i,mes));
		$qtde = trim(pg_result($res,$i,qtde));
		$porc = $qtde * 100 / $qtde_3_ano;
		$porc_soma = $porc_soma + $porc;
		
		echo "<tr>";
		
		echo "<td width='33%' align='center'><font face='Verdana, Arial, Helvetica, sans' size='2'>$mes</font></td>";
		//echo "<td width='33%' align='center'><font face='Verdana, Arial, Helvetica, sans' size='2'>$qtde</font></td>";
		echo "<td width='33%' align='center'><a href='javascript:AbreProduto(\"$mes\",\"$data_inicial\", \"$data_final\");'><font face='Verdana, Arial, Helvetica, sans' size='2'>$qtde</font></a></td>";
		echo "<td width='33%' align='right'><font face='Verdana, Arial, Helvetica, sans' size='2'>". number_format($porc,2,",",".") ."%</font></td>";
		
		echo "</tr>";
	}
	
	echo "<tr>";
	
	echo "<td width='33%' align='center'><font face='Verdana, Arial, Helvetica, sans' size='2'><b>TOTAL</b></font></strong></small></td>";
	echo "<td width='33%' align='center'><font face='Verdana, Arial, Helvetica, sans' size='2'>$qtde_3_ano</font></td>";
	echo "<td width='33%' align='right'><font face='Verdana, Arial, Helvetica, sans' size='2'>". number_format($porc_soma,2,",",".") ."%</font></td>";
	
	echo "</tr>";
	echo "</table>";
	
	echo "</td>";
	# -- Final Tabela Segundo Ano -- '
	
	echo "</tr>";
	echo "</table>";
	
	echo "<br><br>";
}
?>

<? include "rodape.php" ?>