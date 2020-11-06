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

$login_fabrica = 104;
$fabrica_nome = "Vonder";
$tdocs = new TDocs($con, $login_fabrica, 'rotina');
$status_final = 1;
$status_mensagem = 'Rotina Finalizada';

/* Inicio Processo */
$phpCron = new PHPCron($login_fabrica, __FILE__);
$phpCron->inicio();
ob_start();
function msg_log($msg){
    echo "\n".date('H:i:s')." - $msg";
}

try {

    msg_log('Inicia rotina de exportação de peça alternativa');

    $routine = new Routine();
    $routine->setFactory($login_fabrica);

    $arr = $routine->SelectRoutine("Dado Mestre - Peca Alternativa");
    $routine_id = $arr[0]["routine"];

    /*$routineSchedule = new RoutineSchedule();
    $routineSchedule->setRoutine($routine_id);
    $routineSchedule->setWeekDay(date("w"));

    $routine_schedule_id = $routineSchedule->SelectRoutineSchedule();

    if (!strlen($routine_schedule_id)) {
        throw new Exception("Agendamento da rotina não encontrado");
    }

    $routineScheduleLog = new Log();
    $oLogError          = new LogError();*/

    $arquivo_rotina = basename($_SERVER["SCRIPT_FILENAME"]);
    $processos      = explode("\n", shell_exec("ps aux | grep {$arquivo_rotina}"));
    $arquivo_rotina = str_replace(".", "\\.", $arquivo_rotina);

    $count_routine = 0;
    foreach ($processos as $value) {
        if (preg_match("/(.*)php (.*)\/vonder\/{$arquivo_rotina}/", $value)) {
            $count_routine += 1;
        }
    }

    $em_execucao = ($count_routine > 4) ? true : false;

    /*if ($routineScheduleLog->SelectRoutineWithoutFinish($login_fabrica, $routine_id) === true && $em_execucao == false) {

        $routineScheduleLog->setRoutineSchedule($routine_schedule_id);
        $routine_schedule_log_stopped = $routineScheduleLog->GetRoutineWithoutFinish();

        $routineScheduleLog->setRoutineScheduleLog($routine_schedule_log_stopped['routine_schedule_log']);
        $routineScheduleLog->setDateFinish(date("Y-m-d H:i:s"));
        $routineScheduleLog->setStatus(1);
        $routineScheduleLog->setStatusMessage(utf8_encode($status_mensagem));
        $routineScheduleLog->Update();
        msg_log('Finalizou rotina anterior Schedule anterior. Rotina cod: '.$routine_id);
    }

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

    }*/

    if ($_serverEnvironment == 'development') {
	$urlWSDL = "http://ws.carrieronline.com.br/qa6/PSA_WebService/telecontrol.asmx?WSDL";
    } else {
        $urlWSDL = "http://ws.carrieronline.com.br/wsPSATeleControl/telecontrol.asmx?WSDL";
    }
	
    $client = new SoapClient($urlWSDL, array('trace' => 1,'connection_timeout' => 180));

    $diasExecucao = "1 day";
    if (date('w') == 1) {
	$diasExecucao = "2 days";
    }

    $sqlAlternativa = "
        SELECT DISTINCT
            *
        FROM (
            SELECT
                de,
                para,
                peca_de,
                peca_para,
                status
            FROM tbl_peca_alternativa
            WHERE fabrica = {$login_fabrica}
            AND (data_modificacao::DATE = CURRENT_DATE - INTERVAL '{$diasExecucao}'
            OR data_input::DATE = CURRENT_DATE - INTERVAL '{$diasExecucao}')
            UNION
            SELECT
                x.para AS de,
                pa.para,
                x.peca_para AS peca_de,
                pa.peca_para,
                x.status
            FROM tbl_peca_alternativa pa
            JOIN (
                SELECT DISTINCT
                    de,
                    para,
                    peca_de,
                    peca_para,
                    status
                FROM tbl_peca_alternativa
                WHERE fabrica = {$login_fabrica}
                AND (data_modificacao::DATE = CURRENT_DATE - INTERVAL '{$diasExecucao}'
                OR data_input::DATE = CURRENT_DATE - INTERVAL '{$diasExecucao}')
            ) x ON x.peca_de = pa.peca_de
            WHERE pa.fabrica = {$login_fabrica}
            AND pa.peca_para != x.peca_para
            UNION
            SELECT
                para AS de,
                de AS para,
                peca_para AS peca_de,
                peca_de AS peca_para,
                status
            FROM tbl_peca_alternativa
            WHERE fabrica = {$login_fabrica}
            AND (data_modificacao::DATE = CURRENT_DATE - INTERVAL '{$diasExecucao}'
            OR data_input::DATE = CURRENT_DATE - INTERVAL '{$diasExecucao}')
        ) x
        ORDER BY de;
    ";

    die(nl2br($sqlAlternativa));

    $resAlternativa = pg_query($con, $sqlAlternativa);
    $count = pg_num_rows($resAlternativa);

    $totalRecordProcessed = $count;

    if ($count > 0) {
    	for ($i = 0; $i < $count; $i++) {

	    $request = array(
	    	'I_PECA_ORIGINAL'    => pg_fetch_result($resAlternativa, $i, 'para'),
	    	'I_PECA_ALTERNATIVA' => pg_fetch_result($resAlternativa, $i, 'de'),
	    	'I_STATUS'           => ((pg_fetch_result($resAlternativa, $i, 'status') == 't') ? 'A' : 'I')
	    );

	    $result = $client->Z_CB_TC_PECA_ALTERNATIVA($request);
	    $msg_retorno = $result->Z_CB_TC_PECA_ALTERNATIVAResult;
    
	    if (strpos($msg_retorno, 'sucesso') === false) {
                $oLogError->setRoutineScheduleLog($routine_schedule_log_id);
                $oLogError->setLineNumber($i);
                $oLogError->setContents(json_encode($request));
                $oLogError->setErrorMessage(utf8_encode($msg_retorno));

                $oLogError->Insert();
                $totalRecordProcessed = $totalRecordProcessed - 1;
                $status_mensagem = "Processado Parcial";
                $status_final = 2;
            }
	    msg_log("Retorno Webservice: {$msg_retorno}");
	}
    }

    $nome_arquivo = 'rotina-exporta-peca-alternativa-'.date('Ymd').'.txt';
    $arquivo_log  = "/tmp/vonder/$nome_arquivo";

    msg_log("Enviando arquivo para o Tdocs: $nome_arquivo");

    $arquivo = array(
        'tmp_name' => $arquivo_log,
        'name'     => $nome_arquivo,
        'size'     => filesize($arquivo_log),
        'type'     => mime_content_type($arquivo_log),
        'error'    => null
    );

    if (!file_exists($arquivo_log)) {
        system("touch {$arquivo_log}");
    }

    $b = ob_get_contents();

    file_put_contents($arquivo_log, $b, FILE_APPEND);
    ob_end_flush();
    ob_clean();

    if (!$tdocs->uploadFileS3($arquivo, $routine_id)) {
        throw new Exception("Não foi possí­vel enviar o arquivo de log para o Tdocs. Erro: ".$tdocs->error);
    }

	msg_log("Arquivo enviado para o Tdocs.");

    $routineScheduleLog->setRoutineSchedule($routine_schedule_id);
    $routineScheduleLog->setTotalRecord($count);
    $routineScheduleLog->setTotalRecordProcessed($totalRecordProcessed);
    $routineScheduleLog->setStatus($status_final);
    $routineScheduleLog->setStatusMessage(utf8_encode($status_mensagem));
    $routineScheduleLog->setDateFinish(date("Y-m-d H:i:s"));
    $routineScheduleLog->Update();
	
} catch (Exception $e) {
    msg_log("Erro: ".$e->getMessage());

    $status_final = 2;

    //$routineScheduleLog->setStatus($status_final);
    //$routineScheduleLog->setStatusMessage(utf8_encode($e->getMessage()));
    //$routineScheduleLog->setDateFinish(date("Y-m-d H:i:s"));
    //$routineScheduleLog->Update();

}

$phpCron->termino();
?>