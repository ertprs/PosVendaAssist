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
    $drops = "  DROP TABLE IF EXISTS suggar_os_serie_novo;
                DROP TABLE IF EXISTS suggar_numero_serie_novo; ";
    $qry = pg_query($con, $drops);

$sqlOSSerie = " SELECT  fabrica,os,
                        data_abertura,
                        to_char(data_abertura,'YYYY-MM') AS data_mes_ano,
                        serie,
                        data_nf,
                        tbl_os.produto,
                        defeito_constatado,
                        solucao_os,
                        posto,
                        tbl_os.revenda_cnpj AS cnpj,
                        familia,
                        CASE WHEN substr(tbl_os.serie,length(tbl_os.serie) - 1, 2) = '04' THEN
                         FALSE
                        ELSE
                         TRUE
                        END AS matriz
                    INTO suggar_os_serie_novo
                    FROM tbl_os 
                        JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto 
                    WHERE fabrica = {$fabrica} AND fabrica_i = {$fabrica} AND data_nf IS NOT NULL ; ";

$qry = pg_query($con, $sqlOSSerie);

if (!pg_last_error($con)) {
    $msg = 'TABELA suggar_os_serie_novo foi atualizada com sucesso';
} else {
    $msg = 'TABELA suggar_os_serie_novo nгo foi atualizada: ' . pg_last_error($con);
    $erro='ERRO';
}

if ($erro=='OK') {
    $sqlNS1 = " SELECT  tbl_numero_serie.numero_serie,
                        tbl_numero_serie.serie,
                        tbl_numero_serie.produto,
                        tbl_numero_serie.data_fabricacao,
                        to_char(tbl_numero_serie.data_fabricacao,'YYYY-MM') AS data_mes_ano,
                        tbl_produto.familia
                    INTO suggar_numero_serie_novo
                    FROM tbl_numero_serie JOIN tbl_produto using(produto) 
                    WHERE fabrica = {$fabrica} AND fabrica_i = {$fabrica}; ";
    $qry = pg_query($con, $sqlNS1);

    if (!pg_last_error($con)) {
        $msg = 'TABELA suggar_numero_serie_novo foi atualizada com sucesso';
    } else {
        $msg = 'TABELA suggar_numero_serie_novo nгo foi atualizada: ' . pg_last_error($con);
        $erro='ERRO';
    }
}

if ($erro=='OK') {
    
    $drops = "  DROP TABLE IF EXISTS suggar_os_serie;
                DROP TABLE IF EXISTS suggar_numero_serie; ";
    $qry = pg_query($con, $drops);

    if (!pg_last_error($con)) {
        $alter = "ALTER TABLE suggar_os_serie_novo RENAME TO suggar_os_serie;
                  ALTER TABLE suggar_numero_serie_novo RENAME TO suggar_numero_serie; ";
        
        $qry = pg_query($con, $alter);

        $index = "  CREATE INDEX idx_suggar_oss_da ON suggar_os_serie (data_abertura);
                    CREATE INDEX idx_suggar_oss_s ON suggar_os_serie (serie);
                    CREATE INDEX idx_suggar_oss_pr ON suggar_os_serie (produto);
                    CREATE INDEX idx_suggar_oss_d ON suggar_os_serie (defeito_constatado);
                    CREATE INDEX idx_suggar_oss_po ON suggar_os_serie (posto);
                    CREATE INDEX idx_suggar_oss_cnpj ON suggar_os_serie (cnpj);
                    CREATE INDEX idx_suggar_oss_oss_fb ON suggar_os_serie (fabrica);
                    CREATE INDEX idx_suggar_oss_fm ON suggar_os_serie (familia);
                    CREATE INDEX idx_suggar_ns_ns ON suggar_numero_serie (numero_serie);
                    CREATE INDEX idx_suggar_ns_s ON suggar_numero_serie (serie);
                    CREATE INDEX idx_suggar_ns_p ON suggar_numero_serie (produto);
                    CREATE INDEX idx_suggar_ns_df ON suggar_numero_serie (data_fabricacao);
                    CREATE INDEX idx_suggar_ns_fm ON suggar_numero_serie (familia);";
        
        $qry = pg_query($con, $index);

        if (!pg_last_error($con)) {
            $to = 'francisco.ambrozio@telecontrol.com.br, thiago.tobias@telecontrol.com.br';
            $subj = '[Suggar] ';
            $msg = 'A base BI foi atualizada com sucesso';
            #mail($to, $subj, $msg);
        }else{
            $msg = 'ERRO AO ALTERAR TABELA ' . pg_last_error($con);
            $to = 'francisco.ambrozio@telecontrol.com.br, thiago.tobias@telecontrol.com.br';
            $subj = '[ERRO Suggar] ';
            $msg = $msg . ' - A base BI nгo foi atualizada: ' . pg_last_error($con);
            #mail($to, $subj, $msg);
        }
    }else{
        $msg = 'ERRO AO DROPAR TABELA ' . pg_last_error($con);
        $to = 'francisco.ambrozio@telecontrol.com.br, thiago.tobias@telecontrol.com.br';
        $subj = '[ERRO Suggar] ';
        $msg = $msg . ' - A base BI nгo foi atualizada: ' . pg_last_error($con);
    }
    }else{
        $to = 'francisco.ambrozio@telecontrol.com.br, thiago.tobias@telecontrol.com.br';
        $subj = '[ERRO Suggar] ';
        $msg = $msg . ' - A base BI nгo foi atualizada: ' . pg_last_error($con);
    }

#mail($to, $subj, $msg);

echo $msg . "\n";
