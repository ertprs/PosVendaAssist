<?php
/**
 * gera-estratificacao-global.php
 * Thiago Tobias 12/06/2015
 */


include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

$fabrica = 24;

$familia = 'global';
$descricao = 'IRC Global';

$drops = "DROP TABLE IF EXISTS suggar_os_serie_{$familia};
          DROP TABLE IF EXISTS suggar_numero_serie_{$familia}; ";
$qry = pg_query($con, $drops);

$suffix = $fabrica . '_' . $familia;

$sqlOSSerie = "SELECT os,
                    data_abertura,
                    serie,
                    tbl_os.produto,
                    defeito_constatado,
                    posto,
                    data_nf
                INTO suggar_os_serie_{$familia}
                FROM tbl_os 
                JOIN tbl_produto ON tbl_os.produto = tbl_produto.produto
                JOIN tbl_familia ON tbl_produto.familia = tbl_familia.familia
                WHERE tbl_os.fabrica = {$fabrica} 
                AND tbl_produto.fabrica_i = {$fabrica}
                AND tbl_familia.ativo IS TRUE;

                CREATE INDEX idx_ossda_{$suffix} ON suggar_os_serie_{$familia}(data_abertura);
                CREATE INDEX idx_osss_{$suffix} ON suggar_os_serie_{$familia}(serie);
                CREATE INDEX idx_osspr_{$suffix} ON suggar_os_serie_{$familia}(produto);
                CREATE INDEX idx_ossd_{$suffix} ON suggar_os_serie_{$familia}(defeito_constatado);
                CREATE INDEX idx_osspo_{$suffix} ON suggar_os_serie_{$familia}(posto);";


$sqlNS = "SELECT numero_serie,
                    serie,
                    produto,
                    data_fabricacao
                INTO suggar_numero_serie_{$familia}
                FROM tbl_numero_serie 
                JOIN tbl_produto using(produto)
                JOIN tbl_familia USING(familia)
                WHERE tbl_numero_serie.fabrica = {$fabrica} 
                AND tbl_produto.fabrica_i = {$fabrica}
                AND tbl_familia.ativo IS TRUE;

                CREATE INDEX idx_ns1ns_{$suffix} ON suggar_numero_serie_{$familia}(numero_serie);
                CREATE INDEX idx_ns1s_{$suffix} ON suggar_numero_serie_{$familia}(serie);
                CREATE INDEX idx_ns1p_{$suffix} ON suggar_numero_serie_{$familia}(produto);
                CREATE INDEX idx_ns1df_{$suffix} ON suggar_numero_serie_{$familia}(data_fabricacao); ";

$qry = pg_query($con, $sqlOSSerie . $sqlNS);

if (!pg_last_error($con)) {
    //$to = 'carlos.roberto@suggar.com.br, marcela@suggar.com.br, daiane.alaniz@suggar.com.br';
    //$to = 'marisa.silvana@telecontrol.com.br';    $subj = '';
    $to = 'thiago.tobias@telecontrol.com.br';
    //$to = 'fernando.rodrigues@telecontrol.com.br';
    $msg = 'A base BI da familia ' . $descricao . ' foi atualizada com sucesso';
} else {
    //$to = 'marisa.silvana@telecontrol.com.br';
    $to = 'thiago.tobias@telecontrol.com.br';
    //$to = 'fernando.rodrigues@telecontrol.com.br';
    $subj = '[ERRO suggar] ';
    $msg = 'A base BI da familia ' . $descricao . ' não foi atualizada: ' . pg_last_error($con);
}

$subj.= 'Atualização BI - Relatório de Estratificação - ' . $descricao ;

mail($to, $subj, $msg);
