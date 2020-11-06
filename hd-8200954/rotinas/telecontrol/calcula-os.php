<?php

try {
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../../classes/Posvenda/Os.php';

	$login_fabrica = $argv[1];	
	$os = $argv[2];
	$classePropria = false;

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
	
	if(strlen($os) > 0){
		$classOs->calculaOs($os);
	}else{
		
		$sql = "SELECT tbl_os_extra.os FROM tbl_os_extra WHERE extrato = 3299614";
		$res = pg_query($con,$sql);

		if(pg_num_rows($res) > 0){

			$rows = pg_num_rows($res);

			for($i = 0; $i < $rows; $i++){
				$os = pg_fetch_result($res,$i,'os');
				$classOs->calculaOs($os);
			}
		}
	}
}catch(Exception $e){
	echo "Erro ao calcula OR -> ".$e;
}
