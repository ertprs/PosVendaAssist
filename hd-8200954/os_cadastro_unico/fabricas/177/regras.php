<?php

$regras["os|aparencia_produto"]["obrigatorio"] = true;
$regras["os|consumidor_revenda"]["obrigatorio"] = true;
$regras["consumidor|cpf"]["obrigatorio"] = true;
$regras["consumidor|cep"]["obrigatorio"] = true;
$regras["consumidor|estado"]["obrigatorio"] = true;
$regras["consumidor|cidade"]["obrigatorio"] = true;
$regras["consumidor|bairro"]["obrigatorio"] = true;
$regras["consumidor|endereco"]["obrigatorio"] = true;
$regras["consumidor|numero"]["obrigatorio"] = true;
$regras["consumidor|celular"]["obrigatorio"] = true;
$regras["consumidor|telefone"]["obrigatorio"] = true;
$regras["consumidor|email"]["obrigatorio"] = true;

if ($areaAdmin === false) {
    $regras["produto|causa_defeito"]["obrigatorio"] = false;
}

if (strlen(trim(getValue("consumidor[celular]"))) > 0 OR strlen(trim(getValue("consumidor[telefone]"))) > 0) {
    $regras["consumidor|telefone"]["obrigatorio"] = false;
    $regras["consumidor|celular"]["obrigatorio"] = false;
}

    $regras["revenda|nome"]["obrigatorio"] = true;
    $regras["revenda|cnpj"]["obrigatorio"] = true;
    $regras["revenda|cep"]["obrigatorio"] = true;
    $regras["revenda|estado"]["obrigatorio"] = true;
    $regras["revenda|cidade"]["obrigatorio"] = true;
    $regras["revenda|bairro"]["obrigatorio"] = true;
    $regras["revenda|endereco"]["obrigatorio"] = true;
    $regras["revenda|numero"]["obrigatorio"] = true;
    $regras["revenda|telefone"]["obrigatorio"] = true;

if (getValue("produto[produto_lote_hidden]") == 't'){
    $regras["produto|lote"]["obrigatorio"] = true;
}

$funcoes_fabrica = [
    "grava_orcamento_anauger",
];

$valida_anexo_boxuploader = "valida_anexo_boxuploader";

$id_orcamento = 0;
/* Resgata o ID do Tipo de Atendimento Orçamento */
function id_tipo_atendimento_orcamento(){

    global $con, $login_fabrica;

    $sql = "SELECT tipo_atendimento FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND descricao ILIKE 'Or%amento'";
    $res = pg_query($con, $sql);

    return (pg_num_rows($res) > 0) ? pg_fetch_result($res, 0, "tipo_atendimento") : 0;

}

/* Verifica se o posto é do tipo Posto */
function posto_interno($posto_param = ""){

    global $con, $login_fabrica, $campos, $login_posto;

    if(strlen($posto_param) > 0){
        $posto = $posto_param;
    }else{
        $posto = (strlen($campos["posto"]["id"]) > 0) ? $campos["posto"]["id"] : $login_posto;
    }

    if($areaAdmin == true){
        return false;
    }

    $sql = "SELECT
                tbl_tipo_posto.posto_interno
            FROM tbl_posto_fabrica
            INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto
            WHERE
                tbl_posto_fabrica.fabrica = {$login_fabrica}
                AND tbl_posto_fabrica.posto = {$posto}";
    $res = pg_query($con, $sql);

    $posto_interno = pg_fetch_result($res, 0, "posto_interno");

    return ($posto_interno == "t") ? true : false;

}

function grava_orcamento_anauger() {
    global $con, $login_fabrica, $campos, $msg_erro, $os;

    if (tipo_atendimento_orcamento()) {
        foreach ($campos['produto_pecas'] as $key => $pecas) {

            $os_item = (empty($pecas['os_item'])) ? $pecas['os_item_insert'] : $pecas['os_item'];

            $valor_peca  = str_replace(".", "",  $pecas['valor']);
            $valor_peca  = str_replace(",", ".", $valor_peca);

            if (!empty($pecas['id']) AND !empty($valor_peca)) {

                $sql = "UPDATE tbl_os_item SET preco = {$valor_peca}
                        WHERE os_item = {$os_item}";
                $res = pg_query($con, $sql);
            }
        }
        $mo_adicional     = trim($campos["os"]["valor_total"]);
        $mo_adicional     = str_replace(".", "",  $mo_adicional);
        $mo_adicional     = str_replace(",", ".", $mo_adicional);
        $mo_adicional     = number_format($campos["os"]["valor_adicional_mo"], 2);

        // $desconto     = trim($campos["os"]["desconto"]);
        // $desconto     = str_replace(".", "",  $desconto);
        // $desconto     = str_replace(",", ".", $desconto);
        // $desconto     = number_format($campos["os"]["desconto"], 2);
        if (!empty($mo_adicional)){
            pg_query($con, "UPDATE tbl_os_extra SET mao_de_obra_adicional = {$mo_adicional} WHERE os = {$os}");
        }
        
        $descricao_status_orcamento = $campos["os"]["orcamento_status"];

        $sql_status = "SELECT status_os FROM tbl_status_os WHERE UPPER(fn_retira_especiais(descricao)) = UPPER(fn_retira_especiais(trim('{$descricao_status_orcamento}')))";
        $res_status = pg_query($con, $sql_status);

        if(pg_num_rows($res_status) > 0){
            
            $id_status_os = pg_fetch_result($res_status, 0, 'status_os');
            pg_query($con, "UPDATE tbl_os SET status_os_ultimo = {$id_status_os} WHERE os = {$os}");
        }
    }
}

/* Insere o ID do tipo atendimento Orçamento para o posto interno */
if(posto_interno() == true || $areaAdmin == true){

    $id_orcamento = id_tipo_atendimento_orcamento();

}


function grava_os_produto_fabrica()
{
    global $campos;

    if (!empty($campos["produto"]["causa_defeito"])){
        return array(
            "causa_defeito" => $campos["produto"]["causa_defeito"]
        );
    }
}

/* Verifica se o Tipo de Atendimento é ORÇAMENTO */
function tipo_atendimento_orcamento(){
    global $con, $login_fabrica, $os, $campos;
    $posto = $campos["posto"]["id"];
    $tipo_atendimento = $campos["os"]["tipo_atendimento"];

    $sql = "SELECT tipo_atendimento
        FROM tbl_tipo_atendimento
        WHERE fabrica = {$login_fabrica}
        AND tipo_atendimento = {$tipo_atendimento}
        AND grupo_atendimento = 'S' ";
    $res = pg_query($con, $sql);
    return (pg_num_rows($res) > 0) ? true : false;

}

$valida_garantia = "valida_garantia_anauger";
function valida_garantia_anauger($boolean = false) {
    global $con, $login_fabrica, $campos, $msg_erro;
    
    $data_compra   = $campos["os"]["data_compra"];
    $data_abertura = $campos["os"]["data_abertura"];
    $produto       = $campos["produto"]["id"];

    $produto_lote  = $campos["produto"]["lote"];
    $produto_pecas = $campos["produto_pecas"];

    $pecas_lotes = array();
    
    foreach ($produto_pecas as $key => $value) {
        if (empty($value["id"])){
            continue;
        }

        if (!empty($value["lote"]) AND !empty($produto_lote)){
            $pecas_lotes[] = array(
                "id" => $value["id"],
                "lote" => $value["lote"],
                "posicao" => $key,
                "referencia" => $value["referencia"],
                "descricao"  => $value["descricao"]
            );
        }
    }
    
    if (!empty($produto_lote) AND count($pecas_lotes)){
        foreach ($pecas_lotes as $key => $value) {
            $peca       = $value["id"];
            $lote_nova  = $value["lote"];
            $referencia = $value["referencia"];
            $descricao  = $value["descricao"];
            $posicao    = $value["posicao"];

            $sql = "SELECT 
                        tbl_os.os,
                        tbl_os.data_abertura,
                        json_field('caneca',tbl_peca.parametros_adicionais) AS caneca
                    FROM tbl_os
                    JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                    JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
                    WHERE tbl_os.fabrica = {$login_fabrica}
                    AND tbl_os.produto = {$produto}
                    AND tbl_os.codigo_fabricacao = '{$produto_lote}'
                    AND tbl_os_item.peca = $peca
                    AND tbl_os_item.peca_serie = '$lote_nova'";
            $res = pg_query($con, $sql);
            if (pg_num_rows($res) > 0){
                $data_abertura = pg_fetch_result($res, 0, 'data_abertura');
                $caneca = pg_fetch_result($res, 0, 'caneca');

                if ($caneca == "t"){
                    $meses = 3;
                }else{
                    $meses = 6;
                }

                if (strtotime(formata_data($data_abertura)." + $meses months") < strtotime(date('Y-m-d'))) {
                    $msg_erro["msg"][] = traduz("Peça $referencia - $descricao fora de garantia");
                    $msg_erro["campos"][] = "produto_pecas[{$posicao}]";
                }
            }else{
                if (!empty($produto) && !empty($data_compra) && !empty($data_abertura)) {
                    $sql = "SELECT garantia FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto}";
                    $res = pg_query($con, $sql);

                    if (pg_num_rows($res) > 0) {
                        $garantia = pg_fetch_result($res, 0, "garantia");

                        if (strtotime(formata_data($data_compra)." +{$garantia} months") < strtotime(formata_data($data_abertura))) {
                            if ($boolean == false) {
                                $msg_erro["msg"][] = traduz("Produto fora de garantia");
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
    }else{
        if (!empty($produto) && !empty($data_compra) && !empty($data_abertura)) {
            $sql = "SELECT garantia FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto}";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0) {
                $garantia = pg_fetch_result($res, 0, "garantia");

                if (strtotime(formata_data($data_compra)." +{$garantia} months") < strtotime(formata_data($data_abertura))) {
                    if ($boolean == false) {
                        $msg_erro["msg"][] = traduz("Produto fora de garantia");
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

$antes_valida_campos = "antes_valida_campos";
function antes_valida_campos() {
    global $campos, $os, $con, $login_fabrica, $valida_garantia, $regras, $msg_erro, $login_posto_interno, $regras_pecas;

    if (tipo_atendimento_orcamento() === true){
        $regras["os|nota_fiscal"]["obrigatorio"] = false;
        $regras["os|data_compra"]["obrigatorio"] = false;
        $regras["os|aparencia_produto"]["obrigatorio"] = false;

        $regras["revenda|nome"]["obrigatorio"] = false;
        $regras["revenda|cnpj"]["obrigatorio"] = false;
        $regras["revenda|cep"]["obrigatorio"] = false;
        $regras["revenda|estado"]["obrigatorio"] = false;
        $regras["revenda|cidade"]["obrigatorio"] = false;
        $regras["revenda|bairro"]["obrigatorio"] = false;
        $regras["revenda|endereco"]["obrigatorio"] = false;
        $regras["revenda|numero"]["obrigatorio"] = false;
        $regras["revenda|complemento"]["obrigatorio"] = false;
        $regras["revenda|telefone"]["obrigatorio"] = false;
        $regras["os|orcamento_status"]["obrigatorio"] = true;
        $regras_pecas["servico_realizado"] = false;
    }



    $array_pecas            = $campos["produto_pecas"];
    foreach ($array_pecas as $key => $value) {
        $peca                   = $value["id"];
        $referencia             = $value["referencia"];
        $qtde_disparos_pecas    = $value["quantidade_disparos"];
        $numero_serie           = $value["numero_serie"];

        $peca_lote              = $value["peca_lote"];
        $lote_peca              = $value["lote"];

        if (empty($peca)){
            continue;
        }

        if ($peca_lote == "t" AND empty($lote_peca)){
            $msg_erro["msg"][] = traduz("Preencha o campo lote da peça")." ".$referencia;
            $msg_erro["campos"][] = "produto_pecas[$key]";
        }
    }
}

function grava_os_fabrica() {
    global $campos;
    
    return array(
        "codigo_fabricacao" => (!empty($campos["produto"]["lote"])) ? $campos["produto"]["lote"] : "null"
    );
}

$anexos_obrigatorios = ["notafiscal","foto_frontal","foto_traseira","interior_caneca"];
/**
 * Função para validar anexo
 */
function valida_anexo_anauger() {
    global $campos, $msg_erro, $fabricaFileUploadOS, $os, $con, $login_fabrica, $anexos_obrigatorios;

    if (empty($campos['produto_pecas'][0]['id'])) {

        $anexos_obrigatorios = ["notafiscal"];

    }
}

function grava_custo_peca() {

    global $campos;

    $pecas_valor = array();

    foreach($campos["produto_pecas"] as $key => $peca) {
        $valor                    = $peca["valor_total"];
        $pecas_valor[$peca['id']] = $valor;
    }

    return $pecas_valor;

}

$valida_anexo = "valida_anexo_anauger";

$grava_os_item_function = "grava_os_item_anauger";
function grava_os_item_anauger($os_produto, $subproduto = "produto_pecas") {
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
    
    foreach ($campos[$subproduto] as $posicao => $campos_peca) {
        if (strlen($campos_peca["id"]) > 0) {

            if($historico_alteracao === true){
                include "$login_fabrica/historico_alteracao.php";
            }
            
            if (!empty($campos_peca['servico_realizado'])){
                $sql = "SELECT troca_de_peca FROM tbl_servico_realizado WHERE fabrica = {$login_fabrica} AND servico_realizado = {$campos_peca['servico_realizado']}";
                $res = pg_query($con, $sql);

                $troca_de_peca = pg_fetch_result($res, 0, "troca_de_peca");
            }
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

            if(empty($custo_peca[$campos_peca['id']])) $custo_peca[$campos_peca['id']] = 0 ;
            if(empty($campos_peca['valor'])) $campos_peca['valor'] = 0 ;
            
            if (tipo_atendimento_orcamento() === true){
                $campos_peca['servico_realizado'] = "NULL";
            }else{
                $campos_peca['servico_realizado'] = $campos_peca['servico_realizado'];
            }
            
            /** 
             * @author William Castro <william.castro@telecontrol.com.br>
             * hd-6586393 | Mudar status da OS para Aguardando Peças 
             * 
            */
            
            $servico_realizado = $campos_peca['servico_realizado'];

            if ($servico_realizado == 11348) { 

                atualiza_status_checkpoint($os, "Aguardando Peças", $login_fabrica);
                
            } 

            $campos_peca['valor'] = str_replace(".", "", $campos_peca['valor']);
            $custo_peca[$campos_peca['id']] = str_replace(".", "",  $custo_peca[$campos_peca['id']]);

            if (empty($campos_peca["os_item"])) {
                $sql = "INSERT INTO tbl_os_item (
                            os_produto,
                            peca,
                            qtde,
                            servico_realizado,
                            peca_obrigatoria,
                            admin
                            ".((isset($custo_peca)) ? ", custo_peca" : "")."
                            ".((isset($campos_peca['valor'])) ? ", preco" : "")."
                            ".(($grava_defeito_peca == true) ? ", defeito" : "")."
                            ".((!empty($campos_peca['lote'])) ? ", peca_serie_trocada" : "")."
                        ) VALUES (
                            {$os_produto},
                            {$campos_peca['id']},
                            {$campos_peca['qtde']},
                            {$campos_peca['servico_realizado']},
                            {$devolucao_obrigatoria},
                            {$login_admin}
                            ".((isset($custo_peca)) ? ", '".str_replace(',','.',$custo_peca[$campos_peca['id']])."'" : "")."
                            ".((isset($campos_peca['valor'])) ? ", '".str_replace(',','.',$campos_peca['valor'])."'" : "")."
                            ".(($grava_defeito_peca == true) ? ", ".$campos_peca['defeito_peca'] : "")."
                            ".((!empty($campos_peca['lote'])) ? ", '".$campos_peca['lote']."'" : "")."
                        ) RETURNING os_item";
                $acao = "insert";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao gravar Ordem de Serviço #9");
                }
                $campos[$subproduto][$posicao]["os_item_insert"] = pg_fetch_result($res, 0, "os_item");
            } else {
                
                $sql = "
                    SELECT
                        tbl_os_item.os_item,
                        tbl_os_item.peca,
                        tbl_os_item.qtde,
                        tbl_os_item.servico_realizado,
                        tbl_os_item.pedido
                    FROM tbl_os_item
                    JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica}
                    WHERE tbl_os_item.os_produto = {$os_produto}
                    AND tbl_os_item.os_item = {$campos_peca['os_item']}
                    AND UPPER(tbl_servico_realizado.descricao) NOT IN ('CANCELADO', 'TROCA PRODUTO');
                ";

                $res = pg_query($con, $sql);

                if (pg_num_rows($res) > 0) {
                    $os_item_peca = pg_fetch_result($res, 0, "peca");
                    $os_item_qtde = pg_fetch_result($res, 0, "qtde");
                    $os_item_servico = pg_fetch_result($res, 0, "servico_realizado");
                    $os_item_pedido = pg_fetch_result($res, 0, "pedido");

                    if (!empty($os_item_pedido) && ($campos_peca['qtde'] != $os_item_qtde || $campos_peca['servico_realizado'] != $os_item_servico || $campos_peca['id'] != $os_item_peca)) {
                        continue;
                    }
                }

                if (verificaPecaCancelada($campos_peca["os_item"]) === true) {
                    continue;
                }

                if (verificaTrocaProduto($campos_peca["os_item"]) === true) {
                    continue;
                }

                $sql = "
                    UPDATE tbl_os_item SET
                        qtde = {$campos_peca['qtde']},
                        servico_realizado = {$campos_peca['servico_realizado']}
                        ".(($grava_defeito_peca == true) ? ", defeito = {$campos_peca['defeito_peca']}" : "")."
                        ".((isset($campos_peca['lote']))? ", peca_serie_trocada = '".$campos_peca['lote']."'" : "")."
                    WHERE os_produto = {$os_produto}
                    AND os_item = {$campos_peca['os_item']};
                ";

                $acao = "update";
                $res = pg_query($con, $sql);


                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao gravar Ordem de Serviço #10");
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

$auditorias = array(    
    "auditoria_peca_critica",
    "auditoria_troca_obrigatoria",    
    "auditoria_os_reincidente_anauger",
    "auditoria_analise_garantia_anauger",
);

function auditoria_os_reincidente_anauger() {    
    global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

	$consumidor_cpf = preg_replace("/\D/","",$campos['consumidor']['cpf']);	

    $posto = $campos['posto']['id'];

    $sql = "SELECT os 
    FROM tbl_os 
    WHERE fabrica = {$login_fabrica} 
    AND os = {$os} 
    AND os_reincidente IS NOT TRUE";

    $res = pg_query($con, $sql);

    $condicao_auditoria_os_serie = \Posvenda\Regras::get("condicao_auditoria_produto_serie", "ordem_de_servico", $login_fabrica);
    $condicao_auditoria_os_serie = ($condicao_auditoria_os_serie) ? " AND tbl_os.serie = '".$campos['produto']['serie']."'" : '';

    if(pg_num_rows($res) > 0){

        $sql = "SELECT tbl_os.os, tbl_produto.garantia
                FROM tbl_os
                INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                INNER JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto
                WHERE tbl_os.fabrica = {$login_fabrica}                    
                AND tbl_os.excluida IS NOT TRUE
                AND tbl_os.posto = $posto
                AND tbl_os.os < {$os}
                AND tbl_os.nota_fiscal = '{$campos['os']['nota_fiscal']}'                    
                AND tbl_os_produto.produto = {$campos['produto']['id']}
                AND tbl_os.consumidor_cpf = '$consumidor_cpf'
                AND tbl_os.data_abertura BETWEEN  data_nf and data_nf + CAST(tbl_produto.garantia || ' months' AS INTERVAL)  
                $condicao_auditoria_os_serie
                ORDER BY tbl_os.data_abertura DESC
                LIMIT 1";

        $resSelect = pg_query($con, $sql);

        if (pg_num_rows($resSelect) > 0 && verifica_auditoria_unica("tbl_auditoria_status.reincidente = 't'", $os) === true) {
            $os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");
            
            if (verifica_os_reincidente_finalizada($os_reincidente_numero)) {
                $busca = buscaAuditoria("tbl_auditoria_status.reincidente = 't'");

                if($busca['resultado']){
                    $auditoria_status = $busca['auditoria'];
                }

                $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
                        ({$os}, $auditoria_status, 'OS Reincidente por CNPJ, NOTA FISCAL, PRODUTO')";
                        

                $resInsert = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao lançar ordem de serviço");
                } else {
                    $os_reincidente = true;
                }
            }
        }
    }
}

function auditoria_analise_garantia_anauger(){
    global $con, $login_fabrica, $os, $campos;
    $sql = "
        SELECT
            tbl_os_item.os_item
        FROM tbl_os_item
        JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca
        JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
        JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
        WHERE tbl_os.fabrica = {$login_fabrica}
        AND tbl_os_produto.os = {$os} ";
    $res = pg_query($con, $sql);
    
    if (pg_num_rows($res) > 0 && verifica_auditoria_unica(" tbl_auditoria_status.fabricante = 't' AND fn_retira_especiais(tbl_auditoria_os.observacao) ILIKE '%DE ANALISE DA GARANTIA%'", $os) === true && tipo_atendimento_orcamento() === false) {
        $busca = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

        if($busca['resultado']){
            $auditoria_status = $busca['auditoria'];
        }

        $sql = "
            INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao)
            VALUES ({$os}, {$auditoria_status}, 'AUDITORIA DE ANÁLISE DA GARANTIA');
        ";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao lançar ordem de serviço #AUD001");
        }
    }
}
