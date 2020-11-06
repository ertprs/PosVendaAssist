<?php
$posto_interno_nao_valida = true;

/**
 * Função para validar anexo
 */
function valida_anexo() {
	global $campos, $msg_erro, $fabricaFileUploadOS, $os, $con, $login_fabrica, $valida_anexo_boxuploader;

	if (empty($valida_anexo_boxuploader)) {
		if ($fabricaFileUploadOS) {
			$anexo_chave = $campos["anexo_chave"];
			$tdocs = new TDocs($con, $login_fabrica, "revenda");

			if ($anexo_chave != $os) {
				$anexos = $tdocs->getByHashTemp($anexo_chave);
				if(empty($anexos)) {
					$msg_erro["msg"][] = traduz("1 Os anexos são obrigatórios");
				}
			} else {
				$anexos = $tdocs->getdocumentsByRef($anexo_chave);
				if (empty($anexos->url)) {
					$msg_erro["msg"][] = traduz("2 Os anexos são obrigatórios");
				}
			}

		} else {
			$count_anexo = array();

			foreach ($campos["anexo"] as $key => $value) {
				if (strlen($value) > 0) {
					$count_anexo[] = "ok";
				}
			}

			if(!count($count_anexo)){
				$msg_erro["msg"][] = traduz("3 Os anexos são obrigatórios");
			}
		}
	}
}

$valida_anexo = "valida_anexo";

/**
 * Função para validar a garantia do produto
 */
function valida_garantia($boolean = false) {
	global $con, $login_fabrica, $campos, $msg_erro;

	$data_compra   = $campos["os"]["data_compra"];
	$data_abertura = $campos["os"]["data_abertura"];
	$produto       = $campos["produto"]["id"];

	if (!empty($produto) && !empty($data_compra) && !empty($data_abertura)) {
		$sql = "SELECT garantia FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$garantia = pg_fetch_result($res, 0, "garantia");

			if (strtotime(formata_data($data_compra)." +{$garantia} months") < strtotime(formata_data($data_abertura))) {
				if ($boolean == false) {
					$msg_erro["msg"][] = traduz("Produto fora de garantia");
				} else {
					return false;
				}
			} else if ($boolean == true) {
				return true;
			}
		}
	}
}

$valida_garantia = "valida_garantia";
