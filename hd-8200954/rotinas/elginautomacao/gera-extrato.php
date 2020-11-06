<?php

try {

    include dirname(__FILE__) . '/../../dbconfig.php';
    include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
    require dirname(__FILE__) . '/../funcoes.php';
    require dirname(__FILE__) . '/../../classes/Posvenda/Extrato.php';
    require dirname(__FILE__) . '/../../classes/Posvenda/Fabricas/_156/ExtratoElgin.php';

    /*
    * Definições
    */
    $fabrica        = 156;
    $fabrica_nome   = "elginautomacao";
    $dia_mes        = date('d');
    $dia_extrato    = date('Y-m-d H:i:s');

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
    $logClass->adicionaLog(array("titulo" => "Log erro Geração de Extrato Elgin Automação")); // Titulo
    $logClass->adicionaEmail("francisco.ambrozio@telecontrol.com.br");

    /*
    * Extrato Class
    */
	$classExtrato = new Extrato($fabrica);
	$classExtratoElgin = new ExtratoElgin($classExtrato->_model);

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
    $usa_lgr = true;

    /**
    * Verifica valor mínimo
    */
    $verifica_valor_minino = false;

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

        $extrato_anterior = 0;

        try {
            foreach (array('normal', 'callcenter') as $tipo) {
                /*
                 * Begin
                 */
                $classExtrato->_model->getPDO()->beginTransaction();

                /*
                 * Insere o Extrato para o Posto
                 */
                $classExtrato->insereExtratoPosto(
                    $fabrica,
                    $posto,
                    $dia_extrato,
                    $mao_de_obra = 0,
                    $pecas = 0,
                    $total = 0,
                    $avulso = 0
                );

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
                switch ($tipo) {
                    case 'normal':
                        $classExtrato->relacionaExtratoOS($fabrica, $posto, $extrato, $dia_extrato, null, "nt");
                        break;
                    case 'callcenter':
                        $classExtratoElgin->relacionaOsCallcenter($fabrica, $posto, $extrato, $dia_extrato);
                        break;
                }

                if ($tipo == 'normal') {
                    $classExtratoElgin->retiraOsCallcenter($extrato);
                }

                /*
                 * Atualiza os valores avulso dos postos
                 */
                $classExtrato->atualizaValoresAvulsos($fabrica);

                /*
                 * Calcula o Extrato
                 */
                $total_extrato = $classExtrato->calcula($extrato);

                $remove = $classExtratoElgin->removeExtratoZerado($extrato);

                if ($remove > 1) {
                    $msg_erro .= "Erro: mais de 1 extrato zerado: $remove";
                    $msg_erro_arq .= "\n{$msg_erro}\n";

                    $classExtrato->_model->getPDO()->rollBack();

                    continue;
                } elseif ($remove == 1) {
                    $classExtrato->_model->getPDO()->commit();
                    continue;
                }

                /**
                 * Verifica LGR
                 */
                if ($extrato_anterior === 0) {
                    $classExtrato->verificaLGR($extrato, $posto, $data_15);
                } else {
                    if (!$classExtratoElgin->verificaExtratoLgr($extrato_anterior)) {
                        $classExtrato->verificaLGR($extrato, $posto, $data_15);
                    }
                }

                $extrato_anterior = $extrato;

                /*
                 * Commit
                 */
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
        $logClass->enviaEmails();

        mkdir("/tmp/{$fabrica_nome}/pedidos", 0777, true);

        $fp = fopen("/tmp/{$fabrica_nome}/pedidos/log-erro.text", "a");
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

