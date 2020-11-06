<?php

$grava_os_campo_extra = 'grava_os_campo_extra_fabrica';

$regras["consumidor|numero"] = array(
	"obrigatorio" => true
);

$regras["consumidor|endereco"] = array(
	"obrigatorio" => true
);

$regras["consumidor|cep"] = array(
	"obrigatorio" => false
);

$regras["revenda|nome"] = array(
	"obrigatorio" => false
);

$regras["revenda|cnpj"] = array(
	"obrigatorio" => false
);

$regras["revenda|cidade"] = array(
	"obrigatorio" => false
);

$regras["revenda|estado"] = array(
	"obrigatorio" => false
);

$regras["os|observacoes"] = array(
	"obrigatorio" => true
);

$regras["produto|defeito_constatado"] = array(
		"function" => array("valida_familia_defeito_constatado_esab", "valida_defeito_constatado_peca_lancada_esab")
);


/*
*	audiotoria de defeito constando e hora tecnica esta sendo feita na funcao :
*	grava_multiplos_defeitos_esab
*/

$auditorias = array(
	"auditoria_pecas_excedentes",
	"auditoria_os_reincidente_esab",
	"auditoria_produto_troca_obrigatoria",
	"auditoria_km",
	"auditoria_valores_adicionais"
);

function grava_os_campo_extra_fabrica() {
    global $campos;
    //print_r($campos); exit;
    $valor_adicional_consumidor_estado = $campos["consumidor"]["estado"];
    
    $return = array();    
    if(strlen($valor_adicional_consumidor_estado) > 0){
        $return["consumidor"]["estado"] = utf8_encode($valor_adicional_consumidor_estado);
    }    
    return $return;
}

function auditoria_os_reincidente_esab() {
	global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

	$posto = $campos['posto']['id'];
	$sql = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os} AND os_reincidente IS NOT TRUE";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0 && strlen($campos['produto']['serie']) > 0 && strlen($campos['produto']['id']) > 0){

		$sql = "SELECT tbl_os.os
				FROM tbl_os
				INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
				WHERE tbl_os.fabrica = {$login_fabrica}
				AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '90 days')
				AND tbl_os.excluida IS NOT TRUE
				AND tbl_os.posto = $posto
				AND tbl_os.os < {$os}
				AND tbl_os.serie =  '{$campos['produto']['serie']}'
				AND tbl_os_produto.produto = {$campos['produto']['id']}
				ORDER BY tbl_os.data_abertura DESC
				LIMIT 1";
		$resSelect = pg_query($con, $sql);

		if (pg_num_rows($resSelect) > 0 && verifica_auditoria_unica("tbl_auditoria_status.reincidente = 't'", $os) === true) {
			$os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");

			if (verifica_os_reincidente_finalizada_esab($os_reincidente_numero)) {
				$busca = buscaAuditoria("tbl_auditoria_status.reincidente = 't'");

				if($busca['resultado']){
					$auditoria_status = $busca['auditoria'];
				}

	            $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
	                    ({$os}, $auditoria_status, 'OS Reincidente por CNPJ, NOTA FISCAL, PRODUTO')";

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao lançar ordem de serviço");
				} else {
					$os_reincidente = true;
				}
			}
		}
	}
}

function verifica_os_reincidente_finalizada_esab($os) {

	global $con, $login_fabrica, $campos;

	$posto       = $campos['posto']['id'];
	$nota_fiscal = $campos["os"]["nota_fiscal"];

	$sql = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os} AND finalizada IS NOT NULL AND data_fechamento IS NOT NULL";
	$res = pg_query($con, $sql);

	/*
	verifica os duplicada
	*/
	if (pg_num_rows($res) > 0) {
		return true;
	} else {

		$sql = "SELECT 
					sua_os 
				FROM tbl_os 
				WHERE 
					fabrica = {$login_fabrica} 
					AND os = {$os} 
					AND posto = {$posto} 
					AND nota_fiscal = '{$nota_fiscal}' 
					AND tbl_os.cancelada IS NOT TRUE";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res)>0){
			$sua_os = pg_fetch_result($res, 0, "sua_os");
			throw new Exception("Já existe uma Ordem de Serviço aberta com os dados informados, os: {$sua_os}");
		} else {
			return true;
		}
	}
}


function auditoria_valores_adicionais() {
	global $con, $os, $campos;
	if(count($campos["os"]["valor_adicional"]) > 0){
		foreach ($campos["os"]["valor_adicional"] as $key => $value) {
			list($chave,$valor) = explode("|", $value);
			$valores[$key] = array(utf8_encode(utf8_decode($chave)) => $valor);
		}

		$valores = json_encode($valores);

		$valores = str_replace("\\", "\\\\", $valores);

		grava_valor_adicional($valores,$os);

		if (verifica_auditoria_unica("tbl_auditoria_status.fabricante = 't' AND tbl_auditoria_os.observacao ILIKE '%valores adicionais%'", $os) === true) {
			$busca = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

			if($busca['resultado']){
				$auditoria_status = $busca['auditoria'];
			}

			$sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido) VALUES
                    ({$os}, $auditoria_status, 'OS em auditoria de Valores Adicionais', false)";
			$res = pg_query($con, $sql);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao lançar ordem de serviço");
			}
		}
	}
}

function auditoria_km() {
	global $con, $login_fabrica, $os, $campos;

	$tipo_atendimento = $campos["os"]["tipo_atendimento"];

	$sql = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND km_google IS TRUE AND tipo_atendimento = {$tipo_atendimento}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		if ($campos["os"]["qtde_km"] > 0) {
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

function grava_os_fabrica() {
	global $campos;

	$campos["produto"]["defeito_constatado"] = '';
	$tempo_deslocamento = $campos["os"]["tempo_deslocamento"];

	if (!strlen($tempo_deslocamento)) {
		$tempo_deslocamento = 0;
	}

	return array(
		"qtde_hora" => "{$tempo_deslocamento}"
	);
}


function valida_defeito_constatado_peca_lancada_esab() {
	global $campos;

	$defeitos = explode(",", $campos["produto"]["defeitos_constatados_multiplos"]);

	if (verifica_peca_lancada() == true && empty($defeitos)) {
		throw new Exception("Para o lançar peças é necessário informar o defeito constatado");
	}
}

/**
 * Função chamada na valida_campos()
 *
 * Função para validar a amarração do defeito constatado com a famí­lia do produto
 */

function valida_familia_defeito_constatado_esab() {
	global $con, $login_fabrica, $campos;

	$produto  = $campos["produto"]["id"];
	$defeitos = explode(",", $campos["produto"]["defeitos_constatados_multiplos"]);

	if (!empty($produto) && !empty($defeitos)) {
		for($i = 0; $i < count($defeitos); $i++){
			$def = $defeitos[$i];

			if (empty($defeito)) {
				continue;
			}

			$sql_def = "SELECT *
				FROM tbl_diagnostico
				INNER JOIN tbl_familia ON tbl_familia.fabrica = {$login_fabrica} AND tbl_familia.familia = tbl_diagnostico.familia
				INNER JOIN tbl_produto ON tbl_produto.fabrica_i = {$login_fabrica} AND tbl_produto.familia = tbl_familia.familia
				WHERE tbl_diagnostico.fabrica = {$login_fabrica}
				AND tbl_diagnostico.defeito_constatado = {$def}
				AND tbl_produto.produto = {$produto}";
			$res_def = pg_query($con, $sql_def);

			if (!pg_num_rows($res_def)) {
				throw new Exception("Defeito constatado não pertence a famí­lia do produto");
			}
		}
	}
}

if ($areaAdmin != true) {
	function valida_anexo_esab() {
		global $campos, $msg_erro, $fabricaFileUploadOS, $os, $con, $login_fabrica;

		$sql = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os} AND data_abertura < '2019-03-12'";	
		$res = pg_query($con,$sql);

		 if ($fabricaFileUploadOS && pg_num_rows($res) == 0) {
		     $anexo_chave = $campos["anexo_chave"];
	 
		     if (!empty($anexo_chave)){
			 if (!empty($os)){
			     $cond_tdocs = "AND tbl_tdocs.referencia_id = $os";
			 }else{
			     $cond_tdocs = "AND tbl_tdocs.hash_temp = '$anexo_chave'";
			 }
			 $sql_tdocs = "SELECT json_field('typeId',obs) AS typeId 
					     FROM tbl_tdocs 
					     WHERE tbl_tdocs.fabrica = $login_fabrica
					     AND tbl_tdocs.situacao = 'ativo'
					     $cond_tdocs";
			 $res_tdocs = pg_query($con,$sql_tdocs);
	 
			 if (pg_num_rows($res_tdocs) > 0){
	 
			     $typeId = pg_fetch_all_columns($res_tdocs);
				 if (!in_array('notafiscal', $typeId)) {
				     throw new Exception(traduz("Obrigatório anexar a nota fiscal do produto"));
				 }

				if (!in_array('produto', $typeId)) {
					throw new Exception(traduz("Obrigatório anexar a foto do produto"));
				}

				if (!in_array('formulario', $typeId)) {
					throw new Exception(traduz("Obrigatório anexar o formulário"));
				}
	 
			 }else{
				throw new Exception(traduz("Obrigatório os seguintes anexos: fomulário, foto do produto e nota fiscal"));
			}
			}else{
			 	throw new Exception(traduz("Obrigatório os seguintes anexos: fomulário, foto do produto e nota fiscal"));
		     }
		 }
	}

	$valida_anexo = "valida_anexo_esab";
}


// Verificar e modificar as descrições

function grava_multiplos_defeitos_esab() {
	global $con, $os, $campos, $login_fabrica;

	if(!empty($campos["produto"]["defeitos_constatados_multiplos"])){
		$tempo_reparo_defeito   = $campos["produto"]["tempo_reparo_defeito"];
		$defeitos               = explode(",", $campos["produto"]["defeitos_constatados_multiplos"]);
		$auditoria_defeito      = false;
		$auditoria_tempo_reparo = false;

		for($i = 0; $i < count($defeitos); $i++){
			$def          = $defeitos[$i];
			$tempo_reparo = $tempo_reparo_defeito[$def];
			var_dump($tempo_reparo);
			if (!strlen($tempo_reparo)) {
				$tempo_reparo = 0;
			}

			$sql_def = "SELECT defeito_constatado_reclamado, tempo_reparo FROM tbl_os_defeito_reclamado_constatado WHERE os = {$os} AND defeito_constatado = {$def}";
			$res_def = pg_query($con, $sql_def);

			if (!pg_num_rows($res_def)) {
				if ($tempo_reparo > 0) {
					$auditoria_tempo_reparo = true;
				}

				$sql_def = "INSERT INTO tbl_os_defeito_reclamado_constatado
							(os, defeito_constatado, tempo_reparo, fabrica)
							VALUES
							({$os}, {$def}, {$tempo_reparo}, {$login_fabrica})";
				$auditoria_defeito = true;
			} else {
				$id               = pg_fetch_result($res_def, 0, "defeito_constatado_reclamado");
				$tempo_reparo_ant = pg_fetch_result($res_def, 0, "tempo_reparo");

				if ($tempo_reparo > 0 && $tempo_reparo != $tempo_reparo_ant) {
					$auditoria_tempo_reparo = true;
				}

				$sql_def = "UPDATE tbl_os_defeito_reclamado_constatado SET
								tempo_reparo = {$tempo_reparo}
							WHERE defeito_constatado_reclamado = {$id}";

			}

			$res_def = pg_query($con, $sql_def);

			if (strlen(pg_last_error()) > 0) {
				throw new Exception("Erro ao abrir ordem de serviço");
			}
		}

		$busca = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

		if($busca['resultado']){
			$auditoria_status = $busca['auditoria'];
		}

		if ($auditoria_defeito === true) {
			if (verifica_auditoria_unica(" tbl_auditoria_status.fabricante = 't' AND tbl_auditoria_os.observacao ILIKE '%defeito constatado%' ", $os) === true || aprovadoAuditoria("tbl_auditoria_status.fabricante = 't' AND tbl_auditoria_os.observacao ILIKE '%defeito constatado%'")) {
	            $sql = "INSERT INTO tbl_auditoria_os
	            		(os, auditoria_status, observacao, bloqueio_pedido)
	            		VALUES
	                	({$os}, $auditoria_status, 'OS em auditoria de Defeito Constatado', true)";
				$res = pg_query($con, $sql);
				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao lançar ordem de serviço");
				}
			}
		}
		if ($auditoria_tempo_reparo === true) {
			if (verifica_auditoria_unica(" tbl_auditoria_status.fabricante = 't' AND tbl_auditoria_os.observacao ILIKE '%tempo de reparo%' ", $os) === true || aprovadoAuditoria("tbl_auditoria_status.fabricante = 't' AND tbl_auditoria_os.observacao ILIKE '%tempo de reparo%'")) {
	            $sql = "INSERT INTO tbl_auditoria_os
	            		(os, auditoria_status, observacao, bloqueio_pedido)
	            		VALUES
	                	({$os}, $auditoria_status, 'OS em auditoria de tempo de reparo', false)";
				$res = pg_query($con, $sql);
				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao lançar ordem de serviço");
				}
			}
		}
	}
}

function verifica_auditoria_defeito_constatado(){
	global $con, $os, $campos;

	$chamar_fnc_auditoria = false;

	foreach($campos['produto_pecas'] as $itens){
		if(strlen(trim($itens['os_item']))==0  and  strlen(trim($itens['referencia']))>0  and strlen(trim($os))>0){
			$chamar_fnc_auditoria = true;
		}
	}

	if($chamar_fnc_auditoria == true){
		grava_auditoria_defeito_constatado();
	}
}

function grava_auditoria_defeito_constatado(){
	global $con, $os, $campos;

	$busca = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

	if($busca['resultado']){
		$auditoria_status = $busca['auditoria'];
	}

	if (aprovadoAuditoria("tbl_auditoria_status.fabricante = 't' AND tbl_auditoria_os.observacao ILIKE '%defeito constatado%'")) {
        $sql = "INSERT INTO tbl_auditoria_os
        		(os, auditoria_status, observacao, bloqueio_pedido)
        		VALUES
            	({$os}, $auditoria_status, 'OS em auditoria de Defeito Constatado', true)";
		$res = pg_query($con, $sql);
		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro ao lançar ordem de serviço");
		}
	}
}


$pre_funcoes_fabrica = array("verifica_auditoria_defeito_constatado");


$funcoes_fabrica = array(
	"grava_multiplos_defeitos_esab"
);
?>
