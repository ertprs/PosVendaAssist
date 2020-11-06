<?php

$regras["produto|defeito_constatado"]["obrigatorio"] = true;

$regras["consumidor|numero"] = array(
	"obrigatorio"  => true
);

$regras["consumidor|cpf"] = array(
	"obrigatorio"  => true
);

$regras["produto|serie"] = array(
    "function"     => array("valida_numero_de_serie", "valida_serie_rheem")
);

$pre_funcoes_fabrica = array(
    "verifica_valores_adicionais_rheem"
);

$auditoria_valores_adicionais = true;

function verifica_valores_adicionais_rheem() {

	global $os, $campos, $auditoria_valores_adicionais;

	if(count($campos["os"]["valor_adicional"]) > 0){
		foreach ($campos["os"]["valor_adicional"] as $key => $value) {
			list($chave,$valor) = explode("|", $value);
			$valores[$key] = array(utf8_encode(utf8_decode($chave)) => $valor);
		}

		$valores = json_encode($valores);

		if(!empty($os)){

			$sql = "SELECT valores_adicionais FROM tbl_os_campo_extra WHERE os = {$os}";
			$res = pg_query($con,$sql);

			$valores_ant = pg_fetch_result($res, 0, "valores_adicionais");

			if ($valores_ant == $valores){
				$auditoria_valores_adicionais = false;

			}else{
				$auditoria_valores_adicionais = true;
			}
		}
	}
}

function auditoria_valores_adicionais_rheem() {
	global $con, $os, $campos, $login_fabrica, $auditoria_valores_adicionais;

	if (count($campos["os"]["valor_adicional"]) > 0) {
		foreach ($campos["os"]["valor_adicional"] as $key => $value) {
			list($chave,$valor) = explode("|", $value);
			$valores[$key] = array(utf8_encode(utf8_decode($chave)) => $valor);
		}

		$valores = json_encode($valores);
		$valores = str_replace("\\", "\\\\", $valores);

		grava_valor_adicional($valores, $os);

		if ($auditoria_valores_adicionais == true) {
			if (verifica_auditoria_unica(" tbl_auditoria_status.fabricante = 't' AND tbl_auditoria_os.observacao ILIKE '%valores adicionais%' AND tbl_auditoria_os.liberada IS NULL ", $os) === true) {
				$busca = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

				if ($busca['resultado']) {
					$auditoria_status = $busca['auditoria'];
				}

				$sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido) VALUES
						({$os}, $auditoria_status, 'OS em auditoria de Valores Adicionais', false)";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao lançar ordem de serviço1");
				}
			}
		}
	}
}


function valida_serie_rheem() {

    global $con, $campos, $login_fabrica;

    $produto = $campos['produto']['id'];
	$serie   = $campos['produto']['serie'];
	$posto   = $campos['posto']['id'];

	if (!empty($produto)) {

		$sql = "SELECT numero_serie_obrigatorio FROM tbl_produto WHERE produto = {$produto} AND fabrica_i = {$login_fabrica}";
		$res = pg_query($con, $sql);

		$numero_serie_obrigatorio = pg_fetch_result($res, 0, "numero_serie_obrigatorio");

		if($numero_serie_obrigatorio == "t"){

			if(strlen($serie) > 0){

				$sql = " SELECT posto , cnpj
						FROM tbl_posto_fabrica
						JOIN tbl_tipo_posto USING(tipo_posto, fabrica)
						JOIN tbl_posto USING(posto)
						WHERE posto = $posto
						AND   tbl_posto_fabrica.fabrica = $login_fabrica
						AND   tipo_revenda ";
				$res = pg_query($con,$sql);
				if(pg_num_rows($res) > 0) {
					$cnpj = pg_fetch_result($res,0,'cnpj');

					$sql = "SELECT produto
							FROM tbl_numero_serie
							WHERE produto = {$produto}
							AND   serie = '$serie'
							AND   fabrica = $login_fabrica
							AND   cnpj = '$cnpj';";
					$res = pg_query($con,$sql);
					if(pg_num_rows($res) == 0 ) {
						 throw new Exception("Número de série inválido") ;
					}
				} else {

					$sql = "SELECT mascara
							  FROM tbl_produto_valida_serie
							 WHERE produto = {$produto}
							   AND fabrica = {$login_fabrica}";
					$res = pg_query($con, $sql);

					$mascara_ok = null;

					while ($mascara = pg_fetch_object($res)) {
						$regExp = str_replace(array('L','N'), array('[A-Z]', '[0-9]'), $mascara->mascara);

						if (preg_match("/^$regExp$/i", $serie)) {
							$mascara_ok = $mascara->mascara;

							break;
						}
					}

					if ($mascara_ok != null) {
						$msg["success"] = true;
					} else {
						 throw new Exception("Número de série inválido") ;
					}
				}

			} else{

				throw new Exception("Para esse produto o número de série é obrigatório");

			}

		}

    } else {
        throw new Exception("Produto não informado");
    }

}


function auditoria_km_rheem(){

	global $con, $login_fabrica, $os, $campos;

	$tipo_atendimento = $campos["os"]["tipo_atendimento"];

	$sql = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND km_google IS TRUE AND tipo_atendimento = {$tipo_atendimento}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		if ($campos["os"]["qtde_km"] >= 80) {
			if (verifica_auditoria_unica("tbl_auditoria_status.km = 't'", $os) === true) {
				$busca = buscaAuditoria("tbl_auditoria_status.km = 't'");

				if($busca['resultado']){
					$auditoria_status = $busca['auditoria'];
				}

				if ($campos["os"]["qtde_km"] != $campos["os"]["qtde_km_hidden"]) {
					$sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido) VALUES
	                    ({$os}, $auditoria_status, 'OS em auditoria de KM (alterado manualmente)', false)";
				} else {
					$sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido) VALUES
	                    ({$os}, $auditoria_status, 'OS em auditoria de KM', false)";
				}

				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao lançar ordem de serviço");
				}
			}
		}
	}
}

function os_reincidente_rheem(){

	// Será por produto, nota e série, cairá para auditar dentro de 90 dias.
	// Cliente, NF e Revenda dentro de 90 dias.
	global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

	$sql = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os} AND os_reincidente IS NOT TRUE";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){

		$cnpj_revenda = preg_replace("/[\.\-\/]/", "", $campos["revenda"]["cnpj"]);
		$cpf_cliente = preg_replace("/[\.\-\/]/", "", $campos["consumidor"]["cpf"]);

		$select = "SELECT tbl_os.os
				FROM tbl_os
				INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
				WHERE tbl_os.fabrica = {$login_fabrica}
				AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '90 days')
				AND tbl_os.excluida IS NOT TRUE
				AND tbl_os.os < {$os}
				AND tbl_os.consumidor_cpf = '{$cpf_cliente}'
				AND tbl_os.revenda_cnpj = '{$cnpj_revenda}'
				AND tbl_os.nota_fiscal = '{$campos['os']['nota_fiscal']}'
				ORDER BY tbl_os.data_abertura DESC
				LIMIT 1";
		$resSelect = pg_query($con, $select);

		if (pg_num_rows($resSelect) > 0 && verifica_auditoria_unica("tbl_auditoria_status.reincidente = 't'", $os) === true) {
			$os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");

			if (verifica_os_reincidente_finalizada($os_reincidente_numero)) {
				$busca = buscaAuditoria("tbl_auditoria_status.reincidente = 't'");

				if($busca['resultado']){
					$auditoria_status = $busca['auditoria'];
				}

			            $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
			                    ({$os}, $auditoria_status, 'OS Reincidente por Cliente, NF e Revenda dentro de 90 dias'); ";
				$res = pg_query($con,$sql);
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
	"auditoria_peca_critica",
	"auditoria_troca_obrigatoria",
	"auditoria_pecas_excedentes",
	"auditoria_km_rheem",
	"os_reincidente_rheem",
	"auditoria_valores_adicionais_rheem",
);


?>
