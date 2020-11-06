<?php

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	include '../../os_cadastro_unico/fabricas/151/classes/NotaFiscalServicoExtrato.php';

	$fabrica = 151;

	/*
	* Cron Class
	*/
	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();

	/*
	* Log Class
	*/
	$logClass = new Log2();
	$logClass->adicionaLog(array("titulo" => "Log erro Verifica��o de Nota Fiscal de Servi�os em Extratos - Mondial")); // Titulo
	$logClass->adicionaEmail("guilherme.silva@telecontrol.com.br");
	$msg_erro = "";

	$nfServico = new NotaFiscalServicoExtrato();

	/*
	* Verifica extratos
	*/
	try {

		/* Seleciona os extratos que est�o sem nota fiscal de servi�o h� mais de 3 meses */
		/* passa como par�metro do m�todo o per�odo em meses que deseja pesquisar */
		$extratos_postos = $nfServico->verificaExtratroSemNFServico(3);

		if($extratos_postos != false){

			foreach ($extratos_postos as $key => $value) {

				$nfServico->enviaComunicadoPosto($value);

			}

		}


	} catch (Exception $e){

		$msg_erro .= $e->getMessage()."<br />";
	    $msg_erro_arq .= $msg_erro . " - SQL: " . $classExtrato->getErro();

	}

	/*
	* Erro
	*/
	if(!empty($msg_erro)){

	    $logClass->adicionaLog($msg_erro);

	    if($logClass->enviaEmails() == "200"){
	      echo "Log de erro enviado com Sucesso!";
	    }else{
	      echo $logClass->enviaEmails();
	    }

	    $fp = fopen("tmp/{$fabrica_nome}/extrato/log-erro.txt", "a");
	    fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
	    fwrite($fp, $msg_erro_arq . "\n \n");
	    fclose($fp);

	}

	/*
	* Cron T�rmino
	*/
	$phpCron->termino();

?>
