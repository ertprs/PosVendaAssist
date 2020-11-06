<?php

$regras["os|aparencia_produto"] = array(
    "obrigatorio" => true
);

$regras["os|acessorios"] = array(
    "obrigatorio" => true
);

$regras["consumidor|cpf"] = array(
    "obrigatorio" => true,
    "function" => array("valida_consumidor_cpf")
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

$regras["consumidor|telefone"] = array(
    "obrigatorio" => true
);

$regras["consumidor|numero"] = array(
    "obrigatorio" => true
);

$regras["consumidor|email"] = array(
    "obrigatorio" => true
);

$regras["produto|imei"] = array(
    "function" => array("valida_imei")
);

$regras["produto|serie"] = array(
    "function" => array("valida_numero_de_serie_qbex")
);

$regras["produto|sem_ns"] = array(
    "function" => array("valida_produto_sem_numero_serie")
);


$auditorias = array(
  "auditoria_reincidente_qbex",
  "auditoria_peca_critica",
  "auditoria_troca_obrigatoria",
  "auditoria_pecas_excedentes",
  "auditoria_numero_serie",
  "auditoria_revenda"
);

$funcoes_fabrica = array("valida_lanca_peca","verifica_estoque_peca");

$valida_anexo = "valida_anexo_qbex";

function grava_os_fabrica(){
    global $con, $campos, $login_fabrica, $login_unico_tecnico, $msg_erro, $os;

    $return = array();

    if(strlen(trim($login_unico_tecnico)) > 0 and empty($os)){
        $return['tecnico'] = $login_unico_tecnico;
    }

    $return['rg_produto']    = "'{$campos["produto"]["imei"]}'";
    $return['key_code']      = "'{$campos["produto"]["partnumber"]}'";

    return $return;

}

function valida_imei(){
    global $con, $campos, $login_fabrica, $msg_erro;

    $linha_informatica  = $campos['produto']['linha_informatica'];

    if($linha_informatica == 'f' and strlen(trim($campos['produto']['imei'])) == 0 ){

        $msg_erro["msg"]["campo_obrigatorio"] = "Preencha todos os campos obrigatórios";
        $msg_erro["campos"][] = "produto[imei]";

    }
}


function valida_numero_de_serie_qbex(){
    global $con, $campos, $login_fabrica, $msg_erro;

    $produto_id         = $campos["produto"]["id"];
    $produto_serie      = $campos["produto"]["serie"];
    $produto_sem_ns     = $campos["produto"]['sem_ns'];
    $linha_informatica  = $campos['produto']['linha_informatica'];

    if($produto_sem_ns != 't'){
        if (strlen($produto_id) > 0) {
            $sql = "SELECT produto
                    from tbl_produto
                    INNER JOIN tbl_linha on tbl_linha.linha = tbl_produto.linha
                    where fabrica_i = $login_fabrica and produto = $produto_id and numero_serie_obrigatorio is true and tbl_linha.informatica = 't' ";
            $res = pg_query($con,$sql);
            if(pg_num_rows($res) > 0 && empty($produto_serie)){
                $msg_erro["msg"]["campo_obrigatorio"] = "Preencha todos os campos obrigatórios";
                $msg_erro["campos"][] = "produto[serie]";
            }
        }
    }
}

function valida_produto_sem_numero_serie(){

    global $con, $campos, $login_fabrica, $os, $msg_erro;

    $linha_informatica  = $campos['produto']['linha_informatica'];
    $partnumber         = $campos['produto']['partnumber'];
    $campos['os']['key_code'] = $campos['produto']['partnumber'];

    $produto_sem_ns = $campos["produto"]['sem_ns'];

    if( $linha_informatica == 't' and strlen(trim($partnumber))==0 and $produto_sem_ns == 't'){
        $msg_erro["msg"]["campo_obrigatorio"] = "Preencha todos os campos obrigatórios";
        $msg_erro["campos"][] = "produto[partnumber]";
    }
}

function valida_lanca_peca(){

    global $login_fabrica, $campos, $os, $con;


    $pecas_pedido       = $campos["produto_pecas"];
    $defeito_constatado = $campos["produto"]["defeito_constatado"];

    if(!empty($defeito_constatado)) {
        $sql = "SELECT defeito_constatado
                FROM tbl_defeito_constatado
                WHERE fabrica = $login_fabrica
                AND defeito_constatado = $defeito_constatado
                AND lancar_peca";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res) > 0) {

            $sql = "SELECT os
                FROM tbl_os_item
                join tbl_os_produto using(os_produto)
                WHERE os = $os";
            $res = pg_query($con, $sql);
            if(pg_num_rows($res) == 0) {
                throw new Exception("É obrigado lançar peça para este defeito constatado");
            }
        }
    }
}

function auditoria_numero_serie(){

    global $con, $campos, $login_fabrica, $os, $msg_erro;

    $linha_informatica  = $campos['produto']['linha_informatica'];
    $produto_sem_ns = $campos["produto"]['sem_ns'];
    $auditoria_status   = 5;

    $sql = "SELECT * FROM tbl_auditoria_os WHERE os = $os and auditoria_status = $auditoria_status";
    $res = pg_query($con, $sql);
    if(pg_num_rows($res)== 0){
        if($linha_informatica == 't' and $produto_sem_ns == 't'){
            $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
                        ({$os}, $auditoria_status, 'OS em Auditoria de Número de Série')";
            $res = pg_query($con, $sql);
        }
    }

}

function grava_os_campo_extra_fabrica()
{
    global $con,$campos, $os,$login_fabrica,$areaAdmin;
    $data_saida = formata_data($campos["os"]["data_saida"]);
    $retorno_data_saida = $campos["os"]["data_saida"];
    $rastreio   = strtoupper($campos['os']['rastreio']);
// exit($areaAdmin);

    $return = FALSE;

    if ($areaAdmin === true) {
        $sqlf = "SELECT os FROM tbl_os WHERE os = $os AND finalizada IS NOT NULL";
        $qry = pg_query($con, $sqlf);

        $osFim = pg_fetch_result($qry,0,os);
// exit(nl2br($sqlf));
        if (!empty($osFim)) {
            $sqlIns = "
                INSERT INTO tbl_faturamento_correio(
                    fabrica,
                    local,
                    situacao,
                    data,
                    conhecimento,
                    numero_postagem
                ) VALUES(
                    $login_fabrica,
                    'Número informado pelo admin na os $os',
                    'Número informado pelo admin na os $os',
                    '$data_saida',
                    '$rastreio',
                    '$rastreio'
                )
            ";

            $resIns = pg_query($con,$sqlIns);

            $return = array(
                "data_saida"    => $retorno_data_saida,
                "rastreio"      => $rastreio
            );
        }
    }

    return $return;
}

function auditoria_reincidente_qbex(){

        global $con, $campos, $os, $login_fabrica, $msg_erro;

        $imei               = $campos['produto']['imei'];
        $informatica        = $campos['produto']['linha_informatica'];
        $serie              = $campos['produto']['serie'];
        $auditoria_status   = 1;

        if($informatica == 'f'){
            $sql_imei .= " AND rg_produto = '$imei' ";
        }
        if($informatica == 't'){
            $sql_serie .= " AND serie = '$serie' ";
        }

        $sql = "SELECT * FROM tbl_auditoria_os WHERE os = $os and auditoria_status = $auditoria_status";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res)== 0){

            $sql = "select os from tbl_os
                    where
                    serie <> ''
                    and fabrica = $login_fabrica
                    and os < $os
                    $sql_imei
                    $sql_serie
                    and data_abertura >= (data_abertura - INTERVAL '90 days') limit 1";
            $res = pg_query($con, $sql);

            if(pg_num_rows($res)>0){
                $os_reincidente_qbex = pg_fetch_result($res, 0, 'os');

                $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES
                          ({$os}, $auditoria_status, 'OS em Auditoria de Reincidência')";
                $res = pg_query($con, $sql);

                $sql = "UPDATE tbl_os SET os_reincidente = TRUE WHERE fabrica = {$login_fabrica} AND os = {$os}";
                $res = pg_query($con, $sql);

                $sql = "UPDATE tbl_os_extra SET os_reincidente = {$os_reincidente_qbex} WHERE os = {$os}";
                $res = pg_query($con, $sql);
            }
        }
}

/**
 * Função para validar anexo
 */
function valida_anexo_qbex() {
    global $campos, $con, $msg_erro, $login_fabrica;

    $consumidor_revenda = $campos['os']['consumidor_revenda'];

    $posto = $campos['posto']['id'];

    $sql_verifica_tipo_posto = "SELECT tbl_tipo_posto.posto_interno FROM tbl_posto_fabrica
                                INNER JOIN tbl_tipo_posto ON tbl_posto_fabrica.tipo_posto = tbl_tipo_posto.tipo_posto
                                WHERE tbl_posto_fabrica.posto = $posto and tbl_posto_fabrica.fabrica = $login_fabrica";
    $res_verifica_tipo_posto = pg_query($con, $sql_verifica_tipo_posto);
    if(pg_num_rows($res_verifica_tipo_posto)> 0){
        $posto_interno = pg_fetch_result($res_verifica_tipo_posto, 0, posto_interno);
    }

    if($posto_interno != 't' AND $consumidor_revenda != "R"){
        $count_anexo = array();

        foreach ($campos["anexo"] as $key => $value) {
            if (strlen($value) > 0) {
                $count_anexo[] = "ok";
            }
        }
        if(count($count_anexo) < 2){
            $msg_erro["msg"][] = "É obrigatório dois anexos";
        }

    }
}

?>
