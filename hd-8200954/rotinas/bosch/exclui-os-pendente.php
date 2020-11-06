<?php

error_reporting(E_ALL);

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';

    /*
    * Definições
    */
    $fabrica_nome   = "bosch";
    $fabrica        = 20;
    $dia_atual    = date('Y-m-d H:i:s');

    /*
    * Cron Class
    */
    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    /*
    * Log Class
    */
    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log erro Geração de Extrato - Bosch")); // Titulo
    $logClass->adicionaEmail("kaique.magalhaes@telecontrol.com.br");

    /*
    * Extrato Class
    */
    $classOs = new \Posvenda\Os($fabrica);

    $diasEmAberto = 90; // dias

    /*
    * Resgata a quantidade de OS por Posto
    */
    $listaOs = $classOs->getOsExclusaoPeriodo($diasEmAberto);

    if(count($listaOs) == 0){
        exit;
    }

    foreach ($listaOs as $key => $dados) {

        $osId = $dados["os"];

        try {

            $classOs->_model->getPDO()->beginTransaction();

            $classOs->setOs($osId);

            $classOs->insereOsExcluida();

            $classOs->excluiOs();

            $classOs->_model->getPDO()->commit();

        } catch (Exception $e){

            $msg_erro .= $e->getMessage()."<br />";
            $msg_erro_arq .= $msg_erro . " - SQL: ";

            /*
            * Rollback
            */
            $classOs->_model->getPDO()->rollBack();

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
        
        $fp = fopen("/tmp/{$fabrica_nome}/exlui-os-log-erro-".$dia_atual.".log", "a");
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

