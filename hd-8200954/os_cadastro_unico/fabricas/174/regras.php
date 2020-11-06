<?php
$data_abertura_fixa = true;
$auditoria_bloqueia_pedido = "true";

/**
 * Somente para validar na abertura da OS se já tiver uma OS gravada
 */
if (!empty($os)) {
    $sql = "SELECT consumidor_revenda FROM tbl_os WHERE os = {$os};";
    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0) {
        $consumidor_revenda = pg_fetch_result($res, 0, "consumidor_revenda");
    }
}

$funcoes_fabrica = array("controle_estoque_aquarius");


$regras["os|defeito_reclamado"]["obrigatorio"] = true;
$regras["os|defeito_reclamado_descricao"]["obrigatorio"] = false;
$regras["produto|serie"]["obrigatorio"] = false;

if (strlen(getValue("os[consumidor_revenda]")) > 0 || strlen($consumidor_revenda) > 0) {
    if (getValue("os[consumidor_revenda]") == 'C' || $consumidor_revenda == 'C') {
        $regras["consumidor|cpf"]["obrigatorio"] = true;
        $regras["consumidor|cep"]["obrigatorio"] = true;
        $regras["consumidor|bairro"]["obrigatorio"] = true;
        $regras["consumidor|endereco"]["obrigatorio"] = true;
        $regras["consumidor|numero"]["obrigatorio"] = true;
        $regras["consumidor|telefone"]["obrigatorio"] = true;
        $regras["consumidor|celular"]["obrigatorio"] = true;
        $regras["consumidor|email"]["obrigatorio"] = true;
        $regras["revenda|nome"]["obrigatorio"] = true;
        $regras["revenda|cnpj"]["obrigatorio"] = true;
        $regras["revenda|estado"]["obrigatorio"] = false;
        $regras["revenda|cidade"]["obrigatorio"] = false;
        $regras["revenda|contato"]["obrigatorio"] = false;
    } else {
        $regras["os|nota_fiscal"]["obrigatorio"] = false;
        $regras["os|data_compra"]["obrigatorio"] = false;
        $regras["os|defeito_reclamado"]["obrigatorio"] = true;
        $regras["consumidor|cpf"]["obrigatorio"] = false;
        $regras["consumidor|cep"]["obrigatorio"] = false;
        $regras["consumidor|bairro"]["obrigatorio"] = false;
        $regras["consumidor|endereco"]["obrigatorio"] = false;
        $regras["consumidor|numero"]["obrigatorio"] = false;
        $regras["consumidor|celular"]["obrigatorio"] = false;
        $regras["consumidor|telefone"]["obrigatorio"] = false;
        $regras["consumidor|email"]["obrigatorio"] = false;
        $regras["revenda|nome"]["obrigatorio"] = true;
        $regras["revenda|cnpj"]["obrigatorio"] = true;
        $regras["revenda|estado"]["obrigatorio"] = true;
        $regras["revenda|cidade"]["obrigatorio"] = true;
    }
} else {
    $regras["consumidor|cpf"]["obrigatorio"] = true;
    $regras["consumidor|cep"]["obrigatorio"] = true;
    $regras["consumidor|bairro"]["obrigatorio"] = true;
    $regras["consumidor|endereco"]["obrigatorio"] = true;
    $regras["consumidor|numero"]["obrigatorio"] = true;
    $regras["consumidor|telefone"]["obrigatorio"] = true;
    $regras["consumidor|celular"]["obrigatorio"] = true;
    $regras["consumidor|email"]["obrigatorio"] = true;
    $regras["revenda|nome"]["obrigatorio"] = true;
    $regras["revenda|cnpj"]["obrigatorio"] = true;
    $regras["revenda|estado"]["obrigatorio"] = false;
    $regras["revenda|cidade"]["obrigatorio"] = false;
}

$antes_valida_campos = "antes_valida_campos";

$regras_pecas = array(
    'numero_serie' => false,
    'lista_basica' => false,
    'servico_realizado' => false
);

$grava_multiplos_defeitos = "grava_multiplos_defeitos_aquarius";

$auditorias = array(
    "auditoria_troca_obrigatoria_aquarius",
    "auditoria_peca_critica_aquarius",
    "auditoria_pecas_excedentes_aquarius",
    "auditoria_os_reincidente_aquarius"
);

$posto_interno = posto_interno();

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

function antes_valida_campos() {
    global $campos, $con, $login_fabrica, $regras, $valida_anexo, $msg_erro, $valida_garantia, $anexos_obrigatorios;

    $produto = $campos['produto']['id'];
    $nserie = $campos['produto']['serie'];
    $posto = $campos['posto']['id'];

    // remove obrigátorio
    $tipo_atendimento_campo = $campos['os']['tipo_atendimento'];
    $tipo_os_campo          = $campos['consumidor_revenda'];
        
	if (!empty($tipo_atendimento_campo)) {
        $sql_remov = "SELECT * FROM tbl_tipo_atendimento WHERE fabrica = {$login_fabrica} AND tipo_atendimento = {$tipo_atendimento_campo} AND fora_garantia = TRUE;";
        $res_remov = pg_query($con, $sql_remov);
        
        if (pg_num_rows($res_remov) > 0){
	        $valida_garantia = null;
            $regras['os|nota_fiscal']['obrigatorio'] = false;
            $regras["os|data_compra"]["obrigatorio"] = false;
            $regras["consumidor|telefone"]["obrigatorio"] = false;
            $regras["consumidor|email"]["obrigatorio"] = false;
            $valida_anexo = NULL;
            $anexos_obrigatorios = [];
            
            if ($tipo_os_campo == "R") {
                $regras["revenda|nome"]["obrigatorio"] = false;
                $regras["revenda|cnpj"]["obrigatorio"] = false;
                $regras["revenda|estado"]["obrigatorio"] = false;
                $regras["revenda|cidade"]["obrigatorio"] = false;
            }
        }
	}

	if (!empty($produto)) {
       $sql = "SELECT produto FROM tbl_produto WHERE fabrica_i = {$login_fabrica} AND produto = {$produto} AND numero_serie_obrigatorio IS TRUE;";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0 AND empty($nserie)){
            $msg_erro['msg'][] = 'Número de série obrigatório.';
            $msg_erro['campos'][] = 'produto[serie]';
        }
	}

    if (!empty($posto)) {
        $sql = "SELECT tp.tipo_posto FROM tbl_posto_fabrica pf INNER JOIN tbl_tipo_posto tp ON tp.tipo_posto = pf.tipo_posto AND tp.fabrica = {$login_fabrica} WHERE pf.fabrica = {$login_fabrica} AND pf.posto = {$posto} AND tp.posto_interno IS TRUE";
        $res = pg_query($con, $sql);

        if (pg_num_rows($res) > 0) {
            $valida_anexo = null;
            $anexos_obrigatorios = [];
        }
    }
}

function auditoria_os_reincidente_aquarius() {
    global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero, $auditoria_bloqueia_pedido;

    $posto = $campos['posto']['id'];

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
            AND tbl_os.excluida IS NOT TRUE
            AND tbl_os.os < {$os}
            AND tbl_os.posto = {$posto}
            AND tbl_os.nota_fiscal = '{$campos['os']['nota_fiscal']}'
            AND tbl_os.revenda_cnpj = '{$consumidor_cnpj}'
            AND tbl_os_produto.produto = {$campos['produto']['id']}
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
                    VALUES ({$os}, {$auditoria_status}, 'Auditoria de OS reincidente', {$auditoria_bloqueia_pedido});
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

function auditoria_pecas_excedentes_aquarius()
{
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
                AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE
                AND tbl_os.consumidor_revenda = 'C';
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

function auditoria_peca_critica_aquarius()
{
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
        AND tbl_peca.peca_critica IS TRUE
        AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE;
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
                    AND tbl_peca.peca IN (".implode(", ", $nova_peca).")
                    AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE;
                ";
                $res = pg_query($con,$sql);

                if(pg_num_rows($res) > 0){
                    $sql = "
                        INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                        VALUES ({$os}, {$auditoria_status}, 'OS em intervenção da fábrica por Peça Crí­tica', {$auditoria_bloqueia_pedido});
                    ";
                    $res = pg_query($con, $sql);

                    if (strlen(pg_last_error()) > 0) {
                        throw new Exception("Erro ao lançar ordem de serviço #AUD003");
                    }
                }
            }
        }
    }
}

function auditoria_troca_obrigatoria_aquarius()
{
    global $con, $os, $login_fabrica, $auditoria_bloqueia_pedido;

    $sql = "
        SELECT
            tbl_produto.produto
        FROM tbl_os
        JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
        JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto
        JOIN tbl_tipo_atendimento ON tbl_tipo_atendimento.tipo_atendimento = tbl_os.tipo_atendimento AND tbl_tipo_atendimento.fabrica = {$login_fabrica}
        WHERE tbl_os.fabrica = {$login_fabrica}
        AND tbl_os_produto.os = {$os}
        AND tbl_produto.troca_obrigatoria IS TRUE
        AND tbl_tipo_atendimento.fora_garantia IS NOT TRUE
        AND tbl_os.consumidor_revenda = 'C';
    ";

    $res = pg_query($con, $sql);

    if (pg_num_rows($res) > 0 && verifica_auditoria_unica(" tbl_auditoria_status.produto = 't' AND tbl_auditoria_os.observacao ILIKE '%troca obrigatória%'", $os) === true) {
        $busca = buscaAuditoria("tbl_auditoria_status.produto = 't'");

        if($busca['resultado']){
            $auditoria_status = $busca['auditoria'];
        }

        $sql = "
            INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
            VALUES ({$os}, {$auditoria_status}, 'OS em intervenção da fábrica por Produto de troca obrigatória', {$auditoria_bloqueia_pedido});
        ";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao lançar ordem de serviço #AUD004");
        }
    }
}

// não debitar do estoque, aquarius
function controle_estoque_aquarius()
{
    global $con, $login_fabrica, $campos;

    $tipo_atendimento_campo = $campos['os']['tipo_atendimento'];

    $sql = "SELECT * 
        FROM tbl_tipo_atendimento 
        WHERE fabrica = {$login_fabrica} AND 
        tipo_atendimento = {$tipo_atendimento_campo} AND 
        fora_garantia IS NOT TRUE;";
    $query = pg_query($con,$sql);
    $nums  = pg_num_rows($query);

    if ($nums > 0)
    {
        verifica_estoque_peca();
    }
}

function grava_os_campo_extra_fabrica(){
    global $campos;

    return array(
        "valor_nf" => $campos["os"]["valor_nf"]
    );
}
