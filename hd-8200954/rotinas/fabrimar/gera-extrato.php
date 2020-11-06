<?php

try {

	include dirname(__FILE__) . '/../../dbconfig.php';
	include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
	require dirname(__FILE__) . '/../funcoes.php';
	include dirname(__FILE__) . '/../../classes/Posvenda/Extrato.php';

    $env = ($_serverEnvironment == 'development') ? 'teste' : 'producao';

	/*
	* Definições
	*/
	$fabrica 		= 145;
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
    $logClass->adicionaLog(array("titulo" => "Log de erro - Geração de Extrato Fabrimar")); // Titulo
    
    if($env == "teste"){

        $logClass->adicionaEmail("guilherme.silva@telecontrol.com.br");

    }else{

        $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
        $logClass->adicionaEmail("fernando.saibro@fabrimar.com.br");
        $logClass->adicionaEmail("kevin.robinson@fabrimar.com.br");
        $logClass->adicionaEmail("anderson.dutra@fabrimar.com.br");

    }

	/*
	* Extrato Class
	*/
    $classExtrato = new Extrato($fabrica);
    
    /*
	* Resgata o período dos 15 dias
    */
    $data_15 = $classExtrato->getPeriodoDias(14, $dia_extrato);
    $data_15 = date('Y-m-d');

    /*
    * Resgata a quantidade de OS por Posto
    */
    $os_posto = $classExtrato->getOsPosto($dia_extrato, $fabrica);

    if(empty($os_posto)){
    	exit;
    }

    /**
    * Utiliza LGR
    */
    $usa_lgr = true;

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
            * Insere lançamentos avulsos para o Posto
            */
            $classExtrato->atualizaAvulsosPosto($fabrica, $posto, $extrato);

            /*
            * Relaciona as OSs com o Extrato
            */
            $classExtrato->relacionaExtratoOS($fabrica, $posto, $extrato, $dia_extrato);

            /*
            * Atualiza os valores avulso dos postos
            */
            $classExtrato->atualizaValoresAvulsos($fabrica);

            /*
            * Calcula o Extrato
            */
        	$total_extrato = $classExtrato->calcula($extrato);

            /**
            * Verifica LGR
            */
            if($usa_lgr == true){
                $classExtrato->verificaLGR($extrato, $posto, $data_15);
            }

            //Método para criar LGR
            $classExtrato->LGRNovo($extrato, $posto, $fabrica);

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

    $sql = "SELECT extrato,
	    	tbl_extrato.posto 
		FROM tbl_extrato 
		LEFT JOIN tbl_extrato_lgr USING(extrato)
		WHERE tbl_extrato.fabrica = $fabrica
		AND data_geracao::date = CURRENT_DATE
		AND extrato_lgr IS NULL";
    $res = pg_query($con,$sql);
    for($i =0; $i < pg_num_rows($res); $i++){
	    $posto = pg_fetch_result($res,$i,'posto');
	    $extrato = pg_fetch_result($res,$i,'extrato');
	$classExtrato->LGRNovo($extrato, $posto, $fabrica);
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

        $fp = fopen("tmp/{$fabrica_nome}/extratos/log-erro.text", "a");
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

