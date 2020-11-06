<?php
/**
 * 
 * @author Gabriel Tinetti
 *
*/

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include 'funcoes.php';

$msg_erro = "";
$clientId = "4814007018102931";
$clientSecret = "bQq81aS24yoHydpTLhh0AuTJVjB7YvQq";

if ($_POST['remove']) {
	$pecaExterna = $_POST['pecaExterna'];

	pg_query($con, "BEGIN");

	$queryRemove = "DELETE FROM tbl_peca_externa
					WHERE peca_externa = {$pecaExterna}
					AND fabrica = {$login_fabrica}";
	$result = pg_query($con, $queryRemove);

	if (strlen(pg_last_error()) > 0 OR pg_affected_rows($result) > 1) {
		$response = pg_last_error();
		pg_query($con, "ROLLBACK");
	} else {
		$response = "success";
		pg_query($con, "COMMIT");
	}

	echo $response;
	exit;
}

if ($_POST['relaciona']) {
	$produtoAcabado = $_POST['produtoAcabado'];
	$produtoMlNome = preg_replace("/\n|\t|\r/", "", $_POST['produtoMlNome']);
	$produtoMlVal = $_POST['produtoMlVal'];
	$produtoMlVolt = $_POST['produtoMlVolt'];

	if ($produtoAcabado == "selecione" OR $produtoMl == "selecione") {
		$msg_erro .= "Preencha todos os campos";
	}
	
	$atributo = [];
	$atributo['titulo'] = $produtoMlNome;

	if (!empty($produtoMlVolt)) {
		$atributo['voltagem'] = $produtoMlVolt;
	}

	$atributo = json_encode($atributo, JSON_UNESCAPED_UNICODE);

	$queryOrigem = "SELECT
						tbl_peca_externa_origem.peca_externa_origem
					FROM tbl_peca_externa_origem
					WHERE lower(tbl_peca_externa_origem.descricao) = 'mercado livre'";
	$result = pg_query($con, $queryOrigem);
	$origemExterna = pg_fetch_result($result, 0, 'peca_externa_origem');

	pg_query($con, "BEGIN");

	$queryInsert = "INSERT INTO tbl_peca_externa (
						fabrica,
						peca_externa_origem,
						peca,
						id_externo,
						atributo
					) VALUES ($1, $2, $3, $4, $5) RETURNING peca_externa";
	$statement = pg_prepare($con, "insertRel", $queryInsert);

	$result = pg_execute($con, "insertRel", [
		(int)$login_fabrica,
		(int)$origemExterna,
		(int)$produtoAcabado,
		(string)$produtoMlVal,
		$atributo
	]);

	if (strlen(pg_last_error()) > 0 OR !empty($msg_erro)) {
		$response = ['id' => '', 'error' => pg_last_error()];
		pg_query($con, "ROLLBACK");
	} else {
		$response = ['id' => pg_fetch_result($result, 0, 'peca_externa'), 'error' => ''];
		pg_query($con, "COMMIT");
	}

	echo json_encode($response);
	exit;
}

require '../classes/Posvenda/Meli/meli.php';

$meli = new Meli(
	$clientId,
	$clientSecret
);

function getItensML($offset, $resultParams) {
	GLOBAL $meli;

	$requestParams = [
		'access_token' => $resultParams['mlAccessToken'],
		'seller_id' => $resultParams['mlUserId'],
		'offset' => $offset
	];

	try {
		$response = $meli->get('/sites/MLB/search', $requestParams, true);
	} catch (\Exception $e) {
		$msg_erro .= $e->getMessage;
	}

	return $response;
}

// ## //
$selectParams = "SELECT
					parametros_adicionais
				FROM tbl_fabrica
				WHERE fabrica = {$login_fabrica}";
$result = pg_query($con, $selectParams);
$resultParams = pg_fetch_result($result, 0, 'parametros_adicionais');
$resultParams = json_decode($resultParams, true);

if (array_key_exists('meLibreAccount', $resultParams) && (time() > $resultParams['meLibreAccount']['mlExpires'])) {
	
	$requestParams = [
		'grant_type' => 'refresh_token',
		'client_id' => $clientId,
		'client_secret' => $clientSecret,
		'refresh_token' => $resultParams['meLibreAccount']['mlRefreshToken']
	];

	try {
		$tokenRequest = $meli->post('/oauth/token', $requestParams);
	} catch (\Exception $e) {
		$msg_erro .= $e->getMessage();
	}

	$resultParams['meLibreAccount']['mlRefreshToken'] = $tokenRequest['body']->refresh_token;
	$resultParams['meLibreAccount']['mlAccessToken'] = $tokenRequest['body']->access_token;
	$resultParams['meLibreAccount']['mlExpires'] = time() + 21600;

	$newResultParams = json_encode($resultParams);

	pg_query($con, "BEGIN TRANSACTION");

	$queryUpdate = "UPDATE tbl_fabrica
					SET parametros_adicionais = '{$newResultParams}'
					WHERE fabrica = {$login_fabrica}";
	$result = pg_query($con, $queryUpdate);

	if (strlen(pg_last_error()) > 0 OR !empty($msg_erro) OR pg_affected_rows($result) > 1) {
		$msg_erro .= pg_last_error();
		pg_query($con, "ROLLBACK");
	} else {
		pg_query($con, "COMMIT");
	}
}

// ## //
$requestParams = [
	'access_token' => $resultParams['mlAccessToken'],
	'seller_id' => $resultParams['mlUserId'],
];


$endLoop = false;
$offset = 0;
$mlCount = 0;
$meLibreItems = [];

if (array_key_exists('meLibreAccount', $resultParams)) {
	while ($endLoop === false) {
		$response = getItensML($offset, $resultParams['meLibreAccount']);
		foreach ($response['body']['results'] as $k => $item) {
			$variations = $meli->get('/items/' . $item['id'] . '/variations', $requestParams, true);



			if (!empty($variations['body'])) {
				foreach ($variations['body'] as $kk =>  $variation) {
					foreach ($variation['attribute_combinations'] as $kkk => $attribute) {

						 $condVoltagem = "";
						if ($attribute['id'] == 'VOLTAGE') {
							$condVoltagem = "AND pe.atributo->'voltagem' = '\"{$attribute['value_name']}\"'";
						}
							$queryPeca = "SELECT
											pe.peca,
											pe.peca_externa,
											pe.id_externo,
											p.referencia,
											p.descricao
										FROM tbl_peca_externa pe
										JOIN tbl_peca p ON p.peca = pe.peca AND p.fabrica = {$login_fabrica}
										WHERE pe.fabrica = {$login_fabrica}
										AND pe.id_externo = '" . $item['id'] . "'
									    {$condVoltagem}";
							$resultPeca = pg_query($con, $queryPeca);
							
							if (pg_num_rows($resultPeca) == 0) {
								$meLibreItems[$mlCount] = [
									'id' => $item['id'],
									'title' => iconv('UTF-8', 'ISO-8859-1', $item['title']),
									'voltage' => $attribute['value_name']
								];

								$mlCount++;
							}
					}
				}
			} else {
				$queryPeca = "SELECT
								pe.peca,
								pe.peca_externa,
								pe.id_externo,
								p.referencia,
								p.descricao
							FROM tbl_peca_externa pe
							JOIN tbl_peca p ON p.peca = pe.peca AND p.fabrica = {$login_fabrica}
							WHERE pe.fabrica = {$login_fabrica}
							AND pe.id_externo = '" . $item['id'] . "'
							";
				$resultPeca = pg_query($con, $queryPeca);

				if (pg_num_rows($resultPeca) == 0) {
					$meLibreItems[$mlCount] = [
						'id' => $item['id'],
						'title' => iconv('UTF-8', 'ISO-8859-1', $item['title'])
					];

					$mlCount++;
				}
			}
		}

		if ($response['body']['paging']['total'] > 50) {
			$offset += 50;

			if ($offset > $response['body']['paging']['total']) {
				$endLoop = true;
			}
		} else {
			$endLoop = true;
		}

	}

	usort($meLibreItems, 'compare');
}

function compare($meLibreItems , $b) {
	return strcmp($meLibreItems['title'], $b['title']);
}

// ## //
$queryAll = "SELECT
				pe.peca,
				pe.peca_externa,
				pe.id_externo,
				p.referencia,
				p.descricao,
				pe.atributo->'titulo' AS titulo
			FROM tbl_peca_externa pe
			JOIN tbl_peca p ON p.peca = pe.peca AND p.fabrica = {$login_fabrica}
			WHERE pe.fabrica = {$login_fabrica}
			ORDER BY p.descricao";
$result = pg_query($con, $queryAll);
$resultAll = pg_fetch_all($result);

// ## //
$queryPecasInt = "SELECT
					tbl_peca.peca,
					tbl_peca.referencia,
					tbl_peca.descricao
				FROM tbl_peca
				WHERE tbl_peca.fabrica = {$login_fabrica}
				AND tbl_peca.produto_acabado IS TRUE
				AND tbl_peca.ativo IS TRUE
				ORDER BY tbl_peca.descricao";
$result = pg_query($con, $queryPecasInt);
$resultPecasInt = pg_fetch_all($result);


// ## //
$layout_menu = "cadastro";
$title = "RELACIONAMENTO DE PRODUTOS COM MERCADO LIVRE";

include "cabecalho_new.php";
$plugins = ['select2'];
include "plugin_loader.php";

?>

<style>
	.form-group {
		padding:10px 0px;
	}

	.form-group h5 {
		text-align:center;
	}

	.form-group label {
		font-size:14px;
		font-weight:bold;
		text-align:center;
	}

	.form-group center {
		padding:5px 0px;
	}

	.form-group select {
		width:80%;
	}

	.form-group:first-child {
		padding:5px;
		background-color:#596D9B;
		color:#FFF;
	}

	.cad {
		text-align:center;
	}

	.span12 {
		background-color:#D9E2EF;
	}

	p {
		text-align:center;
		font-size:12px;
		font-weight:bold;
	}

	#warning {
		padding:5px 0px;
		display:none;
		text-align:center;
		font-size:14px;
		font-weight:bold;
		color:#FFF;
	}
</style>

<div class="row-fluid">
	<div class="span12">
		<div id="warning">
		</div>
		<form>
			<div class="form-group" style="margin-bottom:20px;">
				<h5>Para realizar o relacionamento, selecione os produtos de ambas plataformas</h5>
			</div>
			<div class="form-group">
				<label>Peças cadastradas no sistema:</label>
				<center>
					<select class="form-control produto_acabado" id="produto_acabado">
						<option value="selecione">Selecione</option>
						<?php foreach ($resultPecasInt as $pecaInt) { ?>
							<option value="<?= $pecaInt['peca'] ?>"><?= $pecaInt['descricao'] ?> - <?= $pecaInt['referencia'] ?></option>
						<?php } ?>
					</select>
				</center>
			</div>
			<div class="form-group">
				<label>Peças cadastradas no Mercado Livre:</label>
				<center>
					<select class="form-control produto_ml" id="produto_ml">
						<option value="selecione">Selecione</option>
						<?php foreach ($meLibreItems as $item) { ?>
							<option title="<?= $item['id'] ?>" value="<?= $item['id'] ?>" <?= !empty($item['voltage']) ? "data-voltage='" . $item['voltage'] . "'" : "" ?>>
								<?= $item['title'] ?><?= !empty($item['voltage']) ? " (" . $item['voltage'] . ")" : "" ?>
							</option>
						<?php } ?>
					</select>
				</center>
			</div>
			<div class="form-group cad">
				<button class="btn btn-primary btn-medium" id="btn_acao" type="button">Relacionar produtos</button>
			</div>
			<p class="alert-info" style="margin-top:30px;">Estão listados somente produtos ativos marcados como 'Produto Acabado'</p>
		</form>
	</div>
</div>
<br />
<table class="table table-bordered">
	<thead>
		<tr class="titulo_coluna" style="font-size:14px;">
			<th colspan="3">Peças Relacionadas</th>
		</tr>
		<tr class="titulo_coluna">
			<th>Peças</th>
			<th>ID Mercado Livre</th>
			<th>Ação</th>
		</tr>
	</thead>
	<tbody class="items">
		<?php foreach ($resultAll as $peca) { ?>
		<tr>
			<td><b>Nome da peça:</b> <?= $peca['descricao'] ?></td>
			<td rowspan="2" style="text-align:center;font-weight:bold;vertical-align:middle"><?= $peca['id_externo'] ?></td>
			<td rowspan="2" style="text-align:center;vertical-align:middle"><button class="btn btn-mini btn-danger btn_remove" data-id="<?= $peca['peca_externa'] ?>">&times;</button></td>
		</tr>
		<tr>
			<td class="title_anuncio" data-titulo="<?= preg_replace("/\"/", "", iconv('UTF-8', 'ISO-8859-1', $peca['titulo'])) ?>">
				<b>Título do anúncio:</b> <?= preg_replace("/\"/", "", iconv('UTF-8', 'ISO-8859-1', $peca['titulo'])) ?>
			</td>
		</tr>
		<?php } ?>
	</tbody>
</table>

<script>
	function removeItem(objeto) {
		var linha = $(objeto).parents("tr");

		if (confirm("Deseja remover este relacionamento?")) {
			var pecaExterna = $(linha).find(".btn_remove").data("id");

			$.ajax('relaciona_produtos_ml.php', {
				method: 'POST',
				data: {
					remove: true,
					pecaExterna: pecaExterna
				}
			}).done(function (response) {
				if (response != "success") {
					$(window).scrollTop(0);
					$("#warning").slideToggle(200, function () {
						$("#warning").css("background-color", "#EF2327;");
						$("#warning").html("Erro ao remover relação");
					});
				} else {
					var option = $("<option></option>");
					$(option).html($(linha).next().find(".title_anuncio").data("titulo"));
					$(option).val(pecaExterna);

					$("#produto_ml").append(option);

					$(linha).next().remove();
					$(linha).remove();
				}
			});
		}
	}

	$(function () {
		$("#produto_acabado").select2();
		$("#produto_ml").select2();

		$(".btn_remove").on("click", function () {
			removeItem($(this));
		});

		$("#btn_acao").on("click", function () {
			var produtoAcabado = $("#produto_acabado").val();
			var produtoAcabadoNome = $("#produto_acabado option:selected").html();
			var produtoMlNome = $("#produto_ml option:selected").html();
			var produtoMlVal = $("#produto_ml").val();
			var produtoMlVolt = $("#produto_ml option:selected").data("voltage");

			$.ajax('relaciona_produtos_ml.php', {
				method: 'POST',
				data: {
					relaciona: true,
					produtoAcabado: produtoAcabado,
					produtoMlNome: produtoMlNome,
					produtoMlVal: produtoMlVal,
					produtoMlVolt: produtoMlVolt
				}
			}).done(function (response) {
				response = JSON.parse(response);
				if (response.error.length > 0) {
					$("#warning").slideToggle(200, function () {
						$("#warning").css("background-color", "#EF2327;");
						$("#warning").html("Erro ao relacionar peças");
					});
				} else {
					var tr = $("<tr></tr>");
					
					var tdTele = $("<td></td>");
					$(tdTele).html("<b>Peça Interna: </b>" + produtoAcabadoNome);

					var tdMl = $("<td></td>");
					$(tdMl).html(produtoMlVal);
					$(tdMl).css("text-align", "center");
					$(tdMl).css("font-weight", "bold");
					$(tdMl).attr("rowspan", "2");
					$(tdMl).css("vertical-align", "middle");

					var tdAction = $("<td></td>");
					$(tdAction).css("text-align", "center");
					$(tdAction).attr("rowspan", "2");
					$(tdAction).css("vertical-align", "middle");
					
					var btnAction = $("<button></button>");
					$(btnAction).addClass("btn btn-mini btn-danger btn_remove");
					$(btnAction).data("id", response.id);
					$(btnAction).html("&times;");

					$(btnAction).on("click", function () {
						removeItem($(this));
					});

					$(tdAction).append(btnAction);

					var trMl = $("<tr></tr>");

					var tdNextMl = $("<td></td>", {
						class: "title_anuncio",
						data: {
							titulo: produtoMlNome
						}
					});
					$(tdNextMl).html("<b>Título do Anúncio: </b>" + produtoMlNome);

					$(tr).append(tdTele);
					$(tr).append(tdMl);
					$(tr).append(tdAction);

					$("#produto_ml").find("option:selected").remove();
					$("#select2-produto_ml-container").attr("title", "Selecione");
					$("#select2-produto_ml-container").html("Selecione");

					$(".items").append(tr);

					$(trMl).append(tdNextMl);
					$(".items").append(trMl);
				}
			});
		});
	});
</script>

<? include "rodape.php"; ?>
