<?php

if ($_POST['consumidor']['estado_ex'] == "EX") {

	$regras["consumidor|cep"]["obrigatorio"] = false;
	$regras["consumidor|cidade"]["obrigatorio"] = false;
	$regras["consumidor|estado"]["obrigatorio"] = false;

} else {

	$regras["consumidor|cep"]["obrigatorio"] = true;
	$regras["consumidor|endereco"]["obrigatorio"] = true;
	$regras["consumidor|bairro"]["obrigatorio"] = true;
	$regras["consumidor|numero"]["obrigatorio"] = true;
}

if($_POST['os']['consumidor_revenda'] == "R" AND $aplicativo == true){
	unset($regras["consumidor|cep"]["regex"]);
}

if (isset($_POST['peca_reposicao'])) {
	$regras["produto|serie"]["obrigatorio"] 	= false;
	$regras["revenda|nome"]["obrigatorio"] = false;
	$regras["revenda|cnpj"]["obrigatorio"] = false;
	$regras["revenda|cidade"]["obrigatorio"] = false;
	$regras["revenda|estado"]["obrigatorio"] = false;
}else{
    $regras["produto|serie"]["obrigatorio"]     = true;
}
$regras["produto|horimetro"]["obrigatorio"] = true; //hd_chamado=6422388 -> interacao 66
$regras["os|tipo_atendimento"]["function"] 	= array("valida_tipo_atendimento");
$regras["consumidor|email"]["obrigatorio"] = true; //hd_chamado=2915463
$auditoria_condicao_numero_serie = " AND TRIM(tbl_os.serie) = '".$campos['os']['serie']."'";

$valida_anexo_boxuploader = "valida_anexo_boxuploader";

if(getValue("os[tipo_atendimento]") == 76977){
	$regras['os|data_compra']['obrigatorio'] = false;
	$regras['os|nota_fiscal']['obrigatorio'] = false;
	$regras['os|defeito_reclamado']['obrigatorio'] = false;
}

$regras["os|data_falha"]["function"] = ["validaDataFalha"];

if (strlen(getValue("os[tipo_atendimento]")) > 0) {
	$tipo_atendimento_arr = getTipoAtendimento(getValue("os[tipo_atendimento]"));

	if ($tipo_atendimento_arr["entrega_tecnica"] == "t" || $tipo_atendimento_arr["grupo_atendimento"] == "R") {
		$regras["os|defeito_reclamado"]["obrigatorio"] = false;
	}
	if ($tipo_atendimento_arr["entrega_tecnica"] == "t" or $tipo_atendimento_arr['descricao'] =='RECALL') {
		unset($valida_garantia,$valida_garantia_item);
	}
	if ($tipo_atendimento_arr["grupo_atendimento"] == "R") {
		$regras["produto|defeito_constatado"]["obrigatorio"] = false;
		unset($regras["produto|defeito_constatado"]["function"]);
	}

}

function validaDataFalha() {
	global $campos, $con;

	$data_falha   = $campos["os"]["data_falha"];

	$sqlTipoGarantia = "SELECT tipo_atendimento
                        FROM tbl_tipo_atendimento
                        WHERE fora_garantia IS NOT TRUE
			AND tipo_atendimento = ".$campos["os"]["tipo_atendimento"]."
			AND upper(descricao) = 'GARANTIA'";
    $resTipoGarantia = pg_query($con, $sqlTipoGarantia);

    if (pg_num_rows($resTipoGarantia) > 0 && empty($data_falha)) {

    	throw new Exception(traduz("Informe a data da falha"));

    }

}

/**
 * Função para validar anexo
 */
function valida_anexo_yanmar() {
    global $campos, $msg_erro, $fabricaFileUploadOS, $os, $con, $login_fabrica, $areaAdmin;

    $tipo_atendimento = $campos["os"]["tipo_atendimento"];
    $produto_serie    = trim($campos["produto"]["serie"]);

    $arrTipoAtendimento = getTipoAtendimento($tipo_atendimento);
    
    if ($fabricaFileUploadOS && $arrTipoAtendimento["descricao"] == "GARANTIA" && !empty($produto_serie)) {

        $anexo_chave = $campos["anexo_chave"];
    
        if (!empty($anexo_chave)){

             if (!empty($os)){

                 $cond_tdocs = "AND tbl_tdocs.referencia_id = $os";

             }else{

                 $cond_tdocs = "AND tbl_tdocs.hash_temp = '$anexo_chave'";

             }

             $sql_tdocs = "
             	SELECT tdocs
                   FROM tbl_tdocs 
                   WHERE tbl_tdocs.fabrica = $login_fabrica
                   AND tbl_tdocs.situacao = 'ativo'
                   $cond_tdocs
            ";
            $res_tdocs = pg_query($con,$sql_tdocs);
     
            if (pg_num_rows($res_tdocs) < 4){
     			
             throw new Exception(traduz("Favor, anexar no mínimo 4 anexos para prosseguir"));
     	
            }

        }

    }

}

$valida_anexo = "valida_anexo_yanmar";

/*
function horimetroObrigatorio(){
	global $con, $login_fabrica, $campos, $regras;


	if (!empty($campos['os']['tipo_atendimento'])) {
		$sql = "SELECT entrega_tecnica FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento = {$campos['os']['tipo_atendimento']}";
		$res = pg_query($con, $sql);

		if(pg_fetch_result($res,0,0) == "t"){
			$regras["produto|horimetro"]["obrigatorio"] = false;
		}else{
			$regras["produto|horimetro"]["obrigatorio"] = true;
		}
	}
}
*/
function getTipoAtendimento($tipo_atendimento) {
	global $con, $login_fabrica;

	if (!empty($tipo_atendimento)) {
		$sql = "SELECT tipo_atendimento as id, UPPER(fn_retira_especiais(descricao)) as descricao, entrega_tecnica, grupo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento = {$tipo_atendimento}";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			return array(
				"id"                => pg_fetch_result($res, 0, "id"),
				"descricao"         => pg_fetch_result($res, 0, "descricao"),
				"entrega_tecnica"   => pg_fetch_result($res, 0, "entrega_tecnica"),
				"grupo_atendimento" => pg_fetch_result($res, 0, "grupo_atendimento")
			);
		} else {
			throw new Exception("Tipo de Atendimento inválido");
		}
	} else {
		return false;
	}
}

function valida_tolerancia_horas($horimetro, $revisao, $tolerancia, $regra = null) {
	$tolerancia_1p  = $revisao * ($tolerancia / 100);
	$tolerancia_max = $revisao + $tolerancia_1p;
	$tolerancia_min = $revisao - $tolerancia_1p;

	$return = true;
	switch ($regra) {
		case '>':
			if ($horimetro > $tolerancia_max) {
				$return = false;
			}
			break;

		case '<':
			if ($horimetro < $tolerancia_min) {
				$return = false;
			}
			break;

		default:
			if ($horimetro < $tolerancia_min || $horimetro > $tolerancia_max) {
				$return = false;
			}
			break;
	}

	return  $return;
}

$hora_tecnica_faltante = "";

function valida_revisoes_efetuadas($produto, $serie, $horimetro) {
	global $con, $login_fabrica, $campos,$hora_tecnica_faltante;

	$return = true;

	$sql = "SELECT valores_adicionais FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto}";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$valores_adicionais = pg_fetch_result($res, 0, "valores_adicionais");
		$valores_adicionais = json_decode($valores_adicionais, true);
		if(empty($valores_adicionais)) {
			throw new Exception("Produto com problema de cadastro de revisão, favor entrar em contato com fabricante");
		}
		$revisao = $campos["produto"]["revisao"];

		$revisoes = $valores_adicionais["revisao"];

		$primeira_revisao = $revisoes["primeira"]["horas"];//50

		$intervalo_revisao = $revisoes["intervalo"]["horas"];//250

		$qtde_revisoes = floor($horimetro / $intervalo_revisao);//No caso do teste, 5

		$produto = $campos["produto"]["id"];
		$serie   = $campos["produto"]["serie"];

		if ($qtde_revisoes == 0 && valida_tolerancia_horas($horimetro, $primeira_revisao, $revisoes["tolerancia"]) == false) {
            $sql = "SELECT  os
                    FROM    tbl_os
                    WHERE   fabrica         = {$login_fabrica}
                    AND     hora_tecnica    = {$primeira_revisao}
                    AND     produto         = {$produto}
                    AND     UPPER(TRIM(serie)) = UPPER(TRIM('{$serie}'))
            ";
			$res = pg_query($con, $sql);

			if (!pg_num_rows($res)) {
                $hora_tecnica_faltante = $primeira_revisao;
				$return = false;
			}
		}

		if (($qtde_revisoes > 0 || valida_tolerancia_horas($horimetro, $primeira_revisao, $revisoes["tolerancia"], ">") == true) && $return == true) {
			$max = $intervalo_revisao * $qtde_revisoes;//No caso do teste, 1250

			for ($i = $max; $i >= $intervalo_revisao; $i = $i - $intervalo_revisao) {
				if (valida_tolerancia_horas($horimetro, $i, $revisoes["tolerancia"]) == false) {
                    $sql = "SELECT  os
                            FROM    tbl_os
                            WHERE   fabrica         = {$login_fabrica}
                            AND     hora_tecnica    = {$i}
                            AND     produto         = {$produto}
                            AND     UPPER(TRIM(serie))  = UPPER(TRIM('{$serie}')) LIMIT 1
                    ";
                    
					$res = pg_query($con, $sql);

					if (!pg_num_rows($res)) {
                        $hora_tecnica_faltante[] = $i;
						$return = false;
						continue;
					}
				}
			}
		}
	} else {
		throw new Exception("Produto não encontrado");
	}

	return $return;
}

function valida_revisao_duplicada($produto, $serie, $revisao) {
	global $con, $login_fabrica, $os;

	if (!empty($os)) {
		$whereOs = "AND os < ({$os})";
	}

    $sql = "SELECT  os
            FROM    tbl_os
            WHERE   fabrica         = {$login_fabrica}
            AND     produto         = {$produto}
			AND     UPPER(TRIM(serie))     = UPPER(TRIM('{$serie}'))
			AND		excluida is not true
            AND     hora_tecnica    = {$revisao}
            {$whereOs}
    ";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		return false;
	} else {
		return true;
	}
}

function getRevisoes($produto) {
	global $con, $login_fabrica;

	$sql = "SELECT valores_adicionais FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto}";
	$res = pg_query($con, $sql);

	$valores_adicionais = json_decode(pg_fetch_result($res, 0, "valores_adicionais"));

	return $revisao = $valores_adicionais->revisao;
}

function valida_pecas_obrigatorias_revisao($produto, $revisao) {
	global $campos;

	$revisao_regra_pecas;
	$revisao_regra_servicos;

	$horas_excecao = 0;
	$i = 0;

	$revisoes = getRevisoes($produto);

	if ($revisoes->primeira->horas == $revisao) {
		$revisao_regra_pecas    = $revisoes->primeira->pecas;
		$revisao_regra_servicos = $revisoes->primeira->servicos;
	} else {
		foreach ($revisoes->excecao as $horas => $array) {
			if ($revisao % $horas == 0) {
				$x = $revisao / $horas;

				if ($i == 0 || $x < $i) {
					$horas_excecao = $horas;
				}
			}
		}

		if ($horas_excecao > 0) {
			$revisao_regra_pecas    = $revisoes->excecao->$horas_excecao->pecas;
			$revisao_regra_servicos = $revisoes->excecao->$horas_excecao->servicos;
		} else {
			$revisao_regra_pecas    = $revisoes->intervalo->pecas;
			$revisao_regra_servicos = $revisoes->intervalo->servicos;
		}
	}

	$pecas_os = $campos["produto_pecas"];

	$pecas_lancadas     = 0;
	$pecas_obrigatorias = count($revisao_regra_pecas);

	if (isset($revisao_regra_pecas) && count($revisao_regra_pecas) > 0) {
		foreach ($revisao_regra_pecas as $key => $peca) {
			$servico = $revisao_regra_servicos[$key];

			foreach ($pecas_os as $key_os => $peca_os) {
				if ($peca == $peca_os["id"] && $servico == $peca_os["servico_realizado"]) {
					$pecas_lancadas++;
				}
			}
		}
	}

	if ($pecas_lancadas == $pecas_obrigatorias) {
		return true;
	} else {
		return false;
	}
}

$produto_garantia_yanmar            = true;
$produto_auditoria_revisao_yanmar   = false;

/* Validações */
function valida_tipo_atendimento(){
	global $campos, $con, $login_fabrica, $msg_erro, $produto_garantia_yanmar,$produto_auditoria_revisao_yanmar,$os;

	if (empty($msg_erro["msg"])) {
		$venda            = $campos["os"]["venda"];
		$tipo_atendimento = $campos["os"]["tipo_atendimento"];
		$produto          = $campos["produto"]["id"];
		$horimetro        = $campos["produto"]["horimetro"];
		$produto          = $campos["produto"]["id"];
		$serie            = $campos["produto"]["serie"];
		$data_compra      = $campos["os"]["data_compra"];
		$revisao          = $campos["produto"]["revisao"];

		if(!empty($os) and strlen(trim($serie)) > 0 ) {
			$sql = "SELECT  qtde_hora
				FROM    tbl_os
				WHERE   fabrica     = {$login_fabrica}
				AND     produto     = {$produto}
				AND     UPPER(TRIM(serie)) = UPPER(TRIM('{$serie}'))
				AND     excluida is not true
				AND     os          < $os
				ORDER BY      data_digitacao DESC
				LIMIT   1 
				";
			$res = pg_query($con, $sql);
			if (pg_num_rows($res) > 0) {
				$ultimo_horimetro = pg_fetch_result($res, 0, "qtde_hora");

				$sql = "SELECT entrega_tecnica FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento = {$campos['os']['tipo_atendimento']}";

				$res = pg_query($con, $sql);
				$entregaTecnica = pg_fetch_result($res,0,0);

				if ($horimetro < $ultimo_horimetro && ($entregaTecnica !='t')) {
					throw new Exception("Valor do horimetro menor do gravado na última ordem de serviço, último horímetro informado $ultimo_horimetro ");
				}
			}
		} else if (strlen(trim($serie)) > 0) {
			$sql = " SELECT qtde_hora 
					 FROM tbl_os_produto p 
					 JOIN tbl_os USING(os) 
					 WHERE p.produto = {$produto} 
					 AND UPPER(TRIM(p.serie)) = UPPER(TRIM('{$serie}'))
					 AND tbl_os.fabrica = {$login_fabrica} 
					 AND tbl_os.excluida is not true 
					 ORDER BY data_digitacao DESC
					 LIMIT 1";
			$res = pg_query($con, $sql);
			if (pg_num_rows($res) > 0) {
				$ultimo_horimetro = pg_fetch_result($res, 0, "qtde_hora");

				$sql = "SELECT entrega_tecnica FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento = {$campos['os']['tipo_atendimento']}";

				$res = pg_query($con, $sql);
				$entregaTecnica = pg_fetch_result($res,0,0);

				if ($horimetro <= $ultimo_horimetro && ($entregaTecnica !='t')) {
					throw new Exception("Valor do horimetro menor do gravado na última ordem de serviço, último horímetro informado $ultimo_horimetro ");
				}
			}
		}

        $tipo_atendimento_arr = getTipoAtendimento($tipo_atendimento);
		//HD-3049906
        if(strlen($os) > 0){
            $cond_os = "and os <> $os";
        }

        $sql = "SELECT  os
                FROM    tbl_os
                WHERE   fabrica             = {$login_fabrica}
                AND     tipo_atendimento    = $tipo_atendimento
                AND     produto             = {$produto}
                AND     UPPER(TRIM(serie))  = UPPER(TRIM('{$serie}'))
                $cond_os
                AND excluida IS NOT TRUE
                ORDER BY data_digitacao DESC
                LIMIT 1
        ";
        
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0){
            $os_cadastrada = "OS: ".pg_fetch_result($res, 0, 'os');

            // if(pg_num_rows($res) > 1) {
            // 	$os_cadastrada = "OS: ".pg_fetch_result(end($res), 0, 'os');
            // }

            if ($tipo_atendimento_arr["entrega_tecnica"] == "t" && verifica_entrega_tecnica($venda, $produto) == true) {
                throw new Exception("Já existe uma Ordem de Serviço de entrega técnica ".$os_cadastrada);
            }
        }
	    //HD-3049906 - FIM
		if ($tipo_atendimento_arr["entrega_tecnica"] == "f" && verifica_entrega_tecnica($venda, $produto) == false && !in_array($tipo_atendimento_arr['descricao'], ['RECALL']) && !isset($_POST["lib_peca_reposicao"]) && $campos["produto"]["produto_em_estoque"] != "on") {
			throw new Exception("Produto fora da garantia, não foi identificado uma Ordem de Serviço de entrega técnica");
		}

		if($tipo_atendimento_arr["descricao"] == "REPARO"){
			if (valida_revisoes_efetuadas($produto, $serie, $horimetro) == false) {
				$produto_garantia_yanmar = false;
			}

			if (valida_garantia(true) == false) {
				$produto_garantia_yanmar = false;
			}
		}

		if($tipo_atendimento_arr["descricao"] == "GARANTIA" || $tipo_atendimento == 220){
            if (!empty($serie)) {
                if (valida_revisoes_efetuadas($produto, $serie, $horimetro) == false) {
                    $produto_auditoria_revisao_yanmar = true;
                }
            }
		}

		if($tipo_atendimento_arr["descricao"] == "REVISAO"){
			$revisoes = getRevisoes($produto);

			if(empty($revisao)){
				throw new Exception("Informe a revisão");
			}

			if (valida_tolerancia_horas($horimetro, $revisao, $revisoes->tolerancia) == false) {
				throw new Exception("Horimetro fora da tolerância da revisão");
			}

// 			if (valida_revisoes_efetuadas($produto, $serie, $horimetro) == false) {
//                 throw new Exception("Produto fora da garantia, há revisões que não foram efetuadas");
// 			}

			if (valida_revisao_duplicada($produto, $serie, $revisao) == false) {
				throw new Exception("A revisão de {$revisao} horas já foi efetuada para o produto");
			}

			if (valida_pecas_obrigatorias_revisao($produto, $revisao) == false) {
				throw new Exception("As peças de lançamentos obrigatório devem ser lançadas na Ordem de Serviço com seus respectivos serviços");
			}
		}

		if($login_fabrica == 148){
			$sql = "SELECT linha from tbl_produto where produto = $produto";
			$res = pg_query($con, $sql);
		
			$linha = pg_fetch_result($res, 'linha');
		
			if(($tipo_atendimento == 220) && ($linha == 876)){
				if(strlen($campos["os"]["acessorios"]) == 0){
					throw new Exception("Para o Tipo de Atendimento Selecionado é Necessário o Lançamento dos Implemento (s)");
				} 
			}
		}
	
		if (strtoupper($tipo_atendimento_arr["descricao"]) == "GARANTIA" || strtoupper(trim($tipo_atendimento_arr["descricao"])) == "PMP   PROGRAMA MELHORIA PRODUTO") {
			if (count($campos['produto_pecas']) == 0) {
				throw new Exception("Para o Tipo de Atendimento Selecionado é Necessário o Lançamento de Peças");
			} else {
				$nTemPeca = true;
				foreach ($campos['produto_pecas'] as $key => $dados) {
	                if (!empty($dados["id"])) {
	                    $nTemPeca = false; 
	                    break;
	                }
	            }
			}
			if ($nTemPeca) {
				$msg_erro["msg"][] = "Para o Tipo de Atendimento Selecionado é Necessário o Lançamento de Peças";
			}
		}
	}
}

/* OS */

function grava_os_fabrica(){

	global $campos;

	$horimetro = $campos["produto"]["horimetro"];
	$horimetro = (strlen($campos["produto"]["horimetro"]) == 0) ? "null" : $horimetro;
	$intervalo_revisao = (strlen($campos["produto"]["revisao"]) == 0) ? 0 : $campos["produto"]["revisao"];
	
	if ($campos['consumidor']['cidade_ex'] != "EX") {
		
		return array(
			"qtde_hora" 	=> "{$horimetro}",
			"hora_tecnica" 	=> "{$intervalo_revisao}"
		);
	} 

	$cidade = $campos['consumidor']['cidade_ex'];
	$estado = $campos['consumidor']['estado_ex'];

	return array(
		"qtde_hora" 	=> "{$horimetro}",
		"hora_tecnica" 	=> "{$intervalo_revisao}",
		"estado" 		=> "{$estado}",
		"cidade" 		=> "{$cidade}"
	);

}

function grava_os_extra_fabrica(){

	global $campos;

	$campos["produto"]["serie_motor"] = str_replace("\\","",$campos["produto"]["serie_motor"]);
	$campos["produto"]["serie_motor"] = str_replace("'","",$campos["produto"]["serie_motor"]);

	$campos_adicionais = array(
		"serie_motor" => $campos["produto"]["serie_motor"], 
		"serie_transmissao" => $campos["produto"]["serie_transmissao"],
		"data_falha" => $campos["os"]["data_falha"]
	);
	$campos_adicionais = json_encode($campos_adicionais);

	return array(
		"obs_adicionais" => "'{$campos_adicionais}'"
	);

}

function grava_custo_peca(){

	global $campos;

	$campos_peca = $campos["produto_pecas"];
	$custo_peca = array();

	foreach ($campos_peca as $posicao => $campos_peca) {
		if(strlen($campos_peca["id"]) > 0){
			$custo_peca[$campos_peca["id"]] = $campos_peca["valor_total"];
		}
	}
	if(empty($custo_peca)){
		return false;
	}
	return $custo_peca;

}

/**
* Grava Valores em campos especificos
*/
function grava_campo_valor_adicional(){

	global $con, $login_fabrica, $campos, $os;
	
	$detalhes_campo = null;

	if($aplicativo == false){
		$detalhes_defeito = str_replace('\r','<br>',$campos['produto']['detalhes_defeito']);
		$detalhes_solucao = str_replace('\r','<br>',$campos['produto']['detalhes_solucao']);

		if(getValue("os[tipo_atendimento]") == 220){
			if(strlen($campos['produto']['defeitos_constatados_multiplos']) > 0){
				if(strlen($detalhes_defeito) < 100){
					throw new Exception("Campos de detalhes de defeito obrigatório no minimo 100 caracteres");
				}
			}
		
			if(strlen($campos['produto']['solucoes_multiplos']) > 0){
				if(strlen($detalhes_solucao) < 100){
					throw new Exception("Campos de detalhes da solução obrigatório no minimo 100 caracteres");
				}
			}
		}

		$sqlVl = "SELECT valores_adicionais FROM tbl_os_campo_extra WHERE os = $os AND fabrica = $login_fabrica";
		$resVl = pg_query($con, $sqlVl);
		if (pg_num_rows($resVl) > 0) {			
			$vl = json_decode(pg_fetch_result($resVl, 0, 'valores_adicionais'), true);
			$vl["produto_em_estoque"] = ($campos["produto"]["produto_em_estoque"] == "on") ? "sim" : "nao" ;
			$vl['descricao_falha'] = utf8_encode($detalhes_defeito);
			$vl['detalhe_solucao'] = utf8_encode($detalhes_solucao);
			$vl = json_encode($vl);
			$campoVl = ", valores_adicionais = '$vl'";
		} else {
			$vl = [];
			$vl["produto_em_estoque"] = ($campos["produto"]["produto_em_estoque"] == "on") ? "sim" : "nao" ;
			$vl['descricao_falha'] = utf8_encode($detalhes_defeito);
			$vl['detalhe_solucao'] = utf8_encode($detalhes_solucao);
			$vl = json_encode($vl);
			$campoVl = ", valores_adicionais";
			$valorVl = ", '$vl'";
		}
	}
	
	if(strlen($os) > 0){
		$venda = $campos["os"]["venda"];
        if (!empty($venda)) {
    		$sql = "SELECT venda FROM tbl_os_campo_extra WHERE os = {$os}";
    		$res = pg_query($con,$sql);

    		if(pg_num_rows($res) > 0){
    			$sql = "UPDATE tbl_os_campo_extra SET venda = $venda $campoVl WHERE os = $os AND fabrica = $login_fabrica";
    		}else{
    			$sql = "INSERT INTO tbl_os_campo_extra(os,fabrica,venda $campoVl) VALUES({$os},{$login_fabrica},{$venda} $valorVl)";
			}

    		$res = pg_query($con, $sql);

    		if (strlen(pg_last_error()) > 0) {
    			throw new Exception("Erro ao gravar Ordem de Serviço #13");
    		}
        }

	}

}

/* Tipo Atendimento */
function verifica_entrega_tecnica($venda, $produto){

	global $con, $login_fabrica;

	$sql = "SELECT
				tbl_os.os
			FROM tbl_os
			JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os
			WHERE
				tbl_os.fabrica = {$login_fabrica}
				AND tbl_os_campo_extra.venda = {$venda}
				AND tbl_os.produto = {$produto}
				AND tbl_os.tipo_atendimento = (SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND entrega_tecnica is true)
				AND tbl_os.excluida IS NOT TRUE
				AND tbl_os.cancelada IS NOT TRUE
    ";
	$res = pg_query($con, $sql);

	return (pg_num_rows($res) > 0) ? true : false;

}

$auditorias = array(
    "auditoria_os_reincidente_yanmar",
    "auditoria_peca_critica",
    "auditoria_produto_troca_obrigatoria",
    "grava_auditoria_os_fora_garantia",
    "auditoria_defeito_constatado_peca_lancada",
    "auditoria_km",
    "auditoria_fabrica",
    "auditoria_revisao",
    "verifica_revisao",
    "auditoria_defeitos_constatados_multiplos"
);


function auditoria_revisao(){
	global $con, $login_fabrica, $os, $campos;


	if ($campos["os"]["tipo_atendimento"] == 218) {
		$produto_id = $campos['produto']['id'];
		$horimetro = $campos['produto']["revisao"];

		$sql = "SELECT 	tbl_produto.parametros_adicionais::jsonb->>'auditoria_revisao' AS auditoria_revisao, 
						tbl_linha.campos_adicionais->>'auditada' AS linha_auditada  
						FROM tbl_produto 
						JOIN tbl_linha USING(linha)
						WHERE tbl_produto.produto = $produto_id 
						AND tbl_produto.fabrica_i = $login_fabrica";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res)>0){
			$auditoria_revisao = pg_fetch_result($res, 0, 'auditoria_revisao');
			$linha_auditada    = pg_fetch_result($res, 0, 'linha_auditada');

			$sem_revisao = true;
			if (!empty($auditoria_revisao)) {
				$auditoria_revisao = json_decode($auditoria_revisao, true);
				foreach ($auditoria_revisao as $key => $value) {
					if($horimetro == $value){
						$sql = "SELECT  os
				                FROM    tbl_auditoria_os
				                WHERE   os = {$os}
				                AND     auditoria_status = 6
				                AND     observacao = 'Auditoria de Revisão Auditada' 
				                AND     liberada IS NULL
				                AND     cancelada IS NULL 
				                AND     reprovada IS NULL";
				        $res = pg_query($con,$sql); 
				        if(pg_num_rows($res)==0){				
							$sql = "INSERT INTO tbl_auditoria_os (auditoria_status, os, observacao, bloqueio_pedido) values (6, $os, 'Auditoria de Revisão Auditada', true)";
							$res = pg_query($con, $sql);
						}
						$sem_revisao = false;
						break;
					}
				}

				if ($linha_auditada == 'true' && $sem_revisao) {
					$sql = "SELECT  os
			                FROM    tbl_auditoria_os
			                WHERE   os = {$os}
			                AND     auditoria_status = 6
			                AND     observacao = 'Auditoria de Revisão' 
			                AND     liberada IS NULL
			                AND     cancelada IS NULL 
			                AND     reprovada IS NULL";
			        $res = pg_query($con,$sql); 
			        if(pg_num_rows($res)==0){				
						$sql = "INSERT INTO tbl_auditoria_os (auditoria_status, os, observacao, bloqueio_pedido) values (6, $os, 'Auditoria de Revisão', true)";
						$res = pg_query($con, $sql);
					}
				}

			} else if ($linha_auditada == 'true') {
				$sql = "SELECT  os
		                FROM    tbl_auditoria_os
		                WHERE   os = {$os}
		                AND     auditoria_status = 6
		                AND     observacao = 'Auditoria de Revisão' 
		                AND     liberada IS NULL
		                AND     cancelada IS NULL 
		                AND     reprovada IS NULL";
		        $res = pg_query($con,$sql); 
		        if(pg_num_rows($res)==0){
					$sql = "INSERT INTO tbl_auditoria_os (auditoria_status, os, observacao, bloqueio_pedido) values (6, $os, 'Auditoria de Revisão', true)";
					$res = pg_query($con, $sql);
				}
			}
		}
	}
}

// retira auditorias de tipo atendimento 218, 219, 278 hd-7237046 
if(strlen(getValue("os[tipo_atendimento]")) > 0) {

	if(in_array(getValue("os[tipo_atendimento]"), [76977, 218,219])){
	    unset($auditorias);
    }
	if(getValue("os[tipo_atendimento]") == 218){
        unset($auditorias);
        $auditorias[] = "auditoria_revisao";
	}
    if(getValue("os[tipo_atendimento]") == 220){
        $auditorias[] = "grava_auditoria_os_revisao";
    }

}


function auditoria_defeitos_constatados_multiplos(){
	global $con, $login_fabrica, $os, $campos;

	$defeitos_constatados_multiplos = $campos["produto"]["defeitos_constatados_multiplos"];

	$defeitos_constatados_multiplos = explode(",",$defeitos_constatados_multiplos);

	if(count($defeitos_constatados_multiplos) > 1){

		$busca = buscaAuditoria("tbl_auditoria_status.produto = 't'");

		if($busca['resultado']){
			$auditoria_status = $busca['auditoria'];

			$sql = "INSERT INTO tbl_auditoria_os
						(os, auditoria_status, observacao)
					VALUES
						({$os}, $auditoria_status, 'Auditoria de Defeitos Constatados do Produto')";
			$res = pg_query($con, $sql);
		}
	}
}

function auditoria_os_reincidente_yanmar() {
	global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

	$tipo_atendimento = $campos["os"]["tipo_atendimento"];

	$posto = $campos['posto']['id'];
	$sql = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os} AND os_reincidente IS NOT TRUE";
	$res = pg_query($con, $sql);

	$condicao_auditoria_os_serie = \Posvenda\Regras::get("condicao_auditoria_produto_serie", "ordem_de_servico", $login_fabrica);
	$condicao_auditoria_os_serie = ($condicao_auditoria_os_serie) ? " AND TRIM(tbl_os.serie) = '".$campos['produto']['serie']."'" : '';

	if(pg_num_rows($res) > 0){
		#hd_chamado=3082172 => adicionada condição tipo atendimento.
		$sql = "SELECT tbl_os.os,
						tbl_os.tipo_atendimento
				FROM tbl_os
				INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
				WHERE tbl_os.fabrica = {$login_fabrica}
				AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '90 days')
				AND tbl_os.excluida IS NOT TRUE
				AND tbl_os.cancelada IS NOT TRUE
				AND tbl_os.posto = $posto
				AND tbl_os.os < {$os}
				AND tbl_os.nota_fiscal = '{$campos['os']['nota_fiscal']}'
				AND tbl_os.revenda_cnpj = '".preg_replace("/[\.\-\/]/", "", $campos["revenda"]["cnpj"])."'
				AND tbl_os_produto.produto = {$campos['produto']['id']}
				AND tbl_os.tipo_atendimento NOT IN (218)
				$condicao_auditoria_os_serie
				ORDER BY tbl_os.data_abertura DESC
				LIMIT 1";
		$resSelect = pg_query($con, $sql);
		if(pg_num_rows($resSelect) > 0 && verifica_auditoria_unica("tbl_auditoria_status.reincidente = 't'", $os) === true) {
			$os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");
			$tipo_atendimento_reincidente = pg_fetch_result($resSelect,0,"tipo_atendimento");

			if($tipo_atendimento == $tipo_atendimento_reincidente){#hd_chamado=3082172
				if (verifica_os_reincidente_finalizada($os_reincidente_numero)) {
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
}

function auditoria_fabrica(){ //hd_chamado=2892201
	global $con, $login_fabrica, $os, $campos, $tipo_atendimento_arr;

	$tipo_atendimento = $campos["os"]["tipo_atendimento"];

	if (in_array($tipo_atendimento_arr["descricao"], array("GARANTIA", "RECALL", "ENTREGA TECNICA"))) {
		$auditoria = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

		if($auditoria['resultado']){
			$auditoria_status = $auditoria['auditoria'];
		}

		$sql = "SELECT tbl_auditoria_os.os,
						tbl_auditoria_os.auditoria_os,
						tbl_auditoria_os.liberada,
						tbl_auditoria_os.reprovada
			FROM tbl_auditoria_os
			INNER JOIN tbl_os ON tbl_os.os = tbl_auditoria_os.os AND tbl_os.fabrica = {$login_fabrica}
			WHERE tbl_auditoria_os.os = {$os}
			AND tbl_auditoria_os.auditoria_status = {$auditoria_status}
			AND tbl_auditoria_os.observacao ILIKE '%Auditoria de F%'";
		$res = pg_query($con, $sql);
		if(pg_num_rows($res) > 0){
	        $liberada           = pg_fetch_result($res, 0, "liberada");
	        $reprovada 			= pg_fetch_result($res, 0, "reprovada");
	        if(strlen($liberada) > 0 OR strlen($reprovada) > 0){
				$sql = "INSERT INTO tbl_auditoria_os
						(os, auditoria_status, observacao)
						VALUES
						({$os}, $auditoria_status, 'Auditoria de Fábrica')";
				$res = pg_query($con, $sql);
				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao lançar ordem de serviço");
				}
			}
	    }else{
	    	$sql = "INSERT INTO tbl_auditoria_os
					(os, auditoria_status, observacao)
					VALUES
					({$os}, $auditoria_status, 'Auditoria de Fábrica')";
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

function grava_auditoria_os_revisao()
{
    global $campos, $con, $login_fabrica, $os, $produto_auditoria_revisao_yanmar,$hora_tecnica_faltante;

    if ($produto_auditoria_revisao_yanmar === true) {
        $sql = "SELECT  os
                FROM    tbl_auditoria_os
                WHERE   os = {$os}
                AND     auditoria_status = 6
                AND     observacao ILIKE '%Revis%'
        ";
        $res = pg_query($con,$sql);

        if (!pg_num_rows($res)) {
            $faltantes = implode(", ",$hora_tecnica_faltante);
            $sql = "INSERT INTO tbl_auditoria_os
                    (os, auditoria_status, observacao)
                    VALUES
                    ({$os}, 6, substr('Revisões não realizadas: $faltantes',1,100))";
            $res = pg_query($con, $sql);
            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao lançar ordem de serviço");
            }
        }
    }
}

function grava_auditoria_os_fora_garantia(){
	global $campos, $con, $login_fabrica, $os, $produto_garantia_yanmar;

	if ($produto_garantia_yanmar === false) {
		$sql = "SELECT auditoria_status FROM tbl_auditoria_status WHERE fabricante = 't'";
		$res = pg_query($con, $sql);

		if(pg_num_rows($res) > 0){
			$auditoria_status = pg_fetch_result($res, 0, "auditoria_status");

			$sql = "SELECT os FROM tbl_auditoria_os WHERE os = {$os} AND auditoria_status = {$auditoria_status} AND observacao ILIKE '%fora de garantia%'";
			$res = pg_query($con, $sql);

			if (!pg_num_rows($res)) {
				$sql = "INSERT INTO tbl_auditoria_os
						(os, auditoria_status, observacao)
						VALUES
						({$os}, $auditoria_status, 'Produto fora de garantia')";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					throw new Exception("Erro ao lançar ordem de serviço");
				}
			}
		}
	}
}

function auditoria_defeito_constatado_peca_lancada(){
	global $con, $login_fabrica, $os, $campos, $login_admin;

	$auditoria = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

	if($auditoria['resultado']){
		$auditoria_status = $auditoria['auditoria'];

	}

	$auditoria = false;

	$sql = "SELECT tbl_auditoria_os.auditoria_os,
			tbl_auditoria_os.liberada,
			tbl_os.defeito_constatado
		FROM tbl_auditoria_os
		INNER JOIN tbl_os ON tbl_os.os = tbl_auditoria_os.os AND tbl_os.fabrica = {$login_fabrica}
		WHERE tbl_auditoria_os.os = {$os}
		AND tbl_auditoria_os.auditoria_status = $auditoria_status
		AND tbl_auditoria_os.observacao ILIKE '%auditoria da ordem de serviço%'";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){
		$liberada           = pg_fetch_result($res, 0, "liberada");
		$defeito_constatado = pg_fetch_result($res, 0, "defeito_constatado");

		if(!empty($liberada) && ($defeito_constatado != $campos['produto']['defeito_constatado'] || verifica_peca_lancada() === true)){
			$auditoria = true;
		}
	}else if (!empty($campos['produto']['defeito_constatado']) || verifica_peca_lancada() == true) {
		$auditoria = true;
	}

	if ($auditoria == true) {
		$sql = "INSERT INTO tbl_auditoria_os
				(os, auditoria_status, observacao, admin)
				VALUES
				({$os}, $auditoria_status, 'Auditoria da Ordem de Serviço',$login_admin)";
		$res = pg_query($con, $sql);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro ao lançar ordem de serviço");
		}
	}
}

function grava_multiplas_solucoes_yanmar() {
    global $con, $os, $campos, $login_fabrica;

    $condSolucoes = (!empty($campos["produto"]["solucoes_multiplos"]))
        ? "\nAND     solucao NOT IN (".$campos["produto"]["solucoes_multiplos"].")"
        : "";

    $sqlDel = "
        DELETE  FROM tbl_os_defeito_reclamado_constatado
        WHERE   os = $os
        $condSolucoes
    ";

    $resDel = pg_query($con,$sqlDel);

    if (!empty($campos["produto"]["solucoes_multiplos"])) {
        $solucoes = explode(",", $campos["produto"]["solucoes_multiplos"]);
        for($i = 0; $i < count($solucoes); $i++){

            $sol = $solucoes[$i];

            $sql_sol = "SELECT defeito_constatado_reclamado FROM tbl_os_defeito_reclamado_constatado WHERE os = {$os} AND solucao = {$sol}";
            $res_sol = pg_query($con, $sql_sol);

            if (!pg_num_rows($res_sol)) {
                $sql_sol = "INSERT INTO tbl_os_defeito_reclamado_constatado (os, solucao, fabrica) VALUES ({$os}, {$sol}, {$login_fabrica})";
                $res_sol = pg_query($con, $sql_sol);
            }
        }
    }

}

$funcoes_fabrica = array(
	"grava_multiplas_solucoes_yanmar",
	"grava_os_tbl_campo_extra"
);

$antes_valida_campos = "antes_valida_campos_yanmar";

function antes_valida_campos_yanmar() {
	global $con, $campos, $login_fabrica;

	foreach ($campos["produto_pecas"] as $key => $peca) {

		if (empty($peca["id"])) {
			continue;
		}

		$sqlServicoPedido = "SELECT servico_realizado 
                             FROM tbl_servico_realizado
                             WHERE servico_realizado = ".$peca['servico_realizado']."
                             AND gera_pedido IS TRUE
                             AND troca_de_peca IS TRUE";
        $resServicoPedido = pg_query($con, $sqlServicoPedido);

        if (pg_num_rows($resServicoPedido) > 0 && empty($peca["nf_estoque_fabrica"])) {

           throw new Exception("Informe a Nota Fiscal da peça {$peca['referencia']}");

        }

        if (empty($peca["os_item"])) {

	        $sql = "SELECT tbl_tabela_item.preco
					FROM tbl_tabela_item
					JOIN tbl_tabela ON tbl_tabela.tabela = tbl_tabela_item.tabela
					WHERE
					tbl_tabela_item.peca = {$peca['id']}
					AND tbl_tabela.tabela_garantia IS TRUE
					AND tbl_tabela.fabrica = {$login_fabrica}
					AND tbl_tabela.ativa IS TRUE";
			$res = pg_query($con, $sql);

			if(pg_num_rows($res) > 0){
				$preco = pg_fetch_result($res, 0, "preco");
				#$preco = number_format($preco, 2);
			}else{
				$preco = "0.00";
			}
			
			$campos["produto_pecas"][$key]["valor"] = $preco;

		}

	}

}

// quando for os_entrega_tecnica nao chamar funcoes de garantia

function valida_garantia_yanmar($boolean = false) {
	global $con, $os ,$login_fabrica, $campos, $msg_erro;

	$data_abertura = $campos["os"]["data_abertura"];
	$produto       = $campos["produto"]["id"];
	$serie 		= $campos["produto"]["serie"];

    $select= "  SELECT  tbl_os.data_fechamento
                FROM    tbl_os
                JOIN    tbl_tipo_atendimento USING (tipo_atendimento)
                WHERE   tbl_os.produto                          = {$produto}
                AND     UPPER(TRIM(tbl_os.serie))               = UPPER(TRIM('{$serie}'))
                AND     tbl_os.fabrica                          = {$login_fabrica}
                AND     tbl_tipo_atendimento.entrega_tecnica    = 't'
                AND     tbl_os.data_fechamento                  IS NOT NULL
          ORDER BY      tbl_os.data_fechamento DESC
                LIMIT   1
    ";
	$res = pg_query($con,$select);

	if(pg_num_rows($res)>0){

		$data_fechamento = pg_fetch_result($res, 0, "data_fechamento");

		if (!empty($produto) && !empty($data_fechamento) && !empty($data_abertura)) {
			$sql = "SELECT garantia FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto}";
			$res = pg_query($con, $sql);

			if (pg_num_rows($res) > 0) {
				$garantia = pg_fetch_result($res, 0, "garantia");

				if (strtotime(formata_data($data_fechamento)." +{$garantia} months") < strtotime(formata_data($data_abertura))) {

					$sql = "UPDATE tbl_os SET tipo_os = 17 where os = {$os} ";
					$res = pg_query($con,$sql);

					if(strlen(pg_last_error()) > 0){
						$msg_erro["msg"][] = "Produto fora de garantia entre em contatto com o fabricante";
					}

					if ($boolean == false) {
						$msg_erro["msg"][] = "Produto fora de garantia";
					} else {
						return false;
					}
				} else if ($boolean == true) {
					return true;
				}
			}
		}
	}
}

$valida_garantia = "valida_garantia_yanmar";

/**
 * Função para validar a garantia da peça
 */
function valida_garantia_item_yanmar() {
	global $con, $os,$login_fabrica, $campos, $msg_erro;

	$data_compra	= $campos["os"]["data_compra"];
	$serie 		= $campos["produto"]["serie"];
	$produto		= $campos["produto"]["id"];
	$pecas			= $campos["produto_pecas"];

	$select= " SELECT  tbl_os.data_fechamento
                FROM    tbl_os
                JOIN    tbl_tipo_atendimento USING (tipo_atendimento)
                WHERE   tbl_os.produto                          = {$produto}
                AND     UPPER(TRIM(tbl_os.serie))               = UPPER(TRIM('{$serie}'))
                AND     tbl_os.fabrica                          = {$login_fabrica}
                AND     tbl_tipo_atendimento.entrega_tecnica    = 't'
                AND     tbl_os.data_fechamento                  IS NOT NULL
          ORDER BY      tbl_os.data_fechamento DESC
                LIMIT   1
    ";

	if(pg_num_rows($res)>0){

		$data_fechamento = pg_fetch_result($res, 0, "data_fechamento");

		if (!empty($produto)) {
			foreach ($pecas as $key => $peca) {
				if (empty($peca["id"])) {
					continue;
				}

				if(!empty($peca['servico_realizado'])) {
					$sql = "SELECT gera_pedido FROM tbl_servico_realizado where servico_realizado = ".$peca['servico_realizado'];
					$res = pg_query($con, $sql);

					if (pg_num_rows($res) > 0) {
						$gera_pedido = pg_fetch_result($res,0,'gera_pedido');
					}
				}

				if (!empty($peca['id']) && !empty($data_fechamento) && !empty($data_abertura) && $gera_pedido == 't') {
					$sql = "SELECT referencia, garantia_diferenciada FROM tbl_peca where peca= ".$peca['id'];
					$res = pg_query($con, $sql);

					if (pg_num_rows($res) > 0) {
						$referencia = pg_fetch_result($res, 0, "referencia");
						$garantia = pg_fetch_result($res, 0, "garantia_diferenciada");

						if($garantia > 0) {
							if (strtotime(formata_data($data_fechamento)." +{$garantia} months") < strtotime(formata_data($data_abertura))) {

								$sql = "UPDATE tbl_os SET tipo_os = 17 where os = {$os} ";
								$res = pg_query($con,$sql);

								if(strlen(pg_last_error()) > 0){
									$msg_erro["msg"][] = "Produto fora de garantia entre em contatto com o fabricante";
								}

								$msg_erro["msg"][] = "Peça $referencia fora de garantia";
							}
						}
					}
				}
			}
		}
	}
}

$valida_garantia_item = "valida_garantia_item_yanmar";

$regras_pecas["lista_basica"] = false;

function dispara_email_yanmar($dados) {

	if (empty($dados) || $dados["erro"]) {
		return false;
	}

    include __DIR__."/../../../class/email/PHPMailer/PHPMailerAutoload.php";

    $phpMailer = new PHPMailer();

    $phpMailer->setFrom("suporte@telecontrol.com.br");

    foreach ($dados["email"] as $key => $value) {
    	$phpMailer->addAddress($value);
    }

    $phpMailer->isHTML(true);
    $phpMailer->Subject = "Abertura de OS ".$dados["tipo_atendimento"];
    $phpMailer->Body = "Foi aberta a OS ".$dados["os"]." com Tipo de atendimento: ".$dados["tipo_atendimento"]." e Familia ".$dados["familia"].".";
    $phpMailer->send();

}

function verifica_dispara_email_yanmar() {
	global $con, $os ,$login_fabrica, $campos, $msg_erro;
	$retorno = array();

    $sql= " SELECT 
					 tbl_tipo_atendimento.tipo_atendimento,
					 tbl_tipo_atendimento.descricao AS nome_atendimento,
					 tbl_familia.descricao AS nome_familia,
					 tbl_familia.familia,
					 tbl_linha.linha,
					 tbl_linha.nome AS nome_linha,
					 tbl_os.os
				FROM tbl_os
			 	JOIN tbl_os_produto USING (os) 
			 	JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento=tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
			 	JOIN tbl_produto ON tbl_produto.produto=tbl_os_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
			 	JOIN tbl_linha ON tbl_linha.linha = tbl_produto.linha AND tbl_linha.fabrica = {$login_fabrica}
			 	JOIN tbl_familia ON tbl_familia.familia = tbl_produto.familia AND tbl_familia.fabrica = {$login_fabrica}
			   WHERE tbl_os.fabrica = {$login_fabrica} 
			     AND tbl_os.os = {$os};";
	$res = pg_query($con, $sql);
	
	if (pg_num_rows($res) > 0) {

        $xos 	            = pg_fetch_result($res, 0, "os");
        $tipoAtendimento 	= pg_fetch_result($res, 0, "tipo_atendimento");
        $nomeTipo           = pg_fetch_result($res, 0, "nome_atendimento");
        $nomeFamilia        = pg_fetch_result($res, 0, "nome_familia");
        $linha 				= pg_fetch_result($res, 0, "linha");
        $familia			= pg_fetch_result($res, 0, "familia");
        $nomeLinha 			= pg_fetch_result($res, 0, "nome_linha");

        $sql_email = "	SELECT 	email,
        						parametros_adicionais 
        				FROM tbl_admin 
        				WHERE (parametros_adicionais ~ 'email_tipo_atendimento' OR parametros_adicionais ~ 'email_linha')
        				AND fabrica = $login_fabrica";
        $res_email = pg_query($con, $sql_email);

        if (pg_num_rows($res_email) > 0) {
            $emails = [];
            for ($e = 0; $e < pg_num_rows($res_email); $e++) {

                $parametros_adicionais = json_decode(pg_fetch_result($res_email, $e, 'parametros_adicionais'), true);

                if (count($parametros_adicionais["email_tipo_atendimento"]) > 0) {

                	if (!in_array($tipoAtendimento, $parametros_adicionais['email_tipo_atendimento'])) {
                		
	                	continue;

	                }

                }

                if (count($parametros_adicionais["email_linha"]) > 0) {

                	if (!in_array($linha, $parametros_adicionais['email_linha'])) {

	                	continue;

	                }

                }

                $emails[] = pg_fetch_result($res_email, $e, 'email');

            }
    		
    		$retorno["email"] 			 = $emails;
        	$retorno["linha"] 			 = $nomeLinha;
        	$retorno["tipo_atendimento"] = $nomeTipo;
        	$retorno["familia"] 		 = $nomeFamilia;
        	$retorno["os"] 		 		 = $xos;
		
			dispara_email_yanmar($retorno);
        }   
	}  else {
		$retorno["erro"] = true;
	}
}
$funcoes_comunicado = array("verifica_dispara_email_yanmar");

/* HD-7074643 */
function grava_os_tbl_campo_extra() {
	global $con, $os, $campos, $login_fabrica;

	$obs_nome_contato     = '';
	$obs_telefone_contato = '';
	$obs_observacao       = '';

	if (isset($campos['os']['observacoes_nome_contato'])):
		$obs_nome_contato = pg_escape_string(utf8_encode($campos['os']['observacoes_nome_contato']));
	endif;

	if (isset($campos['os']['observacoes_telefone_contato'])):
		$obs_telefone_contato = pg_escape_string($campos['os']['observacoes_telefone_contato']);
	endif;

	if (isset($campos['os']['obs_adicionais'])):
		$obs_observacao = pg_escape_string(utf8_encode($campos['os']['obs_adicionais']));
	endif;

	if ($obs_nome_contato != '' and $obs_telefone_contato != '') {

		$campos_obs_arr  = array(
			"observacao" => $obs_observacao, 
			"observacoes_nome_contato" => $obs_nome_contato, 
			"observacoes_telefone_contato" => $obs_telefone_contato);
		$campos_obs_json = json_encode($campos_obs_arr);

		$sql_obs_busca = "SELECT os,valores_adicionais FROM tbl_os_campo_extra WHERE os = $os and fabrica = $login_fabrica";
		$res_obs_busca = pg_query($con,$sql_obs_busca);

		if (pg_num_rows($res_obs_busca) > 0) {
			$campos_adicionais_get = json_decode(pg_fetch_result($res_obs_busca, 0, "valores_adicionais"), true);

			$campos_adicionais_get['observacao']                   = $obs_observacao;
			$campos_adicionais_get['observacoes_nome_contato']     = $obs_nome_contato;
			$campos_adicionais_get['observacoes_telefone_contato'] = $obs_telefone_contato;

			$campos_obs_json = json_encode($campos_adicionais_get);
			
			$sql_obs_extra = "UPDATE tbl_os_campo_extra SET valores_adicionais = '$campos_obs_json' WHERE os = $os AND fabrica = $login_fabrica";
		} else {
			$sql_obs_extra = "INSERT INTO tbl_os_campo_extra (os,fabrica,valores_adicionais)
			VALUES ($os,$login_fabrica,'$campos_obs_json')";
		}

		$res_obs_extra = pg_query($con, $sql_obs_extra); 

		if (strlen(pg_last_error()) > 0) { 
			throw new Exception("Erro ao gravar O.S - Ref: Observação Campos Extras #1".pg_last_error());	
		}
	}
}
?>
