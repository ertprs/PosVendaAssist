<?php
try {
	//consulta lorenzetti
	$busca_ura = ($_GET["busca_ura"] == true) ? true : false;
	$method = $_GET["method"];
	$cep    = preg_replace("/\D/", "", $_GET["cep"]);
	$state = $_GET['state'];
	$tela = $_GET['tela'];

	if ((empty($method) || $method == "webservice") && empty($state) ) {
		$soapClient = new SoapClient("https://apps.correios.com.br/SigepMasterJPA/AtendeClienteService/AtendeCliente?wsdl");
		$address = $soapClient->consultaCEP(array('cep'=>$cep));

		if (is_object($address)) {

			//print_r($address);//exit;

			include "../dbconfig.php";
			include "../includes/dbconnect-inc.php";

			$cidade = utf8_decode($address->return->cidade);
			$estado = $address->return->uf;
			$logradouro = utf8_decode($address->return->end);

			$bairro = utf8_decode($address->return->bairro);

			// verificar se no bairro tem ();
			// No bairro o distrito vem entre ()
			// Na cidade a cidade vem entre ()
			if (strpos($bairro, "(")) {
				$inicio_distrito = strpos($bairro, "(");
				$fim_distrito = strpos($bairro, ")", $inicio_distrito) + 1;
				$distrito = substr($bairro, $inicio_distrito+1, ($fim_distrito - $inicio_distrito) - 2);
				$bairro = strstr($bairro, "(", true);
			}elseif (strpos($cidade, "(")) {
				$distrito = strstr($cidade, "(", true);
				$inicio_distrito = strpos($cidade, "(");
				$fim_distrito = strpos($cidade, ")", $inicio_distrito) + 1;
				$cidade_nome = substr($cidade, 0, $inicio_distrito-1);
				$cidade = substr($cidade, $inicio_distrito+1, ($fim_distrito - $inicio_distrito) - 2);
			}

			//$bairro = substr($bairro,0,58);

			$cidade = str_replace("'", "", $cidade);
			$sql = "SELECT cidade,cod_ibge FROM tbl_cidade WHERE UPPER(fn_retira_especiais(nome)) = UPPER(fn_retira_especiais(trim('{$cidade}'))) AND UPPER(estado) = UPPER('{$estado}')";
			$res = pg_query($con, $sql);
			//echo $sql . " \n";
			$cep    = preg_replace("/\D/", "", $cep);

			if (pg_num_rows($res)==0) {
				//verificar se tem cod_ibge na tbl_ibge,
				$sql = "SELECT cod_ibge FROM tbl_ibge WHERE UPPER(fn_retira_especiais(cidade_pesquisa)) = UPPER(fn_retira_especiais('{$cidade}')) AND UPPER(estado) = UPPER('{$estado}')";
				$res = pg_query($con, $sql);

				if (pg_num_rows($res)==0) {

					$sql = "SELECT logradouro,bairro,cidade,estado FROM tbl_cep WHERE cep = '$cep'";
					$res = pg_query($con, $sql);
					
					if (pg_num_rows($res)==0) {

						//se nao tiver o cod_ibge retornar msg de erro.
						//$sql = "INSERT INTO tbl_cidade (nome, estado, cep) VALUES ('{$cidade}', '{$estado}', '{$cep}')";
						//$res = pg_query($con, $sql);

						if (strpos($_SERVER['HTTP_REFERER'], "posto_cadastro.php")) {
							$msg_erro = "NÃO É POSSÍVEL REALIZAR A ALTERAÇÃO NOS ITENS ABAIXO. POR FAVOR UTILIZE A SOLICITAÇÃO DE ALTERAÇÃO DE DADOS CADASTRAIS NO FINAL DA PÁGINA.";
						}else{
							$msg_erro = "IBGE NAO ENCONTRADO";
						}
					}else{
						$cidade_nome = pg_fetch_result($res,0,'cidade');
						$logradouro = pg_fetch_result($res,0,'logradouro');
						$bairro = pg_fetch_result($res,0,'bairro');
						$estado = pg_fetch_result($res,0,'estado');
					}


				}else{
					//insere o cod_ibge na tbl_cidade
					$cod_ibge = pg_fetch_result($res, 0, cod_ibge);
					$sql = "INSERT INTO tbl_cidade (nome, estado, cep,cod_ibge) VALUES ('{$cidade}', '{$estado}', '44444444','{$cod_ibge}')";
					//$res = pg_query($con, $sql);
				}
			}else{
				$cod_ibge = pg_fetch_result($res, 0, cod_ibge);
			}

			if (empty($msg_erro)) {
				$bairro = str_replace("'","\\'",$bairro);
				$cepx = str_replace("-","",$cep);
				$cepx = str_replace(".","",$cepx);
				$sql = "SELECT cep FROM tbl_cep WHERE cep = '{$cepx}'";
				$res = pg_query($con, $sql);

				if (!pg_num_rows($res)) {
					$bairro = substr($bairro,0,58);
					$sql = "INSERT INTO tbl_cep(cep,logradouro,bairro,cidade,estado) VALUES('{$cepx}','{$logradouro}','{$bairro}','{$cidade}','{$estado}')";
					$res = pg_query($con, $sql);
				}

				if(strlen(trim($cidade_nome)) > 1) {
					$address->return->cidade = $cidade_nome;
				}else{
					$address->return->cidade = utf8_decode($address->return->cidade);
				}


				$address->return->cidade = str_replace('-',' ',$address->return->cidade);
				$address->return->cidade = str_replace("'",'',$address->return->cidade);

				$sql = "SELECT UPPER(fn_retira_especiais('".($address->return->cidade)."')) AS cidade";
				$res = pg_query($con,$sql);
				$address->return->cidade = pg_fetch_result($res,0,"cidade");
				if ($busca_ura) {
					return utf8_decode("ok;{$address->return->end};{$address->return->bairro};{$address->return->cidade};{$address->return->uf};{$cod_ibge}");
				} else {
					echo utf8_decode("ok;{$address->return->end};{$address->return->bairro};{$address->return->cidade};{$address->return->uf};{$cod_ibge}");
				}
			}else{
				if ($busca_ura) {
					return $msg_erro;
				} else {
					echo $msg_erro;
				}
			}

		} else {
			if ($busca_ura) {
				return "CEP NAO ENCONTRADO";
			} else {
				echo "CEP NAO ENCONTRADO";
			}
		}
	} elseif( $method == "database" && empty($state) ) {
		include "../dbconfig.php";
		include "../includes/dbconnect-inc.php";
		$cep = str_replace("-","",$cep);
		$cep = str_replace(".","",$cep);
		$sql = "SELECT logradouro, bairro, cidade, estado FROM tbl_cep WHERE cep = '{$cep}'";
		$res = pg_query($con, $sql);

		if (pg_num_rows($res) > 0) {
			$end    = pg_fetch_result($res, 0, "logradouro");
			$bairro = pg_fetch_result($res, 0, "bairro");
			$cidade = pg_fetch_result($res, 0, "cidade");
			$uf     = pg_fetch_result($res, 0, "estado");
			$cidade = str_replace("-", " ", $cidade);
			$cidade = str_replace("'", "", $cidade);
			if ($busca_ura) {
				
				return "ok;{$end};{$bairro};{$cidade};{$uf}";
			} else {
				echo "ok;{$end};{$bairro};{$cidade};{$uf}";
			}
		} else {
			if ($busca_ura) {
				return "CEP NAO ENCONTRADO";
			} else {
				echo "CEP NAO ENCONTRADO";
			}
		}
	} elseif(!empty($state) ) {
		include "../dbconfig.php";
		include "../includes/dbconnect-inc.php";
		$array_estados = array(
					    'AC' => 'Acre',             'AL' => 'Alagoas',             'AM' => 'Amazonas',
					    'AP' => 'Amapá',            'BA' => 'Bahia',               'CE' => 'Ceará',
					    'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo',      'GO' => 'Goiás',
					    'MA' => 'Maranhão',         'MG' => 'Minas Gerais',        'MS' => 'Mato Grosso do Sul',
					    'MT' => 'Mato Grosso',      'PA' => 'Pará',                'PB' => 'Paraíba',
					    'PE' => 'Pernambuco',       'PI' => 'Piauí',               'PR' => 'Paraná',
					    'RJ' => 'Rio de Janeiro',   'RN' => 'Rio Grande do Norte', 'RO'=>'Rondônia',
					    'RR' => 'Roraima',          'RS' => 'Rio Grande do Sul',   'SC' => 'Santa Catarina',
					    'SE' => 'Sergipe',          'SP' => 'São Paulo',           'TO' => 'Tocantins'
					);
		if (array_key_exists($state, $array_estados)) {
	        $sql = "SELECT DISTINCT * FROM (
	                    SELECT UPPER(fn_retira_especiais(nome)) AS cidade FROM tbl_cidade WHERE UPPER(estado) = UPPER('{$state}')
	                    UNION (
	                        SELECT UPPER(fn_retira_especiais(cidade)) AS cidade FROM tbl_ibge WHERE UPPER(estado) = UPPER('{$state}')
	                    )
	                ) AS cidade
	                ORDER BY cidade ASC";
	        $res = pg_query($con, $sql);

	        if (pg_num_rows($res) > 0) {
	            $array_cidades = array();

	            while ($result = pg_fetch_object($res)) {
	                $array_cidades[] = $result->cidade;
	            }

	            $retorno = array("cidades" => $array_cidades);
	        } else {
	            $retorno = array("error" => utf8_encode("nenhuma cidade encontrada para o estado: {$state}"));
	        }
	    } else {
	        $retorno = array("error" => utf8_encode("estado não encontrado"));
	    }
	    //print_r($retorno);exit;
	    if ($busca_ura) {
			return $retorno;
		} else {
			echo (json_encode($retorno));
		}
	    
	}
} catch (Exception $e) {
	echo $e->getMessage();
}
