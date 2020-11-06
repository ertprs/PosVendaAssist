<?php
/**
 * 
 * @author Gabriel Tinetti
 *
*/

$curUrl = $_SERVER['HTTPS'] === 'on' ? "https" : "http";

include 'dbconfig.php';
include 'includes/dbconnect-inc.php';

$authUrl = "";

if ($_serverEnvironment == 'production') {
	$curUrl .=  "://posvenda.telecontrol.com.br" . $_SERVER['PHP_SELF'];
	$authUrl = "https://posvenda.telecontrol.com.br/assist/admin/autentica_ml.php";
} elseif ($_serverEnvironment == 'development') {
	$curUrl .=  "://novodevel.telecontrol.com.br" . $_SERVER['PHP_SELF'];
	$authUrl = "https://novodevel.telecontrol.com.br/~gabriel/PosVendaAssist/admin/autentica_ml.php";
}

$admin_privilegios="cadastros";
include 'autentica_admin.php';
include '../vendor/autoload.php';
include 'funcoes.php';

require '../classes/Posvenda/Meli/meli.php';

$meli = new Meli(
	'4814007018102931',
	'bQq81aS24yoHydpTLhh0AuTJVjB7YvQq'
);

$query_params = "SELECT
					parametros_adicionais
				FROM tbl_fabrica
				WHERE fabrica = {$login_fabrica}";

if ($_POST['autentica']) {
	$url = $meli->getAuthUrl($authUrl, Meli::$AUTH_URL['MLB']);

	echo $url;
	exit;
}

if ($_POST['removeAcc']) {
	$result = pg_query($con, $query_params);
	$parametros_adicionais = pg_fetch_result($result, 0, 'parametros_adicionais');
	$array_adicionais = json_decode($parametros_adicionais, true);

	if ($array_adicionais['meLibreAccount']) {
		unset($array_adicionais['meLibreAccount']);
	} else {
		echo "Não existem contas cadastradas para esta fábrica.";
		exit;
	}

	$array_adicionais = json_encode($array_adicionais);

	$query_update = "UPDATE tbl_fabrica SET parametros_adicionais = '{$array_adicionais}' WHERE fabrica = {$login_fabrica}";
	$result = pg_query($con, $query_update);

	$response = "success";
	if (strlen(pg_last_error()) > 0 OR pg_affected_rows($result) > 1) {
		$response = "Não foi possível adicionar sua conta. Tente novamente em instantes.";
	}

	echo $response;
	exit;
}

if ($_GET['code']) {
	$code = $_GET['code'];

	$userAccess = $meli->authorize($code, $authUrl);
	
	if ($userAccess['httpCode'] == 200) {
		$params = ['access_token' => $userAccess['body']->access_token];
		$user = $meli->get('/users/me', $params);

		$result = pg_query($con, $query_params);
		$parametros_adicionais = pg_fetch_result($result, 0, 'parametros_adicionais');
		$array_adicionais = json_decode($parametros_adicionais, true);

		$array_adicionais['meLibreAccount'] = [
			'mlUserNickname' 	=> $user['body']->nickname,
			'mlUserId' 		=> $userAccess['body']->user_id,
			'mlAccessToken' 	=> $userAccess['body']->access_token,
			'mlRefreshToken' 	=> $userAccess['body']->refresh_token,
			'mlExpires' => strtotime('now') + 21600
		];

		$array_adicionais = json_encode($array_adicionais);

		pg_query($con, "BEGIN");

		$query_update = "UPDATE 
							tbl_fabrica
						SET parametros_adicionais = '{$array_adicionais}'
						WHERE fabrica = {$login_fabrica}";
		$result = pg_query($con, $query_update);

		if (strlen(pg_last_error()) > 0 OR pg_affected_rows($result) > 1) {
			pg_query($con, "ROLLBACK");
		} else {
			pg_query($con, "COMMIT");
		}
	}
}

$result = pg_query($con, $query_params);

$params_adicionais = [];

if (strlen(pg_last_error()) == 0) {
	$params_adicionais = pg_fetch_result($result, 0, 'parametros_adicionais');
	$params_adicionais = json_decode($params_adicionais, true);
}

$layout_menu = "cadastro";
$title = "AUTENTICAÇÃO COM O MERCADO LIVRE";

include "cabecalho_new.php";
include ("plugin_loader.php");

?>

<style>
	#pages-wrapper tr:first-child td {
		text-align:center;
		font-size:14px;
	}
</style>

<div class="row-fluid">
	<table class="pages-list table table-striped table-bordered table-hover">
		<thead style="font-size:15px;background-color:#596D9B;color:#FFF;">
			<tr>
				<th>Nome de Usuário</th>
				<th>ID</th>
				<th>Remover</th>
			</tr>
		</thead>
		<tbody id="pages-wrapper">
			<?php if ($params_adicionais['meLibreAccount']) { ?>
			<tr>
				<td><?= $params_adicionais['meLibreAccount']['mlUserNickname'] ?></td>
				<td><?= $params_adicionais['meLibreAccount']['mlUserId'] ?></td>
				<td><button class="btn btn-danger btn-mini btn-remove-acc">&times;</button></td>
			</tr>
			<?php } else { ?>
			<tr>
				<td colspan="3" style="text-align:center">Nenhuma conta cadastrada</td>
			</tr>
			<?php } ?>
		</tbody>
	</table>
	<?php if ($params_adicionais['meLibreAccount']) { $display = "display:none;"; } ?>
	<div class="row-fluid add-page" style="text-align:center;margin-top:50px; <?= $display ?>">
		<button id="btn-add-acc" type="button" class="btn btn-primary">Adicionar uma conta</button>
	</div>
</div>

<script>
	var curUrl = "<?= $curUrl ?>";
	$(function () {
		$("#btn-add-acc").on("click", function () {
			$.ajax('autentica_ml.php', {
				method: 'POST',
				data: {
					autentica: true,
				}
			}).done(function (response) {
				window.location.href = response;
			});
		});

		$(".btn-remove-acc").on("click", function () {
			$.ajax('autentica_ml.php', {
				method: 'POST',
				data: {
					removeAcc: true
				}
			}).done(function (response) {
				if (response == "success") {
					window.location.replace(curUrl);
				} else {
					alert(response);
				}
			});
		});
	});
</script>

<? include "rodape.php"; ?>