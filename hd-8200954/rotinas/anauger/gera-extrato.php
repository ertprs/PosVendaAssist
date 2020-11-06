<?php

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Extrato.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Fabricas/_177/Extrato.php';
    include dirname(__FILE__) . '/../../class/ComunicatorMirror.php';
    
    /*
    * Definições
    */
    $fabrica        = 177;
    $dia_mes        = date('d');
    $dia_extrato    = date('Y-m-d H:i:s');
    $nome_fabrica   = "Anauger";
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
    $logClass->adicionaLog(array("titulo" => "Log erro Geração de Extrato Anauger")); // Titulo
    $logClass->adicionaEmail("helpdesk@telecontrol.com.br");

    /*
    * Extrato Class
    */
    $classExtrato = new Extrato($fabrica);
    $classExtratoFabrica = new ExtratoAnauger($fabrica);

    /*
    * Resgata o período dos 15 dias
    */
    // $data_15 = $classExtrato->getPeriodoDias(14, $dia_extrato);
    $data_15 = date("Y-m-d");

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
    // $usa_lgr = false;

    /**
    * Verifica valor mínimo
    */
    // $verifica_valor_minino = false;

    /**
    * Libera extrato automaticamente assim que é gerado
    */
    $libera_extrato_automaticamente = false;

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
        $email_posto    = $os_posto[$i]["contato_email"];

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

            /*
            * Verifica Valor Mínimo
            */
            // if($verifica_valor_minino == true){
            //     $classExtrato->verificaValorMinimoExtrato(250, $total_extrato);
            // }

            /**
            * Verifica LGR
            */
            // if($usa_lgr == true){

            $extratoLgr = $classExtratoFabrica->verificaLGR($extrato, $posto, $data_15);
            // }
            
            if ($extratoLgr === true AND !empty($email_posto)){
                $comunicatorMirror = new ComunicatorMirror();
                $titulo_email      = "LGR PEÇAS - $nome_fabrica";
                $corpo_email       = "Sr. $codigo_posto-$nome foi gerada uma devolução de peças para a fábrica";
                $destinatario      = $email_posto;
                $comunicatorMirror->post($destinatario, utf8_encode("$titulo_email"), utf8_encode("$corpo_email"));
            }

            if ($extratoLgr === true) {

                $titulo        = "LGR PEÇAS - {$nome_fabrica}";
                $msgComunicado = "Sr. {$codigo_posto}-{$nome} foi gerada uma devolução de peças para a fábrica";

                $classExtratoFabrica->enviaComunicadoLgr($msgComunicado, 'Comunicado', $posto, 't', $titulo);

            }

            /**
            * Libera extrato automaticamente
            */
            // if($libera_extrato_automaticamente == true){
            //     $classExtrato->liberaExtrato($extrato);
            // }

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

        $fp = fopen("/tmp/{$fabrica_nome}/extrato/gera-extrato-".date("dmYH").".txt", "a");
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

