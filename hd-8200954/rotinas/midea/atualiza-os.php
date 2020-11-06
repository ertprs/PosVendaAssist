<?php
require dirname(__FILE__) . '/../../dbconfig.php';
require dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../../class/tdocs.class.php';
require dirname(__FILE__) . '/../funcoes.php';
include_once __DIR__.'/../../classes/autoload.php';
use Posvenda\Routine;
use Posvenda\RoutineSchedule;
use Posvenda\Log;
use Posvenda\LogError;
$login_fabrica = 169;
$status_final = 1;
$status_mensagem = 'Rotina Finalizada';

$tdocs = new TDocs($con, $login_fabrica, 'rotina');
/* Inicio Processo */
$phpCron = new PHPCron($login_fabrica, __FILE__);
$phpCron->inicio();
function msg_log($msg){
    echo "\n".date('H:i')." - $msg";
}
//ob_start();
try{
    
    msg_log('Inicia rotina de atualiza os');
    
    $routine = new Routine();
    $routine->setFactory($login_fabrica);
    $arr = $routine->SelectRoutine("Atualiza Os");
    $routine_id = $arr[0]["routine"];
    $routineSchedule = new RoutineSchedule();
    $routineSchedule->setRoutine($routine_id);
    $routineSchedule->setWeekDay(date("w"));
    $routine_schedule_id = $routineSchedule->SelectRoutineSchedule();
    
    if (!strlen($routine_schedule_id)) {
        throw new Exception("Agendamento da rotina não encontrado");
    }
    
    $routineScheduleLog = new Log();
    $oLogError          = new LogError();

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
    
    if ($routineScheduleLog->SelectRoutineWithoutFinish($login_fabrica, $routine_id) === true && $em_execucao == false) {
        $routineScheduleLog->setRoutineSchedule($routine_schedule_id);
        $routine_schedule_log_stopped = $routineScheduleLog->GetRoutineWithoutFinish();
        $routineScheduleLog->setRoutineScheduleLog($routine_schedule_log_stopped['routine_schedule_log']);
        $routineScheduleLog->setDateFinish(date("Y-m-d H:i:s"));
        $routineScheduleLog->setStatus(1);
        $routineScheduleLog->setStatusMessage(utf8_encode('Rotina finalizada'));
        $routineScheduleLog->Update();
        msg_log('Finalizou rotina anterior Schedule anterior. Rotina cod: '.$routine_id);
    }

    /* Limpando variáveis */
    $routineScheduleLog->setRoutineSchedule(null);
    $routineScheduleLog->setRoutineScheduleLog(null);
    $routineScheduleLog->setDateFinish(null);
    $routineScheduleLog->setStatus(null);
    $routineScheduleLog->setStatusMessage(null);

    if ($routineScheduleLog->SelectRoutineWithoutFinish($login_fabrica, $routine_id) === true && $em_execucao == true) {

        throw new Exception("Rotina em execução");

    } else {
        
        $routineScheduleLog->setRoutineSchedule((integer) $routine_schedule_id);
        $routineScheduleLog->setDateStart(date("Y-m-d H:i"));

        if (!$routineScheduleLog->Insert()) {

            throw new Exception("Erro ao gravar log da rotina");

        }

        $routine_schedule_log_id = $routineScheduleLog->SelectId();
        $routineScheduleLog->setRoutineScheduleLog($routine_schedule_log_id);

    }

    if ($_serverEnvironment == 'development') {
        $urlWSDL = "http://ws.carrieronline.com.br/qa6/PSA_WebService/telecontrol.asmx?wsdl";
    } else {
        $urlWSDL = "http://ws.carrieronline.com.br/wsPSATeleControl/telecontrol.asmx?WSDL";
    }

	$client = new SoapClient($urlWSDL, array('trace' => 1,'connection_timeout' => 1000,'cache_wsdl' => WSDL_CACHE_NONE,
		'stream_context'=>stream_context_create(
				array('http'=>
				array(
					'protocol_version'=>'1.0',
					'header' => 'Connection: Close'
					)
				)
			)
		));
    msg_log('Inicia consulta SQL na tabela tbl_pedido');

	$cond = "";
	if(date('H') < 19 and date('H') > 7) {
		$cond = " AND tbl_pedido.data between current_timestamp - interval '1 week' and current_timestamp ";
	}

	if(date('h') % 2 == 1) {
		$cond .= " order by " . rand(1,4) ; 
	    $cond .=  (rand(1,24) % 2 == 1) ? " desc " : " asc ";
	}
    $sql = "
        SELECT DISTINCT
            tbl_os.os,
            tbl_pedido.pedido,
            tbl_pedido.status_pedido,
            tbl_os.os_posto
        FROM tbl_pedido
        JOIN tbl_os_item USING(pedido)
        JOIN tbl_os_produto USING(os_produto)
        JOIN tbl_os USING(os)
	JOIN tbl_peca ON tbl_peca.peca = tbl_os_item.peca AND tbl_peca.fabrica = {$login_fabrica}
	LEFT JOIN tbl_os_campo_extra ON tbl_os_campo_extra.os = tbl_os.os AND tbl_os_campo_extra.fabrica = {$login_fabrica}
        WHERE tbl_os.fabrica = {$login_fabrica}
        AND tbl_pedido.status_pedido IN (1,2,4)
	AND TRIM(tbl_os.os_posto) != ''
	AND tbl_os.finalizada IS NULL
        AND tbl_pedido.data > current_timestamp - interval '3 months'
        AND tbl_os.data_digitacao > current_timestamp - interval '3 months'
	AND ((JSON_FIELD('status_sap', tbl_os_campo_extra.campos_adicionais) = 'PFAL'
	AND tbl_peca.produto_acabado IS NOT TRUE)
	OR JSON_FIELD('status_sap', tbl_os_campo_extra.campos_adicionais) = ''
	OR tbl_os_campo_extra.os IS NULL
	OR tbl_pedido.exportado IS NULL OR tbl_pedido.status_pedido in (1,2))
	$cond 
    ";
    $res  = pg_query($con, $sql);
    $rows = pg_num_rows($res);

    $totalRecordProcessed = $rows;

    msg_log("Iniciando consulta em $rows linhas");

    if ($rows > 0) {
        foreach (pg_fetch_all($res) as $key => $linha) {
            try {

                $xos = $linha['os'];
                $xpedido = $linha['pedido'];
                $xos_sap = trim($linha['os_posto']);
                $xstatus_pedido = $linha['status_pedido'];

                msg_log('Iniciando requisição à API');
                
                $request = array(
                    'PI_NR_ORDEM' => $xos_sap, 
                    'PI_NR_PEDIDO' => $xpedido
                );

                $result    = $client->Z_CB_TC_ATUALIZAR_OS($request);
                $dados_xml = $result->Z_CB_TC_ATUALIZAR_OSResult->any;
                $xml = simplexml_load_string($dados_xml);
                $xml = json_decode(json_encode((array)$xml), TRUE);

                if (count($xml['NewDataSet']['PE_MENSAGENS']["MESSAGE"]) > 0 ) {
                    $oLogError->setContents(json_encode($request));
                    throw new Exception("Retorno Webservice: ".$xml['NewDataSet']['PE_MENSAGENS']["MESSAGE"]);
                } else {

                    if ($xstatus_pedido == 1) {
                        $dataExportacao = $xml['NewDataSet']['DATAHORA']["PE_DATA"].' '.$xml['NewDataSet']['DATAHORA']["PE_HORA"];

                        $upd = "UPDATE tbl_pedido SET exportado = '{$dataExportacao}', status_pedido = 2 WHERE pedido = {$xpedido} AND fabrica = {$login_fabrica}";
                        pg_query($con, $upd);
                    
                        if (strlen(pg_last_error()) > 0) {
                            $oLogError->setContents($upd);
                            throw new Exception("Erro ao exporta o pedido n° {$xpedido} para SAP. Erro: ".pg_last_error());
                        }
                    } else {

                        $statusSAP = '';
                        foreach ($xml['NewDataSet']['PE_MENSAGENS'] as $mensagem) {
                            if (strpos($mensagem['MESSAGE'], 'SENV') !== false) {
                                $statusSAP = 'SENV';
                            } else if (strpos($mensagem['MESSAGE'], 'PFAL') !== false) {
                                $statusSAP = 'PFAL';
                            }
                        }

                        if (!empty($statusSAP)) {

                            $sqlCamposAdicionais = "SELECT campos_adicionais FROM tbl_os_campo_extra WHERE os = {$xos};";
                            $resCamposAdicionais = pg_query($con, $sqlCamposAdicionais);

                            $camposAdicionais = pg_fetch_result($resCamposAdicionais, 0, 'campos_adicionais');
                            if (!empty($camposAdicionais)) {
                                $arrCamposAdicionais = json_decode($camposAdicionais, true);
                            }

							$arrCamposAdicionais['status_sap'] = $statusSAP;
							$arrCamposAdicionais['status_atualiza'] = date('Y-m-d h:i:s');

                            if (!empty($arrCamposAdicionais)) {
                                $camposAdicionais = json_encode($arrCamposAdicionais);
                            }

                            pg_query($con, "BEGIN;");

                            if (pg_num_rows($resCamposAdicionais) > 0) {
                                $sql = "UPDATE tbl_os_campo_extra SET campos_adicionais = '{$camposAdicionais}' WHERE os = {$xos};";
                            } else {
                                $sql = "INSERT INTO tbl_os_campo_extra (campos_adicionais, os, fabrica) VALUES ('{$camposAdicionais}', {$xos}, {$login_fabrica});";
                            }

                            pg_query($con, $sql);

                            if (strlen(pg_last_error()) > 0) {
                                $oLogError->setContents($sql);
                                throw new Exception("Ocorreu um erro atualizando dados da OS #001");
                            } else {
                                $updStatus = "UPDATE tbl_os SET status_checkpoint = fn_os_status_checkpoint_os(os) WHERE os = {$xos};";
                                pg_query($con, $updStatus);
                                if (strlen(pg_last_error()) > 0) {
                                    $oLogError->setContents($updStatus);
                                    throw new Exception("Ocorreu um erro atualizando dados da OS #003");
                                }
                            }
                            pg_query($con, "COMMIT;");
                        }
                    }
                }
            } catch (Exception $e) {
                pg_query($con, "ROLLBACK;");
                msg_log("Erro: ".$e->getMessage());

                $oLogError->setRoutineScheduleLog($routine_schedule_log_id);
                $oLogError->setLineNumber($key);
                $oLogError->setErrorMessage(utf8_encode($e->getMessage()));
                $oLogError->Insert();

                $totalRecordProcessed = $totalRecordProcessed - 1;
                $status_mensagem = "Processado Parcial";
                $status_final = 2;
            }
        }
    }

    msg_log("Arquivo enviado para o Tdocs.");
    
    $routineScheduleLog->setRoutineSchedule($routine_schedule_id);
    $routineScheduleLog->setTotalRecord($rows);
    $routineScheduleLog->setTotalRecordProcessed($totalRecordProcessed);
    $routineScheduleLog->setStatus($status_final);
    $routineScheduleLog->setStatusMessage(utf8_encode($status_mensagem));
    $routineScheduleLog->setDateFinish(date("Y-m-d H:i:s"));
    $routineScheduleLog->Update();

} catch (Exception $e) {
    msg_log("Erro: ".$e->getMessage());
    
    $status_final = 2;

    $routineScheduleLog->setStatus($status_final);
    $routineScheduleLog->setStatusMessage(utf8_encode($e->getMessage()));
    $routineScheduleLog->setDateFinish(date("Y-m-d H:i:s"));
    $routineScheduleLog->Update();
}
$phpCron->termino();
?>

