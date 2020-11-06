<?php
if ($areaAdmin === true) {
	$inputs_interacao[] = "interacao_email_consumidor";
	$inputs_interacao[] = "interacao_sms_consumidor";
} else {
	$interacao_envia_email_regiao = array(
		"default" => "assistec1@amvox.com.br",
		"assistec1@amvox.com.br" => array("AC","AP","AM","RN"),
		"assistec2@amvox.com.br" => array("MG", "RJ", "ES"),
		"assistec3@amvox.com.br" => array("DF", "PI", "SC", "PA", "MT", "AL", "GO", "TO", "RO", "MA", "RS", "RR", "MS"),
		"assistec4@amvox.com.br" => array("BA"),
		"assistec6@amvox.com.br" => array("PE", "CE", "PB", "RN"),
		"assistec8@amvox.com.br" => array("SP", "PR", "SE")
	);
}

$insertInteracao = "insertInteracaoPrecision";

function insertInteracaoPrecision($os, $mensagem, $interna, $email) {
	global $con, $login_fabrica, $login_admin, $areaAdmin, $login_posto, $sms_consumidor;

	if (empty($os)) {
		throw new Exception("OS não informada");
	}

	if ($email == false) {
		$sms_consumidor = "false";
	}
	
	$programa_insert = $_SERVER['PHP_SELF'];
	if ($areaAdmin === true) {
		$sql = "INSERT INTO tbl_os_interacao
				(programa,fabrica, os, admin, comentario, interno, exigir_resposta, sms)
				VALUES
				('{$programa_insert}',{$login_fabrica}, {$os}, {$login_admin}, '{$mensagem}', '{$interna}', '{$email}', '{$sms_consumidor}')";
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
