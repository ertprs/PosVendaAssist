<?php

try {
	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	include dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . "/../../classes/cep.php";

	if ($_serverEnvironment == "production") {
                define("ENV", "prod");
        } else {
                define("ENV", "dev");
        }

	$login_fabrica = 143;
	$fabrica_nome  = "wackerneuson";
	$msg_erro      = array();

	ini_set('default_socket_timeout', 800);

	if (ENV == "prod") {
		$soap = new SoapClient("http://201.91.139.164:8080/g5-senior-services/sapiens_Synccom_senior_g5_co_ger_cad_transportadora?wsdl", array("trace" => 1, "exception" => 1));
	} else {
		$soap = new SoapClient("http://187.87.251.133:8080/g5-senior-services/sapiens_Synccom_senior_g5_co_ger_cad_transportadora?wsdl", array("trace" => 1, "exception" => 1));
	}

	$argumentos = array(
		"user"       => "Telecontrol",
		"password"   => "Telecontrol",
		"encryption" => "0",
		"parameters" => array(
			"consultar" => array(
				"tipCon" => 2
			)
		)
    );

    $metodo = "ConsultarTransportadora";

    $soapResult = $soap->__soapCall($metodo, $argumentos);

    if (strlen($soapResult->erroExecucao) > 0) {
    	throw new Exception($soapResult->erroExecucao);
    }

    if (count($soapResult->retornoProcessamento) > 0) {
		$create_table = "CREATE TEMP TABLE temp_tbl_transportadora_wackerneuson ( 
			transportadora integer,
			transportadora_fabrica boolean,
			codigo text,
			nome text,
			nome_fantasia text,
			ie text,
			cnpj text,
			estado text,
			contato text,
			telefone text,
			fax text,
			endereco text,
			cep text,
			cidade text,
			bairro text
		)";
		$res = pg_query($con, $create_table);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro de execução ao importar transportadoras");
		}

    	foreach ($soapResult->retornoProcessamento as $transportadora) {
    		$erro = false;

    		if (empty($transportadora->nomTra)) {
    			continue;
    		}

			$codigo        = trim($transportadora->codTra);
			$nome          = preg_replace("/\'/", "", substr(trim($transportadora->nomTra), 0, 50));
			$nome_fantasia = preg_replace("/\'/", "", substr(trim($transportadora->apeTra), 0, 50));
			$ie            = trim($transportadora->insEst);
			$cnpj          = (string) preg_replace("/\D/", "", trim($transportadora->cgcCpf));
			$estado        = strtoupper(trim($transportadora->sigUfs));
			$contato       = substr(trim($transportadora->nomCto), 0, 30);
			$telefone      = trim($transportadora->fonTra);
			$fax           = trim($transportadora->faxTra);
			$endereco      = preg_replace("/\'/", "", trim($transportadora->endTra));
			$cep           = preg_replace("/\D/", "", trim($transportadora->cepTra));
			$cidade        = preg_replace("/\'/", "", trim($transportadora->cidTra));
			$bairro        = preg_replace("/\'/", "", substr(trim($transportadora->baiTra), 0, 40));

			if (empty($codigo)) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar a transportadora 'nome: {$nome}', código não informado";
			}

			if (!empty($ie) && strlen($ie) > 30) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar a transportadora 'nome: {$nome}', inscrição estadual não pode ter mais que 30 caracteres";
			}

			if (empty($cnpj)) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar a transportadora 'nome: {$nome}', cnpj não informado";
			}

			if (!empty($cnpj) && strlen($cnpj) > 14) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar a transportadora 'nome: {$nome}', cnpj não pode ter mais que 14 caracteres";
			}

			if (!empty($cnpj) && strlen($cnpj) <= 14) {
				$cnpj_valido = verificaCpfCnpj($cnpj);

				if ($cnpj_valido === false) {
					$erro = true;
					$msg_erro["campo_obrigatorio"][] = "Erro ao importar a transportadora 'nome: {$nome}', cnpj inválido";
				}
			}

			if (!empty($estado) && !in_array($estado, $arrayEstados)) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar a transportadora 'nome: {$nome}', estado inválido";
			}

			if (!empty($telefone) && strlen($telefone) > 30) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar a transportadora 'nome: {$nome}', telefone não pode ter mais que 30 caracteres";
			}

			if (!empty($fax) && strlen($fax) > 30) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar a transportadora 'nome: {$nome}', fax não pode ter mais que 30 caracteres";
			}

			if (!empty($cep) && strlen($cep) > 8) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar a transportadora 'nome: {$nome}', cep não pode ter mais que 8 caracteres";
			}

			$cidade = verificaCidade($cidade, $estado, $cep);

			if ($cidade === false) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar a transportadora 'nome: {$nome}', cidade inválida";
			} else {
				$cidade = preg_replace("/\'/", "", $cidade);
			}

			/*if (verificaCep($cep) === false) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar a transportadora 'nome: {$nome}', cep inválido";
			}*/

			if ($erro === true) {
				continue;
			}

			$insert = "INSERT INTO temp_tbl_transportadora_wackerneuson (
							codigo,
							nome,
							nome_fantasia,
							ie,
							cnpj,
							estado,
							contato,
							telefone,
							fax,
							endereco,
							cep,
							cidade,
							bairro
						) VALUES (
							'{$codigo}',
							'{$nome}',
							'{$nome_fantasia}',
							'{$ie}',
							'{$cnpj}',
							'{$estado}',
							'{$contato}',
							'{$telefone}',
							'{$fax}',
							'{$endereco}',
							'{$cep}',
							'{$cidade}',
							'{$bairro}'
						)";
			$res = pg_query($con, $insert);
    	}

    	#Verifica Transportadora
    	$update = "UPDATE temp_tbl_transportadora_wackerneuson 
    			   SET transportadora = tbl_transportadora.transportadora
    			   FROM tbl_transportadora 
    			   WHERE tbl_transportadora.cnpj = temp_tbl_transportadora_wackerneuson.cnpj";
    	$res = pg_query($con, $update);

    	if (strlen(pg_last_error()) > 0) {
    		throw new Exception("Erro de execução ao importar transportadoras");
    	}

    	$update = "UPDATE temp_tbl_transportadora_wackerneuson
    			   SET transportadora_fabrica = TRUE
    			   FROM tbl_transportadora_fabrica
    			   WHERE temp_tbl_transportadora_wackerneuson.transportadora IS NOT NULL
    			   AND temp_tbl_transportadora_wackerneuson.transportadora = tbl_transportadora_fabrica.transportadora
    			   AND tbl_transportadora_fabrica.fabrica = {$login_fabrica}";
    	$res = pg_query($con, $update);

    	if (strlen(pg_last_error()) > 0) {
    		throw new Exception("Erro de execução ao importar transportadoras");
    	}
    	###

    	#Verifica CNPJ duplicado
    	$select = "SELECT cnpj, COUNT(cnpj) FROM temp_tbl_transportadora_wackerneuson GROUP BY cnpj HAVING COUNT(cnpj) > 1";
    	$res = pg_query($con, $select);

    	if (strlen(pg_last_error()) > 0) {
    		throw new Exception("Erro de execução ao importar transportadoras");
    	}

    	if (pg_num_rows($res) > 0) {
    		while ($transportadora_cnpj_igual = pg_fetch_object($res)) {
    			$selectCnpj = "SELECT nome, codigo FROM temp_tbl_transportadora_wackerneuson WHERE cnpj = '{$transportadora_cnpj_igual->cnpj}'";
    			$resCnpj = pg_query($con, $selectCnpj);

    			$cnpj_igual = "<ul>";

    			while ($info_cnpj_igual = pg_fetch_object($resCnpj)) {
    				$cnpj_igual .= "<li>nome: {$info_cnpj_igual->nome}, codigo: {$info_cnpj_igual->codigo}</li>";
    			}

    			$cnpj_igual .= "</ul>";

    			$msg_erro["cnpj_igual"][] = "CNPJ: {$transportadora_cnpj_igual->cnpj} {$cnpj_igual}";

    			$delete = "DELETE FROM temp_tbl_transportadora_wackerneuson WHERE cnpj = '{$transportadora_cnpj_igual->cnpj}'";
    			$resDelete = pg_query($con, $delete);

    			if (strlen(pg_last_error()) > 0) {
	    			throw new Exception("Erro de execução ao importar transportadoras");
	    		}
    		}
	    }
    	###

    	#Insert Transportadora
    	$insert = "INSERT INTO tbl_transportadora (
    					nome,
						cnpj,
						fantasia,
						endereco,
						cidade,
						estado,
						ie,
						bairro,
						cep,
						fone,
						fax,
						contato
				   ) SELECT
						temp_tbl_transportadora_wackerneuson.nome,
						temp_tbl_transportadora_wackerneuson.cnpj,
						temp_tbl_transportadora_wackerneuson.nome_fantasia,
						temp_tbl_transportadora_wackerneuson.endereco,
						temp_tbl_transportadora_wackerneuson.cidade,
						temp_tbl_transportadora_wackerneuson.estado,
						temp_tbl_transportadora_wackerneuson.ie,
						temp_tbl_transportadora_wackerneuson.bairro,
						temp_tbl_transportadora_wackerneuson.cep,
						temp_tbl_transportadora_wackerneuson.telefone,
						temp_tbl_transportadora_wackerneuson.fax,
						temp_tbl_transportadora_wackerneuson.contato
				   FROM temp_tbl_transportadora_wackerneuson
				   WHERE transportadora IS NULL";
		$res = pg_query($con, $insert);

		if (strlen(pg_last_error()) > 0) {
    		throw new Exception("Erro de execução ao importar transportadoras");
    	}
    	###

    	#Update Transportadora
    	$update = "UPDATE tbl_transportadora SET
						nome     = temp_tbl_transportadora_wackerneuson.nome,
						fantasia = temp_tbl_transportadora_wackerneuson.nome_fantasia,
						endereco = temp_tbl_transportadora_wackerneuson.endereco,
						cidade   = temp_tbl_transportadora_wackerneuson.cidade,
						estado   = temp_tbl_transportadora_wackerneuson.estado,
						ie       = temp_tbl_transportadora_wackerneuson.ie,
						bairro   = temp_tbl_transportadora_wackerneuson.bairro,
						cep      = temp_tbl_transportadora_wackerneuson.cep,
						fone     = temp_tbl_transportadora_wackerneuson.telefone,
						fax      = temp_tbl_transportadora_wackerneuson.fax,
						contato  = temp_tbl_transportadora_wackerneuson.contato
				   FROM temp_tbl_transportadora_wackerneuson
				   WHERE temp_tbl_transportadora_wackerneuson.transportadora = tbl_transportadora.transportadora";
		$res = pg_query($con, $update);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro de execução ao importar transportadoras");
		}

		$update = "UPDATE temp_tbl_transportadora_wackerneuson 
    			   SET transportadora = tbl_transportadora.transportadora
    			   FROM tbl_transportadora 
    			   WHERE tbl_transportadora.cnpj = temp_tbl_transportadora_wackerneuson.cnpj
    			   AND temp_tbl_transportadora_wackerneuson.transportadora IS NULL";
    	$res = pg_query($con, $update);

    	if (strlen(pg_last_error()) > 0) {
    		throw new Exception("Erro de execução ao importar transportadoras");
    	}
    	###

    	#Verifica Código duplicado
    	$select = "SELECT 
    					tn.transportadora AS tn_transportadora,
    					tn.nome AS tn_nome, 
    					tn.cnpj AS tn_cnpj, 
    					tn.codigo AS tn_codigo,
    					tv.nome AS tv_nome,
    					tv.cnpj AS tv_cnpj,
    					tfv.codigo_interno AS tv_codigo
    			   FROM temp_tbl_transportadora_wackerneuson AS tn
    			   INNER JOIN tbl_transportadora_fabrica AS tfv ON tfv.codigo_interno = tn.codigo AND tfv.fabrica = {$login_fabrica} AND tfv.transportadora != tn.transportadora
    			   INNER JOIN tbl_transportadora AS tv ON tv.transportadora = tfv.transportadora
    			   WHERE tn.transportadora_fabrica IS NOT TRUE
    			   AND tn.transportadora IS NOT NULL";
    	$res = pg_query($con, $select);

    	if (strlen(pg_last_error()) > 0) {
    		throw new Exception("Erro de execução ao importar transportadoras");
    	}

    	if (pg_num_rows($res) > 0) {
	    	while ($transportadora_codigo_duplicado = pg_fetch_object($res)) {
	    		$msg_erro["codigo_duplicado"][] = "Erro ao importar a transportadora 'nome: {$transportadora_codigo_duplicado->tn_nome} - cnpj: {$transportadora_codigo_duplicado->tn_cnpj} - código: {$transportadora_codigo_duplicado->tn_codigo}', o código informado já está sendo usado pela transportadora 'nome: {$transportadora_codigo_duplicado->tv_nome} - cnpj: {$transportadora_codigo_duplicado->tv_cnpj} - código: {$transportadora_codigo_duplicado->tv_codigo}'";
	    		
	    		$delete    = "DELETE FROM temp_tbl_transportadora_wackerneuson WHERE transportadora = {$transportadora_codigo_duplicado->tn_transportadora}";
	    		$resDelete = pg_query($con, $delete);

	    		if (strlen(pg_last_error()) > 0) {
	    			throw new Exception("Erro de execução ao importar transportadoras");
	    		}
	    	}
	    }
    	###

    	#Insert Transportadora Fábrica
    	$insert = "INSERT INTO tbl_transportadora_fabrica (
    					transportadora,
    					fabrica,
    					codigo_interno,
    					contato_endereco,
    					contato_cidade,
    					contato_estado,
    					contato_bairro,
    					contato_cep,
    					fone,
    					fax,
    					contato
    			   ) SELECT
						temp_tbl_transportadora_wackerneuson.transportadora,
						{$login_fabrica},
						temp_tbl_transportadora_wackerneuson.codigo,
						temp_tbl_transportadora_wackerneuson.endereco,
						temp_tbl_transportadora_wackerneuson.cidade,
						temp_tbl_transportadora_wackerneuson.estado,
						temp_tbl_transportadora_wackerneuson.bairro,
						temp_tbl_transportadora_wackerneuson.cep,
						temp_tbl_transportadora_wackerneuson.telefone,
						temp_tbl_transportadora_wackerneuson.fax,
						temp_tbl_transportadora_wackerneuson.contato
				   FROM temp_tbl_transportadora_wackerneuson
				   WHERE temp_tbl_transportadora_wackerneuson.transportadora_fabrica IS NOT TRUE
				   AND temp_tbl_transportadora_wackerneuson.transportadora IS NOT NULL";
		$res = pg_query($con, $insert);

		if (strlen(pg_last_error()) > 0) {
    		throw new Exception("Erro de execução ao importar transportadoras");
    	}
    	###

    	#Update Transportadora Fábrica
    	$update = "UPDATE tbl_transportadora_fabrica SET
						codigo_interno = temp_tbl_transportadora_wackerneuson.codigo,
    					contato_endereco = temp_tbl_transportadora_wackerneuson.endereco,
    					contato_cidade = temp_tbl_transportadora_wackerneuson.cidade,
    					contato_estado = temp_tbl_transportadora_wackerneuson.estado,
    					contato_bairro = temp_tbl_transportadora_wackerneuson.bairro,
    					contato_cep = temp_tbl_transportadora_wackerneuson.cep,
    					fone = temp_tbl_transportadora_wackerneuson.telefone,
    					fax = temp_tbl_transportadora_wackerneuson.fax,
    					contato = temp_tbl_transportadora_wackerneuson.contato
    			   FROM temp_tbl_transportadora_wackerneuson
    			   WHERE temp_tbl_transportadora_wackerneuson.transportadora = tbl_transportadora_fabrica.transportadora
    			   AND tbl_transportadora_fabrica.fabrica = {$login_fabrica}
    			   AND temp_tbl_transportadora_wackerneuson.transportadora_fabrica IS TRUE";
    	$res = pg_query($con, $update);

    	if (strlen(pg_last_error()) > 0) {
    		throw new Exception("Erro de execução ao importar transportadoras");
    	}
    	###

		#Verificação dos Erros
		#echo "verificando erros\n";
    	if (count($msg_erro) > 0) {
    		system("mkdir /tmp/{$fabrica_nome}/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/" );
			system("mkdir /tmp/{$fabrica_nome}/transportadora/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/transportadora/" );

			$arquivo_erro_nome = "/tmp/{$fabrica_nome}/transportadora/importa-transportadora-".date("dmYH").".txt";
    		$arquivo_erro = fopen($arquivo_erro_nome, "w");
			
    		if (count($msg_erro["campo_obrigatorio"]) > 0) {
    			fwrite($arquivo_erro, "<br />########## Transportadoras não importadas por falta de informações ou informações incorretas ##########<br />");
				fwrite($arquivo_erro, implode("<br />", $msg_erro["campo_obrigatorio"]));
    			fwrite($arquivo_erro, "<br />###############################################################################################<br />");
    			fwrite($arquivo_erro, "<br />");
    		}

    		if (count($msg_erro["cnpj_igual"]) > 0) {
    			fwrite($arquivo_erro, "<br />########## Transportadoras não importadas por usso de cnpj igual ##########<br />");
				fwrite($arquivo_erro, implode("<br />", $msg_erro["cnpj_igual"]));
    			fwrite($arquivo_erro, "<br />###############################################################################################<br />");
    			fwrite($arquivo_erro, "<br />");
    		}


    		if (count($msg_erro["codigo_duplicado"]) > 0) {
    			fwrite($arquivo_erro, "<br />########## Transportadoras não importadas por uso de código duplicado ##########<br />");
				fwrite($arquivo_erro, implode("<br />", $msg_erro["codigo_duplicado"]));
    			fwrite($arquivo_erro, "<br />###############################################################################################<br />");
    			fwrite($arquivo_erro, "<br />");
    		}

			fclose($arquivo_erro);

			if (ENV == "dev") {
				mail("guilherme.curcio@telecontrol.com.br", "Telecontrol - Erro na importação de transportadoras da Wacker Neuson", file_get_contents($arquivo_erro_nome), "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");
			} else {
				mail("helpdesk@telecontrol.com.br, vanilde.sartorelli@wackerneuson.com", "Telecontrol - Erro na importação de transportadoras da Wacker Neuson", file_get_contents($arquivo_erro_nome), "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");
			}
    	}
    	###
    }

    #echo "fim do processo\n";
} catch(Exception $e) {
	system("mkdir /tmp/{$fabrica_nome}/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/" );
	system("mkdir /tmp/{$fabrica_nome}/transportadora/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/transportadora/" );

	$arquivo_erro = fopen("/tmp/{$fabrica_nome}/transportadora/importa-transportadora-".date("dmYH").".txt", "w");
	fwrite($arquivo_erro, $e->getMessage());
	fclose($arquivo_erro);

	if (ENV == "dev") {
		mail("guilherme.curcio@telecontrol.com.br", "Telecontrol - Erro na importação de transportadoras da Wacker Neuson", $e->getMessage());
	} else {
		mail("helpdesk@telecontrol.com.br, vanilde.sartorelli@wackerneuson.com", "Telecontrol - Erro na importação de transportadoras da Wacker Neuson", $e->getMessage());
	}
}

?>
