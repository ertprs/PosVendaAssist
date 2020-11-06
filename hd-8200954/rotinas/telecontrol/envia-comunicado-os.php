<?php

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

    include dirname(__FILE__) . '/../../classes/Posvenda/Fabrica.php';
    include dirname(__FILE__) . '/../../classes/Posvenda/Os.php';

    /*
    * Definições
    */
    $dia_mes        = date('d');

    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro - Geração comunicados OS")); // Titulo

    /*
    * Log Class
    */
    if ($_serverEnvironment == 'production') {
        $logClass->adicionaEmail("francisco.ambrozio@telecontrol.com.br");
    } else {
        $logClass->adicionaEmail("kaique.magalhaes@telecontrol.com.br");
    }

    $fabricas = [81,114,122,123,125,128,155,160,168];
    //$fabricas = [81];

    foreach ($fabricas as $fabrica) {

        
        /*
        * Cron Class
        */
        $phpCron = new PHPCron($fabrica, __FILE__);
        $phpCron->inicio();

        $osClass = new \Posvenda\Os($fabrica);

        /*
        * Mensagem de Erro
        */
        $msg_erro = "";
        $msg_erro_arq = "";


                try {

                                            /*
                    * Begin
                    */
                    $osClass->_model->getPDO()->beginTransaction();


                    $osPendenteRetirada = $osClass->getOsPendenteRetirada();

                    if (count($osPendenteRetirada) > 0) {

                        foreach ($osPendenteRetirada as $dadosOs) {

                            $os    = $dadosOs['os'];
                            $posto = $dadosOs['posto'];

                            $msgComunicado = "
                                Verificamos que a OS {$os} está com status Aguardando Retirada.Solicito que, se o produto já foi entregue ao consumidor, favor Finalizar a OS
                            ";

                            // Verifica se já foi enviado um comunicado dentro de um período de 5 dias
                            // e se o posto alterou o status
                            if ($osClass->verificaComunicadoEnviado($os, $posto, 4)) {

                                $osClass->enviaComunicadoOs($msgComunicado, 'Comunicado', $posto, 't', json_encode(['os' => (string) $os]));

                            }

                        }

                    }

                    /*
                    * OSs com nota fiscal emitida e a mais de 5 dias em 
                    conserto
                    */

                    $osPendenteConserto = $osClass->getOsPendenteConserto();

                    if (count($osPendenteConserto) > 0) {

                        foreach ($osPendenteConserto as $dadosOs) {

                            $os    = $dadosOs['os'];
                            $posto = $dadosOs['posto'];

                            $msgComunicado = "
                                Verificamos que a OS {$os} Está com o status Aguardando Conserto. Solicitamos que, caso o produto já estiver consertado, favor alterar o status dessa OS para Aguardando Retirada.
                            ";

                            // Verifica se já foi enviado um comunicado dentro de um período de 5 dias
                            // e se o posto alterou o status
                            if ($osClass->verificaComunicadoEnviado($os, $posto, 3)) {

                                $osClass->enviaComunicadoOs($msgComunicado, 'Comunicado', $posto, 't', json_encode(['os' => (string) $os]));

                            }

                        }

                    }

                    $osClass->_model->getPDO()->commit();

                } catch (Exception $e){

                    $msg_erro .= $e->getMessage()."<br />";
                    $msg_erro_arq .= $msg_erro . " - SQL: ";

                    /*
                    * Rollback
                    */
                    $osClass->_model->getPDO()->rollBack();

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

            $fp = fopen("/tmp/distrib/telecontrol/envia-comunicado-os-".date("dmYH").".txt", "a");
            fwrite($fp, "Data Log: " . date("d/m/Y") . "\n");
            fwrite($fp, $msg_erro_arq . "\n \n");
            fclose($fp);

        }

        /*
        * Cron Término
        */
        $phpCron->termino();
    }

} catch (Exception $e) {
    echo $e->getMessage();
}
