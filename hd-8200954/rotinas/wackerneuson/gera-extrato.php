<?php

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../classes/Posvenda/Extrato.php';

	if ($_serverEnvironment == "production") {
                define("ENV", "prod");
        } else {
                define("ENV", "dev");
        }

	/*
	* Definições
	*/
	$fabrica 		= 143;
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
    $logClass->adicionaLog(array("titulo" => "Log erro Geração de Extrato Wacker Neuson")); // Titulo

    if (ENV == "prod") {
       $logClass->adicionaEmail("vanilde.sartorelli@wackerneuson.com");
       $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
    } else {
	   $logClass->adicionaEmail("guilherme.curcio@telecontrol.com.br");
    }

	/*
	* Extrato Class
	*/
    $classExtrato = new Extrato($fabrica);

    /*
	* Resgata o período dos 15 dias
    */
    $data_15 = $classExtrato->getPeriodoDias(14, $dia_extrato);

    /*
    * Resgata a quantidade de OS por Posto
    */
    $os_posto = $classExtrato->getOsPosto($dia_extrato, $fabrica);

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

        if ($env == "prod" && $posto == 6359) {
            continue;
        }

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
            * Relaciona as OSs com o Extrato
            */
            $classExtrato->relacionaExtratoOS($fabrica, $posto, $extrato, $dia_extrato);

            /*
            * verifica valor avulso para nao gerar extrato negativo
            */
            $classExtrato->verificaTotalAvulsos($posto, $extrato);
            
            if(($total_extrato - $total_avulso) <= 0){
                
                $classExtrato->_model->getPDO()->rollBack();
                // verificar mensgem com rodrigo
                throw new Exception("Extrato com valor negativo ou zerado acumulando");
            }

            /*
            * Insere lançamentos avulsos para o Posto
            */
            $classExtrato->atualizaAvulsosPosto($fabrica, $posto, $extrato);

            /*
            * Insere valor dos avulsos para o extrato
            */
            $classExtrato->atualizaValoresAvulsos($fabrica, $extrato);

            /*
            * Calcula o Extrato
            */
        	$total_extrato = $classExtrato->calcula($extrato);

        	/*
        	* Verifica Valor Mínimo
        	*/
        	// $classExtrato->verificaValorMinimoExtrato(250, $total_extrato);

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

        $fp = fopen("tmp/{$fabrica_nome}/pedidos/log-erro.text", "a");
        fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
        fwrite($fp, $msg_erro_arq . "\n \n");
        fclose($fp);

    }

	/*
	* Cron Término
	*/
	$phpCron->termino();

} catch (Exception $e) {
	echo $e->getMessage();
}

