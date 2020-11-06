<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

include 'autentica_admin.php';
include 'funcoes.php';

$posto     = $_REQUEST["posto"];
$data_inicial = formata_data($_REQUEST["data_inicial"]);
$data_final   = formata_data($_REQUEST["data_final"]);

$sqlPosto = "SELECT codigo_posto || ' - ' || tbl_posto.nome as descricao_posto
			 FROM tbl_posto_fabrica
			 JOIN tbl_posto USING(posto)
			 WHERE tbl_posto_fabrica.fabrica = $login_fabrica
			 AND tbl_posto_fabrica.posto = $posto";
$resPosto = pg_query($con, $sqlPosto);

$descricao_posto = pg_fetch_result($resPosto, 0, 'descricao_posto');

$sql = "SELECT 
			tbl_os.os,
			tbl_os.data_abertura,
			tbl_os_extra.extrato,
			tbl_sms_resposta.resposta::int,
			tbl_os.data_conserto,
			(
                SELECT (tbl_os_item.digitacao_item::date - tbl_os.data_abertura::date) 
                FROM tbl_os_item 
                WHERE tbl_os_item.os_produto = tbl_os_produto.os_produto
                ORDER BY tbl_os_item.digitacao_item DESC
                LIMIT 1
            ) as dias_digitacao_itens,
            (
                SELECT (tbl_os.data_conserto::date - tbl_faturamento.conferencia::date)
                FROM tbl_faturamento_item
                JOIN tbl_faturamento ON tbl_faturamento_item.faturamento = tbl_faturamento.faturamento
                WHERE tbl_faturamento_item.os = tbl_os.os
                ORDER BY tbl_faturamento.faturamento ASC 
                LIMIT 1
            ) as dias_em_conserto
		FROM tbl_os
		JOIN tbl_sms          ON tbl_sms.os = tbl_os.os
		JOIN tbl_sms_resposta ON tbl_sms_resposta.sms = tbl_sms.sms
		JOIN tbl_os_extra     ON tbl_os_extra.os = tbl_os.os
		JOIN tbl_os_produto   ON tbl_os_produto.os = tbl_os.os
		WHERE tbl_os.posto = $posto
		AND   (tbl_os.data_abertura BETWEEN '{$data_inicial}' AND '{$data_final}')
		AND tbl_sms.fabrica = $login_fabrica
		AND trim(tbl_sms_resposta.resposta)  ~ '^[0-9.-]+$'
		ORDER BY tbl_sms_resposta.resposta";
$resConsulta = pg_query($con, $sql);

?>
<html>
	<head>
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/bootstrap.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/extra.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="css/tc_css.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="css/tooltips.css" />
		<link type="text/css" rel="stylesheet" media="screen" href="plugins/posvenda_jquery_ui/css/posvenda_ui/jquery-ui-1.10.3.custom.css">
		<link type="text/css" rel="stylesheet" media="screen" href="bootstrap/css/ajuste.css" />
		<script src="plugins/posvenda_jquery_ui/js/jquery-1.9.1.js"></script>
		<script src="plugins/posvenda_jquery_ui/js/jquery-ui-1.10.3.custom.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.core.min.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.widget.min.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.effect.min.js"></script>
		<script src="plugins/posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.tabs.min.js"></script>
		<script src="https://code.highcharts.com/highcharts.js"></script>
		<script src="https://code.highcharts.com/modules/exporting.js"></script>
		<script src="https://code.highcharts.com/modules/export-data.js"></script>
	</head>
	<body>
		<br /><br />
		<table id="resultado_os_atendimento" class='table table-striped table-bordered table-hover table-fixed' >
			<thead>
				<tr>
					<th class="titulo_tabela" colspan="8">
						OSs que receberam SMS no Período de <?= mostra_data($data_inicial)." à ".mostra_data($data_final) ?>
					</th>
				</tr>
				<tr class='titulo_coluna'>
					<th>OS</th>
					<th>Data de Abertura</th>
					<th>Dias Digitação Peças</th>
					<th>Data Recebimento Peças</th>
					<th>Data de Conserto</th>
					<th>Dias em Conserto</th>
					<th>Nota do Consumidor</th>
					<th>Extrato</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$array_pecas[0]["name"] = utf8_encode("OSs com peças lançadas em 1 dia");
				$array_pecas[0]["y"]    = 0;

				$array_pecas[1]["name"] = utf8_encode("OSs com peças lançadas depois de 1 dia");
				$array_pecas[1]["y"]    = 0;

				$array_conserto[0]["name"] = utf8_encode("OSs consertadas em menos de 3 dias");
				$array_conserto[0]["y"]    = 0;

				$array_conserto[1]["name"] = utf8_encode("OSs consertadas depois de 3 dias");
				$array_conserto[1]["y"]    = 0;

				for ($i = 0; $i < pg_num_rows($resConsulta); $i++) {
					$os               = pg_fetch_result($resConsulta, $i, 'os');
					$data_abertura    = pg_fetch_result($resConsulta, $i, 'data_abertura');
					$extrato          = pg_fetch_result($resConsulta, $i, 'extrato');
					$nota             = pg_fetch_result($resConsulta, $i, 'resposta');
					$digitacao_itens  = pg_fetch_result($resConsulta, $i, 'dias_digitacao_itens');
					$dias_em_conserto = pg_fetch_result($resConsulta, $i, 'dias_em_conserto');
					$data_conserto    = pg_fetch_result($resConsulta, $i, 'data_conserto');

					if ($digitacao_itens <= 1) {
						$array_pecas[0]["y"] += 1;
					} else {
						$array_pecas[1]["y"] += 1;
					}

					if ($dias_em_conserto < 3) {
						$array_conserto[0]["y"] += 1;
					} else {
						$array_conserto[1]["y"] += 1;
					}

					$sqlDataConferencia = "SELECT
											tbl_faturamento.conferencia::date as data_conferencia
										   FROM tbl_faturamento
										   JOIN tbl_faturamento_item USING(faturamento)
										   WHERE tbl_faturamento_item.os = {$os}
										   AND tbl_faturamento.conferencia IS NOT NULL
										   AND tbl_faturamento.fabrica = 10
										   LIMIT 1";
					$resDataConferencia = pg_query($con, $sqlDataConferencia);

					$data_conferencia   = pg_fetch_result($resDataConferencia, 0, 'data_conferencia');

				?>
				<tr>
					<td class="tac"><a href="os_press.php?os=<?= $os ?>" target="_blank"><?= $os ?></a></td>
					<td class="tac"><?= mostra_data($data_abertura) ?></td>
					<td class="tac"><?= ($digitacao_itens <= "1") ? "Até 1 " : $digitacao_itens ?> dia(s)</td>
					<td class="tac">
						<?= mostra_data($data_conferencia) ?>
					</td>
					<td class="tac">
						<?= mostra_data($data_conserto) ?>
					</td>
					<td class="tac"><?= ($dias_em_conserto == "0") ? "Até 1 " : $dias_em_conserto ?> dia(s)</td>
					<td class="tac">
						Nota <?= $nota ?>
					</td>
					<td class="tac"><a href="extrato_consulta_os.php?extrato=<?= $extrato ?>" target="_blank"><?= $extrato ?></a></td>
				</tr>
				<?php 
				}

				$json_grafico_pecas    = json_encode($array_pecas);
				$json_grafico_conserto = json_encode($array_conserto);
				?>
			</tbody>
		</table>
		<div id="grafico1" style="min-width: 260px; height: 350px; margin: 0 auto"></div>
		<div id="grafico2" style="min-width: 260px; height: 350px; margin: 0 auto"></div>
	<script>

		$(function() {
			Highcharts.chart('grafico1', {
			    chart: {
			        plotBackgroundColor: null,
			        plotBorderWidth: null,
			        plotShadow: false,
			        type: 'pie'
			    },
			    title: {
			        text: 'Tempo para lançamento de peças na OS'
			    },
			    tooltip: {
			        pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
			    },
			    plotOptions: {
			        pie: {
			            allowPointSelect: true,
			            cursor: 'pointer',
			            dataLabels: {
			                enabled: true,
			                format: 'qtde. OSs: {point.y}',
			                style: {
			                    color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
			                }
			            },
			            showInLegend: true
			        }
			    },
			    series: [{
			        name: 'Pecas',
			        colorByPoint: true,
			        data: <?= $json_grafico_pecas ?>
			    }]
			});

			Highcharts.chart('grafico2', {
			    chart: {
			        plotBackgroundColor: null,
			        plotBorderWidth: null,
			        plotShadow: false,
			        type: 'pie'
			    },
			    title: {
			        text: 'Tempo de conserto da OS'
			    },
			    tooltip: {
			        pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>'
			    },
			    plotOptions: {
			        pie: {
			            allowPointSelect: true,
			            cursor: 'pointer',
			            dataLabels: {
			                enabled: true,
			                format: 'qtde. OSs: {point.y}',
			                style: {
			                    color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
			                }
			            },
			            showInLegend: true
			        }
			    },
			    series: [{
			        name: 'Conserto',
			        colorByPoint: true,
			        data: <?= $json_grafico_conserto ?>
			    }]
			});
		});

	</script>
	</body>
</html>
