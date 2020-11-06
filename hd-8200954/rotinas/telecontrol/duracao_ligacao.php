<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
include dirname(__FILE__) . '/../../class/communicator.class.php';

$mail = new TcComm("smtp@posvenda");
$destinatarios = [
	'francisco@telecontrol.com.br',
	'guilherme.curcio@telecontrol.com.br',
	'paulo@telecontrol.com.br',
	'ronald.santos@telecontrol.com.br'
];
$subject = "Erro Rotina Telefonia";
$msg_erro = "";

$queryIntTel = "SELECT
					sub.fabrica
				FROM (SELECT
					tf.fabrica,
					json_field('integracaoTelefonia', tf.parametros_adicionais) AS integracao
				FROM tbl_fabrica tf
				WHERE tf.ativo_fabrica IS TRUE)sub
				WHERE sub.integracao = 'true'
				ORDER BY sub.fabrica ASC";
$result = pg_query($con, $queryIntTel);
$resultIntTel = pg_fetch_all($result);

if (strlen(pg_last_error()) > 0) {
	$msg_erro .= pg_last_error() . "<br />";
}

if (empty($msg_erro)) {
	foreach ($resultIntTel as $fabrica) {
		$fabricaId = $fabrica['fabrica'];

		$queryCall = "SELECT
							thcie.hd_chamado_item_externo,
							thcie.id_ligacao
						FROM tbl_hd_chamado_item_externo thcie
						WHERE thcie.fabrica = {$fabricaId}
						AND (thcie.id_ligacao IS NOT NULL AND LENGTH(thcie.id_ligacao) > 0)
						AND thcie.data_input::date = (CURRENT_DATE - INTERVAL '1 day')::date
						";
		$result = pg_query($con, $queryCall);
		$resultCall = pg_fetch_all($result);

		if (strlen(pg_last_error()) > 0) {
			$msg_erro .= pg_last_error() . "<br />";
		}

		if (empty($msg_erro)) {
			foreach ($resultCall as $call) {
				$uniqueId = $call['id_ligacao'];
				$chamadoItemExterno = $call['hd_chamado_item_externo'];

				$curlCall = curl_init();

				$queryString = "/unique/{$uniqueId}";

				curl_setopt_array($curlCall, array(
					CURLOPT_URL => 'https://api2.telecontrol.com.br/telefonia/duracao-ligacoes' . $queryString,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_TIMEOUT => 90,
					CURLOPT_HTTPHEADER => array(
						"Access-Application-Key: 084f77e7ff357414d5fe4a25314886fa312b2cff",
			            "Access-Env: PRODUCTION",
			            "Cache-Control: no-cache",
			            "Content-Type: application/json"
					)
				));

				$resultApiCall = curl_exec($curlCall);
				$resultApiCall = json_decode($resultApiCall, true);
				if (strlen(curl_error($curlCall)) > 0 OR $resultApiCall['exception']) {
					$msg_erro .= empty($resultApiCall['exception']) ? curl_error($curlCall) : $resultApiCall['exception'];
					$msg_erro .= "<br />";
				}

				if (!empty($resultApiCall)) {
					$duracao = $resultApiCall['duracao'];

					pg_query($con, "BEGIN");
					$queryUpdate = "UPDATE
										tbl_hd_chamado_item_externo
									SET duracao = {$duracao}
									WHERE hd_chamado_item_externo = {$chamadoItemExterno}
									AND id_ligacao = '{$uniqueId}'";
					$result = pg_query($con, $queryUpdate);

					if (strlen(pg_last_error()) > 0 OR pg_affected_rows($result) > 1) {
						pg_query($con, "ROLLBACK");
						$msg_erro .= pg_last_error() . " - " . pg_affected_rows($result) . "<br />";
					} else {
						pg_query($con, "COMMIT");
					}
				}
			}
		}
	}
}

if (!empty($msg_erro)) {
	$body = "<b>Erro em rotina de duração de chamadas da telefonia.</b><br />" .
	"<b>Horário:</b> " . date("d/m/Y H:i:s") . "<br />" .
	"<b>Erros:</b> " . iconv('ISO-8859-1', 'UTF-8', $msg_erro);
	for ($i = 0; $i <= count($destinatarios); $i++) {
		$mail->sendMail(
			$destinatarios[$i],
			$subject,
			$body,
			"noreply@telecontrol.com.br"
		);
	}
}

?>