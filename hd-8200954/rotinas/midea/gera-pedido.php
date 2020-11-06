<?php

/*
* Includes
*/

include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';

include dirname(__FILE__) . '/../../classes/Posvenda/Fabrica.php';
include dirname(__FILE__) . '/../../classes/Posvenda/Os.php';
include dirname(__FILE__) . '/../../classes/Posvenda/Fabricas/_169/Os.php';
include dirname(__FILE__) . '/../../classes/Posvenda/Pedido.php';

use Posvenda\Routine;
use Posvenda\RoutineSchedule;
use Posvenda\Log;


try {

    /*ini_set("display_errors", 1);
    error_reporting(E_ALL);*/

    /*
    * Definição
    */
    date_default_timezone_set('America/Sao_Paulo');
    $fabrica = 169;
    $data = date('d-m-Y');

    /*
    * A variavel $param defire se o pedido vai ser por posto ou OS, sendo o padrão pedido por posto
    */
    $param = "os"; /* posto | os */

    /**
     * Log da Rotina
     */
    $routine = new Routine();
    $routine->setFactory($fabrica);

    $arr = $routine->SelectRoutine("Gera Pedido");
    $routine_id = $arr[0]["routine"];

    $routineSchedule = new RoutineSchedule();
    $routineSchedule->setRoutine($routine_id);
    $routineSchedule->setWeekDay(date("w"));

    $routine_schedule_id = $routineSchedule->SelectRoutineSchedule();

    if (!strlen($routine_schedule_id)) {
        throw new Exception("Agendamento da rotina não encontrado");
    }

    $routineScheduleLog = new Log();

    $arquivo_rotina = basename($_SERVER["SCRIPT_FILENAME"]);
    $processos      = explode("\n", shell_exec("ps aux | grep {$arquivo_rotina} | grep -v grep"));
    $arquivo_rotina = str_replace(".", "\\.", $arquivo_rotina);

    $count_routine = 0;
    foreach ($processos as $value) {
        if (preg_match("/(.*)php (.*)\/midea\/{$arquivo_rotina}/", $value)) {
            $count_routine += 1;
        }
    }

    $em_execucao = ($count_routine > 4) ? true : false;

    if ($routineScheduleLog->SelectRoutineWithoutFinish($fabrica, $routine_id) === true && $em_execucao == false) {
        $routineScheduleLog->setRoutineSchedule($routine_schedule_id);
        $routine_schedule_log_stopped = $routineScheduleLog->GetRoutineWithoutFinish();

        $routineScheduleLog->setRoutineScheduleLog($routine_schedule_log_stopped['routine_schedule_log']);
        $routineScheduleLog->setDateFinish(date("Y-m-d H:i:s"));
        $routineScheduleLog->setStatus(1);
        $routineScheduleLog->setStatusMessage(utf8_encode('Rotina finalizada'));
        $routineScheduleLog->Update();
    }

    /* Limpando variáveis */
    $routineScheduleLog->setRoutineSchedule(null);
    $routineScheduleLog->setRoutineScheduleLog(null);
    $routineScheduleLog->setDateFinish(null);
    $routineScheduleLog->setStatus(null);
    $routineScheduleLog->setStatusMessage(null);

    if ($routineScheduleLog->SelectRoutineWithoutFinish($fabrica, $routine_id) === true && $em_execucao == true) {
        throw new Exception('Rotina em execução');
    } else {
        $routineScheduleLog->setRoutineSchedule((integer) $routine_schedule_id);
        $routineScheduleLog->setDateStart(date("Y-m-d H:i"));
        if (!$routineScheduleLog->Insert()) {
           throw new Exception("Erro ao gravar log da rotina");
        }
        $routine_schedule_log_id = $routineScheduleLog->SelectId();
        $routineScheduleLog->setRoutineScheduleLog($routine_schedule_log_id);
    }

    /*
    * Log
    */
    $logClass = new Log2();

    $logClass->adicionaLog(array("titulo" => "Log erro - Geração de Pedidos Midea Carrier")); // Titulo

    if ($_serverEnvironment == 'development') {
        $logClass->adicionaEmail("maicon.luiz@telecontrol.com.br");
    } else {
        $logClass->adicionaEmail('helpdesk@telecontrol.com.br');
    }

    /*
    * Cron
    */
    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    /*
    * Class Fábrica
    */
    $fabricaClass = new \Posvenda\Fabrica($fabrica);

    /*
    * Resgata o nome da Fabrica
    */
    $fabrica_nome = $fabricaClass->getNome();

    /*
    * Resgata as OSs em Garantia
    */
    $osClass = new \Posvenda\Os($fabrica);
    $os_garantia = $osClass->getOsGarantia($param);

    if (empty($os_garantia)) {
		$phpCron->termino('Sem OS para gerar');
        exit;
    }

    /**
     * Variáveis de contagem
     */
    $total_dados = count($os_garantia);
    $total_dados_sucesso = 0;

    /*
    * Mensagem de Erro
    */
    $msg_erro = array();
    $pedidoClass = new \Posvenda\Pedido($fabrica, null, $param);

    /*
     * Class OS Fábrica
     */
    $osFabricaClass = new \Posvenda\Fabricas\_169\Os($fabrica, null, $con);

    /*
    * Resgata a condição da Fabrica
    */
    $condicao = $pedidoClass->getCondicaoGarantia();

    /*
    * Resgata a condição da Fabrica
    */
    $tipo_pedido = $pedidoClass->getTipoPedidoGarantia();

    $tabela_padrao = $pedidoClass->_model->getTabelaId("GAR");

    for ($i = 0; $i < count($os_garantia); $i++) {
        try {
            $posto = $os_garantia[$i]["posto"];
            $os = $os_garantia[$i]["os"];

            /*
            * Begin
            */
            $pedidoClass->_model->getPDO()->beginTransaction();

            $dados = array(
                "posto"         => $posto,
                "tipo_pedido"   => $tipo_pedido,
                "condicao"      => $condicao,
                "fabrica"       => $fabrica,
                "status_pedido" => 1,
                "finalizado"       => "'".date("Y-m-d H:i:s")."'"
            );

            /*
            * Grava o Pedido
            */
            $pedidoClass->grava($dados);
            $pedido = $pedidoClass->getPedido();

            $dadosItens = array();

            /**
             * Pega as peças da OS
             */
            $osClass = new \Posvenda\Os($fabrica, $os);

            $pecas = $osClass->getPecasPedidoGarantia();

            foreach ($pecas as $key => $peca) {
                unset($dadosItens);

                /*
                * Insere o Pedido Item
                */
                $preco = $pedidoClass->getPrecoPecaGarantia($peca["peca"], $os, $tabela_padrao);

                $dadosItens[] = array(
                    "pedido"            => (int)$pedido,
                    "peca"              => $peca["peca"],
                    "qtde"              => $peca["qtde"],
                    "qtde_faturada"     => 0,
                    "qtde_cancelada"    => 0,
                    "preco"             => $preco,
                    "total_item"        => $preco * $peca["qtde"]
                );

                $pedidoClass->gravaItem($dadosItens, $pedido);

                /*
                * Resgata o Pedido Item
                */
                $pedido_item = $pedidoClass->getPedidoItem();

                /*
                * Atualiza os Pedidos Item na OS Item
                */
                $pedidoClass->atualizaOsItemPedidoItem($peca["os_item"], $pedido, $pedido_item, $fabrica);
            }

            //$pedidoClass->registrarPedidoExportado($pedido);

            /*
            * Commit
            */
            $pedidoClass->_model->getPDO()->commit();

            $total_dados_sucesso++;

        } catch(Exception $e) {
            $pedidoClass->_model->getPDO()->rollBack();
            $msg_erro[] = $e->getMessage();
            continue;
        }

    }

    $routineScheduleLog->setTotalRecord($total_dados);
    $routineScheduleLog->setTotalRecordProcessed($total_dados_sucesso);
    $routineScheduleLog->setStatus(1);
    $routineScheduleLog->setStatusMessage("Rotina finalizada com sucesso");

    if(!empty($msg_erro)){
        $logClass->adicionaLog(implode("<br />", $msg_erro));

        if($logClass->enviaEmails() == "200"){
          echo "Log de erro enviado com Sucesso!";
        }else{
          $logClass->enviaEmails();
        }
    }

    $routineScheduleLog->setDateFinish(date("Y-m-d H:i"));
    $routineScheduleLog->Update();
    $phpCron->termino();

} catch (Exception $e) {
    echo $e->getMessage();
}
