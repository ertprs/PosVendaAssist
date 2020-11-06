<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "includes/funcoes.php";

$admin_privilegios="gerencia";
include 'autentica_admin.php';

$erro = "";

$meses = array (1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (strlen($_GET["acao"]) > 0) $acao = strtoupper($_GET["acao"]);

if ($acao == "PESQUISAR") {
	$mes   = trim($_GET["mes"]);
	$ano   = trim($_GET["ano"]);
	
	if (strlen($mes) == 0) $msg .= " Favor informar o mês. ";
	if (strlen($ano) == 0) $msg .= " Favor informar o ano. ";
}

$layout_menu = "gerencia";
$title = strtoupper("RelatÓrio de OS e PeÇas digitadas");

include "cabecalho.php";
?>

<style type="text/css">
.titulo_tabela{
	background-color:#596d9b;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.titulo_coluna{
	background-color:#596d9b;
	font: bold 11px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.msg_erro{
	background-color:#FF0000;
	font: bold 14px "Arial";
	color:#FFFFFF;
	text-align:center;
}

.formulario{
	background-color:#D9E2EF;
	font:11px Arial;
	text-align:left;
}

table.tabela tr td{
	font-family: verdana;
	font-size: 11px;
	border-collapse: collapse;
	border:1px solid #596d9b;
}

.texto_avulso{
	font: 14px Arial; color: rgb(89, 109, 155);
	background-color: #d9e2ef;
	text-align: center;
	width:700px;
	margin: 0 auto;
	border-collapse: collapse;
	border:1px solid #596d9b;
}
</style>


<script language="JavaScript">
function FuncMouseOver (linha, cor) {
	linha.style.cursor = "hand";
	linha.style.backgroundColor = cor;
}
function FuncMouseOut (linha, cor) {
	linha.style.cursor = "default";
	linha.style.backgroundColor = cor;
}
</script>

<? if (strlen($erro) > 0) { ?>
<table width="700" border="0" cellpadding="0" cellspacing="1" align="center">
	<tr>
		<td class="msg_erro"><? echo $erro; ?></td>
	</tr>
</table>
<? } ?>

<form name="frm_pesquisa" method="get" action="<? echo $PHP_SELF ?>">

<input type="hidden" name="acao">

<table width="700" border="0" cellspacing="1" cellpadding="0" align="center" class="formulario">
	<tr>
		<td colspan="3" class="titulo_tabela">Parâmetros de Pesquisa</td>
	</tr>
	<tr><td>&nbsp;</td></tr>
	<tr>
		<td width="35%">&nbsp;</td>
		<td width="120px">Mês</td>
		<td>Ano</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>
			<select name="mes" size="1" class="frm">
				<option value=''></option>
				<?
					for ($i = 1 ; $i <= count($meses) ; $i++) {
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
	<tr>
		<td colspan="3" align="center" style="padding:10px 0 10px;">
			<input type="button" onclick="javascript: document.frm_pesquisa.acao.value='PESQUISAR'; document.frm_pesquisa.submit();" style="cursor: pointer;" value="Pesquisar" />
		</td>
	</tr>
</table>

</form>
<br />
<?
flush();
if (strlen($acao) > 0 && strlen($erro) == 0) {
	$data_inicial = date("Y-m-01", mktime(0, 0, 0, $mes, 1, $ano));
	$data_final   = date("Y-m-t", mktime(0, 0, 0, $mes, 1, $ano));
	echo "<table width='700' border='0' cellpadding='0' cellspacing='1' class='tabela' align='center'>";
	echo "<tr height='20' class='titulo_coluna'>";
	echo "<td> Qtde OS</td>";
	echo "<td> Qtde Peça </td>";
	echo "</tr>";
	$cor = ($x % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
	echo "<tr bgcolor='$cor'>";

	$sql =	"

		SELECT COUNT(*) AS qtde_os
		FROM  tbl_os 
		WHERE fabrica    = $login_fabrica
		AND   excluida IS NOT TRUE
		AND   data_digitacao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59';";
	$resO = pg_exec ($con,$sql);

	echo "<td>" . trim(pg_result($resO,0,qtde_os)) . "</td>";
	flush();
	$sql =	"SELECT SUM(qtde) AS qtde_peca
		FROM tbl_os
		JOIN tbl_os_produto ON tbl_os.os = tbl_os_produto.produto  
		JOIN tbl_os_item    ON tbl_os_item.os_produto = tbl_os_produto.os_produto
		WHERE tbl_os.fabrica = $login_fabrica
		AND   data_digitacao BETWEEN '$data_inicial 00:00:00' AND '$data_final 23:59:59';";
	$resP = pg_exec ($con,$sql);
	flush();
	echo "<td>" . trim(pg_result($resP,0,qtde_peca)) . "</td>";
	echo "</tr>";
	echo "</table>";
}

include "rodape.php";
?>
