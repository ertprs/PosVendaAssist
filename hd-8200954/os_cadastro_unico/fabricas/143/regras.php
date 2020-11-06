<?php

$regras["os|defeito_constatado"] = array(
	"obrigatorio"  => false
);

$regras["consumidor|cpf"] = array(
	"obrigatorio"  => false
);

$regras["consumidor|cep"] = array(
	"obrigatorio"  => false
);

$regras["consumidor|nome"] = array(
	"obrigatorio"  => false
);

$regras["consumidor|cidade"] = array(
	"obrigatorio"  => false
);

$regras["consumidor|estado"] = array(
	"obrigatorio"  => false
);

$regras["consumidor|telefone"] = array(
	"obrigatorio"  => false
);

$regras["consumidor|email"] = array(
	"obrigatorio"  => false
);

$regras["revenda|nome"] = array(
	"obrigatorio"  => false
);

$regras["revenda|cnpj"] = array(
	"obrigatorio"  => false
);

$regras["revenda|cidade"] = array(
	"obrigatorio"  => false
);

$regras["revenda|estado"] = array(
	"obrigatorio"  => false
);

function grava_os_fabrica() {
	global $campos;

	return array(
		"rg_produto" => "'{$campos["os"]["rg_produto"]}'"
	);
}



function auditoria_km_wacker_neuson(){

	global $con, $login_fabrica, $os, $campos;

	$sql_linha = "SELECT nome FROM tbl_produto JOIN tbl_linha USING(linha) WHERE referencia = '".$campos["produto"]["referencia"]."' AND fabrica_i = $login_fabrica";
	$res_linha = pg_query($con, $sql_linha);

	if(pg_num_rows($res_linha) > 0){
		$desc_linha = pg_fetch_result($res_linha, 0, 'nome');
	}

	if(!empty($desc_linha) && strtoupper($desc_linha) != strtoupper("Leve")){

		if ((($campos["os"]["qtde_km"] > 300) OR ($campos["os"]["qtde_km"] != $campos["os"]["qtde_km_hidden"])) && verifica_auditoria(array(98, 99, 100), array(98), $os) === true) {
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

function os_reincidente_wacker_neuson(){

	// Será por produto, nota e série, cairá para auditar dentro de 90 dias.
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
				AND tbl_os_produto.produto = {$campos['produto']['id']}
				ORDER BY tbl_os.data_abertura DESC
				LIMIT 1";
		$resSelect = pg_query($con, $select);

		if (pg_num_rows($resSelect) > 0 && verifica_auditoria(array(67, 19), array(67), $os) === true) {
			$os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");

			if (verifica_os_reincidente_finalizada($os_reincidente_numero)) {
				$insert = "INSERT INTO tbl_os_status
						(os, status_os, observacao)
						VALUES
						({$os}, 67, 'OS reincidente de Produto, Série e Nota Fiscal');
						
						UPDATE tbl_os SET os_reincidente = true WHERE os = $os;

		 				UPDATE tbl_os_extra SET os_reincidente = $os_reincidente_numero where os = $os;
						";
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

function pecas_excedentes_wacker_neuson(){

	/*
	Linha Leve : 5
	Linha Pesada: 8
	*/

	global $con, $login_fabrica, $os, $campos;

	$sql_linha = "SELECT nome FROM tbl_produto JOIN tbl_linha USING(linha) WHERE referencia = '".$campos["produto"]["referencia"]."' AND fabrica_i = $login_fabrica";
	$res_linha = pg_query($con, $sql_linha);

	if(pg_num_rows($res_linha) > 0){
		$desc_linha = pg_fetch_result($res_linha, 0, 'nome');

		if(strtoupper($desc_linha) == strtoupper("Leve")){
			$qtde_pecas_intervencao = 5;
		}else if(strtoupper($desc_linha) == strtoupper("Pesado")){
			$qtde_pecas_intervencao = 8;
		}

		$sql = "SELECT COUNT(tbl_os_item.os_item) AS qtde_pecas
				FROM tbl_os_item
				INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
				WHERE tbl_os_produto.os = {$os}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0 && pg_fetch_result($res, 0, "qtde_pecas") > $qtde_pecas_intervencao && verifica_auditoria(array(118, 187), array(118), $os) === true) {
			$sql = "INSERT INTO tbl_os_status
					(os, status_os, observacao)
					VALUES
					({$os}, 118, 'OS em auditoria de peças excedentes')";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao lançar ordem de serviço");
			}
		}

	}

}

$valida_anexo = "";

$auditorias = array(
	"auditoria_peca_critica",
	"auditoria_troca_obrigatoria",
	"auditoria_km_wacker_neuson",
	"os_reincidente_wacker_neuson",
	"pecas_excedentes_wacker_neuson"
);

function grava_multiplos_defeitos_wackerneuson() {
	global $con, $os, $campos, $login_fabrica;

	if(!empty($campos["produto"]["defeitos_constatados_multiplos"])){

		$defeitos = explode(",", $campos["produto"]["defeitos_constatados_multiplos"]);

		for($i = 0; $i < count($defeitos); $i++){
			$def = $defeitos[$i];

			$sql_def = "SELECT defeito_constatado_reclamado FROM tbl_os_defeito_reclamado_constatado WHERE os = {$os} AND defeito_constatado = {$def}";
			$res_def = pg_query($con, $sql_def);

			if (!pg_num_rows($res_def)) {
				$sql_def = "INSERT INTO tbl_os_defeito_reclamado_constatado (os, defeito_constatado,fabrica) VALUES ({$os}, {$def},{$login_fabrica})";
				$res_def = pg_query($con, $sql_def);
			}
		}

	}
}

$funcoes_fabrica = array(
	"grava_multiplos_defeitos_wackerneuson"
);

$regras_pecas["lista_basica"] = false;

?>
