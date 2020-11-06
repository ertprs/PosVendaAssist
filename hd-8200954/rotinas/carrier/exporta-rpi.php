<?php

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../../funcoes.php';

use \Posvenda\Fabricas\_170\Os;

$fabrica = 170;

$sql = "
    SELECT
        r.rpi,
        p.referencia,
        rp.serie
    FROM tbl_rpi r
    JOIN tbl_rpi_produto rp USING(rpi,fabrica)
    JOIN tbl_produto p ON p.produto = rp.produto AND p.fabrica_i = {$fabrica}
    WHERE r.fabrica = {$fabrica}
    AND r.exportado IS NULL;
";

$qry = pg_query($con, $sql);

$className = '\\Posvenda\\Fabricas\\_' . $fabrica . '\\Os';
$classOs = new $className($fabrica, null, $con);

while ($fetch = pg_fetch_assoc($qry)) {
    $rpi = $fetch['rpi'];
    $referencia = $fetch['referencia'];
    $serie = $fetch['serie'];

    $array_dados_valida[$serie] = array(
        'MATNR' => $referencia,
        'SERNR' => $serie
    );

    $valida_rpi = $classOs->validaRPI($array_dados_valida);

    if ($valida_rpi == false) {
        try {
            $dadosRPI = $classOs->getDadosRPIExport($rpi, $serie);
            $exportRPI = $classOs->exportRPI($dadosRPI);

            if ($exportRPI !== true) {
                throw new Exception("O RPI não foi exportado, entre em contato com a fábrica");
            }
        } catch(Exception $e) {
            $update = "
                UPDATE tbl_rpi SET
                    exportado_erro = '{$e->getMessage()}'
                WHERE rpi = {$rpi}
                AND fabrica = {$fabrica};
            ";

            $qry = pg_query($con, $update);
        }
    } else {
        $update = "
            UPDATE tbl_rpi SET
                exportado = now(),
                exportado_erro = NULL
            WHERE rpi = {$rpi}
            AND fabrica = {$fabrica};
        ";

        $qry = pg_query($con, $update);
    }
}
