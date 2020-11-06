<?php
$regras["os|status_orcamento"] = array(
    "obrigatorio" => false,
    "function" => array("valida_status_orcamento")
);

$regras["os|defeito_reclamado_descricao"]["obrigatorio"] = false;
$regras["revenda|nome"]["obrigatorio"]   = false;
$regras["revenda|cnpj"]["obrigatorio"]   = false;
$regras["revenda|cnpj"]["function"]      = [];
$regras["revenda|estado"]["obrigatorio"] = false;
$regras["revenda|cidade"]["obrigatorio"] = false;
$regras["os|data_compra"]["obrigatorio"] = false;
$regras["os|nota_fiscal"]["obrigatorio"]   = false;

if(verifica_tipo_atendimento() == "Orçamento"){
    $regras["produto|defeito_constatado"]["obrigatorio"]   = false;
    $regras["produto|serie"]["obrigatorio"] = false;
    $regras["os|defeito_reclamado"]["obrigatorio"] = false;
    $regras_pecas["servico_realizado"] = false;
    $regras["produto|serie"] = array(
        "function" => array()
    );
    $valida_garantia = "";
} else {
    $regras["produto|defeito_constatado"]["function"] = array("valida_defeito_constatado_nilfisk");

    $auditorias = array(
        "auditoria_os_reincidente_nilfisk",
        "auditoria_peca_critica_nilfisk",
        "auditoria_km_nilfisk",
        "auditoria_numero_de_serie_nilfisk",
        "auditoria_valores_adicionais",
        "auditoria_pecas_excedentes_nilfisk",
    );

}

if (verifica_tipo_atendimento() == "Preventiva" && getValue('limite_horas_trabalhadas')) {
    $regras["os|horimetro"]["obrigatorio"] = true;
//array_push($auditorias,"auditoria_horimetro_nilfisk");
    $auditorias[] =         "auditoria_horimetro_nilfisk";

}

if (verifica_tipo_atendimento() == "Garantia") {
        $valida_garantia = "valida_garantia_nilfisk";
}

if (verifica_tipo_atendimento() == 'Orçamento'){
    $funcoes_envia_email = ["envia_email_consumidor"];
} else {
    $funcoes_fabrica            = ["verifica_estoque_peca_nilfisk"];
    $funcoes_gera_os_orcamento  = ["funcoes_gera_os_orcamento"];
}

function funcoes_gera_os_orcamento() {
    
    global $login_fabrica, $campos, $os, $gravando , $con;

    if (strlen($os) > 0) {

        $abreOs = verificaOsMauUso($os);

        if ($abreOs) {



        }

    }

}

function verificaOsMauUso($os) {

    global $login_fabrica, $campos, $con;
    $sql = "SELECT tbl_os.os 
              FROM tbl_os 
              JOIN tbl_contrato_os ON tbl_contrato_os.os = tbl_os.os
              JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
              JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
              JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = $login_fabrica
             WHERE tbl_os.os={$os}
               AND tbl_os.fabrica = $login_fabrica
               AND tbl_servico_realizado.descricao = 'Mau Uso'";
    $res = pg_query($con, $sql);
    if (pg_num_rows($res) > 0) {

    }

}

function verifica_estoque_peca_nilfisk(){

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
                                $novo_servico_realizado = buscaServicoRealizadoNilfisk("gera_pedido");
                                $sql = "UPDATE tbl_os_item SET servico_realizado = {$novo_servico_realizado} WHERE os_item = {$os_item}";
                                $res = pg_query($con, $sql);
                            }else{
                                $Os->lancaMovimentoEstoque($posto, $peca, $qtde, $os, $os_item, $nota_fiscal, $data_nf, "saida", $con);
                            }
                        }
                    } else {
                        $status_estoque = $Os->verificaEstoquePosto($posto, $peca, $qtde, $con);

                        if(!$status_estoque){
                            $novo_servico_realizado = buscaServicoRealizadoNilfisk("gera_pedido");
                    
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

                        $novo_servico_realizado = buscaServicoRealizadoNilfisk("estoque");

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

function buscaServicoRealizadoNilfisk($tipo) {
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

    if (!empty($tipo_atendimento)) {
        $sql = "SELECT descricao FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento = {$tipo_atendimento}";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res) > 0){
            $descricao = pg_fetch_result($res_status, 0, 'descricao');

            if (verifica_tipo_atendimento() == "Orçamento"){

                $mo_adicional = str_replace(",",".",str_replace(".","", $campos["os"]["valor_adicional_mo"]));

                if (!empty($mo_adicional)){
                    return array(
                        "mao_de_obra_adicional" => $mo_adicional,
                    );
                }
            }
        }
    } 
}



$valida_anexo = "";
function valida_anexo_mq() {
    global $campos, $msg_erro, $fabricaFileUploadOS, $os, $con, $login_fabrica, $areaAdmin;

    if ($fabricaFileUploadOS) {
        $anexo_chave = $campos["anexo_chave"];
    
        if (!empty($anexo_chave) && !in_array(verifica_tipo_atendimento(), ["Orçamento"])){
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

//$valida_anexo = "valida_anexo_mq";
$valida_anexo = "";

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

function auditoria_km_nilfisk(){
    global $con, $os, $login_fabrica, $campos;
    if (!strlen($campos["os"]["qtde_km_hidden"])) {
        $campos["os"]["qtde_km_hidden"] = $campos["os"]["qtde_km"];
    }
    $qtde_km = $campos["os"]["qtde_km"];
    $qtde_km_anterior = $campos["os"]["qtde_km_hidden"];

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

function auditoria_pecas_excedentes_nilfisk(){
    global $con, $os, $login_fabrica, $auditoria_bloqueia_pedido;
    if(verifica_peca_lancada() === true){
        $sql = "SELECT qtde_pecas_intervencao FROM tbl_fabrica WHERE fabrica = {$login_fabrica};";
        $res = pg_query($con, $sql);
        $qtde_pecas_intervencao = pg_fetch_result($res, 0, "qtde_pecas_intervencao");
        if(!strlen($qtde_pecas_intervencao)){
            $qtde_pecas_intervencao = 0;
        }
        if ($qtde_pecas_intervencao > 0) {
            $sql = "
                SELECT
                    COUNT(tbl_os_item.os_item) AS qtde_pecas
                FROM tbl_os_item
                JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
                JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
                JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.gera_pedido IS TRUE AND troca_de_peca IS TRUE
                WHERE tbl_os.fabrica = {$login_fabrica}
                AND tbl_os_produto.os = {$os}
                AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE;
            ";
            $res = pg_query($con, $sql);
            if(pg_num_rows($res) > 0){
                $qtde_pecas = pg_fetch_result($res, 0, "qtde_pecas");
            }else{
                $qtde_pecas = 0;
            }
            if ($qtde_pecas > $qtde_pecas_intervencao) {
                $busca = buscaAuditoria("tbl_auditoria_status.peca = 't'");
                if($busca['resultado']){
                    $auditoria_status = $busca['auditoria'];
                }
                if (verifica_auditoria_unica("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peças excedentes%'", $os) === true || aprovadoAuditoria("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peças excedentes%'")) {
                    
                    $sql = "
                        INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                        VALUES ({$os}, {$auditoria_status}, 'OS em auditoria de peças excedentes', {$auditoria_bloqueia_pedido});
                    ";
                    $res = pg_query($con, $sql);
                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao lançar ordem de serviço #AUD002");
                    }
                }
            }
        }
    }
}

function auditoria_peca_critica_nilfisk(){
    global $con, $os, $login_fabrica, $qtde_pecas, $auditoria_bloqueia_pedido;
    $sql = "
        SELECT
            tbl_os_item.os_item
        FROM tbl_os_item
        JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca
        JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
        JOIN tbl_os ON tbl_os.os = tbl_os_produto.os AND tbl_os.fabrica = {$login_fabrica}
        JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
        JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.gera_pedido IS TRUE AND troca_de_peca IS TRUE
        WHERE tbl_os.fabrica = {$login_fabrica}
        AND tbl_os_produto.os = {$os}
        AND tbl_peca.peca_critica IS TRUE;
    ";
    $res = pg_query($con, $sql);
    if(pg_num_rows($res) > 0){
        $busca = buscaAuditoria("tbl_auditoria_status.peca = 't'");
        if($busca['resultado']){
            $auditoria_status = $busca['auditoria'];
        }
        if (verifica_auditoria_unica("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peça crí­tica%'", $os) === true) {
            $sql = "
                INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                VALUES ({$os}, {$auditoria_status}, 'OS em intervenção da fábrica por Peça Crí­tica', {$auditoria_bloqueia_pedido});
            ";
            $res = pg_query($con, $sql);
            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao lançar ordem de serviço #AUD002");
            }
        } else if (aprovadoAuditoria("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peça crí­tica%'") && verifica_peca_lancada() === true) {
            $nova_peca = pegar_peca_lancada();
            if(count($nova_peca) > 0){
                $sql = "
                    SELECT
                        tbl_os_item.os_item
                    FROM tbl_os_item
                    JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca
                    JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
                    JOIN tbl_os ON tbl_os.os = tbl_os_produto.os
                    JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
                    JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.gera_pedido IS TRUE AND troca_de_peca IS TRUE
                    WHERE tbl_os.fabrica = {$login_fabrica}
                    AND tbl_os_produto.os = {$os}
                    AND tbl_peca.peca_critica IS TRUE
                    AND tbl_peca.peca IN (".implode(", ", $nova_peca).");
                ";
                $res = pg_query($con,$sql);
                if(pg_num_rows($res) > 0){
                    $sql = "
                        INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                        VALUES ({$os}, {$auditoria_status}, 'OS em intervenção da fábrica por Peça Crí­tica', {$auditoria_bloqueia_pedido});";
                    $res = pg_query($con, $sql);
                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao lançar ordem de serviço #AUD003");
                    }
                }
            }
        }
    }
}


function auditoria_horimetro_nilfisk(){
   global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

   if (temContrato($os) == true) {
    

    if (verifica_auditoria_unica(" tbl_auditoria_os.observacao ILIKE '%horimetro%'", $os) === true || aprovadoAuditoria("tbl_auditoria_os.observacao ILIKE '%horimetro%'")) {

        $sql = "
        INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
        VALUES ({$os}, 6, 'OS em auditoria de horimetro', 't');
        ";
        $res = pg_query($con, $sql);
        if (strlen(pg_last_error()) > 0) {
        throw new Exception("Erro ao lançar ordem de serviço #AUD002");
        }
        }

    

    }
}

function auditoria_os_reincidente_nilfisk(){
    global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

    if (!temContrato() && !in_array(verifica_tipo_atendimento(), ["Preventiva","Preventivo"])) {

        $posto = $campos['posto']['id'];
        $sql = "SELECT  os
                FROM    tbl_os
                WHERE   fabrica         = {$login_fabrica}
                AND     os              = {$os}
                AND     os_reincidente  IS NOT TRUE
                AND     cancelada       IS NOT TRUE
        ";
        $res = pg_query($con, $sql);
        if(pg_num_rows($res) > 0 && strlen($campos['produto']['serie']) > 0 && strlen($campos['produto']['id']) > 0){
            $select = "SELECT tbl_os.os
                    FROM tbl_os
                    INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                    WHERE tbl_os.fabrica = {$login_fabrica}
                    AND tbl_os.data_abertura > (CURRENT_DATE - INTERVAL '90 days')
                    AND tbl_os.excluida IS NOT TRUE
                    AND tbl_os.os < {$os}
                    AND tbl_os.posto = $posto
                    AND tbl_os.serie =  '{$campos['produto']['serie']}'
                    AND tbl_os_produto.produto = {$campos['produto']['id']}
                    ORDER BY tbl_os.data_abertura DESC
                    LIMIT 1";
            $resSelect = pg_query($con, $select);
            if (pg_num_rows($resSelect) > 0 && verifica_auditoria(array(70, 19), array(19, 70), $os) === true) {
                $os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");
                if (verifica_os_reincidente_finalizada($os_reincidente_numero)) {
                    $insert = "INSERT INTO tbl_os_status
                            (os, status_os, observacao,bloqueio_pedido)
                            VALUES
                            ({$os}, 70, 'OS reincidente de cnpj, nota fiscal e produto',true)";
                    $resInsert = pg_query($con, $insert);
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

function auditoria_numero_de_serie_nilfisk(){
    global $con, $campos, $login_fabrica, $os, $msg_erro;

    if (!temContrato()) {

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
                            observacao,
                            bloqueio_pedido
                        ) VALUES
                        (
                            {$os},
                            $auditoria_status,
                            'OS em Auditoria de Número de Série',
                            true
                        )";
                $res = pg_query($con, $sql);
                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao Auditoria de Número de Série");
                }
            }
        }
    }
}

function valida_defeito_constatado_nilfisk() {
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

function temContrato($os = '') {
    global $con, $login_fabrica;
    if (strlen($os) > 0) {
    $sqlContrato = "SELECT contrato 
                  FROM tbl_contrato_os 
             WHERE os={$os}";
    $resContrato = pg_query($con, $sqlContrato);
    if (pg_num_rows($resContrato) > 0) {
        return true;
    } else {
        return false;
    }

    } else {
        if (strlen(getValue("posto[id]")) > 0) {
        $sqlContrato = "SELECT contrato 
                  FROM tbl_contrato 
                 WHERE fabrica = {$login_fabrica} 
                   AND posto=".getValue('posto[id]')." 
                   AND ativo IS TRUE 
                   AND cliente=".getValue('posto[id]');
        $resContrato = pg_query($con, $sqlContrato);
        if (pg_num_rows($resContrato) > 0) {
            return true;
        } else {
            return false;
        }
        }
    }
}

function valida_prazo_contrato($tipo = 'preventivo', $tipo_atendimento, $cliente_admin) {
    global $con, $os, $login_fabrica;
    //verifica se o cliente possui contrato e se o mesmo esta vigente e ativo
    //buscar todas as os abertas no mes do tipo preventivo ou corretivo
    //validar se no contrato a corretiva existe e se nao utrapacou a qtde do mes
    //se utrapassar exibir mensagem dizendo que excedeu
    //nao deve abrir a os

    if (strlen($cliente_admin) > 0) {
    
        $sqlContrato = "SELECT CT.*,
                                     (
                                          SELECT tbl_contrato_status.descricao
                                             FROM tbl_contrato_status_movimento 
                                             JOIN tbl_contrato_status ON  tbl_contrato_status.contrato_status = tbl_contrato_status_movimento.contrato_status
                                             WHERE tbl_contrato_status_movimento.contrato = CT.contrato ORDER BY tbl_contrato_status_movimento.data desc LIMIT 1
                                     ) AS status_contrato_descricao
                                FROM tbl_contrato CT
                                JOIN tbl_cliente_admin CA ON CA.cliente_admin = CT.cliente  
                                WHERE CT.fabrica={$login_fabrica}  
                                 AND CT.cliente = {$cliente_admin}";
        $resContrato = pg_query($con, $sqlContrato);

        if (pg_num_rows($resContrato) > 0) {
            $qtde_preventiva = pg_fetch_result($resContrato, 0, 'qtde_preventiva');
            $qtde_corretiva  = pg_fetch_result($resContrato, 0, 'qtde_corretiva');
            $data_vigencia   = pg_fetch_result($resContrato, 0, 'data_vigencia');
            $status_contrato_descricao   = pg_fetch_result($resContrato, 0, 'status_contrato_descricao');

            if (strtotime($data_vigencia) > strtotime(date("Y-m-d")) && $status_contrato_descricao == "Ativo") {

                $sqlOs = "SELECT count(O.os) 
                            FROM tbl_os O 
                            JOIN tbl_contrato_os CO ON CO.os = O.os  
                            JOIN tbl_contrato C ON C.contrato = CO.contrato  
                            JOIN tbl_cliente_admin CA ON CA.cliente_admin = C.cliente  AND CA.fabrica={$login_fabrica} 
                           WHERE O.data_abertura BETWEEN '".date("Y-m-01")."' AND '".date("Y-m-t")."'
                             AND O.fabrica={$login_fabrica} 
                             AND O.tipo_atendimento = $tipo_atendimento";

                $resOs = pg_query($con, $sqlOs);
                if (pg_num_rows($resOs) > 0) {

                    $total_os_abertas = pg_num_rows($resOs);

                    if ($total_os_abertas > $qtde_corretiva) {
                        $msg_erro .= "Não é possível abrir Ordem de Serviço, pois excedeu o limite de O.Ss do Contrato";
                    }
                }

            } else {
                $msg_erro .= "Contrato expirado ou Inativo";
            }
        } else {
            $msg_erro .= "Não é possível abrir Ordem de Serviço, pois o Cliente não possui Contrato";
        }      
    }
}

function valida_garantia_nilfisk() {
    global $con, $login_fabrica, $campos, $msg_erro;

    $data_compra   = $campos["os"]["data_compra"];
    $data_abertura = $campos["os"]["data_abertura"];
    $produto       = $campos["produto"]["id"];

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

                $assunto    = 'Nilfisk - Orçamento';

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
                        <p>Nilfisk</p>
                        ';

                    $envio = $mailTc->sendMail(
                        $consumidor_email,
                        $assunto,
                        $corpoMensagem,
                        'sac@nilfisk.com.br'
                    );

                    return $envio;

            }
        }
    }
    
}



?>
