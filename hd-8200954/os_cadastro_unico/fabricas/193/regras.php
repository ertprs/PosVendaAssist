<?php

$regras["consumidor|nome"]["obrigatorio"]          = true;
$regras["consumidor|cpf"]["obrigatorio"]           = true;
$regras["consumidor|cep"]["obrigatorio"]           = true;
$regras["consumidor|estado"]["obrigatorio"]        = true;
$regras["consumidor|cidade"]["obrigatorio"]        = true;
$regras["consumidor|bairro"]["obrigatorio"]        = true;
$regras["consumidor|endereco"]["obrigatorio"]      = true;
$regras["consumidor|numero"]["obrigatorio"]        = true;
$regras["consumidor|celular"]["obrigatorio"]       = true;
$regras["consumidor|email"]["obrigatorio"]         = true;
$regras["produto|fabricante_motor"]["obrigatorio"] = true; 
$regras["os|data_abertura"]                        = [
	"obrigatorio" => true,
    "regex"       => "date",
    "function"    => array("valida_data_abertura_fabrica")
];

$grava_os_campo_extra     = "grava_os_campo_extra_fabrica";
$antes_valida_campos      = "antes_valida_campos";
$auditorias               = [];
$posto_interno_nao_valida = true;
$valida_anexo_boxuploader = "valida_anexo_boxuploader";

$funcoes_fabrica = array(
    "auditoria_troca_produto_dancor"
);

function antes_valida_campos () {
	global $con,$login_fabrica,$campos,$auditorias,$regras;

	$tipo_atendimento = addslashes($campos['os']['tipo_atendimento']);
	$troca_produto    = $campos['produto']['troca_produto'];

	if ($troca_produto == 't') {
		$auditorias[] = "auditoria_troca_produto_dancor";
	}
	
	$sqlAud = "SELECT tipo_atendimento, km_google
            FROM tbl_tipo_atendimento
            WHERE fabrica        = {$login_fabrica} 
            AND tipo_atendimento = $tipo_atendimento 
            AND fora_garantia IS NOT TRUE";
    $resAud    = pg_query($con, $sqlAud);
    $num       = pg_num_rows($resAud);
    $km_google = pg_fetch_result($resAud, 0, 'km_google');

    if ($num == 0) {
    	$regras["produto|fabricante_motor"]["obrigatorio"] = false; 
    	$regras["os|nota_fiscal"]["obrigatorio"]           = false;
    	$regras["os|data_compra"]["obrigatorio"]           = false;    
    	$regras["consumidor|complemento"]["obrigatorio"]   = false;  
    	$regras["produto|tempo_conserto"]["obrigatorio"]   = false;
    	$regras["revenda|nome"]["obrigatorio"]             = false;
    	$regras["revenda|cnpj"]["obrigatorio"]             = false;
    	$regras["revenda|estado"]["obrigatorio"]           = false;
    	$regras["revenda|cidade"]["obrigatorio"]           = false;
    	
    }

	$sql = "SELECT produto
			FROM tbl_produto
			WHERE fabrica_i = {$login_fabrica}	
			AND produto     = {$campos['produto']['id']}
			AND garantia_horas IS NOT NULL
			AND garantia_horas > 0";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) > 0) {
		if (empty($campos['produto']['tempo_conserto']) && $num > 0 && ($km_google == 't' || $km_google === true)) {
			$regras["produto|tempo_conserto"]["obrigatorio"] = true;
			$msg_erro["msg"][] = "O Tempo de Conserto não pode ser vazio.";
		} else {
			$auditorias[] = "auditoria_tempo_conserto";
		}
 	}

 	$auditorias[] = (verifica_produto_lancamento($campos['produto']['id']) === true) ? "auditoria_produto_lancamento" : "";

 	// tira auditoria pro tipo de atendimento: fora de garantia
 	if ($num == 0) {
 		$auditorias = [];
 	}
}

function auditoria_tempo_conserto() {
	global $con,$login_fabrica,$campos, $os;

	$sql = "SELECT os FROM tbl_auditoria_os WHERE os = {$os} AND auditoria_status = 6 AND observacao = 'Auditoria tempo de conserto'";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) == 0) {
		$sql_auditoria = "INSERT INTO tbl_auditoria_os (os, observacao, bloqueio_pedido, auditoria_status) VALUES ($os, 'Auditoria tempo de conserto', true, 6)";
		$res_auditoria = pg_query($con, $sql_auditoria);
	}
	
	if (pg_last_error() > 0) {
		$msg_erro["msg"][] = "#Aud-1 " . pg_last_error();
		return false;
	}

	return true;
}

function auditoria_produto_lancamento() {
	global $con,$login_fabrica,$campos, $os;

	$sql = "SELECT os FROM tbl_auditoria_os WHERE os = {$os} AND auditoria_status = 6 AND observacao = 'Produto de lançamento'";
	$res = pg_query($con,$sql);

	if (pg_num_rows($res) == 0) {
		$sql_auditoria = "INSERT INTO tbl_auditoria_os (os, observacao, bloqueio_pedido, auditoria_status) VALUES ($os, 'Produto de lançamento', true, 6)";
		$res_auditoria = pg_query($con, $sql_auditoria);
	}

	if (pg_last_error() > 0) {
		$msg_erro["msg"][] = "#Aud-2 " . pg_last_error();
		return false;
	}

	return true;
}

function verifica_produto_lancamento($produto) {
	global $con,$login_fabrica,$campos, $os;

	$sql 		= "SELECT JSON_FIELD('lancamento', parametros_adicionais)::BOOL AS lancamento
					FROM tbl_produto
					WHERE fabrica_i = {$login_fabrica}
					AND produto     = {$produto}";
	$res 		= pg_query($con,$sql); 
	$lancamento = pg_fetch_result($res, 0, lancamento);

	if (pg_num_rows($res) > 0) {
		if ($lancamento == ""){
			return false;
		}else if ($lancamento == 'f' || $lancamento == false){
			return false;
		}else{
			return true;
		}
	} else {
		return false; 	
	}
}

function grava_os_campo_extra_fabrica() {
	global $con,$login_fabrica,$campos, $os;

	if (!empty($os)) {
		if (!empty($campos['produto']['tempo_conserto'])) {
			$tempo_conserto = addslashes($campos['produto']['tempo_conserto']);
		}

		if (!empty($campos['produto']['fabricante_motor'])) {
			$fabricante_motor = addslashes($campos['produto']['fabricante_motor']);
		}

		if (!empty($campos['produto']['troca_produto'])) {
			$troca_produto = $campos['produto']['troca_produto'];
		}	

		$sql = "SELECT os FROM tbl_os WHERE os = {$os} AND fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) == 0) {
			$msg_erro["msg"][] = "#EXTRA-1 Ordem de Serviço não encontrada.";
			return false;
		}

		$sql = "SELECT os,campos_adicionais FROM tbl_os_campo_extra WHERE os = {$os} AND fabrica = {$login_fabrica}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) == 0) {
			$campos_adicionais                     = '';
			$campos_adicionais['tempo_conserto']   = $tempo_conserto;
			$campos_adicionais['fabricante_motor'] = $fabricante_motor;
			$campos_adicionais['troca_produto']    = $troca_produto;
			$campos_adicionais                     = json_encode($campos_adicionais);

			$insert = "INSERT INTO tbl_os_campo_extra(os, fabrica, campos_adicionais) VALUES ({$os}, {$login_fabrica}, '{$campos_adicionais}')";
			$res_i  = pg_query($con, $insert);

			if (pg_last_error() > 0) {
				$msg_erro["msg"][] = "#EXTRA-2 " . pg_last_error();
				return false;
			}

			return true; 
		} else {
			$campos_adicionais                     = pg_fetch_result($res, 0, campos_adicionais); 
			$campos_adicionais                     = json_decode($campos_adicionais, true);
			$campos_adicionais['tempo_conserto']   = $tempo_conserto;
			$campos_adicionais['fabricante_motor'] = $fabricante_motor;
			$campos_adicionais['troca_produto']    = $troca_produto;
			
			$campos_adicionais                     = json_encode($campos_adicionais);

			$update = "UPDATE tbl_os_campo_extra SET campos_adicionais = '{$campos_adicionais}' WHERE os = {$os} AND fabrica = {$login_fabrica}";
			$res_up = pg_query($con, $update);

			if (pg_last_error() > 0) {
				$msg_erro["msg"][] = "#EXTRA-3 " . pg_last_error();
				return false;
			}

			return true;
		}
	}
}

function valida_data_abertura_fabrica() {
	global $campos, $os;

	$data_abertura = $campos["os"]["data_abertura"];

	if (!empty($data_abertura) && empty($os)) {
		list($dia, $mes, $ano) = explode("/", $data_abertura);

		if (!checkdate($mes, $dia, $ano)) {
			throw new Exception("Data de abertura inválida");
		} else if (strtotime("{$ano}-{$mes}-{$dia}") < strtotime("today - 2 days")) {
			throw new Exception("Data de abertura não pode ser anterior a 2 dias");
		}
	}
}

function verifica_posto_interno ($posto_id) {
	$sql = "SELECT tbl_posto_fabrica.tipo_posto from tbl_tipo_posto
			JOIN tbl_posto_fabrica on tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
			WHERE tbl_posto_fabrica.posto   = {$posto_id}
			AND   tbl_posto_fabrica.fabrica = {$login_fabrica}
			AND   tbl_tipo_posto.posto_interno IS TRUE";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		return true; 
	} else {
		return false;
	}
}

/**
 * - auditoria_troca_produto_dancor()
 * Entra em auditoria Ordem de Serviço
 * que foi flegado o check 'troca de produto'
 */
function auditoria_troca_produto_dancor()
{
    global $con, $campos, $login_fabrica, $os;

    $troca_produto = $campos['produto']['troca_produto'];

    if ($troca_produto == 't' && verifica_auditoria_unica("tbl_auditoria_status.fabricante = 't'", $os)) {
        $busca = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

        if($busca['resultado']){
            $auditoria_status = $busca['auditoria'];
        }
        $sqlGrava = "
            INSERT INTO tbl_auditoria_os (
                os,
                auditoria_status,
                observacao
            ) VALUES (
                $os,
                $auditoria_status,
                'OS em auditoria por Solicitação de Troca'
            )
        ";
        $resGrava = pg_query($con,$sqlGrava);

        if (pg_last_error($con)) {
            throw new Exception("Erro ao lançar Ordem de Servico");
        }
    }
}
