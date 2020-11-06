<?php

require __DIR__.'/../../classes/autoload.php';

use Posvenda\Cockpit;
use Posvenda\Routine;
use Posvenda\RoutineSchedule;
use Posvenda\Log;

$tecnico = $argv[1];
$data    = $argv[2];

date_default_timezone_set("America/Sao_Paulo");

$routine = new Routine();
$routine->setFactory(158);

$arr = $routine->SelectRoutine("Ordenação das Ordens de Serviço");
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

    $tecnicos = $cockpit->getTecnicos($tecnico);

    if (count($tecnicos) > 0) {
        if (is_null($data)) {
            $data = date("Y-m-d");
        }

        foreach ($tecnicos as $tecnico) {
            if (!empty($tecnico["codigo_externo"])) {
                $atendimentos = $cockpit->getAtendimentos($tecnico["tecnico"], $data);

                if (!count($atendimentos)) {
                    continue;
                }

                $cockpit->atualizaOrdenacaoOSMobile($tecnico, $data);
            }
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