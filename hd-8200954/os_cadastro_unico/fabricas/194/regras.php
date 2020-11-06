<?php
$regras["revenda|nome"]["obrigatorio"]    = false;
$regras["revenda|cnpj"]["obrigatorio"]    = false;
$regras["revenda|estado"]["obrigatorio"]  = false;
$regras["revenda|cidade"]["obrigatorio"]  = false;

$auditoria_bloqueia_pedido = "true";
$antes_valida_campos       = "antes_valida_campos";

$auditorias = array(
    "auditoria_troca_obrigatoria",
    "auditoria_preco_peca_loja_mecanico",
    "auditoria_peca_critica_loja_mecanico",
    "auditoria_os_reincidente_loja_mecanico",
    "auditoria_pecas_excedentes_loja_mecanico",
    "auditoria_comprovante_garantia_loja_mecanico"
);


function antes_valida_campos() {
    global $campos, $con, $login_fabrica, $msg_erro, $os, $areaAdmin;
    
    $posto_id       = $campos["posto"]["id"];
    $produto_pecas  = $campos["produto_pecas"];
    $produto_id     = $campos["produto"]["id"];

    foreach ($produto_pecas as $key => $peca) {
        $peca_id = $peca["id"];

        if (!empty($peca_id)){
            $sql = "
                SELECT
                    tbl_posto_linha.linha,
                    tbl_posto_linha.posto,
                    tbl_tabela.fabrica,
                    tbl_tabela.tabela,
                    tbl_tabela_item.preco
                FROM tbl_produto
                JOIN tbl_posto_linha ON tbl_posto_linha.linha = tbl_produto.linha AND tbl_posto_linha.posto = {$posto_id}
                JOIN tbl_tabela ON tbl_tabela.tabela = tbl_posto_linha.tabela AND tbl_tabela.fabrica = {$login_fabrica}
                JOIN tbl_tabela_item ON tbl_tabela_item.tabela = tbl_tabela.tabela AND tbl_tabela_item.peca = {$peca_id}
                WHERE tbl_produto.fabrica_i = {$login_fabrica}
                AND tbl_produto.produto = {$produto_id}";
            $res = pg_query($con, $sql);
            
            if (pg_num_rows($res) > 0){
                $preco_peca = pg_fetch_result($res, 0, "preco");
            }else{
                $preco_peca = 0;
            }
            $campos["produto_pecas"][$key]['valor'] = $preco_peca;
        }
    }
}

function auditoria_preco_peca_loja_mecanico() {
    global $con, $os, $login_fabrica, $qtde_pecas, $auditoria_bloqueia_pedido;
    
    $auditoria_preco = false;
    $sql = "
        SELECT 
            SUM (x.item_preco) AS total_item,
            x.percentual_tolerante,
            x.preco
        FROM (
            SELECT
                (tbl_os_item.qtde * tbl_os_item.preco) AS item_preco,
                tbl_produto.preco,
                tbl_produto.parametros_adicionais::jsonb ->> 'percentual_tolerante' AS percentual_tolerante
            FROM tbl_os
            JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
            JOIN tbl_os_item ON tbl_os_item.os_produto = tbl_os_produto.os_produto
            JOIN tbl_produto ON tbl_produto.produto = tbl_os_produto.produto AND tbl_produto.fabrica_i = {$login_fabrica}
            JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.fabrica = {$login_fabrica}
            WHERE tbl_os.fabrica = {$login_fabrica}
            AND tbl_os.os = $os
            AND tbl_servico_realizado.troca_de_peca IS TRUE
            AND tbl_servico_realizado.gera_pedido IS TRUE
        ) x
        GROUP BY x.percentual_tolerante, x.preco";
    $res = pg_query($con, $sql);
    
    if(pg_num_rows($res) > 0){
        $total_item = pg_fetch_result($res, 0, 'total_item');
        $percentual_tolerante = pg_fetch_result($res, 0, 'percentual_tolerante');
        $preco = pg_fetch_result($res, 0, 'preco');

        $percentual_valida = ($total_item / $preco) * 100;

        if ($percentual_valida > $percentual_tolerante){
            $auditoria_preco = true;
        }

        if ($auditoria_preco == true){
            $busca = buscaAuditoria("tbl_auditoria_status.peca = 't'");

            if($busca['resultado']){
                $auditoria_status = $busca['auditoria'];
            }

            if (verifica_auditoria_unica("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%auditoria valor de pe%'", $os) === true) {
                $sql = "
                    INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                    VALUES ({$os}, {$auditoria_status}, 'OS em intervenção da fábrica por valor de peça ', {$auditoria_bloqueia_pedido});";
                $res = pg_query($con, $sql);

                if (strlen(pg_last_error()) > 0) {
                    throw new Exception("Erro ao lançar ordem de serviço #AUD001");
                }
            }
        }
    }
}

function auditoria_pecas_excedentes_loja_mecanico() {
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

/*function auditoria_peca_critica_loja_mecanico222() {
    global $con, $os, $login_fabrica, $qtde_pecas, $campos;
    
    $sql = "SELECT tbl_os_item.os_item
            FROM tbl_os_item
            INNER JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca
            INNER JOIN tbl_os_produto ON tbl_os_produto.os_produto = tbl_os_item.os_produto
            INNER JOIN tbl_servico_realizado ON tbl_servico_realizado.servico_realizado = tbl_os_item.servico_realizado AND tbl_servico_realizado.gera_pedido IS TRUE AND troca_de_peca IS TRUE
            WHERE tbl_os_produto.os = {$os}
            AND tbl_peca.peca_critica IS TRUE";
    $res = pg_query($con, $sql);

    die(var_dump([verifica_auditoria(array(62, 64), array(62), $os), verifica_peca_lancada('true')]));
    if (pg_num_rows($res) > 0 && verifica_auditoria(array(62, 64), array(62), $os) === true && verifica_peca_lancada('true') === true) {
        $sql = "INSERT INTO tbl_os_status
                (os, status_os, observacao)
                VALUES
                ({$os}, 62, 'OS em intervenção da fábrica por Peça Crí­tica')";
        $res = pg_query($con, $sql);

        if (strlen(pg_last_error()) > 0) {
            throw new Exception("Erro ao lançar ordem de serviço");
        }

        $busca = buscaAuditoria("tbl_auditoria_status.peca = 't'");

        if($busca['resultado']){
            $auditoria_status = $busca['auditoria'];
        }

        if (verifica_auditoria_unica("tbl_auditoria_status.peca = 't' AND tbl_auditoria_os.observacao ILIKE '%peça crí­tica%'", $os) === true) {

            $sql = "
                INSERT INTO tbl_auditoria_os (os, auditoria_status, observacao, bloqueio_pedido)
                VALUES ({$os}, {$auditoria_status}, 'OS em intervenção da fábrica por Peça Crí­tica', 't');
            ";
            $res = pg_query($con, $sql);

            if (strlen(pg_last_error()) > 0) {
                throw new Exception("Erro ao lançar ordem de serviço #AUD002");
            }

        }
    }
}
*/
function auditoria_os_reincidente_loja_mecanico() {
    global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;
    
    $posto = $campos['posto']['id'];
    $sql   = "SELECT  os
             FROM    tbl_os
             WHERE   fabrica         = {$login_fabrica}
             AND     os              = {$os}
             AND     os_reincidente  IS NOT TRUE
             AND     cancelada       IS NOT TRUE
     ";
    $res = pg_query($con, $sql);
    if(pg_num_rows($res) > 0){
        $consumidor_cpf = $campos['consumidor']['cpf'];
        $consumidor_cpf = str_replace(['-','.'], '', $consumidor_cpf);
        $nota_fiscal    = $campos['os']['nota_fiscal'];

        $select = "SELECT tbl_os.os
                    FROM tbl_os
                    INNER JOIN tbl_os_produto ON tbl_os_produto.os = tbl_os.os
                    WHERE tbl_os.fabrica = {$login_fabrica}
                    AND tbl_os.data_abertura  > (CURRENT_DATE - INTERVAL '90 days')
                    AND tbl_os.os             < {$os}
                    AND tbl_os.posto          = $posto
                    AND tbl_os.nota_fiscal    = '$nota_fiscal'
                    AND tbl_os.consumidor_cpf = '$consumidor_cpf'
                    AND tbl_os.excluida IS NOT TRUE
                LIMIT 1";
        $resSelect = pg_query($con, $select);

        if (pg_num_rows($resSelect) > 0 && verifica_auditoria(array(70, 19), array(19, 70), $os) === true) {
            $os_reincidente_numero = pg_fetch_result($resSelect, 0, "os");

            if (verifica_os_reincidente_finalizada($os_reincidente_numero)) {
                $insert = "INSERT INTO tbl_os_status
                        (os, status_os, observacao)
                        VALUES
                        ({$os}, 70, 'OS reincidente de cpf e nota fiscal no período de 90 dias.')";
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

function auditoria_comprovante_garantia_loja_mecanico() {
    global $con, $login_fabrica, $os, $campos, $os_reincidente, $os_reincidente_numero;

    $sql = "INSERT INTO tbl_auditoria_os(os, auditoria_status, observacao)
            VALUES ({$os}, 6, 'OS em auditoria de Comprovante de Garantia');";
    $res = pg_query($con, $sql);

    if (strlen(pg_last_error()) > 0) {
        throw new Exception("Erro ao lançar ordem de serviço #AUD-CGR-001");
    }
}

function auditoria_peca_critica_loja_mecanico(){
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