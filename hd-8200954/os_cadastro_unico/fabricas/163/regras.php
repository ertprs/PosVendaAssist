<?php

$regras["produto|serie"] = array(
    "obrigatorio"  => true,
    "function" => array("valida_numero_de_serie_rowa")
);

$regras ["os|nota_fiscal"] = array(
    "obrigatorio" => true
);

$regras [ "os|data_abertura" ] = array(
        "obrigatorio" => true,
        "regex"       => "date",
        "function"    => array("valida_data_abertura_rowa")
);

$regras["consumidor|cpf"] = array(
    "obrigatorio" => true,
    "function" => array("valida_consumidor_cpf")
);

$regras["os|observacoes"] = array(
    //"obrigatorio" => true,
    "function" => array("valida_tipo_atendimento_rowa")
);

$regras["consumidor|cep"] = array(
    "obrigatorio" => true
);

$regras["consumidor|bairro"] = array(
    "obrigatorio" => true
);

$regras["consumidor|endereco"] = array(
    "obrigatorio" => true
);

$regras["consumidor|complemento"] = array(
    "obrigatorio" => false
);

$regras["consumidor|celular"] = array(
    "obrigatorio" => true
);

$regras["consumidor|telefone"] = array(
    "obrigatorio" => false
);

$regras["consumidor|numero"] = array(
    "obrigatorio" => true
);

$regras["revenda|nome"] = array(
    "obrigatorio" => false
);

$regras["revenda|cnpj"] = array(
    "obrigatorio" => false
);

$regras["revenda|estado"] = array(
    "obrigatorio" => false
);

$regras["revenda|cidade"] = array(
    "obrigatorio" => false
);

$regras["produto|defeito_constatado"] = array(
    "function" => array("valida_defeito_constatado_rowa")
);

$valida_anexo = "";

$valida_garantia = "valida_garantia_rowa";
$antes_valida_campos = "regras_tipo_posto_interno_revenda";

$auditorias = array(
    "auditoria_valores_adicionais",
    "auditoria_revenda",
    "auditoria_km",    
    "auditoria_troca_obrigatoria",
    "auditoria_peca_lancada",
    "auditoria_peca_lancada_revenda",
    "auditoria_peca_critica",
    "auditoria_reincidente_rowa"
);

$funcoes_fabrica = array("os_reincidente","verifica_estoque_peca","campos_adicionais_rowa");
########### Validações - Início ########### 



function regras_tipo_posto_interno_revenda() {
    global $campos, $regras, $valida_anexo;

    valida_nota_fiscal_rowa();

    if (empty($campos["posto"]["id"])) {
        global $login_posto;
        $posto_id = $login_posto;        
    } else {
        $posto_id = $campos["posto"]["id"];
    }
    

    $postoInterno = verifica_tipo_posto("posto_interno", "TRUE", $posto_id);

    if ($postoInterno == true) {

        $regras["consumidor|nome"] = array(
            "obrigatorio" => false
        );

        $regras["consumidor|estado"] = array(
            "obrigatorio" => false
        );

        $regras["consumidor|cidade"] = array(
            "obrigatorio" => false
        );

        $regras["consumidor|cpf"] = array(
            "obrigatorio" => false
        );

        $regras["consumidor|cep"] = array(
            "obrigatorio" => false
        );

        $regras[ "os|data_abertura" ] = array(
            "obrigatorio" => true,
            "regex"       => "date",
            "function"    => array("valida_data_abertura_rowa")
        );

        $regras["consumidor|bairro"] = array(
            "obrigatorio" => false
        );

        $regras["consumidor|endereco"] = array(
            "obrigatorio" => false
        );

        $regras["consumidor|complemento"] = array(
            "obrigatorio" => false
        );

        $regras["consumidor|celular"] = array(
            "obrigatorio" => false
        );

        $regras["consumidor|telefone"] = array(
            "obrigatorio" => false
        );

        $regras["consumidor|numero"] = array(
            "obrigatorio" => false
        );
        $valida_anexo = "";
    } else {
        $postoRevenda = verifica_tipo_posto("tipo_revenda", "TRUE", $posto_id);

        if ($postoRevenda == true) {

            $regras["consumidor|nome"] = array(
                "obrigatorio" => false
            );

            $regras["consumidor|estado"] = array(
                "obrigatorio" => false
            );

            $regras["consumidor|cidade"] = array(
                "obrigatorio" => false
            );

            $regras[ "os|data_abertura" ] = array(
                "obrigatorio" => true,
                "regex"       => "date",
                "function"    => array("valida_data_abertura_rowa")
            );

            $regras["consumidor|cpf"] = array(
                "obrigatorio" => false
            );

            $regras["consumidor|cep"] = array(
                "obrigatorio" => false
            );

            $regras["consumidor|bairro"] = array(
                "obrigatorio" => false
            );

            $regras["consumidor|endereco"] = array(
                "obrigatorio" => false
            );

            $regras["consumidor|complemento"] = array(
                "obrigatorio" => false
            );

            $regras["consumidor|celular"] = array(
                "obrigatorio" => false
            );

            $regras["consumidor|telefone"] = array(
                "obrigatorio" => false
            );

            $regras["consumidor|numero"] = array(
                "obrigatorio" => false
            );

            $regras["revenda|cnpj"] = array(
                "obrigatorio" => true
            );

            $regras["revenda|estado"] = array(
                "obrigatorio" => true
            );

            $regras["revenda|cidade"] = array(
                "obrigatorio" => true
            );

            $regras["revenda|nome"] = array(
                "obrigatorio" => true
            );
            
            $valida_anexo = "";
        }
    }

    // Verifica se tem Numero de serie ou Nota fiscal, só é necessário 1 ser Obrigatório
    if (!empty($campos["produto"]["serie"])) {
        $regras ["os|nota_fiscal"] = array(
            "obrigatorio" => false
        );
    } elseif (!empty($campos["os"]["nota_fiscal"])) {
        $regras["produto|serie"] = array(
            "obrigatorio"  => false,
            "function" => array("valida_numero_de_serie_rowa")
        );
    }
}

function valida_fora_garantia($tipo_atendimento){
    global $con, $login_fabrica;

    $sql = "SELECT fora_garantia FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento = {$tipo_atendimento}; ";
    $res = pg_query($con,$sql);
    $fora_garantia = pg_fetch_result($res, 0, fora_garantia);

    if ($fora_garantia == 't') {
        return true;
    } else {
        return false;
    }    
}

function valida_data_abertura_rowa() {
    global $campos, $os;

    $data_abertura = $campos["os"]["data_abertura"];

    if (!empty($data_abertura) && empty($os)) {
        list($dia, $mes, $ano) = explode("/", $data_abertura);

        if (!checkdate($mes, $dia, $ano)) {
            throw new Exception("Data de abertura inválida");
        } else if (strtotime("{$ano}-{$mes}-{$dia}") < strtotime("today - 89 days")) {
            throw new Exception("Data de abertura não pode ser anterior a 90 dias");
        }
    }
}

function valida_numero_de_serie_rowa(){
    global $con, $campos, $login_fabrica, $msg_erro, $regras;

    $produto_serie      = $campos["produto"]["serie"];
    
    if(empty($produto_serie) AND $regras['produto']['serie']['obrigatorio'] == TRUE){
        $msg_erro["msg"]["campo_obrigatorio"] = "Preencha todos os campos obrigatórios";
        $msg_erro["campos"][] = "produto[serie]";
    }
}

function campos_adicionais_rowa() {
    global $os, $campos;
    $tipo_atendimento = $campos["os"]["tipo_atendimento"];

    if(valida_fora_garantia($tipo_atendimento) === true){


        $valor_adicional_aux = $campos["os"]["valor_adicional_mo"];
        $valor_adicional_aux = str_replace(".", "", $valor_adicional_aux);
        $valor_adicional_aux = str_replace(",", ".", $valor_adicional_aux);

        $desconto_aux = $campos["os"]["desconto"];
        $desconto_aux = str_replace(".", "", $desconto_aux);
        $desconto_aux = str_replace(",", ".", $desconto_aux);

        $valores = array(
                    "Valor Adicional" => $valor_adicional_aux,
                    "Desconto" => $desconto_aux
                );
        $valores = json_encode($valores);

        grava_valor_adicional($valores, $os);
    }

}

function valida_defeito_constatado_rowa(){

    global $con, $campos, $login_fabrica, $msg_erro;

    $defeito_constatado = $campos["produto"]["defeito_constatado"];
    $produto_id = $campos["produto"]["id"];

    $sql = "SELECT troca_obrigatoria FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto_id} AND troca_obrigatoria is true";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $troca_obrigatoria_t = true;
    } else {
        $troca_obrigatoria_t = false;
    }

    if(strlen($defeito_constatado) > 0 AND $troca_obrigatoria_t == false){

        $sql = "SELECT descricao, lancar_peca FROM tbl_defeito_constatado WHERE fabrica = {$login_fabrica} AND defeito_constatado = {$defeito_constatado}";
        $res = pg_query($con, $sql);
        
        $descricao_dc = pg_fetch_result($res, 0, "descricao");
        $lancar_peca  = pg_fetch_result($res, 0, "lancar_peca");

        if($lancar_peca == "t"){

            $produto_pecas = $campos["produto_pecas"];
            $qtde_pecas = 0;

            foreach ($produto_pecas as $peca_pos => $peca_valor) {
                
                if(is_numeric($peca_pos)){

                    if(strlen(trim($peca_valor["id"])) > 0){

                        $qtde_pecas++;

                    }

                }

            }

            if($qtde_pecas == 0){
                $msg_erro["msg"]["campo_obrigatorio"] = "Para este Defeito Constatado ({$descricao_dc}) é necessário o lançamento de peça(s)";
                // $msg_erro["campos"][] = "produto[defeito_constatado]";
            }

        }

    }

}

/**
 * Função para validar a garantia do produto
 * Se tipo de atendimento for "Fora de Garantia" não deve realizar a validação
 */
function valida_garantia_rowa($boolean = false) {
    global $con, $login_fabrica, $campos, $msg_erro;

    $data_compra      = $campos["os"]["data_compra"];
    $data_abertura    = $campos["os"]["data_abertura"];
    $tipo_atendimento = $campos["os"]["tipo_atendimento"];
    $produto          = $campos["produto"]["id"];

    if (!empty($produto) && !empty($data_compra) && !empty($data_abertura) && valida_fora_garantia($tipo_atendimento) == false) {
        $sql = "SELECT garantia FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto}";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $garantia = pg_fetch_result($res, 0, "garantia");

            if (strtotime(formata_data($data_compra)." +{$garantia} months") < strtotime(formata_data($data_abertura))) {
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

########### Validações - Fim ########### 

########### Auditorias - Início ########### 
function auditoria_valores_adicionais() {
    global $con, $login_fabrica, $campos, $os;

    $tipo_atendimento = $campos["os"]["tipo_atendimento"];

    if (valida_fora_garantia($tipo_atendimento) == false) {

    	if(!empty($campos["os"]["valor_adicional_mo"]) and $campos["os"]["valor_adicional_mo"] != "0,00"){

    		foreach ($campos["os"]["valor_adicional_mo"] as $key => $value) {

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
                        ({$os}, $auditoria_status, 'OS em auditoria de Valores Adicionais', true)";
    			$res = pg_query($con, $sql);

    			if (strlen(pg_last_error()) > 0) {
    				throw new Exception("Erro ao lançar ordem de serviço");
    			}
    		}
    	}
    }
}

function auditoria_revenda(){
    global $login_fabrica, $campos, $os, $con, $login_admin;
    
    $tipo_atendimento = $campos["os"]["tipo_atendimento"];

    if (valida_fora_garantia($tipo_atendimento) == false) {

        $posto_id = $campos["posto"]["id"];
        $auditoria_status = 6;

        $sql_posto = "SELECT tipo_revenda FROM tbl_posto_fabrica
                      INNER JOIN tbl_tipo_posto on tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
                      WHERE posto = $posto_id and tbl_posto_fabrica.fabrica = $login_fabrica";
        $res_posto = pg_query($con, $sql_posto);

        if(strlen(trim(pg_last_error($con)))>0){
          $msg_erro .= "Erro ao encontrar tipo do posto - Auditoria de Revenda";
        }

        if(pg_num_rows($res_posto)>0){
            $tipo_revenda = pg_fetch_result($res_posto, 0, tipo_revenda);

            if($tipo_revenda == 't'){
            	if (verifica_auditoria_unica("tbl_auditoria_status.fabricante = 't' AND tbl_auditoria_os.observacao ILIKE '%Auditoria OS de Revenda%'", $os) === true) {
    				$busca = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

    				if($busca['resultado']){
    					$auditoria_status = $busca['auditoria'];
    				}

    				$sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido) VALUES
    	                    ({$os}, $auditoria_status, 'Auditoria OS de Revenda', true)";
    				$res = pg_query($con, $sql);

    				if (strlen(pg_last_error()) > 0) {
    					throw new Exception("Erro ao lançar ordem de serviço");
    				}
    			}
            }
        }
    }
}

function auditoria_km() {
    global $con, $login_fabrica, $os, $campos;

    $tipo_atendimento = $campos["os"]["tipo_atendimento"];

    if (valida_fora_garantia($tipo_atendimento) == false) {

        $sql = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND km_google IS TRUE AND tipo_atendimento = {$tipo_atendimento}";
        $res = pg_query($con, $sql);

        /* HD - 4225821 */
        if (pg_num_rows($res) > 0 AND $campos["os"]["qtde_km"] > 30) {
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

function auditoria_peca_lancada() {
    global $con, $os, $login_fabrica, $campos;

    $tipo_atendimento = $campos["os"]["tipo_atendimento"];

    if (valida_fora_garantia($tipo_atendimento) == false) {

        if(verifica_peca_lancada() === true){
            $sql = "SELECT COUNT(tbl_os_item.os_item) AS qtde_pecas
                    FROM tbl_os_item
                    INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                    INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.gera_pedido IS NOT TRUE 
                        -- AND troca_de_peca IS TRUE
                    WHERE tbl_os_produto.os = {$os}";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0 && pg_fetch_result($res, 0, "qtde_pecas") > 0 ) {
                $busca = buscaAuditoria("tbl_auditoria_status.peca = 't'");

                if($busca['resultado']){
                    $auditoria_status = $busca['auditoria'];
                }

                if (verifica_auditoria_unica("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%lançamento de peça%'", $os) === true || aprovadoAuditoria("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%lançamento de peça%'")) {
                    $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
                        ({$os}, $auditoria_status, 'OS em auditoria de lançamento de peça.')";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao lançar ordem de serviço");
                    }
                }
            }        
        }
    }
}

function auditoria_peca_lancada_revenda() {
    global $con, $os, $login_fabrica, $campos;

    $tipo_atendimento = $campos["os"]["tipo_atendimento"];

    if (valida_fora_garantia($tipo_atendimento) == false) {

        $posto_id = $campos["posto"]["id"];

        if (verifica_tipo_posto('tipo_revenda', "TRUE", $posto_id) === true AND verifica_peca_lancada() === true) {
          
            $sql = "SELECT COUNT(tbl_os_item.os_item) AS qtde_pecas
                    FROM tbl_os_item 
                    INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                    INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.gera_pedido IS NOT TRUE AND troca_de_peca IS TRUE
                    WHERE tbl_os_produto.os = {$os}";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0 && pg_fetch_result($res, 0, "qtde_pecas") > 0 ) {
                $busca = buscaAuditoria("tbl_auditoria_status.peca = 't'");

                if($busca['resultado']){
                    $auditoria_status = $busca['auditoria'];
                }

                if (verifica_auditoria_unica("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%lançamento de peça%'", $os) === true || aprovadoAuditoria("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%lançamento de peça%'")) {

                    $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
                        ({$os}, $auditoria_status, 'OS em auditoria de lançamento de peça.')";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao lançar ordem de serviço");
                    }
                }
            }        
        }
    }
}

function auditoria_reincidente_rowa(){

    global $login_fabrica, $campos, $os, $con, $login_admin, $os_reincidente_numero, $os_reincidente;

    $produto        = $campos["produto"]["id"];
    $serie          = $campos["produto"]["serie"];
    $auditoria_status = 1;

    $sql_verifica_auditoria = "SELECT os from tbl_auditoria_os WHERE os = $os and auditoria_status = $auditoria_status";
    $res_verifica_auditoria = pg_query($con, $sql_verifica_auditoria);

    if(pg_num_rows($res_verifica_auditoria) == 0){

        $sql = "SELECT tbl_os.os 
                    FROM tbl_os
                    INNER JOIN tbl_os_produto USING(os)
                    WHERE tbl_os_produto.serie = '{$serie}'
                        AND tbl_os_produto.produto = {$produto}
                        AND fabrica = {$login_fabrica}
                        AND os < {$os}
                        AND data_abertura >= (data_abertura - INTERVAL '180 days') 
                        ORDER BY os DESC 
                        LIMIT 1";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0){
            $os_reincidente_rowa = pg_fetch_result($res, 0, 'os');
            $os_reincidente = true;
            $os_reincidente_numero = $os_reincidente_rowa;

        }
    }
}
########### Auditorias - Fim ########### 

/**
 * Função que grava a OS reincidente e direciona para a tela de OBSERVAÇÂO 
 * Verica se a OS anterior for do mesmo Posto envia para a tela de Justificativa
 * Se for posto diferente envia direto para os_press
 */
function grava_os_reincidente_rowa($os_reincidente_numero) { 
    global $con, $login_fabrica, $os;

    $sql = "UPDATE tbl_os SET os_reincidente = TRUE WHERE fabrica = {$login_fabrica} AND os = {$os}";
    $res = pg_query($con, $sql);

    $sql = "UPDATE tbl_os_extra SET os_reincidente = {$os_reincidente_numero} WHERE os = {$os}";
    $res = pg_query($con, $sql);


    $sql = "SELECT posto FROM tbl_posto_fabrica INNER JOIN tbl_os USING(posto,fabrica) WHERE os = {$os} AND tbl_os.fabrica = {$login_fabrica}";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res) > 0) {
        $posto_atual = pg_fetch_result($res, 0, posto);
    }

    $sql = "SELECT posto FROM tbl_posto_fabrica INNER JOIN tbl_os USING(posto,fabrica) WHERE os = {$os_reincidente_numero} AND tbl_os.fabrica = {$login_fabrica}";
    $res = pg_query($con,$sql);
    if (pg_num_rows($res) > 0) {
        $posto_reinc = pg_fetch_result($res, 0, posto);
    }

    if (strlen(pg_last_error()) > 0) {
        throw new Exception("Erro ao lançar ordem de serviço reincidente");
    }

    if ($posto_atual == $posto_reinc) {
        header("Location: os_motivo_atraso.php?os={$os}&justificativa=ok");
    } else {
        header("Location: os_press.php?os={$os}");
    }
}

$grava_os_reincidente = "grava_os_reincidente_rowa";

/**
 * Função que valida as peças obrigatorias conforme o defeito constatado
 */
function valida_lancar_peca_obrigatorio_rowa(){
    global $login_fabrica, $msg_erro, $campos, $con;

    $pecas_pedido       = $campos["produto_pecas"];
    $defeito_constatado = $campos["produto"]["defeito_constatado"];
    $prod_troca_obrigatoria = $campos["produto"]["produto_troca_obrigatoria"];

    if(!empty($defeito_constatado) AND $prod_troca_obrigatoria != true) {
        $sql = "SELECT defeito_constatado
                FROM tbl_defeito_constatado
                WHERE fabrica = $login_fabrica
                AND defeito_constatado = $defeito_constatado
                AND lancar_peca";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res) > 0) {

            if(!verifica_peca_lancada(false) === true){
                $msg_erro["msg"]["peca_lancadas"] = "É obrigatório lançar peça para este defeito constatado!!!";
            }
        }
    }
}

$valida_lancar_peca_obrigatorio = "valida_lancar_peca_obrigatorio_rowa";

function grava_custo_peca(){

    global $campos;

    $campos_peca = $campos["produto_pecas"];

    $custo_peca = array();

    foreach ($campos_peca as $posicao => $campos_peca) {
        if(strlen($campos_peca["id"]) > 0){
            $custo_peca[$campos_peca["id"]]['custo_peca'] = $campos_peca["valor_total"];
            $custo_peca[$campos_peca["id"]]['peca'] = $campos_peca["valor"];
        }
    }

    if(empty($custo_peca)){
        return false;
    }
    return $custo_peca;

}

function grava_os_item_rowa ($os_produto, $subproduto = "produto_pecas") {

    global $con, $login_fabrica, $login_admin, $campos, $historico_alteracao, $grava_defeito_peca, $areaAdmin, $os;

    if (function_exists("grava_custo_peca") ) {
        /**
         * A função grava_custo_peca deve ficar dentro do arquivo de regras fábrica
         * A função também deve retornar um array sendo "campo_banco" => "valor_campo"
         */
        $custo_peca = grava_custo_peca();
        if($custo_peca==false){
            unset($custo_peca);
        }
    }

    if($historico_alteracao === true){
        $historico = array();
    }

    $tipo_atendimento = $campos["os"]["tipo_atendimento"];

    if (!empty($tipo_atendimento)) {
        $sqlTipoAtendimento = "
            SELECT fora_garantia FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento = {$tipo_atendimento}
        ";
        $resTipoAtendimento = pg_query($con, $sqlTipoAtendimento);

        $fora_garantia = pg_fetch_result($resTipoAtendimento, 0, "fora_garantia");

        $sqlServicoRealizado = "
            SELECT servico_realizado FROM tbl_servico_realizado WHERE fabrica = {$login_fabrica} AND gera_pedido IS NOT TRUE AND troca_de_peca IS TRUE AND peca_estoque IS TRUE
        ";
        $resServicoRealizado = pg_query($con, $sqlServicoRealizado);

        $servico_estoque = pg_fetch_result($resServicoRealizado, 0, "servico_realizado");

        foreach ($campos[$subproduto] as $posicao => $campos_peca) {
            if (strlen($campos_peca["id"]) > 0) {
                if ($fora_garantia == "t") {
                    $campos_peca["servico_realizado"]                   = $servico_estoque;
                    $campos[$subproduto][$posicao]["servico_realizado"] = $servico_estoque;
                }

                if($historico_alteracao === true){
                    include "$login_fabrica/historico_alteracao.php";
                }

                $sql = "SELECT troca_de_peca FROM tbl_servico_realizado WHERE fabrica = {$login_fabrica} AND servico_realizado = {$campos_peca['servico_realizado']}";
                $res = pg_query($con, $sql);

                $troca_de_peca = pg_fetch_result($res, 0, "troca_de_peca");

                if ($troca_de_peca == "t") {
                    $sql = "SELECT devolucao_obrigatoria FROM tbl_peca WHERE fabrica = {$login_fabrica} AND peca = {$campos_peca['id']}";
                    $res = pg_query($con, $sql);

                    $devolucao_obrigatoria = pg_fetch_result($res, 0, "devolucao_obrigatoria");

                    if ($devolucao_obrigatoria == "t") {
                        $devolucao_obrigatoria = "TRUE";
                    } else {
                        $devolucao_obrigatoria = "FALSE";
                    }
                } else {
                    $devolucao_obrigatoria = "FALSE";
                }
                $login_admin = (empty($login_admin)) ? "null" : $login_admin;

                if (empty($campos_peca["os_item"])) {
                    $custoPeca = $custo_peca[$campos_peca['id']]['custo_peca'];
                    $custoPeca = str_replace(".", "", $custoPeca);
                    $custoPeca = str_replace(",", ".", $custoPeca);

                    $precoPeca = $custo_peca[$campos_peca['id']]['peca'];
                    $precoPeca = str_replace(".", "", $precoPeca);
                    $precoPeca = str_replace(",", ".", $precoPeca, $count);

                    /*HD-4225821*/
                    if ($count == 2) {
                        $auxiliar  = explode(".", $precoPeca);
                        $precoPeca = $auxiliar[0] . $auxiliar[1] . "." .$auxiliar[2];
                    }

                    if (empty($custoPeca)) {
                        $custoPeca = "null";
                    }

                    if (empty($precoPeca)) {
                        $precoPeca = "null";
                    }

                    $colunaDefeitoPeca = ($grava_defeito_peca) ? ", defeito" : "";
                    $valorDefeitoPeca = ($grava_defeito_peca) ? ", {$campos_peca['defeito_peca']}" : "";

                    $sql = "INSERT INTO tbl_os_item
                            (
                                os_produto,
                                peca,
                                qtde,
                                servico_realizado,
                                peca_obrigatoria,
                                admin,
                                custo_peca, 
                                preco
                                {$colunaDefeitoPeca}
                            )
                            VALUES
                            (
                                {$os_produto},
                                {$campos_peca['id']},
                                {$campos_peca['qtde']},
                                {$campos_peca['servico_realizado']},
                                {$devolucao_obrigatoria},
                                {$login_admin},
                                {$custoPeca},
                                {$precoPeca}
                                {$valorDefeitoPeca}
                            )
                            RETURNING os_item";
                    $acao = "insert";

                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao gravar Ordem de Serviço #9");
                    }

                    $campos[$subproduto][$posicao]["os_item_insert"] = pg_fetch_result($res, 0, "os_item");
                } else {
                    $sql = "SELECT tbl_os_item.os_item
                            FROM tbl_os_item
                            INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica}
                            WHERE tbl_os_item.os_produto = {$os_produto}
                            AND tbl_os_item.os_item = {$campos_peca['os_item']}
                            AND tbl_os_item.pedido IS NULL
                            AND UPPER(tbl_servico_realizado.descricao) NOT IN('CANCELADO', 'TROCA PRODUTO')";
                    $res = pg_query($con, $sql);

                    if (verificaPecaCancelada($campos_peca["os_item"]) === true) {
                        continue;
                    }

                    if (verificaTrocaProduto($campos_peca["os_item"]) === true) {
                        continue;
                    }

                    if (pg_num_rows($res) > 0) {
                        $sql = "UPDATE tbl_os_item SET
                                    qtde = {$campos_peca['qtde']},
                                    servico_realizado = {$campos_peca['servico_realizado']}
                                    ".(($grava_defeito_peca == true) ? ", defeito = {$campos_peca['defeito_peca']}" : "")."
                                WHERE os_produto = {$os_produto}
                                AND os_item = {$campos_peca['os_item']}";
                        $acao = "update";
                        $res = pg_query($con, $sql);

                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Erro ao gravar Ordem de Serviço #10");
                        }
                    }
                }
            }
        }

        if($historico_alteracao === true){

            if(count($historico) > 0){

                grava_historico($historico, $os, $campos["posto"]["id"], $login_fabrica, $login_admin);

            }

        }
    }

}

$grava_os_item_function = "grava_os_item_rowa";

/**
 * - Valida Anexo
 */
function valida_anexo_rowa() {
    global $campos, $msg_erro;

    $count_anexo = array();

    foreach ($campos["anexo"] as $key => $value) {
        if (strlen($value) > 0) {
            $count_anexo[] = "ok";
        }
    }

    if(!count($count_anexo)){
        if (valida_fora_garantia($campos['os']['tipo_atendimento']) == false) {
            $msg_erro["msg"][] = traduz("Os anexos são obrigatórios");
        } else {
            $msg_erro["msg"][] = traduz("O anexo do laudo técnico é obrigatório");
        }
    }
}

//$valida_anexo = "valida_anexo_rowa";
$valida_anexo = "";

function valida_pecas_rowa($nome = "produto_pecas") {
    global $con, $msg_erro, $login_fabrica, $regras_pecas, $regras_subproduto_pecas, $campos;

    // if (valida_fora_garantia($campos['os']['tipo_atendimento']) == false) {

    if(verifica_peca_lancada(false) === true){

        $pecas_os = array();

        foreach ($campos[$nome] as $posicao => $campos_peca) {
            $peca      = $campos_peca["id"];
            $cancelada = $campos_peca["cancelada"];
            $pedido    = $campos_peca["pedido"];

            if (empty($peca)) {
                continue;
            }

            if (!empty($peca) && empty($campos_peca["qtde"])) {
                $msg_erro["msg"]["peca_qtde"] = traduz('informe.uma.quantidade.para.a.peca.%', null, $nome[$posicao]);
                $msg_erro["campos"][]         = "{$nome}[{$posicao}]";
                continue;
            }

            if ($nome == "subproduto_pecas") {
                $regra_validar = $regras_subproduto_pecas;
            } else {
                $regra_validar = $regras_pecas;
            }

            if(isset($campos_peca["defeito_peca"]) && empty($campos_peca["defeito_peca"]) AND valida_fora_garantia($campos['os']['tipo_atendimento']) == false ){
                $msg_erro["msg"]["peca_qtde"] = traduz('favor.informar.o.defeito.da.peca');
                $msg_erro["campos"][]         = "{$nome}[{$posicao}]";
                continue;
            }

            foreach ($regra_validar as $tipo_regra => $regra) {
                switch ($tipo_regra) {
                    case 'lista_basica':
                        if ($nome == "subproduto_pecas") {
                            $produto   = $campos["subproduto"]["id"];
                        } else {
                            $produto   = $campos["produto"]["id"];
                        }

                        $peca_qtde = $campos_peca["qtde"];

                        if ($regra == true && !empty($produto)) {
                            $sql = "SELECT qtde
                                    FROM tbl_lista_basica
                                    WHERE fabrica = {$login_fabrica}
                                    AND produto = {$produto}
                                    AND peca = {$peca}";
                            $res = pg_query($con, $sql);

                            if (!pg_num_rows($res)) {
                                if(strlen(trim($pedido))>0){
                                    continue;
                                }
                                $msg_erro["msg"][]    = traduz("Peça não consta na lista básica do produto");
                                $msg_erro["campos"][] = "{$nome}[{$posicao}]";
                            } else {
                                $lista_basica_qtde = pg_fetch_result($res, 0, "qtde");

                                if(array_key_exists($peca, $pecas_os)){
                                    $pecas_os[$peca]["qtde"] += $peca_qtde;
                                }else{
                                    $pecas_os[$peca]["qtde"] = $peca_qtde;
                                }

                                if($cancelada > 0){
                                    $pecas_os[$peca]["qtde"] -= $cancelada;
                                }

                                if ($pecas_os[$peca]["qtde"] > $lista_basica_qtde) {
                                    $msg_erro["msg"]["lista_basica_qtde"] = traduz("Quantidade da peça maior que a permitida na lista básica");
                                    $msg_erro["campos"][]                 = "{$nome}[{$posicao}]";
                                }
                            }
                        }

                    case 'servico_realizado':
                        if ($regra === true && !empty($campos_peca["id"]) && empty($campos_peca["servico_realizado"]) AND valida_fora_garantia($campos['os']['tipo_atendimento']) == false ) {
                            $msg_erro["msg"]["servico_realizado"] = traduz("Selecione o serviço da peça");
                            $msg_erro["campos"][] = "{$nome}[{$posicao}]";
                        }
                        break;
                }
            }
        }

    }
    // }

}
$valida_pecas = "valida_pecas_rowa";

function valida_qtde_lista_basica_rowa(){

    global $con, $login_fabrica, $login_admin, $campos, $historico_alteracao, $grava_defeito_peca, $areaAdmin, $os;

    // if (valida_fora_garantia($campos['os']['tipo_atendimento']) == false) {

        if($areaAdmin == false){

            $sql = "select tbl_os_produto.produto, tbl_peca.referencia, tbl_peca.descricao,
                    os_produto, e.peca,  e.admin , sum(qtde) as qtde
                from tbl_os_produto
                join tbl_os_item e using(os_produto)
                join tbl_peca on tbl_peca.peca = e.peca
                join tbl_servico_realizado on e.servico_realizado = tbl_servico_realizado.servico_realizado
                where os = $os and e.admin is null and tbl_servico_realizado.descricao <> 'Cancelado'
                group by
                tbl_os_produto.produto, tbl_peca.referencia, tbl_peca.descricao,
                    os_produto, e.peca,  e.admin , qtde, tbl_servico_realizado.servico_realizado ";
            $res = pg_query($con, $sql);

            if(pg_num_rows($res)>0){
                for($i=0; $i<pg_num_rows($res); $i++ ){
                    $peca       = pg_fetch_result($res, $i, 'peca');
                    $qtde       = pg_fetch_result($res, $i, 'qtde');
                    $descricao      = pg_fetch_result($res, $i, 'descricao');
                    $referencia     = pg_fetch_result($res, $i, 'referencia');
                    $produto    = pg_fetch_result($res, $i, 'produto');

                    $sql_lb = "SELECT qtde
                                FROM tbl_lista_basica
                                WHERE fabrica = {$login_fabrica}
                                AND produto = {$produto}
                                AND peca = {$peca}";
                    $res_lb = pg_query($con, $sql_lb);
                    if(pg_num_rows($res_lb)>0){
                        $qtde_lb    = pg_fetch_result($res_lb, 0, 'qtde');
                        if($qtde > $qtde_lb){
                            throw new Exception("Quantidade da peça  $referencia - $descricao  maior que a permitida na lista básica");
                        }
                    }else{
                        throw new Exception("Peça $referencia - $descricao não consta na lista básica do produto");
                    }
                }
            }
        }
    // }
}
$valida_qtde_lista_basica = "valida_qtde_lista_basica_rowa";

function valida_tipo_atendimento_rowa(){
    global $con, $campos, $login_fabrica, $msg_erro;

    if (valida_fora_garantia($campos['os']['tipo_atendimento']) === false) {
        if(strlen(trim($campos['os']['observacoes']))== 0){
            $msg_erro["msg"]["campo_obrigatorio"] = "Preencha todos os campos obrigatórios";
            $msg_erro["campos"][] = "os[observacoes]";
        }
    }    
}

function valida_nota_fiscal_rowa(){
    global $con, $campos, $login_fabrica, $msg_erro, $regras;

    if (valida_fora_garantia($campos['os']['tipo_atendimento']) != false) {        
        $regras["os|nota_fiscal"] = array(
            "obrigatorio" => false,
            "function" => array()
        );

        $regras["os|data_compra"] = array(
            "obrigatorio" => false,
            "function" => array()
        );
    }

}

//print_r($regras["os|nota_fiscal"]);


/*function verifica_estoque_peca_rowa(){

    global $login_fabrica, $campos, $os, $gravando;

    // if (valida_fora_garantia($campos['os']['tipo_atendimento']) == false) {

        $posto = ($areaAdmin === false) ? $login_posto : $campos["posto"]["id"];

        $Os = new \Posvenda\Os($login_fabrica);

        $status_posto_controla_estoque = $Os->postoControlaEstoque($posto);

        if($status_posto_controla_estoque == true){

            $pecas_pedido = $campos["produto_pecas"];
            $nota_fiscal  = $campos["os"]["nota_fiscal"];
            $data_nf      = $campos["os"]["data_compra"];

            if(!empty($data_nf)){
                list($dia, $mes, $ano) = explode("/", $data_nf);
                $data_nf = $ano."-".$mes."-".$dia;
            }

            foreach ($pecas_pedido as $pecas) {

                if(!empty($pecas["id"])){

                    $servico         = $pecas["servico_realizado"];
                    $peca            = $pecas["id"];
                    $peca_referencia = $pecas["referencia"];
                    $qtde            = $pecas["qtde"];
                    $os_item         = get_os_item($os, $peca);

                    $status_servico = $Os->verificaServicoUsaEstoque($servico);

                    if($status_servico == true){

                        $status_estoque = $Os->verificaEstoquePosto($posto, $peca, $qtde);

                        if($status_estoque == false && $gravando != null){

                            throw new Exception("O posto não tem estoque suficiente para a Peça {$peca_referencia}");

                        }else{

                            $Os->lancaMovimentoEstoque($posto, $peca, $qtde, $os, $os_item, $nota_fiscal, $data_nf, "saida");

                        }

                    } else if ($gravando != true) {

                        $status_exclusao = $Os->excluiMovimentacaoEstoque($posto, $peca, $os, $os_item);

                    }

                }

            }

        }
    // }

}*/

?>
