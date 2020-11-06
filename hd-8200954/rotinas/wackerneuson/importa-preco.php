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
	$msg_erro      = array();

	ini_set('default_socket_timeout', 800);

	if (ENV == "prod") {
		$soap = new SoapClient("http://201.91.139.164:8080/g5-senior-services/sapiens_Synccom_senior_g5_co_wacker_consulta_tabeladeprecos?wsdl", array("trace" => 1, "exception" => 1));
	} else {
		$soap = new SoapClient("http://187.87.251.133:8080/g5-senior-services/sapiens_Synccom_senior_g5_co_wacker_consulta_tabeladeprecos?wsdl", array("trace" => 1, "exception" => 1));
	}

	$argumentos = array(
		"user"       => "Telecontrol",
		"password"   => "Telecontrol",
		"encryption" => "0",
		"parameters" => array(
			"codEmp" => 1
		)
    );

    $metodo = "Consultar";

    $soapResult = $soap->__soapCall($metodo, $argumentos);

    if (strlen($soapResult->erroExecucao) > 0) {
    	throw new Exception($soapResult->erroExecucao);
    }

    if (count($soapResult->retornos->dadosGerais) > 0) {
    	foreach ($soapResult->retornos->dadosGerais as $key =>  $tabela) {
    		$sql = "SELECT tabela FROM tbl_tabela WHERE fabrica = {$login_fabrica} AND UPPER(sigla_tabela) = UPPER('{$tabela->codTpr}')";
    		$res = pg_query($con, $sql);

    		if (strlen(pg_last_error()) > 0) {
				$msg_erro["erro_tabela"][] = "Erro de execução ao importar a tabela {$tabela->codTpr}";
				continue;
			}

    		$ativo = (strtoupper($tabela->sitReg) == "A") ? "true" : "false";

    		if (!pg_num_rows($res)) {
			continue;
    		} else {
    			$tabela_id = pg_fetch_result($res, 0, "tabela");

    			$sql = "UPDATE tbl_tabela SET ativa = {$ativo} WHERE fabrica = {$login_fabrica} AND tabela = {$tabela_id}";
    			$res = pg_query($con, $sql);

    			if (strlen(pg_last_error()) > 0) {
    				$msg_erro["erro_tabela"][] = "Erro de execução ao importar a tabela {$tabela->codTpr}";
    				continue;
    			}
    		}

			foreach ($tabela->itens as $item) {
				$sql = "SELECT peca FROM tbl_peca WHERE fabrica = {$login_fabrica} AND UPPER(referencia) = UPPER('{$item->codPro}')";
				$res = pg_query($con, $sql);

				if (strlen(pg_last_error()) > 0) {
					$msg_erro["erro_item"][] = "Erro de execução ao importar o item {$item->codPro}, tabela {$tabela->codTpr}";
					continue;
				}

				if (!pg_num_rows($res)) {
					$msg_erro["peca_nao_encontrada"][] = "Peça {$item->codPro}, Tabela {$tabela->codTpr}";
				} else {
					$peca = pg_fetch_result($res, 0, "peca");

					$sql = "SELECT tabela_item FROM tbl_tabela_item WHERE tabela = {$tabela_id} AND peca = {$peca}";
					$res = pg_query($con, $sql);

					if (strlen(pg_last_error()) > 0) {
						$msg_erro["erro_item"][] = "Erro de execução ao importar o item {$item->codPro}, tabela {$tabela->codTpr}";
						continue;
					}

					if (pg_num_rows($res) > 0) {
						$tabela_item = pg_fetch_result($res, 0, "tabela_item");

						$sql = "UPDATE tbl_tabela_item SET preco = {$item->preBas} WHERE tabela_item = {$tabela_item}";
						$res = pg_query($con, $sql);

						if (strlen(pg_last_error()) > 0) {
							$msg_erro["erro_item"][] = "Erro de execução ao importar o item {$item->codPro}, tabela {$tabela->codTpr}";
							continue;
						}
					} else {
						$sql = "INSERT INTO tbl_tabela_item (tabela, peca, preco) VALUES ({$tabela_id}, {$peca}, {$item->preBas})";
						$res = pg_query($con, $sql);

						if (strlen(pg_last_error()) > 0) {
							$msg_erro["erro_item"][] = "Erro de execução ao importar o item {$item->codPro}, tabela {$tabela->codTpr}";
							continue;
						}
					}
				}
			}
    	}

		#Verificação dos Erros
    	if (count($msg_erro) > 0) {
    		system("mkdir /tmp/{$fabrica_nome}/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/" );
			system("mkdir /tmp/{$fabrica_nome}/preco/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/preco/" );

			$arquivo_erro_nome = "/tmp/{$fabrica_nome}/preco/importa-preco-".date("dmYH").".txt";
    		$arquivo_erro = fopen($arquivo_erro_nome, "w");
			
    		if (count($msg_erro["erro_tabela"]) > 0) {
    			fwrite($arquivo_erro, "<br />########## Tabelas não importadas ##########<br />");
				fwrite($arquivo_erro, implode("<br />", $msg_erro["erro_tabela"]));
    			fwrite($arquivo_erro, "<br />###############################################################################################<br />");
    			fwrite($arquivo_erro, "<br />");
    		}

    		if (count($msg_erro["peca_nao_encontrada"]) > 0) {
    			fwrite($arquivo_erro, "<br />########## Itens de tabela não importadas por cadastro ausente ##########<br />");
				fwrite($arquivo_erro, implode("<br />", $msg_erro["peca_nao_encontrada"]));
    			fwrite($arquivo_erro, "<br />################################################################################<br />");
    			fwrite($arquivo_erro, "<br />");
    		}

    		if (count($msg_erro["erro_item"]) > 0) {
    			fwrite($arquivo_erro, "<br />########## Itens de tabela não importados ##########<br />");
				fwrite($arquivo_erro, implode("<br />", $msg_erro["erro_item"]));
    			fwrite($arquivo_erro, "<br />##################################################################################<br />");
    			fwrite($arquivo_erro, "<br />");
    		}

			fclose($arquivo_erro);

			if (ENV == "dev") {
				mail("guilherme.curcio@telecontrol.com.br", "Telecontrol - Erro na importação de preços da Wacker Neuson", file_get_contents($arquivo_erro_nome), "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");
			} else {
				mail("helpdesk@telecontrol.com.br, vanilde.sartorelli@wackerneuson.com", "Telecontrol - Erro na importação de preços da Wacker Neuson", file_get_contents($arquivo_erro_nome), "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");
			}
    	}
    	###
    }
} catch(Exception $e) {
	print_r($e);
	exit;
	system("mkdir /tmp/{$fabrica_nome}/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/" );
	system("mkdir /tmp/{$fabrica_nome}/preco/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/preco/" );

	$arquivo_erro = fopen("/tmp/{$fabrica_nome}/preco/importa-preco-".date("dmYH").".txt", "w");
	fwrite($arquivo_erro, $e->getMessage());
	fclose($arquivo_erro);

	if (ENV == "dev") {
		mail("guilherme.curcio@telecontrol.com.br", "Telecontrol - Erro na importação de preços da Wacker Neuson", $e->getMessage());
	} else {
		mail("helpdesk@telecontrol.com.br, vanilde.sartorelli@wackerneuson.com", "Telecontrol - Erro na importação de preços da Wacker Neuson", $e->getMessage());
	}
}

?>
