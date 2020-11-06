<?php                                                                                                                                       
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

	$sql = "SELECT posto,parametros_adicionais FROM tbl_posto_fabrica WHERE fabrica = 50";
	$res = pg_query($con,$sql);
	$numRows = pg_num_rows($res);
	for($i = 0; $i < $numRows; $i++){
		$posto 	    = pg_fetch_result($res,$i,posto);
		$parametros = pg_fetch_result($res,$i,parametros_adicionais);

		$parametros = json_decode($parametros,true);

		$parametros['devolver_pecas'] = "t";

		$parametros = json_encode($parametros);

		$sql = "UPDATE tbl_posto_fabrica SET parametros_adicionais = '$parametros' WHERE fabrica = 50 AND posto = $posto";
		$resU = pg_query($con,$sql);
	}
