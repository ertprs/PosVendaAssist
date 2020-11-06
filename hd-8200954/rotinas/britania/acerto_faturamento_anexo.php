<?php
include_once dirname(__FILE__) . '/../../dbconfig.php';
include_once dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

$fabrica = 3;
$dir = "/tmp/britania";
$file = "log_faturamento.txt";

/**
 * PRIMEIRO: Cria-se a temporária com
 * todos os faturamentos de devolução
 * já criados para a Britania
 */
$sql = "SELECT  faturamento,
                extrato_devolucao AS extrato,
                nota_fiscal
   INTO TEMP    fat_ext
        FROM    tbl_faturamento
        WHERE   tbl_faturamento.fabrica = $fabrica
        AND     distribuidor            IS NOT NULL
        AND     extrato_devolucao       IN (SELECT referencia_id FROM tbl_tdocs WHERE contexto='lgr' AND situacao = 'ativo' AND fabrica = $fabrica)
  ORDER BY      extrato,
                faturamento";
$res = pg_query($con,$sql);

if (!pg_last_error($con)) {
//     echo "Primeiro TEMP completo.\n";
    $fp = fopen( $dir . '/' . $file, "a" );
    fputs ($fp,"Primeiro TEMP completo.\n");
    fclose ($fp);
}

/**
 * SEGUNDO: Cria-se a temporária com
 * todos os arquivos referentes à LGR
 * cadastrados pelo sistema
 */
$sql2 = "SELECT tdocs,
                referencia_id,
                JSON_FIELD('filename',tbl_tdocs.obs) AS nome_arquivo
   INTO TEMP    arq_mudar
        FROM    tbl_tdocs
        WHERE   fabrica = $fabrica
        AND     contexto='lgr'
        AND     situacao = 'ativo'
  ORDER BY      tdocs
";
$res2 = pg_query($con,$sql2);

if (!pg_last_error($con)) {
//     echo "Segundo TEMP completo.\n";
    $fp = fopen( $dir . '/' . $file, "a" );
    fputs ($fp,"Segundo TEMP completo.\n");
    fclose ($fp);
}

/**
 * TERCEIRO: Percorre-se a primeira
 * temporária, extrato por extrato, para encontrar
 * os anexos correspondentes, ordenados por ordem de ID
 * tendo-se por base a ordem dos faturamentos cadastrados
 */

$sql3 = "
    SELECT  DISTINCT
            extrato
    FROM    fat_ext
    ORDER BY extrato
";
$res3 = pg_query($con,$sql3);

while ($resultado = pg_fetch_object($res3)) {
//     echo "Extrato ".$resultado->extrato."\n";
    $fp = fopen( $dir . '/' . $file, "a" );
    fputs ($fp,"Extrato ".$resultado->extrato."\n");
    fclose ($fp);

    $sqlFat = "
        SELECT  faturamento
        FROM    fat_ext
        WHERE   extrato = ".$resultado->extrato;
    $resFat = pg_query($con,$sqlFat);

    $fats = pg_fetch_all($resFat);
//     print_r($fats);
//
//     echo "\n";

    $sqlAnexo = "
        SELECT  tdocs,
                nome_arquivo
        FROM    arq_mudar
        WHERE   referencia_id = ".$resultado->extrato;
    $resAnexo = pg_query($con,$sqlAnexo);

    $anexos = pg_fetch_all($resAnexo);
//     print_r($anexos);
//     exit;


    for ($i = 0; $i < count($fats); $i++) {
        $fatSubs = $fats[$i]['faturamento'];
        $tdocsSubs = $anexos[$i]['tdocs'];

        pg_query($con,"BEGIN TRANSACTION");

        if (empty($tdocsSubs)) {
//             echo "Faturamento $fatSubs sem NF Devolução.\n";
            $fp = fopen( $dir . '/' . $file, "a" );
            fputs ($fp,"Faturamento $fatSubs sem NF Devolução.\n");
            fclose ($fp);
            continue;
        }
        $sqlUp = "
            UPDATE  tbl_tdocs
            SET     referencia_id   = $fatSubs
            WHERE   tdocs           = $tdocsSubs
        ";
//         exit($sqlUp);
        $resUp = pg_query($con,$sqlUp);

        if (pg_last_error($con)) {
            $erro = pg_last_error($con);
            pg_query($con,"ROLLBACK TRANSACTION");

            echo "Problema com extrato ".$resultado->extrato." e faturamento $fatSubs: ".$erro."\n";
            exit;
        }

        pg_query($con,"COMMIT TRANSACTION");
//         echo "Sucesso: Faturamento $fatSubs alterado no TDocs.\n";
        $fp = fopen( $dir . '/' . $file, "a" );
        fputs ($fp,"Sucesso: Faturamento $fatSubs alterado no TDocs.\n");
        fclose ($fp);

        unset($fatSubs);
        unset($tdocsSubs);
    }

    unset($fats);
    unset($anexos);
}
?>
