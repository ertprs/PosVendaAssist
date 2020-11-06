<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';

if (!empty($argv[1]) and !empty($argv[2])) {
    $peca = $argv[1];
    $qtde = $argv[2];
} else {
    echo "Informe os parametros"; exit;
}

$sql = "SELECT faturamento_item, qtde
          FROM tbl_faturamento_item
          JOIN tbl_faturamento USING (faturamento)
         WHERE tbl_faturamento.fabrica    = 10
           AND tbl_faturamento_item.posto = 4311
           AND distribuidor IS NULL
           AND status_nfe   IS NULL
           AND (
                 tbl_faturamento.distribuidor IN
                 ( /* Seleciona apenas os distribuidores da condição nova, descartando quando o posto entrava como distribuidor (LRG Britania)*/
                  SELECT DISTINCT distribuidor
                    FROM tbl_faturamento
                   WHERE fabrica      =  10
                     AND posto        =  4311
                     AND distribuidor IS NOT NULL
                     AND distribuidor <> 4311
                 )
                OR tbl_faturamento.fabrica IN (10)
                AND tbl_faturamento.distribuidor IS NULL
               )
           AND tbl_faturamento.cancelada IS NULL
           AND (tbl_faturamento.tipo_nf   =  0
            OR tbl_faturamento.tipo_nf IS NULL)
           AND tbl_faturamento_item.peca  =  $peca
         ORDER BY faturamento_item ";
$res = pg_query($con,$sql);

$c = pg_num_rows($res);

if($c > 0 ){
    for ($i=0; $i < $c; $i++) {
        $faturamento_item = pg_fetch_result($res, $i, 'faturamento_item');
        $qtde             = pg_fetch_result($res, $i, 'qtde');
    }
}

