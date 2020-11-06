<?php

/*
* Includes
*/
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';
include dirname(__FILE__) . '/../../classes/Posvenda/Fabrica.php';
include dirname(__FILE__) . '/../../classes/Posvenda/Fabricas/_170/Os.php';

use Posvenda\Routine;
use Posvenda\RoutineSchedule;
use Posvenda\Log;

try {

    // ini_set("display_errors", 1);
    // error_reporting(E_ALL);

    /*
    * Definição
    */
    date_default_timezone_set('America/Sao_Paulo');
    $fabrica = 170;
    $data = date('d-m-Y');

    /**
     * Log da Rotina
     */
    $routine = new Routine();
    $routine->setFactory($fabrica);

    $arr = $routine->SelectRoutine("Busca de Faturamentos");
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
    $processos      = explode("\n", shell_exec("ps aux | grep {$arquivo_rotina}"));
    $arquivo_rotina = str_replace(".", "\\.", $arquivo_rotina);

    $count_routine = 0;
    foreach ($processos as $value) {
        if (preg_match("/(.*)php (.*)\/carrier\/{$arquivo_rotina}/", $value)) {
            $count_routine += 1;
        }
    }
    $em_execucao = ($count_routine > 2) ? true : false;

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
    $logClass->adicionaLog(array("titulo" => "Log erro - Busca faturamentos de pedidos Midea Carrier")); // Titulo
    
    if ($_serverEnvironment == 'development') {
        $logClass->adicionaEmail("maicon.luiz@telecontrol.com.br");
    } else {
        $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
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
     * Class Os Fábrica
     */
    $osFabricaClass = new \Posvenda\Fabricas\_170\Os($fabrica, null, $con);

    /*
    * Resgata o nome da Fabrica
    */
    $fabrica_nome = $fabricaClass->getNome();

    /*
    * Mensagem de Erro
    */
    $msg_erro = array();

    /*
    * Busca as ordens com pedidos pedentes de faturamento
    */
    $sql = "
        SELECT DISTINCT
            o.os_posto,
            o.os,
            o.posto,
            p.pedido
        FROM tbl_os o
        INNER JOIN tbl_os_produto op ON op.os = o.os
        INNER JOIN tbl_os_item oi ON oi.os_produto = op.os_produto
        INNER JOIN tbl_peca pc ON pc.peca = oi.peca AND pc.produto_acabado IS NOT TRUE AND pc.fabrica = {$fabrica}
        INNER JOIN tbl_pedido p ON oi.pedido = p.pedido AND p.fabrica = {$fabrica}
        WHERE o.fabrica = {$fabrica}
        AND p.status_pedido IN (2,5,8)
        AND LENGTH(o.os_posto) > 0;
    ";

    $ordens = pg_query($con,$sql);
    $countOrdens = pg_num_rows($ordens);

    if ($countOrdens == 0) {
        exit;
    }

    $osFabricaClass->flushLog(utf8_encode("Ordens de serviço pendente de pedido encontradas. Iniciando processamento..."));

    $total_dados = $countOrdens;
    $total_dados_sucesso = 0;

    /*
    * Links dos ambientes do webservice
    */
    if ($_serverEnvironment == 'development') {
        $urlWSDL = "http://ws.carrieronline.com.br/QA6/PSA_WebService/PSA.asmx?WSDL";
    } else {
        $urlWSDL = "http://ws.carrieronline.com.br/wsPSATeleControl/PSA.asmx?WSDL";
    }

    $osFabricaClass->flushLog("URL Request Remessa: ".$urlWSDL);

    /*
    * Cria o cliente para as requisições
    */
    $client = new \SoapClient($urlWSDL, array('trace' => 1));    

    for ($i = 0; $i < $countOrdens; $i++) {
        try {

            $os_posto   = pg_fetch_result($ordens, $i, "os_posto");
            $os_posto   = str_pad($os_posto, 12, 0, STR_PAD_LEFT);
            $os         = pg_fetch_result($ordens, $i, "os");
            $pedido     = pg_fetch_result($ordens, $i, "pedido");
            $posto      = pg_fetch_result($ordens, $i, "posto");

            $xmlRequest = "<ns1:xmlDoc><criterios><PV_AUFNR>{$os_posto}</PV_AUFNR></criterios></ns1:xmlDoc>";
            $params = new \SoapVar($xmlRequest, XSD_ANYXML);

            $array_params = array("xmlDoc" => $params);
            $result = $client->ListaPecasNaoDevolvidas($array_params);            
            $dados_xml = $result->ListaPecasNaoDevolvidasResult->any;
            $xml = simplexml_load_string($dados_xml);
            $xml = json_decode(json_encode((array)$xml), true);

            $resUtf8Json = json_encode(utf8_encode($xmlRequest));
            $osFabricaClass->flushLog($resUtf8Json);

            $resUtf8Json = json_encode($xml);
            $osFabricaClass->flushLog($resUtf8Json);

            if ($xml['NewDataSet']['ZCBSM_MENSAGEMTABLE']['MSGTY'] == "E") {

                $sql = "SELECT obs FROM tbl_pedido WHERE pedido = {$pedido} AND fabrica = {$fabrica};";
                $res = pg_query($con, $sql);

                $osFabricaClass->flushLog(utf8_encode("Verificando se ja não existe a mesma observação cadastrada..."));

                if (pg_num_rows($res) > 0) {
                    $obs = pg_fetch_result($res, 0, "obs");
                }

                if ($xml['NewDataSet']['ZCBSM_MENSAGEMTABLE']['MSGNO'] == "001") {
                    $msgErro = "Retorno SAP: ".utf8_decode($xml['NewDataSet']['ZCBSM_MENSAGEMTABLE']['MSGNO']." - ".$xml['NewDataSet']['ZCBSM_MENSAGEMTABLE']['MSGTX'])." em ".date('d/m/Y H:i:s');
                    $numErro = "Retorno SAP: ".$xml['NewDataSet']['ZCBSM_MENSAGEMTABLE']['MSGNO'];

                    $osFabricaClass->flushLog(utf8_encode("Caso exista alguma observação concatenamos com o que está no banco e adicionamos a data"));

                    if (!empty($obs)) {
                        if (strripos($obs, $numErro) === false) {
                            $obs = $obs."<br />".$msgErro;
                        } else {
                            continue;
                        }
                    } else {
                        $obs = $msgErro;
                    }

                    $upd = "UPDATE tbl_pedido SET obs = '{$obs}' WHERE pedido = {$pedido} AND fabrica = {$fabrica};";
                    $resUpd = pg_query($con, $upd);

                    $osFabricaClass->flushLog(utf8_encode("Grava a nova observação"));

                    if (strlen(pg_last_error()) > 0) {
                        $osFabricaClass->flushLog("Ocorreu um erro atualizando a observação do pedido {$pedido}.");
                        throw new Exception("Ocorreu um erro atualizando a observação do pedido {$pedido}.");
                    }
                }
            }

            if (strlen($xml['NewDataSet']['ZCBSM_PECAS_DEVOLUCAOTABLE']['NF_ENV']) > 0) {
                $xmlFaturamentos[] = $xml['NewDataSet']['ZCBSM_PECAS_DEVOLUCAOTABLE'];
            } else {
                $xmlFaturamentos = $xml['NewDataSet']['ZCBSM_PECAS_DEVOLUCAOTABLE'];
            }

            if (count($xmlFaturamentos) == 0) {
                continue;
            }

            $osFabricaClass->flushLog(utf8_encode("Faturamento encontrado iniciando inserção no Telecontrol"));

            foreach ($xmlFaturamentos as $faturamentos) {

                try {

                    $referencia_peca    = $faturamentos['MATNR'];
                    $nota_fiscal        = $faturamentos['NF_ENV'];
                    $codigo_posto       = $faturamentos['ARBPL'];
                    $emissao            = $faturamentos['PSTDAT'];
                    $qtde_faturada      = $faturamentos['QTD_ENV'];
                    $transportadora     = $faturamentos['NAME2'];

                    // Validação da peça retornada
                    $sqlPeca = "SELECT * FROM tbl_peca WHERE fabrica = {$fabrica} AND referencia = '{$referencia_peca}';";
                    $resPeca = pg_query($con,$sqlPeca);

                    $osFabricaClass->flushLog(utf8_encode("Validando peça {$referencia_peca}"));

                    if (pg_num_rows($resPeca) == 0) {
                        $osFabricaClass->flushLog("Peça {$referencia_peca} não encontrada para efetuar o faturamento");
                        throw new Exception("Peça {$referencia_peca} não encontrada para efetuar o faturamento");
                    }

                    $peca = pg_fetch_result($resPeca, 0, "peca");

                    // Validação do posto retornado do Webservice com o correspondente ao pedido
                    $sqlDadosPosto = "SELECT * FROM tbl_posto_fabrica WHERE fabrica = {$fabrica} AND codigo_posto = '{$codigo_posto}' AND posto = {$posto};";
                    $resDadosPosto = pg_query($con, $sqlDadosPosto);

                    $osFabricaClass->flushLog(utf8_encode("Validando posto {$codigo_posto}"));

                    if (pg_num_rows($resDadosPosto) == 0) {
                        $osFabricaClass->flushLog("O posto retornado {$codigo_posto} pelo Webservice não corresponde ao posto da Ordem de serviço e pedido");
                        throw new Exception("O posto retornado {$codigo_posto} pelo Webservice não corresponde ao posto da Ordem de serviço e pedido");
                    }

                    // Validação da peça retornada com relação ao pedido
                    $sqlDadosPedido = "
                        SELECT 
                            p.pedido,
                            pi.pedido_item,
                            pi.peca,
                            pi.qtde,
                            pi.qtde - (COALESCE(pi.qtde_cancelada, 0) + COALESCE(pi.qtde_faturada, 0)) AS qtde_pendente
                        FROM tbl_pedido p
                        JOIN tbl_pedido_item pi USING(pedido)
                        WHERE p.fabrica = {$fabrica}
                        AND p.pedido = {$pedido}
                        AND pi.peca = {$peca}
                        AND pi.qtde > (COALESCE(pi.qtde_cancelada, 0) + COALESCE(pi.qtde_faturada, 0));
                    ";

                    $resDadosPedido = pg_query($con,$sqlDadosPedido);

                    $osFabricaClass->flushLog(utf8_encode("Validando peça {$referencia_peca} e pedido Telecontrol {$pedido}"));

                    if (pg_num_rows($resDadosPedido) == 0) {
                        $osFabricaClass->flushLog("Ocorreu um erro buscando os dados para faturamento do pedido {$pedido} e peça {$referencia_peca} #001");
                        throw new Exception("Ocorreu um erro buscando os dados para faturamento do pedido {$pedido} e peça {$referencia_peca} #001");
                    }

                    $verificaFat = "SELECT * FROM tbl_faturamento WHERE fabrica = {$fabrica} AND nota_fiscal = '{$nota_fiscal}';";
                    $resFat = pg_query($con, $verificaFat);

                    $osFabricaClass->flushLog(utf8_encode("Verificando faturamento..."));

                    pg_query($con, "BEGIN;");

                    if (pg_num_rows($resFat) > 0) {
                        $faturamento = pg_fetch_result($resFat, 0, "faturamento");
                        $osFabricaClass->flushLog(utf8_encode("Faturamento encontrado"));
                    } else {

                        $instFat = "
                            INSERT INTO tbl_faturamento (
                                fabrica,
                                pedido,
                                posto,
                                emissao,
                                conhecimento,
                                saida,
                                nota_fiscal,
                                total_nota
                            ) VALUES (
                                {$fabrica},
                                {$pedido},
                                {$posto},
                                '{$emissao}',
                                '{$transportadora}',
                                now(),
                                '{$nota_fiscal}',
                                0.00
                            ) RETURNING faturamento;
                        ";
                        $resInstFat = pg_query($con, $instFat);

                        $osFabricaClass->flushLog(utf8_encode("Inserindo novo Faturamento..."));

                        if (strlen(pg_last_error()) > 0) {
                            $osFabricaClass->flushLog("Ocorreu um erro gravando o novo faturamento para a nota fiscal {$nota_fiscal}");
                            throw new Exception("Ocorreu um erro gravando o novo faturamento para a nota fiscal {$nota_fiscal}");
                        }

                        $faturamento = pg_fetch_result($resInstFat, 0, "faturamento");
                    }

                    if (!empty($faturamento)) {

                        $pedido_item    = pg_fetch_result($resDadosPedido, 0, "pedido_item");
                        $qtde_pendente  = pg_fetch_result($resDadosPedido, 0, "qtde_pendente");

                        if ($qtde_pendente < $qtde_faturada) {
                            $osFabricaClass->flushLog("A quantidade faturada do item {$referencia_peca} é maior que a quantidade pendente");
                            throw new Exception("A quantidade faturada do item {$referencia_peca} é maior que a quantidade pendente");
                        }

                        $sqlFatItem = "SELECT * FROM tbl_faturamento_item WHERE faturamento = {$faturamento} AND peca = {$peca} AND pedido = {$pedido};";
                        $resFatItem = pg_query($con, $sqlFatItem);

                        $osFabricaClass->flushLog(utf8_encode("Verificando se já existe faturamento para a peça {$referencia_peca} e pedido {$pedido}..."));

                        if (pg_num_rows($resFatItem) == 0) {

                            $osFabricaClass->flushLog(utf8_encode("Item não encontrado no faturamento, inserindo..."));

                            $instFatItem = "
                                INSERT INTO tbl_faturamento_item (
                                    pedido,
                                    pedido_item,
                                    faturamento,
                                    peca,
                                    qtde,
                                    preco
                                ) VALUES (
                                    {$pedido},
                                    {$pedido_item},
                                    {$faturamento},
                                    {$peca},
                                    {$qtde_faturada},
                                    0.00
                                );
                            ";
                            $resInstFatItem = pg_query($con, $instFatItem);

                            if (strlen(pg_last_error()) > 0) {
                                $osFabricaClass->flushLog("Ocorreu um erro gravando o novo item do faturamento da nota fiscal {$nota_fiscal}, peça {$referencia_peca}");
                                throw new Exception("Ocorreu um erro gravando o novo item do faturamento da nota fiscal {$nota_fiscal}, peça {$referencia_peca}");
                            }

                            $osFabricaClass->flushLog(utf8_encode("Atualizando pedido com a quantidade faturada..."));
                            $atlPedItem = "SELECT fn_atualiza_pedido_item($peca, $pedido, $pedido_item, $qtde_faturada);";
                            pg_query($con, $atlPedItem);

                            if (strlen(pg_last_error()) > 0) {
                                $osFabricaClass->flushLog("Ocorreu um erro atualizando o pedido {$pedido} com o faturamento da nota fiscal {$nota_fiscal}, peça {$referencia_peca}");
                                throw new Exception("Ocorreu um erro atualizando o pedido {$pedido} com o faturamento da nota fiscal {$nota_fiscal}, peça {$referencia_peca}");
                            }

                        }
                    }

                    $osFabricaClass->flushLog(utf8_encode("Atualiza status do pedido"));

                    $atlPedido = "SELECT fn_atualiza_status_pedido($fabrica, $pedido);";
                    pg_query($con, $atlPedido);

                    if (strlen(pg_last_error()) > 0) {
                        $osFabricaClass->flushLog("Ocorreu um erro atualizando o status do pedido {$pedido}");
                        throw new Exception("Ocorreu um erro atualizando o status do pedido {$pedido}");
                    }

                    pg_query($con, "COMMIT;");

                } catch(Exception $e) {
                    pg_query($con, "ROLLBACK;");
                    continue;
                }
            }

            $total_dados_sucesso++;

        } catch(Exception $e) {
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

    $osFabricaClass->DelTmpLog($routine_schedule_log_id);
    $routineScheduleLog->setDateFinish(date("Y-m-d H:i"));
    $routineScheduleLog->Update();
    $phpCron->termino();

} catch (Exception $e) {
    echo $e->getMessage();
}