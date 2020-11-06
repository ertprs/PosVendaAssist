<?php

include dirname(__FILE__)."/../../dbconfig.php";
include dirname(__FILE__)."/../../includes/dbconnect-inc.php";
include dirname(__FILE__)."/../../funcoes.php";

$sql = "
    SELECT o.os, o.consumidor_cep, o.consumidor_estado, o.consumidor_cidade, o.consumidor_bairro, o.consumidor_endereco, o.consumidor_numero, pf.latitude, pf.longitude
    FROM tbl_os o
    INNER JOIN tbl_posto_fabrica pf ON pf.posto = o.posto AND pf.fabrica = 52
	join tbl_os_extra using(os)
    WHERE o.fabrica = 52
    AND COALESCE(o.qtde_km, 0) = 0
	AND o.tipo_atendimento = 230
	and tbl_os_extra.extrato isnull
    AND o.data_digitacao >= '2017-01-01 00:00:00'
";
$res = pg_query($con, $sql);

echo "\n";

while ($r = pg_fetch_object($res)) {
    echo $r->os."\n";

    $c = array();

    if ($r->consumidor_endereco) {
        $c["end"] = $r->consumidor_endereco;
    }

    if ($r->consumidor_numero && strtoupper($r->consumidor_numero) != "S/N" && strtoupper($r->consumidor_numero) != "SN") {
        $c["num"] = $r->consumidor_numero;
    }

    if ($r->consumidor_bairro) {
        $c["bai"] = $r->consumidor_bairro;
    }

    if ($r->consumidor_cidade) {
        $c["cid"] = $r->consumidor_cidade;
    }

    if ($r->consumidor_estado) {
        $c["est"] = $r->consumidor_estado;
    }

    $c["pai"] = "Brasil";

    $ce = implode(", ", $c);

    echo "endereco 1: ".$ce."\n";

    $p = $r->latitude.", ".$r->longitude;

    echo "posto: ".$p."\n";

    $rota = googleMapsGeraRota($p, $ce);

    echo "status: ".$rota["status"]."\n";

    if ($rota["status"] != "OK") {
	    unset($c["num"], $c["bai"]);
	    $ce = implode(", ", $c);

            echo "endereco 2: ".$ce."\n";

	    $rota = googleMapsGeraRota($p, $ce);

	    echo "status: ".$rota["status"]."\n";
    }


    if ($rota["status"] != "OK") {
	    $ce = $r->consumidor_cep;

	    echo "endereco 3: ".$ce."\n";

	    $rota = googleMapsGeraRota($p, $ce);

	    echo "status: ".$rota["status"]."\n";
    }

    if ($rota["status"] != "OK") {
            unset($c["end"]);
            $ce = implode(", ", $c);

            echo "endereco 4: ".$ce."\n";

            $rota = googleMapsGeraRota($p, $ce);

            echo "status: ".$rota["status"]."\n";
    }


    if ($rota["status"] == "OK") {
        $ida = $rota["routes"][0]["legs"][0]["distance"]["value"];
        $ida = number_format(($ida / 1000), 2, ".", "");

        echo "km ida: ".$ida."\n";

        $rota = googleMapsGeraRota($ce, $p);

        echo "status: ".$rota["status"]."\n";

        if ($rota["status"] == "OK") {
            $volta = $rota["routes"][0]["legs"][0]["distance"]["value"];
            $volta = number_format(($volta / 1000), 2, ".", "");

            echo "km volta: ".$volta."\n";

            $total = $ida + $volta;

            echo "km total: ".$total."\n";

            $up = "UPDATE tbl_os SET qtde_km = {$total} WHERE fabrica = 52 AND os = {$r->os}";
            $rup = pg_query($con, $up);
			if($total > 50) {
				         $sql = "INSERT INTO tbl_os_status
                        (os, status_os, observacao)
                        VALUES
                        ({$r->os}, 98, 'OS aguardando aprovação de KM')";
                $ressss = pg_query($con, $sql);
			}
        }
    }

    echo "\n";
}
