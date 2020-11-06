<?php

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	use \Mirrors\Parts\Itens;
	use \Mirrors\Parts\Condicoes;

	function recursiveEncode($param) {
		$res = [];
		foreach ($param as $key => $value) {
			if (is_array($value)) {
				$res[$key] = recursiveEncode($value);
				continue;
			}

			if (is_string($value)) {
				$res[$key] = utf8_encode(trim($value));
			}
		}

		return $res;
	}


	function arrayToken($dados){

		foreach ($dados as $key => $value) {
			
			if($value["application"]["system_code"] == "POSVENDA"){
				$retorno[$value["key_type"]["system_code"]] = $value["application_key"];
			}
		}

		return $retorno;

	}

	function get_app_key($fabrica) {
 
        $curl = curl_init();
         curl_setopt_array($curl, array(
          CURLOPT_URL => "http://api2.telecontrol.com.br/AccessControl/application-key/client_code/".$fabrica,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "postman-token: 7b8f8d50-ee77-cd29-274b-6e9b2cc6df98"
          ),
        ));
        
        $response = curl_exec($curl);

        $err = curl_error($curl);

        curl_close($curl);
         if ($err) {
          return $err;
        } else {
          return $response;
        }
    }

    function get_key_company($cnpj,$chave) {

    	global $_serverEnvironment;

    	/*$token = ($_serverEnvironment == 'development') ? $chave["HOMOLOGATION"] : $chave["PRODUCTION"];
    	$env   = ($_serverEnvironment == 'development') ? "HOMOLOGATION" :"PRODUCTION";*/
 
 		$token = $chave["PRODUCTION"];
    	$env   = "PRODUCTION";

        $curl = curl_init();
         curl_setopt_array($curl, array(
          CURLOPT_URL => "https://api2.telecontrol.com.br/company/company/document/{$cnpj}",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "GET",
          CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "access-application-key: {$token}",
            "access-env: {$env}",
            "Content-Type: application/json"
          ),
        ));
        
        $response = curl_exec($curl);

        $err = curl_error($curl);

        curl_close($curl);
         if ($err) {
          return $err;
        } else {
          return $response;
        }
    }

    function get_key_user($fabrica) {

    	$dadosJson = ["email" => "batata.onildo@gmail.com", "senha" => "Kilgrave97"];
    	$dadosJson = json_encode($dadosJson);
 
        $curl = curl_init();
         curl_setopt_array($curl, array(
          CURLOPT_URL => "https://api2.telecontrol.com.br/user/userAuth",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => $dadosJson,
          CURLOPT_HTTPHEADER => array(
            "cache-control: no-cache",
            "access-application-key: 32e1ea7c54c0d7c144bc3d3045d8309a5b137af9",
            "access-env: PRODUCTION",
            "Content-Type: application/json"
          ),
        ));
        
        $response = curl_exec($curl);

        $err = curl_error($curl);

        curl_close($curl);
         if ($err) {
          return $err;
        } else {
          return $response;
        }
    }


	$fabricas = [125 => "61064838011763"];
	$tipo_dados = ["exportParts","exportConditions"];

	foreach ($fabricas as $login_fabrica => $cnpj) {	

		$dadosApi = get_app_key($login_fabrica)	;
		$dadosApi = json_decode($dadosApi,true);

		$chaves = arrayToken($dadosApi);
		
		$dadosCompany = get_key_company($cnpj,$chaves);
		$dadosCompany = json_decode($dadosCompany,true);

		/*$dadosUser = get_key_user();
		$dadosUser = json_decode($dadosUser,true);*/

		$hashCompany = $dadosCompany["company"]["internal_hash"]; 

		$hashUser = $dadosCompany["company"]["user_owner_external_hash"];

		foreach ($tipo_dados as $tipo) {
		
			switch ($tipo) {
				case 'exportConditions':
					$ativo = $_POST['ativos'] == 'true' ? 'AND tc.visivel' : '';

					$query_conds = "SELECT
						tc.condicao, tc.codigo_condicao, tc.visivel, tc.fabrica, tc.descricao, tc.limite_minimo
					FROM tbl_condicao tc
					JOIN tbl_fabrica tf ON tf.fabrica = tc.fabrica
					WHERE tf.fabrica = {$login_fabrica}
					AND json_field('companyExternalHash', tf.parametros_adicionais) IS NOT NULL
					AND json_field('integracaoParts', tf.parametros_adicionais)::boolean
					AND tc.campos_adicionais->>'partsExternalHash' IS NULL
					{$ativo}
					ORDER BY tc.condicao ASC
					LIMIT 100";

					$res_conds = pg_query($con, $query_conds);	
					if (strlen(pg_last_error()) >= 1 || pg_num_rows($res_conds) === 0) {
						$response = ['exception' => 'Ocorreu uma falha na exportação. Por favor, contate o suporte.'];
						break;
					}

					$loteAtual = pg_fetch_all($res_conds);

					$loteAtual = array_map(function ($p) {
						$desc = $p['descricao'];
						unset($p['descricao']);

						$p['descricao'] = $desc;
						$p['valorMinimo'] = $p['limite_minimo'];
						$p['codigo'] = $p['codigo_condicao'];
						$p['ativo'] = $p['visivel'] == 'f' ? 'false' : 'true';
						$p['fabrica'] = $login_fabrica;

						return $p;
					}, $loteAtual);

					$requestBody = [
						'user' => $hashUser,
						'company' => $hashCompany,
						'condicoes' => $loteAtual,
						'origin' => 'POSVENDA'
					];

					$encodedBody = recursiveEncode($requestBody);

					print_r($encodedBody);

					$partsCondicoesMirror = new Condicoes();
					try {
						$response = $partsCondicoesMirror->post($requestBody);
					} catch (Exception $e) {
						$response = ['exception' => $e->getMessage()];
						break;
					}

					if (array_key_exists('exception', $response)) {
						break;
					}

					$select_query = "
						SELECT campos_adicionais
						FROM tbl_condicao
						WHERE fabrica = $1
						AND condicao = $2";

					$update_query = "
						UPDATE tbl_condicao
						SET campos_adicionais = $1
						WHERE fabrica = $2
						AND condicao = $3";

					$failed = [];

					foreach ($loteAtual as $cond) {
						foreach ($response['condicoes'] as $condicao) {
							if ($condicao['codigo'] === $cond['codigo']) {

								// getting parametros_adicionais
								$res_select = pg_query_params(
									$con,
									$select_query,
									[$login_fabrica, $cond['condicao']]
								);

								if (strlen(pg_last_error()) >= 1 || pg_num_rows($res_select) >= 2) {
									$response['failed'][] = $condicao;
									continue;
								}

								// setting external hash - parts api
								$campos_adicionais = pg_fetch_result($res_select, 0, 'campos_adicionais');
								$campos_adicionais = json_decode($campos_adicionais, true);

								$campos_adicionais['partsExternalHash'] = $condicao['internal_hash'];
								$campos_adicionais = json_encode($campos_adicionais);

								// updating
								$res_update = pg_query_params(
									$con,
									$update_query,
									[$campos_adicionais, $login_fabrica, $cond['condicao']]
								);

								if (pg_affected_rows($res_update) >= 2 || strlen(pg_last_error()) >= 1) {
									$response['failed'][] = $condicao;
								}
							}
						}
					}

					break;
				case 'exportParts':

					$query_pecas = "SELECT
							tp.peca,
							tp.referencia,
							tp.descricao,
							tp.origem,
							tp.ativo,
							tp.ipi,
							tp.unidade,
							tp.voltagem,
							tp.ncm
						FROM tbl_peca tp
						JOIN tbl_fabrica tf ON tf.fabrica = tp.fabrica
						WHERE json_field('companyExternalHash', tf.parametros_adicionais) IS NOT NULL
						AND json_field('integracaoParts', tf.parametros_adicionais)::boolean
						AND json_field('partsExternalHash', tp.parametros_adicionais) IS NULL
						AND tp.ativo IS TRUE
						AND tp.produto_acabado IS NOT TRUE
						AND tf.fabrica = {$login_fabrica}
						ORDER BY tp.data_input ASC
						LIMIT 1";

					$res_pecas = pg_query($con, $query_pecas);
					if (strlen(pg_last_error()) >= 1 || pg_num_rows($res_pecas) === 0) {
						$response = ['exception' => 'Ocorreu uma falha na exportação. Por favor, contate o suporte.'];
						break;
					}

					$loteAtual = pg_fetch_all($res_pecas);
					$loteAtual = array_map(function ($p) use ($login_fabrica) {
						$desc = $p['descricao'];	
						unset($p['descricao']);

						$p['descricao'] = ['pt_br' => $desc];
						$p['calculo_externo'] = "true";
						$p['fabrica'] = $login_fabrica;

						return $p;
					}, $loteAtual);

					$requestBody = [
						"user" => $hashUser,
						"company" => $hashCompany,
						"itens" => $loteAtual,
						"origin" => "POSVENDA"
					];

					$encodedBody = recursiveEncode($requestBody);
					print_r($encodedBody);

					$partsItensMirror = new Itens();
					try {
						$response = $partsItensMirror->post($encodedBody);
					} catch (Exception $e) {
						$response = ['exception' => $e->getMessage()];
						break;
					}

					if (array_key_exists('exception', $response)) {
						break;
					}

					$select_query = "
						SELECT parametros_adicionais
						FROM tbl_peca
						WHERE fabrica = $1
						AND peca = $2";

					$update_query = "
						UPDATE tbl_peca
						SET parametros_adicionais = $1
						WHERE fabrica = $2
						AND peca = $3";

					$failed = [];
					foreach ($loteAtual as $peca) {
						foreach ($response['itens'] as $item) {
							if ($item['dados']['referencia'] === $peca['referencia']) {

								// getting parametros_adicionais
								$res_select = pg_query_params(
									$con,
									$select_query,
									[$login_fabrica, $peca['peca']]
								);

								if (strlen(pg_last_error()) >= 1 || pg_num_rows($res_select) >= 2) {
									$response['failed'][] = $item;
									continue;
								}

								// setting external hash - parts api
								$parametros_adicionais = pg_fetch_result($res_select, 0, 'parametros_adicionais');
								$parametros_adicionais = json_decode($parametros_adicionais, true);

								$parametros_adicionais['partsExternalHash'] = $item['internal_hash'];
								$parametros_adicionais = json_encode($parametros_adicionais);

								// updating
								$res_update = pg_query_params(
									$con,
									$update_query,
									[$parametros_adicionais, $login_fabrica, $peca['peca']]
								);

								if (pg_affected_rows($res_update) >= 2 || strlen(pg_last_error()) >= 1) {
									$response['failed'][] = $item;
								}
							}
						}
					}

					break;
				case 'genReport':
					$lotes = $_POST['excepts'];

					$path = "xls/integracao-" . date(dmyHis) . "-$login_fabrica.csv";
					$file = fopen($path, 'w');

					$headers = ["MENSAGEM", "CAMPO", "VALOR", "LINHA", "LOTE"];
					fputcsv($file, $headers);

					foreach ($lotes as $lote => $data) {
						foreach ($data as $exception_row) {
							$message = $exception_row['exception'];

							$headers = array_keys($exception_row['values']);
							$col = $headers[0];
							$value = $exception_row['values'][$headers[0]];
							$collection_key = !is_null($exception_row['line_number']) ? $exception_row['line_number'] : 'N/A';
							$lote = $lote;

							$row_data = array_map(function ($v) {
								return strtoupper($v);
							}, [$message, $col, $value, $collection_key, $lote]);

							fputcsv($file, $row_data);
						}
					}

					fclose($file);

					$response = ['path' => $path];
					break;

			}

		}

	}

	

?>