<?php

$regras["os|defeito_constatado"] = array(
	"obrigatorio"  => false
);

$regras["os|qtde_visita"] = array(
	"function"  => array("valida_visita_peca")
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

$regras["consumidor|email"] = array(
	"obrigatorio"  => false
);

/* Revenda */

$regras["revenda|cep"] = array(
	"obrigatorio"  => true
);

$regras["revenda|endereco"] = array(
	"obrigatorio"  => true
);

$regras["revenda|numero"] = array(
	"obrigatorio"  => true
);

$regras["revenda|bairro"] = array(
	"obrigatorio"  => true
);

$regras["revenda|telefone"] = array(
	"obrigatorio"  => true
);

/* Produto */

$regras["produto|referencia"] = array(
	"obrigatorio"  => true
);

$regras["produto|descricao"] = array(
	"obrigatorio"  => true
);

$regras["produto|serie"] = array(
	"obrigatorio"  => false
);

function grava_os_fabrica() {
	global $campos;

	$qtde_visita = $campos["os"]["qtde_visita"];

	if (!strlen($qtde_visita)) {
		$qtde_visita = 0;
	}

	return array(
		"qtde_diaria" => "{$qtde_visita}"
	);
}

function valida_visita_peca() {
	global $campos;

	$qtde_visita = $campos["os"]["qtde_visita"];

	if ($qtde_visita == 2 && verifica_peca_lancada(false) === false) {
		throw new Exception("Para a segunda visita deve ser lançado peças");
	}
}

function valida_serie_produto_v8(){

	global $con, $login_fabrica, $campos;

	$produto = $campos["produto"]["id"];
	$serie = $campos["produto"]["serie"];

	$sql = "SELECT numero_serie_obrigatorio FROM tbl_produto WHERE produto = {$produto} AND fabrica_i = {$login_fabrica}";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){
		$numero_serie_obrigatorio = pg_fetch_result($res, 0, "numero_serie_obrigatorio");
		if($numero_serie_obrigatorio == "t" && empty($serie)){
			throw new Exception("Para este produto o número de Série é obrigatório");
		}
	}

}

function valida_defeito_constatado_v8(){

		global $con, $login_fabrica, $os, $campos;

		if(verifica_peca_lancada() and empty($campos["produto"]["defeito_constatado"])) {
				throw new Exception("Favor preencher o defeito constatado");
		}
}

function auditoria_km() {
	global $con, $login_fabrica, $os, $campos;

	$produto = $campos["produto"]["id"];

	$sql_entrega_tecnica = "SELECT entrega_tecnica FROM tbl_produto WHERE produto = {$produto}";
	$res_entrega_tecnica = pg_query($con, $sql_entrega_tecnica);

	if(pg_num_rows($res_entrega_tecnica) > 0){
		$entrega_tecnica = pg_fetch_result($res_entrega_tecnica, 0, "entrega_tecnica");

		if($entrega_tecnica == "t" && $campos["os"]["qtde_km_hidden"] > 0){
			if (verifica_auditoria(array(98, 99, 100), array(98, 99, 100), $os) === true) {
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

				$taxa_visita = \Posvenda\Regras::get("taxa_visita", "mao_de_obra", $login_fabrica);

				$sql = "UPDATE tbl_os_extra SET taxa_visita = '{$taxa_visita}' WHERE os = {$os}";
				$res = pg_query($con, $sql);
				
				if(strlen(pg_last_error()) > 0){
					throw new Exception("Erro ao Lançar o valor da Visita Técnica do Produto");
				}
			}
		}
	}
}

$regras["produto|serie"] = array(
	"function" => array("valida_serie_produto_v8")
);

$regras["produto|defeito_constatado"] = array(
	"function"  => array("valida_defeito_constatado_v8")
);


$auditorias[] = "auditoria_km";

/**
* Verifica se o produto tem a opção de Deslocamento KM
*/
function verifica_deslocamento_km_produto_v8(){
	global $con, $login_fabrica, $os, $campos;

	$produto = $campos["produto"]["id"];

	$sql = "SELECT valores_adicionais FROM tbl_produto WHERE produto = {$produto} AND fabrica_i = {$login_fabrica}";
	$res = pg_query($con, $sql);

	$valores_adicionais = pg_fetch_result($res, 0, "valores_adicionais");
	$valores_adicionais = json_decode($valores_adicionais);

	foreach ($valores_adicionais as $key => $value) {
		$deslocamento_km  = $value;
	}

	if($deslocamento_km != "t"){
		$sql = "UPDATE tbl_os SET qtde_km_calculada = 0 WHERE os = {$os} AND fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);
	}
}

/**
 * Verifica se o produto é de visita tecnica e grava um valor adicional
 */
function visita_tecnica_valor_adicional_v8() {
	global $con, $os, $campos;

	if(verifica_visita_tecnica_produto($campos["produto"]["id"]) == true){
		$valores = array("Visita Tecnica" => "55");
		$valores = json_encode($valores);

		grava_valor_adicional($valores, $os);
	}
}

/* Valida Anexos */

$funcoes_fabrica = array(
	"visita_tecnica_valor_adicional_v8",
	"verifica_deslocamento_km_produto_v8"
);

?>
