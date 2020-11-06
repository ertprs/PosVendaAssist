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
		$create_table = "CREATE TEMP TABLE temp_tbl_produto_wackerneuson ( 
			produto integer,
			referencia text,
			descricao text,
			linha text,
			linha_id integer,
			familia text,
			familia_id integer,
			voltagem text,
			garantia integer,
			mao_de_obra double precision,
			mao_de_obra_admin double precision,
			troca_obrigatoria boolean
		)";
		$res = pg_query($con, $create_table);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro de execução ao importar produtos");
		}

    	foreach ($soapResult->retornosProdutos as $produto) {
    		if (in_array($produto->codFam, array("06.01", "06.02", "06.03")) || (empty($produto->codPro) && empty($produto->desPro))) {
    			continue;
    		}

    		$erro = false;

			$referencia        = trim($produto->codPro);
			$descricao         = trim($produto->desPro);
			$familia           = trim($produto->codFam);
			$linha             = trim($produto->codLin);
			$voltagem          = trim($produto->volPro);
			$garantia          = trim($produto->garPro);
			$mao_de_obra       = trim($produto->mdoPro);
			$mao_de_obra_admin = trim($produto->mdoAdm);
			$troca_obrigatoria = trim($produto->troObr);

			if ($troca_obrigatoria == "t") {
				$troca_obrigatoria = "true";
			} else {
				$troca_obrigatoria = "false";
			}

			if (!strlen($mao_de_obra)) {
				$mao_de_obra = 0;
			}

			if (!strlen($mao_de_obra_admin)) {
				$mao_de_obra_admin = 0;
			}

			if (!strlen($garantia)) {
				$garantia = 0;
			}

			if (empty($referencia) && !empty($descricao)) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o produto '{$descricao}', referência não informada";
			}

			if (empty($descricao) && !empty($referencia)) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o produto '{$referencia}', descrição não informada";
			}

			if (!empty($referencia) && strlen($referencia) > 20) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o produto '{$referencia} - {$descricao}', referência não pode ter mais de 20 caracteres";
			}

			if (empty($linha)) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o produto '{$referencia} - {$descricao}', linha não informada";
			}

			if (empty($familia)) {
				$erro = true;
				$msg_erro["campo_obrigatorio"][] = "Erro ao importar o produto '{$referencia} - {$descricao}', familia não informada";
			}

			if ($erro === true) {
				continue;
			}

			$insert = "INSERT INTO temp_tbl_produto_wackerneuson (
							referencia,
							descricao,
							linha,
							familia,
							voltagem,
							garantia,
							mao_de_obra,
							mao_de_obra_admin,
							troca_obrigatoria
						) VALUES (
							'{$referencia}',
							'{$descricao}',
							'{$linha}',
							'{$familia}',
							'{$voltagem}',
							{$garantia},
							{$mao_de_obra},
							{$mao_de_obra_admin},
							{$troca_obrigatoria}
						)";
			$res = pg_query($con, $insert);
    	}


    	#Verifica Linha
    	$update = "UPDATE temp_tbl_produto_wackerneuson 
    			   SET linha_id = tbl_linha.linha 
    			   FROM tbl_linha 
    			   WHERE tbl_linha.fabrica = {$login_fabrica} 
    			   AND UPPER(temp_tbl_produto_wackerneuson.linha) = UPPER(tbl_linha.codigo_linha)";
    	$res = pg_query($con, $update);

    	if (strlen(pg_last_error()) > 0) {
    		throw new Exception("Erro de execução ao importar produtos");
    	}

    	$select = "SELECT referencia, descricao, linha FROM temp_tbl_produto_wackerneuson WHERE linha_id IS NULL";
    	$res = pg_query($con, $select);

    	if (pg_num_rows($res) > 0) {
    		while ($produto_sem_linha = pg_fetch_object($res)) {
    			$msg_erro["sem_linha"][] = "O produto '{$produto_sem_linha->referencia} - {$produto_sem_linha->descricao}' não foi importado porque a linha '{$produto_sem_linha->linha}' não está cadastrada no sistema";
    		}
    	}

    	$delete = "DELETE FROM temp_tbl_produto_wackerneuson WHERE linha_id IS NULL";
    	$res = pg_query($con, $delete);

    	if (strlen(pg_last_error()) > 0) {
    		throw new Exception("Erro de execução ao importar produtos");
    	}
    	###

    	#Verifica Família
    	$update = "UPDATE temp_tbl_produto_wackerneuson 
    			   SET familia_id = tbl_familia.familia 
    			   FROM tbl_familia 
    			   WHERE tbl_familia.fabrica = {$login_fabrica} 
    			   AND UPPER(temp_tbl_produto_wackerneuson.familia) = UPPER(tbl_familia.codigo_familia)";
    	$res = pg_query($con, $update);

    	if (strlen(pg_last_error()) > 0) {
    		throw new Exception("Erro de execução ao importar produtos");
    	}

    	$select = "SELECT referencia, descricao, familia FROM temp_tbl_produto_wackerneuson WHERE familia_id IS NULL";
    	$res = pg_query($con, $select);

    	if (pg_num_rows($res) > 0) {
    		while ($produto_sem_linha = pg_fetch_object($res)) {
    			$msg_erro["sem_familia"][] = "O produto '{$produto_sem_linha->referencia} - {$produto_sem_linha->descricao}' não foi importado porque a família '{$produto_sem_linha->familia}' não está cadastrada no sistema";
    		}
    	}

    	$delete = "DELETE FROM temp_tbl_produto_wackerneuson WHERE familia_id IS NULL";
    	$res = pg_query($con, $delete);

    	if (strlen(pg_last_error()) > 0) {
    		throw new Exception("Erro de execução ao importar produtos");
    	}
    	###

    	#Verifica Produto
    	$update = "UPDATE temp_tbl_produto_wackerneuson 
    			   SET produto = tbl_produto.produto
    			   FROM tbl_produto
    			   WHERE tbl_produto.fabrica_i = {$login_fabrica} 
    			   AND UPPER(temp_tbl_produto_wackerneuson.referencia) = UPPER(tbl_produto.referencia)";
    	$res = pg_query($con, $update);

    	if (strlen(pg_last_error()) > 0) {
    		throw new Exception("Erro de execução ao importar produtos");
    	}
    	###

    	#Insert Produto
    	$insert = "INSERT INTO tbl_produto (
    					fabrica_i,
    					referencia,
    					descricao,
    					linha,
    					familia,
    					voltagem,
    					garantia,
    					mao_de_obra,
    					mao_de_obra_admin,
    					troca_obrigatoria
				   ) SELECT
						{$login_fabrica},
						temp_tbl_produto_wackerneuson.referencia,
						SUBSTR(temp_tbl_produto_wackerneuson.descricao, 0, 80),
						temp_tbl_produto_wackerneuson.linha_id,
						temp_tbl_produto_wackerneuson.familia_id,
						temp_tbl_produto_wackerneuson.voltagem,
						temp_tbl_produto_wackerneuson.garantia,
						temp_tbl_produto_wackerneuson.mao_de_obra,
						temp_tbl_produto_wackerneuson.mao_de_obra_admin,
						temp_tbl_produto_wackerneuson.troca_obrigatoria
				   FROM temp_tbl_produto_wackerneuson
				   WHERE produto IS NULL";
		$res = pg_query($con, $insert);

		if (strlen(pg_last_error()) > 0) {
    		throw new Exception("Erro de execução ao importar produtos");
    	}
    	###

    	#Update Produto
    	$update = "UPDATE tbl_produto SET
						linha             = temp_tbl_produto_wackerneuson.linha_id,
						familia           = temp_tbl_produto_wackerneuson.familia_id,
						voltagem          = temp_tbl_produto_wackerneuson.voltagem,
						garantia          = temp_tbl_produto_wackerneuson.garantia,
						mao_de_obra       = temp_tbl_produto_wackerneuson.mao_de_obra,
						mao_de_obra_admin = temp_tbl_produto_wackerneuson.mao_de_obra_admin,
						troca_obrigatoria = temp_tbl_produto_wackerneuson.troca_obrigatoria
				   FROM temp_tbl_produto_wackerneuson
				   WHERE tbl_produto.fabrica_i = {$login_fabrica}
				   AND tbl_produto.produto = temp_tbl_produto_wackerneuson.produto";
		$res = pg_query($con, $update);

		if (strlen(pg_last_error()) > 0) {
			throw new Exception("Erro de execução ao importar produtos");
		}
    	###

		#Verificação dos Erros
    	if (count($msg_erro) > 0) {
    		system("mkdir /tmp/{$fabrica_nome}/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/" );
			system("mkdir /tmp/{$fabrica_nome}/produto/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/produto/" );

			$arquivo_erro_nome = "/tmp/{$fabrica_nome}/produto/importa-produto-".date("dmYH").".txt";
    		$arquivo_erro = fopen($arquivo_erro_nome, "w");
			
    		if (count($msg_erro["campo_obrigatorio"]) > 0) {
    			fwrite($arquivo_erro, "<br />########## Produtos não importados por falta de informações ou informações incorretas ##########<br />");
				fwrite($arquivo_erro, implode("<br />", $msg_erro["campo_obrigatorio"]));
    			fwrite($arquivo_erro, "<br />###############################################################################################<br />");
    			fwrite($arquivo_erro, "<br />");
    		}

    		if (count($msg_erro["sem_linha"]) > 0) {
    			fwrite($arquivo_erro, "<br />########## Produtos não importados por linha não cadastrada no sistema ##########<br />");
				fwrite($arquivo_erro, implode("<br />", $msg_erro["sem_linha"]));
    			fwrite($arquivo_erro, "<br />################################################################################<br />");
    			fwrite($arquivo_erro, "<br />");
    		}

    		if (count($msg_erro["sem_familia"]) > 0) {
    			fwrite($arquivo_erro, "<br />########## Produtos não importados por família não cadastrada no sistema ##########<br />");
				fwrite($arquivo_erro, implode("<br />", $msg_erro["sem_familia"]));
    			fwrite($arquivo_erro, "<br />##################################################################################<br />");
    			fwrite($arquivo_erro, "<br />");
    		}

			fclose($arquivo_erro);

			if (ENV == "dev") {
				mail("guilherme.curcio@telecontrol.com.br", "Telecontrol - Erro na importação de produtos da Wacker Neuson", file_get_contents($arquivo_erro_nome), "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");
			} else {
				mail("helpdesk@telecontrol.com.br, vanilde.sartorelli@wackerneuson.com", "Telecontrol - Erro na importação de produtos da Wacker Neuson", file_get_contents($arquivo_erro_nome), "MIME-Version: 1.0 \r\nContent-type: text/html; charset=iso-8859-1 \r\n");
			}
    	}
    	###
    }
} catch(Exception $e) {
	system("mkdir /tmp/{$fabrica_nome}/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/" );
	system("mkdir /tmp/{$fabrica_nome}/produto/ 2> /dev/null ; chmod 777 /tmp/{$fabrica_nome}/produto/" );

	$arquivo_erro = fopen("/tmp/{$fabrica_nome}/produto/importa-produto-".date("dmYH").".txt", "w");
	fwrite($arquivo_erro, $e->getMessage());
	fclose($arquivo_erro);

	if (ENV == "dev") {
		mail("guilherme.curcio@telecontrol.com.br", "Telecontrol - Erro na importação de produtos da Wacker Neuson", $e->getMessage());
	} else {
		mail("helpdesk@telecontrol.com.br, vanilde.sartorelli@wackerneuson.com", "Telecontrol - Erro na importação de produtos da Wacker Neuson", $e->getMessage());
	}
}

?>
