<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

include 'autentica_admin.php';
$admin_privilegios="gerencia,call_center";

if (!empty($_FILES)) {
	$msg_erro = [];
	$responseCSV = [];

	$uploadedFiles = $_FILES;
	$key = array_keys($uploadedFiles);
	$file = $uploadedFiles[$key[0]];
	$fileType = explode("/", $file['type'])[1];

	$formatos = array("csv", "vnd.ms-excel");

	if (!in_array($fileType, $formatos)){
		$msg_erro[] = "Formato de arquivo não permitido.";
	}
	try {
		$fileHandle = fopen($file['tmp_name'], 'r');
		$data = [];
		while($rowData = fgetcsv($fileHandle,0,';'))
			$data[] = $rowData;

		fclose($fileHandle);

		// implode columns with '_' / columns names
		$columns = array_map(function ($r) {
			return strtolower(implode("_", explode(" ", $r)));
		}, $data[0]);

		// switch array keys with columns names
		$newData = [];
		for ($i = 0; $i < count($data); $i++) {
			for ($j = 0; $j <= count($data[$i]); $j++) {
				$newData[$i][$columns[$j]] = (!empty($data[$i][$j])) ? utf8_decode($data[$i][$j]) : "";
			}
		}

		unset($newData[0]);

		// group by posto
		foreach ($newData as $key => $value) {
			$newData[$value['codigo_posto']][] = $value;
			unset($value['codigo_posto']);
			unset($newData[$key]);
		}
		
		// for each posto
		pg_query($con, "BEGIN");
		foreach ($newData as $keyPosto => $items) {
			$qPosto = "SELECT posto
					   FROM tbl_posto_fabrica
					   WHERE codigo_posto = '{$keyPosto}'
					   AND fabrica = {$login_fabrica}";
			$rPosto = pg_query($con, $qPosto);
			$idPosto = pg_fetch_result($rPosto, 0, 'posto');

			$curDate = new DateTime('now', new DateTimeZone("America/Sao_Paulo"));

			$faturamentoParams = [$login_fabrica, $curDate->format('Y-m-d H:i:s'), $curDate->format('Y-m-d H:i:s'), $idPosto, 0];

			$qFaturamento = "INSERT INTO tbl_faturamento
							(fabrica, emissao, saida, posto, total_nota)
							VALUES
							($1, $2, $3, $4, $5)
							RETURNING faturamento;";
			$rFaturamento = pg_query_params($con, $qFaturamento, $faturamentoParams);
			$idFaturamento = pg_fetch_result($rFaturamento, 0, "faturamento");
			if (strlen(pg_last_error()) > 0) {
				$msg_erro[] = "Falha ao inserir faturamento.";
				break;
			}

			// for each pedido
			foreach ($items as $key => $item) {
				$os = $item["os"];
				$qtdeBaixada = $item["quantidade_baixada"];
				$osItem = $item["os_item"];
				$pedido = $item["pedido"];
				$pedidoItem = $item["pedido_item"];

				if (strlen($qtdeBaixada) == 0) {
					if ($login_fabrica != 177){
						$msg_erro[] = "Não foi informada a quantidade baixada para o item <b>" . $pedidoItem . "</b>";
					}
					continue;
				}
				
				$qQuantidade = "SELECT qtde - (qtde_cancelada + qtde_faturada) AS qtde_pendente,
									peca
								FROM tbl_pedido_item
								WHERE pedido_item = {$pedidoItem}";
				$rQuantidade = pg_query($con, $qQuantidade);
				$qtdePendente = pg_fetch_result($rQuantidade, 0, 'qtde_pendente');
				$peca = pg_fetch_result($rQuantidade, 0, "peca");
				if (strlen(pg_last_error()) > 0) {
					$msg_erro[] = "Falha ao buscar quantidade pendente para a OS <b>" . $suaOS ."</b>.";
					break;
				}

				if ($qtdeBaixada > $qtdePendente) {
					$msg_erro[] = "Quantidade baixada na OS <b>" . $os . "</b> para a peça <b>" . $peca . "</b> é maior que a quantidade pendente no pedido <b>" . $pedido . "</b>.";
					continue;
				}
				
				$qOS = "SELECT op.os,
							   oi.peca
						FROM tbl_os_item oi
						INNER JOIN tbl_os_produto op ON op.os_produto = oi.os_produto
						WHERE oi.os_item = {$osItem}
						AND op.os = {$os}";
				$rOS = pg_query($con, $qOS);
				if (pg_num_rows($rOS) == 0)
					$msg_erro[] = "OS " . $os . " inexistente";

				if (empty($msg_erro)) {
					$faturamentoItemParams = [$idFaturamento, $peca, $qtdeBaixada, 0, $os, $pedido, $osItem, $pedidoItem];
					$qFaturamentoItem = "INSERT INTO tbl_faturamento_item
										(faturamento, peca, qtde, preco, os, pedido, os_item, pedido_item)
										VALUES
										($1, $2, $3, $4, $5, $6, $7, $8)
										RETURNING faturamento_item;";
					$rFaturamentoItem = pg_query_params($con, $qFaturamentoItem, $faturamentoItemParams);
					$idFaturamentoItem = pg_fetch_result($rFaturamentoItem, 0, 'faturamento_item');
					if (strlen(pg_last_error()) > 0) {
						$msg_erro[] = "Falha ao baixar item " . $peca;
						continue;
					}

					$qFnAtualizaPI = "SELECT fn_atualiza_pedido_item({$peca}, {$pedido}, {$pedidoItem}, {$qtdeBaixada});";
					$qFnAtualizaSP = "SELECT fn_atualiza_status_pedido({$login_fabrica}, {$pedido});";
					pg_query($con, $qFnAtualizaPI . $qFnAtualizaSP);
					if (strlen(pg_last_error()) > 0) {
						$msg_erro[] = "Falha ao atualizar status do pedido.";
					}
				}
			}
		}

		if (!empty($msg_erro)) {
			pg_query($con, "ROLLBACK");
			$responseCSV = ["exception" => $msg_erro];
		} else {
			pg_query($con, "COMMIT");
			$responseCSV = ["message" => "Sucesso ao baixar pedidos!"];
		}
	} catch (Exception $e) {
		$responseCSV['exception'][] = $e->getMessage();
	}
}

if (!empty($_POST) AND isset($_GET['ajax'])) {
	switch ($_GET['ajax']) {
		case 'searchPedidos':
			$dataInicial = DateTime::createFromFormat('d/m/Y', $_POST['dataInicial']);
			$dataFinal = DateTime::createFromFormat('d/m/Y', $_POST['dataFinal']);

			if ($dataFinal < $dataInicial) {
				die(json_encode(['exception' => 'Data final menor que data inicial.']));
			} elseif ($dataFinal->diff($dataInicial)->m >= 6) {
				die(json_encode(['exception' => 'Intervalo entre as datas maior que 6 meses.']));
			}

			$dataInicial = $dataInicial->format('Y-m-d');
			$dataFinal = $dataFinal->format('Y-m-d');

			$qPedidos = "SELECT o.os,
							oi.os_item,
							pf.codigo_posto,
							ps.nome AS nome_posto,
							p.pedido,
							TO_CHAR(p.data, 'DD/MM/YYYY') AS data_pedido,
							pi.pedido_item,
							pc.referencia,
							pc.descricao,
							pi.qtde AS quantidade
						FROM tbl_pedido_item pi
						INNER JOIN tbl_pedido p ON p.pedido = pi.pedido
						INNER JOIN tbl_peca pc ON pc.peca = pi.peca
						INNER JOIN tbl_posto ps ON ps.posto = p.posto
						INNER JOIN tbl_posto_fabrica pf ON pf.posto = ps.posto AND pf.fabrica = {$login_fabrica}
						INNER JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = {$login_fabrica}
						INNER JOIN tbl_os_item oi ON oi.pedido_item = pi.pedido_item AND oi.fabrica_i = {$login_fabrica}
						INNER JOIN tbl_os_produto op ON op.os_produto = oi.os_produto
						INNER JOIN tbl_os o ON o.os = op.os AND o.fabrica = {$login_fabrica}
						WHERE p.fabrica = {$login_fabrica}
						AND p.finalizado IS NOT NULL
						AND (pi.qtde - pi.qtde_cancelada - pi.qtde_faturada) > 0
						AND p.data BETWEEN '{$dataInicial} 00:00:00' AND '{$dataFinal} 23:59:59'
						AND tp.posto_interno IS TRUE
						ORDER BY p.data";
			$rPedidos = pg_query($con, $qPedidos);

			if (pg_num_rows($rPedidos) == 0)
				die(json_encode(['exception' => 'Nenhum pedido pendente encontrado.']));

			$pedidos = pg_fetch_all($rPedidos);
			$nPedidos = array_map(function ($e) {
				$e['descricao'] = utf8_encode($e['descricao']);
				$e['nome_posto'] = utf8_encode($e['nome_posto']);
				return $e;
			}, $pedidos);

			echo json_encode(['data' => $nPedidos]);
			break;
	}
	exit;
}

$title = "PEDIDOS DO POSTO INTERNO";
$layout_menu = "callcenter";

include "cabecalho_new.php";

$plugins = array(
    "autocomplete",
    "datepicker",
    "shadowbox",
    "mask",
	"font_awesome"
);

include("plugin_loader.php");
?>

<!-- styles -->
<style type="text/css">
	.fas, .far {margin: 0 5px;}
	.date {width: 93%;}
	.situation {padding: 0;}

	.required {
		color: #E04A4A;
		font-weight: bold;
	}

	.form-title {
		clear: both;
		padding: 5px 0;
		background-color: #596D9B;
		text-align: center;
		color: #FFF;
		min-height:0 !important;
	}

	.search-row {margin-top: 15px;}

	form {
		background-color: #D9E2EF;
		padding: 30px 0 10px 0;
	}
</style>

<!-- javascript -->
<script type="text/javascript">
	$(function () {
		$(".date").datepicker().mask("99/99/9999");
		$(".close-response").on("click", function () {
			$(this).parents(".responsecsv").fadeOut(500);
		});

		$("#search-pedidos").on("click", function () {
			let situationDOM = $(".form-pedidos").find(".situation");
			$(situationDOM).removeClass("alert-info alert-warning alert-error");
			$(situationDOM).addClass("alert-info");
			let spinner = $("<i></i>", {
				class: "fa-spinner fa fa-spin",
				css: {
					"margin": "0px 5px"
				}
			});
			$(situationDOM).find("h6").text("Gerando CSV...");
			$(situationDOM).find("h6").prepend(spinner);

			let dataInicial = $(this).parents(".form-pedidos").find("[name=data-inicial]").val();
			
			let dataFinal = $(this).parents(".form-pedidos").find("[name=data-final]").val();
			if (dataInicial.length == 0 || dataFinal.length == 0) {
				$(situationDOM).removeClass("alert-info alert-warning alert-error");
				$(situationDOM).addClass("alert-warning");
				return $(situationDOM).find("h6").text("Preencha os campos obrigatórios!");
			}

			$.ajax("<?= $PHP_SELF ?>" + "?ajax=searchPedidos", {
				method: 'POST',
				data: {
					dataInicial: dataInicial,
					dataFinal: dataFinal
				}
			}).fail(function (response) {
				$(situationDOM).removeClass("alert-info alert-warning alert-error");
				$(situationDOM).addClass("alert-error");

				let infoIcon = $("<i></i>", {
					class: "far fa-times-circle",
					css: {
						"margin": "0px 5px"
					}
				});

				$(situationDOM).find("h6").html("");
				$(situationDOM).find("h6").text('Falha ao realizar pesquisa!');
				$(situationDOM).find("h6").prepend(infoIcon);
			}).done(function (response) {
				try {
					response = JSON.parse(response);
				} catch (err) {
					return console.log('erro jaysÃ£o');
				}

				if (response.exception) {
					$(situationDOM).removeClass("alert-info alert-warning alert-error");
					$(situationDOM).addClass("alert-error");

					let infoIcon = $("<i></i>", {
						class: "far fa-times-circle",
						css: {
							"margin": "0px 5px"
						}
					});

					$(situationDOM).find("h6").html("");
					$(situationDOM).find("h6").append(response.exception);	
					$(situationDOM).find("h6").prepend(infoIcon);
				} else {
					let columns = Object.keys(response.data[0]);
					let csv = response.data.map(function (row) {
						return columns.map(function (column) {
							return JSON.stringify(row[column], function (index, element) {
								return element === null ? "" : element;
							});
						}).join(';');
					});

					columns = columns.map(function (element) {
						let splitted = element.split("_").join(" ");
						splitted = splitted.toLowerCase().replace(/\b[a-z]/g, function (letter) {
							return letter.toUpperCase();
						});
						return splitted;
					});

					columns.push("Quantidade Baixada");
					csv.unshift(columns.join(';'));
					csv = csv.join("\r\n");

					let downladLink = $("<a></a>");
					let blob = new Blob([csv], {type: 'text/csv'});
					let url = URL.createObjectURL(blob);

					$(situationDOM).removeClass("alert-info alert-warning alert-error");
					$(situationDOM).addClass("alert-success");

					let downloadCSV = $("<a></a>", {
						class: "btn btn-success btn-small",
						text: "Download CSV"
					});
					$(downloadCSV).attr("href", url);

					let fileName = 'pedidos_pendentes_' + Date.now() + '.csv';
					$(downloadCSV).attr("download", fileName);

					let successIcon = $("<i></i>", {
						class: "far fa-check-circle",
						css: {
							"margin": "0px 5px"
						}
					});

					$(downloadCSV).prepend(successIcon);
					$(situationDOM).find("h6").html("");
					$(situationDOM).find("h6").append(downloadCSV);
				}
			});
		});

		$(".button-file-pedido").on("click", function () {
			$(".input-file-pedido").click();
		});

		$(".input-file-pedido").on("change", function (e) {
			let fileName = e.target.files[0].name;
			$(".input-file-name").text(fileName.substring(0, 20) + "...");
		});

		$(".send-file").on("click", function () {
			if ($(".input-file-pedido").val().length == 0)
				return alert('Selecione um arquivo!');

			$(".form-file-pedido").submit();
		});
	});
</script>

<!-- markup -->
<div class="row-fluid">
	<div class="" style="float:right;color:#B94A48;">
		<h6>* Campos obrigatórios</h6>
	</div>
	<div class="row-fluid form-title">
		<h5 style="line-height:1.5px;">PESQUISAR PEDIDOS PENDENTES</h5>
	</div>
	<form class="row-fluid form-pedidos">
		<div class="row-fluid">
			<div class="span3"></div>
			<div class="span3">
				<label for="data-inicial"><span class="required">*</span> Data Inicial:</label>
				<input class="date" name="data-inicial" type="text">
			</div>
			<div class="span3">
				<label for="data-final"><span class="required">*</span> Data Final:</label>
				<input class="date" name="data-final" type="text">
			</div>
			<div class="span3"></div>
		</div>
		<div class="row-fluid">
			<div class="span3"></div>
			<div class="span6 alert alert-warning situation">
				<h6>O intervalo máximo entre as datas inicial e final é de 6 meses.</h6>
			</div>
			<div class="span3"></div>
		</div>
		<div class="row-fluid search-row">
			<div class="span3"></div>
			<div class="span6" style="text-align:center;">
				<button class="btn btn-primary" type="button" id="search-pedidos">
					<i class="fas fa-search"></i>
					Pesquisar
				</button>
			</div>
			<div class="span3"></div>
		</div>
	</form>
</div>
<div class="row-fluid">
	<div class="" style="float:right;color:#B94A48;">
		<h6>* Campos obrigatórios</h6>
	</div>
	<div class="row-fluid form-title">
		<h5 style="line-height:1.5px;">BAIXA NOS PEDIDOS</h5>
	</div>
	<form class="row-fluid form-file-pedido" method="POST" action="<?= $_SERVER['PHP_SELF'] ?>" style="background-color:#D9E2EF" enctype="multipart/form-data">
		<div class="row-fluid">
			<div class="span4"></div>
			<input type="file" name="input-file-pedido" class="input-file-pedido" style="display:none;">
			<div class="span4 button-file-pedido" style="cursor:pointer;height:32px;background-color:#FFF;border-radius:5px;border:1px solid #CCC">
				<span class="input-file-name" style="float:left;padding:5px;">Procurar arquivo...</b></span>
				<button type="button" class="btn btn-info" style="float:right;border-top-left-radius:0px;border-bottom-left-radius:0px;">
					<i class="fas fa-search"></i>
				</button>
			</div>
			<div class="span4">
				<button class="btn btn-success send-file" type="button">Enviar</button>
			</div>
		</div>
		<div class="row-fluid responsecsv" style="display:<?= !empty($responseCSV) ? block : none ?>">
			<div class="span3"></div>
			<div class="span6 alert <?= (!empty($responseCSV['exception'])) ? 'alert-error' : 'alert-success' ?>" style="text-align:center;padding:5px 0px;">
				<button type="button" class="close close-response" style="right:10px;top:-2px;">&times;</button>
				<b><?= (!empty($responseCSV['exception'])) ? "Ocorreram alguns erros!" : $responseCSV['message'] ?></b>
				<?php if (!empty($responseCSV['exception'])) { ?>
				<div class="row-fluid" style="text-align:center;">
					<?php foreach ($responseCSV['exception'] as $exception) { ?>
						<p style="font-size:12px;margin-top:10px;"><?= $exception ?></p>
					<?php } ?>
				</div>
				<?php } ?>
			</div>
			<div class="span3"></div>
		</div>
	</form>
</div>

<?php include "rodape.php" ?>