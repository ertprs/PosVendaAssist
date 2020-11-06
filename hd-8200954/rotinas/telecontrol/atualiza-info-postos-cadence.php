<?php

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';

	$login_fabrica = 35; 

	$sql = "SELECT DISTINCT tbl_posto.posto 
			FROM tbl_posto 
			INNER JOIN tbl_posto_linha ON tbl_posto_linha.posto = tbl_posto.posto
			INNER JOIN tbl_posto_fabrica ON tbl_posto_fabrica.posto = tbl_posto_linha.posto 
			AND tbl_posto_fabrica.fabrica = 35
			WHERE tbl_posto_linha.linha != 901
			AND tbl_posto_fabrica.credenciamento <> 'DESCREDENCIADO'
			AND tbl_posto_fabrica.contato_estado IS NOT NULL 
			ORDER BY tbl_posto.posto ASC";
	$res = pg_query($con, $sql);

	if(pg_num_rows($res) > 0){

		for($i = 0; $i < pg_num_rows($res); $i++){

			$posto = pg_fetch_result($res, $i, "posto");

			$sql_info = "SELECT obs_conta, parametros_adicionais FROM tbl_posto_fabrica WHERE fabrica = {$login_fabrica} AND posto = {$posto}";
			$res_info = pg_query($con, $sql_info);

			$obs_conta = pg_fetch_result($res_info, 0, "obs_conta");
			$parametros_adicionais = pg_fetch_result($res_info, 0, "parametros_adicionais");

			if(strlen(trim($obs_conta)) > 0){

				if(strlen($parametros_adicionais) > 0){

		            $obs = json_decode($parametros_adicionais, true);

		            $obs_cadence = $obs["obs_cadence"];
		            $obs_oster = $obs["obs_oster"];

		            $parametros_adicionais = json_encode(array(
                    	"obs_cadence" => utf8_encode($obs_conta)." \n ".$obs_cadence,
                        "obs_oster"   => $obs_oster
                   	));

		        }else{

		           	$parametros_adicionais = json_encode(array(
                    	"obs_cadence" => utf8_encode($obs_conta),
                        "obs_oster"   => ""
                   	));

		        }

		        $parametros_adicionais = str_replace("\\", "\\\\", $parametros_adicionais);

		        $parametros_adicionais;

		        $sql_pa = "UPDATE tbl_posto_fabrica SET parametros_adicionais = '{$parametros_adicionais}' WHERE fabrica = {$login_fabrica} AND posto = {$posto}";
		        $res_pa = pg_query($con, $sql_pa);

			}

		}

	}

?>