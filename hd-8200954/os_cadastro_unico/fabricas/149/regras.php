<?php

$data_abertura_fixa = true;

$regras["produto|solucao"] = array(
	"obrigatorio"  => false
);

$regras["consumidor|cpf"] = array(
	"obrigatorio"  => true
);

$regras["consumidor|cep"] = array(
	"obrigatorio"  => true
);

$regras["consumidor|bairro"] = array(
	"obrigatorio"  => true
);

$regras["consumidor|endereco"] = array(
	"obrigatorio"  => true
);

$regras["consumidor|numero"] = array(
	"obrigatorio"  => true
);
$regras["consumidor|complemento"] = array(
	"obrigatorio"  => false
);

$valida_anexo_boxuploader = "valida_anexo_boxuploader";

function grava_solucao_cortag() {
	global $con, $os, $campos, $login_fabrica;

	$solucao = $campos["produto"]["solucao"];

	if (!strlen($solucao)) {
		$solucao = "null";
	}

	$sql = "SELECT defeito_constatado_reclamado FROM tbl_os_defeito_reclamado_constatado WHERE os = {$os}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$sql = "UPDATE tbl_os_defeito_reclamado_constatado SET solucao = {$solucao} WHERE os = {$os}";
	} else {
		$sql = "INSERT INTO tbl_os_defeito_reclamado_constatado (os, solucao,fabrica) VALUES ({$os}, {$solucao}, {$login_fabrica})";
	}

	$res = pg_query($con, $sql);

	if (strlen(pg_last_error()) > 0) {
		throw new Exception("Erro ao gravar solução");
	}
}

$funcoes_fabrica = array(
	"grava_solucao_cortag"
);

function auditoria_os_reincidente_cortag() {
	global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

	$sql = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os} AND os_reincidente IS NOT TRUE";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){
		$status_auditoria = array(67,19);
		$msg_auditoria = "OS reincidente de número de série";

		$serie = $campos["produto"]["serie"];

		if (!empty($serie)) {
			$select = "SELECT tbl_os.os
					FROM tbl_os
					INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
					WHERE tbl_os.fabrica = {$login_fabrica}
					AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '90 days')
					AND tbl_os.excluida IS NOT TRUE
					AND tbl_os.posto = {$campos['posto']['id']}
					AND tbl_os.os < {$os}
					AND tbl_os_produto.serie = '{$serie}'
					AND tbl_os_produto.produto = {$campos['produto']['id']}
					ORDER BY tbl_os.data_abertura DESC
					LIMIT 1";
			$resSelect = pg_query($con, $select);
		}

		if (!pg_num_rows($resSelect) || empty($serie)) {
			$status_auditoria = array(70,19);
			$msg_auditoria = "OS reincidente de cnpj, nota fiscal e produto";

			$select = "SELECT tbl_os.os
				FROM tbl_os
				INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
				WHERE tbl_os.fabrica = {$login_fabrica}
				AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '90 days')
				AND tbl_os.excluida IS NOT TRUE
				AND tbl_os.posto = {$campos['posto']['id']}
				AND tbl_os.os < {$os}
				AND tbl_os.nota_fiscal = '{$campos['os']['nota_fiscal']}'
				AND tbl_os.revenda_cnpj = '".preg_replace("/[\.\-\/]/", "", $campos["revenda"]["cnpj"])."'
				AND tbl_os_produto.produto = {$campos['produto']['id']}
				ORDER BY tbl_os.data_abertura DESC
				LIMIT 1";
			$resSelect = pg_query($con, $select);
		}

		if (pg_num_rows($resSelect) > 0 && verifica_auditoria($status_auditoria, $status_auditoria, $os) === true) {
			$os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");

			if (verifica_os_reincidente_finalizada($os_reincidente_numero)) {
				$insert = "INSERT INTO tbl_os_status
						(os, status_os, observacao)
						VALUES
						({$os}, {$status_auditoria[0]}, '{$msg_auditoria}')";
				$resInsert = pg_query($con, $insert);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao lançar ordem de serviço");
				} else {
					$os_reincidente = true;
				}
			}
		}
	}
}

function auditoria_km_cortag() {
	global $con, $login_fabrica, $campos, $os;

	$produto = $campos["produto"]["id"];
	$tipo_atendimento = $campos["os"]["tipo_atendimento"];

		
	$sql = "SELECT tbl_linha.linha
			FROM tbl_produto
			INNER JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica}
			WHERE tbl_produto.fabrica_i = {$login_fabrica}
			AND tbl_produto.produto = {$produto}
			AND tbl_linha.deslocamento IS TRUE";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {


		$sql = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento AND km_google IS TRUE";

		$res = pg_query($con, $sql);

		if(pg_num_rows($res)>0){
			if (verifica_auditoria(array(98, 99, 100), array(98), $os) === true) {
				if ($campos["os"]["qtde_km"] != $campos["os"]["qtde_km_hidden"]) {
					$sql = "INSERT INTO tbl_os_status
							(os, status_os, observacao)
							VALUES
							({$os}, 98, 'KM alterado manualmente')";
				} else {
					$sql = "INSERT INTO tbl_os_status
							(os, status_os, observacao)
							VALUES
							({$os}, 98, 'OS aguardando aprovação de KM')";	
				}

				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao lançar ordem de serviço");
				}
			}
		}
	}else{
		$sql = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento = {$tipo_atendimento} AND km_google IS TRUE";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			throw new Exception("Tipo de atendimento com deslocamento apenas para linha CORTADORES ELÉTRICOS");
		}
	}
}

function auditoria_numero_serie_cortag() {
	global $con, $login_fabrica, $campos, $os;

	$produto = $campos["produto"]["id"];
	$serie   = $campos["produto"]["serie"];

	$sql = "SELECT produto, numero_serie_obrigatorio
			FROM tbl_produto
			WHERE fabrica_i = {$login_fabrica}
			AND produto = {$produto}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$numero_serie_obrigatorio = pg_fetch_result($res, 0, "numero_serie_obrigatorio");

		if ($numero_serie_obrigatorio == "t" && !empty($serie)) {
			$sql = "SELECT numero_serie 
					FROM tbl_numero_serie
					WHERE fabrica = {$login_fabrica}
					AND produto = {$produto}
					AND serie = '{$serie}'";
			$res = pg_query($con, $sql);

			if (!pg_num_rows($res) && verifica_auditoria(array(102,103), array(102,103), $os) === true) {
				$sql = "INSERT INTO tbl_os_status
						(os, status_os, observacao)
						VALUES
						({$os}, 102, 'OS aguardando aprovação de número de série')";
				$res = pg_query($con, $sql);
	
				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao lançar ordem de serviço");
				}
			}
		} else if (empty($serie))  {
			 if (verifica_auditoria(array(102,103), array(102,103), $os) === true) {
                                $sql = "INSERT INTO tbl_os_status
                                                (os, status_os, observacao)
                                                VALUES
                                                ({$os}, 102, 'OS sem número de série')";
				$res = pg_query($con, $sql);

                                if (strlen(pg_last_error()) > 0) {
                                        throw new Exception("Erro ao lançar ordem de serviço");
                                }
                        }
		}
	} 
}

$auditorias = array(
	"auditoria_os_reincidente_cortag",
	"auditoria_peca_critica",
	"auditoria_troca_obrigatoria",
	"auditoria_pecas_excedentes",
	"auditoria_km_cortag",
	"auditoria_numero_serie_cortag"
);

?>
