<?php
           include dirname(__FILE__) . '/../../dbconfig.php';
	   include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';   
           $fabrica = 141;
   
	  $sql = "SELECT os
		  FROM tbl_os_extra
		  JOIN tbl_extrato ON tbl_os_extra.extrato = tbl_extrato.extrato
		  WHERE tbl_extrato.fabrica = $fabrica";
	  $res = pg_query($con,$sql);

	  if(pg_num_rows($res) > 0){

		for($i = 0; $i < pg_num_rows($res); $i++){
			$os = pg_fetch_result($res,$i,'os');
           		$calculaOs = new \Posvenda\Fabricas\_141\ExcecaoMobraAdicional($os,$fabrica);
			unset($calculaOs);
		}
	 }
?>
