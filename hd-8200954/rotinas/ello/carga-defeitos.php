<?php
	
	include dirname(__FILE__) . '/../../dbconfig.php';
    	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

	$sql = "SELECT defeito_reclamado FROM tbl_defeito_reclamado WHERE fabrica = 136 AND defeito_reclamado IN(13386,13388,13389,13390,13392,13393,13394,13398,13403,13407,13413,13417,13418)";
	$res = pg_query($con,$sql);

	for($i = 0; $i < pg_num_rows($res); $i++){

		$defeito_reclamado = pg_fetch_result($res, $i, defeito_reclamado);

		$sql = "INSERT INTO tbl_diagnostico(fabrica,defeito_reclamado) VALUES(136,$defeito_reclamado) RETURNING diagnostico";
		$resD = pg_query($con,$sql);

		$diagnostico = pg_fetch_result($resD, 0, diagnostico);

		$sql = "INSERT INTO tbl_diagnostico_produto(diagnostico,fabrica,produto) SELECT $diagnostico, 136, produto from tbl_produto where fabrica_i = 136 and ativo and referencia like 'EAF%'";
		$resD = pg_query($con,$sql);

	}
?>
