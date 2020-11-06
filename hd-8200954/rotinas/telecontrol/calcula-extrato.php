<?php

try {

        include dirname(__FILE__) . '/../../dbconfig.php';
        include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
        require dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../classes/Posvenda/Extrato.php';
	include dirname(__FILE__) . '/classes/extrato.php';
	include dirname(__FILE__) . '/../../classes/Posvenda/Os.php';
	
	$login_fabrica = $argv[1];
	$extrato = $argv[2];


	if (file_exists("/www/assist/www/classes/Posvenda/Fabricas/_{$login_fabrica}/Extrato.php")) {
		include_once "/www/assist/www/classes/Posvenda/Fabricas/_{$login_fabrica}/Extrato.php";
		$extratoClassName = '\\Posvenda\\Fabricas\\_' . $login_fabrica . '\\Extrato';
		if($login_fabrica == 158) $extratoClassName = 'ExtratoImbera';
		$extratoClassePropria = true;
	}

	if($extratoClassePropria == true){
		$classExtrato = new $extratoClassName($login_fabrica);
	}else{
		$classExtrato = new Extrato($login_fabrica);
	}


	if (file_exists(dirname(__FILE__) . "/classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php")) {
		include_once dirname(__FILE__) . "/classes/Posvenda/Fabricas/_{$login_fabrica}/Os.php";
		$className = '\\Posvenda\\Fabricas\\_' . $login_fabrica . '\\Os';
		$classePropria = true;
	}

	if($classePropria == true){
		$classOs = new $className($login_fabrica);
	}else{
		$classOs = new \Posvenda\Os($login_fabrica);
	}
	
	if(strlen($extrato) > 0){
		$sql = "SELECT tbl_os_extra.os,posto, tbl_extrato_agrupado.codigo  FROM tbl_os_extra join tbl_os using(os) left join tbl_extrato_agrupado using(extrato) WHERE tbl_os_extra.extrato = {$extrato}";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){

			$rows = pg_num_rows($res);

			for($i = 0; $i < $rows; $i++){
				$os = pg_fetch_result($res,$i,'os');
				$posto = pg_fetch_result($res,$i,'posto');
				$codigo = pg_fetch_result($res,$i,'codigo');
				$classOs->calculaOs($os);
			}
		}
		if($login_fabrica == 158) {
			if(!empty($codigo)) {
				$classExtrato->calcula($extrato, $posto,$codigo,$con, 't');
			}else{
				$classExtrato->calcula($extrato, $posto);
			}
		}else{
			$classExtrato->calcula($extrato);
		}

	}else{
		$sql = "SELECT distinct extrato,total  FROM tbl_extrato join tbl_os_extra using(extrato) join tbl_os using(os,fabrica)  WHERE fabrica = 144 AND data_geracao > '2019-09-01 00:00' and aprovado isnull";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){
		
			$rows = pg_num_rows($res);

			for($i = 0; $i < $rows; $i++){

				$extrato = pg_fetch_result($res,$i,'extrato');
		$sql = "SELECT tbl_os_extra.os FROM tbl_os_extra join tbl_os using(os) WHERE extrato = {$extrato} ";
		$resx = pg_query($con,$sql);

		if(pg_num_rows($resx) > 0){

			$rowsx = pg_num_rows($resx);

			for($j = 0; $j < $rowsx; $j++){
				$os = pg_fetch_result($resx,$j,'os');
				$classOs->calculaOs($os);
			}
		}
				$classExtrato->calcula($extrato);
			}
		}
	}

}catch(Excepiton $e){
	echo "Erro ao calcular extratos -> ".$e;
}
