<?php
$antes_valida_campos = "regras_campos_obrigatorios_lofra";

$regras["os|nota_fiscal"]["obrigatorio"]        = true;
$regras["os|tipo_atendimento"]["obrigatorio"]   = true;
$regras["os|defeito_reclamado"]["obrigatorio"]  = true;
$regras["consumidor|nome"]["obrigatorio"]       = true;
$regras["consumidor|cep"]["obrigatorio"]        = true;
$regras["consumidor|cpf"]["obrigatorio"]        = true;
$regras["consumidor|bairro"]["obrigatorio"]     = true;
$regras["consumidor|endereco"]["obrigatorio"]   = true;
$regras["consumidor|cidade"]["obrigatorio"]     = true;
$regras["consumidor|estado"]["obrigatorio"]     = true;
$regras["consumidor|numero"]["obrigatorio"]     = true;
$regras["consumidor|celular"]["obrigatorio"]    = true;

$valida_anexo_boxuploader = "valida_anexo_boxuploader";
$anexos_obrigatorios = [];

$regras["produto|serie"] = array(
    "obrigatorio" => true,
    "function" => array("valida_serie_bloqueada")
);

$valida_anexo_boxuploader = "valida_anexo_boxuploader";

$anexos_obrigatorios = ["notafiscal","etiquetaproduto"];

if (strlen(getValue("os[tipo_atendimento]")) > 0) {

    $tipo_atendimento_arr = getTipoAtendimento(getValue("os[tipo_atendimento]"));

}

if (verifica_tipo_posto("posto_interno","TRUE",$login_posto)) {

    $auditorias = array("auditoria_numero_de_serie_lofra");

} else {

    if (in_array($tipo_atendimento_arr["descricao"], array("INSTALACAO"))) {

        $auditorias = array("auditoria_fabrica_lofra");

    } else {

        $auditorias = array(
            "auditoria_peca_critica",
            "auditoria_troca_obrigatoria",
            "auditoria_reincidente_lofra",
            "auditoria_numero_de_serie_lofra",
            "auditoria_defeito_constatado_lofra"
        );

    }

}

/**
 * Regras de campos obrigatorios
 */
function regras_campos_obrigatorios_lofra() {
    global $campos, $regras, $valida_anexo, $con;

    // índice 
        $produto_ref      = $campos["produto"]["referencia"];
        if ($produto_ref != '' || !empty($produto_ref))
        {
            $sql = "SELECT 
                tbl_produto.marca,
                tbl_marca.marca
            FROM tbl_marca 
                INNER JOIN tbl_produto ON tbl_produto.marca = tbl_marca.marca 
            WHERE tbl_marca.visivel IS TRUE AND 
                tbl_produto.referencia = '{$produto_ref}';";
            $query = pg_query($con, $sql);
            if (pg_num_rows($query) > 0)
            {
                $regras["os|indice"] = array(
                    "obrigatorio" => true
                );
            }
        }
        

    $regras["os|data_compra"] = array(
        "obrigatorio" => true,
        "regex"       => "date",
    );
    $regras["os|data_abertura"] = array(
        "obrigatorio" => true,
        "regex"       => "date",
    );

}
$valida_anexo = "valida_anexo_lofra";
/**
 * Função para validar anexo
 
function valida_anexo_lofra() {
    global $campos, $msg_erro, $con , $anexos_obrigatorios;

    $tipo_atendimento = $campos["os"]["tipo_atendimento"];

    $sql = "
        SELECT descricao
        FROM tbl_tipo_atendimento
        WHERE tipo_atendimento = {$tipo_atendimento}
        AND descricao = 'Garantia' ";

    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0){
      $anexos_obrigatorios = ["notafiscal","etiquetaproduto"];

    }
}

 * Função para validar garantia
 */
function valida_garantia_lofra() {
    global $con, $login_fabrica, $campos, $msg_erro;

    $data_compra   = $campos["os"]["data_compra"];
    $data_abertura = $campos["os"]["data_abertura"];
    $produto       = $campos["produto"]["id"];
    $serie         = $campos["produto"]["serie"];

    if (!empty($produto) && !empty($serie) && !empty($data_compra) && !empty($data_abertura)) {
        $sql = "
            SELECT tbl_produto.garantia, tbl_numero_serie.garantia_extendida
            FROM tbl_produto
            JOIN tbl_numero_serie ON tbl_numero_serie.produto = tbl_produto.produto AND  tbl_numero_serie.fabrica = {$login_fabrica}
            WHERE tbl_produto.fabrica_i = {$login_fabrica}
            AND tbl_numero_serie.produto = {$produto}
            AND tbl_numero_serie.serie   = '{$serie}'
        ";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $garantia_estendida  = pg_fetch_result($res, 0, "garantia_extendida");
            $garantia = pg_fetch_result($res, 0, "garantia");

            if ($garantia_estendida == "t") {
                $garantia = $garantia * 2;

                if (strtotime(formata_data($data_compra)." +{$garantia} months") < strtotime(formata_data($data_abertura))) {
                    $msg_erro["msg"][] = traduz("Produto fora de garantia");
                }
            } else {
                valida_garantia();
            }
        } else {
            valida_garantia();
        }
    }
}

$valida_garantia = "valida_garantia_lofra";

/**
 * Função para auditoria de os reincidente
 */
function auditoria_reincidente_lofra(){

    global $login_fabrica, $campos, $os, $con, $login_admin, $os_reincidente_numero, $os_reincidente;

    $produto          = $campos["produto"]["id"];
    $serie            = $campos["produto"]["serie"];
    $auditoria_status = 1;

    $sql_verifica_auditoria = "SELECT os
                                 FROM tbl_auditoria_os
                                WHERE os = $os
                                  AND auditoria_status = $auditoria_status";
    $res_verifica_auditoria = pg_query($con, $sql_verifica_auditoria);

    if (pg_num_rows($res_verifica_auditoria) == 0) {

        $sql = "SELECT tbl_os.os
                  FROM tbl_os
                  JOIN tbl_os_produto USING(os)
                 WHERE tbl_os_produto.serie = '{$serie}'
                   AND tbl_os_produto.produto = {$produto}
                   AND fabrica = {$login_fabrica}
                   AND os < {$os}
                   AND data_abertura >= (data_abertura - INTERVAL '90 days')
              ORDER BY os DESC
                 LIMIT 1";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {

            $os_reincidente_numero = pg_fetch_result($res, 0, 'os');
            $busca = buscaAuditoria("tbl_auditoria_status.reincidente = 't'");

            if ($busca['resultado']) {

                $auditoria_status = $busca['auditoria'];
                $observacao       = "OS Reincidente com mesmo número de série, OS reincidente: ".$os_reincidente_numero;

                $sql = "INSERT INTO tbl_auditoria_os (
                                                        os,
                                                        auditoria_status,
                                                        observacao
                                                    ) VALUES (
                                                        {$os},
                                                        $auditoria_status,
                                                        '$observacao'
                                                    )";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {

                    throw new Exception("Erro ao lançar ordem de serviço");

                } else {

                    $os_reincidente = true;

                }

            } else {

                throw new Exception("Erro ao lançar ordem de serviço");

            }

        }

    }

}

/**
 * Função para auditoria de fabrica
 */
function auditoria_fabrica_lofra(){
    global $con, $login_fabrica, $os, $campos, $tipo_atendimento_arr;

    $tipo_atendimento = $campos["os"]["tipo_atendimento"];

    if (in_array($tipo_atendimento_arr["descricao"], array("INSTALACAO"))) {

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
                                                    'Auditoria de Fábrica: Os de Instalação'
                                                )";
            $res = pg_query($con, $sql);
            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao lançar ordem de serviço");
            }
        }
    }

}

/**
 * Função para auditoria numero de serie
 */
function auditoria_numero_de_serie_lofra(){
    global $con, $campos, $login_fabrica, $os, $msg_erro;

    $produto            = $campos["produto"]["id"];
    $serie              = $campos["produto"]["serie"];
    $auditoria_status   = 5;

    $sqlNS = "SELECT * FROM tbl_numero_serie
                      WHERE produto = $produto
                        AND serie   = '$serie'
                        AND fabrica = $login_fabrica";
    $resNS = pg_query($con, $sqlNS);
    if (pg_num_rows($resNS) == 0) {
        $sql = "SELECT * FROM tbl_auditoria_os
                        WHERE os = $os
                          AND auditoria_status = $auditoria_status";
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
                                                    'OS em Auditoria de Número de Série'
                                                )";
            $res = pg_query($con, $sql);
        }
    }

}

function valida_serie_bloqueada() {
    global $campos, $con, $login_fabrica;

    $produto = $campos['produto']['id'];
    $produto_serie = $campos['produto']['serie'];

    $sql = "
        SELECT serie_controle
        FROM tbl_serie_controle
        WHERE serie = '{$produto_serie}'
        AND fabrica = {$login_fabrica}
        AND produto = {$produto} ";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0){
        throw new Exception("Número de Série bloqueado.");
    }
}

/**
 * Função para auditoria os reincidente
 */
function auditoria_os_reincidente_lofra() {
    global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

    if(strlen(trim($campos['produto']['serie'])) > 0){

        $sql = "SELECT garantia, parametros_adicionais
                    FROM tbl_produto
                INNER JOIN tbl_os on tbl_os.produto = tbl_produto.produto
                WHERE tbl_os.fabrica = {$login_fabrica}
                AND tbl_produto.fabrica_i = {$login_fabrica}
                AND tbl_os.os = {$os}";
        $res = pg_query($con,$sql);

        $parametros_adicionais = json_decode(pg_fetch_result($res, 0, 'parametros_adicionais'),true);

        $suprimento = $parametros_adicionais['suprimento'];

        if ($suprimento == true) {
            $garantia = 90;
        } else {

            if(strlen(pg_num_rows($res))){
                $garantia = pg_fetch_result($res, 0, "garantia");
            }else{
                $garantia = 0;
            }

        }

        $sql = "SELECT os,data_nf FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os} AND os_reincidente IS NOT TRUE";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0){
            $data_nf = pg_fetch_result($res,0,'data_nf');
            $select = "SELECT tbl_os.os
                    FROM tbl_os
                    INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                    INNER JOIN tbl_produto ON tbl_os_produto.produto = tbl_produto.produto
                    WHERE tbl_os.fabrica = {$login_fabrica}
                    AND tbl_os.data_abertura > '$data_nf'
                    AND tbl_os.data_abertura < ('$data_nf'::date + INTERVAL '".$garantia." months')
                    AND tbl_os.excluida IS NOT TRUE
                    AND tbl_os.posto = {$campos['posto']['id']}
                    AND tbl_os.os < {$os}
                    AND tbl_os.serie = '{$campos['produto']['serie']}'
                    ORDER BY tbl_os.data_abertura DESC
                    LIMIT 1";
            $resSelect = pg_query($con, $select);

            if (pg_num_rows($resSelect) > 0 && verifica_auditoria_unica("tbl_auditoria_status.reincidente = 't'", $os) === true) {
                $os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");

                $busca = buscaAuditoria("tbl_auditoria_status.reincidente = 't'");

                if($busca['resultado']){
                    $auditoria_status = $busca['auditoria'];
                }
                $observacao = "OS Reincidente com mesmo número de série, OS reincidente: ".$os_reincidente_numero;
                $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
                        ({$os}, $auditoria_status, '$observacao')";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao lançar ordem de serviço #4");
                } else {
                    $os_reincidente = true;
                }
            }
        }
    }
}

/**
 * Função para auditoria os defeito constatado
 */
function auditoria_defeito_constatado_lofra(){
    global $con, $login_fabrica, $os, $campos, $login_admin;

    if(strlen(trim($campos['produto']['defeito_constatado'])) > 0){
        $auditoria = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

        if($auditoria['resultado']){
            $auditoria_status = $auditoria['auditoria'];
        }

        $auditoria = false;

        $sql = "SELECT tbl_auditoria_os.auditoria_os,
                       tbl_auditoria_os.liberada,
                       tbl_os.defeito_constatado
                  FROM tbl_auditoria_os
                  JOIN tbl_os ON tbl_os.os = tbl_auditoria_os.os AND tbl_os.fabrica = {$login_fabrica}
                 WHERE tbl_auditoria_os.os = {$os}
                   AND tbl_auditoria_os.auditoria_status = $auditoria_status
                   AND tbl_auditoria_os.observacao ILIKE '%Auditoria de Garantia%'";
        $res = pg_query($con, $sql);

        if(pg_num_rows($res) > 0){
            $liberada           = pg_fetch_result($res, 0, "liberada");
            $defeito_constatado = pg_fetch_result($res, 0, "defeito_constatado");

            if(!empty($liberada) && (($defeito_constatado != $campos['produto']['defeito_constatado']) || verifica_peca_lancada() == true)){
                $auditoria = true;
            }
        }else{
            $auditoria = true;
        }

        if ($auditoria == true) {
            $sql = "INSERT INTO tbl_auditoria_os(
                                                    os,
                                                    auditoria_status,
                                                    observacao,
                                                    admin
                                                ) VALUES (
                                                    {$os},
                                                    $auditoria_status,
                                                    'Auditoria de Garantia',
                                                    $login_admin
                                                )";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao lançar ordem de serviço #1");
            }
        }
    }
}

function getTipoAtendimento($tipo_atendimento) {
    global $con, $login_fabrica;

    if (!empty($tipo_atendimento)) {
        $sql = "SELECT tipo_atendimento as id,
                      UPPER(fn_retira_especiais(descricao)) as descricao,
                      entrega_tecnica, grupo_atendimento
                 FROM tbl_tipo_atendimento
                WHERE fabrica = {$login_fabrica}
                AND tipo_atendimento = {$tipo_atendimento}";
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

function grava_os_fabrica()
{
    global $campos;

    $indice = ($campos['os']['indice'] != '') ? $campos['os']['indice'] : 'NULL';
    return array(
        "type" => "{$indice}"
    );
}
