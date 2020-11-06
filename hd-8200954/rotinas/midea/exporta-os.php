<?php
include dirname(__FILE__) . '/../../dbconfig.php';
include dirname(__FILE__) . '/../../includes/dbconnect-inc.php';
require dirname(__FILE__) . '/../funcoes.php';

include dirname(__FILE__) . '/../../classes/Posvenda/Fabrica.php';
include dirname(__FILE__) . '/../../classes/Posvenda/Os.php';
include dirname(__FILE__) . '/../../classes/Posvenda/Fabricas/_169/Os.php';
require_once dirname(__FILE__) . '/../../class/tdocs.class.php';

use Posvenda\Routine;
use Posvenda\RoutineSchedule;
use Posvenda\Log;

date_default_timezone_set('America/Sao_Paulo');
$fabrica = 169;
$data = date('d-m-Y');

class LogExportacao {

    private $os = array();
    private $tdocs;
    private $fabrica;
    private $con;
    private $log_id;

    public function __construct($con, $fabrica, $log_id) {
        $this->fabrica = $fabrica;
        $this->tdocs = new \TDocs($con, $fabrica);
        $this->con = $con;
        $this->log_id = $log_id;
    }

    public function addOS($os) {
        $sql = "
            INSERT INTO tbl_os_auditar (
                os, 
                auditar,
                fabrica
            ) VALUES (
                {$os['os']}, 
                {$this->log_id},
                {$this->fabrica}
            )
        ";
        $res = pg_query($this->con, $sql);
        
        if (strlen(pg_last_error()) > 0) {
            throw new \Exception("TC/LE-AO-001");
        }
        
        $data_abertura = date("d/m/Y", strtotime($os["data_abertura"]));
        
        if (!empty($os["finalizada"])) {
            $data_fechamento = date("d/m/Y", strtotime($os["finalizada"]));
        } else {
            $data_fechamento = null;
        }

        if (!empty($os["data_primeira_integracao"])) {
            $data_primeira_integracao = date("d/m/Y H:i:s", strtotime($os["data_primeira_integracao"]));
        } else {
            $data_primeira_integracao = date("d/m/Y H:i:s");
        }

        $this->os[$os["os"]] = array(
            "os"                            => $os["os"],
            "sua_os"                        => ($os["os"] != $os["sua_os"]) ? $os["sua_os"] : null,
            "data_abertura"                 => $data_abertura,
            "data_fechamento"               => $data_fechamento,
	    "data_primeira_integracao"      => $data_primeira_integracao,
            "horario_exportacao"            => date("d/m/Y H:i:s"),
            "notificacao_status_exportacao" => null,
            "notificacao_erro_exportacao"   => null,
            "notificacao_xml_enviado"       => null,
            "notificacao_xml_recebido"      => null,
            "os_status_exportacao"          => null,
            "os_erro_exportacao"            => null,
            "os_xml_enviado"                => null,
            "os_xml_recebido" => null
        );
    }

    public function setStatusNotificacao($os, $status, $erro = null) {
        $this->os[$os]["notificacao_status_exportacao"] = $status;
        $this->os[$os]["notificacao_erro_exportacao"] = $erro;
    }

    public function setXmlEnviadoNotificacao($os, $xml) {
        $this->os[$os]["notificacao_xml_enviado"] = $xml;
    }

    public function setXmlRecebidoNotificacao($os, $xml) {
        $this->os[$os]["notificacao_xml_recebido"] = $xml;
    }

    public function setStatusOS($os, $status, $erro = null) {
        $this->os[$os]["os_status_exportacao"] = $status;
        $this->os[$os]["os_erro_exportacao"] = $erro;
    }

    public function setXmlEnviadoOS($os, $xml) {
        $this->os[$os]["os_xml_enviado"] = $xml;
    }

    public function setXmlRecebidoOS($os, $xml) {
        $this->os[$os]["os_xml_recebido"] = $xml;
    }

    public function save() {
		system("mkdir -p /tmp/midea");

        $arquivo_log = "/tmp/midea/exporta_os_log_".date("dmYHi")."{$this->fabrica}{$this->log_id}.txt";
        $log = fopen($arquivo_log, "w");
        
        $arquivo_log_simples = "/tmp/midea/exporta_os_log_simples_".date("dmYHi")."{$this->fabrica}{$this->log_id}.txt";
        $log_simples = fopen($arquivo_log_simples, "w");

        fwrite($log, "Exportação de OS - Log - ".date("d/m/Y H:i")."\n\n");
        
        $headers = array(
            "'os'",
            "'os revenda'",
            "'data de abertura'",
            "'data de fechamento'",
            "'data da primeira integração'",
            "'horário exportação'",
            "'status da exportação da notificação'",
            "'erro da exportação da notificação'",
            "'status da exportação da ordem de serviço'",
            "'erro da exportação da ordem de serviço'"
        );
        fwrite($log_simples, implode(";", $headers)."\n");

        foreach ($this->os as $os => $data) {
            $logOS = array(
                "OS: {$data['os']}",
                "OS Revenda: {$data['sua_os']}",
                "Data de Abertura: {$data['data_abertura']}",
                "Data de Fechamento: {$data['data_fechamento']}",
                "Horário de Exportação: {$data['horario_exportacao']}",
                "Status da exportação da Notificação: {$data['notificacao_status_exportacao']}",
                "Erro da exportação da Notificação: {$data['notificacao_erro_exportacao']}",
                "XML enviado da Notificação:\n{$data['notificacao_xml_enviado']}",
                "XML recebido da Notificação:\n{$data['notificacao_xml_recebido']}",
                "Status da exportação da OS: {$data['os_status_exportacao']}",
                "Erro da exportação da OS: {$data['os_erro_exportacao']}",
                "XML enviado da OS:\n{$data['os_xml_enviado']}",
                "XML recebido da OS:\n{$data['os_xml_recebido']}",
                "\n"
            );
            fwrite($log, implode("\n", $logOS));

	    if ($data['notificacao_status_exportacao'] != 'exportado' || $data['os_status_exportacao'] != 'exportado') {

            	$logSimplesOS = array(
                    "'{$data['os']}'",
                    "'{$data['sua_os']}'",
                    "'{$data['data_abertura']}'",
                    "'{$data['data_fechamento']}'",
                    "'{$data['data_primeira_integracao']}'",
                    "'{$data['horario_exportacao']}'",
                    "'{$data['notificacao_status_exportacao']}'",
                    "'{$data['notificacao_erro_exportacao']}'",
                    "'{$data['os_status_exportacao']}'",
                    "'{$data['os_erro_exportacao']}'"
                );
		fwrite($log_simples, implode(";", $logSimplesOS)."\n");
	    }
        }

        fclose($log);
        fclose($log_simples);

        $this->tdocs->setContext("fabrica", "log");
        if(!$this->tdocs->uploadFileS3($arquivo_log, $this->log_id)){
            throw new \Exception("Não foi possível enviar o arquivo de log para o Tdocs. Erro: ".$this->tdocs->error);
        } else {
            system("rm -f {$arquivo_log}");
        }

        $this->tdocs->setContext("fabrica", "logsimples");
        if(!$this->tdocs->uploadFileS3($arquivo_log_simples, $this->log_id, false)){
            throw new \Exception("Não foi possível enviar o arquivo de log para o Tdocs. Erro: ".$this->tdocs->error);
        } else {
            system("rm -f {$arquivo_log_simples}");
        }
    }
}

try {
    $routine = new Routine();
    $routine->setFactory($fabrica);

    $arr = $routine->SelectRoutine("Exporta OS");
    $routine_id = $arr[0]["routine"];

    $routineSchedule = new RoutineSchedule();
    $routineSchedule->setRoutine($routine_id);
    $routineSchedule->setWeekDay(date("w"));

    $routine_schedule_id = $routineSchedule->SelectRoutineSchedule();

    if (!strlen($routine_schedule_id)) {
        throw new \Exception("Agendamento da rotina não encontrado");
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

    $routineScheduleLog->setRoutineSchedule(null);
    $routineScheduleLog->setRoutineScheduleLog(null);
    $routineScheduleLog->setDateFinish(null);
    $routineScheduleLog->setStatus(null);
    $routineScheduleLog->setStatusMessage(null);

    if ($routineScheduleLog->SelectRoutineWithoutFinish($fabrica, $routine_id) === true && $em_execucao == true) {
        throw new \Exception('Rotina em execução');
    } else {
        $routineScheduleLog->setRoutineSchedule((integer) $routine_schedule_id);
        $routineScheduleLog->setDateStart(date("Y-m-d H:i"));

        if (!$routineScheduleLog->Insert()) {
           throw new \Exception("Erro ao gravar log da rotina");
        }

        $routine_schedule_log_id = $routineScheduleLog->SelectId();
        $routineScheduleLog->setRoutineScheduleLog($routine_schedule_log_id);
    }
    
    $LogExportacao = new LogExportacao($con, $fabrica, $routine_schedule_log_id);

    $phpCron = new PHPCron($fabrica, __FILE__);
    $phpCron->inicio();

    $osFabricaClass = new \Posvenda\Fabricas\_169\Os($fabrica, null, $con);

    $dados = $osFabricaClass->getOsPendenteExportacao();
    $total_dados = count($dados);
    $total_dados_sucesso = 0;

    foreach ($dados as $key => $value) {
        $erro_notificacao = false;

        try {
            $LogExportacao->addOS($value);

            try {
                $notaIntegracao = $osFabricaClass->getDadosNotaExport($value["os"]);
                $osFabricaClass->exportNotificacao($notaIntegracao, $LogExportacao);
            } catch(\Exception $e) {
                if ($e->getCode() == 200) {
                    $LogExportacao->setStatusNotificacao($value["os"], "exportado", $e->getMessage());
                } else {
                    $erro_notificacao = true;
                    throw new \Exception($e->getMessage());
                }
            }

            $osIntegracao = $osFabricaClass->getDadosOSExport($value["os"]);
            $osFabricaClass->exportOS($osIntegracao, $LogExportacao);

            $total_dados_sucesso++;
        } catch (\Exception $e) {
            if ($erro_notificacao == true) {
                $LogExportacao->setStatusNotificacao($value["os"], "erro", $e->getMessage());
            } else {
                $LogExportacao->setStatusOS($value["os"], "erro", $e->getMessage());
            }

            continue;
        }
    }

    $routineScheduleLog->setTotalRecord($total_dados);
    $routineScheduleLog->setTotalRecordProcessed($total_dados_sucesso);
    $routineScheduleLog->setStatus(1);
    $routineScheduleLog->setStatusMessage("Rotina finalizada com sucesso");

    $routineScheduleLog->setDateFinish(date("Y-m-d H:i"));
    $routineScheduleLog->Update();
    $phpCron->termino();

    $LogExportacao->save();
} catch (\Exception $e) {
    mail('maicon.luiz@telecontrol.com.br, waldir@telecontrol.com.br, francisco.ambrozio@telecontrol.com.br', 'Exportação de OS Midea/Carrier - Erro na execução da rotina', $e->getMessage());
}
