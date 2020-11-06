<?php

/*
* Includes
*/
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';
include dirname(__FILE__) . '/../../classes/Posvenda/Fabrica.php';
include dirname(__FILE__) . '/../../classes/Posvenda/Fabricas/_169/Os.php';

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
    $fabrica = 169;
    $data = date('d-m-Y');

    /**
     * Log da Rotina
     */
    $routine = new Routine();
    $routine->setFactory($fabrica);

    $arr = $routine->SelectRoutine("Importa Faturamentos");
    $routine_id = $arr[0]["routine"];

    $routineSchedule = new RoutineSchedule();
    $routineSchedule->setRoutine($routine_id);
    $routineSchedule->setWeekDay(date("w"));

    $routine_schedule_id = $routineSchedule->SelectRoutineSchedule();

    if ($routine_schedule_id === false) {
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

    $em_execucao = ($count_routine > 3) ? true : false;

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
    $logClass->adicionaLog(array("titulo" => "Log erro - Importa faturamentos de pedidos Midea Carrier")); // Titulo
    
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
    $osFabricaClass = new \Posvenda\Fabricas\_169\Os($fabrica, null, $con);

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
	if(date('H') < 19 and date('H') > 7) {
		$cond = " AND p.data between current_date - interval '1 week' and current_date ";
	}

    $sql = "
        SELECT DISTINCT ON (p.pedido)
            p.pedido,
            op.os,
            pf.posto AS posto_id,
            pf.codigo_posto AS posto_codigo
        FROM tbl_pedido p
        JOIN tbl_posto_fabrica pf ON pf.posto = p.posto AND pf.fabrica = {$fabrica}
        LEFT JOIN tbl_os_item oi ON oi.pedido = p.pedido
        LEFT JOIN tbl_os_produto op ON op.os_produto = oi.os_produto
        WHERE p.fabrica = {$fabrica}
		AND p.status_pedido IN (2,5,8)
		$cond
        AND p.data > '2019-05-01 00:00';
    ";

    $pedidos = pg_query($con, $sql);
    $countPedidos = pg_num_rows($pedidos);

    if ($countPedidos == 0) {
        exit;
    }

    $osFabricaClass->flushLog(utf8_encode("Pedidos pendentes de faturamentos encontrados. Iniciando processamento..."));

    $total_dados = $countPedidos;
    $total_dados_sucesso = 0;

    /*
    * Links dos ambientes do webservice
    */
    if ($_serverEnvironment == 'development') {
        $urlWSDL = "http://ws.carrieronline.com.br/qa6/PSA_WebService/telecontrol.asmx?WSDL";
    } else {
        $urlWSDL = "http://ws.carrieronline.com.br/wsPSATeleControl/telecontrol.asmx?WSDL";
    }

    $osFabricaClass->flushLog("URL Request Remessa: ".$urlWSDL);

    /*
    * Cria o cliente para as requisições
    */
	$client = new \SoapClient($urlWSDL, array('trace' => 1,
			'stream_context'=>stream_context_create(
				array('http'=>
				array(
					'protocol_version'=>'1.0',
					'header' => 'Connection: Close'
					)
				)
			)
	));    

    for ($i = 0; $i < $countPedidos; $i++) {
        try {

            unset($dados_xml);
            unset($xml);
            unset($xmlFaturamentos);
            $pedido         = pg_fetch_result($pedidos, $i, "pedido");
            $os             = pg_fetch_result($pedidos, $i, "os");
            $posto_id       = pg_fetch_result($pedidos, $i, "posto_id");
            $posto_codigo   = pg_fetch_result($pedidos, $i, "posto_codigo");
            $osFabricaClass->flushLog(utf8_encode("Pesquisando pedido->$pedido posto->$posto_codigo"));
            
            $array_request = array('PI_PEDIDO'=> $pedido);

            $result = $client->Z_CB_TC_RETORNA_DADOS_NFE($array_request);            
            $dados_xml = $result->Z_CB_TC_RETORNA_DADOS_NFEResult->any;
            $xml = simplexml_load_string($dados_xml);
            $xml = json_decode(json_encode((array)$xml), true);

            $resUtf8Json = json_encode(utf8_encode($xmlRequest));
            $osFabricaClass->flushLog($resUtf8Json);

            $resUtf8Json = json_encode($xml);
            $osFabricaClass->flushLog($resUtf8Json);

            if ($xml['NewDataSet']['DATAHORA']['PE_DATA'] == '0000-00-00') {
                $osFabricaClass->flushLog(utf8_encode("Não existe faturamento para esse pedido->{$pedido}..."));
            } else {

                if (!empty($xml['NewDataSet']['PE_DADOS_NFTABLE']['MATNR'])) {
                    $xmlFaturamentos[] = $xml['NewDataSet']['PE_DADOS_NFTABLE'];
                } else {
                    $xmlFaturamentos = $xml['NewDataSet']['PE_DADOS_NFTABLE'];
                }

                foreach($xmlFaturamentos as $fat => $faturamento) {

                    try {

                        $osFabricaClass->flushLog(utf8_encode("Faturamento encontrado iniciando inserção no Telecontrol -> Pedido: {$pedido}"));

                        $pedidoSAP          = (int) $faturamento['BSTKD'];
                        $cfop               = $faturamento['CFOP'];
                        $referencia_peca    = $faturamento['MATNR'];
                        $qtde_faturada      = $faturamento['MENGE'];
                        $valor_item         = $faturamento['NETWR'];
                        $nota_fiscal        = $faturamento['NFENUM']."-".$faturamento['SERIE'];
                        $emissao            = $faturamento['DOCDAT'];
                        $doc_envio		    = (int) $faturamento['DOCNUM'];
                        $transportadora     = $faturamento['TRANSPORTADORA'];

                        if ($pedido != $pedidoSAP) {
                            continue;
                        }

                        if (!empty($faturamento['PE_IMPOSTOSTABLE'])) {
                            foreach ($faturamento['PE_IMPOSTOSTABLE'] as $impostos) {
                                $impostosFaturamento[$impostos['TAXGRP']]['base'] = $impostos['BASE'];
                                $impostosFaturamento[$impostos['TAXGRP']]['taxa'] = $impostos['RATE'];
                                $impostosFaturamento[$impostos['TAXGRP']]['valor'] = $impostos['TAXVAL'];
                            }
                        } else {
                            foreach ($xml['NewDataSet']['PE_IMPOSTOSTABLE'][$fat] as $impostos) {
                                $impostosFaturamento[$impostos['TAXGRP']]['base'] = $impostos['BASE'];
                                $impostosFaturamento[$impostos['TAXGRP']]['taxa'] = $impostos['RATE'];
                                $impostosFaturamento[$impostos['TAXGRP']]['valor'] = $impostos['TAXVAL'];
                            }
                        }

                        if (empty($referencia_peca)) {
                            $osFabricaClass->flushLog("Referencia da peça não encontrada");
                            throw new Exception("Referencia da peça não encontrada");
                        }

                        // Validação da peça retornada
                        $sqlPeca = "SELECT * FROM tbl_peca WHERE fabrica = {$fabrica} AND referencia = '{$referencia_peca}';";
                        $resPeca = pg_query($con,$sqlPeca);
                        $osFabricaClass->flushLog(utf8_encode("Validando peça {$referencia_peca}"));

                        if (pg_num_rows($resPeca) == 0) {
                            $osFabricaClass->flushLog("Peça {$referencia_peca} não encontrada para efetuar o faturamento");
                            throw new Exception("Peça {$referencia_peca} não encontrada para efetuar o faturamento");
                        }

                        $peca = pg_fetch_result($resPeca, 0, "peca");

                        $sqlDadosPedido = "
                            SELECT 
                                p.pedido,
                                pi.pedido_item,
                                pi.peca,
                                pi.qtde,
                                pi.qtde - (COALESCE(pi.qtde_cancelada, 0) + COALESCE(pi.qtde_faturada, 0)) AS qtde_pendente
                            FROM tbl_pedido p
                            JOIN tbl_pedido_item pi USING(pedido)
                            LEFT JOIN tbl_peca_alternativa pa ON pa.peca_para = {$peca} AND pa.fabrica = {$fabrica} AND pa.status IS TRUE
                            LEFT JOIN tbl_peca_alternativa pa_para ON pa_para.peca_de = {$peca} AND pa_para.fabrica = {$fabrica} AND pa_para.status IS TRUE
                            WHERE p.fabrica = {$fabrica}
                            AND p.pedido = {$pedido}
                            AND (pi.peca = {$peca}
                            OR pi.peca = pa.peca_de
                            OR pi.peca = pa_para.peca_para)
                            AND pi.qtde > (COALESCE(pi.qtde_cancelada, 0) + COALESCE(pi.qtde_faturada, 0));
                        ";

                        $resDadosPedido = pg_query($con,$sqlDadosPedido);
                        $osFabricaClass->flushLog(utf8_encode("Validando peça {$referencia_peca} e pedido Telecontrol {$pedido}"));

                        if (pg_num_rows($resDadosPedido) == 0) {
                            $osFabricaClass->flushLog("Faturamento do pedido {$pedido} e peça {$referencia_peca} já efetuado ou não encontrado");
                            throw new Exception("Faturamento do pedido {$pedido} e peça {$referencia_peca} já efetuado ou não encontrado");
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
                                    cfop,
                                    fabrica,
                                    pedido,
                                    posto,
                                    emissao,
                                    transp,
                                    saida,
                                    nota_fiscal,
                                    total_nota
                                ) VALUES (
                                    '{$cfop}',
                                    {$fabrica},
                                    {$pedido},
                                    {$posto_id},
                                    '{$emissao}',
                                    '".substr($transportadora, 0, 40)."',
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
                            $pedido_peca    = pg_fetch_result($resDadosPedido, 0, "peca");

                            if ($qtde_pendente < $qtde_faturada) {
                                $osFabricaClass->flushLog("A quantidade faturada do item {$referencia_peca} é maior que a quantidade pendente");
                                throw new Exception("A quantidade faturada do item {$referencia_peca} é maior que a quantidade pendente");
                            }

                            $sqlFatItem = "
                			    SELECT pedido
                			    FROM tbl_faturamento_item fi
                			    LEFT JOIN tbl_peca_alternativa pa ON pa.peca_para = {$peca} AND pa.fabrica = {$fabrica}
                                LEFT JOIN tbl_peca_alternativa pa_para ON pa.peca_de = {$peca} AND pa.fabrica = {$fabrica}
                			    WHERE fi.faturamento = {$faturamento}
                			    AND (fi.peca = {$peca}
                			    OR pa.peca_de = fi.peca
                                OR fi.peca = pa_para.peca_para)
                			    AND (fi.sequencia = '{$doc_envio}'
                                OR fi.sequencia IS NULL);
                			";

                            $resFatItem = pg_query($con, $sqlFatItem);

                			$faturar = true;
                			if (pg_num_rows($resFatItem) > 0) {
                			    for ($z = 0; $z > pg_num_rows($resFatItem); $z++) {
                    				$pedido_faturado = pg_fetch_result($resFatItem, $z, "pedido");
                    				if ($faturar === false) {
                    				    continue;
                    				}
                    				if ($pedido_faturado == $pedido) {
                    				    $faturar = false;
                    				}
                			    }
                			}

                            $osFabricaClass->flushLog(utf8_encode("Verificando se já existe faturamento para a peça {$referencia_peca} e pedido {$pedido}..."));

                            if ($faturar === true) {

                                $osFabricaClass->flushLog(utf8_encode("Item não encontrado no faturamento, inserindo..."));

                                $instFatItem = "
                                    INSERT INTO tbl_faturamento_item (
                                        pedido,
                                        pedido_item,
                                        faturamento,
    				                    sequencia,
                                        peca,
                                        qtde,
                                        preco
                                    ) VALUES (
                                        {$pedido},
                                        {$pedido_item},
                                        {$faturamento},
    				                    '{$doc_envio}',
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
                                $atlPedItem = "SELECT fn_atualiza_pedido_item($pedido_peca, $pedido, $pedido_item, $qtde_faturada);";
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

                        $resStatus = pg_query($con, "SELECT fn_os_status_checkpoint_os({$os});");

                        if (strlen(pg_last_error()) > 0) {
                            throw new Exception("Ocorreu um erro atualizando dados do pedido {$pedido} #002");
                        } else {
                            $status_checkpoint = pg_fetch_result($resStatus, 0, 0);
                            pg_query($con, "UPDATE tbl_os SET status_checkpoint = {$status_checkpoint} WHERE os = {$os};");
                            if (strlen(pg_last_error()) > 0) {
                                throw new Exception("Ocorreu um erro atualizando dados do pedido {$pedido} #003");
                            }
                        }

                        pg_query($con, "COMMIT;");
                        // pg_query($con, "ROLLBACK;");
                    } catch(Exception $ei) {
                        $erroItem[] = $ei->getMessage();
                        pg_query($con, "ROLLBACK;");
                        continue;
                    }
                }//FOREACH

                $total_dados_sucesso++;
            }//ELSE
        } catch(Exception $e) {
            $msg_erro[] = $e->getMessage();
            continue;
        }//TRY
    }//FOR

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

