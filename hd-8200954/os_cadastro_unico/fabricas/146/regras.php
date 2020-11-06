<?php

$regras["produto|serie"]["function"] = array("valida_numero_serie_ferragens_negrao");
$regras["consumidor|cpf"]["obrigatorio"] = true;
$regras["consumidor|cep"]["obrigatorio"] = true;
$regras["consumidor|bairro"]["obrigatorio"] = true;
$regras["consumidor|endereco"]["obrigatorio"] = true;
$regras["consumidor|numero"]["obrigatorio"] = true;
$regras["revenda|cep"]["obrigatorio"] = true;
$regras["revenda|bairro"]["obrigatorio"] = true;
$regras["revenda|endereco"]["obrigatorio"] = true;
$regras["revenda|numero"]["obrigatorio"] = true;
$regras["revenda|telefone"]["obrigatorio"] = true;
$valida_anexo_boxuploader = "valida_anexo_boxuploader";


function valida_numero_serie_ferragens_negrao() {
	global $con, $campos, $login_fabrica,$msg_erro;

	$produto_id = $campos["produto"]["id"];
	$produto_serie = $campos["produto"]["serie"];

	if (strlen($produto_id) > 0) {
		$sql = "select produto from tbl_produto where fabrica_i = $login_fabrica and produto = $produto_id and numero_serie_obrigatorio is true";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0 && empty($produto_serie)){
			$msg_erro["msg"]["campo_obrigatorio"] = "Preencha todos os campos obrigatórios";
			$msg_erro["campos"][] = "produto[serie]";
		} else if (pg_num_rows($res) > 0) {
			$sql = "SELECT produto FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto_id} AND validar_serie IS TRUE";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$sql = "SELECT numero_serie FROM tbl_numero_serie WHERE fabrica = {$login_fabrica} AND produto = {$produto_id} AND serie = '{$produto_serie}'";
				$res = pg_query($con, $sql);

				if (!pg_num_rows($res)) {
					throw new Exception("Número de Série inválido");
				}
			}
		}
	}
}

function auditoria_km_ferragens_negrao(){

	global $con, $login_fabrica, $os, $campos;

	$tipo_atendimento = $campos["os"]["tipo_atendimento"];

	if (!empty($tipo_atendimento)) {
		$sql = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento = {$tipo_atendimento} AND km_google IS TRUE";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) > 0){
			if ((($campos["os"]["qtde_km"] > 0) OR ($campos["os"]["qtde_km"] != $campos["os"]["qtde_km_hidden"])) && verifica_auditoria(array(98, 99, 100), array(98), $os) === true) {
				$sql = "INSERT INTO tbl_os_status
						(os, status_os, observacao)
						VALUES
						({$os}, 98, 'OS aguardando aprovação de KM')";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao lançar ordem de serviço");
				}
			}
		}
	}
}

function auditoria_os_reincidente_ferragens_negrao(){
	
	global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

	$sql = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os} AND os_reincidente IS NOT TRUE";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){

		$select = "SELECT tbl_os.os
				FROM tbl_os
				INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
				WHERE tbl_os.fabrica = {$login_fabrica}
				AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '90 days')
				AND tbl_os.excluida IS NOT TRUE
				AND tbl_os.os < {$os}
				AND tbl_os.nota_fiscal = '{$campos['os']['nota_fiscal']}'
				AND tbl_os.revenda_cnpj = '".preg_replace("/[\.\-\/]/", "", $campos["revenda"]["cnpj"])."'
				AND tbl_os_produto.produto = {$campos['produto']['id']}
				ORDER BY tbl_os.data_abertura DESC
				LIMIT 1";
		$resSelect = pg_query($con, $select);

		if (pg_num_rows($resSelect) > 0 && verifica_auditoria(array(70, 19), array(19, 70), $os) === true) {
			$os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");

			if (verifica_os_reincidente_finalizada($os_reincidente_numero)) {
				$insert = "INSERT INTO tbl_os_status
						(os, status_os, observacao)
						VALUES
						({$os}, 70, 'OS reincidentee de cnpj, nota fiscal e produto')";
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

$auditorias = array(
	"auditoria_os_reincidente_ferragens_negrao",
	"auditoria_peca_critica",
	"auditoria_troca_obrigatoria",
	"auditoria_pecas_excedentes",
	"auditoria_km_ferragens_negrao"
);

?>
