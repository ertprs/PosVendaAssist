<?php
/**
 * 
 * @author Gabriel Tinetti
 *
*/

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="call_center";
include 'autentica_admin.php';

if ($_POST['getData']) {
	$dataInicial = implode("-", array_reverse(explode("/", $_POST['inicio'])));
	$dataFinal = implode("-", array_reverse(explode("/", $_POST['final'])));

	$filasTelefonia = array_map(function ($r) {
		return "'$r'";
	}, $filasTelefonia);

	$queryString = "/inicio/{$dataInicial}/final/{$dataFinal}/companhia/10/setor/sac/filas/" . urlencode(implode(",", $filasTelefonia)) . "/fabrica/" . $login_fabrica;
	$curlData = curl_init();

	curl_setopt_array($curlData, array(
		CURLOPT_URL => 'https://api2.telecontrol.com.br/telefonia/relatorio-atendentes' . $queryString,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT => 90,
		CURLOPT_HTTPHEADER => array(
			"Access-Application-Key: 084f77e7ff357414d5fe4a25314886fa312b2cff",
            "Access-Env: PRODUCTION",
            "Cache-Control: no-cache",
            "Content-Type: application/json"
		),
	));

	$responseData = curl_exec($curlData);
	$responseData = json_decode($responseData, true);

	if (strlen(curl_error($curl) > 0) OR $responseData['exception']) {
		$responseData = ['error' => strlen($responseData['exception']) ? $responseData['exception'] : curl_error($curlData)];
	}
	
	curl_close($curlData);

	echo json_encode($responseData);
	exit;
}

include '../vendor/autoload.php';
include 'funcoes.php';

$layout_menu = "callcenter";
$title = "RELATÓRIO TELEFONIA POR ATENDENTE";

include "cabecalho_new.php";
$plugins = array("datepicker", "mask", "font-awesome");
include ("plugin_loader.php");

?>

<style>
	.full_list {
		margin:15px auto;
	}

	.lost_list {
		margin:15px auto;
		width:30%;
	}

	.form_busca {
		padding:20px 0px;
		background-color:#D9E2EF;
	}

	.form_title {
		background-color:#596D9B;
		font-family:'Arial';
		font-size:16px;
		color:#FFF;
		text-align:center;
		font-weight:bold;
		padding:5px;
	}

	.form-group {
		padding: 10px 0;
	}

	.form-group input {
		width:100%;
	}

	.sender-container {
		text-align:center;
	}

	.form-warning {
		background-color:#EF151C;
		font-size:14px;
		font-family:'Arial';
		font-weight:bold;
		text-align:center;
		color:#FFF;
		padding:5px 0px;
	}

	tr:first-child th:first-child {

	}

	tr:nth-child(2) {
		background-color:#FFF;
		color:#333;
	}
</style>

<div class="row-fluid">
	<?php if (strlen($msg_erro) > 0) { ?>
		<div class="form-warning"><?= $msg_erro ?></div>
	<?php } ?>
	<div class="form_title">Parâmetros de Pesquisa</div>
	<form class="form_busca">
		<div class="row-fluid form-group">
			<div class="span3"></div>
			<div class="span3">
				<label>Data Inicial:</label>
				<input type="text" class="form-control" name="inicial_date" id="inicial_date">
			</div>
			<div class="span3">
				<label>Data Final:</label>
				<input type="text" class="form-control" name="final_date" id="final_date">
			</div>
			<div class="span3"></div>
		</div>
		<div class="row-fluid form-group">
			<div class="span12 sender-container">
				<button type="button" class="btn btn-info btn-search" id="btn_search">Pesquisar</button>
			</div>
		</div>
	</form>
</div>
</div>
<div class="infos">

</div>
<table class="full_list table table-striped table-bordered table-hover table-center table-large" style="display:none">
	<thead style="font-size:15px;background-color:#596D9B;color:#FFF;">
		<tr>
			<th rowspan="2">Atendente</th>
			<th colspan="4">Recebidas</th>
			<th colspan="2">Realizadas</th>
		</tr>
		<tr>
			<th>Chamadas</th>
			<th>Duração Média</th>
			<th>Tempo Médio de Espera</th>
			<th>Aderente ao SLA 20"</th>
			<th>Chamadas</th>
			<th>Duração Média</th>
		</tr>
	</thead>
	<tbody class="content">
	</tbody>
	<tfoot class="content-foot" style="background-color:#596D9B;color:#FFF">
	</tfoot>
</table>
<table class="lost_list table table-striped table-bordered table-hover table-center table-large" style="display:none;margin-bottom:20px;">
	<thead style="font-size:15px;background-color:#596D9B;color:#FFF;">
		<tr>
			<th colspan="2">Perdidas</th>
		</tr>
		<tr>
			<th>Total</th>
			<th>Percentual (%)</th>
		</tr>
	</thead>
	<tbody class="lost-content">

	</tbody>
</table>
<div class="download" style="display:none;text-align:center">

</div>

<script>
	$(function () {
		$("#inicial_date").datepicker({maxDate:0, dateFormat:"dd/mm/yy"}).mask("99/99/9999");
		$("#final_date").datepicker({maxDate:0, dateFormat:"dd/mm/yy"}).mask("99/99/9999");

		$("#btn_search").on("click", function () {
			$(".content").html("");
			$(".content-foot").html("");
			$(".lost-content").html("");

			if ($(".alert-warning")) {
				$(".alert-warning").remove();
			}

			var loading = $("<div></div>", {
				class: "alert alert-info"
			});
			$(loading).css({
				"text-align":"center",
				"font-size":"14px",
				"font-weight":"bold",
				"width":"60%",
				"margin":"0px auto 20px auto"
			});
			$(loading).text("Carregando...");
			$(".infos").append(loading);

			getData();
		});
	});

	function refreshData(callback) {
		setTimeout(function () {
			getData();
		}, 180000);
	}

	function getData() {
		var inicio = $("#inicial_date").val();
		var final = $("#final_date").val();

		if (inicio.length == 0 || final.length == 0) {
			if ($(".alert-info")) {
				$(".alert-info").remove();
			}

			var warning = $("<div></div>", {
				class: "alert alert-warning"
			});
			$(warning).css({
				"text-align":"center",
				"font-size":"14px",
				"font-weight":"bold",
				"width":"35%",
				"margin":"0px auto 20px auto"
			});
			$(warning).text("Preencha todos os campos!");

			$(".infos").append(warning);
			return false;
		}

		$.ajax('relatorio_atendentes_telefonia.php', {
			method: 'POST',
			data: {
				getData: true,
				inicio: inicio,
				final: final
			}
		}).done(function (response) {
			if ($(".infos").html().length > 0) {
				$(".infos").html("");
			}

			if ($(".alert-warning")) {
				$(".alert-warning").remove();
			}

			$(".full_list").fadeIn(500);
			$(".lost_list").fadeIn(500);
			$(".content").html("");
			$(".content-foot").html("");
			$(".lost-content").html("");

			var response = JSON.parse(response);
			if (response.length == 0) {
				return false;
			}

			var trTotal = $("<tr></tr>");

			var tdTotal = $("<td></td>");
			$(tdTotal).css({
				"text-align":"center",
				"font-weight":"bold",
				"vertical-align":"middle",
				"font-size":"14px"
			});
			$(tdTotal).text("TOTAL");

			var tdTchamada = $("<td></td>");
			$(tdTchamada).css({
				"text-align":"center",
				"font-weight":"bold",
				"vertical-align":"middle",
				"font-size":"14px"
			});
			$(tdTchamada).text(response.total.recebidas.total);

			var tdTduracao = $("<td></td>");
			$(tdTduracao).css({
				"text-align":"center",
				"font-weight":"bold",
				"vertical-align":"middle",
				"font-size":"14px"
			});
			$(tdTduracao).text(response.total.recebidas.duracao_media);

			var tdTespera = $("<td></td>");
			$(tdTespera).css({
				"text-align":"center",
				"font-weight":"bold",
				"vertical-align":"middle",
				"font-size":"14px"
			});
			$(tdTespera).text(response.total.recebidas.espera_media);

			var tdTsla = $("<td></td>");
			$(tdTsla).css({
				"text-align":"center",
				"font-weight":"bold",
				"vertical-align":"middle",
				"font-size":"14px"
			});
			$(tdTsla).text(response.total.sla.total + " (" + response.total.sla.percentual + "%)");

			var tdTrealizada = $("<td></td>");
			$(tdTrealizada).css({
				"text-align":"center",
				"font-weight":"bold",
				"vertical-align":"middle",
				"font-size":"14px"
			});
			$(tdTrealizada).text(response.total.realizadas.total);

			var tdTrealizadaMedia = $("<td></td>");
			$(tdTrealizadaMedia).css({
				"text-align":"center",
				"font-weight":"bold",
				"vertical-align":"middle",
				"font-size":"14px"
			});
			$(tdTrealizadaMedia).text(response.total.realizadas.duracao_media);

			var trPerdidas = $("<tr></tr>");

			var tdTperdidas = $("<td></tr>");
			$(tdTperdidas).css({
				"text-align":"center",
				"font-weight":"bold",
				"vertical-align":"middle",
				"font-size":"14px"
			});
			$(tdTperdidas).text(response.total.perdidas.total);

			arrTelPerdidas = $.map(response.total.perdidas.telefones, function (element) {
				return element;
			});

			let csv = 'TELEFONES';
			arrTelPerdidas.forEach(function (row) {
				csv += '\n' + row;
			});

			var btnDownload = $("<a></a>");
			$(btnDownload).addClass("btn btn-success csvdownload")
			$(btnDownload).css({
				"text-align": "center",
				"font-size": "14px"
			});
			$(btnDownload).attr("href", "data:text/csv;charset=utf-8," + encodeURI(csv));
			$(btnDownload).attr("download", "perdidas.csv");
			$(btnDownload).text("Download CSV");

			var tdTpercentual = $("<td></td>");
			$(tdTpercentual).css({
				"text-align":"center",
				"font-weight":"bold",
				"vertical-align":"middle",
				"font-size":"14px"
			});
			$(tdTpercentual).text(response.total.perdidas.percentual);

			$.each(response.atendentes, function (index, element) {
				var trLine = $("<tr></tr>");

				var tdAdmin = $("<td></td>");
				$(tdAdmin).css({
					"text-align":"center",
					"font-weight":"bold",
					"vertical-align":"middle",
					"font-size":"14px"
				});
				$(tdAdmin).html(element.nome_completo + " " + "<br />Ramal: " + element.ramal);

				var tdChamadas = $("<td></td>");
				$(tdChamadas).css({
					"text-align":"center",
					"vertical-align":"middle",
					"font-size":"14px"
				});
				$(tdChamadas).text(element.recebidas.total_ligacoes);

				var tdDuracao = $("<td></td>");
				$(tdDuracao).css({
					"text-align":"center",
					"vertical-align":"middle",
					"font-size":"14px"
				});
				if (element.recebidas.duracao_media) {
					$(tdDuracao).text(element.recebidas.duracao_media.split(".")[0]);
				} else {
					$(tdDuracao).text("0");
				}

				var tdEspera = $("<td></td>");
				$(tdEspera).css({
					"text-align":"center",
					"vertical-align":"middle",
					"font-size":"14px"
				});
				if (element.recebidas.espera_media) {
					$(tdEspera).text(element.recebidas.espera_media.split(".")[0]);
				} else {
					$(tdEspera).text("0");
				}
				$(tdEspera).text(element.recebidas.espera_media);

				var tdSLA = $("<td></td>");
				$(tdSLA).css({
					"text-align":"center",
					"vertical-align":"middle",
					"font-size":"14px"
				});
				$(tdSLA).text(element.recebidas.total_sla_20 + " (" + element.recebidas.percentual_sla_20 + "%)");

				var tdRealizada = $("<td></td>");
				$(tdRealizada).css({
					"font-size":"14px",
					"vertical-align":"middle",
					"text-align":"center"
				});
				$(tdRealizada).text(element.realizadas.total_ligacoes);

				var tdRealizadaMedia = $("<td></td>");
				$(tdRealizadaMedia).css({
					"font-size":"14px",
					"vertical-align":"middle",
					"text-align":"center"
				});
				$(tdRealizadaMedia).text(element.realizadas.duracao_media);

				$(trLine).append(tdAdmin);
				$(trLine).append(tdChamadas);
				$(trLine).append(tdDuracao);
				$(trLine).append(tdEspera);
				$(trLine).append(tdSLA);
				$(trLine).append(tdRealizada);
				$(trLine).append(tdRealizadaMedia);

				$(".content").append(trLine);
			});

			$(trTotal).append(tdTotal)
			$(trTotal).append(tdTchamada);
			$(trTotal).append(tdTduracao);
			$(trTotal).append(tdTespera);
			$(trTotal).append(tdTsla);
			$(trTotal).append(tdTrealizada);
			$(trTotal).append(tdTrealizadaMedia);

			$(".content-foot").append(trTotal);

			$(trPerdidas).append(tdTperdidas);
			$(trPerdidas).append(tdTpercentual);

			$(".lost_list").append(trPerdidas);
			if ($(".csvdownload").length > 0) {
				$(".csvdownload").remove();
			}
			$(".download").append(btnDownload);
			$(".download").fadeIn(1000);

			refreshData();
		});
	}
</script>

<? include "rodape.php"; ?>