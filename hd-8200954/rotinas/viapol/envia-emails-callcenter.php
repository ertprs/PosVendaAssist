<?php
require dirname(__FILE__) . '/../../dbconfig.php';
require dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../../class/tdocs.class.php';
require dirname(__FILE__) . '/../funcoes.php';
require dirname(__FILE__) .'/../../class/communicator.class.php';

include_once __DIR__.'/../../classes/autoload.php';
use Posvenda\Routine;
use Posvenda\RoutineSchedule;
use Posvenda\Log;
use Posvenda\LogError;

$login_fabrica = 189;
$tdocs = new TDocs($con, $login_fabrica, 'rotina');

/* Inicio Processo */
$phpCron = new PHPCron($login_fabrica, __FILE__);
$phpCron->inicio();

ob_start();

function msg_log($msg){
    echo "\n".date('H:i:s')." - $msg";
}

try {

    msg_log('Inicia rotina de envio de emails callcenter');
    $routine = new Routine();
    $routine->setFactory($login_fabrica);

    $arr = $routine->SelectRoutine("Envio de Emails Callcenter");
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

    //$data_teste = '2019-06-28 23:35:00';

    $sql = "
        SELECT 
            dados.*,
            (current_timestamp BETWEEN dados.data_metade_providencia AND dados.data_fim_providencia) as envia_email_metade_providencia,
            (current_timestamp > dados.data_fim_providencia AND extract(minute from CURRENT_TIME(0)) BETWEEN 0 AND 30) as envia_email_atraso_providencia
        FROM (
            SELECT 
                hc.hd_chamado,
                hml.descricao,
                hml.texto_email,
                hml.prazo_horas,
                hc.data_providencia - CAST((hml.prazo_horas * 3600) || ' seconds' AS interval) as data_inicio_providencia,
                hc.data_providencia - CAST((hml.prazo_horas * 3600) / 2 || ' seconds' AS interval) as data_metade_providencia,
		hc.data_providencia as data_fim_providencia,
		ad.email
            FROM tbl_hd_chamado hc
            JOIN tbl_hd_chamado_extra hce USING(hd_chamado)
	    JOIN tbl_hd_motivo_ligacao hml USING(hd_motivo_ligacao,fabrica)
	    JOIN tbl_admin ad ON hc.atendente = ad.admin
            WHERE hc.fabrica = {$login_fabrica}
            AND hce.hd_motivo_ligacao IS NOT NULL
            AND LOWER(hc.status) = 'aberto'
        ) dados;
    ";
    $res = pg_query($con, $sql);

    while ($dados = pg_fetch_object($res)) {
	    
    	$destinatarios = array($dados->email,"luis.carlos@telecontrol.com.br","felipe.marttos@telecontrol.com.br");
	$mailTc = new TcComm('smtp@posvenda');

        if ($dados->envia_email_metade_providencia == 't') {

            $res = $mailTc->sendMail(
		$destinatarios,    
		$dados->descricao." - ".$dados->hd_chamado,
                $dados->texto_email,
                "noreply@telecontrol.com.br"
            );

        } else if ($dados->envia_email_atraso_providencia == 't') {

            $res = $mailTc->sendMail(
                $destinatarios,
                $dados->descricao." - ".$dados->hd_chamado,
                "Providência em atraso!",
                "noreply@telecontrol.com.br"
            );

        }


    }

    $nome_arquivo = 'envia-emails-callcenter-'.date('Ymd').'.txt';
    $arquivo_log  = "$nome_arquivo";

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

    if(!$tdocs->uploadFileS3($arquivo, $routine_id)){
        throw new Exception("Não foi possí­vel enviar o arquivo de log para o Tdocs. Erro: ".$tdocs->error);
    }

    msg_log("Arquivo enviado para o Tdocs.");
    
    if (!isset($status_final)) {
        $routineScheduleLog->setStatus(1);
        $routineScheduleLog->setStatusMessage('Rotina finalizada');
        $routineScheduleLog->Update();
    }
    
} catch (Exception $e) {
    msg_log("Erro: ".$e->getMessage());
    $logError = new \Posvenda\LogError();
    $logError->setRoutineScheduleLog($routineScheduleLog->SelectId());
    $logError->setErrorMessage($e->getMessage());
    $logError->Insert();

    $status_final = 2;

    $routineScheduleLog->setStatus($status_final);
    $routineScheduleLog->setStatusMessage(utf8_encode($e->getMessage()));
    $routineScheduleLog->Update();

}

$phpCron->termino();
?>
