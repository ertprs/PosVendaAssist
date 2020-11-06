<?php

require __DIR__.'/../../classes/autoload.php';

use Posvenda\Cockpit;
use Posvenda\Routine;
use Posvenda\RoutineSchedule;
use Posvenda\Log;

$routine = new Routine();
$routine->setFactory(158);

$arr = $routine->SelectRoutine("Desagendar OS reagendada não finalizada");
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

    $reagendar_os = false;
    $array_os = $cockpit->osAgendadaNaoFinalizada($reagendar_os);

    if (count($array_os) > 0) {
        foreach ($array_os as $os) {
            //Reagenda na persys
            $res = $cockpit->desagendarOs($os["id_externo"]);

            if ($res != true) {
                throw new Exception("Erro ao desagendar a OS {$os['os']}: {$persys['error']['message']}");
            }

            //Deleta agenda na telecontrol
            $cockpit->deletarAgenda($os["tecnico_agenda"]);
        }
    }

    $routineScheduleLog->setStatus(1);
    $routineScheduleLog->setStatusMessage("Rotina finalizada com sucesso");
    $routineScheduleLog->setDateFinish(date("Y-m-d H:i"));
    $routineScheduleLog->Update();
} catch(Exception $e) {
    echo $e->getMessage();
    $routineScheduleLog->setStatus(0);
    $routineScheduleLog->setStatusMessage("Erro ao executar a rotina, erro: {$e->getMessage()}");
    $routineScheduleLog->setDateFinish(date("Y-m-d H:i"));
    $routineScheduleLog->Update();
}