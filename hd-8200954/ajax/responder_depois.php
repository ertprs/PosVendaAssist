<?php
include '../dbconfig.php';
include '../includes/dbconnect-inc.php';
include "../funcoes.php";
include '../autentica_usuario.php';

if (isset($_POST['ajaxResponderDepois']) && $_POST['ajaxResponderDepois']) {
	
	$retorno = [];
	if (!$areaAdmin) {

		pg_query($con, "BEGIN");

		$sqlParametros = "SELECT parametros_adicionais
						  FROM tbl_posto_fabrica
						  WHERE fabrica = {$login_fabrica}
						  AND posto = {$login_posto}";
		$resParametros = pg_query($con, $sqlParametros);

		$parametros_adicionais = json_decode(pg_fetch_result($resParametros, 0, "parametros_adicionais"), true);

		$parametros_adicionais["responderAtualizacaoDepois"] = date("Y-m-d h:i:s");

		$parametros_adicionais = json_encode($parametros_adicionais);

		$sqlUpdate = "UPDATE tbl_posto_fabrica 
					  SET parametros_adicionais = '{$parametros_adicionais}' 
					  WHERE posto = {$login_posto} 
					  AND fabrica = {$login_fabrica}";
		$resUpdate = pg_query($con, $sqlUpdate);

		if (pg_last_error()) {

			pg_query("ROLLBACK");
			
			$retorno = ["success" => false];

		} else {

			pg_query("COMMIT");

			$retorno = ["success" => true];

		}

	}

	exit(json_encode($retorno));

}