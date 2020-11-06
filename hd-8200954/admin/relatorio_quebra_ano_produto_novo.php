<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

$acao=$_GET['btn_finalizar'];

// Criterio padrão
########################################
$criterio="data_digitacao";
########################################


function converte_data($date)
{
	$date = explode("-", preg_replace('/\//', '-', $date));
	$date2 = ''.$date[2].'/'.$date[1].'/'.$date[0];
	if (sizeof($date)==3)
		return $date2;
	else return false;
}

if (strlen($_GET["produto"]) > 0) {
	$produto = trim($_GET["produto"]);
}
if (strlen($_GET["descricao"]) > 0) {
	$descricao_produto = trim($_GET["descricao"]);
}

if (strlen($_GET["ano"]) > 0) {
	$ano = trim($_GET["ano"]);
}
else{
	$erro .="Informe o ano!";
}




$layout_menu = "gerencia";

if($login_fabrica == 24){
	$title = "RELATÓRIO DE ATENDIMENTOS POR ANO - PRODUTO";
}else{
	$title = "RELATÓRIO DE QUEBRA POR ANO - PRODUTO";
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
	font-size: 14px;
	font-weight: bold;
	color:#ffffff;
	background-color: #596D9B;
}


.table_line {
	text-align:center;
	padding:2px;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: normal;
	background-color: #D9E2EF
}

.table_line2 {
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
}

/*#F1F4FA
#F9F9F0*/

.conteudo1 {
	padding:2px;
	color:#000000;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	background-color: #FCF9DA;
}
.conteudo2 {
	padding:2px;
	color:#000000;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
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



<?

$total_geral = 0;

if (strlen($ano)>0 && strlen($produto)==0 ){
	echo "<table width='200' border='1'  style='border-collapse: collapse' bordercolor='#485989' cellpadding='1' cellspacing='3' align='center'>";

	echo "<tr>";
	echo "<td align='center' colspan='5' class='menu_top'>";
	echo "<b>MÉDIA DE VIDA (DIAS) POR PRODUTO - ANO $ano</b>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td align='center'  class='table_line' nowrap><b>OS</b></td>";
	echo "<td align='center'  class='table_line' nowrap><b>Referência</b></td>";
	echo "<td align='center'  class='table_line' nowrap><b>Descrição</b></td>";
	echo "<td align='center'  class='table_line' nowrap><b>Qtde</b></td>";
	echo "<td align='center'  class='table_line' nowrap><b>Dias</b></td>";
	echo "</tr>";

	$sql = "SELECT		tbl_produto.produto                       ,
					tbl_produto.referencia                       ,
					tbl_produto.descricao                        ,
					tbl_quebra_ano_produto.qtde AS qtde,
					tbl_quebra_ano_produto.media_vida AS media
			FROM tbl_quebra_ano_produto
			JOIN	tbl_produto USING (produto)
			WHERE	tbl_quebra_ano_produto.ano  = $ano
			AND		tbl_quebra_ano_produto.fabrica = $login_fabrica
			ORDER BY media,qtde ASC
			";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		for ($i=0; $i<pg_numrows($res); $i++){
			$produto_codigo = trim(pg_result($res,$i,produto));
			$referencia = trim(pg_result($res,$i,referencia));
			$descricao  = trim(pg_result($res,$i,descricao));
			$media  = trim(pg_result($res,$i,media));
			$qtde       = trim(pg_result($res,$i,qtde));

			$total_geral += $qtde;

			echo "<TR>";
			echo "	<TD class='conteudo".(($i%2)+1)."' align='left' nowrap><a href='$_PHP_SELF?ano=$ano&produto=$produto_codigo&descricao=$descricao' target='_blank'>Abrir OS's</a></TD>";
			echo "	<TD class='conteudo".(($i%2)+1)."' align='left' nowrap><a href='produto_cadastro.php?produto=$produto_codigo' target='_blank'>$referencia</a></TD>";
			echo "	<TD class='conteudo".(($i%2)+1)."' align='left' nowrap>$descricao</TD>";
			echo "	<TD class='conteudo".(($i%2)+1)."' align='center' nowrap>$qtde</TD>";
			echo "	<TD class='conteudo".(($i%2)+1)."' align='center' nowrap>$media</TD>";
//			echo "	<TD class='conteudo10' align='right'>". number_format($porc,2,",",".") ."%</TD>";
			echo "</TR>";

		}
	}
	echo "</table>";
	
	echo "<br>";
	echo "Total de OS's: $total_geral";
	
	echo "<br><br>";
}

if (strlen($ano)>0 && strlen($produto)>0 ){
	echo "<table width='200' border='1'  style='border-collapse: collapse' bordercolor='#485989' cellpadding='1' cellspacing='3' align='center'>";

	echo "<tr>";
	echo "<td align='center' colspan='6' class='menu_top' nowrap>";
	echo "<b>Relação de OS's com o produto $descricao_produto</b>";
	echo "</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td align='center' colspan='6' class='table_line' nowrap>";
	echo "ANO $ano ";
	echo "</td>";
	echo "</tr>";
 

	echo "<tr>";
	echo "<td align='center'  class='table_line' nowrap><b>OS</b></td>";
	echo "<td align='center'  class='table_line' nowrap><b>Data NF</b></td>";
	echo "<td align='center'  class='table_line' nowrap><b>Abertura</b></td>";
	echo "<td align='center'  class='table_line' nowrap><b>Dias</b></td>";
	echo "<td align='center'  class='table_line' nowrap><b>Cliente</b></td>";
	echo "<td align='center'  class='table_line' nowrap><b>Posto</b></td>";
	echo "</tr>";

	$sql = "SELECT		tbl_produto.referencia                       ,
					tbl_produto.descricao                        ,
					tbl_os.sua_os AS sua_os,
					tbl_os.consumidor_nome AS cliente,
					tbl_os.posto AS posto,
					tbl_posto.nome AS nome_posto,
					tbl_os.os AS os,
					TO_CHAR(tbl_os.data_abertura,'DD/MM/YYYY') AS data_abertura,
					TO_CHAR(tbl_os.data_nf,'DD/MM/YYYY') AS data_nf,
					tbl_os.data_abertura - tbl_os.data_nf AS dias
			FROM tbl_os
			JOIN	tbl_produto USING (produto)
			JOIN tbl_posto USING(posto)
			JOIN tbl_os_extra USING(os)
			WHERE	to_char(tbl_os.data_abertura,'YYYY') = $ano
			AND tbl_os.fabrica = $login_fabrica
			AND tbl_os.produto=$produto
			AND tbl_os.excluida IS NOT NULL
			AND tbl_os_extra.extrato IS NOT NULL
			ORDER BY tbl_os.data_abertura ASC
			";
	$res = pg_exec ($con,$sql);
	
	if (pg_numrows($res) > 0) {
		for ($i=0; $i<pg_numrows($res); $i++){
			$referencia = trim(pg_result($res,$i,referencia));
			$descricao = trim(pg_result($res,$i,descricao));
			$sua_os  = trim(pg_result($res,$i,sua_os));
			$os  = trim(pg_result($res,$i,os));
			$data_abertura  = trim(pg_result($res,$i,data_abertura));
			$data_nf  = trim(pg_result($res,$i,data_nf));
			$dias  = trim(pg_result($res,$i,dias));
			$posto  = trim(pg_result($res,$i,posto));
			$posto_nome  = trim(pg_result($res,$i,nome_posto));
			$cliente  = trim(pg_result($res,$i,cliente));

			echo "<TR>";
			echo "	<TD class='conteudo".(($i%2)+1)."' align='left' nowrap><a href='os_press.php?os=$os' target='_blank'>$sua_os</a></TD>";
			echo "	<TD class='conteudo".(($i%2)+1)."' align='center' nowrap>$data_nf</TD>";
			echo "	<TD class='conteudo".(($i%2)+1)."' align='center' nowrap>$data_abertura</TD>";
			echo "	<TD class='conteudo".(($i%2)+1)."' align='center' nowrap>$dias</TD>";
			echo "	<TD class='conteudo".(($i%2)+1)."' align='left' nowrap>$cliente</TD>";
			echo "	<TD class='conteudo".(($i%2)+1)."' align='left' nowrap>$posto_nome</TD>";
			//echo "	<TD class='conteudo".(($i%2)+1)."' align='center' nowrap>$data</TD>";
			echo "</TR>";

		}
	}
	echo "</table>";
	
	
	echo "<br><br>";
}
?>

<? include "rodape.php" ?>