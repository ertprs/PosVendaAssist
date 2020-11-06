<?php 
/*
* Includes
*/
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';
include dirname(__FILE__) . '/../../classes/Posvenda/Fabrica.php';
include dirname(__FILE__) . '/../../classes/Posvenda/Fabricas/_169/Os.php';

include dirname(__FILE__) . '/../../classes/Posvenda/Pedido.php';

use Posvenda\Routine;
use Posvenda\RoutineSchedule;
use Posvenda\Log;

$dir = __DIR__."/entrada";
$dataAtual = date("Y-m-d-H-i");

try {

	/*
    * Definição
    */
    date_default_timezone_set('America/Sao_Paulo');
    $fabrica = 169;
    $data = date('d-m-Y');

    $logClass = new Log2();
    $logClass->adicionaLog(array("titulo" => "Log - Faturamentos de Pedidos de Venda BS - $dataAtual - Midea Carrier"));

    if ($_serverEnvironment == 'development') {
        $logClass->adicionaEmail("lucas.carlos@telecontrol.com.br");
    } else {
        $logClass->adicionaEmail("helpdesk@telecontrol.com.br");
    }

    $arrayLog[] = "Iniciando importação de faturamento dos pedidos de Venda BS "; 

    /**
     * Log da Rotina
     */
    $routine = new Routine();
    $routine->setFactory($fabrica);

    $arr = $routine->SelectRoutine("Importa Faturamento Venda - BS");
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
    * Cron 
    */
    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

	$msg_erro = array();

    /*
    * Busca as ordens com pedidos pedentes de faturamento
    */
    $sql = "SELECT 
            tbl_pedido.pedido, 
            tbl_pedido.posto as posto_id, 
            tbl_pedido.data, 
            tbl_pedido.total, 
            tbl_pedido.valores_adicionais, 
            tbl_pedido.finalizado, 
            tbl_tipo_pedido.codigo as codigo_tipo_pedido 
        FROM tbl_pedido 
        JOIN tbl_tipo_pedido on tbl_tipo_pedido.tipo_pedido = tbl_pedido.tipo_pedido 
        WHERE tbl_pedido.fabrica = $fabrica 
        AND tbl_tipo_pedido.pedido_faturado = true  
        AND tbl_pedido.status_pedido in(2, 5) 
		AND tbl_pedido.exportado is not null ";

    $pedidos = pg_query($con, $sql);
    $countPedidos = pg_num_rows($pedidos);

    if ($countPedidos == 0) {
        exit;
    }

    $logClass->adicionaLog(utf8_encode("Pedidos pendentes de faturamentos encontrados. Iniciando processamento..."));

    $total_dados = $countPedidos;
    $total_dados_sucesso = 0;
    if ($_serverEnvironment == 'development') {
    	$urlWSDL = "http://ws.carrieronline.com.br/qa6/PSA_WebService/BlueService.asmx?WSDL";
    } else {
        $urlWSDL = "http://ws.carrieronline.com.br/wsPSAtelecontrol/blueservice.asmx?WSDL";
    }
    
	$client = new SoapClient($urlWSDL, array('trace' => 1));   

	for ($i = 0; $i < $countPedidos; $i++) {
        try {
            unset($dados_xml);
            unset($xml);
            unset($xmlFaturamentos);
            $pedido         = pg_fetch_result($pedidos, $i, "pedido");
            $os             = pg_fetch_result($pedidos, $i, "os");
            $posto_id       = pg_fetch_result($pedidos, $i, "posto_id");

            $arrayLog[] = " Pesquisando pedido $pedido ";

            $array_request = array('PI_PEDIDO'=> $pedido);

	   		$result = $client->Z_CB_TC_RETORNA_DADOS_NFE_BS($array_request);

    		$dados_xml = $result->Z_CB_TC_RETORNA_DADOS_NFE_BSResult->any;

    		$xml = simplexml_load_string($dados_xml); 

            $arrayLog[] = " Retorno recebido ";
            $arrayLog[] =  json_encode(array("xml_retorno" => $xml ));

            $xml = json_decode(json_encode((array)$xml), true);

            if ($xml['NewDataSet']['DATAHORA']['PE_DATA'] == '0000-00-00') {
                $logClass->adicionaLog(utf8_encode("Não existe faturamento para esse pedido->{$pedido}..."));
            } else {
            	if (!empty($xml['NewDataSet']['PE_DADOS_NFTABLE']['MATNR'])) {
                    $xmlFaturamentos[] = $xml['NewDataSet']['PE_DADOS_NFTABLE'];
                } else {
                    $xmlFaturamentos = $xml['NewDataSet']['PE_DADOS_NFTABLE'];
                }                

            	foreach($xmlFaturamentos as $fat => $faturamento) {

                    if(!empty($faturamento['MOTIVO_RECUSA']) and $faturamento['MOTIVO_RECUSA'] == 'Z2'){

                        try{
                            pg_query($con, "BEGIN;");

                            $logClass->adicionaLog(utf8_encode("Cancelando o item ".$faturamento['MATNR']." do pedido $pedido "));

                            $pedido_item = (int)$faturamento['ID_IT_TELECONTROL'];
                            $motivo = utf8_decode("Cancelamento via integração - ".$faturamento['MOTIVO_RECUSA']);
                            
                            if (!empty($pedido) && !empty($pedido_item)) {
                                if (empty($qtde)) { 
                                    $setUpd = "qtde_cancelada = qtde - (COALESCE(qtde_faturada, 0) + COALESCE(qtde_cancelada, 0))";
                                } else {
                                    $setUpd = "qtde_cancelada = {$qtde}";
                                }

                                $updPedItem = "
                                    UPDATE tbl_pedido_item
                                    SET {$setUpd}
                                    WHERE pedido = {$pedido}
                                    AND pedido_item = {$pedido_item}
                                    and qtde > (qtde_cancelada + qtde_faturada); ";

                                $resPedItem = pg_query($con, $updPedItem);
                                $erroPedItem = pg_last_error($con); 

                                if (strlen(trim($erroPedItem))>0) {
                                    throw new \Exception("Ocorreu um erro atualizando dados de cancelamento #001");
                                }

                                $insPedCancel = "
                                    INSERT INTO tbl_pedido_cancelado (
                                        pedido,
                                        posto,
                                        fabrica,
                                        peca,
                                        qtde,
                                        motivo,
                                        data,
                                        pedido_item
                                    )
                                    SELECT
                                        tbl_pedido.pedido,
                                        tbl_pedido.posto,
                                        tbl_pedido.fabrica,
                                        tbl_pedido_item.peca,
                                        tbl_pedido_item.qtde_cancelada,
                                        '{$motivo}',
                                        CURRENT_DATE,
                                        tbl_pedido_item.pedido_item
                                    FROM tbl_pedido
                                    JOIN tbl_pedido_item USING(pedido)
                                    left join tbl_pedido_cancelado on tbl_pedido_cancelado.pedido = tbl_pedido.pedido
                                    WHERE tbl_pedido.pedido = {$pedido}
                                    AND tbl_pedido_item.pedido_item = {$pedido_item}
                                    and tbl_pedido_cancelado.pedido is null ; ";

                                $resPedCancel = pg_query($con, $insPedCancel);

                                if (!$resPedCancel) {
                                    throw new \Exception("Ocorreu um erro atualizando dados de cancelamento #002");
                                }

                                $atPedStatus = "SELECT fn_atualiza_status_pedido({$fabrica}, {$pedido});";
                                $resPedStatus = pg_query($con, $atPedStatus);

                                if (!$resPedStatus) {
                                    throw new \Exception("Ocorreu um erro atualizando dados de cancelamento #003");
                                }
                            } else {
                                throw new \Exception("Pedido não encontrado para executar o faturamento");
                            }

                            $logClass->adicionaLog(utf8_encode("Cancelado o item ".$faturamento['MATNR']." do pedido $pedido com sucesso."));
                            pg_query($con, "COMMIT;");
                        
                        } catch (Exception $e) {
                            $logClass->adicionaLog(utf8_encode("Falha ao cancelar o item ".$faturamento['MATNR']." do pedido $pedido ".$e->getMessage() ));
                            pg_query($con, "ROLLBACK;");                            
                        }

                    }elseif(empty($faturamento['MOTIVO_RECUSA'])){

                    try {

                    	$logClass->adicionaLog(utf8_encode("Faturamento encontrado iniciando inserção no Telecontrol -> Pedido: {$pedido}"));

                        $pedidoSAP          = (int) $faturamento['BSTKD'];
                        $cfop               = $faturamento['CFOP'];
                        $referencia_peca    = $faturamento['MATNR'];
                        $qtde_faturada      = $faturamento['MENGE'];
                        $valor_item         = $faturamento['NETWR'];
                        $nota_fiscal        = $faturamento['NFENUM']."-".$faturamento['SERIE'];
                        $emissao            = $faturamento['DOCDAT'];
                        $doc_envio		    = (int) $faturamento['DOCNUM'];
                        $transportadora     = $faturamento['TRANSPORTADORA'];
                        $valor_imposto      = $faturamento['MWSBP']; 

                        if ($pedido != $pedidoSAP) {
                            continue;
                        }

                        if (empty($referencia_peca)) {
                        	$logClass->adicionaLog(utf8_encode("Referencia da peça não encontrada"));
                            throw new Exception("Referencia da peça não encontrada");
                        }

                        // Validação da peça retornada
                        $sqlPeca = "SELECT * FROM tbl_peca WHERE fabrica = {$fabrica} AND referencia = '{$referencia_peca}';";
                        $resPeca = pg_query($con,$sqlPeca);
                        $logClass->adicionaLog(utf8_encode("Validando peça {$referencia_peca}"));

                        if (pg_num_rows($resPeca) == 0) {
                        	$logClass->adicionaLog(utf8_encode("Peça {$referencia_peca} não encontrada para efetuar o faturamento"));                            
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
                        
                        $logClass->adicionaLog(utf8_encode("Validando peça {$referencia_peca} e pedido Telecontrol {$pedido}")); 

                        if (pg_num_rows($resDadosPedido) == 0) {
                        	$logClass->adicionaLog(utf8_encode("Faturamento do pedido {$pedido} e peça {$referencia_peca} já efetuado ou não encontrado"));
                            throw new Exception("Faturamento do pedido {$pedido} e peça {$referencia_peca} já efetuado ou não encontrado");
                        }

                        $verificaFat = "SELECT * FROM tbl_faturamento WHERE fabrica = {$fabrica} AND nota_fiscal = '{$nota_fiscal}';";
                        $resFat = pg_query($con, $verificaFat);

                        $logClass->adicionaLog(utf8_encode("Verificando faturamento..."));                        

                        pg_query($con, "BEGIN;");

                        if (pg_num_rows($resFat) > 0) {
                            $faturamento = pg_fetch_result($resFat, 0, "faturamento");
                            $logClass->adicionaLog(utf8_encode("Faturamento encontrado")); 
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
                            $logClass->adicionaLog(utf8_encode("Inserindo novo Faturamento...")); 
                            
                            if (strlen(pg_last_error()) > 0) {
                            	$logClass->adicionaLog(utf8_encode("Ocorreu um erro gravando o novo faturamento para a nota fiscal {$nota_fiscal}")); 
                                throw new Exception("Ocorreu um erro gravando o novo faturamento para a nota fiscal {$nota_fiscal}");
                            }

                            $faturamento = pg_fetch_result($resInstFat, 0, "faturamento");
                        }

                        if (!empty($faturamento)) {

                            $pedido_item    = pg_fetch_result($resDadosPedido, 0, "pedido_item");
                            $qtde_pendente  = pg_fetch_result($resDadosPedido, 0, "qtde_pendente");
                            $pedido_peca    = pg_fetch_result($resDadosPedido, 0, "peca");

                            if ($qtde_pendente < $qtde_faturada) {
                            	$logClass->adicionaLog(utf8_encode("A quantidade faturada do item {$referencia_peca} é maior que a quantidade pendente")); 
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
                                OR fi.sequencia IS NULL); ";

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

                			$logClass->adicionaLog(utf8_encode("Verificando se já existe faturamento para a peça {$referencia_peca} e pedido {$pedido}..."));

                            if ($faturar === true) {
                            	$logClass->adicionaLog(utf8_encode("Item não encontrado no faturamento, inserindo..."));
                                
                                $instFatItem = "
                                    INSERT INTO tbl_faturamento_item (
                                        pedido,
                                        pedido_item,
                                        faturamento,
    				                    sequencia,
                                        peca,
                                        qtde,
                                        valor_impostos,
                                        cfop,
                                        preco
                                    ) VALUES (
                                        {$pedido},
                                        {$pedido_item},
                                        {$faturamento},
    				                    '{$doc_envio}',
                                        {$peca},
                                        {$qtde_faturada},
                                        '$valor_imposto',
                                        '$cfop',
                                        '$valor_item'
                                    );
                                ";
                                $resInstFatItem = pg_query($con, $instFatItem);

                                if (strlen(pg_last_error()) > 0) {
                                	$logClass->adicionaLog(utf8_encode("Ocorreu um erro gravando o novo item do faturamento da nota fiscal {$nota_fiscal}, peça {$referencia_peca}"));
                                    throw new Exception("Ocorreu um erro gravando o novo item do faturamento da nota fiscal {$nota_fiscal}, peça {$referencia_peca}");
                                }

                                $logClass->adicionaLog(utf8_encode("Atualizando pedido com a quantidade faturada..."));                                
                                $atlPedItem = "SELECT fn_atualiza_pedido_item($pedido_peca, $pedido, $pedido_item, $qtde_faturada);";                                
                                pg_query($con, $atlPedItem);

                                if (strlen(pg_last_error()) > 0) {
                                	$logClass->adicionaLog(utf8_encode("Ocorreu um erro atualizando o pedido {$pedido} com o faturamento da nota fiscal {$nota_fiscal}, peça {$referencia_peca}"));
                                    throw new Exception("Ocorreu um erro atualizando o pedido {$pedido} com o faturamento da nota fiscal {$nota_fiscal}, peça {$referencia_peca}");
                                }
                            }
                        }

                        $logClass->adicionaLog(utf8_encode("Atualiza status do pedido"));

                        $atlPedido = "SELECT fn_atualiza_status_pedido($fabrica, $pedido);";
                        pg_query($con, $atlPedido);

                        if (strlen(pg_last_error()) > 0) {

                        	$logClass->adicionaLog(utf8_encode("Ocorreu um erro atualizando o status do pedido {$pedido}"));
                            throw new Exception("Ocorreu um erro atualizando o status do pedido {$pedido}");
                        }

                        pg_query($con, "COMMIT;");

                    } catch(Exception $ei) {
                        $erroItem[] = $ei->getMessage();
                        pg_query($con, "ROLLBACK;");
                        continue;
                    }
                }
                }

            	$total_dados_sucesso++; 
            } 
                   
    	} catch(Exception $e) {
            $msg_erro[] = $e->getMessage();
            continue;
        }
    }

    //Criar arquivo de log    
    $logClass->adicionaLog(implode("<br />", $arrayLog));
    $logClass->enviaEmails();

    $dadosSalvar = implode("\n", $arrayLog);
    $arq = $dir . '/retorno-faturamento-'. date('Ymd_His'). '.txt';                
    $arq_log = fopen($arq, "w");
    fwrite($arq_log, $dadosSalvar);
    fclose($arq_log);


    $routineScheduleLog->setTotalRecord($total_dados);
    $routineScheduleLog->setTotalRecordProcessed($total_dados_sucesso);
    $routineScheduleLog->setStatus(1);
    $routineScheduleLog->setStatusMessage("Rotina finalizada com sucesso");

    
    $routineScheduleLog->setDateFinish(date("Y-m-d H:i"));
    $routineScheduleLog->Update();
    $phpCron->termino();

}catch (Exception $e) {
    echo $e->getMessage();
}

exit;


?>
