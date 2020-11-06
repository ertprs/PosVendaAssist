<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "gerencia,call_center";
include "autentica_admin.php";

include "funcoes.php";

$layout_menu = "auditoria";

$title = "Visão geral por produto";
#include 'cabecalho.php';

?>

<style type="text/css">
<!--
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}
-->
</style>


<script LANGUAGE="JavaScript">
	function Redirect(produto, data_i, data_f, mobra) {
		window.open('rel_new_visao_geral_peca.php?produto=' + produto + '&data_i=' + data_i + '&data_f=' + data_f + '&mobra=' + mobra,'1', 'height=400,width=750,location=no,scrollbars=yes,menubar=no,toolbar=no,resizable=no')
	}
</script>

<script LANGUAGE="JavaScript">
	function Redirect1(produto, data_i, data_f) {
		window.open('rel_new_visao_os.php?produto=' + produto + '&data_i=' + data_i + '&data_f=' + data_f + '&estado=<? echo $estado; ?>','1', 'height=400,width=750,location=no,scrollbars=yes,menubar=no,toolbar=no,resizable=no')
	}
</script>

<script language="JavaScript" src="js/cal2.js"></script>
<script language="JavaScript" src="js/cal_conf2.js"></script>

<p>

<? if (strlen($erro) > 0) { ?>
<table width="420" border="0" cellpadding="2" cellspacing="0" align="center" class="error">
	<tr>
		<td><?echo $erro?></td>
	</tr>
</table>
<br>
<? } ?>

<br>

<?
$x_data_inicial = $_GET['data_inicial'];
$x_data_final   = $_GET['data_final'];
$produto        = $_GET['produto'];
$referencia     = $_GET['referencia'];
$voltagem       = $_GET['voltagem'];
$linha          = $_GET['linha'];
$estado         = $_GET['estado'];

$cond_linha     = "1=1";
if (strlen ($linha) > 0) $cond_linha = " tbl_produto.linha = $linha ";

$cond_estado    = "1=1";
$cond_estado2   = "1=1";
if (strlen ($estado) > 0) {
	$cond_estado  = " tbl_posto.estado = '$estado' ";
	$cond_estado2 = " black_antigo_item.estado = '$estado' ";
}



$sql = "SELECT descricao, produto
		FROM tbl_produto 
		WHERE tbl_produto.produto = $produto ";
$res = pg_exec ($con,$sql);

$produto = pg_result ($res,0,produto);

?>

<center>
<font face='arial' color='<? echo $cor_forte ?>'><b><?= "$referencia - $voltagem - " . pg_result ($res,0,descricao) ?></b></font>
<center>

<?
if ($relatorio == "gerar" OR 1==1 ) {
	
	flush();
	
	$sql = "SELECT  tbl_peca.referencia  AS referencia ,
					tbl_peca.descricao                 ,
					SUM (tbl_os_item.qtde) AS qtde     ,
					SUM (tbl_os_item.qtde * tbl_os_item.custo_peca) AS preco
			FROM    tbl_peca
			JOIN    tbl_os_item    ON tbl_os_item.peca       = tbl_peca.peca
			JOIN    tbl_os_produto ON tbl_os_item.os_produto = tbl_os_produto.os_produto
			JOIN    tbl_os         ON tbl_os_produto.os      = tbl_os.os
			JOIN    tbl_os_extra   ON tbl_os.os              = tbl_os_extra.os
			JOIN    tbl_produto    ON tbl_produto.produto    = tbl_os.produto
			JOIN    tbl_posto      ON tbl_posto.posto        = tbl_os.posto
			JOIN    tbl_extrato    ON tbl_os_extra.extrato   = tbl_extrato.extrato
			JOIN    tbl_extrato_financeiro ON tbl_os_extra.extrato = tbl_extrato_financeiro.extrato
			WHERE   tbl_os.fabrica = $login_fabrica
			AND     tbl_os_item.servico_realizado = 90
			AND     tbl_extrato_financeiro.data_envio BETWEEN '$x_data_inicial' AND '$x_data_final'
			AND     tbl_produto.produto = $produto
			AND     $cond_linha
			AND     $cond_estado
			GROUP BY tbl_peca.referencia, tbl_peca.descricao
			UNION
			SELECT black_antigo_item.peca_referencia        AS referencia  ,
				   tbl_peca.descricao                                      ,
				   SUM (black_antigo_item.qtde)             AS qtde        ,
				   SUM (black_antigo_item.qtde * black_antigo_item.preco) AS preco
			FROM black_antigo_item
			LEFT JOIN tbl_peca    ON black_antigo_item.peca_referencia = tbl_peca.referencia AND tbl_peca.fabrica = $login_fabrica
			LEFT JOIN tbl_produto ON black_antigo_item.produto         = tbl_produto.produto
			WHERE black_antigo_item.data_financeiro BETWEEN '$x_data_inicial' AND '$x_data_final'
			AND   black_antigo_item.produto = $produto
			AND   $cond_linha
			AND   $cond_estado2
			GROUP BY black_antigo_item.peca_referencia , tbl_peca.descricao
			ORDER BY referencia;

			";

#			JOIN    tbl_extrato_financeiro ON tbl_extrato.extrato = tbl_extrato_financeiro.extrato
#			AND     tbl_extrato_financeiro.data_envio BETWEEN '$x_data_inicial' AND '$x_data_final'
#

#echo $sql;

/*

			
			INTO TEMP TABLE black_antigo

			SELECT referencia  ,
				   produto     ,
				   nome        ,
				   voltagem    ,
				   SUM (ocorrencia)  AS ocorrencia  ,
				   SUM (mao_de_obra) AS mao_de_obra ,
				   SUM (pecas)       AS pecas
			FROM black_antigo
			GROUP BY referencia, produto, nome, voltagem
			ORDER BY referencia ;
*/


	$res = pg_exec ($con,$sql);

	if (pg_numrows($res) > 0) {
		echo "<table width='700' border='0' cellpadding='2' cellspacing='2' align='center'>";
		echo "<tr>";
		
		echo "<td bgcolor='#B6DADA' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>Peça</b></font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>Referência</b></font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>Qtde</b></font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>Custo</b></font>";
		echo "</td>";

		echo "<td bgcolor='#B6DADA' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>%</b></font>";
		echo "</td>";
		
		echo "</tr>";
		
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$total_ocorrencia = $total_ocorrencia + pg_result($res,$x,qtde);
			$total_peca       = $total_peca + pg_result($res,$x,preco);
		}
		
		for ($x = 0; $x < pg_numrows($res); $x++) {
			$referencia = pg_result($res,$x,referencia);
			$descricao  = pg_result($res,$x,descricao);
			$qtde       = pg_result($res,$x,qtde);
			$preco      = pg_result($res,$x,preco);
			
			if ($preco > 0 AND $total_peca > 0) {
				$porcentagem = ($preco / $total_peca * 100);
			}
			
			$cor = '#EFF5F5';
			
			if ($x % 2 == 0) $cor = '#B6DADA';
			
			echo "<tr>";
			
			echo "<td bgcolor='$cor' align='left' nowrap>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo substr(pg_result($res,$x,descricao),0,45);
			echo "</font>";
			echo "</td>";
			
			echo "<td bgcolor='$cor' align='left' nowrap>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2' nowrap>";
			echo $referencia ;
			echo "</font>";
			echo "</td>";
			
			echo "<td bgcolor='$cor' align='right'>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo number_format($qtde,0,",",".");
			echo "</td>";
			
			echo "<td bgcolor='$cor' align='right'>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo number_format($preco,2,",",".");
			echo "</td>";
			
			echo "<td bgcolor='$cor' align='center'>";
			echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>";
			echo number_format($porcentagem,2,",",".");
			echo "</font>";
			echo "</td>";
			
			echo "</tr>";
			
		}
		echo "<tr>";
		
		echo "<td bgcolor='#B6DADA' align='left' colspan='2'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'><b>TOTAL</b></font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='right'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>". number_format($total_ocorrencia,0,",",".") ."</font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='right'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>". number_format($total_peca,2,",",".") ."</font>";
		echo "</td>";
		
		echo "<td bgcolor='#B6DADA' align='center'>";
		echo "<font face='Verdana, Arial, Helvetica, sans' color='#000000' size='2'>100%</font>";
		echo "</td>";
		
		echo "</tr>";
		echo "</table>";
		
	}
}


echo "<p>";

if (strlen($meu_grafico) > 0) {
	echo $meu_grafico;
}

echo "<p>";

#include 'rodape.php';
?>
