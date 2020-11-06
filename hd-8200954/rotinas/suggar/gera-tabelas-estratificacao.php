<?php
/**
 * gera-tabelas-estratificacao.php
 *
 * Gera as tabelas usadas no Relatório de Estratificação
 *
 */


include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

$fabrica = 24;

$sqlFamilias = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = {$fabrica} and ativo ";
$qryFamilias = pg_query($con, $sqlFamilias);

while ($fetch = pg_fetch_assoc($qryFamilias)) {
    $familia = $fetch['familia'];
    $descricao = $fetch['descricao'];

    $drops = "DROP TABLE IF EXISTS suggar_os_serie_{$familia};
              DROP TABLE IF EXISTS suggar_numero_serie1_{$familia};
              DROP TABLE IF EXISTS suggar_numero_serie2_{$familia};
              DROP TABLE IF EXISTS suggar_numero_serie_{$familia}; ";
    $qry = pg_query($con, $drops);

    $suffix = $fabrica . '_' . $familia;

    $sqlOSSerie = "SELECT os,
                        data_abertura, 
                        data_nf,
                        serie, 
                        tbl_os.produto,
                        defeito_constatado,
                        posto,
                        CASE
                        WHEN substr(tbl_os.serie,length(tbl_os.serie) - 1, 2) = '02' THEN
                        TRUE
                        ELSE
                        FALSE
                        END AS matriz

                    INTO suggar_os_serie_{$familia}
                    FROM tbl_os JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto 
                    WHERE fabrica = {$fabrica} AND familia = {$familia} AND fabrica_i = {$fabrica} AND data_nf IS NOT NULL;

                    CREATE INDEX idx_ossda_{$suffix} ON suggar_os_serie_{$familia}(data_abertura);
                    CREATE INDEX idx_ossdnf_{$suffix} ON suggar_os_serie_{$familia}(data_nf);
                    CREATE INDEX idx_osss_{$suffix} ON suggar_os_serie_{$familia}(serie);
                    CREATE INDEX idx_osspr_{$suffix} ON suggar_os_serie_{$familia}(produto);
                    CREATE INDEX idx_ossd_{$suffix} ON suggar_os_serie_{$familia}(defeito_constatado); 
                    CREATE INDEX idx_osspo_{$suffix} ON suggar_os_serie_{$familia}(posto);";
    
    $sqlNS = "SELECT numero_serie,
                        serie,
                        produto,
                        data_fabricacao,
                        CASE
                        WHEN substr(tbl_numero_serie.serie,length(tbl_numero_serie.serie) - 1, 2) = '02' THEN
                        TRUE
                        ELSE
                        FALSE
                        END AS matriz
                    INTO suggar_numero_serie_{$familia}
                    FROM tbl_numero_serie JOIN tbl_produto using(produto) 
                    WHERE fabrica = {$fabrica} AND familia = {$familia} AND fabrica_i = {$fabrica};

                    CREATE INDEX idx_nsns_{$suffix} ON suggar_numero_serie_{$familia}(numero_serie);
                    CREATE INDEX idx_nss_{$suffix} ON suggar_numero_serie_{$familia}(serie);
                    CREATE INDEX idx_nsp_{$suffix} ON suggar_numero_serie_{$familia}(produto);";

    $qry = pg_query($con, $sqlOSSerie . $sqlNS);

    if (!pg_last_error($con)) {
        $to = 'claudio.ramos@suggar.com.br';
        $subj = '';
        $msg = 'A base BI da familia ' . $descricao . ' foi atualizada com sucesso';
    } else {
        $to = 'francisco.ambrozio@telecontrol.com.br';
        $subj = '[ERRO Suggar] ';
        $msg = 'A base BI da familia ' . $descricao . ' não foi atualizada: ' . pg_last_error($con);
    }

    $subj.= 'Atualização BI - Relatório de Estratificação - ' . $descricao ;
    
    mail($to, $subj, $msg);

}

