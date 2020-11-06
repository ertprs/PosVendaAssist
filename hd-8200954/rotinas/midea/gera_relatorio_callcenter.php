<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';


$fabrica = 169;

$date = new DateTime();
$verificaHora = $date->format('H');
$verificaData = $date->format('Y-m-d');

$dia_anterior = $date->sub(new DateInterval('P1D'));

if ($verificaHora > 12) {
    $dataHoraInicial    = $verificaData." 06:00:00";
    $dataHoraFinal      = $verificaData." 11:59:59";
} else {
    $dataHoraInicial    = $dia_anterior->format('Y-m-d')." 12:00:00";
    $dataHoraFinal      = $dia_anterior->format('Y-m-d')." 23:59:59";
}
$sql = "
		select hd_chamado
		into temp tmp_callcenter
		from tbl_hd_chamado
		join tbl_hd_chamado_item using(hd_chamado)
		where fabrica_responsavel = $fabrica
		and tbl_hd_chamado_item.data  BETWEEN '$dataHoraInicial' and '$dataHoraFinal';

        SELECT  DISTINCT
                tbl_hd_chamado.hd_chamado,
                to_char(tbl_hd_chamado.data,'DD/MM/YYYY')   AS data_abertura,
                tbl_hd_chamado_extra.nome                   AS nome_cliente,
                tbl_hd_chamado_extra.cpf                    AS cpf_cliente,
                REPLACE(tbl_hd_chamado_extra.email,';',',')                  AS email_cliente,
                tbl_hd_chamado_extra.fone                   AS fone_cliente,
                tbl_hd_chamado_extra.celular                AS fone2,
                tbl_cidade.nome                             AS cidade,
                tbl_cidade.estado,
                tbl_hd_chamado_extra.dias_aberto,
                tbl_hd_chamado_extra.dias_ultima_interacao,
                tbl_hd_chamado_extra.origem,
                CASE
                    WHEN tbl_hd_chamado_extra.consumidor_revenda = 'C'
                    THEN 'Consumidor'
                    ELSE 'Revenda'
                END                                                         AS tipo,

                CASE
                    WHEN tbl_hd_chamado_extra.defeito_reclamado IS NULL
                    THEN tbl_os.defeito_reclamado
                    ELSE tbl_hd_chamado_extra.defeito_reclamado
                END                                                         AS defeito_reclamado,

                CASE
                    WHEN tbl_hd_chamado_extra.defeito_reclamado_descricao IS NULL
                        OR LENGTH(tbl_hd_chamado_extra.defeito_reclamado_descricao) = 0
                    THEN REPLACE(tbl_os.defeito_reclamado_descricao, ';',',')
                    ELSE REPLACE(tbl_hd_chamado_extra.defeito_reclamado_descricao,';',',')
                END                                                         AS defeito_reclamado_descricao,

                TPE.referencia
                                  AS referencia_produto,

                 REPLACE(TPE.descricao,';',',')
                                                                      AS descricao_produto,

                TO_CHAR(tbl_hd_chamado.data_providencia,'DD/MM/YYYY')       AS data_providencia,
                REPLACE(tbl_hd_motivo_ligacao.descricao,';',',')                             AS providencia,
                UPPER(AB.login) AS login_abertura,
                UPPER(AA.login) AS login_atendente,
                (
                    SELECT  UPPER(AUI.login)
                    FROM    tbl_hd_chamado_item HDI
                    JOIN    tbl_admin AUI USING(admin)
                    WHERE   HDI.hd_chamado = tbl_hd_chamado.hd_chamado
            ORDER BY      HDI.hd_chamado_item DESC
                    LIMIT   1
                )                                                           AS login_ultima_interacao,
                (
                    SELECT  TO_CHAR(HDIDT.data,'DD/MM/YYYY')
                    FROM    tbl_hd_chamado_item HDIDT
                    WHERE   HDIDT.hd_chamado = tbl_hd_chamado.hd_chamado
            ORDER BY      HDIDT.hd_chamado_item DESC
                    LIMIT   1
                )                                                           AS data_ultima_interacao,
                (
                    SELECT  TO_CHAR(HDID.data,'DD/MM/YYYY')
                    FROM    tbl_hd_chamado_item HDID
                    WHERE   HDID.hd_chamado = tbl_hd_chamado.hd_chamado
                    AND     HDID.status_item = 'Resolvido'
            ORDER BY      HDID.hd_chamado_item DESC
                    LIMIT   1
                )                                                           AS data_finalizado,
                (
                    SELECT  UPPER(AUIF.login)
                    FROM    tbl_hd_chamado_item HDIF
                    JOIN    tbl_admin AUIF USING(admin)
                    WHERE   HDIF.hd_chamado = tbl_hd_chamado.hd_chamado
                    AND     HDIF.status_item = 'Resolvido'
            ORDER BY      HDIF.hd_chamado_item DESC
                    LIMIT   1
                )                                                           AS login_finalizado,
                (
                    SELECT  COUNT(CI.hd_chamado_item)
                    FROM    tbl_hd_chamado_item CI
                    WHERE   CI.hd_chamado = tbl_hd_chamado.hd_chamado
                )                                                           AS total_interacoes,
                tbl_hd_chamado_extra.os                   AS os,
                tbl_posto_fabrica.codigo_posto,
                TO_CHAR(tbl_os.data_digitacao,'DD/MM/YYYY')                 AS data_digitacao,
                tbl_hd_chamado.status                                       AS situacao,
                tbl_hd_classificacao.descricao                              AS classificacao,
                (
                    SELECT  gerar_pedido
                    FROM    tbl_os_troca
                    WHERE   tbl_os_troca.os IN (tbl_hd_chamado_extra.os)
              ORDER BY      os_troca DESC
                    LIMIT   1
                )                                                           AS gerar_pedido,
                (
                    SELECT  TO_CHAR(tbl_os_troca.data,'DD/MM/YYYY')
                    FROM    tbl_os_troca
                    WHERE   tbl_os_troca.os IN (tbl_hd_chamado_extra.os)
              ORDER BY      os_troca DESC
                    LIMIT   1
                )                                                           AS data_troca_recompra,
                (
                    SELECT  tbl_causa_troca.descricao
                    FROM    tbl_os_troca
                    JOIN    tbl_causa_troca USING(causa_troca)
                    WHERE   tbl_os_troca.os in (tbl_hd_chamado_extra.os)
              ORDER BY      os_troca DESC
                    LIMIT   1
                )                                                           AS motivo,
                tbl_os_item.pedido,
                (
                    SELECT  ARRAY_TO_STRING(ARRAY_AGG(referencia || ' - ' || descricao || ' | ' || qtde), ',')
                    FROM    (
                        SELECT  referencia,
                                tbl_peca.descricao,
                                tbl_os_item.qtde
                        FROM    tbl_os,
                                tbl_os_produto,
                                tbl_os_item,
                                tbl_peca
                        WHERE   (
                                        tbl_os.os               IN ( tbl_hd_chamado_extra.os)
                                AND     tbl_os_produto.os       = tbl_os.os
                                AND     tbl_os_item.os_produto  = tbl_os_produto.os_produto
                                )
                        AND     tbl_os_item.peca = tbl_peca.peca
                    UNION
                        SELECT  referencia,
                                descricao,
                                qtde
                        FROM    tbl_pedido_item,
                                tbl_peca
                        WHERE   (
                                    (
                                        tbl_pedido_item.pedido  = tbl_hd_chamado_extra.pedido
                                    )
                                )
                        AND     tbl_pedido_item.peca            = tbl_peca.peca
                    ) x
                )                                                           AS itens,
                tbl_ressarcimento.valor_original                            AS valor_ressarcimento,
                tbl_ressarcimento.autorizacao_pagto,
                TO_CHAR(tbl_ressarcimento.previsao_pagamento, 'DD/MM/YYYY') AS previsao_pagamento,
                TO_CHAR(tbl_ressarcimento.finalizado,         'DD/MM/YYYY') AS data_pagamento,
                tbl_faturamento.nota_fiscal,
                tbl_faturamento.conhecimento,
                TO_CHAR(tbl_faturamento.saida,   'DD/MM/YYYY')              AS saida,
                TO_CHAR(tbl_faturamento.emissao, 'DD/MM/YYYY')              AS emissao_nota
        FROM    tbl_hd_chamado
        JOIN    tbl_hd_chamado_extra ON tbl_hd_chamado_extra.hd_chamado = tbl_hd_chamado.hd_chamado
        JOIN    tbl_hd_classificacao    ON  tbl_hd_classificacao.hd_classificacao       = tbl_hd_chamado.hd_classificacao
                                        AND tbl_hd_classificacao.fabrica                = tbl_hd_chamado.fabrica_responsavel
        JOIN    tbl_admin AA            ON  AA.admin                                    = tbl_hd_chamado.atendente
                                        AND AA.fabrica                                  = tbl_hd_chamado.fabrica_responsavel
        JOIN    tbl_admin AB            ON  AB.admin                                    = tbl_hd_chamado.admin
                                        AND AB.fabrica                                  = tbl_hd_chamado.fabrica_responsavel
        JOIN    tbl_hd_motivo_ligacao   ON  tbl_hd_motivo_ligacao.hd_motivo_ligacao     = tbl_hd_chamado_extra.hd_motivo_ligacao
                                        AND tbl_hd_motivo_ligacao.fabrica               = tbl_hd_chamado.fabrica_responsavel
        JOIN    tbl_cidade              ON  tbl_cidade.cidade                           = tbl_hd_chamado_extra.cidade
   LEFT JOIN    tbl_produto TPE         ON  TPE.produto                                 = tbl_hd_chamado_extra.produto
                                        AND TPE.fabrica_i                               = tbl_hd_chamado.fabrica_responsavel
   LEFT JOIN    tbl_os                  ON  tbl_hd_chamado_extra.os   = tbl_os.os
                                        AND tbl_os.fabrica                              = tbl_hd_chamado.fabrica_responsavel
                                        AND tbl_os.excluida                             IS NOT TRUE
   LEFT JOIN    tbl_posto_fabrica       ON  tbl_posto_fabrica.posto                     = tbl_os.posto
                                        AND tbl_posto_fabrica.fabrica                   = tbl_hd_chamado.fabrica_responsavel
   LEFT JOIN    tbl_os_produto          ON  tbl_os_produto.os                           = tbl_os.os
   LEFT JOIN    tbl_os_item             ON  tbl_os_item.os_produto                      = tbl_os_produto.os_produto
   LEFT JOIN    tbl_pedido_item         ON  tbl_pedido_item.pedido_item             = tbl_os_item.pedido_item
   LEFT JOIN    tbl_pedido              ON  tbl_pedido.pedido                           = tbl_pedido_item.pedido
                                        AND tbl_pedido.fabrica                          = $fabrica
   LEFT JOIN    tbl_peca                ON  tbl_peca.peca                               = tbl_pedido_item.peca
                                        AND tbl_peca.fabrica                            = $fabrica
   LEFT JOIN    tbl_ressarcimento       ON  tbl_ressarcimento.os                        = tbl_os.os
                                        AND tbl_ressarcimento.fabrica                   = tbl_hd_chamado.fabrica
   LEFT JOIN    tbl_faturamento_item    ON  tbl_faturamento_item.pedido_item                 = tbl_os_item.pedido_item
   LEFT JOIN    tbl_faturamento         ON  tbl_faturamento.faturamento                 = tbl_faturamento_item.faturamento
                                        AND tbl_faturamento.fabrica                     = tbl_hd_chamado.fabrica
        WHERE   tbl_hd_chamado.fabrica_responsavel = $fabrica
        AND     (
                    tbl_hd_chamado.data BETWEEN '$dataHoraInicial' and '$dataHoraFinal'
                OR  tbl_hd_chamado.hd_chamado IN (
                        SELECT  hd_chamado
                        FROM    tmp_callcenter
                    )
                )
       ";

    $resSubmit = pg_query($con, $sql);

    if (!pg_last_error($con)) {
        $fileName = "relatorio_geral-atendimentos-{$fabrica}-$verificaData-{$verificaHora}.csv";

        //$file = fopen("xls/{$fileName}", "w");
        $file = fopen("/home/midea/telecontrol-midea/{$fileName}", "w");

        $thead = "Protocolo;Ação;Atendente Abertura;Atendente Atual;Data Abertura;Ultima Interação;Dias em Aberto;Dias Última Interção;Última interação;Nome Cliente;CPF / CNPJ;E-mail Cliente;Telefone Cliente;Telefone 1;UF;Cidade;Origem;Tipo;Defeito Reclamado;Defeito Reclamado Combo;Referência;Descrição Produto;Situação;Classificação;Providência Tomada;Data Retorno;Data Finalizado;Finalizado Por;OS relacionada;OS Data Digitação;Posto Aut.;Recompra / Troca;Nr. Pedido;Motivo;Itens;Dta. Recompra / Troca;Valor;Correção;Indenização;Multa;Outros;Valor Total;NF;Data Nota;Data de Saída Troca;SPD;DATA Venc;DATA Pagamento;Numero Rastreamento1;Numero Rastreamento2;Numero Rastreamento3;Numero Rastreamento4;Numero Rastreamento5;Numero Rastreamento6;Numero Rastreamento7;Numero Rastreamento8;\n";
        fwrite($file, $thead);

        if (pg_num_rows($resSubmit) > 0) {
            while ($campos = pg_fetch_object($resSubmit)) {
                if (strlen($campos->defeito_reclamado) > 0) {
                    $sql_df = "SELECT descricao FROM tbl_defeito_reclamado WHERE fabrica = $fabrica AND defeito_reclamado = ".$campos->defeito_reclamado;
                    $res_df = pg_query($con,$sql_df);
                    if (pg_num_rows($res_df) > 0) {
                        $defeito_reclamado_combo = pg_fetch_result($res_df, 0, descricao);
                    }
                }

                $ressar_troca = ($campos->gerar_pedido == "t") ? "TROCA" : "";
                $ressar_troca = ($campos->valor_ressarcimento > 0) ? "RESSARCIMENTO" : $ressar_troca;

                $acao = ($campos->hd_chamado != $hd_chamado_anterior) ? 1 : $acao + 1;

                $hd_chamado_anterior = $campos->hd_chamado;

                if (!empty($campos->pedido)) {
                    if ($campos->pedido != $pedido_anterior AND empty($item)) {
                        $item = 1;
                    } else if($referencia_item != $referencia_item_anterior) {
                        $item++;
                    } else {
                        $item = $item;
                    }
                    $pedido_anterior = $campos->pedido;
                    $referencia_item_anterior = $referencia_item;
                } else {
                    $item = "";
                }

                $itens  = explode(",",$campos->itens);
                $tbItem = implode(" - ", $itens);

                $body = $campos->hd_chamado.";"
                    .$acao.";"
                    .$campos->login_abertura.";"
                    .$campos->login_atendente.";"
                    .$campos->data_abertura.";"
                    .$campos->login_ultima_interacao.";"
                    .$campos->dias_aberto.";"
                    .$campos->dias_ultima_interacao.";"
                    .$campos->data_ultima_interacao.";"
                    .$campos->nome_cliente.";"
                    .$campos->cpf_cliente.";"
                    .$campos->email_cliente.";"
                    .$campos->fone_cliente.";"
                    .$campos->fone2.";"
                    .$campos->estado.";"
                    .$campos->cidade.";"
                    .$campos->origem.";"
                    .$campos->tipo.";"
                    .$campos->defeito_reclamado_descricao.";"
                    .$defeito_reclamado_combo.";"
                    .$campos->referencia_produto.";"
                    .$campos->descricao_produto.";"
                    .$campos->situacao.";"
                    .$campos->classificacao.";"
                    .$campos->providencia.";"
                    .$campos->data_providencia.";"
                    .$campos->data_finalizado.";"
                    .$campos->login_finalizado.";"
                    .$campos->os.";"
                    .$campos->data_digitacao.";"
                    .$campos->codigo_posto.";"
                    .$ressar_troca.";"
                    .$campos->pedido.";"
                    .$campos->motivo.";"
                    .$tbItem.";"
                    .$campos->data_troca_recompra.";"
                    .$campos->valor_ressarcimento.";;;;;"
                    .$campos->valor_ressarcimento.";"
                    .$campos->nota_fiscal.";"
                    .$campos->emissao_nota.";"
                    .$campos->saida.";"
                    .$campos->autorizacao_pagto.";"
                    .$campos->previsao_pagamento.";"
                    .$campos->data_pagamento;

                if (strlen($campos->conhecimento) == 0) {
                    $codigo_rastreio = array();
                } else if (preg_match("/^\[.+\]$/", $campos->conhecimento)) {
                    $codigo_rastreio = json_decode($campos->conhecimento,true);
                } else {
                    $codigo_rastreio = array();
                    $codigo_rastreio[] = $campos->conhecimento;
                }

                for($x = 0; $x < 8; $x++){
                    $body .= ";".$codigo_rastreio[$x];
                }

                $body .= "\n";

                fwrite($file, $body);
            }

            fclose($file);
        }
    }
