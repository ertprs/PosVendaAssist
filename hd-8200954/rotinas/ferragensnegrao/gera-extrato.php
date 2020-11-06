<?php

error_reporting(E_ALL);

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../classes/Posvenda/Extrato.php';

	/*
	* Defini��es
	*/
	$fabrica 		= 146;
	$dia_mes     	= date('d');
	$dia_extrato 	= date('Y-m-d H:i:s');

	#$dia_mes     = "27";
	#$dia_extrato = "2014-08-27 23:59:00";

	/*
	* Cron Class
	*/
	$phpCron = new PHPCron($fabrica, __FILE__);
	$phpCron->inicio();

	/*
	* Log Class
	*/
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro Gera��o de Extrato Ferragens Negr�o")); // Titulo
	if ($_serverEnvironment != "production") {
		$logClass->adicionaEmail("guilherme.curcio@telecontrol.com.br");
	} else {
		$logClass->adicionaEmail("marcelo@worker.ind.br");
		$logClass->adicionaEmail("sac@matsuyama.ind.br");
		$logClass->adicionaEmail("assistencia@worker.ind.br");
	}

	/*
	* Extrato Class
	*/
    $classExtrato = new Extrato($fabrica);

    /*
	* Resgata o per�odo dos 15 dias
    */
    $data_15 = $classExtrato->getPeriodoDias(14, $dia_extrato);
    $data_15 = date('Y-m-d');

    /*
    * Resgata a quantidade de OS por Posto
    */
    $extrato_marca = true;
    $os_posto = $classExtrato->getOsPosto($dia_extrato, $fabrica, $extrato_marca);

    if(empty($os_posto)){
    	exit;
    }

    /*
    * Mensagem de Erro
    */
    $msg_erro = "";
    $msg_erro_arq = "";

	for ($i = 0; $i < count($os_posto); $i++) {

		$posto 			= $os_posto[$i]["posto"];
		$nome 			= $os_posto[$i]["nome"];
		$codigo_posto 	= $os_posto[$i]["codigo_posto"];
		$qtde  			= $os_posto[$i]["qtde"];
        $marca          = $os_posto[$i]["marca"];

		try {
            /*
            * Begin
            */
            $classExtrato->_model->getPDO()->beginTransaction();

			/*
            * Insere o Extrato para o Posto
            */
            $classExtrato->insereExtratoPosto($fabrica, $posto, $dia_extrato, $mao_de_obra = 0, $pecas = 0, $total = 0, $avulso = 0);

            /*
            * Resgata o numero do Extrato
            */
            $extrato = $classExtrato->getExtrato();

            /*
            * Insere lan�amentos avulsos para o Posto
            */
            $classExtrato->atualizaAvulsosPosto($fabrica, $posto, $extrato);

            /*
            * Relaciona as OSs com o Extrato
            */
            $classExtrato->relacionaExtratoOS($fabrica, $posto, $extrato, $dia_extrato, $marca);

            /*
            * Atualiza os valores avulso dos postos
            */
            $classExtrato->atualizaValoresAvulsos($fabrica, $extrato);

            /*
            * Calcula o Extrato
            */
        	$total_extrato = $classExtrato->calcula($extrato);

            /**
            * Verifica LGR
            */
            $lgr_troca_produto = true;
            $classExtrato->verificaLGR($extrato, $posto, $data_15, $fabrica, $lgr_troca_produto);

			/*
            * Commit
            */
            $classExtrato->_model->getPDO()->commit();

		} catch (Exception $e){

			$msg_erro .= $e->getMessage()."<br />";
            $msg_erro_arq .= $msg_erro . " - SQL: " . $classExtrato->getErro();

            /*
            * Rollback
            */
            $classExtrato->_model->getPDO()->rollBack();

		}

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

        $fp = fopen("tmp/{$fabrica_nome}/extratos/".date("d-m-Y")."txt", "a");
        fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
        fwrite($fp, $msg_erro_arq . "\n \n");
        fclose($fp);

    }

	/*
	* Cron T�rmino
	*/
	$phpCron->termino();

} catch (Exception $e) {
	echo $e->getMessage();
}

