<?php

try {
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../funcoes.php';

	if ($_serverEnvironment == "production") {
                define("ENV", "prod");
        } else {
                define("ENV", "dev");
        }

	$login_fabrica = 143;
	$fabrica_nome  = "wackerneuson";

	ini_set('default_socket_timeout', 800);

	if (ENV == "prod") {
		$soap = new SoapClient("http://201.91.139.164:8080/g5-senior-services/sapiens_Synccom_senior_g5_co_wacker_consulta_estruturaprodutos?wsdl", array("trace" => 1, "exception" => 1));
	} else {
		$soap = new SoapClient("http://187.87.251.133:8080/g5-senior-services/sapiens_Synccom_senior_g5_co_wacker_consulta_estruturaprodutos?wsdl", array("trace" => 1, "exception" => 1));
	}

	$argumentos = array(
		"user"       => "Telecontrol",
		"password"   => "Telecontrol",
		"encryption" => "0",
		"parameters" => array(
			"codEmp" => 1,
			"sitPro" => "A"
		)
    );

    $metodo = "ConsultaProdutos";

    $soapResult = $soap->__soapCall($metodo, $argumentos);

    if (strlen($soapResult->erroExecucao) > 0) {
    	throw new Exception($soapResult->erroExecucao);
    }

    if (count($soapResult->retornosProdutos) > 0) {
		$create_table = "CREATE TEMP TABLE temp_tbl_peca_wackerneuson ( referencia text, descricao text, peca integer )";
		$res          = pg_query($con, $create_table);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro de execução ao importar peças");
		}

    	foreach ($soapResult->retornosProdutos as $peca) {
    		if (in_array($peca->codFam, array("06.01", "06.02", "06.03")) && (!empty($peca->codPro) && !empty($peca->desPro))) {
				$referencia = trim($peca->codPro);
				$descricao  = trim($peca->desPro);

				$insert = "INSERT INTO temp_tbl_peca_wackerneuson (referencia, descricao) VALUES ('{$referencia}', '{$descricao}')";
				$res    = pg_query($con, $insert);
    		}
    	}

    	$update = "UPDATE temp_tbl_peca_wackerneuson
    			   SET peca = tbl_peca.peca
    			   FROM tbl_peca
    			   WHERE tbl_peca.fabrica = {$login_fabrica}
    			   AND UPPER(temp_tbl_peca_wackerneuson.referencia) = UPPER(tbl_peca.referencia)";
    	$res = pg_query($con, $update);

    	if (strlen(pg_last_error()) > 0) {
    		throw new Exception("Erro de execução ao importar peças");
    	}

		$delete = "DELETE FROM temp_tbl_peca_wackerneuson WHERE peca IS NOT NULL";
		$res    = pg_query($con, $delete);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro de execução ao importar peças");
		}

		$insert = "INSERT INTO tbl_peca (fabrica, referencia, descricao, origem)
				   SELECT {$login_fabrica}, temp_tbl_peca_wackerneuson.referencia, SUBSTR(temp_tbl_peca_wackerneuson.descricao, 0, 50), 'NAC'
				   FROM temp_tbl_peca_wackerneuson";
		$res = pg_query($con, $insert);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro de execução ao importar peças");
		}
    }
} catch(Exception $e) {
	system("mkdir /tmp/{$fabrica_nome}/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/" );
	system("mkdir /tmp/{$fabrica_nome}/peca/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/peca/" );

	$arquivo_erro = fopen("/tmp/{$fabrica_nome}/peca/importa-peca-".date("dmYH").".txt", "w");
	fwrite($arquivo_erro, $e->getMessage());
	fclose($arquivo_erro);

	if (ENV == "dev") {
		mail("guilherme.curcio@telecontrol.com.br", "Telecontrol - Erro na importação de peças da Wacker Neuson", $e->getMessage());
	} else {
		mail("helpdesk@telecontrol.com.br, vanilde.sartorelli@wackerneuson.com", "Telecontrol - Erro na importação de peças da Wacker Neuson", $e->getMessage());
	}
}

?>
