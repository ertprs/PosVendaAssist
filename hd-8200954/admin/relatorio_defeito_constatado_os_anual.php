<?
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';
include 'includes/funcoes.php';
include "autentica_admin.php";
$admin_privilegios="gerencia";
$layout_menu = 'gerencia';
include "funcoes.php";

if (strlen($_POST["acao"]) > 0) $acao = $_POST["acao"];

if ($_POST["acao"] == 0 && strtoupper($_GET["acao"]) == "PESQUISAR") {
	$acao = $_GET["acao"];
}

$acao = trim(strtoupper($acao));

$ano = trim($_POST['ano']);
if (strlen($ano) == 0) $ano = trim($_GET["ano"]);

if (strlen($ano) == 4) {
	$ano_atual = intval(date("Y"));
	if ($ano > $ano_atual || $ano < 2000) {
		$msg_erro = "Ano selecionado fora do período permitido";
	}
}
else {
	$msg_erro = "Selecione o ano para gerar o relatório";
}

$title = "RELATÓRIO ANUAL DE OS POR DEFEITOS CONSTATADOS";

if ($excel) {
	ob_start();
}
else {
	include "cabecalho.php";
}
?>

<style type="text/css">
.Titulo {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #596D9B;
}
.Erro {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #FF0000;
}
.Mensagem {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #007700;
}
.Total {
	text-align: center;
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 12px;
	font-weight: bold;
	color: #FFFFFF;
	background-color: #DDEEEE;
}
.Conteudo {
	font-family: Verdana, Tahoma, Geneva, Arial, Helvetica, sans-serif;
	font-size: 10px;
	font-weight: normal;
}

<?
include "js/blue/style.css";
?>
</style>

<? include "javascript_pesquisas.php"; ?>
<? include "javascript_calendario.php"; ?>
<script type='text/javascript' src='js/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='js/dimensions.js'></script>
<script type='text/javascript' src='js/bibliotecaAJAX.js'></script>
<script type="text/javascript" src="js/jquery.tablesorter.js"></script>
<script type="text/javascript" src="js/jquery.tablesorter.pager.js"></script>

<script language="JavaScript">

$(function() {
	// add new widget called repeatHeaders
	$.tablesorter.addWidget({
		// give the widget a id
		id: "repeatHeaders",
		// format is called when the on init and when a sorting has finished
		format: function(table) {
			// cache and collect all TH headers
			if(!this.headers) {
				var h = this.headers = [];
				$("thead th",table).each(function() {
					h.push(
						"<th>" + $(this).text() + "</th>"
					);

				});
			}

			// remove appended headers by classname.
			$("tr.repated-header",table).remove();

			// loop all tr elements and insert a copy of the "headers"
			for(var i=0; i < table.tBodies[0].rows.length; i++) {
				// insert a copy of the table head every 10th row
				if((i%20) == 0) {
					if(i!=0){
					$("tbody tr:eq(" + i + ")",table).before(
						$("<tr></tr>").addClass("repated-header").html(this.headers.join(""))

					);
				}}
			}

		}
	});
	$("table").tablesorter({
		widgets: ['zebra','repeatHeaders']
	});

});

</script>

<? if (strlen($msg_erro) > 0) { ?>
	<br>
	<table width="406" border="0" cellspacing="0" cellpadding="2" align="center" style='border-style: solid; border-color: #770000; border-width:1px;
	font-family: Verdana;
	font-size: 10px;'>
		<tr class="Erro">
			<td colspan="4" height='25'><? echo $msg_erro; ?></td>
		</tr>
	</table>
<? } ?>

<? if (strlen($_GET["msg"]) > 0) { ?>
	<br>
	<table width="406" border="0" cellspacing="0" cellpadding="2" align="center" style='border-style: solid; border-color: #770000; border-width:1px;
	font-family: Verdana;
	font-size: 10px;'>
		<tr class="Mensagem">
			<td colspan="4" height='25'><? echo $_GET["msg"]; ?></td>
		</tr>
	</table>
<? }

if ($excel) {
}
else {
?>

	<br>

	<form name="frm_busca" method="GET" action="<? echo $PHP_SELF ?>">
	<input type="hidden" name="acao">
	<table width="400" border="0" cellspacing="0" cellpadding="2" align="center" style='border-style: solid; border-color: #6699CC; border-width:1px;
	font-family: Verdana;
	font-size: 10px;'>
		<tr class="Titulo">
			<td colspan="4" background='imagens_admin/azul.gif' height='25'>PESQUISA</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td colspan="4">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td colspan="2" align="center">Ano</td>
			<td width="10">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF" align='left'>
			<td width="10">&nbsp;</td>
			<td colspan="2" align="center">
			<select name="ano" id="ano">
			<?
			$ano_atual = intval(date("Y"));
			for($i = $ano_atual; $i >= 2010; $i--) {
				echo "
				<option value='$i'>$i</option>";
			}
			?>
			</select>
			</td>
			<td width="10">&nbsp;</td>
		</tr>

		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td colspan="4">&nbsp;</td>
		</tr>
		<tr class="Conteudo" bgcolor="#D9E2EF">
			<td colspan="4"><img src="imagens_admin/btn_pesquisar_400.gif" onclick="javascript: document.frm_busca.acao.value='PESQUISAR'; document.frm_busca.submit();" style="cursor:pointer " alt="Clique AQUI para pesquisar"></td>
		</tr>
	</table>
	</form>

<?
} //if ($excel)

if ($acao == "PESQUISAR" && strlen($msg_erro) == 0) {
	$ano_atual = intval(date("Y"));

	$sql = "
	SELECT
	bi_os.familia,
	tbl_defeito_constatado.codigo AS defeito_constatado_codigo,
	bi_os.defeito_constatado,
	COUNT(bi_os.os) AS count_os,
	SUM(bi_os.data_finalizada - bi_os.data_abertura) AS tempo_atendimento,
	TO_CHAR(bi_os.data_finalizada, 'MM') AS mes,
	SUM(bi_os.mao_de_obra) AS sum_mao_de_obra
	
	FROM
	bi_os
	JOIN tbl_familia ON bi_os.familia=tbl_familia.familia
	JOIN tbl_defeito_constatado ON bi_os.defeito_constatado=tbl_defeito_constatado.defeito_constatado
	
	WHERE
	bi_os.fabrica=$login_fabrica
	AND bi_os.data_finalizada BETWEEN '$ano-01-01 00:00:00' AND '$ano-12-31 23:59:59'
	AND bi_os.excluida IS NOT TRUE
	AND bi_os.extrato_aprovacao IS NOT NULL
	
	GROUP BY
	bi_os.familia,
	tbl_defeito_constatado.codigo,
	bi_os.defeito_constatado,
	tbl_familia.descricao,
	tbl_defeito_constatado.defeito_constatado_grupo,
	to_char(bi_os.data_finalizada, 'MM')
	
	ORDER BY
	tbl_familia.descricao,
	tbl_defeito_constatado.codigo,
	tbl_defeito_constatado.defeito_constatado_grupo,
	TO_CHAR(bi_os.data_finalizada, 'MM')
	";
	$res = pg_query($con, $sql);

	if (pg_numrows($res) > 0) {
		$defeitos = array();
		$meses = array("Janeiro", "Fevereiro", "Março", "Abril", "Maio", "Junho", "Julho", "Agosto", "Setembro", "Outubro", "Novembro", "Dezembro");
		$ultimo_mes = 0;

		for($i = 0; $i < pg_num_rows($res); $i++) {
			$defeito_constatado = intval(pg_result($res, $i, defeito_constatado));
			$count_os = intval(pg_result($res, $i, count_os));
			$mes = intval(pg_result($res, $i, mes));
			$tempo_atendimento = intval(pg_result($res, $i, tempo_atendimento));
			$sum_mao_de_obra = floatval(pg_result($res, $i, sum_mao_de_obra));
			$familia = floatval(pg_result($res, $i, familia));
			$defeito_constatado_codigo = pg_result($res, $i, defeito_constatado_codigo);

			$defeitos[$defeito_constatado . "|" . $familia . "|" . $defeito_constatado_codigo][$mes]["os"] = $count_os;
			$defeitos[$defeito_constatado . "|" . $familia . "|" . $defeito_constatado_codigo][$mes]["tempo"] = $tempo_atendimento;
			$defeitos[$defeito_constatado . "|" . $familia . "|" . $defeito_constatado_codigo][$mes]["valor"] = $sum_mao_de_obra;

			if ($mes > $ultimo_mes) {
				$ultimo_mes = $mes;
			}
		}

		echo "
		<table class=tablesorter>
		<thead>
			<tr>
				<th>Família</th>
				<th>Código Defeito</th>
				<th>Grupo Defeito</th>
				<th>Defeito</th>";
		
		for($i = 0; $i < $ultimo_mes; $i++) {
			echo "
				<th width=50> OS " . $meses[$i] . "</th>
				<th width=50> Média Dias " . $meses[$i] . "</th>";
		}

		echo "
				<th width=50>Total OS</th>
				<th width=50>Valor Médio Serviço</th>
				<th width=50>Total Serviços</th>
			</tr>
		</thead>
		<tbody>";

		$total_geral_os_mensal = array();
		$total_geral_tempo_mensal = array();
		$total_geral_os = 0;
		$total_geral_servico = 0;

		foreach($defeitos as $defeito_constatado_familia => $mes_array) {
			$partes = explode("|", $defeito_constatado_familia);
			$defeito_constatado = intval($partes[0]);
			$familia = intval($partes[1]);
			$defeito_constatado_codigo = $partes[2];

			$sql = "
			SELECT
			tbl_defeito_constatado.descricao,
			tbl_defeito_constatado.mao_de_obra,
			tbl_defeito_constatado_grupo.defeito_constatado_grupo,
			tbl_defeito_constatado_grupo.descricao AS defeito_constatado_grupo_descricao
			
			FROM
			tbl_defeito_constatado
			JOIN tbl_defeito_constatado_grupo USING(defeito_constatado_grupo)
			
			WHERE
			defeito_constatado=$defeito_constatado
			";
			$res = pg_query($con, $sql);
			$defeito_constatado_descricao = pg_result($res, 0, descricao);
			$defeito_constatado_mao_de_obra = pg_result($res, 0, mao_de_obra);
			$defeito_constatado_grupo = pg_result($res, 0, defeito_constatado_grupo);
			$defeito_constatado_grupo_descricao = pg_result($res, 0, defeito_constatado_grupo_descricao);

			$sql = "SELECT descricao FROM tbl_familia WHERE familia=" . $familia;
			$res_familia = pg_query($con, $sql);
			$familia_descricao = strtoupper(pg_result($res_familia, 0, descricao));
			
			echo "
			<tr>
				<td>$familia_descricao</td>
				<td>$defeito_constatado_codigo</td>
				<td>$defeito_constatado_grupo-$defeito_constatado_grupo_descricao</td>
				<td>$defeito_constatado_descricao</td>";
			
			$total_os = 0;
			$total_servico = 0;
			
			//Montando colunas dos meses
			for ($m = 1; $m <= $ultimo_mes; $m++) {
				if ($tempo_atendimento = $mes_array[$m]["tempo"]) {
				}
				else {
					$tempo_atendimento = 0;
				}

				if ($count_os = $mes_array[$m]["os"]) {
				}
				else {
					$count_os = 0;
				}

				if ($count_os) {
					$media_atendimento = number_format(($tempo_atendimento / $count_os), 2, ",", "");
				}
				else {
					$media_atendimento = 0;
				}

				$total_os += $count_os;
				$total_servico += $mes_array[$m]["valor"];

				$total_geral_os_mensal[$m] += $count_os;
				$total_geral_tempo_mensal[$m] += $tempo_atendimento;
				$total_geral_os += $count_os;
				$total_geral_servico += $mes_array[$m]["valor"];

				echo "
				<td>$count_os</td>
				<td>$media_atendimento</td>";
			}

			$media_servico = $total_servico / $total_os;

			echo "
				<td>$total_os</td>
				<td>" . number_format($media_servico, 2, ",", "") . "</td>
				<td>" . number_format($total_servico, 2, ",", "") . "</td>
			</tr>";
		}

		echo "
		</tbody>
		<tfoot style='font-weight: bold;'>
			<tr>
				<td colspan=4>TOTAL GERAL</td>";

		for ($m = 1; $m <= $ultimo_mes; $m++) {
			echo "
				<td>" . $total_geral_os_mensal[$m] . "</td>
				<td>" . number_format(($total_geral_tempo_mensal[$m] / $total_geral_os_mensal[$m]), 2, ",", "") . "</td>";
		}

		echo "
				<td>$total_geral_os</td>
				<td>" . number_format(($total_geral_servico / $total_geral_os), 2, ",", "") . "</td>
				<td>" . number_format($total_geral_servico, 2, ",", "") . "</td>
			</tr>
		</tfoot>
		</table>";
	}
	else {
		echo "<br><FONT size='2' COLOR=\"#FF3333\"><B>Não encontrado!</B></FONT><br><br>";
	}
}
echo "<br>";


if ($excel) {
	$conteudo_excel = ob_get_clean();
	$arquivo = fopen("xls/relatorio_defeito_constatado_os_anual_$login_fabrica$login_admin.xls", "w+");
	fwrite($arquivo, $conteudo_excel);
	fclose($arquivo);
	header("location:xls/relatorio_defeito_constatado_os_anual_$login_fabrica$login_admin.xls");
}
else {
	if ($acao == "PESQUISAR") {
		echo "<a href='" . $PHP_SELF . "?" . $_SERVER["QUERY_STRING"] . "&excel=1' style='font-size: 10pt;'><img src='imagens/excell.gif'> Clique aqui para download do relatório em Excel</a>";
		echo "<br><br>";
	}

	include "rodape.php";
}
?>
