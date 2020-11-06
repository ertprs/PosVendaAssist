<?
include "dbconfig.php";
include "includes/dbconnect-inc.php";
include "autentica_usuario.php";

include "funcoes.php";

$erro = "";

if (strlen($_POST["acao"]) > 0 ) $acao = strtoupper($_POST["acao"]);
if (strlen($_GET["acao"]) > 0 )  $acao = strtoupper($_GET["acao"]);

$meses = array(1 => "Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");

if (strlen($acao) > 0 && $acao == "PESQUISAR") {
	if (strlen(trim($_POST["mes"])) > 0) $mes = trim($_POST["mes"]);
	if (strlen(trim($_GET["mes"])) > 0)  $mes = trim($_GET["mes"]);
	if (strlen(trim($_POST["ano"])) > 0) $ano = trim($_POST["ano"]);
	if (strlen(trim($_GET["ano"])) > 0)  $ano = trim($_GET["ano"]);
	
	if ($mes == 0) $erro .= " Selecione o mês para realizar a pesquisa. ";
	if (strlen($ano) == 0) $erro .= " Selecione o ano para realizar a pesquisa. ";
	
	if (strlen($erro) == 0) {
		$data_inicial = date("Y-m-01", mktime(0, 0, 0, $mes, 1, $ano));
		$data_final   = date("Y-m-t", mktime(0, 0, 0, $mes, 1, $ano));
	}
	
	$link_status = "http://" . $HTTP_HOST . $REQUEST_URI . "?data_inicial=" . $_POST["data_inicial"] . "&data_final=" . $_POST["data_final"] . "&acao=PESQUISAR";
	setcookie("LinkStatus", $link_status);
}

$layout_menu = "os";
$title = "Devolução de Peças";

include "cabecalho.php";
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12 px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12 px;
	font-weight: normal;
}
</style>

<script language="javascript" src="js/cal2.js"></script>
<script language="javascript" src="js/cal_conf2.js"></script>

<br>

<? if (strlen($erro) > 0) { ?>
<table width="600" border="0" cellspacing="0" cellpadding="2" align="center" class="Error">
	<tr>
		<td><?echo $erro?></td>
	</tr>
</table>
<br>
<? } ?>

<form name="frm_relatorio" method="post" action="<?echo $PHP_SELF?>">
<input type="hidden" name="acao">
<table width="400" border="0" cellspacing="0" cellpadding="2" align="center">
	<tr class="Titulo">
		<td colspan="4"><b>PESQUISE ENTRE DATAS</b></td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
		<td align='left'>Mês</td>
		<td align='left'>Ano</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr class="Conteudo" bgcolor="#D9E2EF">
		<td width="10">&nbsp;</td>
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
				<option value='2005' <? if ($ano == "2005") echo " selected"; ?>>2005</option>
			</select>
		</td>
		<td width="10">&nbsp;</td>
	</tr>
	<tr bgcolor="#D9E2EF">
		<td colspan="4"><img border="0" src="imagens/btn_pesquisar_400.gif" onClick="document.frm_relatorio.acao.value='PESQUISAR'; document.frm_relatorio.submit();" style="cursor: hand;" alt="Preencha as opções e clique aqui para pesquisar"></td>
	</tr>
</table>

<br>

<?
if (strlen($acao) > 0 && strlen($erro) == 0) {
	##### OS FINALIZADAS #####

	$sql =	"SELECT tbl_posto_fabrica.codigo_posto ,
					tbl_os.sua_os                                                    ,
					tbl_peca.referencia        AS peca_referencia,
					tbl_peca.descricao         AS peca_descricao,
					tbl_os_item.qtde
			FROM tbl_os
			JOIN tbl_os_produto         ON  tbl_os_produto.os         = tbl_os.os
			JOIN tbl_os_item            ON  tbl_os_item.os_produto    = tbl_os_produto.os_produto
			JOIN tbl_servico_realizado  ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado
			JOIN tbl_peca               ON  tbl_os_item.peca          = tbl_peca.peca
			JOIN tbl_posto              ON  tbl_posto.posto           = tbl_os.posto
			JOIN tbl_posto_fabrica      ON  tbl_posto_fabrica.posto   = tbl_posto.posto
										AND tbl_posto_fabrica.fabrica = $login_fabrica
			JOIN tbl_os_extra           ON  tbl_os_extra.os           = tbl_os.os
			JOIN tbl_extrato            ON  tbl_extrato.extrato       = tbl_os_extra.extrato
			WHERE tbl_extrato.aprovado::date BETWEEN '$data_inicial' AND '$data_final'
			AND tbl_os.posto   = $login_posto
			AND tbl_os.fabrica = $login_fabrica
			AND tbl_servico_realizado.servico_realizado = 62
			ORDER BY tbl_os.sua_os, tbl_peca.referencia;";
	$res = pg_exec($con,$sql);
	
	if (getenv("REMOTE_ADDR") == "201.0.9.216") echo nl2br($sql) . "<br>" . pg_numrows($res);
	
	if (pg_numrows($res) > 0) {
		echo "<table width='600' border='1' cellpadding='2' cellspacing='0' style='border-collapse: collapse' bordercolor='#000000'>";
		echo "<tr class='Titulo'>";
		echo "<td>OS</td>";
		echo "<td>PEÇA</td>";
		echo "<td>QTDE</td>";
		echo "</tr>";

		for ($i = 0 ; $i < pg_numrows($res) ; $i++) {
			$codigo_posto   = trim(pg_result($res,$i,codigo_posto));
			$sua_os         = trim(pg_result($res,$i,sua_os));
			$peca_referencia = trim(pg_result($res,$i,peca_referencia));
			$peca_descricao        = trim(pg_result($res,$i,peca_descricao));
			$qtde    = trim(pg_result($res,$i,qtde));
			
			$cor = ($i % 2 == 0) ? "#F1F4FA" : "#F7F5F0" ;
			
			echo "<tr class='Conteudo' bgcolor='$cor'>";
			echo "<td align='center'>";
			if ($login_fabrica == 1) {
				echo $codigo_posto;
			}
			echo $sua_os;
			echo "</td>";
			echo "<td align='left'>" . $peca_referencia . " - " . $peca_descricao . "</td>";
			echo "<td align='center'>" . $qtde . "</td>";
			echo "</tr>";
		}
		echo "</table>";
		echo "<br>";
	}
}

include "rodape.php";
?>
