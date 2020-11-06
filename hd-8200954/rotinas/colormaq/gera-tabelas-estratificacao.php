<?php
/**
 * gera-tabelas-estratificacao.php
 *
 * Gera as tabelas usadas no Relatório de Estratificação
 *
 */


include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

$fabrica = 50;

$sqlFamilias = "SELECT familia, descricao FROM tbl_familia WHERE fabrica = {$fabrica} and ativo";
$qryFamilias = pg_query($con, $sqlFamilias);

while ($fetch = pg_fetch_assoc($qryFamilias)) {
    $familia = $fetch['familia'];
    $descricao = $fetch['descricao'];

    $drops = "DROP TABLE IF EXISTS colormaq_os_serie_{$familia};
              DROP TABLE IF EXISTS colormaq_numero_serie1_{$familia};
              DROP TABLE IF EXISTS colormaq_numero_serie2_{$familia};
              DROP TABLE IF EXISTS colormaq_numero_serie_{$familia}; ";
    $qry = pg_query($con, $drops);

    $suffix = $fabrica . '_' . $familia;

    $sqlOSSerie = "SELECT os,
                        data_abertura, 
                        serie, 
                        tbl_os.produto,
                        defeito_constatado,
                        posto
                    INTO colormaq_os_serie_{$familia}
                    FROM tbl_os JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto 
					WHERE fabrica = {$fabrica}
					AND excluida is not true
					AND familia = {$familia}
				   	AND fabrica_i = {$fabrica};

                    CREATE INDEX idx_ossda_{$suffix} ON colormaq_os_serie_{$familia}(data_abertura);
                    CREATE INDEX idx_osss_{$suffix} ON colormaq_os_serie_{$familia}(serie);
                    CREATE INDEX idx_osspr_{$suffix} ON colormaq_os_serie_{$familia}(produto);
                    CREATE INDEX idx_ossd_{$suffix} ON colormaq_os_serie_{$familia}(defeito_constatado); 
                    CREATE INDEX idx_osspo_{$suffix} ON colormaq_os_serie_{$familia}(posto);";
    
    $sqlNS1 = "SELECT numero_serie,
                        serie,
                        produto,
                        data_fabricacao
                    INTO colormaq_numero_serie1_{$familia}
                    FROM tbl_numero_serie JOIN tbl_produto using(produto) 
                    WHERE fabrica = {$fabrica} AND familia = {$familia} AND fabrica_i = {$fabrica};

                    CREATE INDEX idx_ns1ns_{$suffix} ON colormaq_numero_serie1_{$familia}(numero_serie);
                    CREATE INDEX idx_ns1s_{$suffix} ON colormaq_numero_serie1_{$familia}(serie);
                    CREATE INDEX idx_ns1p_{$suffix} ON colormaq_numero_serie1_{$familia}(produto);
                    CREATE INDEX idx_ns1df_{$suffix} ON colormaq_numero_serie1_{$familia}(data_fabricacao); ";
    
    $sqlNS2 = "SELECT numero_serie,
                        substr(serie,1,length(serie) -1) as serie,
                        produto,
                        data_fabricacao
                    INTO colormaq_numero_serie2_{$familia}
                    FROM tbl_numero_serie JOIN tbl_produto using(produto) 
                    WHERE fabrica = {$fabrica} AND familia = {$familia} AND fabrica_i = {$fabrica} AND data_fabricacao between '2013-07-25' and '2013-09-13';

                    CREATE INDEX idx_ns2ns_{$suffix} ON colormaq_numero_serie2_{$familia}(numero_serie);
                    CREATE INDEX idx_ns2s_{$suffix} ON colormaq_numero_serie2_{$familia}(serie);
                    CREATE INDEX idx_ns2p_{$suffix} ON colormaq_numero_serie2_{$familia}(produto);
                    CREATE INDEX idx_ns2df_{$suffix} ON colormaq_numero_serie2_{$familia}(data_fabricacao); ";

    $sqlNS = "SELECT numero_serie,
                        serie, 
                        produto,
                        data_fabricacao 
                    INTO colormaq_numero_serie_{$familia} 
                    FROM (select * from colormaq_numero_serie1_{$familia} UNION select * from colormaq_numero_serie2_{$familia}) x;

                    CREATE INDEX idx_nsns_{$suffix} ON colormaq_numero_serie_{$familia}(numero_serie);
                    CREATE INDEX idx_nss_{$suffix} ON colormaq_numero_serie_{$familia}(serie);
                    CREATE INDEX idx_nsp_{$suffix} ON colormaq_numero_serie_{$familia}(produto);
                    CREATE INDEX idx_nsdf_{$suffix} ON colormaq_numero_serie_{$familia}(data_fabricacao); ";

    $qry = pg_query($con, $sqlOSSerie . $sqlNS1 . $sqlNS2 . $sqlNS);

    if (!pg_last_error($con)) {
        $to = 'estratificacao@colormaq.com.br, jaqueline.alcantara@colormaq.com.br';
        $subj = '';
        $msg = 'A base BI da familia ' . $descricao . ' foi atualizada com sucesso';
    } else {
        $to = 'francisco.ambrozio@telecontrol.com.br, joao.junior@telecontrol.com.br';
        $subj = '[ERRO Colormaq] ';
        $msg = 'A base BI da familia ' . $descricao . ' não foi atualizada: ' . pg_last_error($con);
    }

    $subj.= 'Atualização BI - Relatório de Estratificação - ' . $descricao ;
    
    mail($to, $subj, $msg);

}
