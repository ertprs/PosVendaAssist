<?php

include "dbconfig.php";
include "includes/dbconnect-inc.php";

$admin_privilegios = "gerencia, call_center";

include "autentica_admin.php";
include "funcoes.php";

if ($_POST["ajax_carrega_cep_posto"] == true) {
	$posto = $_POST["posto"];
	if ($login_fabrica == 183){
		$cond_blacklist = " AND tbl_posto_cep_atendimento.blacklist IS TRUE ";
	}else{
		$cond_blacklist = " AND tbl_posto_cep_atendimento.blacklist IS FALSE ";
	}
	$sql = "SELECT 
				tbl_posto_cep_atendimento.cep_inicial, 
				tbl_posto_cep_atendimento.cep_final 
			FROM 
				tbl_posto_cep_atendimento 
			JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto=tbl_posto_cep_atendimento.posto AND tbl_posto_fabrica.fabrica={$login_fabrica}
			WHERE 
				tbl_posto_cep_atendimento.fabrica={$login_fabrica}
			AND
				tbl_posto_fabrica.codigo_posto='{$posto}'
			$cond_blacklist
			ORDER BY tbl_posto_cep_atendimento.cep_inicial ASC";
	$res = pg_query($con, $sql);

	if (pg_num_rows($res) > 0) {
		$rows = pg_num_rows($res);

		$ceps_posto = array();

		for ($i = 0; $i < $rows; $i++) {
			$cep_inicial    		 = pg_fetch_result($res, $i, "cep_inicial");
			$posto_cep_atendimento   = pg_fetch_result($res, $i, "posto_cep_atendimento");

			if (is_numeric($cep_inicial)) {
				$ceps_posto[] = $cep_inicial;
			}
		}

		$retorno = array(
			"ceps_posto" => $ceps_posto
		);
	} else {
		$retorno = array("erro" => true);
	}

	exit(json_encode($retorno));
}
