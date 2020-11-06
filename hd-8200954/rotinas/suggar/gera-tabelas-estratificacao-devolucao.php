<?php
/**
 * gera-tabelas-estratificacao_v2.php
 *
 * Gera as tabelas usadas no Relatуrio de Estratificaзгo v201505
 *
 */

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

$fabrica = 24; //Suggar

$erro='OK';


$sqlOSSerie = " SELECT tbl_os_laudo.fabrica,
tbl_os_laudo.os_laudo,
tbl_os_laudo.data_recebimento,
to_char(tbl_os_laudo.data_recebimento,'YYYY-MM') AS data_mes_ano,
tbl_os_laudo.serie,
tbl_os_laudo.data_nf,
tbl_os_laudo.produto,
tbl_os_laudo.defeito_constatado,
tbl_os_laudo.solucao,
tbl_produto.familia,
CASE WHEN substr(tbl_os_laudo.serie,length(tbl_os_laudo.serie) - 1, 2) = '04' THEN
FALSE
ELSE
TRUE
END AS matriz
INTO suggar_os_serie_devolucao
FROM tbl_os_laudo
JOIN tbl_produto ON tbl_os_laudo.produto = tbl_produto.produto
WHERE tbl_os_laudo.fabrica = {$fabrica}
AND tbl_produto.fabrica_i = {$fabrica}
AND tbl_os_laudo.data_nf IS NOT NULL ";

$qry = pg_query($con, $sqlOSSerie);

if (!pg_last_error($con)) {
    $msg = 'TABELA suggar_os_serie_devolucao foi atualizada com sucesso';
} else {
    $msg = 'TABELA suggar_os_serie_devolucao nгo foi atualizada: ' . pg_last_error($con);
    $erro='ERRO';
}

if ($erro=='OK') {
    $sqlNS1 = " SELECT  tbl_numero_serie.numero_serie,
                        tbl_numero_serie.serie,
                        tbl_numero_serie.produto,
                        tbl_numero_serie.data_fabricacao,
                        to_char(tbl_numero_serie.data_fabricacao,'YYYY-MM') AS data_mes_ano,
                        tbl_produto.familia
                    INTO suggar_numero_serie_devolucao
                    FROM tbl_numero_serie JOIN tbl_produto using(produto) 
                    WHERE fabrica = {$fabrica} AND fabrica_i = {$fabrica}; ";
    $qry = pg_query($con, $sqlNS1);

    if (!pg_last_error($con)) {
        $msg = 'TABELA suggar_numero_serie_devolucao foi atualizada com sucesso';
    } else {
        $msg = 'TABELA suggar_numero_serie_devolucao nгo foi atualizada: ' . pg_last_error($con);
        $erro='ERRO';
    }
}

if ($erro=='OK') {
    
    $drops = "  DROP TABLE IF EXISTS suggar_os_devolucao_serie;
                DROP TABLE IF EXISTS suggar_devolucao_numero_serie; ";
    $qry = pg_query($con, $drops);

    if (!pg_last_error($con)) {
        
        $alter = "ALTER TABLE suggar_os_serie_devolucao RENAME TO suggar_os_devolucao_serie;
                  ALTER TABLE suggar_numero_serie_devolucao RENAME TO suggar_devolucao_numero_serie; ";
        
        $qry = pg_query($con, $alter);

        //retirado 
        //CREATE INDEX idx_suggar_oss_po ON suggar_os_serie (posto);
        //CREATE INDEX idx_suggar_oss_cnpj ON suggar_os_serie (cnpj);

        $index = "  CREATE INDEX idx_suggar_oss_da_devolucao ON suggar_os_devolucao_serie (data_recebimento);
                    CREATE INDEX idx_suggar_oss_s_devolucao ON suggar_os_devolucao_serie (serie);
                    CREATE INDEX idx_suggar_oss_pr_devolucao ON suggar_os_devolucao_serie (produto);
                    CREATE INDEX idx_suggar_oss_d_devolucao ON suggar_os_devolucao_serie (defeito_constatado);
                    CREATE INDEX idx_suggar_oss_oss_fb_devolucao ON suggar_os_devolucao_serie (fabrica);
                    CREATE INDEX idx_suggar_oss_fm_devolucao ON suggar_os_devolucao_serie (familia);
                    CREATE INDEX idx_suggar_ns_ns_devolucao ON suggar_devolucao_numero_serie (numero_serie);
                    CREATE INDEX idx_suggar_ns_s_devolucao ON suggar_devolucao_numero_serie (serie);
                    CREATE INDEX idx_suggar_ns_p_devolucao ON suggar_devolucao_numero_serie (produto);
                    CREATE INDEX idx_suggar_ns_df_devolucao ON suggar_devolucao_numero_serie (data_fabricacao);
                    CREATE INDEX idx_suggar_ns_fm_devolucao ON suggar_devolucao_numero_serie (familia);";
        
        $qry = pg_query($con, $index);

        if (!pg_last_error($con)) {
            $to = 'helpdesk@telecontrol.com.br';
            $subj = '[Suggar] ';
            $msg = 'A base BI foi atualizada com sucesso';
            mail($to, $subj, $msg);
        }else{
            $msg = 'ERRO AO ALTERAR TABELA ' . pg_last_error($con);
            $to = 'helpdesk@telecontrol.com.br';
            $subj = '[ERRO Suggar] ';
            $msg = $msg . ' - A base BI nгo foi atualizada: ' . pg_last_error($con);
            mail($to, $subj, $msg);
        }
    }else{
        $msg = 'ERRO AO DROPAR TABELA ' . pg_last_error($con);
        $to = 'helpdesk@telecontrol.com.br';
        $subj = '[ERRO Suggar] ';
        $msg = $msg . ' - A base BI nгo foi atualizada: ' . pg_last_error($con);
    }
    }else{
        $to = 'helpdesk@telecontrol.com.br';
        $subj = '[ERRO Suggar] ';
        $msg = $msg . ' - A base BI nгo foi atualizada: ' . pg_last_error($con);
    }

mail($to, $subj, $msg);

echo $msg . "\n";
