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
    $arr = $routine->SelectRoutine("Status Os");
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
    $processos      = explode("\n", shell_exec("ps aux | grep {$arquivo_rotina}"));
    $arquivo_rotina = str_replace(".", "\\.", $arquivo_rotina);
    $count_routine = 0;
    
    foreach ($processos as $value) {
        if (preg_match("/(.*)php (.*)\/midea\/{$arquivo_rotina}/", $value)) {
            $count_routine += 1;
        }
    }
    
    $em_execucao = ($count_routine > 4) ? true : false;
    
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

    $client = new SoapClient($urlWSDL, array('trace' => 1,'connection_timeout' => 1000,'cache_wsdl' => WSDL_CACHE_NONE));
    msg_log('Inicia consulta SQL na tabela tbl_pedido');

    $data = '2019-05-06';

    $sql = "
        SELECT DISTINCT
            tbl_os.os,
            tbl_os.os_posto
        FROM tbl_os
LEFT JOIN tbl_os_campo_extra USING(os,fabrica)

        WHERE tbl_os.fabrica = {$login_fabrica}
    	AND tbl_os.status_checkpoint = 48
    	AND TRIM(tbl_os.os_posto) != ''
        AND tbl_os.data_abertura >= '{$data} 00:00'
AND excluida is null
AND json_field('status_atualiza', campos_adicionais) = '';
    ";

    $res  = pg_query($con, $sql);
    $rows = pg_num_rows($res);
    msg_log("Iniciando consulta em $rows linhas");
    if ($rows > 0) {

        foreach (pg_fetch_all($res) as $key => $linha) {
            try {

                $xos = $linha['os'];
                $xos_sap = trim($linha['os_posto']);

                msg_log('Iniciando requisição à API');
                
                $request = array(
                    'I_NR_OS' => $xos_sap
                );

                $result    = $client->Z_CB_TC_CONSULTA_STATUS_OS($request);
                $dados_xml = $result->Z_CB_TC_CONSULTA_STATUS_OSResult->any;
                $xml = simplexml_load_string($dados_xml);
                $xml = json_decode(json_encode((array)$xml), TRUE);

                $dadosStatus = array();
                if (!empty($xml['NewDataSet']['T_STATUSTABLE'])) {
                    $dadosStatus = $xml['NewDataSet']['T_STATUSTABLE'];
                }

                $statusSAP = '';
                foreach ($dadosStatus as $resStatus) {
                    if ($resStatus['USER_STATUS_CODE'] == 'NPGT') {
                        $statusSAP = 'NPGT';
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
                    }

		    $sqlStatus = "SELECT fn_os_status_checkpoint_os({$xos});";
                    $resStatus = pg_query($con, $sqlStatus);

                    if (strlen(pg_last_error()) > 0) {
			$oLogError->setContents($sqlStatus);
                        throw new Exception("Ocorreu um erro atualizando dados da OS #002");
                    } else {
                        $status_checkpoint = pg_fetch_result($resStatus, 0, 0);
			$updStatus = "UPDATE tbl_os SET status_checkpoint = {$status_checkpoint} WHERE os = {$xos};";
                        pg_query($con, $updStatus);
                        if (strlen(pg_last_error()) > 0) {
			    $oLogError->setContents($updStatus);
                            throw new Exception("Ocorreu um erro atualizando dados da OS #003");
                        }
                    }
                    pg_query($con, "COMMIT;");
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

