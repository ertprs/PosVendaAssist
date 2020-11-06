<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

$sql = "SELECT extrato FROM tbl_extrato WHERE fabrica = 52 AND data_geracao > '2016-06-01 00:00:00' AND extrato <> 2956723";
$res = pg_query($con,$sql);

for($i = 0; $i < pg_num_rows($res); $i++){

	$extrato = pg_fetch_result($res,$i,"extrato");

	$sqlKm = "SELECT tbl_os.os, 
		tbl_os.qtde_km, 
		tbl_os.data_conserto::date as data_conserto 
		FROM tbl_os JOIN tbl_os_extra USING(os) 
		WHERE tbl_os_extra.extrato = {$extrato} 
		AND tbl_os.qtde_km > 0
		ORDER BY data_conserto, qtde_km";
	$resKm = pg_query($con,$sqlKm);
	$calculoKm = pg_fetch_all($resKm);

	if (!pg_last_error($con)) {
		if (count($calculoKm) > 0) {
			$dataAnterior = "";
			$arrayData = array();

			foreach ($calculoKm as $arrayKm) {
				if ($arrayKm['data_conserto'] != $dataAnterior) {
					$dataAnterior = $arrayKm['data_conserto'];
}

$arrayData[$dataAnterior][] = array(
	"os"        => $arrayKm['os'],
	"qtde_km"   => $arrayKm['qtde_km']
);
}

foreach ($arrayData as $valoresKm) {
	if (count($valoresKm) == 1) {
		continue;
} else {
	$kmSoma = 0;
	$msg = "";
	$ultimoArray = (count($valoresKm) - 1);
	for ($x = 0; $x < count($valoresKm); $x++) {
		if ($x == 0) {
			$recebeOsMsg = "";
			$osMsg = array();
			$osMsg[] = $valoresKm[$x]['os'];
			$msg = "OS passou por uma roteirização e o KM foi alterado de ".$valoresKm[$x]['qtde_km'];
			$msg .= "\npara 0, sendo pago na Ordem de Serviço ".$valoresKm[$ultimoArray]['os'];

			$sqlUp = "
				UPDATE  tbl_os_extra
				SET     obs_adicionais = '" . $msg . "'
				WHERE   os = " . $valoresKm[$x]['os'];
			$resUp = pg_query($con,$sqlUp);
} else if ($x == (count($valoresKm) - 1)) {
	$recebeOsMsg = implode(", ",$osMsg);
	$msg = "OS passou por uma roteirização e o valor do KM contemplará as Ordens de Serviço: ".$recebeOsMsg;

	$sqlUp = "
		UPDATE  tbl_os_extra
		SET     obs_adicionais = '" . $msg . "'
		WHERE   os = " . $valoresKm[$x]['os'];
	$resUp = pg_query($con,$sqlUp);

	$sqlUp2 = "
		UPDATE  tbl_os
		SET     qtde_km             = 0,
		qtde_km_calculada   = 0
		WHERE os IN ($recebeOsMsg)
		";
	$resUp2 = pg_query($con,$sqlUp2);
} else {
	$osMsg[] = $valoresKm[$x]['os'];
	$msg = "OS passou por uma roteirização e o KM foi alterado de ".$valoresKm[$x]['qtde_km'];
	$msg .= "\npara 0, sendo pago na Ordem de Serviço ".$valoresKm[$ultimoArray]['os'];

	$sqlUp = "
		UPDATE  tbl_os_extra
		SET     obs_adicionais = '" . $msg . "'
		WHERE   os = " . $valoresKm[$x]['os'];
	$resUp = pg_query($con,$sqlUp);
}
}
}
}
}
}
}

$sql5      = "SELECT fn_calcula_extrato ($fabrica, $extrato)";
$res5      = pg_query($con, $sql5);
