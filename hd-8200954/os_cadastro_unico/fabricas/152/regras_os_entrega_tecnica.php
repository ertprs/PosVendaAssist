<?php

$regras["produto|serie"] = array(
	"obrigatorio"  => false
);

$regas["revenda|nome"] = array(
	"obrigatorio" => false
);

$regas["revenda|cnpj"] = array(
	"obrigatorio" => false,
	"function"    => array("valida_revenda_cnpj_esab")
);

$regas["revenda|cidade"] = array(
	"obrigatorio" => false
);

$regas["revenda|estado"] = array(
	"obrigatorio" => false
);


$auditorias = array(
    "grava_auditoria_os_entrega_tecnica",
    "grava_auditoria_os_qtde_horas",
    "auditoria_km"
);


function grava_os_fabrica() {
	global $campos;

	$hora_tecnica      = $campos["os"]["qtde_horas"];
	$qtde_deslocamento = $campos["os"]["tempo_deslocamento"];

	if (!strlen($hora_tecnica)) {
		$hora_tecnica = 0;
	}
	if (!strlen($qtde_deslocamento)) {
		$qtde_deslocamento = 0;
	}

	return array(
		"hora_tecnica" => $hora_tecnica,
		"qtde_hora" => $qtde_deslocamento
	);
}

function valida_revenda_cnpj_esab() {
	global $con, $campos;

	if(!empty($campos["revenda"]["cnpj"])){

		$cnpj = preg_replace("/\D/", "", $campos["revenda"]["cnpj"]);

		if(strlen($cnpj) < 14){
			throw new Exception("CNPJ da Revenda é inválido");
		}

		if (strlen($cnpj) > 0) {
			$sql = "SELECT fn_valida_cnpj_cpf('{$cnpj}')";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("CNPJ da Revenda é inválido");
			}
		}
	}
}

function grava_auditoria_os_qtde_horas(){
	global $campos, $con, $login_fabrica, $os;

	$hora_tecnica = $campos["os"]["qtde_horas"];
	$produto_hora_tecnica = false;

	if(count($campos["produto"]) > 0){
		$count = count($campos["produto"]);

		for($i=0; $i<$count; $i++){
			$array_produto = $campos["produto"][$i];

			if (empty($array_produto["id"])) {
				continue;
			}

			$sql = "SELECT code_convention FROM tbl_produto 
				WHERE tbl_produto.produto = {$array_produto['id']} AND code_convention = 'hora'";
			$res = pg_query($con,$sql);

			if(pg_num_rows($res) > 0){
				$produto_hora_tecnica = true;
				break;
			}
		}	
	}

	if(strlen($hora_tecnica)>0 && $produto_hora_tecnica == true){

		$sql = "SELECT auditoria_status FROM tbl_auditoria_status WHERE fabricante = 't'";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) > 0){
			$auditoria_status = pg_fetch_result($res, 0, "auditoria_status");

			$sql = "SELECT os FROM tbl_auditoria_os WHERE os = {$os} AND auditoria_status = {$auditoria_status} AND observacao ILIKE '%hora técnica%'";
			$res = pg_query($con, $sql);

			if (!pg_num_rows($res)) {
				$sql = "INSERT INTO tbl_auditoria_os
						(os, auditoria_status, observacao)
						VALUES
						({$os}, $auditoria_status, 'OS em auditoria por hora técnica')";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao lançar ordem de serviço");
				}
			}
		}
	}
}

function grava_auditoria_os_entrega_tecnica(){
	global $campos, $con, $login_fabrica, $os;

	$sql = "SELECT auditoria_status FROM tbl_auditoria_status WHERE fabricante = 't'";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){
		$auditoria_status = pg_fetch_result($res, 0, "auditoria_status");

		$sql = "SELECT os FROM tbl_auditoria_os WHERE os = {$os} AND auditoria_status = {$auditoria_status} AND observacao ILIKE '%entrega técnica%'";
		$res = pg_query($con, $sql);

		if (!pg_num_rows($res)) {
			$sql = "INSERT INTO tbl_auditoria_os
					(os, auditoria_status, observacao)
					VALUES
					({$os}, $auditoria_status, 'OS de entrega técnica')";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao lançar ordem de serviço");
			}
		}
	}
}

function auditoria_km() {
	global $con, $login_fabrica, $os, $campos;

	if ($campos["os"]["qtde_km"] > 0) {
		if (verifica_auditoria_unica("tbl_auditoria_status.km = 't'", $os) === true) {
			$busca = buscaAuditoria("tbl_auditoria_status.km = 't'");

			if($busca['resultado']){
				$auditoria_status = $busca['auditoria'];
			}

			if ($campos["os"]["qtde_km"] != $campos["os"]["qtde_km_hidden"]) {
				$sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
                    ({$os}, $auditoria_status, 'OS em auditoria de KM (alterado manualmente)')";
			} else {
				$sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
                    ({$os}, $auditoria_status, 'OS em auditoria de KM')";
			}

			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao lançar ordem de serviço");
			}
		}
	}
}

?>
