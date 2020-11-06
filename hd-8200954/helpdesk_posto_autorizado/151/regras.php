<?php

$regras["responsavel_solicitacao"]["obrigatorio"] = false;

$buscar_atendente = "buscarAtendenteMondial";

function gravarAnexosBoxUploader() {

	global $con, $login_fabrica, $hd_chamado_item;
	
	$anexo_chave = $_POST["anexo_chave"];

	if ($anexo_chave != $hd_chamado_item) {

		$getTdocs = "SELECT * FROM tbl_tdocs 
					 WHERE fabrica  = $login_fabrica
					 AND contexto   = 'help desk'
					 AND hash_temp = '{$anexo_chave}'
					 AND situacao   = 'ativo'";

		$resTdocs = pg_query($con, $getTdocs);

		$anexos = pg_fetch_all($resTdocs);

		if (!empty($anexos)) {

			$update = "UPDATE tbl_tdocs 
					   SET
						  referencia_id = $hd_chamado_item,
						  hash_temp  = NULL
					   WHERE fabrica = $login_fabrica
					   AND contexto  = 'help desk'
					   AND situacao  = 'ativo'
					   AND hash_temp = '$anexo_chave'";

			$uptodate = pg_query($con, $update);

			if (pg_num_rows($uptodate) == 0) {
				$msg_erro["msg"][] = traduz("Erro ao gravar anexos");
			}
		}
	}

}

function retorna_anexos_inseridos() {
	global $con, $login_fabrica, $hd_chamado_item, $fabricaFileUploadOS;

	$anexo_chave 	  = $_POST["anexo_chave"];
	$anexos_inseridos = [];

    if (!empty($hd_chamado_item)){
        $cond_tdocs = "AND tbl_tdocs.referencia_id = {$hd_chamado_item}";
    }else{
        $cond_tdocs = "AND tbl_tdocs.hash_temp = '{$anexo_chave}'";
    }

	$sql = "SELECT obs
			FROM   tbl_tdocs
			WHERE  tbl_tdocs.fabrica = {$login_fabrica}
			AND    tbl_tdocs.situacao = 'ativo'
			{$cond_tdocs}";

	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {

		while ($dados = pg_fetch_object($res)) {

			$json_obs = json_decode($dados->obs, true);

			$anexos_inseridos[] = $json_obs[0]['typeId'];

		}

	}

	return $anexos_inseridos;
}

function buscarAtendenteMondial($posto, $tipo_solicitacao, $posto_autorizado_dados) {
	global $con, $login_fabrica;

	if (empty($posto)) {
		throw new Exception("Erro ao buscar atendente, posto autorizado não informado");
	}

	$whereClassificacao = "AND tbl_admin_atendente_estado.hd_classificacao IS NULL";

	$cod_ibge_pa = $posto_autorizado_dados["cod_ibge"];
	$estado_pa   = strtoupper($posto_autorizado_dados["contato_estado"]);

	if(!empty($cod_ibge_pa)) {
		$sql = "SELECT tbl_admin_atendente_estado.admin
			FROM tbl_admin_atendente_estado
			INNER JOIN tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin AND tbl_admin.fabrica = {$login_fabrica}
			WHERE tbl_admin_atendente_estado.fabrica = {$login_fabrica}
			AND tbl_admin_atendente_estado.tipo_solicitacao = {$tipo_solicitacao}
			AND tbl_admin_atendente_estado.cod_ibge = {$cod_ibge_pa}
			AND UPPER(tbl_admin_atendente_estado.estado) = '{$estado_pa}'
			AND tbl_admin.ativo
			AND (tbl_admin.nao_disponivel IS NULL OR LENGTH(tbl_admin.nao_disponivel) = 0)
			{$whereClassificacao}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			return pg_fetch_result($res, 0, "admin");
		}

		$sql = "SELECT tbl_admin_atendente_estado.admin
			FROM tbl_admin_atendente_estado
			INNER JOIN tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin AND tbl_admin.fabrica = {$login_fabrica}
			WHERE tbl_admin_atendente_estado.fabrica = {$login_fabrica}
			AND tbl_admin_atendente_estado.tipo_solicitacao IS NULL
			AND tbl_admin_atendente_estado.cod_ibge = {$cod_ibge_pa}
			AND tbl_admin.ativo
			AND UPPER(tbl_admin_atendente_estado.estado) = '{$estado_pa}'
			AND (tbl_admin.nao_disponivel IS NULL OR LENGTH(tbl_admin.nao_disponivel) = 0)
		{$whereClassificacao}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			return pg_fetch_result($res, 0, "admin");
		}
	}
	$sql = "SELECT tbl_admin_atendente_estado.admin
			FROM tbl_admin_atendente_estado
			INNER JOIN tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin AND tbl_admin.fabrica = {$login_fabrica}
			WHERE tbl_admin_atendente_estado.fabrica = {$login_fabrica}
			AND tbl_admin_atendente_estado.tipo_solicitacao = {$tipo_solicitacao}
			AND tbl_admin_atendente_estado.cod_ibge IS NULL
			AND tbl_admin.ativo
			AND UPPER(tbl_admin_atendente_estado.estado) = '{$estado_pa}'
			AND (tbl_admin.nao_disponivel IS NULL OR LENGTH(tbl_admin.nao_disponivel) = 0)
			{$whereClassificacao}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		return pg_fetch_result($res, 0, "admin");
	}

	$sql = "SELECT tbl_admin_atendente_estado.admin
			FROM tbl_admin_atendente_estado
			INNER JOIN tbl_admin ON tbl_admin.admin = tbl_admin_atendente_estado.admin AND tbl_admin.fabrica = {$login_fabrica}
			WHERE tbl_admin_atendente_estado.fabrica = {$login_fabrica}
			AND tbl_admin_atendente_estado.tipo_solicitacao IS NULL
			AND tbl_admin_atendente_estado.cod_ibge IS NULL
			AND tbl_admin.ativo
			AND UPPER(tbl_admin_atendente_estado.estado) = '{$estado_pa}'
			AND (tbl_admin.nao_disponivel IS NULL OR LENGTH(tbl_admin.nao_disponivel) = 0)
			{$whereClassificacao}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		return pg_fetch_result($res, 0, "admin");
	}

	throw new Exception("Nenhum atendente encontrado para o estado");
}
