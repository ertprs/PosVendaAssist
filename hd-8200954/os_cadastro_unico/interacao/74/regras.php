<?php

if ($areaAdmin === true) {
	unset($inputs_interacao);
	
	$inputs_interacao = array(
		"interacao_data_contato",
		"interacao_atendido"
	);

	$funcoes_fabrica = array(
		"interacao_os_atendida"
	);
} else {
	unset($posto_legendas);
}

function interacao_os_atendida() {
	global $con, $os, $login_fabrica, $atendido;

	if ($atendido == "true") {
		$sql = "UPDATE tbl_os_interacao SET atendido = true WHERE fabrica = {$login_fabrica} AND os = {$os}";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro ao gravar interação");
		}
	}
}

$insertInteracao = "insertInteracaoAtlas";

function insertInteracaoAtlas($os, $mensagem, $interna, $email) {
	global $con, $login_fabrica, $login_admin, $areaAdmin, $login_posto, $data_contato, $atendido;

	if (empty($os)) {
		throw new Exception("OS não informada");
	}

	if (empty($data_contato)) {
		$data_contato = "null";
	} else {
		$data_contato = "'$data_contato'";
	}
	$programa_insert = $_SERVER['PHP_SELF'];
	if ($areaAdmin === true) {
		$sql = "INSERT INTO tbl_os_interacao
				(programa,fabrica, os, admin, comentario, interno, exigir_resposta, data_contato, atendido)
				VALUES
				('{$programa_insert}',{$login_fabrica}, {$os}, {$login_admin}, '{$mensagem}', '{$interna}', '{$email}', {$data_contato}, '{$atendido}')";
	} else {
		$sql = "INSERT INTO tbl_os_interacao
				(programa,fabrica, os, posto, comentario, interno, exigir_resposta)
				VALUES
				('{$programa_insert}',{$login_fabrica}, {$os}, {$login_posto}, '{$mensagem}', false, false)";
	}

	$res = pg_query($con, $sql);

	if (strlen(pg_last_error()) > 0) {
		throw new Exception("Erro ao gravar interação");
	}
}

