<?php
$regras["consumidor|cpf"] = array(
    "obrigatorio" => true
); 

$regras["consumidor|celular"]["obrigatorio"] = true;
$regras["consumidor|telefone"]["obrigatorio"] = true;

if (strlen(trim(getValue("consumidor[celular]"))) > 0 OR strlen(trim(getValue("consumidor[telefone]"))) > 0) {
    $regras["consumidor|telefone"]["obrigatorio"] = false;
    $regras["consumidor|celular"]["obrigatorio"] = false;
}

$regras["consumidor|email"]["obrigatorio"] = false; 
$regras["consumidor|cep"]["obrigatorio"] = true; 
$regras["consumidor|endereco"]["obrigatorio"] = true; 
$regras["consumidor|bairro"]["obrigatorio"] = true; 
$regras["consumidor|numero"]["obrigatorio"] = true; 

$regras["revenda|cep"]["obrigatorio"] = true;
$regras["revenda|endereco"]["obrigatorio"] = true;
$regras["revenda|bairro"]["obrigatorio"] = true;
$regras["revenda|numero"]["obrigatorio"] = true;
$regras["revenda|telefone"]["obrigatorio"] = false;

$regras["os|defeito_reclamado"]["obrigatorio"] = true;
$regras["os|aparencia_produto"]["obrigatorio"] = true;
$regras["os|acessorios"]["obrigatorio"] = true;

if ($_POST["os"]["consumidor_revenda"] == "R") {
    $regras["os|aparencia_produto"]["obrigatorio"] = false;
    $regras["os|acessorios"]["obrigatorio"] = false;
    $regras["os|defeito_reclamado"]["obrigatorio"] = false;
}

$regras["os|tipo_atendimento"]["obrigatorio"] = false;

$regras["produto|serie"]["obrigatorio"] = false;
$regras["produto|defeito_constatado"]["obrigatorio"] = true;

$auditorias = array(
                        "auditoria_os_reincidente_ventisol",
                        "auditoria_valor_adicional_ventisol"
                    );

$valida_anexo_boxuploader = "";


function auditoria_os_reincidente_ventisol() {
    global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

    $posto = $campos['posto']['id'];
    $revenda_cnpj = str_replace(["-","/","."], "", $campos["revenda"]["cnpj"]);

    $sql = "SELECT os FROM tbl_os WHERE fabrica = {$login_fabrica} AND os = {$os} AND os_reincidente IS NOT TRUE AND cancelada IS NOT TRUE;";
    $res = pg_query($con, $sql);
    if(pg_num_rows($res) > 0){

        $select = "
            SELECT
                tbl_os.os
            FROM tbl_os
            INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
            WHERE tbl_os.fabrica = {$login_fabrica}
            AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '90 days')
            AND tbl_os.os < {$os}
            AND tbl_os_produto.produto = {$campos['produto']['id']}
            AND tbl_os.revenda_cnpj = '$revenda_cnpj'
            AND tbl_os.nota_fiscal = '{$campos['os']['nota_fiscal']}'
            AND tbl_os.nota_fiscal <> ''
            AND tbl_os.consumidor_revenda <> 'R'
            AND tbl_os.posto = {$posto}
            AND tbl_os.excluida IS NOT TRUE
            ORDER BY tbl_os.data_abertura DESC
            LIMIT 1;
        ";

        $resSelect = pg_query($con, $select);

        if (pg_num_rows($resSelect) > 0 && verifica_auditoria_unica("tbl_auditoria_status.reincidente = 't'", $os) === true) {
            $os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");


            if (verifica_os_reincidente_finalizada($os_reincidente_numero)) {
                $busca = buscaAuditoria("tbl_auditoria_status.reincidente = 't'");

                if($busca['resultado']){
                    $auditoria_status = $busca['auditoria'];
                }

                $sql = "
                    INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                    VALUES ({$os}, {$auditoria_status}, 'OS reincidente com mesmo produto, revenda e nota fiscal', true);
                ";

                pg_query($con,$sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao lançar ordem de serviço #AUD001");
                } else {
                    $os_reincidente_justificativa = true;
                    $os_reincidente = true;
                }
            }
        } else if (pg_num_rows($resSelect) == 0) {
            $select = "
                SELECT
                    tbl_os.os
                FROM tbl_os
                INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                WHERE tbl_os.fabrica = {$login_fabrica}
                AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '90 days')
                AND tbl_os.os < {$os}
                AND tbl_os.revenda_cnpj = '$revenda_cnpj'
                AND tbl_os.nota_fiscal = '{$campos['os']['nota_fiscal']}'
                AND tbl_os.nota_fiscal <> ''
                AND tbl_os.consumidor_revenda <> 'R'
                AND tbl_os.posto = {$posto}
                AND tbl_os.excluida IS NOT TRUE
                ORDER BY tbl_os.data_abertura DESC
                LIMIT 1;
            ";

            $resSelect = pg_query($con, $select);

            if (pg_num_rows($resSelect) > 0 && verifica_auditoria_unica("tbl_auditoria_status.reincidente = 't'", $os) === true) {
                $os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");


                if (verifica_os_reincidente_finalizada($os_reincidente_numero)) {
                    $busca = buscaAuditoria("tbl_auditoria_status.reincidente = 't'");

                    if($busca['resultado']){
                        $auditoria_status = $busca['auditoria'];
                    }

                    $sql = "
                        INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                        VALUES ({$os}, {$auditoria_status}, 'Auditoria de OS reincidente', true);
                    ";

                    pg_query($con,$sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao lançar ordem de serviço #AUD001");
                    } else {
                        $os_reincidente_justificativa = true;
                        $os_reincidente = true;
                    }
                }
            } 
        }
    }
}

function auditoria_valor_adicional_ventisol(){
    global $con, $campos, $os, $login_fabrica;

    if(!empty($os) && count($campos["os"]["valor_adicional"]) > 0){
        
        if(verifica_auditoria_unica("tbl_auditoria_status.fabricante = 't' AND tbl_auditoria_os.observacao = 'OS em Auditoria de Valores Adicionais' AND tbl_auditoria_os.reprovada IS NULL AND tbl_auditoria_os.liberada IS NULL ", $os) === true){
            $busca = buscaAuditoria("tbl_auditoria_status.fabricante = 't'");

            if($busca["resultado"]){
                $auditoria = $busca["auditoria"];

                $sql = "INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao) VALUES ($os, $auditoria, 'OS em Auditoria de Valores Adicionais')";
                pg_query($con,$sql);

                if(strlen(pg_last_error()) > 0){
                    throw new Exception("Erro ao criar auditoria de aplicação indevida para a OS");

                }else{
                    return true;
                }
            }else{
                throw new Exception("Erro ao buscar auditoria aplicação indevida");

            }
        }
    }
}

$valida_anexo = "";
function valida_anexo_ventisol() {
    global $campos, $msg_erro, $fabricaFileUploadOS, $os, $con, $login_fabrica, $areaAdmin;

    if ($fabricaFileUploadOS) {
        $anexo_chave = $campos["anexo_chave"];
    
        if (!empty($anexo_chave)) {
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
                     throw new Exception(traduz("Obrigatório anexar: nota fiscal do produto"));
                 }
     
             }else{
                throw new Exception(traduz("Obrigatório os seguintes anexos: nota fiscal"));
            }
        }
     }
}

$valida_anexo = "valida_anexo_ventisol";

?>
