<?php
$auditorias = array(
                "auditoria_peca_critica",
                "auditoria_pecas_excedentes",
                "auditoria_troca_obrigatoria",
                "auditoria_os_reincidente_grohe",
                "auditoria_valor_adicional_grohe",
                "auditoria_km_grohe",
                "auditoria_peca_nao_cadastrada_grohe",
                "auditoria_troca_obrigatoria_grohe",
                "auditoria_pedido_peca_grohe",
                "auditoria_estoque_peca_grohe"
            );

$regras["os|nota_fiscal"]["obrigatorio"]      = false;
$regras["os|data_compra"]["obrigatorio"]      = false;
$valida_anexo_boxuploader = "valida_anexo_boxuploader";
$anexos_obrigatorios      = [];


if (getValue("os[contrato]") == "t") {
	$regras["os|nota_fiscal"]["obrigatorio"]      = true;
	$regras["os|data_compra"]["obrigatorio"]      = true;
}

if (!empty(getValue('os[qtde_km]')) && getValue('os[qtde_km]') != 0.00) {
    $regras["os|qtde_visita"]["obrigatorio"]  = true;
}

$regras["consumidor|cpf"]["obrigatorio"]      = true;
$regras["consumidor|cep"]["obrigatorio"]      = true;
$regras["consumidor|bairro"]["obrigatorio"]   = true;
$regras["consumidor|endereco"]["obrigatorio"] = true;
$regras["consumidor|numero"]["obrigatorio"]   = true;
$regras["consumidor|telefone"]["obrigatorio"] = true;
$regras["consumidor|celular"]["obrigatorio"]  = true;
$regras["consumidor|email"]["obrigatorio"]    = true;
unset($regras["produto|serie"]);

$regras["revenda|nome"]["obrigatorio"]   = false;
$regras["revenda|cnpj"]["obrigatorio"] = false;
$regras["revenda|cidade"]["obrigatorio"]  = false;
$regras["revenda|estado"]["obrigatorio"]    = false;

$antes_valida_campos      = "antes_valida_campos";
$valida_garantia          = "valida_garantia_grohe";
$verifica_peca_estoque    = "verifica_peca_estoque";
$valida_anexo             = "valida_anexo_grohe";
$funcoes_fabrica = array("verifica_estoque_peca");

function valida_anexo_grohe() {
    global $campos, $msg_erro, $anexos_obrigatorios;
    if ($campos["os"]["contrato"] == "t") {

        $regras["os|nota_fiscal"]["obrigatorio"]      = true;

        $anexos_obrigatorios[] = "notafiscal";

    } 
}

function verifica_peca_estoque() {
    global $campos, $con, $login_fabrica, $login_posto, $msg_erro;

    for ($x=0;$x < count($campos);$x++) {

        $peca                  = $campos["produto_pecas"][$x]["id"];
        $servico_realizado     = $campos["produto_pecas"][$x]["servico_realizado"];
        $referencia            = $campos["produto_pecas"][$x]["referencia"];

        $sql = "SELECT peca_estoque
                FROM   tbl_servico_realizado
                WHERE  servico_realizado = {$servico_realizado}";
        $res = pg_query($con, $sql);

        $usa_estoque = pg_fetch_result($res, 0, "peca_estoque");

        if ($usa_estoque == "t") {

            $sql = "SELECT * FROM tbl_estoque_posto
                    WHERE peca  = {$peca}
                    AND posto   = {$login_posto}
                    AND fabrica = {$login_fabrica}";
            $res = pg_query($con, $sql);

            if (pg_num_rows($res) == 0) {
                $msg_erro["msg"][] = traduz("Não existe estoque cadastrado para a peça <strong>$referencia</strong>, selecione outro serviço");
            }
        }
    }
}

function grava_os_campo_extra_fabrica(){
    global $campos;

    return array(
            "qtde_km_ida" => $campos["os"]["qtde_km_ida"],
            "qtde_km_volta" => $campos["os"]["qtde_km_volta"],
            "edificio" => $campos["os"]["edificio"],
            "edificio_total_andares" => $campos["os"]["edificio_total_andares"]
        );
}

function valida_garantia_grohe(){
    global $con, $login_fabrica, $campos, $msg_erro, $os;

    $data_compra   = $campos["os"]["data_compra"];
    $data_abertura = $campos["os"]["data_abertura"];
    $produto       = $campos["produto"]["id"];

    if (!empty($produto) && !empty($data_compra) && !empty($data_abertura)) {
        $garantia = 0;

        $sql = "SELECT os FROM tbl_os WHERE contrato IS TRUE AND os = {$os} AND fabrica = {$login_fabrica}";
        $res = pg_query($con, $sql);
        if (pg_num_rows($res)) {
            $garantia = 5;
        }else{
            $sql = "SELECT garantia FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto}";
            $res = pg_query($con, $sql);
            if (pg_num_rows($res) > 0)
                $garantia = pg_fetch_result($res, 0, "garantia");
        }
        if ($garantia !== 0) {
            if (strtotime(formata_data($data_compra)." +{$garantia} year") < strtotime(formata_data($data_abertura))) {
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

function grava_os_extra_fabrica(){
    global $campos;

    if (empty($campos["os"]["tempo_uso"])) {
        $campos["os"]["tempo_uso"] = 0;
    }

    if (empty($campos["os"]["pressao_agua"])) {
        $campos["os"]["pressao_agua"] = 0;
    }

    return array("regulagem_peso_padrao" => $campos["os"]["pressao_agua"], "qtde_horas" => $campos["os"]["tempo_uso"]);
}

function grava_os_fabrica(){
    global $campos;

    if (empty($campos["os"]["qtde_visita"])) {
        $campos["os"]["qtde_visita"] = 0;
    }

    return array(
        "qtde_diaria" => $campos["os"]["qtde_visita"],
        "defeito_reclamado" => $campos["os"]["defeito_reclamado"]
    );
}

function antes_valida_campos(){
    global $regras, $campos, $valida_garantia, $con, $os, $qtde_diaria_anterior;

    if (empty($campos["os"]["tipo_atendimento"])) { return; }

    $sql = "SELECT tipo_atendimento,km_google FROM tbl_tipo_atendimento WHERE fora_garantia IS TRUE AND tipo_atendimento = ".$campos["os"]["tipo_atendimento"];
    $res = pg_query($con, $sql);

    $km_google = pg_fetch_result($res, 0, 'km_google');

    if ($km_google == "t") {
        $regras["os|observacoes"]["obrigatorio"] = true;
    }

    if ($campos["os"]["contrato"] == "t"){
        $regras["os|nota_fiscal"]["obrigatorio"] = true;
	    $regras["os|data_compra"]["obrigatorio"] = true;
    }

    $sql = "SELECT 
            tbl_auditoria_os.auditoria_status,
            tbl_os.qtde_diaria
            FROM tbl_auditoria_os
            JOIN tbl_os USING (os)
            WHERE os = {$os}
            AND   auditoria_status = 2";
                     
    $res = pg_query($con, $sql);

    $qtde_diaria_anterior = pg_fetch_result($res, 0, 'qtde_diaria');
}

function auditoria_os_reincidente_grohe(){
    global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

    $posto = $campos['posto']['id'];

    $sql = "SELECT  os
            FROM    tbl_os
            WHERE   fabrica         = {$login_fabrica}
            AND     os              = {$os}
            AND     os_reincidente  IS NOT TRUE
            AND     cancelada       IS NOT TRUE
    ";
    $res = pg_query($con, $sql);

    if(pg_num_rows($res) > 0){

        $consumidor_cpf = preg_replace("/\D/", "", $campos["consumidor"]["cpf"]);
        $consumidor_cnpj = preg_replace("/\D/", "", $campos["revenda"]["cnpj"]);

        $select = "SELECT tbl_os.os
                FROM tbl_os
                INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                WHERE tbl_os.fabrica = {$login_fabrica}
                AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '90 days')
                AND tbl_os.excluida IS NOT TRUE
                AND tbl_os.os < {$os}
                AND tbl_os.posto = $posto
                AND tbl_os.consumidor_cpf = '$consumidor_cpf'
                AND tbl_os.nota_fiscal = '{$campos['os']['nota_fiscal']}'
                AND tbl_os.revenda_cnpj = '$consumidor_cnpj'
                AND tbl_os_produto.produto = {$campos['produto']['id']}
                ORDER BY tbl_os.data_abertura DESC
                LIMIT 1";
                
        $resSelect = pg_query($con, $select);

        if (pg_num_rows($resSelect) > 0 && verifica_auditoria(array(70, 19), array(19, 70), $os) === true) {
            $os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");
            
            if ($areaAdmin === false){ 
                if (verifica_os_reincidente_finalizada($os_reincidente_numero)) {
                    if(!insere_auditoria(1, 'OS reincidente de cnpj, nota fiscal e produto'))
                        throw new Exception("Erro ao lançar ordem de serviço");

                    $os_reincidente = true;
                }
            }
            
        }
    }
}

function auditoria_valor_adicional_grohe(){
    global $con, $login_fabrica, $os, $campos;

    $valores_adicionais        = $campos["auditoria"]["valor_adicional_valor_antes"];
    $valores_adicionais_antes  = (!is_string($valores_adicionais) || strlen($valores_adicionais) == 0) ? array() : json_decode($valores_adicionais, true);
    $valores_adicionais_depois = (is_array($campos["os"]["valor_adicional"])) ? $campos["os"]["valor_adicional"] : array();

    if (count($valores_adicionais_antes) !== count($valores_adicionais_depois)){
        if(!insere_auditoria(6,"OS em Auditoria de Valores Adicionais", "false"))
            throw new Exception("Erro ao lançar ordem de serviço");

        return;
    }

    $valores_adicionais_depois = array_map(function($val){
        global $campos;

        $valores = explode('|', $val);
        return array($valores[0] => $campos["os"]['valor_adicional_valor'][$valores[0]]);
    }, $valores_adicionais_depois);

    $comparar_antes = array();
    foreach ($valores_adicionais_antes as $array_antes) {
        foreach ($array_antes as $key => $value) {
            $comparar_antes[$key] = $value;
        }
    }

    $comparar_depois = array();
    foreach ($valores_adicionais_depois as $array_depois) {
        foreach ($array_depois as $key => $value) {
            $comparar_depois[$key] = $value;
        }
    }

    if (count(array_diff_assoc($comparar_antes, $comparar_depois)))
        if(!insere_auditoria(6,"OS em Auditoria de Valores Adicionais", "false"))
            throw new Exception("Erro ao lançar ordem de serviço");
}

function auditoria_km_grohe(){
    global $campos, $os, $con, $qtde_diaria_anterior;

    if (!empty($campos['os']['qtde_km']) && $campos['os']['qtde_km'] != 0) {

        if (!empty($os)) {
            $sql = "SELECT
                    tbl_auditoria_os.auditoria_status,
                    tbl_os.qtde_diaria
                    FROM tbl_auditoria_os
                    JOIN tbl_os USING (os)
                    WHERE os = {$os}
                    AND   auditoria_status = 2";

            $res = pg_query($con, $sql);

            if (pg_num_rows($res) > 0 && $campos['os']['qtde_visita'] != $_POST["qtde_visita_anterior"]) {

                if(!insere_auditoria(2,"OS em auditoria de KM (Qtde. de visitas alterada)", "false")){
                    throw new Exception("Erro ao lançar ordem de serviço");
                }
            } elseif (pg_num_rows($res) == 0) {
                if(!insere_auditoria(2,"OS em auditoria de KM", "false")){
                    throw new Exception("Erro ao lançar ordem de serviço");
                }
            }
        } else {
            if(!insere_auditoria(2,"OS em auditoria de KM", "false")){
                throw new Exception("Erro ao lançar ordem de serviço");
            }
        }
    }
}

function auditoria_peca_nao_cadastrada_grohe(){
    global $campos, $con;

    pg_prepare($con, 'peca_pre_selecionada', "SELECT peca FROM tbl_peca WHERE peca = $1 AND pre_selecionada = true");
    foreach ($campos['produto_pecas'] as $pecas) {
        if ($pecas['id'] == null || empty($pecas['id'])){ continue; }

        $res = pg_execute($con, 'peca_pre_selecionada', array($pecas['id']));
        if (pg_num_rows($res) > 0){
            if(!insere_auditoria(4,"Auditoria de peça não cadastrada"))
                throw new Exception("Erro ao lançar ordem de serviço");

            break;
        }
    }
}

function auditoria_troca_obrigatoria_grohe(){
    global $con, $os, $login_fabrica;
    $sql = "SELECT tbl_produto.produto
            FROM tbl_os_produto
            INNER JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
            JOIN tbl_os ON tbl_os.os = tbl_os_produto.os
            JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento
            WHERE tbl_os_produto.os = {$os}
            AND tbl_produto.troca_obrigatoria IS TRUE
            AND tbl_tipo_atendimento.fora_garantia IS TRUE";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0 && verifica_auditoria(array(62, 64), array(62, 64), $os) === true)
        if(!insere_auditoria(3, 'OS em intervenção da fábrica por Produto de troca obrigatória'))
            throw new Exception("Erro ao lançar ordem de serviço");
}

function auditoria_pedido_peca_grohe(){
    global $campos, $con, $login_fabrica;

    pg_prepare($con, 'servico_realizado', "SELECT servico_realizado FROM tbl_servico_realizado WHERE servico_realizado = $1 AND fabrica = {$login_fabrica} AND troca_de_peca is true AND gera_pedido is true");

    foreach ($campos['produto_pecas'] as $pecas) {
        if (empty($pecas['servico_realizado']) || $pecas['servico_realizado'] == 'null') {
            continue;
	}
        $res = pg_execute($con, 'servico_realizado', array($pecas['servico_realizado']));
        if (pg_num_rows($res) > 0){
            if(!insere_auditoria(4,"Auditoria de pedido de peça"))
                throw new Exception("Erro ao lançar ordem de serviço");

            break;
        }
    }
}

function insere_auditoria($status_os, $descricao, $pedido = 'true'){
    global $os, $con;

    /* VERIFICA SE JÁ POSSUI A AUDITORIA CADASTRADA E SE NÃO FOI APROVADA, CANCELADA OU REPROVADA */
    $sql = "SELECT os FROM tbl_auditoria_os
                WHERE os = {$os}
                    AND auditoria_status = {$status_os}
                    AND observacao = '{$descricao}'
                    AND liberada IS NULL
                    AND cancelada IS NULL
                    AND reprovada IS NULL
            ";
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0)
        return true;

    $sql = "INSERT INTO tbl_auditoria_os
                (os, auditoria_status, observacao, bloqueio_pedido)
            VALUES
                ({$os}, {$status_os}, '{$descricao}', {$pedido})";

    $res = pg_query($con, $sql);
    return (strlen(pg_last_error()) > 0) ? false : true;
}

function auditoria_estoque_peca_grohe()
{
    global $campos, $os, $con, $login_fabrica;
    
    if (verifica_peca_lancada() == true)
    {
        foreach ($campos['produto_pecas'] as $pecas_for)
        {
            if (empty($pecas_for['os_item']) && $pecas_for['servico_realizado'] == true || $pecas_for['servico_realizado'] == 't')
            {
                $posto = $campos['posto']['id'];
                $sql_estoque = "
                    SELECT qtde 
                        FROM tbl_estoque_posto
                    WHERE fabrica = {$login_fabrica}
                    AND posto = {$posto}
                    AND peca = {$pecas_for['id']} AND qtde > 0
                ";
                
                $query = pg_query($con,$sql_estoque);
                if (pg_num_rows($query) > 0)
                {
                    $verifica = verifica_auditoria_unica("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%auditoria de estoque de peça%' AND tbl_auditoria_os.liberada IS NULL", $os);
                    if ($verifica)
                    {
                        $resp_aud  = buscaAuditoria("tbl_auditoria_status.peca = 't'");
                        $id_aud    = $resp_aud['auditoria'];
                        $sql_ins = "INSERT INTO tbl_auditoria_os
                                (os, auditoria_status, observacao) 
                            VALUES
                                ({$os}, {$id_aud}, 'Auditoria de estoque de peça')";
                        $query_ins = pg_query($con,$sql_ins);
                        return (strlen(pg_last_error()) > 0) ? false : true;
                    }
                }
            }
        }
    }
}
