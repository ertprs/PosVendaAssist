<?php

require __DIR__.'/../../classes/autoload.php';

use Posvenda\Cockpit;
use Posvenda\Routine;
use Posvenda\RoutineSchedule;
use Posvenda\Log;

date_default_timezone_set("America/Sao_Paulo");

$periodo = $argv[1];

if ($_serverEnvironment == "production" && !strlen($periodo)) {
        exit;
}

$routine = new Routine();
$routine->setFactory(158);

$arr = $routine->SelectRoutine("Historico de Deslocamento dos Tecnicos");
$routine_id = $arr[0]["routine"];

if (!strlen($routine_id)) {
    throw new Exception("Rotina não encontrada");
}

$routineSchedule = new RoutineSchedule();
$routineSchedule->setRoutine($routine_id);
$routineSchedule->setWeekDay(date("w"));

$routine_schedule_id = $routineSchedule->SelectRoutineSchedule();

if (!strlen($routine_schedule_id)) {
    throw new Exception("Agendamento da rotina não encontrado");
}

$routineScheduleLog = new Log();
$routineScheduleLog->setRoutineSchedule((integer) $routine_schedule_id);
$routineScheduleLog->setDateStart(date("Y-m-d H:i"));

if (!$routineScheduleLog->Insert()) {
    throw new Exception("Erro ao gravar log da rotina");
}

$routine_schedule_log_id = $routineScheduleLog->SelectId();
$routineScheduleLog->setRoutineScheduleLog($routine_schedule_log_id);

try {
    $cockpit = new Cockpit(158);

    $tecnicos = $cockpit->getTecnicos();

    if (count($tecnicos) > 0) {
        if ($_serverEnvironment == "production") {
            if ($periodo == 0) {
                $data_inicial = date("c", strtotime(date("Y-m-d 12:00:00", strtotime("-1 day"))));
                $data_final   = date("c", strtotime(date("Y-m-d 23:59:59", strtotime("-1 day"))));
            } else if ($periodo == 12) {
                $data_inicial = date("c", strtotime(date("Y-m-d 00:00:00", strtotime("-1 day"))));
                $data_final   = date("c", strtotime(date("Y-m-d 11:59:59", strtotime("-1 day"))));
            }
        } else {
            $data_inicial = date("c", strtotime(date("Y-m-d 00:00:00")));
            $data_final   = date("c", strtotime(date("Y-m-d 23:59:59")));
        }

        echo "\n";
        echo "Iniciando...\n";

        foreach ($tecnicos as $tecnico) {
            echo "Técnico: ".$tecnico["codigo_externo"]."\n";

            if (empty($tecnico["codigo_externo"])) {
                continue;
            }

            $id_externo = $cockpit->getTecnicoIdExterno($tecnico["codigo_externo"]);

            if (empty($id_externo)) {
                continue;
            }

            $historico = $cockpit->getTecnicoHistoricoDeslocamento($id_externo, $data_inicial, $data_final);

            if (count($historico) > 0) {
                foreach ($historico as $dados) {
                    if ($dados["latitude"] == 0 || $dados["longitude"] == 0) {
                        continue;
                    }

                    echo ".";

                    $cockpit->insertTecnicoMonitoramento(158, $tecnico["tecnico"], $dados["latitude"], $dados["longitude"], "rede", $dados["created_at"]);
                }
            }

            echo "\n";
        }
    }

    $routineScheduleLog->setStatus(1);
    $routineScheduleLog->setStatusMessage("Rotina finalizada com sucesso");
    $routineScheduleLog->setDateFinish(date("Y-m-d H:i"));
    $routineScheduleLog->Update();
} catch(Exception $e) {
    $routineScheduleLog->setStatus(0);
    $routineScheduleLog->setStatusMessage("Erro ao executar a rotina, erro: {$e->getMessage()}");
    $routineScheduleLog->setDateFinish(date("Y-m-d H:i"));
    $routineScheduleLog->Update();
}
