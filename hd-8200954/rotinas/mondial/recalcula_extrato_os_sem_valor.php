<?php


try {
    
    echo "\n";

    include dirname(__FILE__) . "/../../dbconfig.php";
    include dirname(__FILE__) . "/../../includes/dbconnect-inc.php";
    include dirname(__FILE__) . "/../funcoes.php";
    include dirname(__FILE__) . "/../../classes/Posvenda/Extrato.php";

    $fabrica = 151;

    $array_extratos      = array();
    $sql = "
        SELECT
            tbl_os.os,
            tbl_os_extra.extrato,
            tbl_posto_fabrica.posto
        FROM tbl_os
        INNER JOIN tbl_os_extra ON tbl_os_extra.os = tbl_os.os
        INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_os.posto AND tbl_posto_fabrica.fabrica = {$fabrica}
        INNER JOIN tbl_tipo_posto ON tbl_tipo_posto.tipo_posto = tbl_posto_fabrica.tipo_posto AND tbl_tipo_posto.fabrica = {$fabrica}
        INNER JOIN tbl_os_troca ON tbl_os_troca.os = tbl_os.os AND tbl_os_troca.fabric = {$fabrica}
        WHERE tbl_os.fabrica = {$fabrica}
        AND tbl_os.data_fechamento IS NOT NULL
        AND tbl_os_troca.ressarcimento IS NOT TRUE
        AND (tbl_os.mao_de_obra IS NULL OR tbl_os.mao_de_obra = 0)
        AND tbl_tipo_posto.posto_interno IS NOT TRUE
    ";
    $qry = pg_query($con, $sql);

    $osClass             = new \Posvenda\Os($fabrica);
    $moClass             = new \Posvenda\MaoDeObra(null, $fabrica);
    $extratoClass        = new Extrato($fabrica);

    $osClass->_model->getPDO()->beginTransaction();

    echo "OS - Mão de Obra\n";

    while ($os = pg_fetch_object($qry)) {
        if (!empty($os->extrato) && !in_array($os->extrato, $array_extratos)) {
            $array_extratos[$os->extrato] = $os->posto;
        }

        $osClass->calculaOs($os->os);
        $moClass->setOs($os->os);
        
        echo $os->os." - ".$moClass->getMaoDeObra()."\n";
    }

    echo "\n";
    echo "Extratos:\n";
    echo implode(", ", array_keys($array_extratos))."\n";
    echo "\n";

    foreach ($array_extratos as $extrato => $posto) {
        $total = $extratoClass->calcula($extrato);

        echo "Extrato $extrato - Valor $total\n";
    }

    $osClass->_model->getPDO()->rollBack();
    #$osClass->_model->getPDO()->commit();

    echo "\n";

} catch (Exception $e) {
    echo $e->getMessage();
    echo "\n";

    $osClass->_model->getPDO()->rollBack();
}
