<?php
try {
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../../classes/Posvenda/Os.php';
	$fabrica = 162;

	$classOs = new \Posvenda\Os($fabrica);
	$sql = "SELECT os FROM tbl_os_extra JOIN tbl_extrato USING(extrato) WHERE fabrica = $fabrica AND data_geracao > '2016-11-01 00:00' AND liberado IS NULL";
	$res = pg_query($con,$sql);

	for($i = 0; $i < pg_num_rows($res); $i++){
		$os = pg_fetch_result($res,$i,'os');
		$classOs->calculaOs($os);
	}

}catch (Exception $e) {
	echo $e->getMessage();
}
