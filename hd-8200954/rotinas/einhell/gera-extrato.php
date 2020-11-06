<?php

error_reporting(E_ALL);

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Extrato.php';
    include dirname(__FILE__) . '/../../os_cadastro_unico/fabricas/160/classes/ExtratoEinhell.php';

    /*
    * Definições
    */
    $fabrica_nome   = "einhell";
    $fabrica        = 160;
    $dia_mes        = date('d');
    $dia_extrato    = date('Y-m-d H:i:s');

    /*
    * Cron Class
    */
    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    /*
    * Log Class
    */
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro Geração de Extrato - Einhell")); // Titulo
    $logClass->adicionaEmail("daniel.pereira@einhell.com");
    $logClass->adicionaEmail("luiz.munoz@einhell.com");

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

    /**
    * Utiliza LGR
    */

    /*
    * Mensagem de Erro
    */
    $msg_erro = "";
    $msg_erro_arq = "";

    for ($i = 0; $i < count($os_posto); $i++) {

        $posto          = $os_posto[$i]["posto"];
        $nome           = $os_posto[$i]["nome"];
        $codigo_posto   = $os_posto[$i]["codigo_posto"];
        $qtde           = $os_posto[$i]["qtde"];

        try {
            /*
            * Begin
            */
            $classExtrato->_model->getPDO()->beginTransaction();

                        /*
            * Regras Extrato Einhell
            */
            $classRegrasExtrato = new RegrasExtrato($classExtrato, $fabrica);

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

            //Pega as OSs que não tinham resposta no extrato anterior
            $classRegrasExtrato->osComRespostaSemAvulso($posto, $extrato);
            
            /** 
            * Bonificação Einhell
            */
            $classRegrasExtrato->bonificacaoMO($extrato, $posto);

            /*
            * Pega o total da mobra adicional
            */
            $mobra_adicional = $classRegrasExtrato->getMobraBonificada();
            $mobra_debito    = $classRegrasExtrato->getMobraDebito();

            /*
            * Calcula o Extrato
            */           
            $total_extrato = $classExtrato->calcula($extrato);

            /**
            * Verifica LGR
            */
            
            /*
            * Libera o extrato
            */
            #$classExtrato->liberaExtrato($extrato);

            /*
            * Commit
            */
			if($total_extrato >  0) {
				$classExtrato->_model->getPDO()->commit();
			}

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
        
        $fp = fopen("/tmp/{$fabrica_nome}/extrato-log-erro-".$dia_extrato.".log", "a");
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

