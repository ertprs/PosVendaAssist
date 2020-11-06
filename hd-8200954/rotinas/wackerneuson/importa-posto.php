<?php

try {
	include dirname(__FILE__) . '/../../dbconfig.php';
	#echo "include dbconfig.php\n";
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	#echo "include dbconnect-inc.php\n";
	include dirname(__FILE__) . '/../funcoes.php';
	#echo "include funcoes.php\n";
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
		$soap = new SoapClient("http://201.91.139.164:8080/g5-senior-services/sapiens_Synccom_senior_g5_co_wacker_consulta_cadastroclientes?wsdl", array("trace" => 1, "exception" => 1));
	} else {
		$soap = new SoapClient("http://187.87.251.133:8080/g5-senior-services/sapiens_Synccom_senior_g5_co_wacker_consulta_cadastroclientes?wsdl", array("trace" => 1, "exception" => 1));
	}
	#echo "conectou no wsdl\n";

	$argumentos = array(
		"user"       => "Telecontrol",
		"password"   => "Telecontrol",
		"encryption" => "0",
		"parameters" => array(
			"sitCli" => "A"
		)
    );

    $metodo = "ConsultaClientes";

    $soapResult = $soap->__soapCall($metodo, $argumentos);
    #echo "enviou requisição\n";

    if (strlen($soapResult->erroExecucao) > 0) {
    	#echo "deu erro na requisição\n";
    	throw new Exception($soapResult->erroExecucao);
    }

    if (count($soapResult->retornoClientes) > 0) {
    	#echo "retornou resultado\n";
		$create_table = "CREATE TEMP TABLE temp_tbl_posto_wackerneuson ( 
			posto integer,
			posto_fabrica boolean,
			nome text,
			nome_fantasia text,
			cpf_cnpj text,
			inscricao_estadual text,
			codigo text,
			endereco text,
			numero text,
			complemento text,
			bairro text,
			cep text,
			cidade text,
			estado text,
			email text,
			telefone text,
			fax text,
			contato text,
			banco text,
			agencia text,
			conta text,
			tipo_conta text,
			cpf_cnpj_favorecido text,
			nome_favorecido text,
			tipo_posto integer
		)";
		$res = pg_query($con, $create_table);

		if (strlen(pg_last_error()) > 0) {
			#echo "deu erro ao criar tabela temporaria\n";
			throw new Exception("Erro de execução ao importar postos autorizados");
		}
		#echo "criou a tabela temporaria\n";

		#echo "iniciando foreach nos resultados\n";
    	foreach ($soapResult->retornoClientes as $posto) {
    		$erro = false;

    		if (empty($posto->codRam) || (empty($posto->nomCli) && empty($posto->cpf_cnpj)) || !in_array($posto->codRam, array("C", "L", "RL"))) {
    			#echo "pulou registro\n";
    			continue;
    		}
    		#echo "processando {$posto->nomCli} {$posto->codCli}\n";

			$nome                = preg_replace("/\'/", "", substr(trim($posto->nomCli), 0, 150));
			$nome_fantasia       = preg_replace("/\'/", "", substr(trim($posto->apeCli), 0, 50));
			$cpf_cnpj            = (string) preg_replace("/\D/", "", trim($posto->cgcCpf));
			$inscricao_estadual  = trim($posto->insEst);
			$codigo              = trim($posto->codCli);
			$endereco            = preg_replace("/\'/", "", trim($posto->endCli));
			$numero              = trim($posto->nenCli);
			$complemento         = substr(trim($posto->cplEnd), 0, 20);
			$bairro              = preg_replace("/\'/", "", trim($posto->baiCli));
			$cep                 = preg_replace("/\D/", "", trim($posto->cepCli));
			$cidade              = preg_replace("/\'/", "", trim($posto->cidCli));
			$estado              = strtoupper(trim($posto->sigUfs));
			$email               = trim($posto->intNet);
			$telefone            = trim($posto->fonCli);
			$fax                 = trim($posto->faxCli);
			$contato             = substr(trim($posto->nomCto), 0, 60);
			$banco               = trim($posto->codBan);
			$agencia             = trim($posto->codAge);
			$conta               = trim($posto->ccbCli);
			$tipo_conta          = strtoupper(trim($posto->tipCta));
			$cpf_cnpj_favorecido = preg_replace("/\D/", "", trim($posto->cgcFav));
			$nome_favorecido     = preg_replace("/\'/", "", substr(trim($posto->nomFav), 0, 50));

			if (empty($nome) && !empty($cpf_cnpj)) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o posto 'cnpj: {$cpf_cnpj}', nome não informado";
			}

			if (empty($cpf_cnpj) && !empty($nome)) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o posto 'nome: {$nome}', cnpj não informado";
			}

			if (empty($codigo)) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o posto 'nome: {$nome} - cnpj: {$cpf_cnpj}', código não informado";
			}

			if (empty($endereco)) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o posto 'nome: {$nome} - cnpj: {$cpf_cnpj}', endereço não informado";
			}

			if (empty($cep)) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o posto 'nome: {$nome} - cnpj: {$cpf_cnpj}', cep não informado";
			}

			if (empty($cidade)) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o posto 'nome: {$nome} - cnpj: {$cpf_cnpj}', cidade não informada";
			}

			if (empty($estado)) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o posto 'nome: {$nome} - cnpj: {$cpf_cnpj}', estado não informado";
			}

			if (empty($telefone)) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o posto 'nome: {$nome} - cnpj: {$cpf_cnpj}', telefone não informado";
			}

			if (!empty($cpf_cnpj) && strlen($cpf_cnpj) > 14) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o posto 'nome: {$nome} - cnpj: {$cpf_cnpj}', cnpj não pode ter mais que 14 caracteres";
			}

			if (!empty($inscricao_estadual) && strlen($inscricao_estadual) > 30) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o posto 'nome: {$nome} - cnpj: {$cpf_cnpj}', inscrição estadual não pode ter mais que 30 caracteres";
			}

			if (!empty($codigo) && strlen($codigo) > 20) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o posto 'nome: {$nome} - cnpj: {$cpf_cnpj}', código não pode ter mais que 20 caracteres";
			}

			if (!empty($cep) && strlen($cep) > 8) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o posto 'nome: {$nome} - cnpj: {$cpf_cnpj}', cep não pode ter mais que 8 caracteres";
			}

			if (!empty($estado) && !in_array($estado, $arrayEstados)) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o posto 'nome: {$nome} - cnpj: {$cpf_cnpj}', estado inválido";
			}

			if (!empty($telefone) && strlen($telefone) > 30) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o posto 'nome: {$nome} - cnpj: {$cpf_cnpj}', telefone não pode ter mais que 30 caracteres";
			}

			if (!empty($fax) && strlen($fax) > 30) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o posto 'nome: {$nome} - cnpj: {$cpf_cnpj}', fax não pode ter mais que 30 caracteres";
			}

			if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o posto 'nome: {$nome} - cnpj: {$cpf_cnpj}', email inválido";
			}

			if (!empty($banco) && strlen($banco) > 5) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o posto 'nome: {$nome} - cnpj: {$cpf_cnpj}', o código do banco não pode ter mais que 5 caracteres";
			}

			if (!empty($agencia) && strlen($agencia) > 10) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o posto 'nome: {$nome} - cnpj: {$cpf_cnpj}', a agência não pode ter mais que 10 caracteres";
			}

			if (!empty($conta) && strlen($conta) > 20) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o posto 'nome: {$nome} - cnpj: {$cpf_cnpj}', o código da conta não pode ter mais que 20 caracteres";
			}

			if (!empty($cpf_cnpj_favorecido) && strlen($cpf_cnpj_favorecido) > 14) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o posto 'nome: {$nome} - cnpj: {$cpf_cnpj}', cpf/cnpj do favorecido não pode ter mais que 14 caracteres";
			}

			#echo "\t\t\tverificando cpf/cnpj do posto\n";
			if (!empty($cpf_cnpj) && strlen($cpf_cnpj) <= 14) {
				$cpf_cnpj_valido = verificaCpfCnpj($cpf_cnpj);

				if ($cpf_cnpj_valido === false) {
					$erro = true;
					$msg_erro["campo_obrigatorio"][] = "Erro ao importar o posto 'nome: {$nome} - cnpj: {$cpf_cnpj}', cnpj inválido";
				}
			}

			#echo "\t\t\tverificando cidade do posto {$cidade} {$estado} {$cep}\n";
			$cidade = verificaCidade($cidade, $estado, $cep);

			if ($cidade === false) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o posto 'nome: {$nome} - cnpj: {$cpf_cnpj}', cidade inválida";
			} else {
				$cidade = preg_replace("/\'/", "", $cidade);
			}

			/*if (verificaCep($cep) === false) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o posto 'nome: {$nome} - cnpj: {$cpf_cnpj}', cep inválido";
			}*/

			#echo "\t\t\tverificando tipo de conta\n";
			if (!empty($tipo_conta)) {
				switch ($conta) {
					case "CORRENTE":
						$conta = "Conta corrente";
						break;
					
					case "POUPANÇA":
						$conta = "Conta poupança";
						break;
				}
			}

			#echo "\t\t\tverificando codigo do posto\n";
			switch ($posto->codRam) {
				case "C":
					$tipo_posto = 436;
					break;

				case "L":
					$tipo_posto = 435;
					break;

				case "RL":
					$tipo_posto = 434;
					break;
			}

			if (!isset($tipo_posto) || empty($tipo_posto)) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o posto 'nome: {$nome} - cnpj: {$cpf_cnpj}', tipo de posto inválido";
			}

			if ($erro === true) {
				#echo "\t\t\tpulou registro\n";
				continue;
			}

			#echo "\t\t\tiniciando insert na tabela temporaria\n";
			$insert = "INSERT INTO temp_tbl_posto_wackerneuson (
							nome,
							nome_fantasia,
							cpf_cnpj,
							inscricao_estadual,
							codigo,
							endereco,
							numero,
							complemento,
							bairro,
							cep,
							cidade,
							estado,
							email,
							telefone,
							fax,
							contato,
							banco,
							agencia,
							conta,
							tipo_conta,
							cpf_cnpj_favorecido,
							nome_favorecido,
							tipo_posto
						) VALUES (
							'{$nome}',
							'{$nome_fantasia}',
							'{$cpf_cnpj}',
							'{$inscricao_estadual}',
							'{$codigo}',
							'{$endereco}',
							'{$numero}',
							'{$complemento}',
							'{$bairro}',
							'{$cep}',
							'{$cidade}',
							'{$estado}',
							'{$email}',
							'{$telefone}',
							'{$fax}',
							'{$contato}',
							'{$banco}',
							'{$agencia}',
							'{$conta}',
							'{$tipo_conta}',
							'{$cpf_cnpj_favorecido}',
							'{$nome_favorecido}',
							{$tipo_posto}
						)";
			$res = pg_query($con, $insert);
			// echo "\t\t\trealizou insert na tabela temporaria\n";
    	}

    	#echo "fim do processamento dos postos\n";

    	#$sql = "SELECT COUNT(*) AS qtde FROM temp_tbl_posto_wackerneuson";
    	#$res = pg_query($con, $sql);

    	#echo pg_fetch_result($res, 0, "qtde")." postos processados\n";

    	#echo "iniciando verificação de postos que já existem\n";
    	#Verifica Posto
    	$update = "UPDATE temp_tbl_posto_wackerneuson 
    			   SET posto = tbl_posto.posto
    			   FROM tbl_posto 
    			   WHERE tbl_posto.cnpj = temp_tbl_posto_wackerneuson.cpf_cnpj";
    	$res = pg_query($con, $update);

    	if (strlen(pg_last_error()) > 0) {
    		#echo "erro na verificação\n";
    		throw new Exception("Erro de execução ao importar postos autorizados");
    	}
    	#echo pg_affected_rows($res)." postos já existem\n";
    	#echo "fim da verificação\n";

    	#echo "iniciando verificação de postos que já existem para a fábrica\n";
    	$update = "UPDATE temp_tbl_posto_wackerneuson
    			   SET posto_fabrica = TRUE
    			   FROM tbl_posto_fabrica
    			   WHERE temp_tbl_posto_wackerneuson.posto IS NOT NULL
    			   AND temp_tbl_posto_wackerneuson.posto = tbl_posto_fabrica.posto
    			   AND tbl_posto_fabrica.fabrica = {$login_fabrica}";
    	$res = pg_query($con, $update);

    	if (strlen(pg_last_error()) > 0) {
    		#echo "erro na verificação\n";
    		throw new Exception("Erro de execução ao importar postos autorizados");
    	}
    	#echo pg_affected_rows($res)." postos já existem para a fábrica\n";
    	#echo "fim da verificação\n";
    	###

    	#Insert Posto
    	#echo "iniciando insert dos postos que não existem\n";
    	$insert = "INSERT INTO tbl_posto (
    					nome,
						nome_fantasia,
						cnpj,
						ie,
						endereco,
						numero,
						complemento,
						cep,
						cidade,
						estado,
						bairro,
						fone,
						fax,
						contato
				   ) SELECT
						temp_tbl_posto_wackerneuson.nome,
						temp_tbl_posto_wackerneuson.nome_fantasia,
						temp_tbl_posto_wackerneuson.cpf_cnpj,
						temp_tbl_posto_wackerneuson.inscricao_estadual,
						temp_tbl_posto_wackerneuson.endereco,
						temp_tbl_posto_wackerneuson.numero,
						temp_tbl_posto_wackerneuson.complemento,
						temp_tbl_posto_wackerneuson.cep,
						temp_tbl_posto_wackerneuson.cidade,
						temp_tbl_posto_wackerneuson.estado,
						temp_tbl_posto_wackerneuson.bairro,
						temp_tbl_posto_wackerneuson.telefone,
						temp_tbl_posto_wackerneuson.fax,
						temp_tbl_posto_wackerneuson.contato
				   FROM temp_tbl_posto_wackerneuson
				   WHERE posto IS NULL";
		$res = pg_query($con, $insert);

		if (strlen(pg_last_error()) > 0) {
			// echo "erro na inserção\n".pg_last_error()."\n";
    		throw new Exception("Erro de execução ao importar postos autorizados");
    	}
    	// echo pg_affected_rows($res)." postos inseridos\n";
    	// echo "fim da inserção\n";
    	###

		#echo "atualizando existência de postos que não existiam na temporaria\n";
		$update = "UPDATE temp_tbl_posto_wackerneuson 
    			   SET posto = tbl_posto.posto
    			   FROM tbl_posto 
    			   WHERE tbl_posto.cnpj = temp_tbl_posto_wackerneuson.cpf_cnpj
    			   AND temp_tbl_posto_wackerneuson.posto IS NULL";
    	$res = pg_query($con, $update);

    	if (strlen(pg_last_error()) > 0) {
    		// echo "erro na atualização\n";
    		throw new Exception("Erro de execução ao importar postos autorizados");
    	}
    	#echo pg_affected_rows($res)." postos atualizados\n";
    	#echo "fim da atualização\n";
    	###

    	#Verifica Código duplicado
    	#echo "iniciando verificação de posto com código duplicado\n";
    	$select = "SELECT 
    					pn.posto AS pn_posto,
    					pn.nome AS pn_nome, 
    					pn.cpf_cnpj AS pn_cnpj, 
    					pn.codigo AS pn_codigo,
    					pv.nome AS pv_nome,
    					pv.cnpj AS pv_cnpj,
    					pfv.codigo_posto AS pv_codigo
    			   FROM temp_tbl_posto_wackerneuson AS pn
    			   INNER JOIN tbl_posto_fabrica AS pfv ON pfv.codigo_posto = pn.codigo AND pfv.fabrica = {$login_fabrica} AND pfv.posto != pn.posto
    			   INNER JOIN tbl_posto AS pv ON pv.posto = pfv.posto
    			   WHERE pn.posto_fabrica IS NOT TRUE
    			   AND pn.posto IS NOT NULL";
    	$res = pg_query($con, $select);

    	if (strlen(pg_last_error()) > 0) {
			// echo "erro na verificação\n";
    		throw new Exception("Erro de execução ao importar postos autorizados");
    	}

    	if (pg_num_rows($res) > 0) {
	    	while ($posto_codigo_duplicado = pg_fetch_object($res)) {
	    		$msg_erro["codigo_duplicado"][] = "Erro ao importar o posto 'nome: {$posto_codigo_duplicado->pn_nome} - cnpj: {$posto_codigo_duplicado->pn_cnpj} - código: {$posto_codigo_duplicado->pn_codigo}', o código informado já está sendo usado pelo posto 'nome: {$posto_codigo_duplicado->pv_nome} - cnpj: {$posto_codigo_duplicado->pv_cnpj} - código: {$posto_codigo_duplicado->pv_codigo}'";
	    		
	    		$delete    = "DELETE FROM temp_tbl_posto_wackerneuson WHERE posto = {$posto_codigo_duplicado->pn_posto}";
	    		$resDelete = pg_query($con, $delete);

	    		if (strlen(pg_last_error()) > 0) {
	    			throw new Exception("Erro de execução ao importar postos autorizados");
	    		}
	    	}
	    }

    	// echo "fim da verificação\n";
    	###

    	#Insert Posto Fábrica
    	// echo "iniciando inserção de postos para a fábrica\n";
    	$insert = "INSERT INTO tbl_posto_fabrica (
    					fabrica,
    					senha,
    					posto,
						codigo_posto,
						contato_endereco,
						contato_numero,
						contato_complemento,
						contato_bairro,
						contato_cep,
						contato_cidade,
						contato_estado,
						contato_email,
						contato_fone_comercial,
						contato_fax,
						contato_nome,
						banco,
						agencia,
						conta,
						tipo_conta,
						cpf_conta,
						favorecido_conta,
						tipo_posto
    			   ) SELECT
						{$login_fabrica},
						'*',
						temp_tbl_posto_wackerneuson.posto,
						temp_tbl_posto_wackerneuson.codigo,
						temp_tbl_posto_wackerneuson.endereco,
						temp_tbl_posto_wackerneuson.numero,
						temp_tbl_posto_wackerneuson.complemento,
						temp_tbl_posto_wackerneuson.bairro,
						temp_tbl_posto_wackerneuson.cep,
						temp_tbl_posto_wackerneuson.cidade,
						temp_tbl_posto_wackerneuson.estado,
						temp_tbl_posto_wackerneuson.email,
						temp_tbl_posto_wackerneuson.telefone,
						temp_tbl_posto_wackerneuson.fax,
						temp_tbl_posto_wackerneuson.contato,
						temp_tbl_posto_wackerneuson.banco,
						temp_tbl_posto_wackerneuson.agencia,
						temp_tbl_posto_wackerneuson.conta,
						temp_tbl_posto_wackerneuson.tipo_conta,
						temp_tbl_posto_wackerneuson.cpf_cnpj_favorecido,
						temp_tbl_posto_wackerneuson.nome_favorecido,
						temp_tbl_posto_wackerneuson.tipo_posto
				   FROM temp_tbl_posto_wackerneuson
				   WHERE temp_tbl_posto_wackerneuson.posto_fabrica IS NOT TRUE
				   AND temp_tbl_posto_wackerneuson.posto IS NOT NULL";
		$res = pg_query($con, $insert);

		if (strlen(pg_last_error()) > 0) {
			// echo "erro na inserção linha 528\n";
    		throw new Exception("Erro de execução ao importar postos autorizados");
    	}
    	// echo pg_affected_rows($res)." postos inseridos\n";
    	// echo "fim da inserção\n";
    	###

    	#Update Posto Fábrica
    	// echo "atualizando postos da fábrica\n";
    	$update = "UPDATE tbl_posto_fabrica SET
						codigo_posto           = temp_tbl_posto_wackerneuson.codigo,
						contato_endereco       = temp_tbl_posto_wackerneuson.endereco,
						contato_numero         = temp_tbl_posto_wackerneuson.numero,
						contato_complemento    = temp_tbl_posto_wackerneuson.complemento,
						contato_bairro         = temp_tbl_posto_wackerneuson.bairro,
						contato_cep            = temp_tbl_posto_wackerneuson.cep,
						contato_cidade         = temp_tbl_posto_wackerneuson.cidade,
						contato_estado         = temp_tbl_posto_wackerneuson.estado,
						contato_email          = temp_tbl_posto_wackerneuson.email,
						contato_fone_comercial = temp_tbl_posto_wackerneuson.telefone,
						contato_fax            = temp_tbl_posto_wackerneuson.fax,
						contato_nome           = temp_tbl_posto_wackerneuson.contato,
						banco                  = temp_tbl_posto_wackerneuson.banco,
						agencia                = temp_tbl_posto_wackerneuson.agencia,
						conta                  = temp_tbl_posto_wackerneuson.conta,
						tipo_conta             = temp_tbl_posto_wackerneuson.tipo_conta,
						cpf_conta              = temp_tbl_posto_wackerneuson.cpf_cnpj_favorecido,
						favorecido_conta       = temp_tbl_posto_wackerneuson.nome_favorecido,
						tipo_posto             = temp_tbl_posto_wackerneuson.tipo_posto
    			   FROM temp_tbl_posto_wackerneuson
    			   WHERE temp_tbl_posto_wackerneuson.posto = tbl_posto_fabrica.posto
    			   AND tbl_posto_fabrica.fabrica = {$login_fabrica}
    			   AND temp_tbl_posto_wackerneuson.posto_fabrica IS TRUE";
    	$res = pg_query($con, $update);

    	if (strlen(pg_last_error()) > 0) {
    		// echo "erro ao atualizar 564\n";
    		throw new Exception("Erro de execução ao importar postos autorizados");
    	}
    	#echo pg_affected_rows($res)." postos atualizados\n";
    	// echo "fim da atualização\n";
    	###

		#Verificação dos Erros
		#echo "verificando erros\n";
    	if (count($msg_erro) > 0) {
    		system("mkdir /tmp/{$fabrica_nome}/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/" );
			system("mkdir /tmp/{$fabrica_nome}/posto/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/posto/" );

			$arquivo_erro_nome = "/tmp/{$fabrica_nome}/posto/importa-posto-".date("dmYH").".txt";
    		$arquivo_erro = fopen($arquivo_erro_nome, "w");
			
    		if (count($msg_erro["campo_obrigatorio"]) > 0) {
    			fwrite($arquivo_erro, "<br />########## Postos Autorizados não importados por falta de informações ou informações incorretas ##########<br />");
				fwrite($arquivo_erro, implode("<br />", $msg_erro["campo_obrigatorio"]));
    			fwrite($arquivo_erro, "<br />###############################################################################################<br />");
    			fwrite($arquivo_erro, "<br />");
    		}

    		if (count($msg_erro["codigo_duplicado"]) > 0) {
    			fwrite($arquivo_erro, "<br />########## Postos Autorizados não importados por uso de código duplicado ##########<br />");
				fwrite($arquivo_erro, implode("<br />", $msg_erro["codigo_duplicado"]));
    			fwrite($arquivo_erro, "<br />###############################################################################################<br />");
    			fwrite($arquivo_erro, "<br />");
    		}

			fclose($arquivo_erro);

			if (ENV == "dev") {
				mail("william.lopes@telecontrol.com.br", "Telecontrol - Erro na importação de postos autorizados da Wacker Neuson", file_get_contents($arquivo_erro_nome), "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");
			} else {
				mail("helpdesk@telecontrol.com.br, vanilde.sartorelli@wackerneuson.com", "Telecontrol - Erro na importação de postos autorizados da Wacker Neuson", file_get_contents($arquivo_erro_nome), "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");
			}
    	}
    	###
    }

    #echo "fim do processo\n";
} catch(Exception $e) {
	system("mkdir /tmp/{$fabrica_nome}/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/" );
	system("mkdir /tmp/{$fabrica_nome}/posto/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/posto/" );

	$arquivo_erro = fopen("/tmp/{$fabrica_nome}/posto/importa-posto-".date("dmYH").".txt", "w");
	fwrite($arquivo_erro, $e->getMessage());
	fclose($arquivo_erro);

	if (ENV == "dev") {
		mail("william.lopes@telecontrol.com.br", "Telecontrol - Erro na importação de postos autorizados da Wacker Neuson", $e->getMessage());
	} else {
		mail("helpdesk@telecontrol.com.br, vanilde.sartorelli@wackerneuson.com", "Telecontrol - Erro na importação de postos autorizados da Wacker Neuson", $e->getMessage());
	}
}

?>
