<?php
/**
 * gera-tabelas-estratificacao_v2.php
 *
 * Gera as tabelas usadas no Relatório de Estratificação v201505
 *
 */

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

$fabrica = 201;

$erro='OK';

$sqlOSSerie = " SELECT  fabrica,os,
                        data_abertura,
                        to_char(data_abertura,'YYYY-MM') AS data_mes_ano,
                        serie,
                        data_nf,
                        tbl_os.produto,
                        defeito_constatado,
                        posto,
                        tbl_os.revenda_cnpj AS cnpj,
                        familia
                    INTO newup_os_serie_novo
                    FROM tbl_os 
                        JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto 
                    WHERE fabrica = {$fabrica} AND fabrica_i = {$fabrica} AND data_nf IS NOT NULL ; ";

$qry = pg_query($con, $sqlOSSerie);

if (!pg_last_error($con)) {
    $msg = 'TABELA newup_os_serie_novo foi atualizada com sucesso';
} else {
    $msg = 'TABELA newup_os_serie_novo não foi atualizada: ' . pg_last_error($con);
    $erro='ERRO';
}
    
echo $msg . "\n";

if ($erro=='OK') {
    $sqlNS1 = " SELECT  tbl_numero_serie.numero_serie,
                        tbl_numero_serie.serie,
                        tbl_numero_serie.produto,
                        tbl_numero_serie.data_fabricacao,
                        to_char(tbl_numero_serie.data_fabricacao,'YYYY-MM') AS data_mes_ano,
                        tbl_produto.familia
                    INTO newup_numero_serie_novo
                    FROM tbl_numero_serie JOIN tbl_produto using(produto) 
                    WHERE fabrica = {$fabrica} AND fabrica_i = {$fabrica}; ";

    $qry = pg_query($con, $sqlNS1);

    if (!pg_last_error($con)) {
        $msg = 'TABELA newup_numero_serie_novo foi atualizada com sucesso';
    } else {
        $msg = 'TABELA newup_numero_serie_novo não foi atualizada: ' . pg_last_error($con);
        $erro='ERRO';
    }
}

echo $msg . "\n";

if ($erro<>'OK') {
    $to = 'francisco.ambrozio@telecontrol.com.br, thiago.tobias@telecontrol.com.br';
    $subj = '[ERRO newup] ';
    $msg = $msg . ' - A base BI não foi atualizada: ' . pg_last_error($con);
}else{
    $drops = "  DROP TABLE IF EXISTS newup_os_serie;
                DROP TABLE IF EXISTS newup_numero_serie; ";
    $qry = pg_query($con, $drops);

    if (!pg_last_error($con)) {
        $alter = "ALTER TABLE newup_os_serie_novo RENAME TO newup_os_serie;
                  ALTER TABLE newup_numero_serie_novo RENAME TO newup_numero_serie; ";
        
        $qry = pg_query($con, $alter);

        $index = "CREATE INDEX idx_newup_oss_da ON newup_os_serie (data_abertura);
                  CREATE INDEX idx_newup_oss_s ON newup_os_serie (serie);
                  CREATE INDEX idx_newup_oss_pr ON newup_os_serie (produto);
                  CREATE INDEX idx_newup_oss_d ON newup_os_serie (defeito_constatado);
                  CREATE INDEX idx_newup_oss_po ON newup_os_serie (posto);
                  CREATE INDEX idx_newup_oss_cnpj ON newup_os_serie (cnpj);
                  CREATE INDEX idx_newup_oss_fb ON newup_os_serie (fabrica);
                  CREATE INDEX idx_newup_oss_fm ON newup_os_serie (familia); 
                  CREATE INDEX idx_newup_ns_ns ON newup_numero_serie (numero_serie);
                  CREATE INDEX idx_newup_ns_s ON newup_numero_serie (serie);
                  CREATE INDEX idx_newup_ns_pr ON newup_numero_serie (produto);
                  CREATE INDEX idx_newup_ns_df ON newup_numero_serie (data_fabricacao);
                  CREATE INDEX idx_newup_ns_fm ON newup_numero_serie (familia);";
        
        $qry = pg_query($con, $index);

        if (!pg_last_error($con)) {
            $to = 'francisco.ambrozio@telecontrol.com.br, thiago.tobias@telecontrol.com.br';
            $subj = '[newup] ';
            $msg = 'A base BI foi atualizada com sucesso';
        }else{
            $msg = 'ERRO AO ALTERAR TABELA ' . pg_last_error($con);
            $to = 'francisco.ambrozio@telecontrol.com.br, thiago.tobias@telecontrol.com.br';
            $subj = '[ERRO newup] ';
            $msg = $msg . ' - A base BI não foi atualizada: ' . pg_last_error($con);
        }
    }else{
        $msg = 'ERRO AO DROPAR TABELA ' . pg_last_error($con);
        $to = 'francisco.ambrozio@telecontrol.com.br, thiago.tobias@telecontrol.com.br';
        $subj = '[ERRO newup] ';
        $msg = $msg . ' - A base BI não foi atualizada: ' . pg_last_error($con);
        
    }
}

#mail($to, $subj, $msg);

echo $msg . "\n";
