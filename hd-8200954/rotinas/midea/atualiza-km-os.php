<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

use Posvenda\TcMaps;

$sql = "
    SELECT 
        o.os, 
        pf.latitude,
        pf.longitude,
        o.consumidor_endereco,
        o.consumidor_numero,
        o.consumidor_bairro,
        o.consumidor_cidade,
        o.consumidor_estado,
        o.consumidor_revenda
    FROM tbl_os o 
    INNER JOIN tbl_tipo_atendimento ta ON ta.tipo_atendimento = o.tipo_atendimento AND ta.fabrica = 169 
    INNER JOIN tbl_os_produto op ON op.os = o.os 
    INNER JOIN tbl_produto p ON p.produto = op.produto AND p.fabrica_i = 169 
    INNER JOIN tbl_linha l ON l.linha = p.linha AND l.fabrica = 169 
    INNER JOIN tbl_status_checkpoint sc ON sc.status_checkpoint = o.status_checkpoint 
    INNER JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = 169
    WHERE o.fabrica = 169 
    AND o.status_checkpoint NOT IN(0, 1, 28) 
    AND l.deslocamento IS TRUE 
    AND (o.qtde_km IS NULL OR o.qtde_km = 0) 
    AND o.os_numero IS NULL 
    AND o.sua_os NOT LIKE '%-%' 
    AND ta.km_google IS TRUE 
    ORDER BY o.data_digitacao DESC
";
$res = pg_query($con, $sql);

$tcmaps = new TcMaps(169, $con);

if (pg_num_rows($res) > 0) {
    pg_query($con, "BEGIN");

    while ($row = pg_fetch_object($res)) {
	echo "\n";
	echo $row->os." \n";

	$debug = false;

        $geo = $tcmaps->geocode(
            $row->consumidor_endereco,
            $row->consumidor_numero,
            $row->consumidor_bairro,
            $row->consumidor_cidade,
            $row->consumidor_estado,
            "BR",
	    $debug
        );

	if (empty($geo['latitude']) || empty($geo['longitude'])) {
	    echo "geolocalizacao nao encontrada\n";
	} else {
	    $route = $tcmaps->route(
	        $row->latitude.",".$row->longitude,
		$geo['latitude'].",".$geo['longitude']
	    );

	    $km_ida = $route['total_km'];

	    if ($km_ida > 200) {
		echo "km acima de 200\n";
		continue;
	    }

	    $route = $tcmaps->route(
		$geo['latitude'].",".$geo['longitude'],
                $row->latitude.",".$row->longitude
            );

	    $km_volta = $route['total_km'];

	    $km = $km_ida + $km_volta;

	    echo $km." km\n";

	    pg_query($con, "
	        UPDATE tbl_os SET qtde_km = $km WHERE fabrica = 169 AND os = {$row->os}
	    ");

            if (strlen(pg_last_error()) > 0) {
	        echo "erro ao atualizar OSs\n";
		echo pg_last_error();
		echo "\n";
		pg_query($con, "ROLLBACK");
		exit;
	    }
	}
    }

    pg_query($con, "COMMIT");
}
