<?php
include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$admin_privilegios="cadastros";
include 'autentica_admin.php';

include '../vendor/autoload.php';

include '../classes/Mirrors/Parts/Itens.php';
include '../classes/Mirrors/Parts/Condicoes.php';

$sess = get_cookie_login($_COOKIE['sess']);

function recursiveEncode($param) {
	$res = [];
	foreach ($param as $key => $value) {
		if (is_array($value)) {
			$res[$key] = recursiveEncode($value);
			continue;
		}

		if (is_string($value)) {
			$res[$key] = utf8_encode(trim($value));
		}
	}

	return $res;
}

$granted = false;
if (!empty($sess['cook_user_external_hash']) && !empty($sess['cook_company_external_hash'])) {
	$granted = true;
}

if (in_array($_POST['ajax'], ['exportConditions', 'exportParts', 'genReport'])) {
	switch ($_POST['ajax']) {
		case 'exportConditions':
			$ativo = $_POST['ativos'] == 'true' ? 'AND tc.visivel' : '';

			$query_conds = "SELECT
				tc.condicao, tc.codigo_condicao, tc.visivel, tc.fabrica, tc.descricao, tc.limite_minimo
			FROM tbl_condicao tc
			JOIN tbl_fabrica tf ON tf.fabrica = tc.fabrica
			WHERE tf.fabrica = {$login_fabrica}
			AND json_field('companyExternalHash', tf.parametros_adicionais) IS NOT NULL
			AND json_field('integracaoParts', tf.parametros_adicionais)::boolean
			AND tc.campos_adicionais->>'partsExternalHash' IS NULL
			{$ativo}
			ORDER BY tc.condicao ASC
			LIMIT 100";

			$res_conds = pg_query($con, $query_conds);	
			if (strlen(pg_last_error()) >= 1 || pg_num_rows($res_conds) === 0) {
				$response = ['exception' => 'Ocorreu uma falha na exportação. Por favor, contate o suporte.'];
				break;
			}

			$loteAtual = pg_fetch_all($res_conds);

			$loteAtual = array_map(function ($p) {
				$desc = $p['descricao'];
				unset($p['descricao']);

				$p['descricao'] = $desc;
				$p['valorMinimo'] = $p['limite_minimo'];
				$p['codigo'] = $p['codigo_condicao'];
				$p['ativo'] = $p['visivel'] == 'f' ? 'false' : 'true';
				$p['fabrica'] = $login_fabrica;

				return $p;
			}, $loteAtual);

			$requestBody = [
				'user' => $sess['cook_user_external_hash'],
				'company' => $sess['cook_company_external_hash'],
				'condicoes' => $loteAtual,
				'origin' => 'POSVENDA'
			];

			$encodedBody = recursiveEncode($requestBody);

			$partsCondicoesMirror = new Condicoes();
			try {
				$response = $partsCondicoesMirror->post($requestBody);
			} catch (Exception $e) {
				$response = ['exception' => $e->getMessage()];
				break;
			}

			if (array_key_exists('exception', $response)) {
				break;
			}

			$select_query = "
				SELECT campos_adicionais
				FROM tbl_condicao
				WHERE fabrica = $1
				AND condicao = $2";

			$update_query = "
				UPDATE tbl_condicao
				SET campos_adicionais = $1
				WHERE fabrica = $2
				AND condicao = $3";

			$failed = [];

			foreach ($loteAtual as $cond) {
				foreach ($response['condicoes'] as $condicao) {
					if ($condicao['codigo'] === $cond['codigo']) {

						// getting parametros_adicionais
						$res_select = pg_query_params(
							$con,
							$select_query,
							[$login_fabrica, $cond['condicao']]
						);

						if (strlen(pg_last_error()) >= 1 || pg_num_rows($res_select) >= 2) {
							$response['failed'][] = $condicao;
							continue;
						}

						// setting external hash - parts api
						$campos_adicionais = pg_fetch_result($res_select, 0, 'campos_adicionais');
						$campos_adicionais = json_decode($campos_adicionais, true);

						$campos_adicionais['partsExternalHash'] = $condicao['internal_hash'];
						$campos_adicionais = json_encode($campos_adicionais);

						// updating
						$res_update = pg_query_params(
							$con,
							$update_query,
							[$campos_adicionais, $login_fabrica, $cond['condicao']]
						);

						if (pg_affected_rows($res_update) >= 2 || strlen(pg_last_error()) >= 1) {
							$response['failed'][] = $condicao;
						}
					}
				}
			}

			break;
		case 'exportParts':
			$ativo = $_POST['ativos'] == 'true' ? 'AND tp.ativo IS TRUE' : '';

			$query_pecas = "SELECT
					tp.peca,
					tp.referencia,
					tp.descricao,
					tp.origem,
					tp.ativo,
					tp.ipi,
					tp.unidade,
					tp.voltagem,
					tp.ncm
				FROM tbl_peca tp
				JOIN tbl_fabrica tf ON tf.fabrica = tp.fabrica
				WHERE json_field('companyExternalHash', tf.parametros_adicionais) IS NOT NULL
				AND json_field('integracaoParts', tf.parametros_adicionais)::boolean
				AND json_field('partsExternalHash', tp.parametros_adicionais) IS NULL
				{$ativo}
				AND tp.produto_acabado IS NOT TRUE
				AND tf.fabrica = {$login_fabrica}
				ORDER BY tp.data_input ASC
				LIMIT 100";

			$res_pecas = pg_query($con, $query_pecas);
			if (strlen(pg_last_error()) >= 1 || pg_num_rows($res_pecas) === 0) {
				$response = ['exception' => 'Ocorreu uma falha na exportação. Por favor, contate o suporte.'];
				break;
			}

			$loteAtual = pg_fetch_all($res_pecas);
			$loteAtual = array_map(function ($p) use ($login_fabrica) {
				$desc = $p['descricao'];	
				unset($p['descricao']);

				$p['descricao'] = ['pt_br' => $desc];
				$p['calculo_externo'] = "true";
				$p['fabrica'] = $login_fabrica;

				return $p;
			}, $loteAtual);

			$requestBody = [
				"user" => $sess['cook_user_external_hash'],
				"company" => $sess['cook_company_external_hash'],
				"itens" => $loteAtual,
				"origin" => "POSVENDA"
			];

			$encodedBody = recursiveEncode($requestBody);

			$partsItensMirror = new Itens();
			try {
				$response = $partsItensMirror->post($encodedBody);
			} catch (Exception $e) {
				$response = ['exception' => $e->getMessage()];
				break;
			}

			if (array_key_exists('exception', $response)) {
				break;
			}

			$select_query = "
				SELECT parametros_adicionais
				FROM tbl_peca
				WHERE fabrica = $1
				AND peca = $2";

			$update_query = "
				UPDATE tbl_peca
				SET parametros_adicionais = $1
				WHERE fabrica = $2
				AND peca = $3";

			$failed = [];
			foreach ($loteAtual as $peca) {
				foreach ($response['itens'] as $item) {
					if ($item['dados']['referencia'] === $peca['referencia']) {

						// getting parametros_adicionais
						$res_select = pg_query_params(
							$con,
							$select_query,
							[$login_fabrica, $peca['peca']]
						);

						if (strlen(pg_last_error()) >= 1 || pg_num_rows($res_select) >= 2) {
							$response['failed'][] = $item;
							continue;
						}

						// setting external hash - parts api
						$parametros_adicionais = pg_fetch_result($res_select, 0, 'parametros_adicionais');
						$parametros_adicionais = json_decode($parametros_adicionais, true);

						$parametros_adicionais['partsExternalHash'] = $item['internal_hash'];
						$parametros_adicionais = json_encode($parametros_adicionais);

						// updating
						$res_update = pg_query_params(
							$con,
							$update_query,
							[$parametros_adicionais, $login_fabrica, $peca['peca']]
						);

						if (pg_affected_rows($res_update) >= 2 || strlen(pg_last_error()) >= 1) {
							$response['failed'][] = $item;
						}
					}
				}
			}

			break;
		case 'genReport':
			$lotes = $_POST['excepts'];

			$path = "xls/integracao-" . date(dmyHis) . "-$login_fabrica.csv";
			$file = fopen($path, 'w');

			$headers = ["MENSAGEM", "CAMPO", "VALOR", "LINHA", "LOTE"];
			fputcsv($file, $headers);

			foreach ($lotes as $lote => $data) {
				foreach ($data as $exception_row) {
					$message = $exception_row['exception'];

					$headers = array_keys($exception_row['values']);
					$col = $headers[0];
					$value = $exception_row['values'][$headers[0]];
					$collection_key = !is_null($exception_row['line_number']) ? $exception_row['line_number'] : 'N/A';
					$lote = $lote;

					$row_data = array_map(function ($v) {
						return strtoupper($v);
					}, [$message, $col, $value, $collection_key, $lote]);

					fputcsv($file, $row_data);
				}
			}

			fclose($file);

			$response = ['path' => $path];
			break;

	}

	$response = recursiveEncode($response);
	exit(json_encode($response));
}

if (!empty($_GET['user']) && !empty($_GET['comps'])) {
	$userHash = trim($_GET['user']);
	$companies = explode(',', trim($_GET['comps']));

	foreach ($companies as $company) {
		if ($company === $companyExternalHash) {
			$granted = true;

			$sess['cook_user_external_hash'] = $userHash;
			$sess['cook_company_external_hash'] = $companyExternalHash;
			set_cookie_login($_COOKIE['sess'], $sess);

			break;
		}
	}

	echo "<script>window.close()</script>";
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' || ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['ajax'] === 'reDo')) {
	
	$partsAtivo = $_POST['active'] == 'true' ? 'AND tp.ativo' : '';
	$condsAtivo = $_POST['active'] == 'true' ? 'AND tc.visivel' : '';
	
	$query_pecas = "
		WITH total_pecas AS (
			SELECT
				tp.peca
			FROM tbl_peca tp
			JOIN tbl_fabrica tf ON tf.fabrica = tp.fabrica
			WHERE json_field('companyExternalHash', tf.parametros_adicionais) IS NOT NULL
			AND json_field('integracaoParts', tf.parametros_adicionais)::boolean
			AND NOT tp.produto_acabado
			AND tf.fabrica = {$login_fabrica}
			{$partsAtivo}
		), exportadas AS (
			SELECT
				tp.peca
			FROM tbl_peca tp
			JOIN tbl_fabrica tf ON tf.fabrica = tp.fabrica
			WHERE json_field('companyExternalHash', tf.parametros_adicionais) IS NOT NULL
			AND json_field('integracaoParts', tf.parametros_adicionais)::boolean
			AND json_field('partsExternalHash', tp.parametros_adicionais) IS NOT NULL
			AND NOT tp.produto_acabado
			AND tf.fabrica = {$login_fabrica}	
			{$partsAtivo}
		)
		SELECT
			count(exportadas.peca) AS total_exportadas,
			count(total_pecas.peca) AS total_pecas
		FROM total_pecas
		LEFT JOIN exportadas ON exportadas.peca = total_pecas.peca
	";

	$res_pecas = pg_query($con, $query_pecas);
	if (strlen(pg_last_error()) === 0) {
		$totalp_pecas = pg_fetch_result($res_pecas, 0, 'total_pecas');
		$totalp_exportadas = pg_fetch_result($res_pecas, 0, 'total_exportadas');
	}

	if ($granted) {
		$query_conds = "
			WITH total_conds AS (
				SELECT
					tc.condicao
				FROM tbl_condicao tc
				JOIN tbl_fabrica tf ON tf.fabrica = tc.fabrica
				WHERE tf.fabrica = {$login_fabrica}
				AND json_field('integracaoParts', tf.parametros_adicionais)::boolean
				AND json_field('companyExternalHash', tf.parametros_adicionais) IS NOT NULL
				{$condsAtivo}
			), exportadas AS (
				SELECT
					tc.condicao
				FROM tbl_condicao tc
				JOIN tbl_fabrica tf ON tf.fabrica = tc.fabrica
				WHERE tf.fabrica = {$login_fabrica}
				AND tc.campos_adicionais->>'partsExternalHash' IS NOT NULL
				AND json_field('integracaoParts', tf.parametros_adicionais)::boolean
				AND json_field('companyExternalHash', tf.parametros_adicionais) IS NOT NULL
				{$condsAtivo}
			)
			SELECT
				count(total_conds.condicao) AS total_condicoes,
				count(exportadas.condicao) AS total_exportadas
			FROM total_conds
			LEFT JOIN exportadas ON exportadas.condicao = total_conds.condicao
		";

		$res_conds = pg_query($con, $query_conds);
		if (strlen(pg_last_error()) === 0) {
			$totalc_conds = pg_fetch_result($res_conds, 0, 'total_condicoes');
			$totalc_exportadas = pg_fetch_result($res_conds, 0, 'total_exportadas');
		} 
	}

	if (!empty($_POST)) {
		$res = [
			'pecas' => ['total' => $totalp_pecas, 'exportadas' => $totalp_exportadas],
			'condicoes' => ['total' => $totalc_conds, 'exportadas' => $totalc_exportadas], 
			'lotes' => [
				'pecas' => ceil(($totalp_pecas - $totalp_exportadas) / 100), 
				'condicoes' => ceil(($totalc_conds - $totalc_exportadas) / 100)
			]
		];

		exit(json_encode($res));
	}
}

$auth_params = $login_fabrica . $login_admin;
$auth_token = hash('sha256', $auth_params . "3H4E7Y4C2oD8eR43t3Hi8sI4s2AU7ni3QS2aL8T1");

include 'funcoes.php';

$layout_menu = "cadastro";
$title = "INTEGRAÇÃO SERVIÇO DE VENDA DE PEÇAS";
$plugins = ['font_awesome'];

include "cabecalho_new.php";
include ("plugin_loader.php");

?>

<style type="text/css">
	.table td {vertical-align: middle;}
	.table input {margin: 0; max-width: 80px;}

	tr td:last-child {text-align: right; font-weight: bold;}

	.export-row {margin-top: 20px;}
	.export-row div:nth-child(3) {text-align: right;}

	table th {background-color: #596d9b; color: #fff;}

	span.warning {color: #ff0000; font-weight: bold;}

	.checkbox {margin: 5px 0;}
	.checkbox strong {margin-left: 5px;}

	#cancel-btn {margin-right: 3px;}
</style>

<?php if (!$granted): ?>
<div class="authentication" <?= !$granted ? "" : "hidden" ?>>
	<div class="row-fluid" style="margin-bottom: 50px">
		<div class="span3"></div>
		<div class="span6" style="text-align: center">
			<h5 class="text-center">Para realizar as exportações, será necessário realizar a autenticação na plataforma User Auth.</h5>
			<button type="button" class="btn btn-primary" id="autenticar_admin" style="text-align: center">Logar na User Auth</button>
			<input type="hidden" value="<?= $auth_params . "|" . $auth_token ?>"/>
		</div>
		<div class="span3"></div>
	</div>
</div>
<?php elseif ($granted): ?>
<div class="tabbable" style="margin-bottom: 50px">
	<ul class="nav nav-tabs">
		<li id="linkone" class="active"><a href="#parts" data-toggle="tab">Peças</a></li>
		<li id="linktwo"><a href="#conditions" data-toggle="tab">Condições de Pagamento</a></li>
	</ul>
	<div class="tab-content">
		<div class="tab-pane active" id="parts">
			<div class="row-fluid warnings">
				<div class="span1"></div>
				<div class="span10"></div>
			</div>
			<div class="row-fluid">
				<div class="span1"></div>
				<div class="span10">
					<table class="table table-bordered table-striped">
						<thead>
							<tr>
								<th>Dados</th>
								<th>Valores</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>Total de Peças</td>
								<td class="totalp_pecas"><?= $totalp_pecas ?></td>
							</tr>
							<tr>
								<td>Total de Peças Exportadas</td>
								<td class="totalp_exportadas"><?= $totalp_exportadas ?></td>
							</tr>
							<tr>
								<td>Total de Peças Restantes</td>
								<td class="totalp_restantes"><?= $totalp_pecas - $totalp_exportadas ?></td>
							</tr>
							<tr>
								<td>Lotes a Serem Processados</td>
								<td>
									<?php $total_lotes_pecas = ceil(($totalp_pecas - $totalp_exportadas) / 100);
										$maxLote = $total_lotes_pecas > 0 ? 1 : 0 ?>
									<input type="number" class="form-control" id="lotes-parts-qtde" value="<?= $maxLote ?>" 
										min="<?= $maxLote ?>" max="<?= $total_lotes_pecas ?>" /> de
									<input type="text" class="total_lotes_pecas" value="<?= $total_lotes_pecas ?>" readonly>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="tab-pane" id="conditions">
			<div class="row-fluid warnings">
				<div class="span1"></div>
				<div class="span10"></div>
			</div>
			<div class="row-fluid">
				<div class="span1"></div>
				<div class="span10">
					<table class="table table-bordered table-striped">
						<thead>
							<th>Dados</th>
							<th>Valores</th>
						</thead>
						<tbody>
							<tr>
								<td>Total de Condições de Pagto</td>
								<td class="totalc_conds"><?= $totalc_conds ?></td>
							</tr>
							<tr>
								<td>Total de Condições de Pagto. Exportadas</td>
								<td class="totalc_exportadas"><?= $totalc_exportadas ?></td>
							</tr>
							<tr>
								<td>Total de Condições de Pagto. Restantes</td>
								<td class="totalc_restantes"><?= $totalc_conds - $totalc_exportadas ?></td>
							</tr>
							<tr>
							<?php $total_lotes_cond = ceil(($totalc_conds - $totalc_exportadas) / 100) ?>
								<td>Lotes a Serem Processados</td>
								<td>
									<?php $maxLote = $total_lotes_cond > 0 ? 1 : 0 ?>
									<input type="number" class="form-control" id="lotes-conds-qtde" value="<?= $maxLote ?>" 
										min="<?= $maxLote ?>" max="<?= $total_lotes_cond ?>" /> de
									<input type="text" class="lotes_conds_qtde" value="<?= $total_lotes_cond ?>" readonly>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="row-fluid">
			<div class="span1"></div>
			<div class="span10">
				<small>
					<span class="warning">*</span> Os dados são exportadas em lotes de 100 registros. Tenha em mente que o processo pode demorar quando há um alto número de lotes.<br />
					<span class="warning">**</span> Não feche esta página enquanto o processo não for concluído.<br />
					<span class="warning">***</span> Ao cancelar, o lote atual terminará a exportação e, só então, o processo será abortado.
				</small>
			</div>
		</div>
		<div class="row-fluid export-row">
			<div class="span1"></div>
			<div class="span4">
				<label class="checkbox">
					<input type="checkbox" id="only-active" /><strong>Exportar Somente Itens Ativos</strong>
				</label>
				<label class="checkbox">
					<input type="checkbox" id="ignore-errors" /><strong>Ignorar Lotes Com Erro</strong>
				</label>
			</div>
			<div class="span6">
				<button type="button" class="btn btn-danger" id="cancel-btn" disabled>Cancelar</button>
				<button type="button" class="btn btn-primary" id="export-btn">Iniciar Exportação</button>
			</div>
		</div>
	</div>
</div>
<? elseif (!$granted): ?>
	<div class="row-fluid" style="margin-bottom: 50px">
		<div class="span3"></div>
		<div class="span6" style="text-align: center">
			<h5 class="text-center">Você não tem permissðo para acessar essa tela. Você será redirecionado.</h5>
			<script type="text/javascript"> setInterval(function () {
				location = `http://userauthtc.telecontrol.com.br/`;
			}, 3000) </script>
		</div>
		<div class="span3"></div>
	</div>
<? endif; ?>

<script type="text/javascript">
	'use strict';
	document.addEventListener('DOMContentLoaded', function (e) {
		init();
	})

	var auth;
	var cancel = false;


	function init() {
		const authAdmin = document.querySelector('#autenticar_admin');
		if (authAdmin !== null) {
			authAdmin.addEventListener('click', authUser);
		}

		const exportData = document.querySelector('#export-btn');
		if (exportData !== null) {
			exportData.addEventListener('click', exportItens)
		}

		const onlyActive = document.querySelector('#only-active');
		if (onlyActive !== null) {
			onlyActive.addEventListener('change', onlyActiveItens)
		}

		const cancelExport = document.querySelector('#cancel-btn');
		if (cancelExport !== null) {
			cancelExport.addEventListener('click', cancelItens);
		}
	}

	function authUser(e) {
		const values = e.target.parentElement.lastElementChild.value.split('|');
		const matcher = values[0];
		const tokenizer = values[1];

		const href = `http://userauthtc.telecontrol.com.br/?auth_token=${tokenizer}&matcher=${matcher}&redirect_uri=${location.href}`;
		if (typeof auth === 'undefined') {
			auth = open(href, 'popAuth', 'height=650,width=550')
			const closedCheck = setInterval(function () {
				if (auth.closed) {
					location.reload();
					clearInterval(closedCheck)
				}
			}, 1000)
		}

		auth.focus();
	}

	function onlyActiveItens(e) {
       	const active = e.target.checked;

       	const container = document.querySelector('.tab-pane.active .warnings div.span10');

		const alert = document.createElement('div');
		alert.className = `alert alert-warning`;
		alert.id = `update-table-alert`;

		const button = document.createElement('button');
		button.setAttribute('type', 'button');
		button.setAttribute('data-dismiss', 'alert');
		button.className = 'close';
		button.innerHTML = '&times;';

		const messageEl = document.createElement('strong');
			  messageEl.textContent = "Os valores da tabela serão atualizados, aguarde.";

		alert.prepend(messageEl);
		alert.prepend(button);


		if (!$('#update-table-alert').is(':visible')) {
			container.append(alert);
		}

		setTimeout(function() {
			$('.tab-pane.active #update-table-alert').hide();
		}, 5000);

       	$.post(location, {ajax: 'reDo', active})
           .then(function (res) {
               const response = JSON.parse(res);

               $('.totalp_pecas').html(response.pecas.total);
               $('.totalp_exportadas').html(response.pecas.exportadas);

               $('.totalc_conds').html(response.condicoes.total);
               $('.totalc_exportadas').html(response.condicoes.exportadas);

       		   $('.total_lotes_pecas').val(response.lotes.pecas);
	           $('.lotes_conds_qtde').val(response.lotes.condicoes);

	           $('#lotes-parts-qtde').attr('max', $('.total_lotes_pecas').val());
	           $('.lotes_conds_qtde').attr('max', response.lotes.condicoes);

	           $('.totalp_restantes').html(parseInt(response.pecas.total) - parseInt(response.pecas.exportadas));
	           $('.totalc_restantes').html(parseInt(response.condicoes.total) - parseInt(response.condicoes.exportadas));
         	});
	}


	function exportItens(e) {

		const btn = e.target;
		const btnCancel = $('#cancel-btn');
		const ativos = document.querySelector('#only-active').checked;

		const tab = document.querySelector('.tab-pane.active').getAttribute('id');
		const ajaxMethod = tab === 'parts' ? 'exportParts' : 'exportConditions';

		const ignoreErrors = document.querySelector('#ignore-errors').checked;

		const inputLotes = tab === 'parts' 
			? parseInt(document.querySelector('#lotes-parts-qtde').value) 
			: parseInt(document.querySelector('#lotes-conds-qtde').value);

		const maxLotes = tab === 'parts' 
			? parseInt(document.querySelector('.total_lotes_pecas').value) 
			: parseInt(document.querySelector('.lotes_conds_qtde').value);

		if (isNaN(inputLotes) || inputLotes <= 0) {
			return;
		}

		btn.textContent = 'Iniciando Processo...';

		const spinner = document.createElement('i');
		spinner.className = 'fa fa-spinner fa-spin';
		spinner.style.marginLeft = '5px';
		
		setTimeout(function () {
			btn.setAttribute('disabled', 'disabled');

			btn.innerText = 'Exportando Lote 1';
			btn.append(spinner);

			const exceptionsNFailures = {};

			const exporter = function (lote) {
				return new Promise(function (resolve, reject) {

					$.post(location, {ajax: ajaxMethod, lote, ativos})
						.done(function (response) {
							if (response === null) {
								reject('Ocorreu uma falha na exportação. Por favor, contate o suporte.');
							}

							response = JSON.parse(response);
							const { exception, failed, itens } = response;

							if (typeof exception !== 'undefined' || typeof failed !== 'undefined') {
								exceptionsNFailures[lote] = [];
							}

							if (typeof exception !== 'undefined') {
								exceptionsNFailures[lote].push(response);
								if (!ignoreErrors) {
									reject(response.exception);
								}

								resolve();
							}

							if (typeof failed !== 'undefined') {
								if (failed.length >= 1) {
									failed.forEach(function (item) {
										exceptionsNFailures[lote].push(item);
									})

									if (!ignoreErrors) {
										reject('Alguns itens falharam ao serem exportados. Abaixo você poderá gerar um relatório de erros.');
										$(btnCancel).prop({disabled: true});
										$(btn).prop({disabled: false});
									}
								}
							}

							resolve();

							let totalPecasExp = parseInt($('.totalp_exportadas').html());
							let totalCondExp = parseInt($('.totalc_exportadas').html());

			                $('.totalp_exportadas').html(response.itens ? totalPecasExp + response.itens.length : totalPecasExp);
		                   	$('.totalc_exportadas').html(response.condicoes ? totalCondExp + response.condicoes.length : totalCondExp);

		                   	$('.totalp_restantes').html(parseInt($('.totalp_pecas').html()) - parseInt($('.totalp_exportadas').html()));
	           				$('.totalc_restantes').html(parseInt($('.totalc_conds').html()) - parseInt($('.totalc_exportadas').html()));

						})
						.fail(function () {
							reject('Ocorreu uma falha na exportação. Por favor, contate o suporte.');
						});
				});
			}

			const processItens = async function (lote) {

				if (lote >= 1 && lote < inputLotes) {
					$('#cancel-btn').prop({disabled: false});
				} else {
					$('#cancel-btn').prop({disabled: true});
				}

				await exporter(lote)
					.then(function (response) {
						let newPLotes = Math.ceil(parseInt(document.querySelector('.totalp_restantes').innerHTML) / 100);
						let newCLotes = Math.ceil(parseInt(document.querySelector('.totalc_restantes').innerHTML) / 100);

						if (cancel) {
							const container = document.querySelector('.tab-pane.active .warnings div.span10');

							const alert = document.createElement('div');
							alert.className = `alert alert-info`;
							alert.id = `cancel-alert`;

							const button = document.createElement('button');
							button.setAttribute('type', 'button');
							button.setAttribute('data-dismiss', 'alert');
							button.className = 'close';
							button.innerHTML = '&times;';

							const messageEl = document.createElement('strong');
								  messageEl.textContent = "Exportação cancelada!";

							alert.prepend(messageEl);
							alert.prepend(button);

							if (!$('#cancel-alert').is(':visible')) {
								container.append(alert);
							}
							
							$(btn).find('i').remove();
							$(btn).prop({innerText: 'Iniciar Exportação'});
							$(btn).prop({disabled: false});
							$(btn).show();
							$(btnCancel).find('i').remove();
							$(btnCancel).prop({innerText: 'Cancelar'});
							$(btnCancel).prop({disabled: true});

							if (newPLotes === 0 || newCLotes === 0) {
								$('#export-btn').prop({disabled: true});
							}

							$('.total_lotes_pecas').val(newPLotes);
							$('.lotes_conds_qtde').val(newCLotes);

							let finalPLote = document.querySelector('.totalp_restantes').innerHTML > 0 
							? document.querySelector('#lotes-parts-qtde').value = 1
							: document.querySelector('#lotes-parts-qtde').value = 0

							let finalCLote = document.querySelector('.totalc_restantes').innerHTML > 0 
							? document.querySelector('#lotes-conds-qtde').value = 1
							: document.querySelector('#lotes-conds-qtde').value = 0

							return;
						}

						if (inputLotes > lote) {
							lote++;
							btn.textContent = 'Exportando Lote ' + lote;
							btn.append(spinner);

							return processItens(lote);
						}

						let finalPLote = document.querySelector('.totalp_restantes').innerHTML > 0 
							? document.querySelector('#lotes-parts-qtde').value = 1
							: document.querySelector('#lotes-parts-qtde').value = 0

						let finalCLote = document.querySelector('.totalc_restantes').innerHTML > 0 
							? document.querySelector('#lotes-conds-qtde').value = 1
							: document.querySelector('#lotes-conds-qtde').value = 0

						$('.total_lotes_pecas').val(newPLotes);
						$('.lotes_conds_qtde').val(newCLotes);

						btn.textContent = 'Iniciar Exportação';
						$(btn).prop({disabled: false});

						if (Object.keys(exceptionsNFailures).length >= 1) {
							buildMessage('Alguns lotes falharam ao serem exportados. Abaixo você poderá gerar um relatório de erros.', 'warning');
							$(btnCancel).prop({disabled: true});
							$(btn).prop({disabled: false});
							return;
						}

						buildMessage('Exportação Concluída Com Sucesso!', 'success');
						$(btnCancel).prop({disabled: true});
						$(btn).prop({disabled: false});
					})
					.catch(function (err) {
						btn.textContent = 'Iniciar Exportação';
						buildMessage(err, 'error');
						return;
					});
			}

			const generateReport = function (excepts, callback) {
				$.post(location, {ajax: 'genReport', excepts})
					.then(function (res) {
						callback(JSON.parse(res));
					})
			}

			const buildMessage = function (message, type) {
				const container = document.querySelector('.tab-pane.active .warnings div.span10');

				const alert = document.createElement('div');
				alert.className = `alert alert-${type}`;
				alert.id = `alert-${type}`;

				const button = document.createElement('button');
				button.setAttribute('type', 'button');
				button.setAttribute('data-dismiss', 'alert');
				button.className = 'close';
				button.innerHTML = '&times;';

				if (type === "warning" || type === "error") {
					if (Object.keys(exceptionsNFailures).length >= 1) {

						const fileButton = document.createElement('a');
						fileButton.className = 'btn btn-warning';
						fileButton.setAttribute('target', '_blank');
						fileButton.innerHTML = '<i class="fas fa-file-alt"></i> Gerar Relatório';

						generateReport(exceptionsNFailures, function (res) {
							const { path } = res;
							fileButton.setAttribute('download', path.split('/').reverse()[0]);
							fileButton.setAttribute('href', path)
						});

						alert.innerHTML = '<br /><br />';
						alert.append(fileButton);
					}
				}

				const messageEl = document.createElement('strong');
				messageEl.textContent = message;

				alert.prepend(messageEl);
				alert.prepend(button);

				if (!$(`#alert-${type}`).is(':visible')) {
					container.append(alert);
				}
			}

			processItens(1)
		}, 1500);
	}

	function cancelItens(e) {
		const btn = e.target;

		$(btn).prop({disabled: true, innerText: 'Cancelando...'});
		$('#export-btn').hide();

		cancel = true;
	}

</script>
<? include "rodape.php"; ?>
