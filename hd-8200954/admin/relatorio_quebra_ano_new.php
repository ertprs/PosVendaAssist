<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

$acao=$_GET['btn_finalizar'];

// Criterio padrão
########################################
$criterio="data_abertura";
########################################


function converte_data($date)
{
	$date = explode("-", preg_replace('/\//', '-', $date));
	$date2 = ''.$date[2].'/'.$date[1].'/'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}


if ($acao=="1"){
	$ano = $_GET["ano"];
	if (strlen(trim($ano))!=4 ){
		$erro .="Selecione o ano!";
	}
	$criterio     = trim($_GET["criterio"]);
}

if ($acao=="1" && strlen($erro) == 0) {
	$listar = "ok";
	if (strlen($erro) > 0) {
		$msg  = "<b>Foi detectado o seguinte erro: </b> $erro<br>";
	}
}


$layout_menu = "gerencia";
if($login_fabrica == 24){
	$title = "RELATÓRIO DE ATENDIMENTOS POR ANO";
}else{
	$title = "RELATÓRIO DE QUEBRA POR ANO";
}

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
	text-align:center;
	padding:2px;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
	border: 0px solid;
	background-color: #D9E2EF
}

.table_line2 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
}

/*#F1F4FA
#F9F9F0*/

.conteudo1 {
	padding:5px;
	color:#000000;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	background-color: #FCF9DA;
}
.conteudo2 {
	padding:2px;
	color:#000000;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	background-color: #F1F4FA;
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
<FORM name="frm_pesquisa" METHOD="GET" ACTION="<? echo $PHP_SELF ?>">

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
	<TD colspan="6" class="menu_top" colspan='2'><div align="center"><b>Pesquisa por Intervalo entre Datas</b></div></TD>
  </TR>
  <TR>
	<TD class="table_line" align='center' colspan='2'>Selecione o ano</TD>
  </TR>
  <TR>
	<TD class="table_line"  colspan='2'>

		<select name="ano">
		<?php
		$ano_atual = date("Y");
		$ano_get = $_GET["ano"];
		for($i = $ano_atual; $i >=2004 ; $i--){
			if($i == $ano_get) 	echo "\t<option value='$i' selected='selected'>$i</option>\n";
			else				echo "\t<option value='$i'>$i</option>\n";
		}
		
		?>
		</select>
		<br /><br>
	</TD>
  </TR>
  <TR>
	<TD class="table_line">
		<INPUT TYPE="radio" <? if ($criterio == "data_abertura") echo " checked "; ?> NAME="criterio" value="data_abertura">Abertura da OS
	</TD>
	<TD class="table_line">
		<!-- <INPUT TYPE="radio" <? if ($criterio == "data_digitacao") echo " checked "; ?> NAME="criterio" value="data_digitacao">Lançamento da OS -->
	</TD>
  </TR>

  <TR><br>
    
    <TD class="table_line" style="text-align: center;" colspan='2'>
	<input type='hidden' name='btn_finalizar' value='0'>
	<IMG src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: if ( document.frm_pesquisa.btn_finalizar.value == '0' ) { document.frm_pesquisa.btn_finalizar.value='1'; document.frm_pesquisa.submit() ; } else { alert ('Aguarde submissão da OS...'); }" style="cursor:pointer " alt='Clique AQUI para pesquisar'></TD>
  </TR>
</TABLE>
</FORM>

<!-- =========== AQUI TERMINA O FORMULÁRIO FRM_PESQUISA =========== -->
</DIV>

<?

if ($listar == "ok") {
	
	$ano1 = $ano;
	$ano2 = $ano-1;
	$ano3 = $ano-2;
	$ano4 = $ano-3;

	$sql = "SELECT
			(
				SELECT	SUM(tbl_quebra_ano.qtde) AS qtde
				FROM		tbl_quebra_ano
				WHERE	to_char(tbl_quebra_ano.data,'YYYY') = $ano1
						AND tbl_quebra_ano.criterio='$criterio'
						AND fabrica=$login_fabrica
			) AS qtde_1_ano,
			(
				SELECT	SUM(tbl_quebra_ano.qtde) AS qtde
				FROM		tbl_quebra_ano
				WHERE	to_char(tbl_quebra_ano.data,'YYYY') = $ano2
						AND tbl_quebra_ano.criterio='$criterio'
						AND fabrica=$login_fabrica
			) AS qtde_2_ano,
			(
				SELECT	SUM(tbl_quebra_ano.qtde) AS qtde
				FROM		tbl_quebra_ano
				WHERE	to_char(tbl_quebra_ano.data,'YYYY') = $ano3
						AND tbl_quebra_ano.criterio='$criterio'
						AND fabrica=$login_fabrica
			) AS qtde_3_ano,
			(
				SELECT	SUM(tbl_quebra_ano.qtde) AS qtde
				FROM		tbl_quebra_ano
				WHERE	to_char(tbl_quebra_ano.data,'YYYY') = $ano4
						AND tbl_quebra_ano.criterio='$criterio'
						AND fabrica=$login_fabrica
			) AS qtde_4_ano";

	$res = @pg_exec ($con,$sql);
//if($login_admin ==568)	echo "sql: $sql<br>";
	if (@pg_numrows($res) > 0) {
		$qtde_1_ano = trim(pg_result($res,0,qtde_1_ano));
		$qtde_2_ano = trim(pg_result($res,0,qtde_2_ano));
		$qtde_3_ano = trim(pg_result($res,0,qtde_3_ano));
		$qtde_4_ano = trim(pg_result($res,0,qtde_4_ano));

		$total_qtde = $qtde_1_ano + $qtde_2_ano + $qtde_3_ano + $qtde_4_ano;

		if ($qtde_1_ano > 0) $porc_1_ano = $qtde_1_ano * 100 / $total_qtde;
		if ($qtde_2_ano > 0) $porc_2_ano = $qtde_2_ano * 100 / $total_qtde;
		if ($qtde_3_ano > 0) $porc_3_ano = $qtde_3_ano * 100 / $total_qtde;
		if ($qtde_4_ano > 0) $porc_4_ano = $qtde_4_ano * 100 / $total_qtde;
		
		$total_porc = $porc_1_ano + $porc_2_ano + $porc_3_ano + $porc_4_ano;
;
	}

	if ($total_qtde>0){
		echo "<br>";
	
		echo "<b>Resultado da Pesquisa (últimos 3 anos)</b><br><br>Clique sobre o Ano";
	
		//echo "<b>Resultado de pesquisa entre os dias ".date("m/Y",strtotime($periodo_2))." e ".date("m/Y",strtotime($periodo_1))." e períodos de 12 meses antecedentes.</b>";
	
		echo "<br><br>";
		
		echo "<table width='200' border='0' cellpadding='2' cellspacing='0' align='center'>";
	
		echo "<tr>";
		echo "<td align='center' colspan='3' class='menu_top'>";
		echo "<b>VISÃO GERAL POR ANO</b>";
		echo "</td>";
		echo "</tr>";
	
		echo "<tr>";
		echo "<td align='center'  class='table_line'><b>ANO</b></td>";
		echo "<td align='center'  class='table_line'><b>QTDE</b></td>";
		echo "<td align='center'  class='table_line'><b>%</b></td>";
		echo "</tr>";
	
		echo "<tr>";
		### QUEBRA 1º ANO ###
		echo "<td align='center' class='conteudo1' nowrap><a href='relatorio_quebra_ano_produto_novo.php?ano=$ano1'>$ano1</a></td>";
		echo "<td align='center' class='conteudo1'>$qtde_1_ano</td>";
		echo "<td align='right' class='conteudo1'>".number_format($porc_1_ano,2,",",".") ."%</td>";
		echo "</tr>";
	
		echo "<tr>";
		### QUEBRA 2º ANO ###
		echo "<td align='center' class='conteudo2' nowrap><a href='relatorio_quebra_ano_produto_novo.php?ano=$ano2'>$ano2</a></td>";
		echo "<td align='center'  class='conteudo2'>$qtde_2_ano</td>";
		echo "<td align='right'  class='conteudo2'>".number_format($porc_2_ano,2,",",".") ."%</td>";
		echo "</tr>";
	
		echo "<tr>";
		### QUEBRA 3º ANO ###
		echo "<td align='center' class='conteudo1' nowrap><a href='relatorio_quebra_ano_produto_novo.php?ano=$ano3'>$ano3</a></td>";
		echo "<td align='center' class='conteudo1'>$qtde_3_ano</td>";
		echo "<td align='right' class='conteudo1'>".number_format($porc_3_ano,2,",",".") ."%</td>";
		echo "</tr>";
	
		echo "<tr>";
		### QUEBRA ACIMA 3 ANOS ###
		echo "<td align='center'  class='conteudo2'>ACIMA DE 3 ANOS</td>";
		echo "<td align='center' class='conteudo2'>$qtde_4_ano</td>";
		echo "<td align='right' class='conteudo2'>".number_format($porc_4_ano,2,",",".") ."%</td>";
		echo "</tr>";
	
		echo "</table>";
		
		echo "<br>";
		
	
		echo "<table border='0' cellpadding='2' cellspacing='2' width='600' align='center'>";
	
		# -- Início Tabela Primeiro Ano -- '
		echo "<tr>";
		echo "<td width='30%' valign='top'>";
		
		echo "<table border='0' cellpadding='2' cellspacing='0' width='100%' align='center'>";
	
		echo "<tr>";
		echo "<td align='center' colspan='3' class='menu_top'>";
		echo "<b><a href='relatorio_quebra_ano_produto_novo.php?ano=$ano1'  style='color:white'>$ano1</a></b>";
		echo "</td>";
		echo "</tr>";
	
		echo "<tr>";
		echo "<td align='center'  class='table_line'><b>MÊS</b></td>";
		echo "<td align='center'  class='table_line'><b>QTDE</b></td>";
		echo "<td align='center'  class='table_line'><b>%</b></td>";
		echo "</tr>";
		
	
		$sql = "SELECT	SUM(tbl_quebra_ano.qtde) AS qtde,
					TO_CHAR(tbl_quebra_ano.data,'MM') AS periodo
					FROM		tbl_quebra_ano
					WHERE	to_char(tbl_quebra_ano.data,'YYYY') = $ano1
					AND     tbl_quebra_ano.fabrica = $login_fabrica
					AND tbl_quebra_ano.criterio='$criterio'
					GROUP BY periodo
					ORDER BY periodo;";
	
//	if($login_admin ==568) 	echo "sql: $sql<br>";
		$res = @pg_exec ($con,$sql);
		$porc_soma = 0;
	
	
		$qtde_1=0;
		for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
			$qtde_1 += trim(pg_result($res,$i,qtde));
		}
	
		for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
			$data  = trim(pg_result($res,$i,periodo));
			$qtde = trim(pg_result($res,$i,qtde));
			$porc = $qtde * 100 / $qtde_1;
			$porc_soma = $porc_soma + $porc;
	
			$tmp = ($i%2)+1;
			echo "<tr>";
			echo "<td width='33%' align='center' class='conteudo$tmp'>$data</td>";
			echo "<td width='33%' align='center' class='conteudo$tmp'>
				$qtde
				</td>";
			echo "<td width='33%' align='right' class='conteudo$tmp'>". number_format($porc,2,",",".") ."%</td>";
			echo "</tr>";
		}
		
		echo "<tr>";
		echo "<td width='33%' align='center' class='table_line2'><b>TOTAL</b></td>";
		echo "<td width='33%' align='center' class='table_line2'>$qtde_1_ano</td>";
		echo "<td width='33%' align='right'   class='table_line2'>". number_format($porc_soma,2,",",".") ."%</td>";
		echo "</tr>";
	
		echo "</table>";
	
	
		
		echo "</td>";
		# -- Final Tabela Primeiro Ano -- '
		
		echo "<td width='5%' valign='top'>&nbsp;</td>";
		
		# -- Início Tabela Segundo Ano -- '
		echo "<td width='30%' valign='top'>";
		
		echo "<table border='0' cellpadding='2' cellspacing='0' width='100%' align='center'>";
	
		echo "<tr>";
		echo "<td align='center' colspan='3' class='menu_top'>";
		echo "<b><a href='relatorio_quebra_ano_produto_novo.php?ano=$ano2'  style='color:white'>$ano2</a></b>";
		echo "</td>";
		echo "</tr>";
	
		echo "<tr>";
		echo "<td align='center'  class='table_line'><b>MÊS</b></td>";
		echo "<td align='center'  class='table_line'><b>QTDE</b></td>";
		echo "<td align='center'  class='table_line'><b>%</b></td>";
		echo "</tr>";
		
		
		$sql = "SELECT	SUM(tbl_quebra_ano.qtde) AS qtde,
					TO_CHAR(tbl_quebra_ano.data,'MM') AS periodo
					FROM		tbl_quebra_ano
					WHERE	to_char(tbl_quebra_ano.data,'YYYY') = $ano2
					AND     tbl_quebra_ano.fabrica = $login_fabrica
					AND tbl_quebra_ano.criterio='$criterio'
					GROUP BY periodo
					ORDER BY periodo;";
//if($login_admin ==568)	echo "sql: $sql<br>";
		$res = @pg_exec ($con,$sql);
		$porc_soma = 0;
	
	
		$qtde_2=0;
		for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
			$qtde_2 += trim(pg_result($res,$i,qtde));
		}
	
	
		for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
			$data  = trim(pg_result($res,$i,periodo));
			$qtde = trim(pg_result($res,$i,qtde));
			$porc = $qtde * 100 / $qtde_2;
			$porc_soma = $porc_soma + $porc;
	
			$tmp = ($i%2)+1;
			echo "<tr>";
			echo "<td width='33%' align='center' class='conteudo$tmp'>$data</td>";
			echo "<td width='33%' align='center' class='conteudo$tmp'>
				$qtde
				</td>";
			echo "<td width='33%' align='right' class='conteudo$tmp'>". number_format($porc,2,",",".") ."%</td>";
			echo "</tr>";
		}
		
		echo "<tr>";
		echo "<td width='33%' align='center' class='table_line2'><b>TOTAL</b></td>";
		echo "<td width='33%' align='center' class='table_line2'>$qtde_2_ano</td>";
		echo "<td width='33%' align='right'   class='table_line2'>". number_format($porc_soma,2,",",".") ."%</td>";
		echo "</tr>";
	
		echo "</table>";
		
		echo "</td>";
		# -- Final Tabela Segundo Ano -- '
		
		echo "<td width='5%' valign='top'>&nbsp;</td>";
		
		# -- Início Tabela Terceiro Ano -- '
		echo "<td width='30%' valign='top'>";
		
		echo "<table border='0' cellpadding='2' cellspacing='0' width='100%' align='center'>";
	
		echo "<tr>";
		echo "<td align='center' colspan='3' class='menu_top'>";
		echo "<b><a href='relatorio_quebra_ano_produto_novo.php?ano=$ano3' style='color:white'>$ano3</a></b>";
		echo "</td>";
		echo "</tr>";
	
		echo "<tr>";
		echo "<td align='center'  class='table_line'><b>MÊS</b></td>";
		echo "<td align='center'  class='table_line'><b>QTDE</b></td>";
		echo "<td align='center'  class='table_line'><b>%</b></td>";
		echo "</tr>";
		
	
	
		$sql = "SELECT	SUM(tbl_quebra_ano.qtde) AS qtde,
					TO_CHAR(tbl_quebra_ano.data,'MM') AS periodo
					FROM		tbl_quebra_ano
					WHERE	to_char(tbl_quebra_ano.data,'YYYY') = $ano3
					AND     tbl_quebra_ano.fabrica = $login_fabrica
					AND tbl_quebra_ano.criterio='$criterio'
					GROUP BY periodo
					ORDER BY periodo;";
//	if($login_admin ==568)	echo "sql: $sql<br>";
		$res = @pg_exec ($con,$sql);
		$porc_soma = 0;
	
		$qtde_3=0;
		for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
			$qtde_3 += trim(pg_result($res,$i,qtde));
		}
	
		for ( $i = 0 ; $i < @pg_numrows ($res) ; $i++ ) {
			$data  = trim(pg_result($res,$i,periodo));
			$qtde = trim(pg_result($res,$i,qtde));
			$porc = $qtde * 100 / $qtde_3;
			$porc_soma = $porc_soma + $porc;
	
			$tmp = ($i%2)+1;
			echo "<tr>";
			echo "<td width='33%' align='center' class='conteudo$tmp'>$data</td>";
			echo "<td width='33%' align='center' class='conteudo$tmp'>
				$qtde
				</td>";
			echo "<td width='33%' align='right' class='conteudo$tmp'>". number_format($porc,2,",",".") ."%</td>";
			echo "</tr>";
		}
		
		echo "<tr>";
		echo "<td width='33%' align='center' class='table_line2'><b>TOTAL</b></td>";
		echo "<td width='33%' align='center' class='table_line2'>$qtde_3_ano</td>";
		echo "<td width='33%' align='right' class='table_line2'>". number_format($porc_soma,2,",",".") ."%</td>";
		echo "</tr>";
	
		echo "</table>";
		
		echo "</td>";
		# -- Final Tabela Segundo Ano -- '
		
		echo "</tr>";
		echo "</table>";
		
		echo "<br><br>";
	}
	else{
		echo "<br><br>Não há ocorrências de OS's neste período<br><br>";
	}
}
?>

<? include "rodape.php" ?>