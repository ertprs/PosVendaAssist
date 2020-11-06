<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="gerencia";
include 'autentica_admin.php';

if ($login_fabrica <> 3) {
	echo "�rea restrita.";
	exit;
}

include 'funcoes.php';

$msg_erro = "";

$layout_menu = "gerencia";
$title = "Relat�rio";

include "cabecalho.php";

include "javascript_pesquisas.php";

?>

<script language="JavaScript" src="js/cal2.js"></script>
<script language="JavaScript" src="js/cal_conf2.js"></script>

<style type="text/css">
.menu_top_5 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
	background-color: #d9e2ef
}
.menu_top_5_20 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
	background-color: #99CC00
}
.menu_top_20 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
	background-color: #FFFFCC
}
.menu_top_30 {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: bold;
	border: 1px solid;
	color:#000000;
	background-color: #FF6600
}

.topo {
	text-align: center;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	border: 1px solid;
	color:#ffffff;
	background-color: #596D9B
}


.conteudoleft {
	text-align: left;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: normal;
	color:#000000;
}

.conteudoright {
	text-align: right;
	font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;
	font-size: x-small;
	font-weight: bold;
	color:#000000;
}


</style>

<p>

<?
$meses = array(1 => "Janeiro", "Fevereiro", "Mar�o", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

$btn_acao = strtolower($_POST['btn_acao']);

$mes = trim($_POST["mes"]);
$ano = trim($_POST["ano"]);

if (strlen($mes) == 0 AND strlen($ano) == 0 AND strlen($btn_acao) > 0)
	$msg_erro = " Selecione a data. ";

if (strlen($msg_erro) > 0) { ?>
<table width='500' align='center' border='0' cellspacing='2' cellpadding='2'>
	<tr class='error'>
		<td><?echo $msg_erro?></td>
	</tr>
</table>
<? } ?>

<form name='frm_os_posto' action='<? echo $PHP_SELF ?>' method="POST">
<input type="hidden" name='btn_acao' value="">

<table width='500' align='center' border='0' cellspacing='2' cellpadding='2'>
	<tr class='topo'>
		<td colspan='2'>Seleciona o m�s e o ano</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>M�s</td>
		<td>Ano</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td>
			<select name="mes" size="1" class="frm">
				<?
				for ($i = 0 ; $i <= count($meses) ; $i++) {
					echo "<option value='$i'";
					if ($mes == $i) echo " selected";
					echo ">" . $meses[$i] . "</option>";
				}
				?>
			</select>
		</td>
		<td>
			<select name="ano" size="1" class="frm">
				<option value=''></option>
				<?
				for ($i = 2003 ; $i <= date("Y") ; $i++) {
					echo "<option value='$i'";
					if ($ano == $i) echo " selected";
					echo ">$i</option>";
				}
				?>
			</select>
		</td>
	</tr>
</table>

<center>
<img src='imagens_admin/btn_confirmar.gif' style="cursor:pointer" onclick="javascript: if (document.frm_os_posto.btn_acao.value == '' ) { document.frm_os_posto.btn_acao.value='continuar' ; document.frm_os_posto.submit() } else { alert ('Aguarde submiss�o') }" ALT="Confirmar" border='0'>
</center>

</form>

<br>

<?

if (strlen($btn_acao) > 0 AND strlen($_POST['mes']) > 0 AND strlen($_POST['ano']) > 0 AND strlen($msg_erro) == 0){

	echo "<center>Por favor, aguarde. O sistema est� gerando o relat�rio.</center><br>";

	if (strlen(trim($_POST['mes'])) > 0){
		$mes = trim($_POST['mes']);
		$ano = trim($_POST['ano']);
		if (strlen($mes) == 1) $mes = "0".$mes;
	}


	$data_inicial = date("Y-m-01 00:00:00", mktime(0, 0, 0, $mes, 1, $ano));
	$data_final   = date("Y-m-t 23:59:59", mktime(0, 0, 0, $mes, 1, $ano));

	echo "<table width='400' align='center' border='0' cellspacing='2' cellpadding='2'>";
	echo "<tr>";
	echo "<td colspan='2'><H3>RELAT�RIO GERAL $mes / $ano</H3></td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td COLSPAN=2><H4>Ordens em Aberto</H4></td>";
	echo "</tr>";

	$sql = "SELECT count(*) from (
			SELECT DISTINCT tbl_os.os 
			FROM tbl_os 
			left join tbl_os_produto using (os) 
			left join tbl_os_item using (os_produto) 
			where tbl_os.fabrica = $login_fabrica 
			and tbl_os.data_digitacao between '$data_inicial' AND '$data_final' 
			and tbl_os.excluida is not true 
			and data_fechamento is null 
			and tbl_os_item.peca is null) abertas " ;
	$res = pg_exec ($con,$sql);
	$qtde_sem_pedido = pg_result($res,0,0);
	//echo $qtde_sem_pedido . " OS abertas mas sem pedido de pe�as <br>";

	echo "<tr>";
	echo "<td class='conteudoleft'>OS abertas mas sem pedido de pe�as</td>";
	echo "<td class='conteudoright'>$qtde_sem_pedido</td>";
	echo "</tr>";

	flush();

	$sql = "SELECT COUNT(*) from (
				SELECT DISTINCT tbl_os.os 
				FROM tbl_os 
				left join tbl_os_produto using (os) 
				left join tbl_os_item using (os_produto) 
				where tbl_os.fabrica = $login_fabrica 
				and tbl_os.data_digitacao between '$data_inicial' AND '$data_final' 
				and tbl_os.excluida is not true 
				and data_fechamento is null) abertas " ;
	$res = pg_exec ($con,$sql);
	$qtde_abertas = pg_result($res,0,0);
	//echo $qtde_abertas - $qtde_sem_pedido . " OS aguardando pe�as <br>";
	//echo $qtde_abertas . " Total de OS em aberto <br>";

	$TOT = $qtde_abertas - $qtde_sem_pedido;

	echo "<tr>";
	echo "<td class='conteudoleft'>OS aguardando pe�as</td>";
	echo "<td class='conteudoright'>$TOT</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class='conteudoleft'>Total de OS em aberto</td>";
	echo "<td class='conteudoright'>$qtde_abertas</td>";
	echo "</tr>";

	flush();

	echo "<tr>";
	echo "<td COLSPAN=2>&nbsp;</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td COLSPAN=2><H4>Pend�ncia Total de Pe�as </H4></td>";
	echo "</tr>";

	$sql = "SELECT SUM (tbl_pedido_item.qtde - tbl_pedido_item.qtde_cancelada - tbl_pedido_item.qtde_faturada) 
		FROM tbl_pedido 
		JOIN tbl_pedido_item using (pedido) 
		WHERE tbl_pedido.fabrica = $login_fabrica
		AND (tbl_pedido.tipo_pedido = 3 OR (tbl_pedido.tipo_pedido = 2 and (tbl_pedido.distribuidor is null or tbl_pedido.distribuidor = tbl_pedido.posto)))
		AND tbl_pedido.data BETWEEN '$data_inicial' AND '$data_final' 
		AND tbl_pedido.recebido_fabrica IS NOT NULL";
	$res = pg_exec ($con,$sql);
	$qtde = pg_result($res,0,0);
	//echo $qtde . " pe�as <br>";
	//echo "<br><br><br>";

	echo "<tr>";
	echo "<td class='conteudoleft'>Pe�as </td>";
	echo "<td class='conteudoright'>$qtde</td>";
	echo "</tr>";

	flush();

	echo "<tr>";
	echo "<td COLSPAN=2>&nbsp;</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td COLSPAN=2><H4>Ordens de Servi�o </H4></td>";
	echo "</tr>";

	$sql = "SELECT COUNT(*) FROM tbl_os WHERE fabrica = $login_fabrica AND excluida IS NOT TRUE AND data_digitacao BETWEEN '$data_inicial' AND '$data_final' ";
	$res = pg_exec ($con,$sql);
	$qtde = pg_result($res,0,0);
	//echo $qtde . " OS digitadas <br>";

	echo "<tr>";
	echo "<td class='conteudoleft'>OS digitadas </td>";
	echo "<td class='conteudoright'>$qtde</td>";
	echo "</tr>";

	flush();

	$sql = "SELECT COUNT(*) FROM tbl_os JOIN tbl_produto USING (produto) WHERE fabrica = $login_fabrica AND excluida IS NOT TRUE AND data_digitacao BETWEEN '$data_inicial' AND '$data_final' AND tbl_produto.linha = 3 ";
	$res = pg_exec ($con,$sql);
	$qtde = pg_result($res,0,0);
	//echo "Sendo " . $qtde . " de �udio, ";

	echo "<tr>";
	echo "<td class='conteudoleft'>�udio</td>";
	echo "<td class='conteudoright'>$qtde</td>";
	echo "</tr>";

	flush();

	$sql = "SELECT COUNT(*) FROM tbl_os JOIN tbl_produto USING (produto) WHERE fabrica = $login_fabrica AND excluida IS NOT TRUE AND data_digitacao BETWEEN '$data_inicial' AND '$data_final' AND tbl_produto.linha = 2 ";
	$res = pg_exec ($con,$sql);
	$qtde = pg_result($res,0,0);
	//echo $qtde . " de Eletro, ";

	echo "<tr>";
	echo "<td class='conteudoleft'>Eletro</td>";
	echo "<td class='conteudoright'>$qtde</td>";
	echo "</tr>";

	flush();

	$sql = "SELECT COUNT(*) FROM tbl_os JOIN tbl_produto USING (produto) WHERE fabrica = $login_fabrica AND excluida IS NOT TRUE AND data_digitacao BETWEEN '$data_inicial' AND '$data_final' AND tbl_produto.linha = 4 ";
	$res = pg_exec ($con,$sql);
	$qtde = pg_result($res,0,0);
	//echo $qtde . " Branca e ";

	echo "<tr>";
	echo "<td class='conteudoleft'>Branca</td>";
	echo "<td class='conteudoright'>$qtde</td>";
	echo "</tr>";

	flush();

	$sql = "SELECT COUNT(*) FROM tbl_os JOIN tbl_produto USING (produto) WHERE fabrica = $login_fabrica AND excluida IS NOT TRUE AND data_digitacao BETWEEN '$data_inicial' AND '$data_final' AND tbl_produto.linha = 212 ";
	$res = pg_exec ($con,$sql);
	$qtde = pg_result($res,0,0);
	//echo $qtde . " Autor�dio. <br>";

	echo "<tr>";
	echo "<td class='conteudoleft'>Autor�dio</td>";
	echo "<td class='conteudoright'>$qtde</td>";
	echo "</tr>";

	flush();

	$sql = "SELECT COUNT(*) FROM tbl_pedido WHERE fabrica = $login_fabrica AND data BETWEEN '$data_inicial' AND '$data_final' AND tipo_pedido = 3 ";
	$res = pg_exec ($con,$sql);
	$qtde = pg_result($res,0,0);
	//echo $qtde . " Pedidos de Pe�a em Garantia <br>";

	echo "<tr>";
	echo "<td class='conteudoleft'>Pedidos de Pe�a em Garantia </td>";
	echo "<td class='conteudoright'>$qtde</td>";
	echo "</tr>";

	flush();

	$sql = "SELECT SUM(tbl_pedido_item.qtde) FROM tbl_pedido JOIN tbl_pedido_item USING (pedido) WHERE tbl_pedido.fabrica = $login_fabrica AND tbl_pedido.data BETWEEN '$data_inicial' AND '$data_final' AND tbl_pedido.tipo_pedido = 3 ";
	$res = pg_exec ($con,$sql);
	$qtde = pg_result($res,0,0);
	//echo $qtde . " Pe�as solicitadas em Garantia <br>";

	echo "<tr>";
	echo "<td class='conteudoleft'>Pe�as solicitadas em Garantia </td>";
	echo "<td class='conteudoright'>$qtde</td>";
	echo "</tr>";

	flush();

	$sql = "SELECT trim (to_char (SUM(tbl_pedido.total),'999,999.99')) FROM tbl_pedido WHERE fabrica = $login_fabrica AND data BETWEEN '$data_inicial' AND '$data_final' AND tipo_pedido = 3 ";
	$res = pg_exec ($con,$sql);
	$qtde = pg_result($res,0,0);
	//echo "R\$ " . $qtde . "<br><br>";

	echo "<tr>";
	echo "<td class='conteudoleft'>Valor R$ </td>";
	echo "<td class='conteudoright'>".$qtde."</td>";
	echo "</tr>";

	flush();

	$sql = "SELECT SUM (qtde) FROM tbl_faturamento_item JOIN tbl_pedido USING (pedido) JOIN tbl_faturamento USING (faturamento) WHERE tbl_pedido.fabrica = $login_fabrica AND tbl_pedido.tipo_pedido = 3 AND tbl_faturamento.emissao BETWEEN '$data_inicial' AND '$data_final' ";
	$res = pg_exec ($con,$sql);
	$qtde = pg_result($res,0,0);
	//echo $qtde . " Pe�as faturadas em Garantia <br>";

	echo "<tr>";
	echo "<td class='conteudoleft'>Pe�as faturadas em Garantia</td>";
	echo "<td class='conteudoright'>$qtde</td>";
	echo "</tr>";

	flush();

	$sql = "SELECT trim (to_char (SUM (qtde * preco),'999,999,999.99')) FROM tbl_faturamento_item JOIN tbl_pedido USING (pedido) JOIN tbl_faturamento USING (faturamento) WHERE tbl_pedido.fabrica = $login_fabrica AND tbl_pedido.tipo_pedido = 3 AND tbl_faturamento.emissao BETWEEN '$data_inicial' AND '$data_final' ";
	$res = pg_exec ($con,$sql);
	$qtde = pg_result($res,0,0);
	//echo "R\$ " . $qtde . "<br><br><br>";

	echo "<tr>";
	echo "<td class='conteudoleft'>Valor R$ </td>";
	echo "<td class='conteudoright'>".$qtde."</td>";
	echo "</tr>";

	flush();

	$sql = "SELECT COUNT(*) FROM tbl_os WHERE fabrica = $login_fabrica AND excluida IS NOT TRUE AND data_fechamento BETWEEN '$data_inicial' AND '$data_final' ";
	$res = pg_exec ($con,$sql);
	$qtde = pg_result($res,0,0);
	//echo $qtde . " OS fechadas <br>";

	echo "<tr>";
	echo "<td class='conteudoleft'>OS fechadas </td>";
	echo "<td class='conteudoright'>$qtde</td>";
	echo "</tr>";

	flush();

	$sql = "SELECT trim (to_char (SUM(tbl_os.mao_de_obra),'999,999.99')) FROM tbl_os WHERE fabrica = $login_fabrica AND excluida IS NOT TRUE AND data_fechamento BETWEEN '$data_inicial' AND '$data_final' ";
	$res = pg_exec ($con,$sql);
	$qtde = pg_result($res,0,0);
	//echo "R\$ " . $qtde . " de M�o-de-Obra <br><br><br><br><br>";

	echo "<tr>";
	echo "<td class='conteudoleft'>Valor R$ </td>";
	echo "<td class='conteudoright'>".$qtde."</td>";
	echo "</tr>";

	flush();

	echo "<tr>";
	echo "<td COLSPAN=2>&nbsp;</td>";
	echo "</tr>";

	echo "<tr>";
	echo "<td COLSPAN=2><H4>Pe�as Vendidas </H4></td>";
	echo "</tr>";

	$sql = "SELECT COUNT(*) FROM tbl_pedido WHERE fabrica = $login_fabrica AND data BETWEEN '$data_inicial' AND '$data_final' AND tipo_pedido = 2 ";
	$res = pg_exec ($con,$sql);
	$qtde = pg_result($res,0,0);
	//echo $qtde . " Pedidos de Venda <br>";

	echo "<tr>";
	echo "<td class='conteudoleft'>Pedidos de Venda  </td>";
	echo "<td class='conteudoright'>$qtde</td>";
	echo "</tr>";

	flush();

	$sql = "SELECT SUM(tbl_pedido_item.qtde) FROM tbl_pedido JOIN tbl_pedido_item USING (pedido) WHERE tbl_pedido.fabrica = $login_fabrica AND tbl_pedido.data BETWEEN '$data_inicial' AND '$data_final' AND tbl_pedido.tipo_pedido = 2 ";
	$res = pg_exec ($con,$sql);
	$qtde = pg_result($res,0,0);
	//echo $qtde . " Pe�as vendidas <br>";

	echo "<tr>";
	echo "<td class='conteudoleft'>Pe�as vendidas </td>";
	echo "<td class='conteudoright'>$qtde</td>";
	echo "</tr>";

	flush();

	$sql = "SELECT trim (to_char (SUM(tbl_pedido.total),'999,999.99')) FROM tbl_pedido WHERE fabrica = $login_fabrica AND data BETWEEN '$data_inicial' AND '$data_final' AND tipo_pedido = 2 ";
	$res = pg_exec ($con,$sql);
	$qtde = pg_result($res,0,0);
	//echo "R\$ " . $qtde . "<br><br><br>";

	echo "<tr>";
	echo "<td class='conteudoleft'>Valor R$ </td>";
	echo "<td class='conteudoright'>".$qtde."</td>";
	echo "</tr>";

	flush();

	$sql = "SELECT SUM (qtde) FROM tbl_faturamento_item JOIN tbl_pedido USING (pedido) JOIN tbl_faturamento USING (faturamento) WHERE tbl_pedido.fabrica = $login_fabrica AND tbl_pedido.tipo_pedido = 2 AND tbl_faturamento.emissao BETWEEN '$data_inicial' AND '$data_final' ";
	$res = pg_exec ($con,$sql);
	$qtde = pg_result($res,0,0);
	//echo $qtde . " Pe�as faturadas em Venda <br>";

	echo "<tr>";
	echo "<td class='conteudoleft'>Pe�as faturadas em Venda </td>";
	echo "<td class='conteudoright'>$qtde</td>";
	echo "</tr>";

	flush();

	$sql = "SELECT trim (to_char (SUM (qtde * preco),'999,999.99')) FROM tbl_faturamento_item JOIN tbl_pedido USING (pedido) JOIN tbl_faturamento USING (faturamento) WHERE tbl_pedido.fabrica = $login_fabrica AND tbl_pedido.tipo_pedido = 2 AND tbl_faturamento.emissao BETWEEN '$data_inicial' AND '$data_final' ";
	$res = pg_exec ($con,$sql);
	$qtde = pg_result($res,0,0);
	//echo "R\$ " . $qtde . "<br><br><br>";

	echo "<tr>";
	echo "<td class='conteudoleft'>Valor R$ </td>";
	echo "<td class='conteudoright'>".$qtde."</td>";
	echo "</tr>";

	flush();

	echo "</table>";

}

echo "<br>";
	
include "rodape.php"; 

?>