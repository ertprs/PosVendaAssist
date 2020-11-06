<?php

$regras["os|status_orcamento"] = array(
    "obrigatorio" => false,
    "function" => array("valida_status_orcamento")
);
$regras["consumidor|cpf"]["obrigatorio"]           = true;

$regras["os|defeito_reclamado_descricao"]["obrigatorio"] = false;
$regras["revenda|nome"]["obrigatorio"]   = false;
$regras["revenda|cnpj"]["obrigatorio"]   = false;
$regras["revenda|cnpj"]["function"]      = [];
$regras["revenda|estado"]["obrigatorio"] = false;
$regras["revenda|cidade"]["obrigatorio"] = false;
$regras["os|data_compra"]["obrigatorio"] = false;
$regras["os|nota_fiscal"]["obrigatorio"]   = false;

if (strlen(getValue("os[nota_fiscal]")) > 0) {
    $regras["os|data_compra"]["obrigatorio"] = true;
}
if (in_array(verifica_tipo_atendimento(), ["Cortesia com deslocamento", "Garantia com deslocamento"])) {
    $regras["produto|id"]["obrigatorio"] = false;
    $regras["produto|referencia"]["obrigatorio"] = false;
    $regras["produto|descricao"]["obrigatorio"] = false;
    $regras["produto|defeito_constatado"]["obrigatorio"] = false;
} 

if(verifica_tipo_atendimento() == "Orçamento"){
    $regras["produto|defeito_constatado"]["obrigatorio"]   = false;
    $regras["produto|serie"]["obrigatorio"] = false;
    $regras["os|defeito_reclamado"]["obrigatorio"] = false;
    $regras_pecas["servico_realizado"] = false;
    $regras["produto|serie"] = array(
        "function" => array()
    );
    $valida_garantia = "";
    $auditorias = array(
        "auditoria_troca_syllent",
    );
} else {
    $regras["produto|defeito_constatado"]["function"] = [];
   // $regras["produto|defeito_constatado"]["function"] = array("valida_defeito_constatado_syllent");

    $auditorias = array(
        "auditoria_fabrica_syllent",
        "auditoria_troca_syllent",
        "auditoria_os_reincidente_syllent",
        "auditoria_km_syllent",
        "auditoria_valores_adicionais",
    );

}
$valida_anexo = "";

if (in_array(verifica_tipo_atendimento(), ["Garantia Balcão", "Garantia com deslocamento"])) {
    $valida_garantia = "valida_garantia_syllent";
}

$antes_valida_campos = "antes_valida_campos_syllent";

function antes_valida_campos_syllent() {
    global $con, $login_fabrica, $login_posto, $areaAdmin, $campos, $msg_erro;
    if (in_array(verifica_tipo_atendimento(), ["Cortesia com deslocamento", "Garantia com deslocamento"]) && strlen($campos['produto']['id']) == 0) {
        $sql = "SELECT * FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND ativo IS TRUE AND referencia='SEMPRODUTO'";
        $query = pg_query($con, $sql);
        if (pg_num_rows($query) > 0) {
            $campos['produto']['id'] = pg_fetch_result($query, 0, 'produto');
            $campos['produto']['referencia'] = pg_fetch_result($query, 0, 'referencia');
            $campos['produto']['descricao'] = pg_fetch_result($query, 0, 'descricao');
        }
    }
}

if (verifica_tipo_atendimento() == 'Orçamento'){
    $funcoes_envia_email = ["envia_email_consumidor"];
} else {
    $funcoes_fabrica = ["verifica_estoque_peca_syllent"];
}

if (in_array(verifica_tipo_atendimento(), ["Cortesia com deslocamento", "Garantia com deslocamento"]) && empty($os)) {
    $funcoes_fabrica = ["grava_visita_syllent"];

}
function grava_visita_syllent() {
    global $con, $login_fabrica, $campos, $os, $areaAdmin;
    
    $periodo                    = $campos["os"]["periodo_visita"];
    $tecnico                    = $campos["os"]["tecnico"];
    $data_agendamento           = $campos["os"]["data_agendamento"]; 

    list($d,$m,$y) = explode("/", $data_agendamento);

    if(!checkdate($m,$d,$y)){
        throw new Exception("Data de agendamento Invalida");
    }else{
        $xdata_agendamento = "$y-$m-$d";

        $countAgenda = "SELECT COUNT(*) FROM tbl_tecnico_agenda WHERE fabrica = {$login_fabrica} AND os = {$os};";
        $resCountAgenda = pg_query($con,$countAgenda);

        $ordem = pg_fetch_result($resCountAgenda, 0, 0);
        $ordem += 1;

        $sqlAgenda = "
            INSERT INTO tbl_tecnico_agenda (
                                            tecnico,
                                            fabrica,
                                            os,
                                            data_agendamento,
                                            ordem,
                                            periodo,
                                            confirmado
                                        ) VALUES (
                                            {$tecnico},
                                            {$login_fabrica},
                                            {$os},
                                            '{$xdata_agendamento}',
                                            $ordem, 
                                            '$periodo',
                                            now()
                                        );
        ";

        $res = pg_query($con,$sqlAgenda);

        if (pg_last_error()) {
            throw new Exception("Erro ao gravar agendamento");
        }
    }
}



function verifica_estoque_peca_syllent(){

    global $login_fabrica, $campos, $os, $gravando , $con;

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
                    $sqlEstoque = "
                        SELECT qtde_saida FROM tbl_estoque_posto_movimento WHERE os_item = {$os_item}
                    ";
                    $resEstoque = pg_query($con, $sqlEstoque);

                    if (pg_num_rows($resEstoque) > 0) {
                        $qtde_saida = pg_fetch_result($resEstoque, 0, "qtde_saida");

                        $diferenca = $qtde - $qtde_saida;

                        if ($diferenca != 0) {
                            $$Os->excluiMovimentacaoEstoque($posto, $peca, $os, $os_item, $con);

                            $status_estoque = $Os->verificaEstoquePosto($posto, $peca, $qtde, $con);

                            if($status_estoque == false){
                                $novo_servico_realizado = buscaServicoRealizadoSyllent("gera_pedido");
                                $sql = "UPDATE tbl_os_item SET servico_realizado = {$novo_servico_realizado} WHERE os_item = {$os_item}";
                                $res = pg_query($con, $sql);
                            }else{
                                $Os->lancaMovimentoEstoque($posto, $peca, $qtde, $os, $os_item, $nota_fiscal, $data_nf, "saida", $con);
                            }
                        }
                    } else {
                        $status_estoque = $Os->verificaEstoquePosto($posto, $peca, $qtde, $con);

                        if(!$status_estoque){
                            $novo_servico_realizado = buscaServicoRealizadoSyllent("gera_pedido");
                    
                            if(!empty($novo_servico_realizado)){
                                $sql = "UPDATE tbl_os_item SET servico_realizado = {$novo_servico_realizado} WHERE os_item = {$os_item}";
                                $res = pg_query($con, $sql);
                            }else{
                                throw new Exception("O posto não tem estoque suficiente para a Peça {$peca_referencia}");
                            }
                        }else{
                            $Os->lancaMovimentoEstoque($posto, $peca, $qtde, $os, $os_item, $nota_fiscal, $data_nf, "saida", $con);
                        }
                    }
                } else {
                    $status_exclusao = $Os->excluiMovimentacaoEstoque($posto, $peca, $os, $os_item, $con);

                    $status_servico = $Os->verificaServicoGeraPedido($servico);

                    if($status_servico == true){

                     $status_estoque = $Os->verificaEstoquePosto($posto, $peca, $qtde, $con);

                     if($status_estoque == true){

                        $novo_servico_realizado = buscaServicoRealizadoSyllent("estoque");

                        if(!empty($novo_servico_realizado)){
                            $Os->lancaMovimentoEstoque($posto, $peca, $qtde, $os, $os_item, $nota_fiscal, $data_nf, "saida", $con);
                            $sql = "UPDATE tbl_os_item SET servico_realizado = {$novo_servico_realizado} WHERE os_item = {$os_item}";
                            $res = pg_query($con, $sql);
                        }
                     }

                    }
                }

            }

        }

    }

}

function buscaServicoRealizadoSyllent($tipo) {
    global $login_fabrica, $con;

    switch($tipo){
    
        case "gera_pedido"   :  $cond = " AND troca_de_peca IS TRUE AND peca_estoque IS NOT TRUE"; break;
        case "estoque"       :  $cond = " AND troca_de_peca IS TRUE AND peca_estoque IS TRUE"; break;
        case "troca_produto" :  $cond = " AND troca_produto IS TRUE"; break;

    }

    $sql = "SELECT servico_realizado FROM tbl_servico_realizado WHERE fabrica = {$login_fabrica} AND ativo IS TRUE AND gera_pedido IS TRUE $cond";
    $query = pg_query($con, $sql);
    $res = pg_fetch_all($query);
    return (is_array($res) && count($res) > 0) ? $res[0]['servico_realizado'] : false;
}


function grava_os_fabrica(){

    global $campos, $con;

    $campos_bd = array();
    $horimetro = $campos["os"]["horimetro"];
    $horimetro = (strlen($campos["os"]["horimetro"]) == 0) ? "null" : $horimetro;
    $campos_bd["qtde_hora"] = $horimetro;

    $descricao_status_orcamento = $campos["os"]["status_orcamento"];
    $sql_status = "SELECT status_os FROM tbl_status_os WHERE UPPER(fn_retira_especiais(descricao)) = UPPER(fn_retira_especiais(trim('{$descricao_status_orcamento}')))";
    $res_status = pg_query($con, $sql_status);

    if(pg_num_rows($res_status) > 0){
        $id_status_os = pg_fetch_result($res_status, 0, 'status_os');
        $campos_bd["status_os_ultimo"] = $id_status_os;
    }
    return $campos_bd;

}

function grava_os_extra_fabrica() {
   
    global $con, $campos, $os, $login_fabrica;

    $tipo_atendimento = $campos["os"]["tipo_atendimento"];
    $tipo_de_os       = $campos["os"]["consumidor_revenda"];
    if (strlen($campos['produto']['data_fabricacao']) > 0) {
        $retorno["data_fabricacao"] = "'".geraDataBDD($campos['produto']['data_fabricacao'])."'";
    }

    if (!empty($tipo_atendimento)) {
        $sql = "SELECT descricao FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento = {$tipo_atendimento}";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res) > 0){
            $descricao = pg_fetch_result($res_status, 0, 'descricao');

            if (verifica_tipo_atendimento() == "Orçamento"){

                $mo_adicional = str_replace(",",".",str_replace(".","", $campos["os"]["valor_adicional_mo"]));

                if (!empty($mo_adicional)){
                    $retorno["mao_de_obra_adicional"] = $mo_adicional;
                }
            }
        }
    } 

    return $retorno;
}

function geraDataBDD($data){
    list($dia,$mes,$ano) = explode("/", $data);
    return "$ano-$mes-$dia";
}
function grava_os_campo_extra_fabrica() {
    global $campos;

    $return = array();

    if (isset($campos["produto"]["solicita_troca_antecipada"]) && strlen($campos["produto"]["solicita_troca_antecipada"]) > 0) {
        $return["solicita_troca_antecipada"] = $campos["produto"]["solicita_troca_antecipada"];
    }

    if (isset($campos["produto"]["solicita_troca_antecipada_orcamento"]) && strlen($campos["solicita_troca_antecipada_orcamento"]) > 0) {
        $return["solicita_troca_antecipada_orcamento"] = $campos["produto"]["solicita_troca_antecipada_orcamento"];
    }

    return $return;
}


function valida_status_orcamento() {
    global $campos, $msg_erro, $con, $login_fabrica;

    $tipo_atendimento = $campos["os"]["tipo_atendimento"];
    $status_orcamento = $campos["os"]["status_orcamento"];

    $sql = "SELECT descricao FROM tbl_tipo_atendimento WHERE fabrica = $login_fabrica AND tipo_atendimento = $tipo_atendimento ";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){
        $descricao = pg_fetch_result($res, 0, 'descricao');

        if($descricao == "Orçamento" AND strlen(trim($status_orcamento)) == 0){
            $msg_erro["msg"][]    = "É obrigatório informar status do Orçamento";
            $msg_erro["campos"][] = "os[status_orcamento]";
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
                    ({$os}, $auditoria_status, 'OS em auditoria de Valores Adicionais', true)";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao lançar ordem de serviço");
            }
        }
    }
}

function auditoria_km_syllent(){
    global $con, $os, $login_fabrica, $campos;
    if (!strlen($campos["os"]["qtde_km_hidden"])) {
        $campos["os"]["qtde_km_hidden"] = $campos["os"]["qtde_km"];
    }
    $qtde_km = trim($campos["os"]["qtde_km"]);
    $qtde_km_anterior = trim($campos["os"]["qtde_km_hidden"]);

    $busca = buscaAuditoria("tbl_auditoria_status.km = 't'");
    if($busca['resultado']){
        $auditoria_status = $busca['auditoria'];
    }
    $sql = "
        SELECT o.os
        FROM tbl_os o
        JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = {$login_fabrica}
        WHERE o.fabrica = {$login_fabrica}
        AND o.os = {$os};
    ";
    $res = pg_query($con,$sql);

    if (pg_num_rows($res) > 0) {


        if (verifica_auditoria_unica("tbl_auditoria_status.km = 't' AND tbl_auditoria_os.observacao ILIKE '%auditoria de KM%'", $os) === true) {

            if ($qtde_km >= 100) {

                $sql = "
                    INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                    VALUES ({$os}, {$auditoria_status}, 'OS em auditoria de KM', false);
                ";
                $res = pg_query($con, $sql);
                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao lançar ordem de serviço #AUD012");
                }
            } elseif ($qtde_km > $qtde_km_anterior) {
                $sql = "
                    INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                    VALUES ({$os}, {$auditoria_status}, 'KM alterado manualmente de $qtde_km_anterior para $qtde_km', false);
                ";
                $res = pg_query($con, $sql);
                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao lançar ordem de serviço #AUD012");
                }
            }
        } else {

            if ($qtde_km > $qtde_km_anterior) {
                $sql = "
                    INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                    VALUES ({$os}, {$auditoria_status}, 'KM alterado manualmente de $qtde_km_anterior para $qtde_km', false);
                    ";
                $res = pg_query($con, $sql);
                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao lançar ordem de serviço #AUD012");
                }
            }
        }
    }
}

function auditoria_troca_syllent() {
    global $con, $login_fabrica, $os, $campos;
    
    $titulo_auditoria = "";

    if (isset($campos["produto"]["solicita_troca_antecipada"]) && strlen($campos["produto"]["solicita_troca_antecipada"]) > 0) {

        if ($campos["produto"]["solicita_troca_antecipada"] == "troca_produto_antecipado") {
            $titulo_auditoria = "Troca de Produto Antecipado Garantia";
        }
        if ($campos["produto"]["solicita_troca_antecipada"] == "troca_produto") {
            $titulo_auditoria = "Troca de Produto Garantia";
        }

    }

    if (isset($campos["produto"]["solicita_troca_antecipada_orcamento"]) && strlen($campos["produto"]["solicita_troca_antecipada_orcamento"]) > 0) {

        if ($campos["produto"]["solicita_troca_antecipada_orcamento"] == "troca_produto_antecipada_orcamento") {
            $titulo_auditoria = "Troca de Produto Antecipado Orçamento";
        }
        if ($campos["produto"]["solicita_troca_antecipada_orcamento"] == "troca_produto_orcamento") {
            $titulo_auditoria = "Troca de Produto Orçamento";
        }

    }

    if (strlen($titulo_auditoria) > 0 && verifica_auditoria_unica(" tbl_auditoria_status.produto = 't' AND tbl_auditoria_os.observacao ILIKE '%Troca de Produto%'", $os) === true) {

        $busca = buscaAuditoria("tbl_auditoria_status.produto = 't'");

        if($busca['resultado']){
            $auditoria_status = $busca['auditoria'];
        }

        $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
            ({$os}, $auditoria_status, 'OS em auditoria de {$titulo_auditoria}')";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao lançar ordem de serviço");
        }

    }

}

function auditoria_fabrica_syllent(){
    global $con, $login_fabrica, $os, $campos;

    $sqlItens = "SELECT tbl_os.os 
              FROM tbl_os 
              JOIN tbl_os_produto using(os) 
              JOIN tbl_os_item using(os_produto) 
              JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica={$login_fabrica} AND tbl_servico_realizado.troca_de_peca IS true AND tbl_servico_realizado.ativo IS true
             WHERE tbl_os.os={$os}
               AND tbl_os.fabrica={$login_fabrica}";
    $resItens = pg_query($con, $sqlItens);


    if (pg_num_rows($resItens) > 0) {

        $auditoria = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

        if($auditoria['resultado']){
            $auditoria_status = $auditoria['auditoria'];
        }

        $sql = "SELECT tbl_auditoria_os.os,
                       tbl_auditoria_os.auditoria_os,
                       tbl_auditoria_os.liberada,
                       tbl_auditoria_os.reprovada
                  FROM tbl_auditoria_os
                  JOIN tbl_os ON tbl_os.os = tbl_auditoria_os.os AND tbl_os.fabrica = {$login_fabrica}
                 WHERE tbl_auditoria_os.os = {$os}
                   AND tbl_auditoria_os.auditoria_status = {$auditoria_status}
                   AND tbl_auditoria_os.observacao ILIKE '%Auditoria de F%'";
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) == 0) {
            $sql = "INSERT INTO tbl_auditoria_os (
                                                    os,
                                                    auditoria_status,
                                                    observacao
                                                ) VALUES
                                                (
                                                    {$os},
                                                    $auditoria_status,
                                                    'Auditoria de Fábrica: Peças'
                                                )";
            $res = pg_query($con, $sql);
            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao lançar ordem de serviço");
            }
        }
    }

}

function auditoria_os_reincidente_syllent() {
    global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

    $posto = $campos['posto']['id'];
    $sql = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os} AND os_reincidente IS NOT TRUE";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){

        $sql = "SELECT tbl_os.os
                FROM tbl_os
                INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                WHERE tbl_os.fabrica = {$login_fabrica}
                AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '90 days')
                AND tbl_os.excluida IS NOT TRUE
                AND tbl_os.posto = $posto
                AND tbl_os.os < {$os}
                AND tbl_os.nota_fiscal = '{$campos['os']['nota_fiscal']}'
                AND length(tbl_os.nota_fiscal) > 0
                AND tbl_os.consumidor_cpf = '".preg_replace("/[\.\-\/]/", "", $campos["consumidor"]["cnpjCpf"])."'
                AND length(tbl_os.consumidor_cpf) > 0
                AND tbl_os_produto.produto = {$campos['produto']['id']}
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
                        ({$os}, $auditoria_status, 'OS Reincidente por CPF, NOTA FISCAL, PRODUTO')";

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao lançar ordem de serviço");
                } else {
                    $os_reincidente = true;
                }
            }
        }
    }
}

function valida_defeito_constatado_syllent() {
    global $campos, $defeitoConstatadoMultiplo;

    if (isset($defeitoConstatadoMultiplo)) {
        $defeitos_constatados = explode(",", $campos["produto"]["defeitos_constatados_multiplos"]);
    } else {
        $defeitos_constatados = array($campos["produto"]["defeito_constatado"]);
    }

    $defeitos_constatados = array_filter($defeitos_constatados);

    if (count($defeitos_constatados) == 0) {
        throw new Exception("É necessário informar o defeito constatado");
    }
}

function verifica_tipo_atendimento() {
    global $con, $login_fabrica;

    if (getValue("os[tipo_atendimento]")) {
        $sqlTipo = "SELECT descricao 
                      FROM tbl_tipo_atendimento 
                     WHERE fabrica = {$login_fabrica} 
                       AND tipo_atendimento=".getValue("os[tipo_atendimento]");
        $resTipo = pg_query($con, $sqlTipo);
        $descricaoTipo = pg_fetch_result($resTipo, 0, 'descricao');
        return $descricaoTipo;
    }

}

function valida_garantia_syllent() {
    global $con, $login_fabrica, $campos, $msg_erro;

    $data_fabricacao   = $campos["produto"]["data_fabricacao"];
    $xdata_compra   = $campos["os"]["data_compra"];
    $data_abertura = $campos["os"]["data_abertura"];
    $produto       = $campos["produto"]["id"];

    if (strlen($xdata_compra) > 0 && strlen($data_fabricacao) > 0) {

        if (strtotime($xdata_compra) > strtotime($data_fabricacao)) {
            $data_compra   = $xdata_compra;
        } else {
            $data_compra   = $data_fabricacao;
        }

    } else if (strlen($xdata_compra) > 0 && strlen($data_fabricacao) == 0) {
        $data_compra   = $xdata_compra;
    }  else if (strlen($xdata_compra) == 0 && strlen($data_fabricacao) > 0) {
        $data_compra   = $data_fabricacao;
    }

    if (!empty($produto) && !empty($data_compra) && !empty($data_abertura)) {
        $sql = "SELECT garantia
                  FROM tbl_produto 
                 WHERE fabrica_i = {$login_fabrica} 
                   AND produto = {$produto}";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
         
            $garantia = pg_fetch_result($res, 0, "garantia");

            if (strtotime(formata_data($data_compra)." +{$garantia} months") < strtotime(formata_data($data_abertura))) {
                    $msg_erro["msg"][] = traduz("Produto fora de garantia");
            } 
        }
    }
}

$regras_pecas = array(
    "lista_basica" => false
);
$valida_qtde_lista_basica = "";

function envia_email_consumidor() {
    global $con, $login_fabrica, $campos, $os, $externalId, $_REQUEST;

    if ($campos["os"]["envia_orcamento_email"] == 't') {

        include __DIR__."/../../../class/communicator.class.php";

        $mailTc = new TcComm('smtp@posvenda');
        $sql = "select tbl_os.os,
                       tbl_os.sua_os,
                       tbl_os.consumidor_nome,
                       tbl_os.consumidor_email,
                       tbl_os.consumidor_cidade,
                       tbl_os.consumidor_estado,
                       tbl_os.consumidor_fone,
                       tbl_os.consumidor_cpf,
                       tbl_os.consumidor_revenda,
                       tbl_os.consumidor_endereco,
                       tbl_os.consumidor_numero,
                       tbl_os.consumidor_cep,
                       tbl_os.consumidor_complemento,
                       tbl_os.consumidor_bairro,
                       tbl_os.consumidor_celular,
                       tbl_os.consumidor_fone_comercial,
                       tbl_os.consumidor_nome_assinatura,
                       tbl_os.consumidor_fone_recado,
                       tbl_os.tipo_atendimento,
                       TO_CHAR(tbl_os.finalizada, 'DD/MM/YYYY HH24:MI:SS') AS finalizada,
                       TO_CHAR(tbl_os.data_digitacao, 'DD/MM/YYYY HH24:MI:SS') AS data_digitacao,
                       TO_CHAR(tbl_os.data_abertura, 'DD/MM/YYYY') AS data_abertura,
                       TO_CHAR(tbl_os.data_fechamento, 'DD/MM/YYYY') AS data_fechamento,
                       tbl_os.revenda_cnpj,
                       tbl_os.revenda_nome,
                       TO_CHAR(tbl_os.data_nf, 'DD/MM/YYYY') AS data_nf,
                       tbl_os.revenda_nome,
                       tbl_os.revenda_cnpj,
                       tbl_defeito_reclamado.descricao AS defeito_reclamado_nome,
                       tbl_defeito_constatado.descricao AS defeito_constatado_nome,
                       tbl_os.nota_fiscal,
                       tbl_produto.descricao AS nome_produto,
                       tbl_produto.referencia AS referencia_produto,
                       tbl_status_os.descricao AS status_orcamento,
                       tbl_os_extra.mao_de_obra_adicional,
                       tbl_tipo_atendimento.descricao AS tipo_atendimento_desc,
                       tbl_status_checkpoint.descricao AS status_da_os,
                       tbl_os_revenda.campos_extra AS revenda_campos_extra
                 FROM tbl_os 
                 JOIN tbl_os_produto USING(os)
                 JOIN tbl_os_extra USING(os)
                 JOIN tbl_status_checkpoint ON tbl_os.status_checkpoint = tbl_status_checkpoint.status_checkpoint
                 JOIN tbl_tipo_atendimento ON tbl_os.tipo_atendimento = tbl_tipo_atendimento.tipo_atendimento  AND tbl_tipo_atendimento.fabrica={$login_fabrica}
            LEFT JOIN tbl_defeito_reclamado ON tbl_defeito_reclamado.defeito_reclamado = tbl_os.defeito_reclamado AND tbl_defeito_reclamado.fabrica={$login_fabrica}
            LEFT JOIN tbl_defeito_constatado ON tbl_defeito_constatado.defeito_constatado = tbl_os.defeito_constatado AND tbl_defeito_constatado.fabrica={$login_fabrica}
            LEFT JOIN tbl_status_os ON tbl_os.status_os_ultimo = tbl_status_os.status_os
            LEFT JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i={$login_fabrica}
            LEFT JOIN tbl_os_revenda ON tbl_os_revenda.sua_os = SPLIT_PART(tbl_os.sua_os, '-', 1) AND tbl_os_revenda.fabrica = {$login_fabrica}
                WHERE tbl_os.os={$os}
                  AND tbl_os.fabrica={$login_fabrica};";
        $res = pg_query($con, $sql);
        if (pg_num_rows($res) > 0) {
            $xos = pg_fetch_result($res, 0, 'os');
            $consumidor_nome                = pg_fetch_result($res, 0, 'consumidor_nome');
            $consumidor_email               = pg_fetch_result($res, 0, 'consumidor_email');
            $consumidor_cidade              = pg_fetch_result($res, 0, 'consumidor_cidade');
            $consumidor_estado              = pg_fetch_result($res, 0, 'consumidor_estado');
            $consumidor_fone                = pg_fetch_result($res, 0, 'consumidor_fone');
            $consumidor_cpf                 = pg_fetch_result($res, 0, 'consumidor_cpf');
            $consumidor_revenda             = pg_fetch_result($res, 0, 'consumidor_revenda');
            $consumidor_endereco            = pg_fetch_result($res, 0, 'consumidor_endereco');
            $consumidor_numero              = pg_fetch_result($res, 0, 'consumidor_numero');
            $consumidor_cep                 = pg_fetch_result($res, 0, 'consumidor_cep');
            $consumidor_complemento         = pg_fetch_result($res, 0, 'consumidor_complemento');
            $consumidor_bairro              = pg_fetch_result($res, 0, 'consumidor_bairro');
            $consumidor_celular             = pg_fetch_result($res, 0, 'consumidor_celular');
            $consumidor_fone_comercial      = pg_fetch_result($res, 0, 'consumidor_fone_comercial');
            $consumidor_nome_assinatura     = pg_fetch_result($res, 0, 'consumidor_nome_assinatura');
            $consumidor_fone_recado         = pg_fetch_result($res, 0, 'consumidor_fone_recado');
            $tipo_atendimento               = pg_fetch_result($res, 0, 'tipo_atendimento_desc');
            $qtde_km                        = pg_fetch_result($res, 0, 'qtde_km');
            $data_abertura                  = pg_fetch_result($res, 0, 'data_abertura');
            $data_digitacao                 = pg_fetch_result($res, 0, 'data_digitacao');
            $data_fechamento                = pg_fetch_result($res, 0, 'data_fechamento');
            $data_nf                        = pg_fetch_result($res, 0, 'data_nf');
            $rev_nome                       = pg_fetch_result($res, 0, 'revenda_nome');
            $rev_cnpj                       = pg_fetch_result($res, 0, 'revenda_cnpj');
            $defeito_reclamado              = pg_fetch_result($res, 0, 'defeito_reclamado_nome');
            $defeito_constatado             = pg_fetch_result($res, 0, 'defeito_constatado_nome');
            $nf                             = pg_fetch_result($res, 0, 'nota_fiscal');
            $data_nf                        = pg_fetch_result($res, 0, 'data_nf');
            $finalizada                     = pg_fetch_result($res, 0, 'finalizada');
            $data_consertado                = pg_fetch_result($res, 0, 'data_consertado');
            $referencia_produto             = pg_fetch_result($res, 0, 'referencia_produto');
            $nome_produto                   = pg_fetch_result($res, 0, 'nome_produto');
            $numero_serie                   = pg_fetch_result($res, 0, 'numero_serie');
            $rg_produto                     = pg_fetch_result($res, 0, 'rg_produto');
            $status_orcamento               = pg_fetch_result($res, 0, 'status_orcamento');
            $status_da_os                   = pg_fetch_result($res, 0, 'status_da_os');
            $mao_de_obra_adicional          = pg_fetch_result($res, 0, 'mao_de_obra_adicional');
            $revenda_campos_extra           = json_decode(pg_fetch_result($res, 0, 'revenda_campos_extra'), true);
            $revenda_email                  = $revenda_campos_extra["revenda_email"];
            $tipo_atendimento               = $campos["os"]["tipo_atendimento"]; 
            $tipo_de_os                     = $campos["os"]["consumidor_revenda"];

            $status_os = $status_da_os;
            $status_oc = $status_orcamento ;
            $os_fabricante = "OS FABRICANTE";

            $xos2 = $xos;
   
            if (filter_var($consumidor_email, FILTER_VALIDATE_EMAIL)) {

                $assunto    = 'Syllent - Orçamento';

                $corpoMensagem = '
                <p>Prezado '.$consumidor_nome.'.</p>
                <p>Segue abaixo o orçamento para reparo do produto <b>'.$referencia_produto .' - '. $nome_produto.'</b>.</p><br>
                        <table width="700" border="0" cellspacing="1" cellpadding="0" style="border: 1px solid #d2e4fc;background-color: #485989;" align="center">
                            <tbody>
                                <tr>
                                    <td rowspan="4" style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" width="300">
                                        <center>
                                            '.$os_fabricante.'<br>&nbsp;
                                            <b><font size="6" color="#C67700">'.$xos2.'</font></b>
                                        </center>
                                    </td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;padding-right: 1px;text-transform: uppercase;background: #485989;text-align: center;padding: 1px 2px; margin: 0.0em 0.0em;color: #FFFFFF;" height="15" colspan="4">&nbsp;Datas da OS</td>
                                </tr>
                                <tr>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" width="100" height="15">Abertura &nbsp;</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" width="100" height="15">&nbsp;'.$data_abertura.'</td>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" width="100" height="15">Digitação </td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" width="100" height="15">&nbsp;'.$data_digitacao.'</td>
                                </tr>
                                <tr>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" width="100" height="15">Fechamento&nbsp;</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" width="100" height="15" id="data_fechamento">&nbsp;'.$data_fechamento.'</td>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" width="100" height="15">Finalizada </td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" width="100" height="15" id="finalizada">&nbsp;'.$finalizada.'</td>
                                </tr>
                                <tr>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Data da NF </td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'.$data_nf.'</td>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" width="100" height="15">Fechado em  </td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" id="fechado_em" width="100" height="15">&nbsp;'.$data_fechamento.'      </td>
                                </tr>
                                <tr>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;">
                                        <b></b><center><b>
                                            '.$status_os.'
                                        </b></center>
                                    </td>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" width="100" height="15">Consertado &nbsp; </td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" width="100" height="15" colspan="1" id="consertado">&nbsp;&nbsp;'.$data_consertado.'</td>                
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" width="100" height="15"></td>
                                </tr>
                            </tbody>
                        </table>

                        <table width="700" border="0" cellspacing="1" cellpadding="0" style="border: 1px solid #d2e4fc;background-color: #485989;" align="center">
                            <tbody>
                                <tr>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Tipo de Atendimento</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'.$tipo_atendimento.'</td>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" width="100">STATUS ORÇAMENTO</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15" nowrap="">&nbsp;'.$status_oc.'</td>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" width="100">Quantidade de KM</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15" nowrap="">&nbsp;'.$qtde_km.' KM</td>
                                </tr>
                            </tbody>
                        </table>

                        <table width="700" border="0" cellspacing="1" cellpadding="0" style="border: 1px solid #d2e4fc;background-color: #485989;" align="center">
                            <tbody>
                                <tr>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;padding-right: 1px;text-transform: uppercase;background: #485989;text-align: center;padding: 1px 2px; margin: 0.0em 0.0em;color: #FFFFFF;" height="15" colspan="100%">&nbsp;Informações do Produto   </td>
                                </tr>
                                <tr>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" width="90">Referência</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'.$referencia_produto.' </td>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" width="90">Descrição</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'.$nome_produto.'</td>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" width="90">N. de Série   &nbsp;</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">'.$numero_serie.'</td>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" width="90">RG Produto</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" nowrap="">'.$rg_produto.'</td>
                                </tr>
                           </tbody>
                        </table>

                        <table width="700" border="0" cellspacing="1" cellpadding="0" align="center" style="border: 1px solid #d2e4fc;background-color: #485989;">
                            <tbody>
                                <tr>
                                    <td height="15" style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;padding-right: 1px;text-transform: uppercase;background: #485989;text-align: center;padding: 1px 2px; margin: 0.0em 0.0em;color: #FFFFFF;" colspan="100%">&nbsp;Defeitos</td>
                                </tr>
                                <tr>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" width="90">Reclamado</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15" width="140">'.$defeito_reclamado.'</td>
                                </tr>
                                <tr>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Defeito Constatado</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;">&nbsp; '.$defeito_constatado.'</td>
                                </tr>
                            </tbody>
                        </table>

                        <table width="700" border="0" cellspacing="1" cellpadding="0" align="center" style="border: 1px solid #d2e4fc;background-color: #485989;">
                            <tbody>
                                <tr>
                                    <td  style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;padding-right: 1px;text-transform: uppercase;background: #485989;text-align: center;padding: 1px 2px; margin: 0.0em 0.0em;color: #FFFFFF;" colspan="100%" height="15">&nbsp;Informações sobre o consumidor </td>
                                </tr>
                                <tr>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Nome</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15" width="300">&nbsp;'.$consumidor_nome.'</td>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;">Telefone Residencial</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'.$consumidor_fone.'</td>
                                </tr>
                                <tr>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Celular</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15" width="300">&nbsp;'.$consumidor_celular.'</td>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;">Telefone Comercial</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'. $consumidor_fone_comercial .'</td>
                                </tr>
                                <tr>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" nowrap="">CPF Consumidor</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15"> &nbsp;'. $consumidor_cpf .'</td>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">CEP</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'. $consumidor_cep .'</td>
                                </tr>
                                <tr>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Endereço</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'. $consumidor_endereco .'</td>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Número</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'. $consumidor_numero .'</td>
                                </tr>
                                <tr>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Complemento</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'. $consumidor_complemento .'</td>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Bairro</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15" nowrap="">&nbsp;'. $consumidor_bairro .'</td>
                                </tr>
                                <tr>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;">Cidade</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;">&nbsp;'. $consumidor_cidade .'</td>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;">Estado</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;">&nbsp;'. $consumidor_estado .'</td>
                                </tr>
                               <tr>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;">E-Mail</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;">&nbsp;'. $consumidor_email .'</td>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;">&nbsp;</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;">&nbsp;</td>
                                </tr>
                            </tbody>
                        </table>

                        <table width="700" border="0" cellspacing="1" cellpadding="0" align="center" style="border: 1px solid #d2e4fc;background-color: #485989;">
                            <tbody>
                                <tr>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;padding-right: 1px;text-transform: uppercase;background: #485989;text-align: center;padding: 1px 2px; margin: 0.0em 0.0em;color: #FFFFFF;" colspan="100%" height="15">&nbsp;Informações Da Revenda</td>
                                </tr>
                                <tr>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Nome</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15" width="300" 1="">&nbsp;'.$rev_nome.'</td>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" width="80">CNPJ Revenda</td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'.$rev_cnpj.'</td>
                                </tr>
                                <tr>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">NF Número </td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'.$nf.'</td>
                                    <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Data da NF </td>
                                    <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">&nbsp;'.$data_nf.'</td>
                                </tr>
                            </tbody>
                        </table>

                        <table width="700" border="0" cellspacing="1" cellpadding="0" align="center" style="border: 1px solid #d2e4fc;background-color: #485989;">
                                <tbody>
                     <tr>
                                        <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;padding-right: 1px;text-transform: uppercase;background: #eed3d7;text-align: center;padding: 1px 2px; margin: 0.0em 0.0em;color: #b94a48;" colspan="100%" height="15">&nbsp;<b>Atenção:</b> O valor do pedido poderá ser alterado devido aos impostos calculados pela Fábrica</td>
                                    </tr>


                                    <tr>
                                        <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;padding-right: 1px;text-transform: uppercase;background: #485989;text-align: center;padding: 1px 2px; margin: 0.0em 0.0em;color: #FFFFFF;" colspan="100%" height="15">&nbsp;DIAGNÓSTICOS - COMPONENTES - MANUTENÇÕES EXECUTADAS</td>
                                    </tr>
                                    <tr>
                                        <td style="font-family: Arial;font-size: 7pt;text-align: left;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Componente</td>
                                        <td style="font-family: Arial;font-size: 7pt;text-align: center;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">QTD</td>
                                        <td style="font-family: Arial;font-size: 7pt;text-align: center;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">Preço unitário</td>
                                        <td style="font-family: Arial;font-size: 7pt;text-align: center;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15">PREÇO TOTAL</td>
                                    </tr>';
                            $sqlItens = "
                                SELECT tbl_os_item.peca,
                                    tbl_os_item.qtde,
                                    tbl_peca.referencia,
                                    tbl_peca.descricao,
                                    tbl_os_item.preco
                                FROM tbl_os
                                    JOIN tbl_os_produto USING(os)
                                    JOIN tbl_os_item USING(os_produto)
                                    JOIN tbl_peca USING(peca)
                                WHERE
                                    tbl_os.os = $xos
                                    AND tbl_os.fabrica = $login_fabrica;";

                            $resItens = pg_query($con,$sqlItens);

                            if (pg_num_rows($resItens) > 0) {
                                $total_pecas = [];
                                foreach (pg_fetch_all($resItens) as $key => $rows) {
                                $total_pecas[] = ($rows['qtde']*$rows['preco']);

                    $corpoMensagem .= '
                                    <tr>
                                        <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">'. $rows['referencia'] .' - '. $rows['descricao'] .'</td>
                                        <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: center;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">'. $rows['qtde'] .'</td>
                                        <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: center;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">R$ '. number_format($rows['preco'], 2, ',', '.') .'</td>
                                        <td style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: center;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">R$ '. number_format(($rows['qtde']*$rows['preco']), 2, ',', '.') .'</td>
                                    </tr>';
                                }
                            }
                    $corpoMensagem .= '
                                    <tr>
                                        <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" colspan="3">Valor de MÃO DE OBRA</td>
                                        <td  colspan="8" style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">R$ '. number_format($mao_de_obra_adicional, 2, ',', '.') .'</td>
                                    </tr>
                                    <tr>
                                        <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" colspan="3">Valor Total Peças</td>
                                        <td colspan="8" style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">R$ '. number_format(array_sum($total_pecas), 2, ',', '.') .'</td>
                                    </tr>
                                    <tr>
                                        <td style="font-family: Arial;font-size: 7pt;text-align: right;color: #000000;background: #ced7e7;padding-left: 5px;padding-right: 5px;text-transform: uppercase;" height="15" colspan="3">Valor total geral</td>
                                        <td colspan="8" style="font-family: Arial;font-size: 8pt;font-weight: bold;text-align: left;background: #F4F7FB;padding-left: 5px;padding-right: 5px;" height="15">R$ '. number_format((array_sum($total_pecas)+$mao_de_obra_adicional), 2, ',', '.') .'</td>
                                    </tr>';
                    $corpoMensagem .= '
                                </tbody>
                            </table>
                        <br>

                        <p>Estamos no aguardo da aprovação do orçamento para prosseguir com o reparo do produto.</p><br/>

                        Em caso de duvidas ficamos a disposição !!
                        <br>

                        <p>Atenciosamente</p>
                        <p>Syllent</p>
                        ';

                    $envio = $mailTc->sendMail(
                        $consumidor_email,
                        $assunto,
                        $corpoMensagem,
                        'sac@syllent.com.br'
                    );

                    return $envio;

            }
        }
    }
    
}



?>
